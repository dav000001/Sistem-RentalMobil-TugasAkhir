<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        // Set expires_at untuk subscription berbayar (price > 0) yang belum punya expires_at
        $paidPackageIds = DB::table('subscription_packages')
            ->where('price_per_month', '>', 0)
            ->pluck('id');

        $subs = DB::table('vendor_subscriptions')
            ->whereIn('package_id', $paidPackageIds)
            ->whereNull('expires_at')
            ->where('status', 'active')
            ->get();

        foreach ($subs as $sub) {
            $startedAt = Carbon::parse($sub->started_at);
            // Set expires_at = 1 bulan dari started_at
            DB::table('vendor_subscriptions')
                ->where('id', $sub->id)
                ->update([
                    'expires_at' => $startedAt->addMonth(),
                ]);
        }

        // Pastikan Free tetap NULL (selamanya)
        $freePackageId = DB::table('subscription_packages')
            ->where('code', 'free')
            ->value('id');

        DB::table('vendor_subscriptions')
            ->where('package_id', $freePackageId)
            ->update(['expires_at' => null]);
    }

    public function down(): void
    {
        DB::table('vendor_subscriptions')->update(['expires_at' => null]);
    }
};
