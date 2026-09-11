<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Nolkan dulu data taxes yang ada agar tidak ada sisa
        DB::table('bookings')->where('taxes', '>', 0)->orderBy('id')->each(function ($booking) {
            // Kurangi total dengan taxes agar balance kembali
            DB::table('bookings')
                ->where('id', $booking->id)
                ->update([
                    'total' => $booking->total - $booking->taxes,
                    'taxes' => 0,
                ]);
        });

        // Drop kolom taxes
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('taxes');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('taxes', 10, 2)->default(0)->after('addon_fees');
        });
    }
};
