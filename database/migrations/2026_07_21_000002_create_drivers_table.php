<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('license_number')->unique(); // Nomor SIM
            $table->string('phone');
            $table->string('photo')->nullable(); // Path foto
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00); // 0.00 - 5.00
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
