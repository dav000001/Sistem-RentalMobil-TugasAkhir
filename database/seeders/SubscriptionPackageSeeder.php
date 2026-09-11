<?php

namespace Database\Seeders;

use App\Models\SubscriptionPackage;
use Illuminate\Database\Seeder;

class SubscriptionPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'code'             => 'free',
                'name'             => 'Free',
                'description'      => 'Paket gratis untuk memulai. Cocok untuk vendor baru dengan armada kecil.',
                'price_per_month'  => 0,
                'commission_rate'  => 12.00,
                'rank'             => 0,
                'is_active'        => true,
                'allow_self_signup'=> true,
                'features'         => [
                    'max_cars'        => 3,
                    'max_photos'      => 3,
                    'priority_search' => false,
                    'analytics'       => false,
                    'payout_schedule' => 'weekly',
                    'support'         => 'email',
                    'badge_verified'  => false,
                ],
            ],
            [
                'code'             => 'basic',
                'name'             => 'Basic',
                'description'      => 'Paket untuk vendor yang ingin berkembang. Komisi lebih rendah dan fitur lebih lengkap.',
                'price_per_month'  => 99000,
                'commission_rate'  => 9.00,
                'rank'             => 1,
                'is_active'        => true,
                'allow_self_signup'=> true,
                'features'         => [
                    'max_cars'        => 10,
                    'max_photos'      => 6,
                    'priority_search' => true,
                    'analytics'       => false,
                    'payout_schedule' => 'weekly',
                    'support'         => 'whatsapp',
                    'badge_verified'  => true,
                ],
            ],
            [
                'code'             => 'premium',
                'name'             => 'Premium',
                'description'      => 'Paket terbaik untuk vendor profesional. Komisi terendah, fitur lengkap, prioritas tertinggi.',
                'price_per_month'  => 249000,
                'commission_rate'  => 5.00,
                'rank'             => 2,
                'is_active'        => true,
                'allow_self_signup'=> true,
                'features'         => [
                    'max_cars'        => -1, // unlimited
                    'max_photos'      => -1, // unlimited
                    'priority_search' => true,
                    'analytics'       => true,
                    'payout_schedule' => 'twice_weekly',
                    'support'         => 'dedicated',
                    'badge_verified'  => true,
                ],
            ],
        ];

        foreach ($packages as $pkg) {
            SubscriptionPackage::updateOrCreate(
                ['code' => $pkg['code']],
                $pkg
            );
        }

        $this->command->info('✅ 3 subscription packages seeded (Free, Basic, Premium)');
    }
}
