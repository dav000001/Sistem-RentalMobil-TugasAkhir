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
            $table->string('business_type', 32)->nullable()->after('business_name');
            $table->string('fleet_size_estimate', 16)->nullable()->after('city_id');
            $table->string('lead_source', 50)->nullable()->after('fleet_size_estimate');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['business_type', 'fleet_size_estimate', 'lead_source']);
        });
    }
};
