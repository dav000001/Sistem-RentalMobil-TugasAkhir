<?php

namespace App\Filament\Vendor\Resources\CarResource\Pages;

use App\Filament\Vendor\Resources\CarResource;
use App\Models\CarPricing;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCar extends CreateRecord
{
    protected static string $resource = CarResource::class;

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Tambah Armada';
    }

    public function getBreadcrumb(): string
    {
        return 'Tambah Armada';
    }

    // Simpan pricing data sementara sebelum create
    protected array $pricingData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $vendor = auth('vendor')->user()?->vendor;

        if (!$vendor) {
            $this->halt();
            \Filament\Notifications\Notification::make()
                ->title('Akses Ditolak')
                ->body('Akun Anda tidak terdaftar sebagai vendor.')
                ->danger()
                ->send();
            return $data;
        }

        // Cek subscription aktif
        $service = app(\App\Services\VendorSubscriptionService::class);
        $sub = $service->getCurrent($vendor);

        if (!$sub || !$sub->canAcceptBookings()) {
            $this->halt();
            \Filament\Notifications\Notification::make()
                ->title('Paket Tidak Aktif')
                ->body('Anda tidak dapat menambah mobil karena tidak memiliki paket berlangganan aktif. Silakan pilih paket terlebih dahulu.')
                ->danger()
                ->send();
            return $data;
        }

        // Cek limit mobil berdasarkan paket
        if (!$vendor->canAddMoreCars()) {
            $maxCars = $sub->snapshot_features['max_cars'] ?? '?';
            $this->halt();
            \Filament\Notifications\Notification::make()
                ->title('Limit Mobil Tercapai')
                ->body('Paket ' . $sub->package->name . ' Anda hanya mengizinkan maksimal ' . $maxCars . ' mobil aktif. Upgrade paket untuk menambah lebih banyak mobil.')
                ->danger()
                ->send();
            return $data;
        }

        // ── Resolve brand & model (dropdown atau manual) ─────────────
        $data = $this->resolveBrandAndModel($data);

        // ── Bersihkan monthly_price jika sewa bulanan tidak aktif ────
        if (empty($data['is_monthly_available'])) {
            $data['is_monthly_available'] = false;
            $data['monthly_price'] = null;
        }

        // ── Bersihkan with_driver_price jika self_drive_only ─────────
        if (($data['rental_option'] ?? 'self_drive_only') === 'self_drive_only') {
            $data['with_driver_price'] = null;
        }

        // Extract pricing data sebelum disimpan ke cars table
        $this->pricingData = [
            'daily_price'          => $data['daily_price'] ?? null,
            'monthly_price'        => $data['monthly_price'] ?? null,
            'with_driver_price'    => $data['with_driver_price'] ?? null,
            'fuel_included'        => $data['fuel_included'] ?? false,
        ];

        // Extract foto
        $this->photoFiles = $data['car_photos'] ?? [];

        unset(
            $data['daily_price'],
            $data['monthly_price'],
            $data['with_driver_price'],
            $data['fuel_included'],
            $data['car_photos'],
            $data['brand_manual'],
            $data['model_manual'],
        );

        $data['vendor_id'] = $vendor->id;
        $data['status']    = 'published';
        $data['slug'] = \Illuminate\Support\Str::slug(
            $data['brand'] . '-' . $data['model'] . '-' . $data['year'] . '-' . $data['plate_number']
        );

        return $data;
    }

    /**
     * Resolve brand_id dan model_id dari input dropdown atau manual ketik.
     * Mengisi $data['brand'], $data['model'], $data['car_brand_id'], $data['car_model_id'].
     */
    protected function resolveBrandAndModel(array $data): array
    {
        $brandIdRaw  = $data['car_brand_id'] ?? null;
        $modelIdRaw  = $data['car_model_id'] ?? null;
        $brandManual = trim($data['brand_manual'] ?? '');
        $modelManual = trim($data['model_manual'] ?? '');

        if ($brandIdRaw === 'other' || empty($brandIdRaw)) {
            // Brand baru dari input manual
            $brand = \App\Models\CarBrand::findOrCreateFromInput($brandManual ?: 'Unknown');
            $data['car_brand_id'] = $brand->id;
            $data['brand']        = $brand->name;

            // Model juga harus manual karena brand baru
            $carModel = \App\Models\CarModel::findOrCreateFromInput($brand->id, $modelManual ?: 'Unknown');
            $data['car_model_id'] = $carModel->id;
            $data['model']        = $carModel->name;
        } else {
            $brand = \App\Models\CarBrand::find($brandIdRaw);
            $data['brand'] = $brand?->name ?? $brandManual;

            if ($modelIdRaw === 'other' || empty($modelIdRaw)) {
                // Model baru dari input manual
                $carModel = \App\Models\CarModel::findOrCreateFromInput((int) $brandIdRaw, $modelManual ?: 'Unknown');
                $data['car_model_id'] = $carModel->id;
                $data['model']        = $carModel->name;
            } else {
                $carModel = \App\Models\CarModel::find($modelIdRaw);
                $data['model'] = $carModel?->name ?? $modelManual;
            }
        }

        return $data;
    }

    protected array $photoFiles = [];

    protected function afterCreate(): void
    {
        if (!empty(array_filter($this->pricingData))) {
            CarPricing::create(array_merge(
                $this->pricingData,
                ['car_id' => $this->record->id]
            ));
        }

        // Simpan foto
        foreach ($this->photoFiles as $index => $path) {
            \App\Models\CarPhoto::create([
                'car_id'     => $this->record->id,
                'path'       => '/storage/' . $path,
                'sort_order' => $index,
            ]);
        }
    }
}
