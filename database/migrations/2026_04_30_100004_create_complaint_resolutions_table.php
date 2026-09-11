<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_resolutions', function (Blueprint $table) {
            $table->id();
            $table->uuid('complaint_id');
            $table->foreign('complaint_id')->references('id')->on('complaints')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->enum('decision', ['refund_full','refund_partial','discount_voucher','warning_vendor','suspend_vendor','rejected','escalated_legal','escalated_insurance','mutual_agreement']);
            $table->unsignedInteger('refund_amount')->nullable();
            $table->unsignedInteger('vendor_penalty_amount')->nullable();
            $table->unsignedInteger('voucher_amount')->nullable();
            $table->string('voucher_code')->nullable();
            $table->unsignedInteger('vendor_suspend_days')->nullable();
            $table->text('reasoning');
            $table->json('evidence_summary')->nullable();
            $table->boolean('customer_acknowledged')->default(false);
            $table->boolean('vendor_acknowledged')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_resolutions');
    }
};
