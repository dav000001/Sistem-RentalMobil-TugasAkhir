<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'commission_percent' => '10',
            'default_timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'payout_schedule' => 'weekly',
            'platform_name' => 'Rental Mobil',
            'platform_email' => 'admin@rentalmobil.com',
            'platform_phone' => '081234567890',
        ];

        foreach ($settings as $key => $value) {
            Setting::create([
                'key' => $key,
                'value' => $value,
            ]);
        }
    }
}