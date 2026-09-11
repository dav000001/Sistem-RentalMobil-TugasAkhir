<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DisputeResource\Pages;
use App\Models\Dispute;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DisputeResource extends Resource
{
    protected static ?string $model = Dispute::class;

    protected static bool $shouldRegisterNavigation = false; // Digabung ke fitur Komplain

    public static function getNavigationGroup(): ?string
    {
        return 'Transaksi';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-exclamation-triangle';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Dispute::whereIn('status', ['open', 'in_review'])->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            // ── Info Booking ───────────────────────────────────────────
            \Filament\Schemas\Components\Section::make('📋 Informasi Booking')
                ->columns(3)
                ->components([
                    Forms\Components\Placeholder::make('booking_code')
                        ->label('Kode Booking')
                        ->content(fn ($record) => $record?->booking?->code ?? '—'),

                    Forms\Components\Placeholder::make('car_name')
                        ->label('Mobil')
                        ->content(fn ($record) => $record?->booking?->car
                            ? $record->booking->car->brand . ' ' . $record->booking->car->model
                              . ' (' . $record->booking->car->plate_number . ')'
                            : '—'),

                    Forms\Components\Placeholder::make('booking_status')
                        ->label('Status Booking')
                        ->content(fn ($record) => match ($record?->booking?->status) {
                            'awaiting_payment' => '⏳ Menunggu Pembayaran',
                            'awaiting_vendor'  => '🔔 Menunggu Konfirmasi Vendor',
                            'confirmed'        => '✅ Dikonfirmasi',
                            'ongoing'          => '🚗 Sedang Berlangsung',
                            'completed'        => '🏁 Selesai',
                            'cancelled'        => '❌ Dibatalkan',
                            'refunded'         => '💸 Refund',
                            'disputed'         => '⚠️ Sengketa',
                            default            => ucfirst($record?->booking?->status ?? '—'),
                        }),

                    Forms\Components\Placeholder::make('booking_period')
                        ->label('Periode Sewa')
                        ->content(fn ($record) => $record?->booking?->start_at && $record?->booking?->end_at
                            ? $record->booking->start_at->format('d M Y') . ' → '
                              . $record->booking->end_at->format('d M Y')
                            : '—'),

                    Forms\Components\Placeholder::make('booking_total')
                        ->label('Total Pembayaran')
                        ->content(fn ($record) => $record?->booking?->total
                            ? 'Rp ' . number_format($record->booking->total, 0, ',', '.')
                            : '—'),

                    Forms\Components\Placeholder::make('payment_status')
                        ->label('Status Pembayaran')
                        ->content(fn ($record) => match ($record?->booking?->payment?->status) {
                            'paid'     => '✅ Sudah Dibayar',
                            'pending'  => '⏳ Belum Dibayar',
                            'refunded' => '💸 Direfund',
                            'failed'   => '❌ Gagal',
                            default    => '—',
                        }),
                ]),

            // ── Pihak Terlibat ─────────────────────────────────────────
            \Filament\Schemas\Components\Section::make('👥 Pihak yang Terlibat')
                ->columns(2)
                ->components([
                    Forms\Components\Placeholder::make('customer_info')
                        ->label('Customer (Penyewa)')
                        ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                            '<strong>' . ($record?->booking?->customer?->full_name ?? '—') . '</strong><br>'
                            . ($record?->booking?->customer?->user?->email ?? '—') . '<br>'
                            . ($record?->booking?->customer?->user?->phone ?? '—')
                        )),

                    Forms\Components\Placeholder::make('vendor_info')
                        ->label('Vendor (Penyedia)')
                        ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                            '<strong>' . ($record?->booking?->vendor?->business_name ?? '—') . '</strong><br>'
                            . ($record?->booking?->vendor?->user?->email ?? '—') . '<br>'
                            . ($record?->booking?->vendor?->user?->phone ?? '—')
                        )),

                    Forms\Components\Placeholder::make('opened_by')
                        ->label('Dibuka Oleh')
                        ->content(fn ($record) => $record?->openedBy
                            ? $record->openedBy->name . ' — ' . match($record->opened_by_role ?? 'customer') {
                                'vendor'   => '🏪 Vendor',
                                'customer' => '👤 Customer',
                                default    => ucfirst($record->opened_by_role ?? '—'),
                              }
                            : '—'),

                    Forms\Components\Placeholder::make('opened_at')
                        ->label('Dibuka Pada')
                        ->content(fn ($record) => $record?->created_at?->format('d M Y H:i') ?? '—'),
                ]),

            // ── Detail Sengketa ────────────────────────────────────────
            \Filament\Schemas\Components\Section::make('⚠️ Detail Sengketa')
                ->components([
                    Forms\Components\Placeholder::make('reason_display')
                        ->label('Alasan Sengketa')
                        ->content(fn ($record) => $record?->reason ?? '—')
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('evidence_display')
                        ->label('Bukti yang Dilampirkan')
                        ->content(function ($record) {
                            $evidence = $record?->evidence;
                            if (empty($evidence)) {
                                return new \Illuminate\Support\HtmlString('<span class="text-gray-400">Tidak ada bukti dilampirkan</span>');
                            }

                            $html = '<div class="flex flex-wrap gap-3 mt-1">';
                            foreach ((array) $evidence as $path) {
                                $url  = asset('storage/' . ltrim($path, '/'));
                                $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);

                                if ($isImg) {
                                    $html .= '<a href="' . $url . '" target="_blank">'
                                           . '<img src="' . $url . '" '
                                           . 'style="height:100px;width:auto;border-radius:6px;border:1px solid #e5e7eb;cursor:pointer;" '
                                           . 'title="Klik untuk lihat penuh"></a>';
                                } else {
                                    $html .= '<a href="' . $url . '" target="_blank" '
                                           . 'class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 rounded text-sm text-blue-600 hover:bg-gray-200">'
                                           . '📎 ' . basename($path) . '</a>';
                                }
                            }
                            $html .= '</div>';

                            return new \Illuminate\Support\HtmlString($html);
                        })
                        ->columnSpanFull(),
                ]),

            // ── Penanganan Admin ───────────────────────────────────────
            \Filament\Schemas\Components\Section::make('🔧 Penanganan Admin')
                ->columns(2)
                ->components([
                    Forms\Components\Placeholder::make('status_display')
                        ->label('Status Sengketa')
                        ->content(fn ($record) => match ($record?->status) {
                            'open'      => new \Illuminate\Support\HtmlString('<span class="text-red-600 font-semibold">🔴 Baru / Belum Ditinjau</span>'),
                            'in_review' => new \Illuminate\Support\HtmlString('<span class="text-yellow-600 font-semibold">🟡 Sedang Ditinjau</span>'),
                            'resolved'  => new \Illuminate\Support\HtmlString('<span class="text-green-600 font-semibold">🟢 Diselesaikan</span>'),
                            'rejected'  => new \Illuminate\Support\HtmlString('<span class="text-gray-600 font-semibold">⚫ Ditolak</span>'),
                            default     => ucfirst($record?->status ?? '—'),
                        }),

                    Forms\Components\Placeholder::make('admin_handler')
                        ->label('Admin Penanganan')
                        ->content(fn ($record) => $record?->admin?->name ?? '—'),

                    Forms\Components\Placeholder::make('resolved_at_display')
                        ->label('Diselesaikan / Ditolak Pada')
                        ->content(fn ($record) => $record?->resolved_at?->format('d M Y H:i') ?? '—'),

                    Forms\Components\Placeholder::make('resolution_display')
                        ->label('Keputusan / Resolusi')
                        ->content(fn ($record) => $record?->resolution
                            ? new \Illuminate\Support\HtmlString('<div class="p-3 bg-green-50 border border-green-200 rounded text-sm">' . e($record->resolution) . '</div>')
                            : new \Illuminate\Support\HtmlString('<span class="text-gray-400 italic">Belum ada keputusan</span>'))
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width(60),

                Tables\Columns\TextColumn::make('booking.code')
                    ->label('Kode Booking')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Dispute $record) =>
                        $record->booking?->car
                            ? $record->booking->car->brand . ' ' . $record->booking->car->model
                            : '—'
                    ),

                Tables\Columns\TextColumn::make('openedBy.name')
                    ->label('Dibuka Oleh')
                    ->searchable()
                    ->description(fn (Dispute $record) => match($record->opened_by_role ?? 'customer') {
                        'vendor'   => '🏪 Vendor',
                        'customer' => '👤 Customer',
                        default    => $record->openedBy?->role ?? '—',
                    }),

                Tables\Columns\TextColumn::make('booking.customer.full_name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('booking.vendor.business_name')
                    ->label('Vendor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(50)
                    ->tooltip(fn (Dispute $record) => $record->reason),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'open'      => '🔴 Baru',
                        'in_review' => '🟡 Ditinjau',
                        'resolved'  => '🟢 Selesai',
                        'rejected'  => '⚫ Ditolak',
                        default     => ucfirst($state),
                    })
                    ->color(fn (string $state) => match ($state) {
                        'open'      => 'danger',
                        'in_review' => 'warning',
                        'resolved'  => 'success',
                        'rejected'  => 'gray',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('admin.name')
                    ->label('Admin')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuka')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Diselesaikan')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open'      => '🔴 Baru',
                        'in_review' => '🟡 Sedang Ditinjau',
                        'resolved'  => '🟢 Diselesaikan',
                        'rejected'  => '⚫ Ditolak',
                    ]),
            ])
            ->actions([
                EditAction::make()->label('Kelola'),
            ])
            ->recordUrl(fn (Dispute $record) => static::getUrl('edit', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDisputes::route('/'),
            'edit'  => Pages\EditDispute::route('/{record}/edit'),
        ];
    }
}
