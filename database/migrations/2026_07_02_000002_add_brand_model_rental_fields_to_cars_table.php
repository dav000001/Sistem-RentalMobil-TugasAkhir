<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            // Foreign key ke car_brands & car_models (nullable untuk backward compat)
            $table->foreignId('car_brand_id')->nullable()->after('city_id')->constrained('car_brands')->nullOnDelete();
            $table->foreignId('car_model_id')->nullable()->after('car_brand_id')->constrained('car_models')->nullOnDelete();

            // Opsi sewa: lepas kunci saja / dengan sopir saja / keduanya
            $table->enum('rental_option', ['self_drive_only', 'with_driver_only', 'both'])
                ->default('self_drive_only')
                ->after('description');

            // Bisa disewa bulanan?
            $table->boolean('is_monthly_available')->default(false)->after('rental_option');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropForeign(['car_brand_id']);
            $table->dropForeign(['car_model_id']);
            $table->dropColumn(['car_brand_id', 'car_model_id', 'rental_option', 'is_monthly_available']);
        });
    }
};
