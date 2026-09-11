<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $t->foreignId('package_id')->constrained('subscription_packages')->restrictOnDelete();
            $t->enum('status', [
                'pending_payment',
                'active',
                'grace_period',
                'expired_locked',
                'cancelled_admin',
                'cancelled_by_upgrade',
            ])->default('pending_payment');
            $t->timestamp('started_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('grace_until')->nullable();
            $t->unsignedInteger('amount_paid')->default(0);
            $t->unsignedInteger('proration_credit')->default(0);
            $t->string('payment_method', 32)->nullable();
            $t->string('payment_reference')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->boolean('auto_renew')->default(false);
            $t->json('snapshot_features')->nullable();
            $t->decimal('snapshot_commission', 5, 2)->nullable();
            $t->text('cancel_reason')->nullable();
            $t->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['vendor_id', 'status']);
            $t->index(['expires_at']);
            $t->index(['grace_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_subscriptions');
    }
};
