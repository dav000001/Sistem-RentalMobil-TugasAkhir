<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_view_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            // Vendor yang melihat (via vendor user)
            $table->unsignedBigInteger('viewed_by_vendor_id')->nullable();
            $table->foreign('viewed_by_vendor_id')->references('id')->on('vendors')->nullOnDelete();
            // Dokumen apa yang dilihat
            $table->enum('document_type', ['ktp', 'sim', 'selfie'])->nullable()->comment('null = semua dokumen ditampilkan sekaligus');
            $table->string('viewer_ip', 45)->nullable();
            $table->timestamp('viewed_at')->useCurrent();
            $table->timestamps();

            $table->index(['booking_id', 'viewed_by_vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_view_logs');
    }
};
