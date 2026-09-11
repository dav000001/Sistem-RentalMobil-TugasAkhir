<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('late_fee_charges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();

            // Nominal denda
            $table->decimal('amount', 10, 2);
            $table->unsignedInteger('late_hours');

            // Status penagihan
            $table->enum('status', [
                'pending',   // Menunggu konfirmasi admin
                'confirmed', // Admin sudah konfirmasi, customer harus bayar
                'paid',      // Customer sudah bayar, admin verifikasi
                'waived',    // Denda dibebaskan admin
            ])->default('pending');

            // Bukti pembayaran denda dari customer
            $table->string('payment_proof')->nullable();
            $table->timestamp('proof_uploaded_at')->nullable();

            // Admin action
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('waive_reason')->nullable();

            // Rekening tujuan transfer denda (ke vendor)
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_account_name')->nullable();

            $table->timestamps();

            $table->index(['status', 'customer_id']);
            $table->index(['booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('late_fee_charges');
    }
};
