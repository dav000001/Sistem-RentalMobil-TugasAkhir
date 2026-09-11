<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Komisi per paket
        $commissionMap = [
            'free'    => 12.00,
            'basic'   => 9.00,
            'premium' => 5.00,
        ];

        // Update snapshot_commission di setiap subscription
        // sesuai dengan plan vendor yang aktif saat ini
        $vendors = DB::table('vendors')
            ->whereNotNull('current_subscription_id')
            ->get();

        foreach ($vendors as $vendor) {
            $plan = $vendor->plan ?? 'free';
            $correctCommission = $commissionMap[$plan] ?? 12.00;

            DB::table('vendor_subscriptions')
                ->where('id', $vendor->current_subscription_id)
                ->update(['snapshot_commission' => $correctCommission]);
        }
    }

    public function down(): void
    {
        // Kembalikan semua ke 12 (tidak ideal tapi reversible)
        DB::table('vendor_subscriptions')->update(['snapshot_commission' => 12.00]);
    }
};
