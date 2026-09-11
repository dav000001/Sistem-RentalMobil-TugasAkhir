<?php

namespace App\Filament\Admin\Resources;

use App\Models\CompensationCharge;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CompensationChargeResource extends Resource
{
    protected static ?string $model = CompensationCharge::class;
    protected static ?string $navigationLabel = 'Tagihan Kompensasi';
    protected static ?string $modelLabel = 'Tagihan Kompensasi';
    protected static ?string $pluralModelLabel = 'Tagihan Kompensasi';
    protected static ?string $breadcrumb = 'Tagihan Kompensasi';
    protected static ?int    $navigationSort  = 8;

    public static function getNavigationGroup(): ?string
    {
        return 'Keuangan';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-receipt-percent';
    }

    public static function getNavigationBadge(): ?string
    {
        // Tampilkan badge jika ada tagihan pending ATAU ada bukti bayar yang belum dikonfirmasi
        $count = CompensationCharge::where('status', 'pending')->count();
        $withProof = CompensationCharge::where('status', 'pending')
            ->whereNotNull('payment_proof')
            ->count();
        // Prioritaskan tampil jika ada bukti yang perlu dikonfirmasi
        return $count > 0 ? (string) $count . ($withProof > 0 ? ' (' . $withProof . ' perlu konfirmasi)' : '') : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Placeholder::make('booking_info')
                ->label('Booking')
                ->content(fn ($record) => $record?->booking
                    ? $record->booking->code . ' — ' . ($record->booking->car?->brand . ' ' . $record->booking->car?->model)
                    : '—')
                ->columnSpanFull(),

            Forms\Components\Placeholder::make('customer_info')
                ->label('Customer')
                ->content(fn ($record) => $record?->customer
                    ? $record->customer->full_name . ' | ' . ($record->customer->user?->email ?? '—') . ' | ' . ($record->customer->user?->phone ?? '—')
                    : '—')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('reason')
                ->label('Alasan Kompensasi')
                ->disabled()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('amount')
                ->label('Jumlah')
                ->prefix('Rp')
                ->numeric()
                ->disabled(),

            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => '⏳ Menunggu Pembayaran',
                    'paid'    => '✅ Sudah Dibayar',
                    'waived'  => '🎁 Dibebaskan',
                ])
                ->disabled(),

            Forms\Components\Placeholder::make('due_date_display')
                ->label('Batas Pembayaran')
                ->content(fn ($record) => $record?->due_date?->format('d M Y') ?? '—'),

            Forms\Components\Placeholder::make('payment_proof_display')
                ->label('Bukti Bayar dari Customer')
                ->content(fn ($record) => $record?->payment_proof
                    ? new \Illuminate\Support\HtmlString(
                        // Banner peringatan jika status masih pending
                        ($record->status === 'pending'
                            ? '<div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#92400e;">'
                              . '⚠️ <strong>Bukti bayar sudah masuk.</strong> Klik tombol <strong>"✅ Konfirmasi Lunas"</strong> di kanan atas untuk memverifikasi.'
                              . '</div>'
                            : '')
                        . '<a href="' . asset('storage/' . $record->payment_proof) . '" target="_blank">'
                        . '<img src="' . asset('storage/' . $record->payment_proof) . '" '
                        . 'style="max-width:300px;max-height:200px;border-radius:8px;border:1px solid #e5e7eb;cursor:pointer;">'
                        . '<br><small style="color:#2563eb;">Klik untuk lihat ukuran penuh</small>'
                        . '</a>'
                    )
                    : new \Illuminate\Support\HtmlString('<span style="color:#9ca3af;">Belum ada bukti bayar dari customer</span>'))
                ->columnSpanFull(),

            Forms\Components\Textarea::make('notes')
                ->label('Catatan')
                ->disabled()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking.code')
                    ->label('Booking')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) =>
                        $record->booking?->car?->brand . ' ' . $record->booking?->car?->model
                    ),

                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn ($record) =>
                        $record->customer?->user?->phone ?? $record->customer?->user?->email ?? '—'
                    ),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->reason),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Menunggu Bayar',
                        'paid'    => 'Sudah Dibayar',
                        'waived'  => 'Dibebaskan',
                        default   => ucfirst($state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'danger',
                        'paid'    => 'success',
                        'waived'  => 'gray',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Batas Bayar')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->description(fn ($record) => $record->isOverdue()
                        ? new \Illuminate\Support\HtmlString('<span style="color:#dc2626;font-weight:600;">Lewat batas!</span>')
                        : null
                    ),

                Tables\Columns\IconColumn::make('payment_proof')
                    ->label('Bukti Bayar')
                    ->boolean()
                    ->trueIcon('heroicon-o-photo')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn ($record) => !empty($record->payment_proof)),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Dibayar')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu Bayar',
                        'paid'    => 'Sudah Dibayar',
                        'waived'  => 'Dibebaskan',
                    ]),
            ])
            ->actions([
                // Konfirmasi bayar — jika customer sudah upload bukti
                Action::make('mark_paid')
                    ->label('✅ Konfirmasi Lunas')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (CompensationCharge $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pembayaran Kompensasi')
                    ->modalDescription(fn (CompensationCharge $record) =>
                        'Konfirmasi bahwa customer ' . ($record->customer?->full_name ?? '—')
                        . ' sudah membayar kompensasi sebesar Rp '
                        . number_format($record->amount, 0, ',', '.') . '?'
                    )
                    ->modalSubmitActionLabel('Ya, Konfirmasi Lunas')
                    ->action(function (CompensationCharge $record) {
                        $record->update([
                            'status'       => 'paid',
                            'paid_at'      => now(),
                            'confirmed_by' => auth('admin')->id() ?? auth()->id(),
                        ]);

                        // ── Otomatis buat Payout Vendor sebesar kompensasi ────────────
                        $booking = $record->booking;
                        $vendor  = $booking?->vendor;

                        if ($vendor) {
                            \App\Models\Payout::create([
                                'vendor_id'    => $vendor->id,
                                'period_start' => now()->toDateString(),
                                'period_end'   => now()->toDateString(),
                                'amount'       => $record->amount,
                                'status'       => 'pending',
                                'notes'        => 'Kompensasi kerusakan dari customer '
                                               . ($record->customer?->full_name ?? '-')
                                               . ' — Booking ' . ($booking?->code ?? '-')
                                               . ' — ' . $record->reason,
                            ]);
                        }

                        // Notifikasi ke vendor
                        try {
                            $vendor?->user?->notify(
                                new \App\Notifications\CompensationPaidNotification($record)
                            );
                        } catch (\Throwable) {}

                        Notification::make()
                            ->title('Kompensasi dikonfirmasi lunas')
                            ->body('Rp ' . number_format($record->amount, 0, ',', '.') . ' dari ' . ($record->customer?->full_name ?? '-') . '. Payout vendor otomatis dibuat.')
                            ->success()
                            ->send();
                    }),

                // Bebaskan tagihan (waive)
                Action::make('waive')
                    ->label('🎁 Bebaskan')
                    ->color('gray')
                    ->icon('heroicon-o-gift')
                    ->visible(fn (CompensationCharge $record) => $record->status === 'pending')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Alasan Pembebasan')
                            ->required()
                            ->rows(3)
                            ->placeholder('Contoh: Customer sudah membayar biaya perbaikan langsung ke vendor.'),
                    ])
                    ->modalHeading('Bebaskan Tagihan Kompensasi')
                    ->modalSubmitActionLabel('Bebaskan')
                    ->action(function (CompensationCharge $record, array $data) {
                        $record->update([
                            'status'       => 'waived',
                            'notes'        => $data['notes'],
                            'confirmed_by' => auth('admin')->id() ?? auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Tagihan kompensasi dibebaskan')
                            ->warning()
                            ->send();
                    }),

                // Kirim ulang notifikasi ke customer
                Action::make('resend_notification')
                    ->label('📨 Kirim Ulang Notif')
                    ->color('info')
                    ->icon('heroicon-o-envelope')
                    ->visible(fn (CompensationCharge $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Ulang Notifikasi?')
                    ->modalDescription('Email & notifikasi akan dikirim ulang ke customer.')
                    ->action(function (CompensationCharge $record) {
                        try {
                            $record->customer?->user?->notify(
                                new \App\Notifications\CompensationChargeNotification($record)
                            );
                        } catch (\Throwable) {}

                        Notification::make()
                            ->title('Notifikasi berhasil dikirim ulang')
                            ->success()
                            ->send();
                    }),

                EditAction::make()->label('Detail'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\CompensationChargeResource\Pages\ListCompensationCharges::route('/'),
            'edit'  => \App\Filament\Admin\Resources\CompensationChargeResource\Pages\EditCompensationCharge::route('/{record}/edit'),
        ];
    }
}
