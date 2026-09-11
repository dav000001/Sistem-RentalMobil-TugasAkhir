<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('city_id')->constrained();
            $table->string('brand');
            $table->string('model');
            $table->year('year');
            $table->string('plate_number')->unique();
            $table->enum('transmission', ['manual', 'automatic']);
            $table->enum('fuel', ['bensin', 'diesel', 'listrik', 'hybrid']);
            $table->integer('seats');
            $table->integer('luggage');
            $table->json('features')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'published', 'suspended'])->default('draft');
            $table->timestamps();
            
            $table->index(['city_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
