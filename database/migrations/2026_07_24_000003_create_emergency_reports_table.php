<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_reports', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            
            // Emergency details
            $table->enum('type', ['breakdown', 'accident', 'theft', 'harassment', 'medical', 'other'])
                  ->comment('Jenis emergency');
            $table->text('description')->nullable()
                  ->comment('Deskripsi detail emergency');
            
            // Location
            $table->decimal('latitude', 10, 8)
                  ->comment('GPS latitude lokasi emergency');
            $table->decimal('longitude', 11, 8)
                  ->comment('GPS longitude lokasi emergency');
            
            // Status tracking
            $table->enum('status', ['active', 'responding', 'resolved', 'closed'])
                  ->default('active')
                  ->comment('Status penanganan emergency');
            $table->text('resolution')->nullable()
                  ->comment('Cara penyelesaian emergency');
            
            // Timestamps
            $table->timestamp('reported_at')
                  ->comment('Waktu emergency dilaporkan');
            $table->timestamp('resolved_at')->nullable()
                  ->comment('Waktu emergency diselesaikan');
            $table->timestamps();

            // Indexes
            $table->index(['status', 'reported_at']);
            $table->index(['type', 'status']);
            $table->index(['booking_id', 'status']);
            $table->index(['customer_id', 'reported_at']);
            $table->index(['vendor_id', 'status']);
            $table->index('reported_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_reports');
    }
};