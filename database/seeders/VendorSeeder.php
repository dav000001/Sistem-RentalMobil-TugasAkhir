<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vendor;
use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            [
                'name' => 'Rental Mobil Jakarta',
                'email' => 'vendor1@gmail.com',
                'phone' => '081234567891',
                'business_name' => 'CV. Rental Mobil Jakarta',
                'address' => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'city' => 'Jakarta',
                'bank_name' => 'BCA',
                'bank_account_number' => '1234567890',
                'bank_account_name' => 'CV. Rental Mobil Jakarta',
                'status' => 'approved',
            ],
            [
                'name' => 'Bandung Car Rental',
                'email' => 'vendor2@gmail.com',
                'phone' => '081234567892',
                'business_name' => 'PT. Bandung Car Rental',
                'address' => 'Jl. Asia Afrika No. 456, Bandung',
                'city' => 'Bandung',
                'bank_name' => 'Mandiri',
                'bank_account_number' => '0987654321',
                'bank_account_name' => 'PT. Bandung Car Rental',
                'status' => 'approved',
            ],
            [
                'name' => 'Surabaya Rent Car',
                'email' => 'vendor3@gmail.com',
                'phone' => '081234567893',
                'business_name' => 'Surabaya Rent Car',
                'address' => 'Jl. Pemuda No. 789, Surabaya',
                'city' => 'Surabaya',
                'bank_name' => 'BNI',
                'bank_account_number' => '1122334455',
                'bank_account_name' => 'Surabaya Rent Car',
                'status' => 'pending',
            ],
        ];

        foreach ($vendors as $vendorData) {
            $user = User::create([
                'name' => $vendorData['name'],
                'email' => $vendorData['email'],
                'phone' => $vendorData['phone'],
                'password' => Hash::make('password'),
                'role' => 'vendor',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            $city = City::where('name', $vendorData['city'])->first();

            Vendor::create([
                'user_id' => $user->id,
                'business_name' => $vendorData['business_name'],
                'address' => $vendorData['address'],
                'city_id' => $city->id,
                'bank_name' => $vendorData['bank_name'],
                'bank_account_number' => $vendorData['bank_account_number'],
                'bank_account_name' => $vendorData['bank_account_name'],
                'status' => $vendorData['status'] ?? 'approved',
            ]);
        }
    }
}
