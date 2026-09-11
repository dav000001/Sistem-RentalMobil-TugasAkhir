<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'vendor', 'customer'])->default('customer')->after('email');
        });

        // Set role berdasarkan relasi yang sudah ada
        // User yang punya record di tabel vendors → role vendor
        DB::statement("
            UPDATE users
            SET role = 'vendor'
            WHERE id IN (SELECT user_id FROM vendors)
        ");

        // User yang punya record di tabel customers → role customer
        DB::statement("
            UPDATE users
            SET role = 'customer'
            WHERE id IN (SELECT user_id FROM customers)
        ");

        // User yang tidak punya relasi vendor/customer → tetap admin (default sudah customer,
        // jadi kita set admin untuk yang tidak ada di kedua tabel)
        DB::statement("
            UPDATE users
            SET role = 'admin'
            WHERE id NOT IN (SELECT user_id FROM vendors)
              AND id NOT IN (SELECT user_id FROM customers)
        ");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
