<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('late_fee_charges', function (Blueprint $table) {
            $table->text('dispute_rejection_reason')->nullable()->after('disputed_at');
            $table->timestamp('dispute_rejected_at')->nullable()->after('dispute_rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('late_fee_charges', function (Blueprint $table) {
            $table->dropColumn(['dispute_rejection_reason', 'dispute_rejected_at']);
        });
    }
};
