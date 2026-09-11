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
        Schema::table('bookings', function (Blueprint $table) {
            // Index untuk kolom yang sering di-query
            $table->index('status', 'bookings_status_index');
            $table->index('code', 'bookings_code_index');
            $table->index('created_at', 'bookings_created_at_index');
            $table->index('start_at', 'bookings_start_at_index');
            $table->index('end_at', 'bookings_end_at_index');
            
            // Composite index untuk query yang sering digunakan
            $table->index(['status', 'created_at'], 'bookings_status_created_index');
            $table->index(['customer_id', 'status'], 'bookings_customer_status_index');
            $table->index(['vendor_id', 'status'], 'bookings_vendor_status_index');
            $table->index(['car_id', 'status'], 'bookings_car_status_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            // Index untuk kolom yang sering di-query
            $table->index('status', 'payments_status_index');
            $table->index('method', 'payments_method_index');
            $table->index(['booking_id', 'status'], 'payments_booking_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_status_index');
            $table->dropIndex('bookings_code_index');
            $table->dropIndex('bookings_created_at_index');
            $table->dropIndex('bookings_start_at_index');
            $table->dropIndex('bookings_end_at_index');
            $table->dropIndex('bookings_status_created_index');
            $table->dropIndex('bookings_customer_status_index');
            $table->dropIndex('bookings_vendor_status_index');
            $table->dropIndex('bookings_car_status_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_status_index');
            $table->dropIndex('payments_method_index');
            $table->dropIndex('payments_booking_status_index');
        });
    }
};
