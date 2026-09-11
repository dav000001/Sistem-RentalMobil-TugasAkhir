<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('plan', 20)->default('free')->after('status');
            $table->timestamp('plan_upgraded_at')->nullable()->after('plan');
            $table->timestamp('plan_expires_at')->nullable()->after('plan_upgraded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['plan', 'plan_upgraded_at', 'plan_expires_at']);
        });
    }
};
