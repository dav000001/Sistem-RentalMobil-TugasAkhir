<?php

namespace App\Filament\Admin\Resources\VendorSubscriptionResource\Pages;

use App\Filament\Admin\Resources\VendorSubscriptionResource;
use Filament\Resources\Pages\ListRecords;

class ListVendorSubscriptions extends ListRecords
{
    protected static string $resource = VendorSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
