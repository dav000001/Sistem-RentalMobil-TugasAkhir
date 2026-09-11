<?php

namespace App\Filament\Admin\Resources\DisputeResource\Pages;

use App\Filament\Admin\Resources\DisputeResource;
use App\Notifications\DisputeStatusNotification;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDispute extends EditRecord
{
    protected static string $resource = DisputeResource::class;

    // Sembunyikan tombol Save/Cancel bawaan — semua aksi via header actions
    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [

            // ── Ambil Alih (open → in_review) ─────────────────────────
            Action::make('take_over')
                ->label('🔍 Tinjau Sekarang')
                ->color('info')
                ->icon('heroicon-o-eye')
                ->visible(fn () => $this->record->status === 'open')
                ->requiresConfirmation()
                ->modalHeading('Mulai Tinjau Sengketa')
                ->modalDescription(
                    'Sengketa akan ditandai sebagai "Sedang Ditinjau" dan Anda akan dicatat sebagai admin penanganan. '
                    . 'Kedua pihak akan mendapat notifikasi.'
                )
                ->modalSubmitActionLabel('Ya, Mulai Tinjau')
                ->action(function () {
                    $adminId = auth('admin')->id() ?? auth()->id();

                    $this->record->update([
                        'status'   => 'in_review',
                        'admin_id' => $adminId,
                    ]);

                    $this->sendNotificationToDisputer('in_review');

                    Notification::make()
                        ->title('Sengketa diambil alih')
                        ->body('Status diubah ke "Sedang Ditinjau". Semua pihak sudah dinotifikasi.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'admin_id']);
                }),

            // ── Selesaikan Sengketa (in_review → resolved) ─────────────
            Action::make('resolve')
                ->label('✅ Selesaikan Sengketa')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => $this->record->status === 'in_review')
                ->schema([
                    // Info konteks: siapa yang membuka sengketa
                    Forms\Components\Placeholder::make('dispute_context')
                        ->label('Sengketa Diajukan Oleh')
                        ->content(fn () => ($this->record->opened_by_role ?? 'customer') === 'vendor'
                            ? new \Illuminate\Support\HtmlString(
                                '<span style="color:#c2410c;font-weight:600;">&#127978; Vendor &mdash; ' . e($this->record->openedBy?->name ?? '&mdash;') . '</span>'
                                . '<p style="font-size:12px;color:#6b7280;margin-top:4px;">Jika vendor menang: pilih "Tidak Ada Refund". Catat keputusan kompensasi di kolom resolusi.</p>'
                              )
                            : new \Illuminate\Support\HtmlString(
                                '<span style="color:#1d4ed8;font-weight:600;">&#128100; Customer &mdash; ' . e($this->record->openedBy?->name ?? '&mdash;') . '</span>'
                                . '<p style="font-size:12px;color:#6b7280;margin-top:4px;">Jika customer menang: buat refund ke customer di bawah.</p>'
                              )
                        )
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('resolution')
                        ->label('Keputusan / Resolusi')
                        ->placeholder(
                            'Jelaskan keputusan penyelesaian secara detail. '
                            . 'Contoh: Berdasarkan bukti yang ada, customer terbukti merusak bumper depan. '
                            . 'Vendor berhak mendapat kompensasi perbaikan sesuai estimasi bengkel.'
                        )
                        ->rows(5)
                        ->required()
                        ->minLength(20)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('refund_decision')
                        ->label('Keputusan Refund ke Customer')
                        ->helperText('Jika sengketa dibuka vendor dan vendor menang, pilih "Tidak Ada Refund".')
                        ->options([
                            'none'    => 'Tidak Ada Refund ke Customer',
                            'full'    => 'Refund Penuh ke Customer (seluruh pembayaran)',
                            'partial' => 'Refund Sebagian ke Customer (tentukan jumlah)',
                        ])
                        ->default('none')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                            if ($state === 'partial') {
                                // Auto-isi 30% dari total pembayaran booking
                                $total = $this->record->booking?->payment?->amount
                                      ?? $this->record->booking?->total
                                      ?? 0;
                                $set('refund_amount', (int) round($total * 0.30));
                            } elseif ($state === 'full') {
                                $total = $this->record->booking?->payment?->amount
                                      ?? $this->record->booking?->total
                                      ?? 0;
                                $set('refund_amount', (int) $total);
                            } else {
                                $set('refund_amount', null);
                            }
                        }),

                    Forms\Components\TextInput::make('refund_amount')
                        ->label('Jumlah Refund (Rp)')
                        ->numeric()
                        ->prefix('Rp')
                        ->minValue(1)
                        ->helperText(function (\Filament\Schemas\Components\Utilities\Get $get) {
                            if ($get('refund_decision') !== 'partial') return null;
                            $total = $this->record->booking?->payment?->amount
                                  ?? $this->record->booking?->total
                                  ?? 0;
                            $persen30 = (int) round($total * 0.30);
                            return 'Default 30% dari total pembayaran = Rp ' . number_format($persen30, 0, ',', '.') . '. Bisa diubah sesuai keputusan.';
                        })
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('refund_decision') === 'partial')
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('refund_decision') === 'partial'),

                    Forms\Components\Toggle::make('change_booking_status')
                        ->label('Ubah Status Booking ke "Selesai"')
                        ->helperText('Aktifkan jika sengketa sudah sepenuhnya selesai dan booking bisa ditutup.')
                        ->default(false),

                    // ── Tagihkan kompensasi ke customer (jika vendor yang buka & vendor menang) ──
                    Forms\Components\Toggle::make('create_compensation')
                        ->label('Tagihkan Kompensasi ke Customer')
                        ->helperText('Aktifkan jika customer terbukti bersalah dan harus membayar kompensasi ke vendor.')
                        ->default(false)
                        ->live()
                        ->visible(fn () => ($this->record->opened_by_role ?? 'customer') === 'vendor'),

                    Forms\Components\TextInput::make('compensation_amount')
                        ->label('Jumlah Kompensasi (Rp)')
                        ->numeric()
                        ->prefix('Rp')
                        ->minValue(1)
                        ->placeholder('Contoh: 500000')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) =>
                            $get('create_compensation') === true
                        )
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) =>
                            $get('create_compensation') === true
                        ),

                    Forms\Components\TextInput::make('compensation_reason')
                        ->label('Alasan Kompensasi')
                        ->placeholder('Contoh: Kerusakan bumper depan — biaya perbaikan')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) =>
                            $get('create_compensation') === true
                        )
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) =>
                            $get('create_compensation') === true
                        ),

                    Forms\Components\DatePicker::make('compensation_due_date')
                        ->label('Batas Pembayaran Kompensasi')
                        ->default(now()->addDays(7)->format('Y-m-d'))
                        ->minDate(now()->format('Y-m-d'))
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) =>
                            $get('create_compensation') === true
                        ),
                ])
                ->modalHeading('Selesaikan Sengketa')
                ->modalDescription('Masukkan keputusan final. Semua pihak akan mendapat notifikasi.')
                ->modalSubmitActionLabel('Simpan & Selesaikan')
                ->action(function (array $data) {
                    $adminId = auth('admin')->id() ?? auth()->id();

                    $this->record->update([
                        'status'      => 'resolved',
                        'resolution'  => $data['resolution'],
                        'admin_id'    => $adminId,
                        'resolved_at' => now(),
                    ]);

                    // Buat refund ke customer jika dipilih
                    $booking = $this->record->booking;
                    if ($data['refund_decision'] !== 'none' && $booking) {
                        $booking->load(['payment', 'customer']);

                        $refundAmount = match ($data['refund_decision']) {
                            'full'    => $booking->payment?->amount ?? $booking->total,
                            'partial' => (float) ($data['refund_amount'] ?? 0),
                            default   => 0,
                        };

                        if ($refundAmount > 0) {
                            $alreadyExists = $booking->refund()
                                ->whereIn('status', ['pending', 'paid'])
                                ->exists();

                            if (! $alreadyExists) {
                                $customer = $booking->customer;
                                \App\Models\CustomerRefund::create([
                                    'booking_id'        => $booking->id,
                                    'customer_id'       => $booking->customer_id,
                                    'amount'            => $refundAmount,
                                    'status'            => 'pending',
                                    'bank_name'         => $customer?->bank_name,
                                    'bank_account_no'   => $customer?->bank_account_no,
                                    'bank_account_name' => $customer?->bank_account_name,
                                    'notes'             => 'Refund dari penyelesaian sengketa #' . $this->record->id,
                                ]);
                            }
                        }
                    }

                    // Ubah status booking jika diminta
                    if (! empty($data['change_booking_status']) && $booking) {
                        $booking->update(['status' => 'completed']);
                    }

                    // ── Buat tagihan kompensasi ke customer jika vendor menang ──
                    if (! empty($data['create_compensation']) && $booking) {
                        $alreadyExists = \App\Models\CompensationCharge::where('booking_id', $booking->id)
                            ->where('status', 'pending')
                            ->exists();

                        if (! $alreadyExists) {
                            $charge = \App\Models\CompensationCharge::create([
                                'booking_id'  => $booking->id,
                                'customer_id' => $booking->customer_id,
                                'dispute_id'  => $this->record->id,
                                'amount'      => (float) ($data['compensation_amount'] ?? 0),
                                'reason'      => $data['compensation_reason'] ?? 'Kompensasi sengketa',
                                'status'      => 'pending',
                                'due_date'    => $data['compensation_due_date'] ?? now()->addDays(7)->format('Y-m-d'),
                                'notes'       => 'Dibuat otomatis dari penyelesaian sengketa #' . $this->record->id,
                            ]);

                            // Notifikasi ke customer
                            try {
                                $booking->customer?->user?->notify(
                                    new \App\Notifications\CompensationChargeNotification($charge)
                                );
                            } catch (\Throwable) {}
                        }
                    }

                    $this->sendNotificationToDisputer('resolved');
                    $this->sendNotificationToOtherParty('resolved');

                    $refundMsg = match ($data['refund_decision'] ?? 'none') {
                        'full'    => ' Refund penuh ke customer otomatis dibuat.',
                        'partial' => ' Refund sebagian Rp ' . number_format($data['refund_amount'] ?? 0, 0, ',', '.') . ' otomatis dibuat.',
                        default   => ' Tidak ada refund ke customer.',
                    };

                    $compensationMsg = ! empty($data['create_compensation'])
                        ? ' Tagihan kompensasi Rp ' . number_format($data['compensation_amount'] ?? 0, 0, ',', '.') . ' dikirim ke customer.'
                        : '';

                    Notification::make()
                        ->title('Sengketa diselesaikan')
                        ->body('Keputusan telah disimpan. Semua pihak sudah dinotifikasi.' . $refundMsg . $compensationMsg)
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'resolution', 'resolved_at', 'admin_id']);
                }),

            // ── Tolak Sengketa (in_review → rejected) ──────────────────
            Action::make('reject')
                ->label('Tolak Sengketa')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn () => $this->record->status === 'in_review')
                ->schema([
                    Forms\Components\Textarea::make('resolution')
                        ->label('Alasan Penolakan')
                        ->placeholder(
                            'Jelaskan mengapa sengketa ini ditolak. '
                            . 'Contoh: Bukti yang dilampirkan tidak mencukupi dan tidak relevan dengan klaim yang diajukan.'
                        )
                        ->rows(4)
                        ->required()
                        ->minLength(10)
                        ->columnSpanFull(),
                ])
                ->modalHeading('Tolak Sengketa')
                ->modalDescription('Sengketa akan ditolak. Pihak yang mengajukan akan mendapat notifikasi beserta alasannya.')
                ->modalSubmitActionLabel('Tolak Sengketa')
                ->action(function (array $data) {
                    $adminId = auth('admin')->id() ?? auth()->id();

                    $this->record->update([
                        'status'      => 'rejected',
                        'resolution'  => $data['resolution'],
                        'admin_id'    => $adminId,
                        'resolved_at' => now(),
                    ]);

                    $this->sendNotificationToDisputer('rejected');

                    Notification::make()
                        ->title('Sengketa ditolak')
                        ->body('Penolakan telah disimpan dan pihak pengaju sudah dinotifikasi.')
                        ->warning()
                        ->send();

                    $this->refreshFormData(['status', 'resolution', 'resolved_at', 'admin_id']);
                }),

            // ── Buka Ulang (resolved/rejected → open) ─────────────────
            Action::make('reopen')
                ->label('Buka Ulang')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => in_array($this->record->status, ['resolved', 'rejected']))
                ->requiresConfirmation()
                ->modalHeading('Buka Ulang Sengketa')
                ->modalDescription('Sengketa akan dikembalikan ke status "Baru" dan dapat ditinjau ulang.')
                ->modalSubmitActionLabel('Ya, Buka Ulang')
                ->action(function () {
                    $this->record->update([
                        'status'      => 'open',
                        'resolution'  => null,
                        'resolved_at' => null,
                    ]);

                    Notification::make()
                        ->title('Sengketa dibuka ulang')
                        ->body('Status kembali ke "Baru".')
                        ->info()
                        ->send();

                    $this->refreshFormData(['status', 'resolution', 'resolved_at']);
                }),

            // ── Lihat Booking Terkait ──────────────────────────────────
            Action::make('view_booking')
                ->label('Lihat Booking')
                ->color('gray')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => $this->record->booking_id
                    ? \App\Filament\Admin\Resources\BookingResource::getUrl('edit', ['record' => $this->record->booking_id])
                    : null
                )
                ->openUrlInNewTab(),
        ];
    }

    // ── Helper: Notifikasi ke pembuka sengketa ─────────────────────────
    private function sendNotificationToDisputer(string $status): void
    {
        try {
            $disputer = $this->record->openedBy;
            if ($disputer) {
                $disputer->notify(new DisputeStatusNotification($this->record, $status));
            }
        } catch (\Throwable) {}
    }

    // ── Helper: Notifikasi ke pihak lain (customer/vendor) ────────────
    private function sendNotificationToOtherParty(string $status): void
    {
        try {
            $booking  = $this->record->booking;
            $openerId = $this->record->opened_by_user_id;

            // Notifikasi ke customer jika bukan yang membuka
            if ($booking?->customer?->user_id !== $openerId) {
                $booking?->customer?->user?->notify(
                    new DisputeStatusNotification($this->record, $status)
                );
            }

            // Notifikasi ke vendor jika bukan yang membuka
            if ($booking?->vendor?->user_id !== $openerId) {
                $booking?->vendor?->user?->notify(
                    new DisputeStatusNotification($this->record, $status)
                );
            }
        } catch (\Throwable) {}
    }
}
