<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_tracking_points', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            
            // GPS coordinates
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('address')->nullable()
                  ->comment('Alamat dari reverse geocoding');
            
            // Accuracy and validation
            $table->unsignedTinyInteger('accuracy_score')->default(0)
                  ->comment('GPS accuracy score 0-100');
            $table->json('gps_validation_data')->nullable()
                  ->comment('GPS validation results');
            
            // Additional metadata
            $table->json('metadata')->nullable()
                  ->comment('Additional tracking metadata (speed, bearing, etc)');
            
            // Timestamp
            $table->timestamp('recorded_at');
            $table->timestamps();

            // Indexes
            $table->index(['booking_id', 'recorded_at']);
            $table->index(['booking_id', 'accuracy_score']);
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_tracking_points');
    }
};