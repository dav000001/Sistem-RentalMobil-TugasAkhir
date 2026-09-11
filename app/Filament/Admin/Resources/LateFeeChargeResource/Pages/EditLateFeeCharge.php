<?php

namespace App\Filament\Admin\Resources\LateFeeChargeResource\Pages;

use App\Filament\Admin\Resources\LateFeeChargeResource;
use Filament\Resources\Pages\EditRecord;

class EditLateFeeCharge extends EditRecord
{
    protected static string $resource = LateFeeChargeResource::class;

    public function getTitle(): string
    {
        return 'Detail Tagihan Denda — ' . ($this->record?->booking?->code ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
