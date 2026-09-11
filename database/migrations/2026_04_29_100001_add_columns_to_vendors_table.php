<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->timestamp('whatsapp_verified_at')->nullable()->after('status');
            $table->timestamp('documents_submitted_at')->nullable()->after('whatsapp_verified_at');
            $table->timestamp('last_reviewed_at')->nullable()->after('documents_submitted_at');
            $table->text('internal_notes')->nullable()->after('last_reviewed_at');
            $table->boolean('documents_complete')->default(false)->after('internal_notes');
            $table->boolean('documents_verified')->default(false)->after('documents_complete');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_verified_at',
                'documents_submitted_at',
                'last_reviewed_at',
                'internal_notes',
                'documents_complete',
                'documents_verified',
            ]);
        });
    }
};
