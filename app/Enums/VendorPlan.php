<?php

namespace App\Enums;

enum VendorPlan: string
{
    case Free = 'free';
    case Basic = 'basic';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Basic => 'Basic',
            self::Premium => 'Premium',
        };
    }

    public function commissionRate(): float
    {
        return match ($this) {
            self::Free => 0.12,    // 12%
            self::Basic => 0.09,   // 9%
            self::Premium => 0.05, // 5%
        };
    }

    public function monthlyFee(): int
    {
        return match ($this) {
            self::Free => 0,
            self::Basic => 99000,
            self::Premium => 249000,
        };
    }

    public function maxCars(): ?int
    {
        return match ($this) {
            self::Free => 3,
            self::Basic => 10,
            self::Premium => null, // unlimited
        };
    }

    public function features(): array
    {
        return match ($this) {
            self::Free => [
                'Listing hingga 3 mobil',
                'Payout mingguan',
                'Dashboard dasar',
                'Support via email',
            ],
            self::Basic => [
                'Listing hingga 10 mobil',
                'Payout mingguan',
                'Statistik performa',
                'Prioritas tampil di pencarian',
                'Support via WhatsApp',
            ],
            self::Premium => [
                'Listing mobil tidak terbatas',
                'Payout 2× seminggu',
                'Analitik lengkap + laporan',
                'Posisi teratas di pencarian',
                'Badge "Vendor Terverifikasi"',
                'Dedicated account manager',
            ],
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Free => 'gray',
            self::Basic => 'blue',
            self::Premium => 'orange',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Free => 'bg-gray-100 text-gray-700',
            self::Basic => 'bg-blue-100 text-blue-700',
            self::Premium => 'bg-gradient-to-r from-yellow-400 to-orange-400 text-white',
        };
    }

    public static function default(): self
    {
        return self::Free;
    }
}
