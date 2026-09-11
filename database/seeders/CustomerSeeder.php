<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Budi Santoso',
                'email' => 'customer1@gmail.com',
                'phone' => '081234567893',
                'full_name' => 'Budi Santoso',
            ],
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'customer2@gmail.com',
                'phone' => '081234567894',
                'full_name' => 'Siti Nurhaliza',
            ],
        ];

        foreach ($customers as $customerData) {
            $user = User::create([
                'name' => $customerData['name'],
                'email' => $customerData['email'],
                'phone' => $customerData['phone'],
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            Customer::create([
                'user_id' => $user->id,
                'full_name' => $customerData['full_name'],
                'verification_status' => 'verified',
            ]);
        }
    }
}
