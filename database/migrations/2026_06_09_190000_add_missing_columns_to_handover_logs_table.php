<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('handover_logs', function (Blueprint $table) {
            $table->json('checklist')->nullable()->after('notes');
            $table->decimal('penalty_amount', 15, 2)->nullable()->after('checklist');
            $table->string('penalty_reason')->nullable()->after('penalty_amount');
            $table->timestamp('signed_at')->nullable()->after('signed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('handover_logs', function (Blueprint $table) {
            $table->dropColumn(['checklist', 'penalty_amount', 'penalty_reason', 'signed_at']);
        });
    }
};
