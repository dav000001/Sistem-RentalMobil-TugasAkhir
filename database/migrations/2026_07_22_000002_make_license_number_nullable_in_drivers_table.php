<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            // Buat nullable karena field ini dihapus dari form input vendor
            $table->string('license_number')->nullable()->change();
            // Hapus unique constraint dulu, lalu buat ulang sebagai nullable unique
            $table->dropUnique(['license_number']);
            $table->unique('license_number');
        });

        // Set nilai '' menjadi null untuk data lama (jika ada)
        \DB::table('drivers')
            ->whereRaw("license_number = ''")
            ->update(['license_number' => null]);
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('license_number')->nullable(false)->change();
        });
    }
};
