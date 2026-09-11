<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rejected_booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('recommended_car_id')->constrained('cars')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->integer('rank')->default(0)->comment('Urutan rekomendasi 1-5');
            $table->decimal('score', 5, 2)->nullable()->comment('Skor matching (opsional untuk analisis)');
            $table->boolean('clicked')->default(false)->comment('Apakah customer klik rekomendasi ini');
            $table->timestamp('clicked_at')->nullable();
            $table->timestamps();

            $table->index(['rejected_booking_id', 'rank']);
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_recommendations');
    }
};
