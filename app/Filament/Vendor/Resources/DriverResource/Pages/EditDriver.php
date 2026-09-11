<?php

namespace App\Filament\Vendor\Resources\DriverResource\Pages;

use App\Filament\Vendor\Resources\DriverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDriver extends EditRecord
{
    protected static string $resource = DriverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus Sopir')
                ->requiresConfirmation()
                ->before(function ($action, $record) {
                    // Cegah hapus jika masih ada booking aktif
                    $active = $record->bookings()
                        ->whereIn('status', ['awaiting_vendor', 'confirmed', 'ongoing'])
                        ->count();
                    if ($active > 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('Tidak Dapat Dihapus')
                            ->body("Sopir ini masih ditugaskan di {$active} booking aktif.")
                            ->danger()
                            ->send();
                        $action->halt();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
