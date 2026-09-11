<?php

namespace App\Filament\Admin\Resources\CustomerRefundResource\Pages;

use App\Filament\Admin\Resources\CustomerRefundResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerRefunds extends ListRecords
{
    protected static string $resource = CustomerRefundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('+ Buat Refund'),
        ];
    }
}
