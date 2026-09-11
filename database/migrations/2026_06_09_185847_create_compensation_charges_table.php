<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compensation_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dispute_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->enum('status', ['pending', 'paid', 'waived'])->default('pending');
            $table->date('due_date')->nullable()->comment('Batas waktu pembayaran');
            $table->text('notes')->nullable();
            $table->string('payment_proof')->nullable()->comment('Bukti bayar dari customer');
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable()->comment('admin user id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compensation_charges');
    }
};
