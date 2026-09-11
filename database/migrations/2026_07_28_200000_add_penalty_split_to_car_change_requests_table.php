<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom untuk mencatat pembagian penalty saat vendor menolak:
     * - vendor_penalty_amount : 40% dari total pembayaran (dicatat sebagai Payout ke vendor)
     * - platform_penalty_amount: 10% dari total pembayaran (tetap di admin)
     * - vendor_payout_id       : FK ke tabel payouts jika sudah dibuat
     */
    public function up(): void
    {
        Schema::table('car_change_requests', function (Blueprint $table) {
            $table->decimal('vendor_penalty_amount', 12, 2)->nullable()->after('price_difference')
                  ->comment('40% dari total pembayaran customer, masuk ke vendor sebagai kompensasi');
            $table->decimal('platform_penalty_amount', 12, 2)->nullable()->after('vendor_penalty_amount')
                  ->comment('10% dari total pembayaran customer, tetap di platform/admin');
            $table->foreignId('vendor_payout_id')->nullable()->after('platform_penalty_amount')
                  ->constrained('payouts')->nullOnDelete()
                  ->comment('FK ke payouts record yang diciptakan saat vendor menolak');
        });
    }

    public function down(): void
    {
        Schema::table('car_change_requests', function (Blueprint $table) {
            $table->dropForeign(['vendor_payout_id']);
            $table->dropColumn(['vendor_penalty_amount', 'platform_penalty_amount', 'vendor_payout_id']);
        });
    }
};
