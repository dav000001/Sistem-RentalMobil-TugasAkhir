<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_proof')->nullable()->after('paid_at')
                ->comment('Path file bukti transfer yang diupload customer');
            $table->string('sender_name')->nullable()->after('payment_proof')
                ->comment('Nama pengirim transfer');
            $table->enum('gateway', ['midtrans', 'xendit', 'manual'])
                ->default('midtrans')->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payment_proof', 'sender_name']);
        });
    }
};
