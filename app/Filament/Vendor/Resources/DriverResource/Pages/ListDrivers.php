<?php

namespace App\Filament\Vendor\Resources\DriverResource\Pages;

use App\Filament\Vendor\Resources\DriverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDrivers extends ListRecords
{
    protected static string $resource = DriverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('+ Tambah Sopir'),
        ];
    }
}
