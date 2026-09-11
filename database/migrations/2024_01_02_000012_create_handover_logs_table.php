<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handover_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['pickup', 'return']);
            $table->json('photos')->nullable();
            $table->integer('odometer')->nullable();
            $table->string('fuel_level')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('signed_by_user_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handover_logs');
    }
};
