<?php

namespace App\Filament\Admin\Resources\VendorPlanPaymentResource\Pages;

use App\Filament\Admin\Resources\VendorPlanPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendorPlanPayments extends ListRecords
{
    protected static string $resource = VendorPlanPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Buat Tagihan Manual'),
        ];
    }
}
