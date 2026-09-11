<?php

namespace Database\Seeders;

use App\Models\ComplaintCategory;
use Illuminate\Database\Seeder;

class ComplaintCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code'             => 'car_dirty',
                'name'             => 'Mobil Kotor / Tidak Bersih',
                'description'      => 'Mobil diserahkan dalam kondisi kotor, berbau, atau tidak terawat.',
                'severity'         => 'low',
                'vendor_sla_hours' => 12,
                'display_order'    => 1,
            ],
            [
                'code'             => 'ac_broken',
                'name'             => 'AC Rusak / Tidak Berfungsi',
                'description'      => 'AC mobil tidak berfungsi atau tidak dingin selama masa sewa.',
                'severity'         => 'medium',
                'vendor_sla_hours' => 24,
                'display_order'    => 2,
            ],
            [
                'code'             => 'late_handover',
                'name'             => 'Serah Terima Terlambat',
                'description'      => 'Vendor terlambat menyerahkan atau mengambil kembali kendaraan.',
                'severity'         => 'medium',
                'vendor_sla_hours' => 12,
                'display_order'    => 3,
            ],
            [
                'code'             => 'wrong_car',
                'name'             => 'Mobil Tidak Sesuai Pesanan',
                'description'      => 'Mobil yang diberikan berbeda dari yang dipesan (merek, model, atau tahun).',
                'severity'         => 'high',
                'vendor_sla_hours' => 24,
                'display_order'    => 4,
            ],
            [
                'code'             => 'car_breakdown',
                'name'             => 'Mobil Mogok / Kerusakan Mesin',
                'description'      => 'Mobil mengalami kerusakan mesin atau mogok selama masa sewa.',
                'severity'         => 'high',
                'vendor_sla_hours' => 24,
                'display_order'    => 5,
            ],
            [
                'code'             => 'unsafe_car',
                'name'             => 'Mobil Tidak Aman / Berbahaya',
                'description'      => 'Kondisi mobil membahayakan keselamatan pengemudi dan penumpang.',
                'severity'         => 'critical',
                'vendor_sla_hours' => 12,
                'display_order'    => 6,
            ],
            [
                'code'             => 'vendor_unresponsive',
                'name'             => 'Vendor Tidak Responsif',
                'description'      => 'Vendor tidak dapat dihubungi atau tidak merespons selama masa sewa.',
                'severity'         => 'medium',
                'vendor_sla_hours' => 24,
                'display_order'    => 7,
            ],
            [
                'code'             => 'extra_charge',
                'name'             => 'Biaya Tambahan Tidak Wajar',
                'description'      => 'Vendor mengenakan biaya tambahan yang tidak sesuai perjanjian.',
                'severity'         => 'high',
                'vendor_sla_hours' => 48,
                'display_order'    => 8,
            ],
            [
                'code'             => 'damage_dispute',
                'name'             => 'Sengketa Kerusakan Kendaraan',
                'description'      => 'Perselisihan mengenai kerusakan kendaraan yang diklaim vendor.',
                'severity'         => 'high',
                'vendor_sla_hours' => 48,
                'display_order'    => 9,
            ],
            [
                'code'             => 'accident',
                'name'             => 'Kecelakaan',
                'description'      => 'Terjadi kecelakaan selama masa sewa kendaraan.',
                'severity'         => 'critical',
                'vendor_sla_hours' => 24,
                'display_order'    => 10,
            ],
            [
                'code'             => 'theft',
                'name'             => 'Pencurian / Kehilangan',
                'description'      => 'Kendaraan atau barang bawaan hilang atau dicuri.',
                'severity'         => 'critical',
                'vendor_sla_hours' => 24,
                'display_order'    => 11,
            ],
            [
                'code'             => 'other',
                'name'             => 'Lainnya',
                'description'      => 'Masalah lain yang tidak termasuk dalam kategori di atas.',
                'severity'         => 'medium',
                'vendor_sla_hours' => 48,
                'display_order'    => 12,
            ],
        ];

        foreach ($categories as $category) {
            ComplaintCategory::updateOrCreate(
                ['code' => $category['code']],
                $category
            );
        }

        $this->command->info('ComplaintCategorySeeder: ' . count($categories) . ' kategori berhasil di-seed.');
    }
}
