<?php

namespace App\Services;

use App\Models\SubscriptionPackage;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use App\Models\VendorSubscriptionLog;
use App\Models\User;
use App\Notifications\Subscription\SubscriptionActivatedNotification;
use App\Notifications\Subscription\SubscriptionExpiredNotification;
use App\Notifications\Subscription\SubscriptionGracePeriodNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VendorSubscriptionService
{
    // ── Query ────────────────────────────────────────────────────────────

    public function getCurrent(Vendor $vendor): ?VendorSubscription
    {
        // Prioritaskan kolom current_subscription_id
        if ($vendor->current_subscription_id) {
            $sub = $vendor->currentSubscription()->with('package')->first();
            if ($sub) return $sub;
        }

        // Fallback: cari subscription aktif dari tabel (vendor lama / data tidak konsisten)
        $sub = $vendor->subscriptions()
            ->with('package')
            ->whereIn('status', ['active', 'grace_period', 'pending_payment'])
            ->latest('started_at')
            ->first();

        // Auto-repair: sinkronkan current_subscription_id jika ditemukan
        if ($sub && $sub->status === 'active') {
            $vendor->update(['current_subscription_id' => $sub->id]);
        }

        return $sub;
    }

    // ── Lock-In Check ────────────────────────────────────────────────────

    /**
     * Cek apakah vendor BOLEH pilih paket target sekarang.
     * Return ['allowed' => bool, 'reason' => string|null, 'mode' => 'fresh'|'upgrade'|'renewal'|'blocked']
     */
    public function canChoosePackage(Vendor $vendor, SubscriptionPackage $target): array
    {
        $current = $this->getCurrent($vendor);

        // 1. Belum punya / expired_locked / cancelled_admin → boleh apa saja
        if (!$current || in_array($current->status, ['expired_locked', 'cancelled_admin'])) {
            return ['allowed' => true, 'mode' => 'fresh', 'reason' => null];
        }

        // 2. Grace period → boleh apa saja (renewal)
        if ($current->status === 'grace_period') {
            return ['allowed' => true, 'mode' => 'renewal', 'reason' => null];
        }

        // 3. Pending payment → tidak boleh pilih lain
        if ($current->status === 'pending_payment') {
            return [
                'allowed' => false,
                'mode'    => 'blocked',
                'reason'  => 'Anda punya pembayaran tertunda untuk paket ' . $current->package->name . '. Selesaikan atau tunggu kedaluwarsa (24 jam).',
            ];
        }

        // 4. Active
        if ($current->status === 'active') {
            // Free package tidak ada expiry — boleh upgrade kapan saja
            if (!$current->expires_at) {
                if ($target->rank > $current->package->rank) {
                    return ['allowed' => true, 'mode' => 'upgrade', 'reason' => null];
                }
                if ($target->id === $current->package_id) {
                    return ['allowed' => false, 'mode' => 'blocked', 'reason' => 'Anda sudah berlangganan paket ' . $current->package->name . '.'];
                }
                // Downgrade dari free ke free — tidak ada
                return ['allowed' => true, 'mode' => 'fresh', 'reason' => null];
            }

            $sevenDaysBeforeExpiry = $current->expires_at->copy()->subDays(7);

            // 4a. Dalam window 7 hari sebelum expiry → boleh apa saja (renewal cycle berikutnya)
            if (now()->gte($sevenDaysBeforeExpiry)) {
                return ['allowed' => true, 'mode' => 'renewal', 'reason' => null];
            }

            // 4b. Target sama → tidak perlu
            if ($target->id === $current->package_id) {
                return [
                    'allowed' => false,
                    'mode'    => 'blocked',
                    'reason'  => 'Anda sudah berlangganan paket ' . $current->package->name . '. Aktif sampai ' . $current->expires_at->format('d M Y') . '.',
                ];
            }

            // 4c. Upgrade → boleh dengan proration
            if ($target->rank > $current->package->rank) {
                return ['allowed' => true, 'mode' => 'upgrade', 'reason' => null];
            }

            // 4d. Downgrade → BLOCKED
            return [
                'allowed' => false,
                'mode'    => 'blocked',
                'reason'  => sprintf(
                    'Paket %s Anda masih aktif sampai %s. Downgrade ke paket %s hanya bisa dilakukan setelah masa aktif berakhir.',
                    $current->package->name,
                    $current->expires_at->format('d M Y'),
                    $target->name,
                ),
            ];
        }

        return ['allowed' => false, 'mode' => 'blocked', 'reason' => 'Status subscription tidak valid.'];
    }

    // ── Purchase ─────────────────────────────────────────────────────────

    /**
     * Buat subscription baru. Free langsung aktif, berbayar → pending_payment.
     * Throw SubscriptionLockedException jika tidak boleh.
     */
    public function purchase(Vendor $vendor, SubscriptionPackage $target, string $paymentMethod = 'manual'): VendorSubscription
    {
        return DB::transaction(function () use ($vendor, $target, $paymentMethod) {
            $check = $this->canChoosePackage($vendor, $target);

            if (!$check['allowed']) {
                throw new \RuntimeException($check['reason']);
            }

            $current = $this->getCurrent($vendor);

            // Hitung proration kalau upgrade
            $amount          = $target->price_per_month;
            $prorationCredit = 0;

            if ($check['mode'] === 'upgrade' && $current) {
                $prorationCredit = $this->calculateProration($current, $target);
                $amount          = max(0, $amount - $prorationCredit);
            }

            // Free → langsung aktif
            if ($target->price_per_month === 0) {
                // Cancel subscription lama jika ada
                if ($current && $current->status === 'active') {
                    $current->update([
                        'status'        => 'cancelled_by_upgrade',
                        'cancel_reason' => 'Pindah ke paket Free',
                    ]);
                    $this->log($current, 'cancelled_by_upgrade', $current->status, 'cancelled_by_upgrade');
                }

                $sub = VendorSubscription::create([
                    'uuid'                => Str::uuid(),
                    'vendor_id'           => $vendor->id,
                    'package_id'          => $target->id,
                    'status'              => 'active',
                    'started_at'          => now(),
                    'expires_at'          => null, // Free tidak ada expiry
                    'grace_until'         => null,
                    'amount_paid'         => 0,
                    'snapshot_features'   => $target->features,
                    'snapshot_commission' => $target->commission_rate,
                    'paid_at'             => now(),
                    'payment_method'      => 'free',
                ]);

                $vendor->update(['current_subscription_id' => $sub->id]);
                $this->log($sub, 'created_free', null, 'active');

                // Pulihkan mobil yang sempat di-suspend karena expired, lalu enforce limit Free
                $this->restoreVendorCars($vendor);
                $this->enforceCarLimit($vendor, $target);

                return $sub;
            }

            // Berbayar → pending_payment
            $sub = VendorSubscription::create([
                'uuid'                => Str::uuid(),
                'vendor_id'           => $vendor->id,
                'package_id'          => $target->id,
                'status'              => 'pending_payment',
                'amount_paid'         => 0,
                'proration_credit'    => $prorationCredit,
                'snapshot_features'   => $target->features,
                'snapshot_commission' => $target->commission_rate,
                'payment_method'      => $paymentMethod,
            ]);

            $this->log($sub, 'created_pending', null, 'pending_payment', [
                'amount'          => $amount,
                'proration_credit' => $prorationCredit,
                'mode'            => $check['mode'],
            ]);

            return $sub;
        });
    }

    // ── Activate (setelah bayar) ──────────────────────────────────────────

    public function activate(VendorSubscription $sub, array $paymentMeta = []): void
    {
        DB::transaction(function () use ($sub, $paymentMeta) {
            if ($sub->status !== 'pending_payment') {
                throw new \LogicException('Subscription bukan pending_payment, status: ' . $sub->status);
            }

            // Idempotent: jika sudah paid, skip
            if ($sub->paid_at) return;

            $vendor          = $sub->vendor;
            $oldSubscription = $this->getCurrent($vendor);

            // Cancel paket lama (upgrade)
            if ($oldSubscription && $oldSubscription->id !== $sub->id) {
                $oldSubscription->update([
                    'status'        => 'cancelled_by_upgrade',
                    'cancel_reason' => 'Upgrade ke ' . $sub->package->name,
                ]);
                $this->log($oldSubscription, 'cancelled_by_upgrade', 'active', 'cancelled_by_upgrade');
            }

            $now = now();
            $sub->update([
                'status'            => 'active',
                'started_at'        => $now,
                'expires_at'        => $now->copy()->addDays(30),
                'grace_until'       => $now->copy()->addDays(37),
                'paid_at'           => $now,
                'amount_paid'       => $paymentMeta['amount'] ?? $sub->package->price_per_month,
                'payment_reference' => $paymentMeta['reference'] ?? null,
            ]);

            $vendor->update(['current_subscription_id' => $sub->id]);
            $this->log($sub, 'activated', 'pending_payment', 'active', $paymentMeta);

            // Pulihkan mobil yang sempat di-suspend karena expired,
            // lalu enforce limit paket baru (relevan saat renewal ke paket lebih kecil)
            $this->restoreVendorCars($vendor);
            $this->enforceCarLimit($vendor, $sub->package);

            // Notifikasi
            try {
                $vendor->user->notify(new SubscriptionActivatedNotification($sub));
            } catch (\Throwable) {}
        });
    }

    // ── Proration ────────────────────────────────────────────────────────

    public function calculateProration(VendorSubscription $current, SubscriptionPackage $target): int
    {
        if ($current->status !== 'active') return 0;
        if (!$current->expires_at) return 0;

        $remainingDays = max(0, (int) now()->diffInDays($current->expires_at, false));
        $perDay        = $current->package->price_per_month / 30;

        return (int) round($remainingDays * $perDay);
    }

    public function calculateUpgradeAmount(VendorSubscription $current, SubscriptionPackage $target): int
    {
        $credit = $this->calculateProration($current, $target);
        return max(0, $target->price_per_month - $credit);
    }

    // ── Expire Jobs ──────────────────────────────────────────────────────

    public function moveToGracePeriod(VendorSubscription $sub): void
    {
        if ($sub->status !== 'active') return;

        $sub->update(['status' => 'grace_period']);
        $this->log($sub, 'expired_to_grace', 'active', 'grace_period');

        // Grace period: mobil tetap tampil, vendor masih bisa terima booking

        try {
            $sub->vendor->user->notify(new SubscriptionGracePeriodNotification($sub));
        } catch (\Throwable) {}
    }

    public function moveToExpiredLocked(VendorSubscription $sub): void
    {
        if ($sub->status !== 'grace_period') return;

        $sub->update(['status' => 'expired_locked']);
        $sub->vendor->update(['current_subscription_id' => null]);

        // Sembunyikan semua mobil vendor dari pencarian publik
        $sub->vendor->cars()
            ->where('status', 'published')
            ->update([
                'status'               => 'suspended',
                'unavailability_reason' => 'subscription_expired',
                'unavailability_notes'  => 'Paket berlangganan vendor telah berakhir.',
            ]);

        $this->log($sub, 'expired_locked', 'grace_period', 'expired_locked');

        try {
            $sub->vendor->user->notify(new SubscriptionExpiredNotification($sub));
        } catch (\Throwable) {}
    }

    public function expireDueSubscriptions(): void
    {
        // active → grace_period
        VendorSubscription::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with(['vendor.user', 'package'])
            ->cursor()
            ->each(fn ($sub) => $this->moveToGracePeriod($sub));

        // grace_period → expired_locked
        VendorSubscription::where('status', 'grace_period')
            ->whereNotNull('grace_until')
            ->where('grace_until', '<=', now())
            ->with(['vendor.user', 'package'])
            ->cursor()
            ->each(fn ($sub) => $this->moveToExpiredLocked($sub));

        // pending_payment > 24 jam → cleanup
        VendorSubscription::where('status', 'pending_payment')
            ->where('created_at', '<=', now()->subHours(24))
            ->cursor()
            ->each(function (VendorSubscription $sub) {
                $sub->update(['status' => 'cancelled_admin', 'cancel_reason' => 'Timeout pembayaran 24 jam']);
                $this->log($sub, 'payment_timeout', 'pending_payment', 'cancelled_admin');
            });
    }

    // ── Booking Guard ────────────────────────────────────────────────────

    public function canAcceptNewBooking(Vendor $vendor): bool
    {
        $sub = $this->getCurrent($vendor);
        if (!$sub) return false;
        return $sub->canAcceptBookings();
    }

    // ── Car Management ───────────────────────────────────────────────────

    /**
     * Re-publish mobil yang sebelumnya di-suspend karena subscription expired.
     * Dipanggil saat vendor mengaktifkan paket baru.
     */
    public function restoreVendorCars(Vendor $vendor): void
    {
        $vendor->cars()
            ->where('status', 'suspended')
            ->where('unavailability_reason', 'subscription_expired')
            ->update([
                'status'               => 'published',
                'unavailability_reason' => null,
                'unavailability_notes'  => null,
            ]);
    }

    /**
     * Enforce batas mobil sesuai paket baru.
     * Jika jumlah mobil melebihi limit paket, mobil kelebihan di-unpublish
     * (diurutkan dari yang paling baru ditambahkan).
     *
     * @return int Jumlah mobil yang di-unpublish
     */
    public function enforceCarLimit(Vendor $vendor, SubscriptionPackage $package): int
    {
        $maxCars = $package->features['max_cars'] ?? null;

        // -1 = unlimited, null = tidak ada limit
        if ($maxCars === null || $maxCars === -1) {
            return 0;
        }

        $publishedCars = $vendor->cars()
            ->where('status', 'published')
            ->orderBy('created_at', 'asc') // mobil lama dipertahankan
            ->get();

        if ($publishedCars->count() <= $maxCars) {
            return 0;
        }

        // Mobil ke-N dari yang terbaru harus di-suspend
        $carsToSuspend = $publishedCars->slice($maxCars);
        $count = $carsToSuspend->count();

        \App\Models\Car::whereIn('id', $carsToSuspend->pluck('id'))->update([
            'status'               => 'suspended',
            'unavailability_reason' => 'plan_downgrade',
            'unavailability_notes'  => sprintf(
                'Mobil disembunyikan otomatis. Paket %s hanya mengizinkan maks %d mobil aktif.',
                $package->name,
                $maxCars
            ),
        ]);

        // Log ke subscription terbaru vendor
        $activeSub = $vendor->subscriptions()
            ->whereIn('status', ['active', 'grace_period'])
            ->latest()
            ->first();

        if ($activeSub) {
            $this->log($activeSub, 'car_limit_enforced', null, null, [
                'max_cars'       => $maxCars,
                'suspended_count' => $count,
                'package'        => $package->name,
            ]);
        }

        return $count;
    }

    // ── Admin Actions ────────────────────────────────────────────────────

    public function adminCancel(VendorSubscription $sub, User $admin, string $reason): void
    {
        DB::transaction(function () use ($sub, $admin, $reason) {
            $fromStatus = $sub->status;
            $sub->update([
                'status'        => 'cancelled_admin',
                'cancel_reason' => $reason,
                'cancelled_by'  => $admin->id,
            ]);

            if ($sub->vendor->current_subscription_id === $sub->id) {
                $sub->vendor->update(['current_subscription_id' => null]);
            }

            $this->log($sub, 'admin_cancel', $fromStatus, 'cancelled_admin', [
                'reason'   => $reason,
                'admin_id' => $admin->id,
            ], $admin->id);
        });
    }

    public function adminGrant(Vendor $vendor, SubscriptionPackage $package, User $admin, int $days = 30): VendorSubscription
    {
        return DB::transaction(function () use ($vendor, $package, $admin, $days) {
            $current = $this->getCurrent($vendor);

            // Jika sudah punya active subscription, extend expires_at
            if ($current && $current->status === 'active') {
                $newExpiry = ($current->expires_at ?? now())->addDays($days);
                $current->update([
                    'expires_at'  => $newExpiry,
                    'grace_until' => $newExpiry->copy()->addDays(7),
                ]);
                $this->log($current, 'admin_grant_extend', 'active', 'active', [
                    'days'     => $days,
                    'admin_id' => $admin->id,
                ], $admin->id);
                return $current;
            }

            // Buat subscription baru
            $sub = VendorSubscription::create([
                'uuid'                => Str::uuid(),
                'vendor_id'           => $vendor->id,
                'package_id'          => $package->id,
                'status'              => 'active',
                'started_at'          => now(),
                'expires_at'          => now()->addDays($days),
                'grace_until'         => now()->addDays($days + 7),
                'amount_paid'         => 0,
                'snapshot_features'   => $package->features,
                'snapshot_commission' => $package->commission_rate,
                'paid_at'             => now(),
                'payment_method'      => 'admin_grant',
            ]);

            $vendor->update(['current_subscription_id' => $sub->id]);
            $this->log($sub, 'admin_grant', null, 'active', [
                'days'     => $days,
                'admin_id' => $admin->id,
            ], $admin->id);

            // Pulihkan mobil suspended karena expired, enforce limit paket baru
            $this->restoreVendorCars($vendor);
            $this->enforceCarLimit($vendor, $package);

            return $sub;
        });
    }

    public function adminForceSwitch(Vendor $vendor, SubscriptionPackage $package, User $admin, string $reason = ''): VendorSubscription
    {
        return DB::transaction(function () use ($vendor, $package, $admin, $reason) {
            $current = $this->getCurrent($vendor);

            if ($current) {
                $current->update([
                    'status'        => 'cancelled_admin',
                    'cancel_reason' => 'Admin force switch: ' . $reason,
                    'cancelled_by'  => $admin->id,
                ]);
                $this->log($current, 'admin_force_switch_cancel', $current->status, 'cancelled_admin', [
                    'reason'   => $reason,
                    'admin_id' => $admin->id,
                ], $admin->id);
            }

            $sub = VendorSubscription::create([
                'uuid'                => Str::uuid(),
                'vendor_id'           => $vendor->id,
                'package_id'          => $package->id,
                'status'              => 'active',
                'started_at'          => now(),
                'expires_at'          => $package->price_per_month > 0 ? now()->addDays(30) : null,
                'grace_until'         => $package->price_per_month > 0 ? now()->addDays(37) : null,
                'amount_paid'         => 0,
                'snapshot_features'   => $package->features,
                'snapshot_commission' => $package->commission_rate,
                'paid_at'             => now(),
                'payment_method'      => 'admin_grant',
            ]);

            $vendor->update(['current_subscription_id' => $sub->id]);
            $this->log($sub, 'admin_force_switch', null, 'active', [
                'reason'   => $reason,
                'admin_id' => $admin->id,
            ], $admin->id);

            // Pulihkan mobil suspended karena expired, enforce limit paket baru
            $this->restoreVendorCars($vendor);
            $this->enforceCarLimit($vendor, $package);

            return $sub;
        });
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function log(
        VendorSubscription $sub,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        array $meta = [],
        ?int $actorId = null
    ): void {
        VendorSubscriptionLog::create([
            'subscription_id' => $sub->id,
            'vendor_id'       => $sub->vendor_id,
            'actor_id'        => $actorId,
            'action'          => $action,
            'from_status'     => $fromStatus,
            'to_status'       => $toStatus,
            'meta'            => $meta ?: null,
            'ip'              => request()->ip(),
            'user_agent'      => request()->userAgent(),
        ]);
    }
}
