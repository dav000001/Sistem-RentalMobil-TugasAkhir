<?php

namespace App\Filament\Admin\Resources\VendorSubscriptionResource\Pages;

use App\Filament\Admin\Resources\VendorSubscriptionResource;
use App\Models\VendorSubscription;
use App\Services\VendorSubscriptionService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVendorSubscription extends EditRecord
{
    protected static string $resource = VendorSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Konfirmasi Lunas di Halaman Edit
            Actions\Action::make('confirm_paid_edit')
                ->label('Konfirmasi Lunas')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending_payment')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pembayaran Paket?')
                ->modalDescription(fn () => 'Konfirmasi pembayaran paket ' . $this->record->package->name . ' sebesar Rp ' . number_format($this->record->package->price_per_month - $this->record->proration_credit, 0, ',', '.') . ' untuk vendor ' . $this->record->vendor->business_name . '. Paket akan langsung aktif.')
                ->action(function () {
                    $amount = $this->record->package->price_per_month - $this->record->proration_credit;
                    app(VendorSubscriptionService::class)->activate($this->record, [
                        'amount' => $amount,
                        'reference' => $this->record->transfer_ref ?? 'MANUAL-' . strtoupper(uniqid()),
                    ]);
                    Notification::make()->title('Pembayaran paket berhasil dikonfirmasi')->success()->send();
                    $this->refreshFormData(['status', 'paid_at', 'started_at', 'expires_at', 'amount_paid', 'payment_reference']);
                }),

            // Batalkan Paket di Halaman Edit
            Actions\Action::make('cancel_subscription_edit')
                ->label('Batalkan Paket')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, ['active', 'grace_period']))
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Alasan Pembatalan')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    app(VendorSubscriptionService::class)->adminCancel($this->record, auth()->user(), $data['reason']);
                    Notification::make()->title('Langganan vendor berhasil dibatalkan')->warning()->send();
                    $this->refreshFormData(['status', 'cancel_reason', 'cancelled_by']);
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
