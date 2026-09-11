<?php

namespace App\Filament\Admin\Resources\CompensationChargeResource\Pages;

use App\Filament\Admin\Resources\CompensationChargeResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCompensationCharge extends EditRecord
{
    protected static string $resource = CompensationChargeResource::class;

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Konfirmasi lunas — muncul jika ada bukti bayar dan status masih pending
            Action::make('confirm_paid')
                ->label('✅ Konfirmasi Lunas')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => $this->record->status === 'pending' && !empty($this->record->payment_proof))
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pembayaran Kompensasi')
                ->modalDescription(fn () =>
                    'Konfirmasi bahwa customer ' . ($this->record->customer?->full_name ?? '-')
                    . ' sudah membayar kompensasi sebesar Rp '
                    . number_format($this->record->amount, 0, ',', '.')
                    . '?'
                )
                ->modalSubmitActionLabel('Ya, Konfirmasi Lunas')
                ->action(function () {
                    $this->record->update([
                        'status'       => 'paid',
                        'paid_at'      => now(),
                        'confirmed_by' => auth('admin')->id() ?? auth()->id(),
                    ]);

                    // ── Otomatis buat Payout Vendor sebesar kompensasi ────────────
                    $booking = $this->record->booking;
                    $vendor  = $booking?->vendor;

                    if ($vendor) {
                        \App\Models\Payout::create([
                            'vendor_id'    => $vendor->id,
                            'period_start' => now()->toDateString(),
                            'period_end'   => now()->toDateString(),
                            'amount'       => $this->record->amount,
                            'status'       => 'pending',
                            'notes'        => 'Kompensasi kerusakan dari customer '
                                           . ($this->record->customer?->full_name ?? '-')
                                           . ' — Booking ' . ($booking?->code ?? '-')
                                           . ' — ' . $this->record->reason,
                        ]);
                    }

                    // Notifikasi ke vendor bahwa kompensasi sudah dibayar
                    try {
                        $booking?->vendor?->user?->notify(
                            new \App\Notifications\CompensationPaidNotification($this->record)
                        );
                    } catch (\Throwable) {}

                    Notification::make()
                        ->title('Kompensasi dikonfirmasi lunas')
                        ->body('Rp ' . number_format($this->record->amount, 0, ',', '.') . ' dari ' . ($this->record->customer?->full_name ?? '-') . '. Payout vendor otomatis dibuat.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'paid_at']);
                }),

            // Bebaskan tagihan
            Action::make('waive')
                ->label('🎁 Bebaskan Tagihan')
                ->color('gray')
                ->icon('heroicon-o-gift')
                ->visible(fn () => $this->record->status === 'pending')
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('waive_warning')
                        ->label('')
                        ->content(function () {
                            if (!empty($this->record->payment_proof)) {
                                return new \Illuminate\Support\HtmlString(
                                    '<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:12px;color:#991b1b;font-size:13px;">'
                                    . '<strong>⚠️ Customer sudah mengupload bukti pembayaran.</strong><br>'
                                    . 'Jika Anda membebaskan tagihan ini, sistem akan otomatis membuat <strong>refund ke customer</strong> sebesar '
                                    . '<strong>Rp ' . number_format($this->record->amount, 0, ',', '.') . '</strong>.'
                                    . '</div>'
                                );
                            }
                            return new \Illuminate\Support\HtmlString(
                                '<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px;color:#166534;font-size:13px;">'
                                . 'ℹ️ Customer belum membayar. Tagihan akan dibatalkan.'
                                . '</div>'
                            );
                        })
                        ->columnSpanFull(),

                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Alasan Pembebasan')
                        ->required()
                        ->rows(3)
                        ->placeholder('Contoh: Customer sudah bayar langsung ke vendor, atau tagihan dibatalkan atas permintaan vendor.'),
                ])
                ->modalHeading('Bebaskan Tagihan Kompensasi')
                ->modalSubmitActionLabel('Ya, Bebaskan')
                ->action(function (array $data) {
                    $hasProof = !empty($this->record->payment_proof);

                    $this->record->update([
                        'status'       => 'waived',
                        'notes'        => $data['notes'],
                        'confirmed_by' => auth('admin')->id() ?? auth()->id(),
                    ]);

                    // Jika customer sudah upload bukti bayar → buat refund otomatis
                    if ($hasProof) {
                        $booking  = $this->record->booking;
                        $customer = $this->record->customer;

                        if ($booking && $customer) {
                            $alreadyExists = \App\Models\CustomerRefund::where('booking_id', $booking->id)
                                ->where('notes', 'like', '%kompensasi%')
                                ->whereIn('status', ['pending', 'paid'])
                                ->exists();

                            if (!$alreadyExists) {
                                \App\Models\CustomerRefund::create([
                                    'booking_id'        => $booking->id,
                                    'customer_id'       => $customer->id,
                                    'amount'            => $this->record->amount,
                                    'status'            => 'pending',
                                    'bank_name'         => $customer->bank_name,
                                    'bank_account_no'   => $customer->bank_account_no,
                                    'bank_account_name' => $customer->bank_account_name,
                                    'notes'             => 'Refund tagihan kompensasi yang dibebaskan — ' . $data['notes'],
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Tagihan dibebaskan — Refund customer dibuat')
                            ->body('Customer sudah bayar, refund Rp ' . number_format($this->record->amount, 0, ',', '.') . ' otomatis dibuat di menu Refund Customer.')
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Tagihan kompensasi dibebaskan')
                            ->body('Customer tidak perlu membayar.')
                            ->warning()
                            ->send();
                    }

                    $this->refreshFormData(['status', 'notes']);
                }),
        ];
    }
}
