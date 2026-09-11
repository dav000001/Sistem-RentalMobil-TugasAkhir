<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('late_return_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();

            // Siapa yang melaporkan
            $table->enum('reporter_type', ['customer', 'driver'])
                  ->comment('customer = customer lapor sendiri, driver = vendor lapor atas nama sopir');
            $table->foreignId('reported_by_user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('user_id customer atau vendor yang melaporkan');

            // Detail laporan
            $table->unsignedTinyInteger('estimated_late_hours')->default(1)
                  ->comment('Perkiraan jam keterlambatan');
            $table->text('reason')->nullable()
                  ->comment('Alasan keterlambatan');

            // Lokasi GPS customer saat lapor (Opsi A)
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_address')->nullable()
                  ->comment('Alamat hasil reverse geocode (diisi frontend)');

            // Lokasi pengembalian aktual (Opsi B — saat konfirmasi pengembalian)
            $table->decimal('return_latitude', 10, 8)->nullable();
            $table->decimal('return_longitude', 11, 8)->nullable();
            $table->string('return_location_address')->nullable();
            $table->timestamp('return_confirmed_at')->nullable();

            // Status
            $table->enum('status', ['reported', 'acknowledged'])
                  ->default('reported');
            $table->timestamp('acknowledged_at')->nullable();

            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('late_return_reports');
    }
};
