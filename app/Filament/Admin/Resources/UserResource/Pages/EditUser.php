<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── Approve Verifikasi Customer ── hanya untuk role customer dengan status pending
            Action::make('approve_verification')
                ->label('✅ Setujui Verifikasi')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(function () {
                    $customer = $this->record->customer;
                    return $this->record->role === 'customer'
                        && $customer
                        && $customer->verification_status === 'pending';
                })
                ->requiresConfirmation()
                ->modalHeading('Setujui Verifikasi Customer')
                ->modalDescription('Dokumen customer akan disetujui dan customer dapat melakukan pemesanan.')
                ->action(function () {
                    $customer = $this->record->customer;
                    $customer->update([
                        'verification_status' => 'verified',
                        'rejection_reason'    => null,
                    ]);

                    try {
                        $this->record->notify(
                            new \App\Notifications\CustomerVerificationNotification('verified')
                        );
                    } catch (\Throwable) {}

                    Notification::make()
                        ->title('Verifikasi disetujui')
                        ->body('Customer ' . $this->record->name . ' sudah dapat melakukan pemesanan.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // ── Reject Verifikasi Customer ── hanya untuk role customer & status pending
            Action::make('reject_verification')
                ->label('❌ Tolak Verifikasi')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(function () {
                    $customer = $this->record->customer;
                    return $this->record->role === 'customer'
                        && $customer
                        && $customer->verification_status === 'pending';
                })
                ->form([
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Alasan Penolakan')
                        ->placeholder('Contoh: Foto KTP buram, tidak terbaca. Silakan upload ulang dengan foto yang lebih jelas.')
                        ->required()
                        ->rows(3),
                ])
                ->modalHeading('Tolak Verifikasi Customer')
                ->modalSubmitActionLabel('Ya, Tolak')
                ->action(function (array $data) {
                    $customer = $this->record->customer;
                    $customer->update([
                        'verification_status' => 'rejected',
                        'rejection_reason'    => $data['rejection_reason'],
                    ]);

                    try {
                        $this->record->notify(
                            new \App\Notifications\CustomerVerificationNotification('rejected', $data['rejection_reason'])
                        );
                    } catch (\Throwable) {}

                    Notification::make()
                        ->title('Verifikasi ditolak')
                        ->body('Customer ' . $this->record->name . ' diberitahu untuk upload ulang dokumen.')
                        ->warning()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // ── Reset ke Pending — tampil jika sudah verified atau rejected ──
            Action::make('reset_verification')
                ->label('🔄 Reset ke Pending')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->visible(function () {
                    $customer = $this->record->customer;
                    return $this->record->role === 'customer'
                        && $customer
                        && in_array($customer->verification_status, ['verified', 'rejected']);
                })
                ->requiresConfirmation()
                ->modalHeading('Reset Status Verifikasi?')
                ->modalDescription('Status verifikasi akan dikembalikan ke Pending, sehingga tombol Setujui/Tolak muncul kembali.')
                ->modalSubmitActionLabel('Ya, Reset')
                ->action(function () {
                    $customer = $this->record->customer;
                    $customer->update([
                        'verification_status' => 'pending',
                        'rejection_reason'    => null,
                    ]);

                    Notification::make()
                        ->title('Status direset ke Pending')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Actions\DeleteAction::make()->label('Hapus'),
        ];
    }
}
