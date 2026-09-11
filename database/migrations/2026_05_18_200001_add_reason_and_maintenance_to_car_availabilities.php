<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ubah enum status agar include 'maintenance'
        DB::statement("ALTER TABLE car_availabilities MODIFY COLUMN status ENUM('available','blocked','booked','maintenance') NOT NULL DEFAULT 'available'");

        // 2. Tambah kolom reason
        Schema::table('car_availabilities', function (Blueprint $table) {
            $table->string('reason', 200)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('car_availabilities', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
        DB::statement("ALTER TABLE car_availabilities MODIFY COLUMN status ENUM('available','blocked','booked') NOT NULL DEFAULT 'available'");
    }
};
