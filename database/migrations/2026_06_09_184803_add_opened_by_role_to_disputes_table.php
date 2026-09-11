<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            // Siapa yang membuka sengketa: 'customer' atau 'vendor'
            $table->enum('opened_by_role', ['customer', 'vendor'])
                  ->default('customer')
                  ->after('opened_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->dropColumn('opened_by_role');
        });
    }
};
