<?php

namespace App\Services;

use App\Enums\VendorPlan;
use App\Models\Vendor;
use App\Models\VendorPlanPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VendorPlanService
{
    /**
     * Upgrade/downgrade paket vendor dan buat tagihan jika perlu.
     */
    public function changePlan(Vendor $vendor, VendorPlan $newPlan, ?int $adminId = null): VendorPlanPayment|null
    {
        return DB::transaction(function () use ($vendor, $newPlan, $adminId) {
            $oldPlan = $vendor->plan;

            // Jika ke Free, tidak ada tagihan
            if ($newPlan === VendorPlan::Free) {
                $vendor->update([
                    'plan'             => VendorPlan::Free->value,
                    'plan_upgraded_at' => now(),
                    'plan_expires_at'  => null,
                ]);
                return null;
            }

            $periodStart = now()->startOfDay();
            $periodEnd   = now()->addMonth()->subDay()->endOfDay();

            // Update vendor plan
            $vendor->update([
                'plan'             => $newPlan->value,
                'plan_upgraded_at' => now(),
                'plan_expires_at'  => $periodEnd,
            ]);

            // Buat tagihan
            $payment = VendorPlanPayment::create([
                'vendor_id'    => $vendor->id,
                'plan'         => $newPlan->value,
                'amount'       => $newPlan->monthlyFee(),
                'status'       => 'pending',
                'method'       => 'manual',
                'period_start' => $periodStart,
                'period_end'   => $periodEnd,
                'confirmed_by' => $adminId,
                'notes'        => "Upgrade ke paket {$newPlan->label()} oleh " . ($adminId ? 'admin' : 'vendor'),
            ]);

            return $payment;
        });
    }

    /**
     * Konfirmasi pembayaran tagihan paket (oleh admin).
     */
    public function confirmPayment(VendorPlanPayment $payment, int $adminId, string $method = 'manual', ?string $reference = null): void
    {
        DB::transaction(function () use ($payment, $adminId, $method, $reference) {
            $payment->update([
                'status'       => 'paid',
                'method'       => $method,
                'paid_at'      => now(),
                'confirmed_by' => $adminId,
                'reference'    => $reference,
            ]);

            // Perpanjang masa aktif paket
            $vendor = $payment->vendor;
            $vendor->update([
                'plan_expires_at' => Carbon::parse($payment->period_end)->addMonth(),
            ]);
        });
    }

    /**
     * Bebaskan tagihan (waive) oleh admin.
     */
    public function waivePayment(VendorPlanPayment $payment, int $adminId, string $reason = ''): void
    {
        $payment->update([
            'status'       => 'waived',
            'method'       => 'waived',
            'paid_at'      => now(),
            'confirmed_by' => $adminId,
            'notes'        => $reason ?: 'Dibebaskan oleh admin',
        ]);

        // Perpanjang masa aktif
        $vendor = $payment->vendor;
        $vendor->update([
            'plan_expires_at' => Carbon::parse($payment->period_end)->addMonth(),
        ]);
    }

    /**
     * Potong dari payout vendor.
     */
    public function deductFromPayout(VendorPlanPayment $payment, int $adminId): void
    {
        $payment->update([
            'status'       => 'paid',
            'method'       => 'payout_deduction',
            'paid_at'      => now(),
            'confirmed_by' => $adminId,
            'notes'        => 'Dipotong otomatis dari payout',
        ]);

        $vendor = $payment->vendor;
        $vendor->update([
            'plan_expires_at' => Carbon::parse($payment->period_end)->addMonth(),
        ]);
    }

    /**
     * Buat tagihan bulanan untuk semua vendor berbayar (dipanggil oleh scheduler).
     */
    public function generateMonthlyBills(): int
    {
        $count = 0;

        Vendor::whereIn('plan', [VendorPlan::Basic->value, VendorPlan::Premium->value])
            ->whereNotNull('plan_expires_at')
            ->where('plan_expires_at', '<=', now()->addDays(3)) // 3 hari sebelum expired
            ->each(function (Vendor $vendor) use (&$count) {
                // Jangan buat tagihan duplikat
                $alreadyExists = VendorPlanPayment::where('vendor_id', $vendor->id)
                    ->where('status', 'pending')
                    ->where('period_start', '>=', now()->startOfMonth())
                    ->exists();

                if (!$alreadyExists) {
                    $periodStart = $vendor->plan_expires_at->addDay()->startOfDay();
                    $periodEnd   = $periodStart->copy()->addMonth()->subDay()->endOfDay();

                    VendorPlanPayment::create([
                        'vendor_id'    => $vendor->id,
                        'plan'         => $vendor->plan->value,
                        'amount'       => $vendor->plan->monthlyFee(),
                        'status'       => 'pending',
                        'method'       => 'manual',
                        'period_start' => $periodStart,
                        'period_end'   => $periodEnd,
                        'notes'        => 'Tagihan otomatis bulanan',
                    ]);

                    $count++;
                }
            });

        return $count;
    }

    /**
     * Downgrade vendor ke Free jika tagihan tidak dibayar setelah grace period.
     */
    public function expireUnpaidPlans(int $graceDays = 7): int
    {
        $count = 0;

        Vendor::whereIn('plan', [VendorPlan::Basic->value, VendorPlan::Premium->value])
            ->where('plan_expires_at', '<', now()->subDays($graceDays))
            ->each(function (Vendor $vendor) use (&$count) {
                $hasPendingPayment = $vendor->planPayments()
                    ->where('status', 'pending')
                    ->exists();

                if ($hasPendingPayment) {
                    // Downgrade ke Free
                    $vendor->update([
                        'plan'            => VendorPlan::Free->value,
                        'plan_expires_at' => null,
                    ]);

                    // Tandai tagihan sebagai failed
                    $vendor->planPayments()
                        ->where('status', 'pending')
                        ->update(['status' => 'failed']);

                    $count++;
                }
            });

        return $count;
    }
}
