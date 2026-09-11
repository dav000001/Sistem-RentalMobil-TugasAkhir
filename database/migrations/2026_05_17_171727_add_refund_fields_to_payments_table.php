<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->string('refund_ref', 100)->nullable()->after('refunded_at')
                ->comment('Nomor referensi transaksi refund');
            $table->text('refund_note')->nullable()->after('refund_ref')
                ->comment('Catatan admin saat proses refund');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['refunded_at', 'refund_ref', 'refund_note']);
        });
    }
};
