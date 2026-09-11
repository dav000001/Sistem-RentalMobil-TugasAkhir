<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\BookingResource;
use App\Models\Booking;
use App\Notifications\BookingCreatedNotification;
use App\Notifications\LateFeeConfirmedNotification;
use App\Notifications\LateFeePaidNotification;
use App\Notifications\LateFeeWaivedNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\PaymentSucceededNotification;
use App\Services\BookingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    public function getTitle(): string|Htmlable
    {
        $status = $this->record?->status;
        if (in_array($status, ['completed', 'cancelled'])) {
            return 'Riwayat Booking';
        }

        return 'Detail Booking';
    }

    public function getBreadcrumb(): string
    {
        $status = $this->record?->status;
        if (in_array($status, ['completed', 'cancelled'])) {
            return 'Riwayat Booking';
        }

        return 'Detail Booking';
    }

    // Sembunyikan tombol Simpan dan Batal — semua aksi via header actions
    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            // ── Konfirmasi Pembayaran (awaiting_payment → awaiting_vendor) ──
            Action::make('confirm_payment')
                ->label('✅ Konfirmasi Pembayaran')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => $this->record->status === 'awaiting_payment' &&
                    $this->record->payment?->status === 'pending' &&
                    $this->record->payment?->payment_proof !== null
                )
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pembayaran')
                ->modalDescription(fn () => 'Konfirmasi bahwa transfer dari '.
                    ($this->record->payment?->sender_name ?? '—').
                    ' sebesar Rp '.number_format($this->record->payment?->amount ?? 0, 0, ',', '.').
                    ' sudah diterima? Vendor akan mendapat notifikasi untuk konfirmasi pesanan.'
                )
                ->modalSubmitActionLabel('Ya, Konfirmasi')
                ->action(function () {
                    $confirmed = DB::transaction(function (): bool {
                        $booking = Booking::query()
                            ->whereKey($this->record->getKey())
                            ->lockForUpdate()
                            ->first();
                        $payment = $booking?->payment()->lockForUpdate()->first();

                        if (
                            ! $booking ||
                            $booking->status !== 'awaiting_payment' ||
                            ! $payment ||
                            $payment->status !== 'pending' ||
                            ! $payment->payment_proof
                        ) {
                            return false;
                        }

                        $payment->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);
                        $booking->update(['status' => 'awaiting_vendor']);

                        return true;
                    });

                    $this->record->refresh();

                    if (! $confirmed) {
                        Notification::make()
                            ->title('Pembayaran tidak dapat dikonfirmasi')
                            ->body('Data booking atau pembayaran sudah berubah. Muat ulang halaman dan periksa kembali.')
                            ->warning()
                            ->send();

                        return;
                    }

                    // Notifikasi ke vendor agar konfirmasi pesanan
                    try {
                        $this->record->vendor?->user?->notify(
                            new BookingCreatedNotification($this->record)
                        );
                    } catch (\Throwable $exception) {
                        Log::warning('Gagal mengirim notifikasi booking ke vendor.', [
                            'booking_id' => $this->record->getKey(),
                            'exception' => $exception,
                        ]);
                    }

                    // Notifikasi ke customer bahwa pembayaran diterima
                    try {
                        $this->record->customer?->user?->notify(
                            new PaymentSucceededNotification($this->record->payment)
                        );
                    } catch (\Throwable $exception) {
                        Log::warning('Gagal mengirim notifikasi pembayaran ke customer.', [
                            'booking_id' => $this->record->getKey(),
                            'exception' => $exception,
                        ]);
                    }

                    Notification::make()
                        ->title('Pembayaran dikonfirmasi!')
                        ->body('Vendor akan mendapat notifikasi untuk konfirmasi pesanan.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // ── Tolak Pembayaran (awaiting_payment → awaiting_payment) ──
            Action::make('reject_payment')
                ->label('❌ Tolak Pembayaran')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn () => $this->record->status === 'awaiting_payment' &&
                    $this->record->payment?->status === 'pending' &&
                    $this->record->payment?->payment_proof !== null
                )
                ->requiresConfirmation()
                ->modalHeading('Tolak Pembayaran')
                ->modalDescription('Bukti transfer akan ditolak. Customer perlu upload ulang bukti transfer.')
                ->modalSubmitActionLabel('Ya, Tolak')
                ->action(function () {
                    $rejectedProof = DB::transaction(function (): string|false {
                        $booking = Booking::query()
                            ->whereKey($this->record->getKey())
                            ->lockForUpdate()
                            ->first();
                        $payment = $booking?->payment()->lockForUpdate()->first();

                        if (
                            ! $booking ||
                            $booking->status !== 'awaiting_payment' ||
                            ! $payment ||
                            $payment->status !== 'pending' ||
                            ! $payment->payment_proof
                        ) {
                            return false;
                        }

                        $proofPath = $payment->payment_proof;

                        // Status booking tetap awaiting_payment agar customer dapat upload ulang.
                        $payment->update([
                            'status' => 'pending',
                            'payment_proof' => null,
                            'sender_name' => null,
                        ]);

                        return $proofPath;
                    });

                    $this->record->refresh();

                    if ($rejectedProof === false) {
                        Notification::make()
                            ->title('Bukti tidak dapat ditolak')
                            ->body('Data booking atau pembayaran sudah berubah. Muat ulang halaman dan periksa kembali.')
                            ->warning()
                            ->send();

                        return;
                    }

                    Storage::disk('public')->delete($rejectedProof);

                    // Kirim notifikasi "bukti ditolak" bukan "booking dibatalkan"
                    try {
                        $this->record->customer?->user?->notify(
                            new PaymentReceivedNotification($this->record)
                        );
                    } catch (\Throwable $exception) {
                        Log::warning('Gagal mengirim notifikasi penolakan bukti pembayaran.', [
                            'booking_id' => $this->record->getKey(),
                            'exception' => $exception,
                        ]);
                    }

                    Notification::make()
                        ->title('Bukti transfer ditolak')
                        ->body('Customer perlu upload ulang bukti transfer. Booking masih aktif.')
                        ->warning()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // ── Batalkan Booking (admin force cancel) ──
            Action::make('force_cancel')
                ->label('🚫 Batalkan Booking')
                ->color('danger')
                ->icon('heroicon-o-x-mark')
                ->visible(fn () => app(BookingService::class)->canBeCancelled($this->record))
                ->requiresConfirmation()
                ->modalHeading('Batalkan Booking')
                ->modalDescription('Booking akan dibatalkan. Tindakan ini tidak dapat diurungkan.')
                ->modalSubmitActionLabel('Ya, Batalkan')
                ->action(function () {
                    $bookingService = app(BookingService::class);

                    // Gunakan service untuk cancel booking
                    $success = $bookingService->cancelByVendor($this->record, 'Dibatalkan oleh admin');

                    if ($success) {
                        $refundCreated = $bookingService->needsRefund($this->record);

                        Notification::make()
                            ->title('Booking dibatalkan')
                            ->body($refundCreated
                                ? 'Refund pending dibuat otomatis. Silakan proses di menu Keuangan → Refund Customer.'
                                : 'Booking dibatalkan. Tidak ada refund yang perlu diproses.')
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Gagal membatalkan booking')
                            ->body('Status booking tidak memungkinkan untuk dibatalkan.')
                            ->danger()
                            ->send();
                    }

                    $this->refreshFormData(['status']);
                }),

            // ── Konfirmasi Pembayaran Selisih Ganti Mobil ──
            Action::make('confirm_car_change_payment')
                ->label('✅ Konfirmasi Bayar Selisih Mobil')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->visible(fn () => $this->record->carChangeRequest?->status === 'approved'
                    && $this->record->carChangeRequest?->additional_payment_proof !== null
                    && $this->record->carChangeRequest?->additional_payment_at === null
                )
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pembayaran Selisih Ganti Mobil')
                ->modalDescription(function () {
                    $req = $this->record->carChangeRequest;
                    $diff = $req?->price_difference ?? 0;
                    $newCarName = $req?->newCar ? $req->newCar->brand . ' ' . $req->newCar->model : 'mobil baru';
                    return 'Konfirmasi bahwa transfer selisih sebesar Rp ' . number_format($diff, 0, ',', '.')
                         . ' sudah diterima? Mobil rental akan otomatis diupdate ke ' . $newCarName . '.';
                })
                ->modalSubmitActionLabel('Ya, Konfirmasi Pembayaran')
                ->action(function () {
                    $changeReq = $this->record->carChangeRequest;
                    if (!$changeReq) return;

                    \App\Http\Controllers\Web\CarChangeRequestController::adminConfirmPayment($changeReq);

                    Notification::make()
                        ->title('Pembayaran selisih dikonfirmasi!')
                        ->body('Data mobil di booking telah berhasil diperbarui ke unit baru.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // ── Konfirmasi Tagihan Denda (langsung dari halaman booking) ──
            Action::make('confirm_late_fee')
                ->label('💸 Konfirmasi Tagihan Denda')
                ->color('warning')
                ->icon('heroicon-o-banknotes')
                ->visible(function () {
                    $charge = $this->record->lateFeeCharge;

                    return $charge && $charge->status === 'pending';
                })
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Tagihan Denda Keterlambatan')
                ->modalDescription(function () {
                    $charge = $this->record->lateFeeCharge;
                    if (! $charge) {
                        return '';
                    }

                    return 'Konfirmasi tagihan denda sebesar Rp '
                        .number_format($charge->amount, 0, ',', '.')
                        .' ('.$charge->late_hours.' jam terlambat) ke customer '
                        .$charge->customer?->full_name.'.'
                        .' Customer akan mendapat notifikasi dan instruksi pembayaran.';
                })
                ->modalSubmitActionLabel('Ya, Konfirmasi & Tagih')
                ->action(function () {
                    $charge = $this->record->lateFeeCharge;
                    $updated = $charge
                        ? $charge->newQuery()
                            ->whereKey($charge->getKey())
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'confirmed',
                                'confirmed_by' => auth()->id(),
                                'confirmed_at' => now(),
                            ])
                        : 0;

                    if ($updated !== 1) {
                        Notification::make()
                            ->title('Tagihan tidak dapat dikonfirmasi')
                            ->body('Status tagihan sudah berubah. Muat ulang halaman dan periksa kembali.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $charge->refresh();

                    // Kirim notifikasi ke customer
                    try {
                        $charge->customer?->user?->notify(
                            new LateFeeConfirmedNotification($charge)
                        );
                    } catch (\Throwable $exception) {
                        Log::warning('Gagal mengirim notifikasi konfirmasi denda.', [
                            'booking_id' => $this->record->getKey(),
                            'late_fee_charge_id' => $charge->getKey(),
                            'exception' => $exception,
                        ]);
                    }

                    Notification::make()
                        ->title('Tagihan denda dikonfirmasi')
                        ->body('Customer '.$charge->customer?->full_name.' akan mendapat notifikasi & instruksi pembayaran.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // ── Tandai Denda Lunas (dari halaman booking) ──
            Action::make('mark_late_fee_paid')
                ->label('✅ Denda Lunas')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->visible(function () {
                    $charge = $this->record->lateFeeCharge;

                    return $charge && $charge->status === 'confirmed';
                })
                ->form([
                    Placeholder::make('proof_check')
                        ->label('')
                        ->content(function () {
                            $charge = $this->record->lateFeeCharge;
                            if ($charge?->payment_proof) {
                                $url = e(asset('storage/' . $charge->payment_proof));

                                return new HtmlString(
                                    '<div>'
                                    .'<p class="text-sm text-green-700 font-medium mb-2">✅ Customer sudah upload bukti pembayaran.</p>'
                                    .'<a href="'.$url.'" target="_blank" rel="noopener noreferrer">'
                                    .'<img src="'.$url.'" alt="Bukti pembayaran denda" style="max-width:250px;border-radius:8px;border:1px solid #e5e7eb;">'
                                    .'</a>'
                                    .'</div>'
                                );
                            }

                            return new HtmlString(
                                '<p class="text-sm text-orange-600">⚠️ Customer belum upload bukti. Yakin ingin tandai lunas?</p>'
                            );
                        }),
                    Textarea::make('admin_notes')
                        ->label('Catatan (opsional)')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $charge = $this->record->lateFeeCharge;
                    $updated = $charge
                        ? $charge->newQuery()
                            ->whereKey($charge->getKey())
                            ->where('status', 'confirmed')
                            ->update([
                                'status' => 'paid',
                                'paid_at' => now(),
                                'admin_notes' => $data['admin_notes'] ?? null,
                            ])
                        : 0;

                    if ($updated !== 1) {
                        Notification::make()
                            ->title('Denda tidak dapat ditandai lunas')
                            ->body('Status tagihan sudah berubah. Muat ulang halaman dan periksa kembali.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $charge->refresh();

                    try {
                        $charge->customer?->user?->notify(
                            new LateFeePaidNotification($charge)
                        );
                    } catch (\Throwable $exception) {
                        Log::warning('Gagal mengirim notifikasi pembayaran denda ke customer.', [
                            'booking_id' => $this->record->getKey(),
                            'late_fee_charge_id' => $charge->getKey(),
                            'exception' => $exception,
                        ]);
                    }

                    try {
                        $charge->vendor?->user?->notify(
                            new LateFeePaidNotification($charge)
                        );
                    } catch (\Throwable $exception) {
                        Log::warning('Gagal mengirim notifikasi pembayaran denda ke vendor.', [
                            'booking_id' => $this->record->getKey(),
                            'late_fee_charge_id' => $charge->getKey(),
                            'exception' => $exception,
                        ]);
                    }

                    Notification::make()
                        ->title('Denda ditandai lunas')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // ── Bebaskan Denda (dari halaman booking) ──
            Action::make('waive_late_fee')
                ->label('🎁 Bebaskan Denda')
                ->color('gray')
                ->icon('heroicon-o-gift')
                ->visible(function () {
                    $charge = $this->record->lateFeeCharge;

                    return $charge && in_array($charge->status, ['pending', 'confirmed']);
                })
                ->form([
                    TextInput::make('waive_reason')
                        ->label('Alasan Pembebasan')
                        ->required()
                        ->placeholder('Contoh: Customer reguler, denda dibebaskan atas permintaan vendor'),
                ])
                ->action(function (array $data) {
                    $charge = $this->record->lateFeeCharge;
                    $updated = $charge
                        ? $charge->newQuery()
                            ->whereKey($charge->getKey())
                            ->whereIn('status', ['pending', 'confirmed'])
                            ->update([
                                'status' => 'waived',
                                'waive_reason' => $data['waive_reason'],
                            ])
                        : 0;

                    if ($updated !== 1) {
                        Notification::make()
                            ->title('Denda tidak dapat dibebaskan')
                            ->body('Status tagihan sudah berubah. Muat ulang halaman dan periksa kembali.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $charge->refresh();

                    try {
                        $charge->customer?->user?->notify(
                            new LateFeeWaivedNotification($charge)
                        );
                    } catch (\Throwable $exception) {
                        Log::warning('Gagal mengirim notifikasi pembebasan denda.', [
                            'booking_id' => $this->record->getKey(),
                            'late_fee_charge_id' => $charge->getKey(),
                            'exception' => $exception,
                        ]);
                    }

                    Notification::make()
                        ->title('Denda dibebaskan')
                        ->body('Customer mendapat notifikasi bahwa denda dibebaskan.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // ── Proses Refund — gunakan menu Refund Customer di sidebar ──
            // (tombol ini dihapus, refund dikelola via menu Keuangan → Refund Customer)
        ];
    }
}
