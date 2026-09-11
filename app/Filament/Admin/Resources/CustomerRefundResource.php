<?php

namespace App\Filament\Admin\Resources;

use App\Models\CustomerRefund;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerRefundResource extends Resource
{
    protected static ?string $model = CustomerRefund::class;
    protected static ?string $navigationLabel = 'Refund Customer';
    protected static ?int    $navigationSort  = 7;

    public static function getNavigationGroup(): ?string
    {
        return 'Keuangan';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-arrow-uturn-left';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = CustomerRefund::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            // ── Pilih Booking (hanya saat CREATE) ────────────────────
            Forms\Components\Select::make('booking_id')
                ->label('Booking')
                ->required()
                ->options(function () {
                    return Booking::query()
                        ->where('status', 'cancelled')
                        ->whereHas('payment', fn ($q) => $q->where('status', 'paid'))
                        ->whereDoesntHave('refund', fn ($q) => $q->whereIn('status', ['pending', 'paid']))
                        ->with(['customer', 'car'])
                        ->get()
                        ->mapWithKeys(fn ($b) => [
                            $b->id => $b->code
                                . ' — ' . ($b->customer?->full_name ?? '—')
                                . ' — ' . ($b->car?->brand . ' ' . $b->car?->model)
                                . ' — Rp ' . number_format($b->total, 0, ',', '.')
                        ]);
                })
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state, Set $set) {
                    if (! $state) return;

                    $booking = Booking::with(['customer', 'payment'])->find($state);
                    if (! $booking) return;

                    $customer = $booking->customer;

                    $set('customer_id',       $customer?->id);
                    $set('amount',            $booking->payment?->amount);
                    $set('bank_name',         $customer?->bank_name);
                    $set('bank_account_no',   $customer?->bank_account_no);
                    $set('bank_account_name', $customer?->bank_account_name);
                })
                ->visibleOn('create')
                ->columnSpanFull(),

            // ── Info Booking (hanya saat EDIT, readonly) ──────────────
            Forms\Components\Placeholder::make('booking_info')
                ->label('Booking')
                ->content(function ($record) {
                    if (! $record?->booking) return '—';
                    $b = $record->booking;
                    return ($b->code ?? '—')
                        . ' — ' . ($b->customer?->full_name ?? '—')
                        . ' — ' . ($b->car?->brand . ' ' . $b->car?->model)
                        . ' — Rp ' . number_format($b->total, 0, ',', '.');
                })
                ->visibleOn('edit')
                ->columnSpanFull(),

            // ── Info Rekening Customer (auto-fill) ────────────────────
            Forms\Components\Placeholder::make('_bank_info')
                ->label('🏦 Info Rekening Customer')
                ->content(function (Get $get, $record) {
                    $bookingId = $get('booking_id') ?? $record?->booking_id;
                    if (! $bookingId) return '—';

                    $booking = Booking::with('customer')->find($bookingId);
                    $customer = $booking?->customer;
                    if (! $customer) return '—';

                    if ($customer->bank_account_no) {
                        return ($customer->bank_name ?? '—')
                            . ' | ' . $customer->bank_account_no
                            . ' | a.n. ' . ($customer->bank_account_name ?? '—');
                    }

                    return new \Illuminate\Support\HtmlString(
                        '<span class="text-orange-600 font-medium">⚠️ Customer belum mengisi info rekening. '
                        . 'Hubungi customer untuk mendapatkan nomor rekening.</span>'
                    );
                })
                ->visible(fn (Get $get, $record) => (bool) ($get('booking_id') ?? $record?->booking_id))
                ->columnSpanFull(),

            // ── Hidden customer_id ────────────────────────────────────
            Forms\Components\Hidden::make('customer_id'),

            // ── Rekening (editable, auto-fill dari customer) ──────────
            Forms\Components\TextInput::make('bank_name')
                ->label('Bank / E-Wallet')
                ->placeholder('Contoh: BCA, GoPay')
                ->maxLength(50),

            Forms\Components\TextInput::make('bank_account_no')
                ->label('Nomor Rekening / HP')
                ->placeholder('Contoh: 1234567890')
                ->maxLength(30),

            Forms\Components\TextInput::make('bank_account_name')
                ->label('Nama Pemilik Rekening')
                ->placeholder('Nama sesuai buku tabungan')
                ->maxLength(100),

            // ── Jumlah & Status ───────────────────────────────────────
            Forms\Components\TextInput::make('amount')
                ->numeric()
                ->required()
                ->prefix('Rp')
                ->label('Jumlah Refund')
                ->helperText('Otomatis terisi dari jumlah pembayaran booking.'),

            Forms\Components\Select::make('status')
                ->options([
                    'pending' => '⏳ Pending — Belum Ditransfer',
                    'paid'    => '✅ Sudah Ditransfer',
                    'failed'  => '❌ Gagal',
                ])
                ->required()
                ->label('Status')
                ->default('pending'),

            // ── Referensi & Bukti ─────────────────────────────────────
            Forms\Components\TextInput::make('transfer_reference')
                ->label('Nomor Referensi Transfer')
                ->placeholder('Nomor transaksi dari m-banking setelah transfer')
                ->maxLength(100)
                ->columnSpanFull(),

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

            // Field upload baru — nama berbeda agar tidak auto-load file lama
            Forms\Components\FileUpload::make('transfer_proof_new')
                ->label(fn ($record) => $record?->transfer_proof ? '🔄 Ganti Bukti Transfer' : '📎 Upload Bukti Transfer')
                ->helperText('Upload foto/screenshot bukti transfer (JPG, PNG — maks 2MB)')
                ->image()
                ->imagePreviewHeight('200')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                ->maxSize(2048)
                ->disk('public')
                ->directory('refund-proofs')
                ->columnSpanFull(),

            Forms\Components\Textarea::make('notes')
                ->label('Catatan')
                ->placeholder('Catatan internal (opsional)')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking.code')
                    ->label('Kode Booking')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn ($record) =>
                        $record->bank_name
                            ? $record->bank_name . ' — ' . $record->bank_account_no
                            : '⚠️ Rekening belum diisi'
                    ),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah Refund')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Belum Ditransfer',
                        'paid'    => 'Sudah Ditransfer',
                        'failed'  => 'Gagal',
                        default   => ucfirst($state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'danger',
                        'paid'    => 'success',
                        'failed'  => 'gray',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Ditransfer')
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
                        'pending' => 'Belum Ditransfer',
                        'paid'    => 'Sudah Ditransfer',
                        'failed'  => 'Gagal',
                    ]),
            ])
            ->actions([
                // Konfirmasi sudah transfer refund
                Action::make('mark_paid')
                    ->label('✅ Konfirmasi Transfer')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (CustomerRefund $record) => $record->status === 'pending')
                    ->schema([
                        Forms\Components\Placeholder::make('_info')
                            ->label('Info Refund')
                            ->content(fn (CustomerRefund $record) =>
                                'Booking: ' . $record->booking?->code
                                . ' | Customer: ' . $record->customer?->full_name
                                . ' | Jumlah: Rp ' . number_format($record->amount, 0, ',', '.')
                                . ' | Rekening: ' . ($record->bank_name ?? '—')
                                . ' ' . ($record->bank_account_no ?? '—')
                                . ' a.n. ' . ($record->bank_account_name ?? '—')
                            ),
                        Forms\Components\TextInput::make('transfer_reference')
                            ->label('Nomor Referensi Transfer')
                            ->placeholder('Nomor transaksi dari m-banking')
                            ->required(),
                        Forms\Components\FileUpload::make('transfer_proof')
                            ->label('📎 Upload Bukti Transfer')
                            ->image()
                            ->imagePreviewHeight('150')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                            ->maxSize(2048)
                            ->disk('public')
                            ->directory('refund-proofs'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Opsional')
                            ->rows(2),
                    ])
                    ->action(function (CustomerRefund $record, array $data) {
                        $record->update([
                            'status'             => 'paid',
                            'paid_at'            => now(),
                            'transfer_reference' => $data['transfer_reference'],
                            'transfer_proof'     => $data['transfer_proof'] ?? null,
                            'notes'              => $data['notes'] ?? null,
                            'confirmed_by'       => auth('admin')->id() ?? auth()->id(),
                        ]);

                        // Load relasi booking + payment secara eksplisit
                        $booking = \App\Models\Booking::with('payment')
                            ->find($record->booking_id);

                        if ($booking?->payment) {
                            $booking->payment->update([
                                'status'      => 'refunded',
                                'refunded_at' => now(),
                                'refund_ref'  => $data['transfer_reference'],
                                'refund_note' => $data['notes'] ?? null,
                            ]);
                        }

                        // ── Potong vendor_payout_amount sebesar refund ────────────────
                        // Hanya potong jika notes mengandung "komplain" (bukan refund pembatalan biasa)
                        if ($booking && str_contains(strtolower($record->notes ?? ''), 'komplain')) {
                            $currentPayout = $booking->vendor_payout_amount ?? 0;
                            $newPayout     = max(0, $currentPayout - $record->amount);
                            $booking->updateQuietly(['vendor_payout_amount' => $newPayout]);
                        }

                        // Notifikasi ke customer
                        try {
                            $customer = \App\Models\Customer::with('user')->find($record->customer_id);
                            if ($customer?->user && $booking) {
                                $customer->user->notify(
                                    new \App\Notifications\RefundProcessedNotification(
                                        $booking,
                                        $data['transfer_reference']
                                    )
                                );
                            }
                        } catch (\Throwable) {}

                        Notification::make()
                            ->title('Refund dikonfirmasi — Rp ' . number_format($record->amount, 0, ',', '.'))
                            ->body('Customer akan mendapat notifikasi.' . (str_contains(strtolower($record->notes ?? ''), 'komplain') ? ' Payout vendor otomatis dipotong.' : ''))
                            ->success()
                            ->send();
                    }),

                EditAction::make()->label('Edit'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Admin\Resources\CustomerRefundResource\Pages\ListCustomerRefunds::route('/'),
            'create' => \App\Filament\Admin\Resources\CustomerRefundResource\Pages\CreateCustomerRefund::route('/create'),
            'edit'   => \App\Filament\Admin\Resources\CustomerRefundResource\Pages\EditCustomerRefund::route('/{record}/edit'),
        ];
    }
}
