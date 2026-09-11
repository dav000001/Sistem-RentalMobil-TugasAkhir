<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Tanggal pengembalian aktual (diisi vendor saat tandai selesai)
            $table->dateTime('actual_return_at')->nullable()->after('completed_at');

            // Flag keterlambatan
            $table->boolean('is_late')->default(false)->after('actual_return_at');

            // Durasi terlambat dalam jam (bisa pecahan)
            $table->unsignedInteger('late_duration_hours')->default(0)->after('is_late');

            // Denda keterlambatan (dihitung per hari = daily_price / 24 * jam terlambat)
            $table->decimal('late_fee', 10, 2)->default(0)->after('late_duration_hours');

            $table->index(['is_late', 'vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['is_late', 'vendor_id']);
            $table->dropColumn(['actual_return_at', 'is_late', 'late_duration_hours', 'late_fee']);
        });
    }
};
