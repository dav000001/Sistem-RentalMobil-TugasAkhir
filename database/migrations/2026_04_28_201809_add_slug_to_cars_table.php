<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('plate_number');
        });

        // Generate slug untuk data existing
        DB::table('cars')->get()->each(function ($car) {
            DB::table('cars')->where('id', $car->id)->update([
                'slug' => \Illuminate\Support\Str::slug($car->brand . '-' . $car->model . '-' . $car->year . '-' . $car->plate_number)
            ]);
        });

        Schema::table('cars', function (Blueprint $table) {
            $table->string('slug')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
