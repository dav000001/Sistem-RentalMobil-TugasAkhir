<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('bank_name', 50)->nullable()->after('rejection_reason')
                ->comment('Nama bank untuk refund, contoh: BCA, BRI, Mandiri');
            $table->string('bank_account_no', 30)->nullable()->after('bank_name')
                ->comment('Nomor rekening untuk refund');
            $table->string('bank_account_name', 100)->nullable()->after('bank_account_no')
                ->comment('Nama pemilik rekening sesuai buku tabungan');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_no', 'bank_account_name']);
        });
    }
};
