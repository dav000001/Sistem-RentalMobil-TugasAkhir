<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_id')->constrained();
            $table->foreignId('vendor_id')->constrained();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->boolean('with_driver')->default(false);
            $table->text('pickup_location');
            $table->text('dropoff_location')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('addon_fees', 10, 2)->default(0);
            $table->decimal('taxes', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('vendor_payout_amount', 10, 2);
            $table->enum('status', [
                'awaiting_payment',
                'awaiting_vendor',
                'confirmed',
                'ongoing',
                'completed',
                'cancelled',
                'refunded',
                'disputed'
            ])->default('awaiting_payment');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
