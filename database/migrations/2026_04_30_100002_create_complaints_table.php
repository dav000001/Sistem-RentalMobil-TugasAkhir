<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference', 16)->unique();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained('complaint_categories')->restrictOnDelete();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['submitted','forwarded_to_vendor','vendor_responded','vendor_accepted','vendor_silent','under_admin_review','resolved','rejected','escalated','awaiting_insurance'])->default('submitted');
            $table->text('description');
            $table->json('attachments')->nullable();
            $table->enum('customer_demand', ['refund_full','refund_partial','discount_next','apology','other'])->default('apology');
            $table->unsignedInteger('demanded_refund_amount')->nullable();
            $table->text('customer_demand_note')->nullable();
            $table->timestamp('forwarded_at')->nullable();
            $table->timestamp('vendor_due_at')->nullable();
            $table->timestamp('admin_due_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->json('auto_flags')->nullable();
            $table->text('admin_notes')->nullable();
            $table->boolean('escrow_held')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'severity', 'created_at']);
            $table->index(['vendor_id', 'status']);
            $table->index('reporter_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
