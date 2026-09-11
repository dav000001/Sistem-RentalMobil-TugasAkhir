<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['available', 'blocked', 'booked'])->default('available');
            $table->timestamps();
            
            $table->unique(['car_id', 'date']);
            $table->index(['car_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_availabilities');
    }
};
