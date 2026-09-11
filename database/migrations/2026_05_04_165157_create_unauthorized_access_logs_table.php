<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unauthorized_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('attempted_resource', 100)->nullable();
            $table->unsignedBigInteger('attempted_id')->nullable();
            $table->unsignedBigInteger('owner_vendor_id')->nullable();
            $table->string('action', 32)->nullable();
            $table->string('route', 200)->nullable();
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unauthorized_access_logs');
    }
};
