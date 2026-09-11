<?php

namespace App\Services\Vendor;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use App\Models\User;
use App\Models\VendorStatusLog;
use App\Models\SubscriptionPackage;
use App\Models\VendorSubscription;
use App\Services\VendorSubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VendorVerificationService
{
    public function submitForReview(Vendor $vendor): void
    {
        $this->transition($vendor, VendorStatus::Pending, null, 'Dokumen disubmit untuk review');
        $vendor->update(['documents_submitted_at' => now(), 'documents_complete' => true]);

        // Kirim notifikasi ke semua admin
        try {
            \App\Models\User::where('role', 'admin')
                ->get()
                ->each(function (User $admin) use ($vendor) {
                    $admin->notify(new \App\Notifications\VendorSubmittedDocumentsNotification($vendor));
                });
        } catch (\Throwable) {}
    }

    public function approveWithoutDocCheck(Vendor $vendor, User $admin): void
    {
        $this->transition($vendor, VendorStatus::Approved, $admin, 'Vendor disetujui oleh admin');
        $vendor->update(['last_reviewed_at' => now(), 'documents_verified' => true]);

        // Pastikan role user terupdate ke 'vendor'
        $vendor->user->update(['role' => 'vendor']);

        // Auto-assign paket Free jika belum punya subscription aktif
        $this->assignFreePackageIfNeeded($vendor);

        try {
            $vendor->user->notify(new \App\Notifications\Vendor\ApprovedNotification($vendor));
        } catch (\Throwable) {}
    }

    public function approve(Vendor $vendor, User $admin): void
    {
        // Check all required documents approved
        $requiredTypes = \App\Models\VendorDocument::requiredTypes($vendor->business_type ?? 'badan_usaha');
        foreach ($requiredTypes as $type) {
            $doc = $vendor->documents()->where('type', $type)->where('status', 'approved')->first();
            if (!$doc) {
                throw new \Exception("Dokumen {$type} belum disetujui.");
            }
        }
        $this->transition($vendor, VendorStatus::Approved, $admin, 'Vendor disetujui');
        $vendor->update(['last_reviewed_at' => now(), 'documents_verified' => true]);

        // Pastikan role user terupdate ke 'vendor'
        $vendor->user->update(['role' => 'vendor']);

        // Auto-assign paket Free jika belum punya subscription aktif
        $this->assignFreePackageIfNeeded($vendor);

        try {
            $vendor->user->notify(new \App\Notifications\Vendor\ApprovedNotification($vendor));
        } catch (\Throwable) {}
    }

    public function reject(Vendor $vendor, User $admin, string $reason): void
    {
        $this->transition($vendor, VendorStatus::Rejected, $admin, $reason);
        $vendor->update(['last_reviewed_at' => now()]);
        try {
            $vendor->user->notify(new \App\Notifications\Vendor\RejectedNotification($vendor, $reason));
        } catch (\Throwable) {}
    }

    public function requestRevision(Vendor $vendor, User $admin, string $reason): void
    {
        $this->transition($vendor, VendorStatus::NeedsRevision, $admin, $reason);
        $vendor->update(['last_reviewed_at' => now()]);
        try {
            $vendor->user->notify(new \App\Notifications\Vendor\NeedsRevisionNotification($vendor, $reason));
        } catch (\Throwable) {}
    }

    public function suspend(Vendor $vendor, User $admin, string $reason): void
    {
        $this->transition($vendor, VendorStatus::Suspended, $admin, $reason);
        // Unpublish all cars
        $vendor->cars()->update(['status' => 'suspended']);
        try {
            $vendor->user->notify(new \App\Notifications\Vendor\SuspendedNotification($vendor, $reason));
        } catch (\Throwable) {}
    }

    public function unsuspend(Vendor $vendor, User $admin): void
    {
        $this->transition($vendor, VendorStatus::Approved, $admin, 'Pembekuan dicabut');
        // Re-publish cars
        $vendor->cars()->where('status', 'suspended')->update(['status' => 'published']);
        try {
            $vendor->user->notify(new \App\Notifications\Vendor\UnsuspendedNotification($vendor));
        } catch (\Throwable) {}
    }

    /**
     * Assign paket Free secara otomatis saat vendor baru diapprove,
     * jika belum memiliki subscription aktif sama sekali.
     */
    private function assignFreePackageIfNeeded(Vendor $vendor): void
    {
        // Reload untuk pastikan data fresh
        $vendor->refresh();

        $subService = app(VendorSubscriptionService::class);
        $current    = $subService->getCurrent($vendor);

        // Sudah punya subscription aktif/grace/pending — tidak perlu assign ulang
        if ($current) return;

        $freePackage = SubscriptionPackage::where('code', 'free')
            ->where('is_active', true)
            ->first();

        if (!$freePackage) {
            // Log error agar admin tahu — jangan silent fail
            \Illuminate\Support\Facades\Log::error(
                '[VendorApproval] Paket Free tidak ditemukan di database! ' .
                'Vendor ID ' . $vendor->id . ' (' . $vendor->business_name . ') ' .
                'diapprove tanpa subscription. Jalankan: php artisan vendor:assign-free-package'
            );
            return;
        }

        DB::transaction(function () use ($vendor, $freePackage) {
            $sub = VendorSubscription::create([
                'uuid'                => Str::uuid(),
                'vendor_id'           => $vendor->id,
                'package_id'          => $freePackage->id,
                'status'              => 'active',
                'started_at'          => now(),
                'expires_at'          => null, // Free tidak ada expiry
                'grace_until'         => null,
                'amount_paid'         => 0,
                'snapshot_features'   => $freePackage->features,
                'snapshot_commission' => $freePackage->commission_rate,
                'paid_at'             => now(),
                'payment_method'      => 'free',
            ]);

            $vendor->update(['current_subscription_id' => $sub->id]);
        });
    }

    private function transition(Vendor $vendor, VendorStatus $to, ?User $actor, ?string $reason, array $meta = []): void
    {
        DB::transaction(function () use ($vendor, $to, $actor, $reason, $meta) {
            $from = $vendor->status instanceof VendorStatus ? $vendor->status->value : $vendor->status;
            $vendor->update(['status' => $to->value]);
            VendorStatusLog::create([
                'vendor_id' => $vendor->id,
                'actor_id'  => $actor?->id,
                'from_status' => $from,
                'to_status'   => $to->value,
                'reason'      => $reason,
                'metadata'    => $meta ?: null,
            ]);
        });
    }
}
