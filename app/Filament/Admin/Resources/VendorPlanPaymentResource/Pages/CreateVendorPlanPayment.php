<?php

namespace App\Filament\Admin\Resources\VendorPlanPaymentResource\Pages;

use App\Filament\Admin\Resources\VendorPlanPaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorPlanPayment extends CreateRecord
{
    protected static string $resource = VendorPlanPaymentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
