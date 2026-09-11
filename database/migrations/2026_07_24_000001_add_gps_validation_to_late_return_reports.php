<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('late_return_reports', function (Blueprint $table) {
            // GPS validation data for report location
            $table->json('gps_validation_data')->nullable()
                  ->comment('GPS validation results for report location');
            
            // GPS validation data for return location
            $table->json('return_gps_validation')->nullable()
                  ->comment('GPS validation results for return confirmation location');
            
            // Auto detection metadata
            $table->json('auto_detection_data')->nullable()
                  ->comment('Auto late detection system data');
            
            // Reporter type update untuk sistem otomatis
            $table->dropColumn('reporter_type');
        });

        // Recreate enum with new system option
        Schema::table('late_return_reports', function (Blueprint $table) {
            $table->enum('reporter_type', ['customer', 'driver', 'system'])
                  ->default('customer')
                  ->comment('customer = customer lapor sendiri, driver = vendor lapor atas nama sopir, system = deteksi otomatis');
        });
    }

    public function down(): void
    {
        Schema::table('late_return_reports', function (Blueprint $table) {
            $table->dropColumn([
                'gps_validation_data',
                'return_gps_validation', 
                'auto_detection_data'
            ]);
            
            $table->dropColumn('reporter_type');
        });

        // Restore original enum
        Schema::table('late_return_reports', function (Blueprint $table) {
            $table->enum('reporter_type', ['customer', 'driver'])
                  ->comment('customer = customer lapor sendiri, driver = vendor lapor atas nama sopir');
        });
    }
};