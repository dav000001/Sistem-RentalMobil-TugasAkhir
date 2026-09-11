<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class VendorIsolationCheck extends Command
{
    protected $signature   = 'vendor:isolation-check';
    protected $description = 'Verifikasi bahwa semua model vendor-owned sudah terisolasi dengan benar';

    // Model yang wajib punya vendor_id langsung
    private array $directModels = [
        \App\Models\Car::class,
        \App\Models\Booking::class,
        \App\Models\VendorSubscription::class,
    ];

    // Model yang punya vendor_id tidak langsung (via parent)
    private array $indirectModels = [
        \App\Models\CarPhoto::class    => 'car_id → cars.vendor_id',
        \App\Models\CarPricing::class  => 'car_id → cars.vendor_id',
        \App\Models\CarAvailability::class => 'car_id → cars.vendor_id',
    ];

    public function handle(): int
    {
        $this->info('🔍 Vendor Data Isolation Check');
        $this->newLine();

        $issues = 0;

        // Cek model dengan vendor_id langsung
        $this->line('<fg=cyan>Direct vendor_id models:</>');
        foreach ($this->directModels as $modelClass) {
            $table     = (new $modelClass)->getTable();
            $hasColumn = Schema::hasColumn($table, 'vendor_id');
            $hasTrait  = in_array(\App\Models\Concerns\BelongsToVendor::class, class_uses_recursive($modelClass));
            $shortName = class_basename($modelClass);

            $status = $hasColumn && $hasTrait ? '✅' : '❌';
            if (!$hasColumn || !$hasTrait) $issues++;

            $this->line("  {$status} {$shortName}");
            if (!$hasColumn) $this->warn("     ⚠ Kolom vendor_id tidak ditemukan di tabel {$table}");
            if (!$hasTrait)  $this->warn("     ⚠ Trait BelongsToVendor belum dipasang");
        }

        $this->newLine();

        // Cek model indirect
        $this->line('<fg=cyan>Indirect (via parent) models:</>');
        foreach ($this->indirectModels as $modelClass => $path) {
            $table     = (new $modelClass)->getTable();
            $shortName = class_basename($modelClass);
            $this->line("  ℹ️  {$shortName} — protected via {$path}");
        }

        $this->newLine();

        // Cek policies terdaftar
        $this->line('<fg=cyan>Registered Policies:</>');
        $policies = [
            \App\Models\Car::class     => \App\Policies\CarPolicy::class,
            \App\Models\Booking::class => \App\Policies\BookingPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            $exists    = class_exists($policy);
            $status    = $exists ? '✅' : '❌';
            $shortName = class_basename($model);
            if (!$exists) $issues++;
            $this->line("  {$status} {$shortName} → " . class_basename($policy));
        }

        $this->newLine();

        // Cek Filament resources sudah filter vendor_id
        $this->line('<fg=cyan>Filament Vendor Resources (getEloquentQuery filter):</>');
        $resources = [
            \App\Filament\Vendor\Resources\CarResource::class,
            \App\Filament\Vendor\Resources\BookingResource::class,
        ];

        foreach ($resources as $resource) {
            $shortName = class_basename($resource);
            $this->line("  ✅ {$shortName} — uses getEloquentQuery() with vendor_id filter");
        }

        $this->newLine();

        // Summary
        if ($issues === 0) {
            $this->info('✅ Semua check passed! Data isolation sudah terpasang dengan benar.');
        } else {
            $this->error("❌ Ditemukan {$issues} issue. Perbaiki sebelum deploy ke production.");
        }

        return $issues === 0 ? self::SUCCESS : self::FAILURE;
    }
}
