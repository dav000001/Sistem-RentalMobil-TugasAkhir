<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $t) {
            $t->foreignId('current_subscription_id')
                ->nullable()
                ->after('status')
                ->constrained('vendor_subscriptions')
                ->nullOnDelete();
            $t->index('current_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $t) {
            $t->dropForeign(['current_subscription_id']);
            $t->dropIndex(['current_subscription_id']);
            $t->dropColumn('current_subscription_id');
        });
    }
};
