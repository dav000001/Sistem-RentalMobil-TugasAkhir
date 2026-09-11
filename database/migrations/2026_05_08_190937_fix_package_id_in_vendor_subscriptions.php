<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Map plan vendor ke package_id yang benar
        $planToPackageId = [
            'free'    => DB::table('subscription_packages')->where('code', 'free')->value('id'),
            'basic'   => DB::table('subscription_packages')->where('code', 'basic')->value('id'),
            'premium' => DB::table('subscription_packages')->where('code', 'premium')->value('id'),
        ];

        // Update setiap subscription sesuai plan vendor
        $vendors = DB::table('vendors')
            ->whereNotNull('current_subscription_id')
            ->get();

        foreach ($vendors as $vendor) {
            $plan      = $vendor->plan ?? 'free';
            $packageId = $planToPackageId[$plan] ?? $planToPackageId['free'];

            DB::table('vendor_subscriptions')
                ->where('id', $vendor->current_subscription_id)
                ->update(['package_id' => $packageId]);
        }
    }

    public function down(): void
    {
        // Kembalikan semua ke package free
        $freeId = DB::table('subscription_packages')->where('code', 'free')->value('id');
        DB::table('vendor_subscriptions')->update(['package_id' => $freeId]);
    }
};
