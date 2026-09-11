<?php

namespace App\Filament\Admin\Resources\VendorResource\Pages;

use App\Enums\VendorStatus;
use App\Filament\Admin\Resources\VendorResource;
use App\Services\Vendor\VendorVerificationService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVendor extends EditRecord
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        $status = $this->record->status instanceof VendorStatus
            ? $this->record->status->value
            : $this->record->status;

        $actions = [];

        if ($status === 'approved') {
            // Vendor aktif — ganti Hapus dengan Bekukan
            $actions[] = Actions\Action::make('suspend')
                ->label('Bekukan Akun')
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Alasan Pembekuan')
                        ->required()
                        ->rows(3)
                        ->placeholder('Jelaskan alasan pembekuan...'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Bekukan Akun Vendor')
                ->modalDescription('Vendor tidak bisa menerima booking baru dan semua mobil akan di-unpublish.')
                ->action(function (array $data) {
                    try {
                        app(VendorVerificationService::class)->suspend($this->record, auth('admin')->user(), $data['reason']);
                        Notification::make()->title('Akun vendor dibekukan')->warning()->send();
                        $this->redirect(VendorResource::getUrl('view', ['record' => $this->record]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
                    }
                });

        } elseif ($status === 'rejected') {
            // Vendor ditolak — bisa dibuka kembali atau dihapus
            $actions[] = Actions\Action::make('reopen')
                ->label('Buka Kembali')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Buka Kembali Pendaftaran')
                ->modalDescription('Status vendor akan dikembalikan ke Pending agar bisa submit ulang dokumen.')
                ->action(function () {
                    try {
                        // Reset ke pending agar vendor bisa submit ulang
                        $this->record->update(['status' => VendorStatus::Pending->value]);
                        \App\Models\VendorStatusLog::create([
                            'vendor_id'   => $this->record->id,
                            'actor_id'    => auth('admin')->id(),
                            'from_status' => 'rejected',
                            'to_status'   => 'pending',
                            'reason'      => 'Dibuka kembali oleh admin untuk submit ulang dokumen',
                        ]);
                        Notification::make()->title('Pendaftaran dibuka kembali')->success()->send();
                        $this->redirect(VendorResource::getUrl('view', ['record' => $this->record]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
                    }
                });

            // Hapus hanya untuk vendor yang ditolak
            $actions[] = Actions\DeleteAction::make()
                ->label('Hapus Permanen')
                ->modalHeading('Hapus Data Vendor')
                ->modalDescription('Data vendor akan dihapus permanen. Akun user tetap ada. Tindakan ini tidak bisa dibatalkan.');

        } elseif ($status === 'suspended') {
            // Vendor dibekukan — bisa diaktifkan kembali
            $actions[] = Actions\Action::make('unsuspend')
                ->label('Aktifkan Kembali')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Aktifkan Kembali Vendor')
                ->modalDescription('Semua mobil yang dibekukan akan dipublish ulang.')
                ->action(function () {
                    try {
                        app(VendorVerificationService::class)->unsuspend($this->record, auth('admin')->user());
                        Notification::make()->title('Vendor diaktifkan kembali')->success()->send();
                        $this->redirect(VendorResource::getUrl('view', ['record' => $this->record]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
                    }
                });
        }

        return $actions;
    }
}
