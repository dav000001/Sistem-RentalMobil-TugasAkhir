<?php

namespace App\Filament\Vendor\Resources\LateReturnResource\Pages;

use App\Filament\Vendor\Resources\LateReturnResource;
use Filament\Resources\Pages\ListRecords;

class ListLateReturns extends ListRecords
{
    protected static string $resource = LateReturnResource::class;

    public function getTitle(): string
    {
        return '⏰ Laporan Keterlambatan Pengembalian';
    }
}
