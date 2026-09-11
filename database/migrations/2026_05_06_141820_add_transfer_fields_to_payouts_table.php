<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('transfer_reference')->nullable()->after('paid_at');
            $table->text('notes')->nullable()->after('transfer_reference');
            $table->foreignId('confirmed_by')->nullable()->after('notes')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['transfer_reference', 'notes', 'confirmed_by']);
        });
    }
};
