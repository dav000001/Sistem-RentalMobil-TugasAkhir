<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // complaints.reporter_id → SET NULL (vendor/customer bisa dihapus)
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['reporter_id']);
            $table->dropForeign(['resolved_by']);
            $table->foreignId('reporter_id')->nullable()->change();
            $table->foreignId('resolved_by')->nullable()->change();
            $table->foreign('reporter_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
        });

        // complaint_resolutions.admin_id → SET NULL
        Schema::table('complaint_resolutions', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->foreignId('admin_id')->nullable()->change();
            $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
        });

        // complaint_responses.author_id → SET NULL
        Schema::table('complaint_responses', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
            $table->foreignId('author_id')->nullable()->change();
            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
        });

        // disputes.admin_id & opened_by_user_id → SET NULL
        Schema::table('disputes', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropForeign(['opened_by_user_id']);
            $table->foreignId('admin_id')->nullable()->change();
            $table->foreignId('opened_by_user_id')->nullable()->change();
            $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('opened_by_user_id')->references('id')->on('users')->nullOnDelete();
        });

        // handover_logs.signed_by_user_id → SET NULL
        Schema::table('handover_logs', function (Blueprint $table) {
            $table->dropForeign(['signed_by_user_id']);
            $table->foreignId('signed_by_user_id')->nullable()->change();
            $table->foreign('signed_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Kembalikan ke RESTRICT
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['reporter_id']);
            $table->dropForeign(['resolved_by']);
            $table->foreign('reporter_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('resolved_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('complaint_resolutions', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->foreign('admin_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('complaint_responses', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
            $table->foreign('author_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('disputes', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropForeign(['opened_by_user_id']);
            $table->foreign('admin_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('opened_by_user_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('handover_logs', function (Blueprint $table) {
            $table->dropForeign(['signed_by_user_id']);
            $table->foreign('signed_by_user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }
};
