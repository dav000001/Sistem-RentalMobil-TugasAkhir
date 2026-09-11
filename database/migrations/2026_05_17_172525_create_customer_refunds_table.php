<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('bank_name', 50)->nullable();
            $table->string('bank_account_no', 30)->nullable();
            $table->string('bank_account_name', 100)->nullable();
            $table->string('transfer_reference', 100)->nullable()->comment('Nomor referensi transfer dari bank');
            $table->string('transfer_proof')->nullable()->comment('Path foto bukti transfer');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable()->comment('admin user id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_refunds');
    }
};
