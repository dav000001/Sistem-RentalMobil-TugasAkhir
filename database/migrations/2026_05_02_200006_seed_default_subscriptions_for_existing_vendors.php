<?php

use App\Models\SubscriptionPackage;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan paket Free sudah ada
        $freePackage = SubscriptionPackage::where('code', 'free')->first();
        if (!$freePackage) {
            $freePackage = SubscriptionPackage::create([
                'code'              => 'free',
                'name'              => 'Free',
                'description'       => 'Paket gratis untuk memulai.',
                'price_per_month'   => 0,
                'commission_rate'   => 12.00,
                'rank'              => 0,
                'is_active'         => true,
                'allow_self_signup' => true,
                'features'          => json_encode([
                    'max_cars'        => 3,
                    'max_photos'      => 3,
                    'priority_search' => false,
                    'analytics'       => false,
                    'payout_schedule' => 'weekly',
                    'support'         => 'email',
                    'badge_verified'  => false,
                ]),
            ]);
        }

        // Assign Free subscription ke semua vendor yang belum punya
        Vendor::whereNull('current_subscription_id')->each(function (Vendor $vendor) use ($freePackage) {
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

    public function down(): void
    {
        // Tidak di-rollback — data subscription tidak dihapus
    }
};
