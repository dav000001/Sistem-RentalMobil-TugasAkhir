<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('car_change_requests', function (Blueprint $table) {
            $table->boolean('with_driver')->nullable()->after('passenger_count')
                  ->comment('Pilihan customer saat ganti mobil: 1 = dengan sopir, 0 = tanpa sopir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('car_change_requests', function (Blueprint $table) {
            $table->dropColumn(['with_driver']);
        });
    }
};
