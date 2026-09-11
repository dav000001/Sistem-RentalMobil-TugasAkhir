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
        Schema::create('car_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('old_car_id')->constrained('cars');
            $table->foreignId('new_car_id')->nullable()->constrained('cars')->nullOnDelete();
            $table->unsignedInteger('passenger_count'); // Jumlah penumpang aktual customer
            $table->text('reason');                    // Alasan pengajuan dari customer
            $table->enum('status', [
                'pending',             // Menunggu respon vendor
                'approved',            // Vendor setuju, mobil sudah diganti
                'rejected',            // Vendor tidak punya mobil, booking dibatalkan
                'cancelled_by_customer', // Customer batalkan permintaan
            ])->default('pending');
            $table->decimal('original_total', 12, 2)->default(0); // Total lama
            $table->decimal('new_total', 12, 2)->nullable();      // Total baru
            $table->decimal('price_difference', 12, 2)->nullable(); // Selisih yg harus dibayar
            $table->string('additional_payment_proof')->nullable(); // Bukti bayar selisih
            $table->timestamp('additional_payment_at')->nullable();
            $table->text('vendor_notes')->nullable();  // Catatan dari vendor
            $table->text('admin_notes')->nullable();   // Catatan dari admin
            $table->timestamp('requested_at')->nullable(); // Waktu pengajuan
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_change_requests');
    }
};
