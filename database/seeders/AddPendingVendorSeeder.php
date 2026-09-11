<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AddPendingVendorSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'vendor3@gmail.com')->exists()) {
            $this->command->info('vendor3@gmail.com already exists, skipping.');
            return;
        }

        $city = City::where('name', 'Surabaya')->first();

        if (!$city) {
            $this->command->warn('City Surabaya not found, skipping.');
            return;
        }

        $user = User::create([
            'name'              => 'Surabaya Rent Car',
            'email'             => 'vendor3@gmail.com',
            'phone'             => '081234567893',
            'password'          => Hash::make('password'),
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        Vendor::create([
            'user_id'            => $user->id,
            'business_name'      => 'Surabaya Rent Car',
            'address'            => 'Jl. Pemuda No. 789, Surabaya',
            'city_id'            => $city->id,
            'bank_name'          => 'BNI',
            'bank_account_number' => '1122334455',
            'bank_account_name'  => 'Surabaya Rent Car',
            'status'             => 'pending',
        ]);

        $this->command->info('Pending vendor vendor3@gmail.com created successfully.');
    }
}
