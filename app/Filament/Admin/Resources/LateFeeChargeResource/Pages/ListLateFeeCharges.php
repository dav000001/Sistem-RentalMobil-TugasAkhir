<?php

namespace App\Filament\Admin\Resources\LateFeeChargeResource\Pages;

use App\Filament\Admin\Resources\LateFeeChargeResource;
use Filament\Resources\Pages\ListRecords;

class ListLateFeeCharges extends ListRecords
{
    protected static string $resource = LateFeeChargeResource::class;

    public function getTitle(): string
    {
        return '💸 Tagihan Denda Keterlambatan';
    }
}
