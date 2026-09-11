<?php

namespace App\Filament\Admin\Resources\CarResource\Pages;

use App\Filament\Admin\Resources\CarResource;
use App\Models\CarPricing;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCar extends EditRecord
{
    protected static string $resource = CarResource::class;

    protected array $pricingData = [];

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $pricing = $this->record->pricing;
        if ($pricing) {
            $data['daily_price']       = $pricing->daily_price;
            $data['monthly_price']     = $pricing->monthly_price;
            $data['with_driver_price'] = $pricing->with_driver_price;
            $data['fuel_included']     = $pricing->fuel_included;
        }

        $data['is_monthly_available'] = (bool) $this->record->is_monthly_available;
        $data['rental_option']        = $this->record->rental_option ?? 'self_drive_only';
        $data['car_brand_id']         = $this->record->car_brand_id;
        $data['car_model_id']         = $this->record->car_model_id;
        $data['brand_manual']         = null;
        $data['model_manual']         = null;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

        return $data;
    }

    protected function afterSave(): void
    {
        CarPricing::updateOrCreate(
            ['car_id' => $this->record->id],
            $this->pricingData
        );
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
