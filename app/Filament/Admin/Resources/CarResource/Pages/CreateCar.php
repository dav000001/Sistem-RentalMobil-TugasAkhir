<?php

namespace App\Filament\Admin\Resources\CarResource\Pages;

use App\Filament\Admin\Resources\CarResource;
use App\Models\CarPricing;
use Filament\Resources\Pages\CreateRecord;

class CreateCar extends CreateRecord
{
    protected static string $resource = CarResource::class;

    protected array $pricingData = [];

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Tambah Mobil';
    }

    public function getBreadcrumb(): string
    {
        return 'Tambah Mobil';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->resolveBrandAndModel($data);

        if (empty($data['is_monthly_available'])) {
            $data['is_monthly_available'] = false;
            $data['monthly_price'] = null;
        }

        if (($data['rental_option'] ?? 'self_drive_only') === 'self_drive_only') {
            $data['with_driver_price'] = null;
        }

        $this->pricingData = [
            'daily_price'       => $data['daily_price'] ?? null,
            'monthly_price'     => $data['monthly_price'] ?? null,
            'with_driver_price' => $data['with_driver_price'] ?? null,
            'fuel_included'     => $data['fuel_included'] ?? false,
        ];

        unset(
            $data['daily_price'],
            $data['monthly_price'],
            $data['with_driver_price'],
            $data['fuel_included'],
            $data['brand_manual'],
            $data['model_manual'],
        );

        if (empty($data['slug'])) {
            $data['slug'] = \Illuminate\Support\Str::slug(
                ($data['brand'] ?? '') . '-' . ($data['model'] ?? '') . '-' . ($data['year'] ?? '') . '-' . ($data['plate_number'] ?? '')
            );
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if (!empty(array_filter($this->pricingData))) {
            CarPricing::create(array_merge(
                $this->pricingData,
                ['car_id' => $this->record->id]
            ));
        }
    }

    protected function resolveBrandAndModel(array $data): array
    {
        $brandIdRaw  = $data['car_brand_id'] ?? null;
        $modelIdRaw  = $data['car_model_id'] ?? null;
        $brandManual = trim($data['brand_manual'] ?? '');
        $modelManual = trim($data['model_manual'] ?? '');

        if ($brandIdRaw === 'other' || empty($brandIdRaw)) {
            $brand = \App\Models\CarBrand::findOrCreateFromInput($brandManual ?: 'Unknown');
            $data['car_brand_id'] = $brand->id;
            $data['brand']        = $brand->name;
            $carModel = \App\Models\CarModel::findOrCreateFromInput($brand->id, $modelManual ?: 'Unknown');
            $data['car_model_id'] = $carModel->id;
            $data['model']        = $carModel->name;
        } else {
            $brand = \App\Models\CarBrand::find($brandIdRaw);
            $data['brand'] = $brand?->name ?? $brandManual;
            if ($modelIdRaw === 'other' || empty($modelIdRaw)) {
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
}
