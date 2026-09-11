<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CitySeeder::class,
            CategorySeeder::class,
            AdminSeeder::class,
            VendorSeeder::class,
            CustomerSeeder::class,
            CarSeeder::class,
            SettingSeeder::class,
            ReviewSeeder::class,
            ComplaintCategorySeeder::class,
        ]);
    }
}
