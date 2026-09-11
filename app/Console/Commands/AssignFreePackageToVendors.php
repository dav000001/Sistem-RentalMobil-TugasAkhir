<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPackage;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AssignFreePackageToVendors extends Command
{
    protected $signature   = 'vendor:assign-free-package';
    protected $description = 'Assign paket Free ke semua vendor approved yang belum punya subscription aktif';

    public function handle(): int
    {
        $freePackage = SubscriptionPackage::where('code', 'free')
            ->where('is_active', true)
            ->first();

        if (!$freePackage) {
            $this->error('Paket Free tidak ditemukan di database!');
            return self::FAILURE;
        }

        $vendors = Vendor::where('status', 'approved')
            ->whereNull('current_subscription_id')
            ->get();

        if ($vendors->isEmpty()) {
            $this->info('Semua vendor sudah memiliki paket aktif.');
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$vendors->count()} vendor tanpa paket aktif...");
        $fixed = 0;

        foreach ($vendors as $vendor) {
            // Cek apakah sudah ada subscription aktif tapi current_subscription_id null
            $existing = $vendor->subscriptions()
                ->whereIn('status', ['active', 'grace_period', 'pending_payment'])
                ->latest('started_at')
                ->first();

            if ($existing) {
                $vendor->update(['current_subscription_id' => $existing->id]);
                $this->line("  ✔ Sinkronisasi sub existing untuk: {$vendor->business_name} (sub #{$existing->id})");
                $fixed++;
                continue;
            }

            // Buat subscription Free baru
            $sub = VendorSubscription::create([
                'uuid'                => Str::uuid(),
                'vendor_id'           => $vendor->id,
                'package_id'          => $freePackage->id,
                'status'              => 'active',
                'started_at'          => now(),
                'expires_at'          => null,
                'grace_until'         => null,
                'amount_paid'         => 0,
                'snapshot_features'   => $freePackage->features,
                'snapshot_commission' => $freePackage->commission_rate,
                'paid_at'             => now(),
                'payment_method'      => 'free',
            ]);

            $vendor->update(['current_subscription_id' => $sub->id]);
            $this->line("  ✔ Assigned paket Free ke: {$vendor->business_name}");
            $fixed++;
        }

        $this->info("Selesai. {$fixed} vendor berhasil difix.");
        return self::SUCCESS;
    }
}
