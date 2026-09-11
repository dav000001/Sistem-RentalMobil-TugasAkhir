<?php

namespace App\Filament\Admin\Resources;

use App\Models\Payout;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;
    protected static ?string $navigationLabel = 'Payout Vendor';
    protected static ?int    $navigationSort  = 6;

    public static function getNavigationGroup(): ?string
    {
        return 'Keuangan';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Payout::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            // ── Pilih Vendor ──────────────────────────────────────────
            Forms\Components\Select::make('vendor_id')
                ->label('Vendor')
                ->required()
                ->options(
                    \App\Models\Vendor::query()
                        ->orderBy('business_name')
                        ->get()
                        ->mapWithKeys(fn ($v) => [
                            $v->id => $v->business_name
                                . ($v->bank_account_number ? ' — ' . $v->bank_name . ' ' . $v->bank_account_number : ' — (rekening belum diisi)')
                        ])
                )
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state, Set $set) {
                    if (! $state) {
                        $set('transfer_reference', null);
                        $set('_bank_info', null);
                        $set('amount', null);
                        return;
                    }
                    $vendor = \App\Models\Vendor::find($state);
                    if ($vendor) {
                        // Auto-fill no. rekening ke field transfer_reference
                        $set('transfer_reference', $vendor->bank_account_number);

                        // Tampilkan info lengkap rekening
                        $bankInfo = implode(' | ', array_filter([
                            $vendor->bank_name,
                            $vendor->bank_account_number,
                            $vendor->bank_account_name,
                        ]));
                        $set('_bank_info', $bankInfo ?: null);

                        // Auto-fill jumlah payout dari booking yang belum di-payout
                        // EXCLUDE booking yang payment-nya sudah di-refund
                        $sudahDipayout = \App\Models\Payout::where('vendor_id', $state)
                            ->whereIn('status', ['paid', 'pending'])
                            ->sum('amount');

                        $totalVendorPayout = \App\Models\Booking::where('vendor_id', $state)
                            ->whereHas('payment', fn ($q) => $q->where('status', 'paid')) // exclude refunded
                            ->sum('vendor_payout_amount');

                        $sisaBelumDipayout = max(0, $totalVendorPayout - $sudahDipayout);
                        $set('amount', $sisaBelumDipayout > 0 ? $sisaBelumDipayout : null);
                    }
                })
                ->columnSpanFull(),

            // ── Info Rekening (readonly, auto-fill) ───────────────────
            Forms\Components\Placeholder::make('_bank_info')
                ->label('🏦 Info Rekening Vendor')
                ->content(function (Get $get, $record) {
                    // Saat edit: ambil dari record
                    $vendorId = $get('vendor_id') ?? $record?->vendor_id;
                    if (! $vendorId) return '—';
                    $vendor = \App\Models\Vendor::find($vendorId);
                    if (! $vendor) return '—';
                    return implode(' | ', array_filter([
                        $vendor->bank_name,
                        $vendor->bank_account_number,
                        $vendor->bank_account_name,
                    ])) ?: '— Data rekening belum diisi —';
                })
                ->visible(fn (Get $get, $record) => (bool) ($get('vendor_id') ?? $record?->vendor_id))
                ->columnSpanFull(),

            // ── Jumlah & Status ───────────────────────────────────────
            Forms\Components\TextInput::make('amount')
                ->numeric()
                ->required()
                ->prefix('Rp')
                ->label('Jumlah Payout')
                ->placeholder('Contoh: 1000000 (untuk Rp 1.000.000)')
                ->helperText('Ketik angka tanpa titik. Contoh: 1000000 = Rp 1.000.000'),

            Forms\Components\Select::make('status')
                ->options([
                    'pending' => '⏳ Pending — Belum Ditransfer',
                    'paid'    => '✅ Sudah Dibayar',
                    'failed'  => '❌ Gagal',
                ])
                ->required()
                ->label('Status')
                ->default('pending'),

            // ── No. Rekening (auto-fill dari vendor) ──────────────────
            Forms\Components\TextInput::make('transfer_reference')
                ->label('No. Rekening / Referensi Transfer')
                ->placeholder('Otomatis terisi saat vendor dipilih')
                ->helperText('Terisi otomatis dari data rekening vendor. Bisa diubah jika perlu.')
                ->columnSpanFull(),

            Forms\Components\Textarea::make('notes')
                ->label('Catatan')
                ->placeholder('Catatan internal (opsional)')
                ->columnSpanFull(),

            // ── Bukti Transfer ────────────────────────────────────────
            // Preview gambar existing jika sudah ada
            Forms\Components\Placeholder::make('transfer_proof_preview')
                ->label('📎 Bukti Transfer (Tersimpan)')
                ->content(function ($record) {
                    if (!$record?->transfer_proof) return '—';
                    $url = asset('storage/' . ltrim($record->transfer_proof, '/'));
                    return new \Illuminate\Support\HtmlString(
                        '<a href="' . $url . '" target="_blank">'
                        . '<img src="' . $url . '" style="max-height:200px;border-radius:8px;border:1px solid #e5e7eb;" />'
                        . '<br><small class="text-gray-400">Klik untuk buka di tab baru</small>'
                        . '</a>'
                    );
                })
                ->visible(fn ($record) => (bool) $record?->transfer_proof)
                ->columnSpanFull(),

            // Field upload baru — pakai nama berbeda agar tidak auto-load file lama
            Forms\Components\FileUpload::make('transfer_proof_new')
                ->label(fn ($record) => $record?->transfer_proof ? '🔄 Ganti Bukti Transfer' : '📎 Upload Bukti Transfer')
                ->helperText('Upload foto/screenshot bukti transfer dari bank (JPG, PNG — maks 2MB)')
                ->image()
                ->imagePreviewHeight('200')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                ->maxSize(2048)
                ->disk('public')
                ->directory('payout-proofs')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vendor.business_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) =>
                        $record->vendor?->bank_name . ' — ' . $record->vendor?->bank_account_number
                    ),

                Tables\Columns\TextColumn::make('period_start')
                    ->label('Periode')
                    ->formatStateUsing(fn ($state, $record) =>
                        $record->period_start->format('d M Y') . ' – ' . $record->period_end->format('d M Y')
                    ),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Belum Dibayar',
                        'paid'    => 'Sudah Dibayar',
                        'failed'  => 'Gagal',
                        default   => ucfirst($state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'paid'    => 'success',
                        'failed'  => 'danger',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Dibayar')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('transfer_reference')
                    ->label('Ref. Transfer')
                    ->placeholder('—'),

                Tables\Columns\ImageColumn::make('transfer_proof')
                    ->label('Bukti')
                    ->disk('public')
                    ->height(40)
                    ->width(60)
                    ->defaultImageUrl(null)
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Belum Dibayar',
                        'paid'    => 'Sudah Dibayar',
                        'failed'  => 'Gagal',
                    ]),
            ])
            ->actions([
                // Konfirmasi sudah transfer
                Action::make('mark_paid')
                    ->label('✅ Konfirmasi Transfer')
                    ->color('success')
                    ->visible(fn (Payout $record) => $record->status === 'pending')
                    ->schema([
                        Forms\Components\TextInput::make('transfer_reference')
                            ->label('No. Referensi Transfer')
                            ->placeholder('Isi dengan nomor referensi dari bank setelah transfer')
                            ->helperText('Contoh: 20260508143201234 — nomor ini ada di struk/bukti transfer bank')
                            ->required(),
                        Forms\Components\FileUpload::make('transfer_proof')
                            ->label('📎 Upload Bukti Transfer')
                            ->helperText('Upload foto/screenshot struk transfer dari bank')
                            ->image()
                            ->imagePreviewHeight('150')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                            ->maxSize(2048)
                            ->disk('public')
                            ->directory('payout-proofs')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Opsional'),
                    ])
                    ->action(function (Payout $record, array $data) {
                        $record->update([
                            'status'             => 'paid',
                            'paid_at'            => now(),
                            'transfer_reference' => $data['transfer_reference'],
                            'transfer_proof'     => $data['transfer_proof'] ?? null,
                            'notes'              => $data['notes'] ?? null,
                            'confirmed_by'       => auth('admin')->id() ?? auth()->id(),
                        ]);

                        // Notifikasi ke vendor
                        try {
                            $record->vendor->user->notify(
                                new \App\Notifications\PayoutCreatedNotification($record)
                            );
                        } catch (\Throwable) {}

                        Notification::make()
                            ->title('Payout dikonfirmasi — Rp ' . number_format($record->amount, 0, ',', '.'))
                            ->success()
                            ->send();
                    }),

                // Tandai Gagal (dari pending)
                Action::make('mark_failed')
                    ->label('❌ Tandai Gagal')
                    ->color('danger')
                    ->visible(fn (Payout $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Payout Gagal?')
                    ->modalDescription('Vendor akan mendapat notifikasi bahwa transfer gagal. Payout akan tetap tercatat dan bisa dicoba ulang.')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Alasan Kegagalan')
                            ->placeholder('Contoh: Nomor rekening tidak valid, rekening ditutup, dll.')
                            ->required(),
                    ])
                    ->action(function (Payout $record, array $data) {
                        $record->update([
                            'status' => 'failed',
                            'notes'  => $data['notes'],
                        ]);

                        // Notifikasi ke vendor
                        try {
                            $record->vendor?->user?->notify(
                                new \App\Notifications\PayoutFailedNotification($record, $data['notes'])
                            );
                        } catch (\Throwable) {}

                        Notification::make()
                            ->title('Payout ditandai gagal')
                            ->body('Vendor telah dinotifikasi.')
                            ->warning()
                            ->send();
                    }),

                // Coba Lagi (dari failed → kembali ke pending)
                Action::make('retry_payout')
                    ->label('🔄 Coba Lagi')
                    ->color('warning')
                    ->visible(fn (Payout $record) => $record->status === 'failed')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Payout ke Pending?')
                    ->modalDescription('Status akan dikembalikan ke Pending sehingga bisa diproses ulang.')
                    ->action(function (Payout $record) {
                        $record->update([
                            'status' => 'pending',
                            'notes'  => $record->notes ? $record->notes . ' [Dicoba ulang ' . now()->format('d/m/Y H:i') . ']' : null,
                        ]);

                        Notification::make()
                            ->title('Payout direset ke Pending')
                            ->body('Silakan proses transfer ulang.')
                            ->success()
                            ->send();
                    }),

                EditAction::make()->label('Edit'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Admin\Resources\PayoutResource\Pages\ListPayouts::route('/'),
            'create' => \App\Filament\Admin\Resources\PayoutResource\Pages\CreatePayout::route('/create'),
            'edit'   => \App\Filament\Admin\Resources\PayoutResource\Pages\EditPayout::route('/{record}/edit'),
        ];
    }
}
