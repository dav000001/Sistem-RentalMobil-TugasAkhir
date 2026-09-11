<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop tabel upgrade_requests jika ada (dari migrasi gagal sebelumnya)
        DB::statement('DROP TABLE IF EXISTS vendor_subscription_upgrade_requests');

        Schema::create('vendor_subscription_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('subscription_id')->constrained('vendor_subscriptions')->cascadeOnDelete();
            $t->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 64);
            $t->string('from_status', 32)->nullable();
            $t->string('to_status', 32)->nullable();
            $t->json('meta')->nullable();
            $t->ipAddress('ip')->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamps();

            $t->index(['vendor_id', 'created_at']);
        });

        Schema::create('vendor_subscription_upgrade_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('current_subscription_id')->nullable();
            $t->foreignId('target_package_id')->constrained('subscription_packages')->restrictOnDelete();
            $t->enum('type', ['upgrade', 'downgrade_early', 'cancel_early']);
            $t->text('reason');
            $t->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('admin_note')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();

            $t->foreign('current_subscription_id', 'vsur_cur_sub_fk')
                ->references('id')->on('vendor_subscriptions')->nullOnDelete();
            $t->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_subscription_upgrade_requests');
        Schema::dropIfExists('vendor_subscription_logs');
    }
};
