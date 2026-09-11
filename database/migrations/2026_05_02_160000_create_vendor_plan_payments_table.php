<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_plan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('plan', 20);                          // basic / premium
            $table->decimal('amount', 10, 2);                    // nominal tagihan
            $table->enum('status', ['pending', 'paid', 'failed', 'waived'])->default('pending');
            $table->enum('method', ['manual', 'payout_deduction', 'transfer', 'waived'])->default('manual');
            $table->date('period_start');                        // awal periode
            $table->date('period_end');                          // akhir periode
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete(); // admin yg konfirmasi
            $table->text('notes')->nullable();
            $table->string('reference')->nullable();             // no. referensi transfer
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['status', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_plan_payments');
    }
};
