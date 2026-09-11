<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            // Tambah status 'unavailable' ke ENUM yang sudah ada
            \DB::statement("ALTER TABLE cars MODIFY COLUMN status ENUM('draft','published','suspended','unavailable') NOT NULL DEFAULT 'draft'");

            // Alasan ketidaktersediaan
            $table->string('unavailability_reason')->nullable()->after('status')
                ->comment('service|rusak|kecelakaan|lainnya');
            $table->text('unavailability_notes')->nullable()->after('unavailability_reason')
                ->comment('Keterangan tambahan dari vendor');
            $table->date('unavailable_until')->nullable()->after('unavailability_notes')
                ->comment('Perkiraan kapan mobil kembali tersedia');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn(['unavailability_reason', 'unavailability_notes', 'unavailable_until']);
        });
        \DB::statement("ALTER TABLE cars MODIFY COLUMN status ENUM('draft','published','suspended') NOT NULL DEFAULT 'draft'");
    }
};
