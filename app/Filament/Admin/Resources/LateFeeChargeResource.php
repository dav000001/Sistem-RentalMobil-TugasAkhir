<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LateFeeChargeResource\Pages;
use App\Models\LateFeeCharge;
use App\Notifications\LateFeeConfirmedNotification;
use App\Notifications\LateFeePaidNotification;
use App\Notifications\LateFeeWaivedNotification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class LateFeeChargeResource extends Resource
{
    protected static ?string $model = LateFeeCharge::class;

    protected static ?string $navigationLabel = 'Tagihan Denda';

    protected static ?string $modelLabel = 'Tagihan Denda';

    protected static ?string $pluralModelLabel = 'Tagihan Denda';

    protected static ?int $navigationSort = 5;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Keuangan';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = LateFeeCharge::whereIn('status', ['pending', 'confirmed'])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return LateFeeCharge::where('status', 'pending')->exists() ? 'warning' : 'info';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('📋 Informasi Tagihan')
                ->columns(2)
                ->components([
                    Forms\Components\Placeholder::make('booking_code')
                        ->label('Kode Booking')
                        ->content(fn ($record) => $record?->booking?->code ?? '—'),

                    Forms\Components\Placeholder::make('status_now')
                        ->label('Status Tagihan')
                        ->content(fn ($record) => $record?->statusLabel() ?? '—'),

                    Forms\Components\Placeholder::make('customer_name')
                        ->label('Customer')
                        ->content(fn ($record) => $record?->customer?->full_name ?? '—'),

                    Forms\Components\Placeholder::make('vendor_name')
                        ->label('Vendor')
                        ->content(fn ($record) => $record?->vendor?->business_name ?? '—'),

                    Forms\Components\Placeholder::make('late_hours')
                        ->label('Durasi Terlambat')
                        ->content(fn ($record) => $record?->late_hours ? $record->late_hours.' jam' : '—'),

                    Forms\Components\Placeholder::make('amount')
                        ->label('Jumlah Denda')
                        ->content(fn ($record) => $record?->amount
                            ? 'Rp '.number_format($record->amount, 0, ',', '.')
                            : '—'),

                    Forms\Components\Placeholder::make('end_at')
                        ->label('Jatuh Tempo Sewa')
                        ->content(fn ($record) => $record?->booking?->end_at?->format('d M Y, H:i') ?? '—'),

                    Forms\Components\Placeholder::make('actual_return_at')
                        ->label('Waktu Aktual Kembali')
                        ->content(fn ($record) => $record?->booking?->actual_return_at?->format('d M Y, H:i') ?? '—'),
                ]),

            Section::make('🏦 Rekening Tujuan Pembayaran')
                ->description('Customer membayar denda ke rekening vendor ini')
                ->columns(3)
                ->components([
                    Forms\Components\Placeholder::make('bank_name')
                        ->label('Bank')
                        ->content(fn ($record) => $record?->bank_name ?? '—'),

                    Forms\Components\Placeholder::make('bank_account_no')
                        ->label('No. Rekening')
                        ->content(fn ($record) => $record?->bank_account_no ?? '—'),

                    Forms\Components\Placeholder::make('bank_account_name')
                        ->label('Atas Nama')
                        ->content(fn ($record) => $record?->bank_account_name ?? '—'),
                ]),

            Section::make('📎 Bukti Pembayaran Customer')
                ->components([
                    Forms\Components\Placeholder::make('payment_proof_preview')
                        ->label('Bukti Transfer')
                        ->columnSpanFull()
                        ->content(function ($record) {
                            if (! $record?->payment_proof) {
                                return new HtmlString(
                                    '<span class="text-gray-400 text-sm">Belum ada bukti pembayaran diupload.</span>'
                                );
                            }

                            $url = e(asset('storage/' . $record->payment_proof));

                            return new HtmlString(
                                '<div>'
                                .'<a href="'.$url.'" target="_blank" rel="noopener noreferrer">'
                                .'<img src="'.$url.'" alt="Bukti pembayaran" style="max-width:320px;max-height:220px;border-radius:8px;border:1px solid #e5e7eb;cursor:pointer;" />'
                                .'<br><small style="color:#2563eb;">🔍 Klik untuk lihat ukuran penuh</small>'
                                .'</a>'
                                .'<p class="text-xs text-gray-500 mt-1">Diupload: '
                                .e($record->proof_uploaded_at?->format('d M Y H:i') ?? '—')
                                .'</p>'
                                .'</div>'
                            );
                        }),
                ])
                ->visible(fn ($record) => $record?->status === 'paid' || $record?->payment_proof !== null),

            Section::make('⚠️ Keberatan Denda dari Customer')
                ->components([
                    Forms\Components\Placeholder::make('dispute_reason_text')
                        ->label('Alasan Keberatan')
                        ->content(fn ($record) => $record?->dispute_reason ?? '—'),
                    Forms\Components\Placeholder::make('disputed_at_text')
                        ->label('Tanggal Diajukan')
                        ->content(fn ($record) => $record?->disputed_at?->format('d M Y H:i') ?? '—'),
                    Forms\Components\Placeholder::make('dispute_rejection_reason_text')
                        ->label('Alasan Penolakan oleh Admin')
                        ->content(fn ($record) => $record?->dispute_rejection_reason ?? '—')
                        ->visible(fn ($record) => !empty($record?->dispute_rejection_reason)),
                ])
                ->visible(fn ($record) => !empty($record?->dispute_reason)),

            Section::make('📝 Catatan Admin')
                ->components([
                    Forms\Components\Textarea::make('admin_notes')
                        ->label('Catatan')
                        ->rows(3)
                        ->placeholder('Catatan internal untuk tagihan ini...'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking.code')
                    ->label('Booking')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('vendor.business_name')
                    ->label('Vendor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('late_hours')
                    ->label('Terlambat')
                    ->formatStateUsing(fn ($state) => $state.' jam')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Denda')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'success' => 'paid',
                        'gray' => 'waived',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => '⏳ Menunggu Konfirmasi',
                        'confirmed' => '🔔 Tagih ke Customer',
                        'paid' => '✅ Sudah Dibayar',
                        'waived' => '🎁 Dibebaskan',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => '⏳ Menunggu Konfirmasi',
                        'confirmed' => '🔔 Tagih ke Customer',
                        'paid' => '✅ Sudah Dibayar',
                        'waived' => '🎁 Dibebaskan',
                    ]),
            ])
            ->recordActions([
                Action::make('confirm')
                    ->label('✅ Konfirmasi & Tagih')
                    ->color('warning')
                    ->icon('heroicon-o-bell')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Tagihan Denda')
                    ->modalDescription('Admin akan mengkonfirmasi tagihan denda dan mengirim notifikasi + email ke customer berisi jumlah denda dan rekening tujuan pembayaran.')
                    ->visible(fn (LateFeeCharge $record) => $record->status === 'pending')
                    ->action(function (LateFeeCharge $record) {
                        $record->update([
                            'status' => 'confirmed',
                            'confirmed_by' => auth()->id(),
                            'confirmed_at' => now(),
                        ]);

                        // Notifikasi ke customer
                        try {
                            $record->customer?->user?->notify(
                                new LateFeeConfirmedNotification($record)
                            );
                        } catch (\Throwable) {
                        }

                        Notification::make()
                            ->title('Tagihan dikonfirmasi — notifikasi dikirim ke customer')
                            ->success()
                            ->send();
                    }),

                Action::make('mark_paid')
                    ->label('💰 Tandai Lunas')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (LateFeeCharge $record) => $record->status === 'confirmed')
                    ->form([
                        Forms\Components\Placeholder::make('proof_info')
                            ->label('')
                            ->content(fn (LateFeeCharge $record) => $record->payment_proof
                                ? new HtmlString(
                                    '<p class="text-sm text-green-700">✅ Customer sudah upload bukti pembayaran.</p>'
                                )
                                : new HtmlString(
                                    '<p class="text-sm text-orange-600">⚠️ Customer belum upload bukti. Yakin ingin tandai lunas?</p>'
                                )
                            ),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Catatan Konfirmasi (opsional)')
                            ->rows(2),
                    ])
                    ->action(function (LateFeeCharge $record, array $data) {
                        $record->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                            'admin_notes' => $data['admin_notes'] ?? $record->admin_notes,
                        ]);

                        // Notifikasi ke customer dan vendor bahwa denda sudah terkonfirmasi lunas
                        try {
                            $record->customer?->user?->notify(
                                new LateFeePaidNotification($record)
                            );
                        } catch (\Throwable) {
                        }

                        try {
                            $record->vendor?->user?->notify(
                                new LateFeePaidNotification($record)
                            );
                        } catch (\Throwable) {
                        }

                        Notification::make()
                            ->title('Denda ditandai lunas')
                            ->success()
                            ->send();
                    }),

                Action::make('waive')
                    ->label('🎁 Bebaskan Denda')
                    ->color('gray')
                    ->icon('heroicon-o-gift')
                    ->visible(fn (LateFeeCharge $record) => in_array($record->status, ['pending', 'confirmed']))
                    ->form([
                        Forms\Components\TextInput::make('waive_reason')
                            ->label('Alasan Pembebasan Denda')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Customer reguler, denda dibebaskan atas kebijakan vendor'),
                    ])
                    ->action(function (LateFeeCharge $record, array $data) {
                        $record->update([
                            'status' => 'waived',
                            'waive_reason' => $data['waive_reason'],
                        ]);

                        // Notifikasi ke customer bahwa denda dibebaskan
                        try {
                            $record->customer?->user?->notify(
                                new LateFeeWaivedNotification($record)
                            );
                        } catch (\Throwable) {
                        }

                        Notification::make()
                            ->title('Denda dibebaskan — notifikasi dikirim ke customer')
                            ->success()
                            ->send();
                    }),

                Action::make('reject_dispute')
                    ->label('❌ Tolak Banding & Minta Bayar')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (LateFeeCharge $record) => !empty($record->dispute_reason) && in_array($record->status, ['pending', 'confirmed']))
                    ->form([
                        Forms\Components\Textarea::make('dispute_rejection_reason')
                            ->label('Alasan Penolakan Keberatan / Penjelasan Admin')
                            ->required()
                            ->rows(3)
                            ->placeholder('Jelaskan mengapa denda tetap wajib dibayar, misal: Berdasarkan data pelacakan GPS, kendaraan berada di area non-kemacetan...'),
                    ])
                    ->action(function (LateFeeCharge $record, array $data) {
                        $record->update([
                            'status'                   => 'confirmed',
                            'dispute_rejection_reason' => $data['dispute_rejection_reason'],
                            'dispute_rejected_at'       => now(),
                        ]);

                        // Notifikasi ke customer bahwa keberatan denda ditolak
                        try {
                            $record->customer?->user?->notify(
                                new \App\Notifications\LateFeeDisputeRejectedNotification($record)
                            );
                        } catch (\Throwable $e) {
                            \Log::error('Failed to notify customer on dispute rejection', ['id' => $record->id]);
                        }

                        Notification::make()
                            ->title('Keberatan ditolak — notifikasi & penjelasan dikirim ke customer')
                            ->success()
                            ->send();
                    }),

                EditAction::make()->label('Detail'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (LateFeeCharge $record) => static::getUrl('edit', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLateFeeCharges::route('/'),
            'edit' => Pages\EditLateFeeCharge::route('/{record}/edit'),
        ];
    }
}
