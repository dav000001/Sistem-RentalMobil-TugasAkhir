<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\CarPricing;
use App\Models\Vendor;
use App\Models\Category;
use App\Models\City;
use Illuminate\Database\Seeder;

class CarSeeder extends Seeder
{
    public function run(): void
    {
        $cars = [
            [
                'vendor' => 'CV. Rental Mobil Jakarta',
                'category' => 'MPV',
                'city' => 'Jakarta',
                'brand' => 'Toyota',
                'model' => 'Avanza',
                'year' => 2022,
                'plate_number' => 'B 1234 ABC',
                'transmission' => 'manual',
                'fuel' => 'bensin',
                'seats' => 7,
                'luggage' => 2,
                'features' => ['AC', 'Audio', 'USB Charger'],
                'description' => 'Toyota Avanza 2022 dalam kondisi prima, cocok untuk keluarga',
                'daily_price' => 300000,
                'with_driver_price' => 150000,
                'photos' => [
                    '/images/cars/toyota-avanza.jpg',
                ],
            ],
            [
                'vendor' => 'CV. Rental Mobil Jakarta',
                'category' => 'City Car',
                'city' => 'Jakarta',
                'brand' => 'Honda',
                'model' => 'Brio',
                'year' => 2021,
                'plate_number' => 'B 5678 DEF',
                'transmission' => 'automatic',
                'fuel' => 'bensin',
                'seats' => 5,
                'luggage' => 1,
                'features' => ['AC', 'Audio', 'Power Steering'],
                'description' => 'Honda Brio matic, irit dan nyaman untuk dalam kota',
                'daily_price' => 250000,
                'with_driver_price' => 120000,
                'photos' => [
                    '/images/cars/honda-brio.jpg',
                ],
            ],
            [
                'vendor' => 'PT. Bandung Car Rental',
                'category' => 'SUV',
                'city' => 'Bandung',
                'brand' => 'Mitsubishi',
                'model' => 'Pajero Sport',
                'year' => 2023,
                'plate_number' => 'D 9012 GHI',
                'transmission' => 'automatic',
                'fuel' => 'diesel',
                'seats' => 7,
                'luggage' => 3,
                'features' => ['AC', 'Audio', 'GPS', 'Leather Seat', '4WD'],
                'description' => 'Mitsubishi Pajero Sport terbaru, cocok untuk adventure',
                'daily_price' => 800000,
                'with_driver_price' => 200000,
                'photos' => [
                    '/images/cars/mitsubishi-pajero.jpg',
                ],
            ],
            [
                'vendor' => 'PT. Bandung Car Rental',
                'category' => 'Premium',
                'city' => 'Bandung',
                'brand' => 'Toyota',
                'model' => 'Alphard',
                'year' => 2022,
                'plate_number' => 'D 3456 JKL',
                'transmission' => 'automatic',
                'fuel' => 'bensin',
                'seats' => 8,
                'luggage' => 2,
                'features' => ['AC', 'Audio', 'GPS', 'Leather Seat', 'Captain Seat', 'TV'],
                'description' => 'Toyota Alphard mewah untuk acara special',
                'daily_price' => 1500000,
                'with_driver_price' => 300000,
                'photos' => [
                    '/images/cars/toyota-alphard.jpg',
                ],
            ],
            [
                'vendor' => 'CV. Rental Mobil Jakarta',
                'category' => 'Sedan',
                'city' => 'Jakarta',
                'brand' => 'Honda',
                'model' => 'Civic',
                'year' => 2021,
                'plate_number' => 'B 7890 MNO',
                'transmission' => 'automatic',
                'fuel' => 'bensin',
                'seats' => 5,
                'luggage' => 1,
                'features' => ['AC', 'Audio', 'Sunroof', 'Cruise Control'],
                'description' => 'Honda Civic sporty dan elegan',
                'daily_price' => 450000,
                'with_driver_price' => 150000,
                'photos' => [
                    '/images/cars/honda-civic.jpg',
                ],
            ],
        ];

        foreach ($cars as $carData) {
            $vendor = Vendor::where('business_name', $carData['vendor'])->first();
            if (!$vendor) continue;

            $category = Category::where('name', $carData['category'])->first();
            if (!$category) continue;

            $city = City::where('name', $carData['city'])->first();
            if (!$city) continue;

            $car = Car::create([
                'vendor_id' => $vendor->id,
                'category_id' => $category->id,
                'city_id' => $city->id,
                'brand' => $carData['brand'],
                'model' => $carData['model'],
                'year' => $carData['year'],
                'plate_number' => $carData['plate_number'],
                'slug' => \Illuminate\Support\Str::slug($carData['brand'] . '-' . $carData['model'] . '-' . $carData['year'] . '-' . $carData['plate_number']),
                'transmission' => $carData['transmission'],
                'fuel' => $carData['fuel'],
                'seats' => $carData['seats'],
                'luggage' => $carData['luggage'],
                'features' => $carData['features'],
                'description' => $carData['description'],
                'status' => 'published',
            ]);

            CarPricing::create([
                'car_id' => $car->id,
                'daily_price' => $carData['daily_price'],
                'weekly_price' => $carData['daily_price'] * 6,
                'monthly_price' => $carData['daily_price'] * 25,
                'with_driver_price' => $carData['with_driver_price'],
                'fuel_included' => false,
                'delivery_fee_per_km' => 5000,
            ]);

            // Save photos
            foreach ($carData['photos'] as $index => $photoUrl) {
                \App\Models\CarPhoto::create([
                    'car_id' => $car->id,
                    'path' => $photoUrl,
                    'sort_order' => $index,
                ]);
            }
        }
    }
}