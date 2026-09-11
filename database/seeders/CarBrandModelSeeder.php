<?php

namespace Database\Seeders;

use App\Models\CarBrand;
use App\Models\CarModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CarBrandModelSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Toyota' => [
                'Avanza', 'Innova', 'Innova Reborn', 'Fortuner', 'Rush', 'Kijang',
                'Calya', 'Sienta', 'Alphard', 'Veloz', 'Raize', 'Yaris', 'Camry',
                'Corolla', 'Hilux', 'Land Cruiser', 'Prado', 'HiAce', 'Voxy',
            ],
            'Honda' => [
                'Brio', 'Jazz', 'City', 'Civic', 'Accord', 'CR-V', 'HR-V',
                'BR-V', 'WR-V', 'Mobilio', 'Freed', 'Odyssey', 'Passport',
            ],
            'Daihatsu' => [
                'Ayla', 'Sigra', 'Xenia', 'Terios', 'Rocky', 'Luxio', 'Gran Max',
                'Taft', 'Feroza',
            ],
            'Mitsubishi' => [
                'Pajero Sport', 'Outlander', 'Xpander', 'Xpander Cross',
                'Eclipse Cross', 'Galant', 'Lancer', 'Triton', 'Colt',
                'L300', 'Delica',
            ],
            'Suzuki' => [
                'Ertiga', 'XL7', 'Ignis', 'Baleno', 'Swift', 'Jimny',
                'Karimun', 'APV', 'Carry',
            ],
            'Nissan' => [
                'Grand Livina', 'Livina', 'X-Trail', 'Serena', 'March',
                'Juke', 'Teana', 'Terra', 'Navara', 'Evalia',
            ],
            'Hyundai' => [
                'Creta', 'Tucson', 'Santa Fe', 'Staria', 'Stargazer',
                'Ioniq 5', 'Palisade', 'Kona',
            ],
            'Kia' => [
                'Sportage', 'Sorento', 'Carnival', 'Seltos', 'Picanto',
            ],
            'Wuling' => [
                'Confero', 'Cortez', 'Almaz', 'Air EV', 'BinguoEV', 'Formo',
            ],
            'Chery' => [
                'Tiggo 5X', 'Tiggo 7 Pro', 'Tiggo 8 Pro', 'Omoda 5',
            ],
            'BMW' => [
                'Series 3', 'Series 5', 'Series 7', 'X1', 'X3', 'X5', 'X7', 'iX',
            ],
            'Mercedes-Benz' => [
                'C-Class', 'E-Class', 'S-Class', 'GLC', 'GLE', 'GLS', 'Vito',
            ],
            'Isuzu' => [
                'Panther', 'D-Max', 'Elf', 'Traga',
            ],
            'Mazda' => [
                'CX-3', 'CX-5', 'CX-8', 'CX-30', 'Mazda2', 'Mazda3', 'Mazda6',
            ],
            'Ford' => [
                'EcoSport', 'Everest', 'Ranger', 'Explorer',
            ],
            'Chevrolet' => [
                'Trax', 'Captiva', 'Spin', 'Trailblazer',
            ],
            'DFSK' => [
                'Glory 580', 'Glory i-Auto', 'Super Cab',
            ],
        ];

        foreach ($data as $brandName => $modelNames) {
            $brand = CarBrand::firstOrCreate(
                ['slug' => Str::slug($brandName)],
                [
                    'name'        => $brandName,
                    'source'      => 'seeder',
                    'is_verified' => true,
                ]
            );

            foreach ($modelNames as $modelName) {
                CarModel::firstOrCreate(
                    [
                        'car_brand_id' => $brand->id,
                        'slug'         => Str::slug($modelName),
                    ],
                    [
                        'name'        => $modelName,
                        'source'      => 'seeder',
                        'is_verified' => true,
                    ]
                );
            }
        }
    }
}
