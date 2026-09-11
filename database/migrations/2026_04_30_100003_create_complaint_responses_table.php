<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_responses', function (Blueprint $table) {
            $table->id();
            $table->uuid('complaint_id');
            $table->foreign('complaint_id')->references('id')->on('complaints')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->enum('author_role', ['customer', 'vendor', 'admin']);
            $table->enum('visibility', ['public', 'internal_admin'])->default('public');
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_offer')->default(false);
            $table->json('offer_payload')->nullable();
            $table->boolean('offer_accepted')->nullable();
            $table->timestamps();
            $table->index(['complaint_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_responses');
    }
};
