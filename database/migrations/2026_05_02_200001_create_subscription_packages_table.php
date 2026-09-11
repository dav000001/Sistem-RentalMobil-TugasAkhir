<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_packages', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();          // free, basic, premium
            $t->string('name', 100);
            $t->text('description');
            $t->unsignedInteger('price_per_month');    // dalam Rupiah
            $t->decimal('commission_rate', 5, 2);      // 12.00 = 12%
            $t->json('features');                      // max_cars, max_photos, priority_search, ...
            $t->unsignedInteger('rank')->default(0);   // 0=free, 1=basic, 2=premium
            $t->boolean('is_active')->default(true);
            $t->boolean('allow_self_signup')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_packages');
    }
};
