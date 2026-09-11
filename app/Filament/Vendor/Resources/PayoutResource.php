<?php

namespace App\Filament\Vendor\Resources;

use App\Models\Payout;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;
    protected static ?string $navigationLabel = 'Riwayat Payout';
    protected static ?int    $navigationSort  = 3;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationBadge(): ?string
    {
        $vendor = auth('vendor')->user()?->vendor;
        if (! $vendor) return null;
        $count = Payout::where('vendor_id', $vendor->id)
            ->where('status', 'paid')
            ->whereNull('transfer_proof')
            ->count();
        return null; // tidak perlu badge
    }

    // Vendor hanya bisa lihat payout miliknya sendiri
    public static function getEloquentQuery(): Builder
    {
        $vendor = auth('vendor')->user()?->vendor;
        return parent::getEloquentQuery()
            ->where('vendor_id', $vendor?->id ?? 0)
            ->latest();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Placeholder::make('vendor_info')
                ->label('Vendor')
                ->content(fn ($record) => $record?->vendor?->business_name ?? '—'),

            Forms\Components\Placeholder::make('amount')
                ->label('Jumlah Payout')
                ->content(fn ($record) => $record?->amount
                    ? 'Rp ' . number_format($record->amount, 0, ',', '.')
                    : '—'),

            Forms\Components\Placeholder::make('status')
                ->label('Status')
                ->content(fn ($record) => match($record?->status) {
                    'paid'    => '✅ Sudah Dibayar',
                    'pending' => '⏳ Menunggu Transfer',
                    'failed'  => '❌ Gagal',
                    default   => '—',
                }),

            Forms\Components\Placeholder::make('paid_at')
                ->label('Tanggal Dibayar')
                ->content(fn ($record) => $record?->paid_at?->format('d M Y H:i') ?? '—'),

            Forms\Components\Placeholder::make('transfer_reference')
                ->label('No. Referensi Transfer')
                ->content(fn ($record) => $record?->transfer_reference ?? '—'),

            Forms\Components\Placeholder::make('transfer_proof')
                ->label('📎 Bukti Transfer')
                ->content(fn ($record) => $record?->transfer_proof
                    ? new \Illuminate\Support\HtmlString(
                        '<a href="' . asset('storage/' . $record->transfer_proof) . '" target="_blank">'
                        . '<img src="' . asset('storage/' . $record->transfer_proof) . '" '
                        . 'style="max-width:350px; max-height:250px; border-radius:8px; border:1px solid #e5e7eb; cursor:pointer;">'
                        . '<br><small style="color:#2563eb;">🔍 Klik untuk lihat ukuran penuh</small>'
                        . '</a>'
                    )
                    : new \Illuminate\Support\HtmlString(
                        '<span style="color:#9ca3af;">— Bukti transfer belum diupload admin</span>'
                    ))
                ->columnSpanFull(),

            Forms\Components\Placeholder::make('notes')
                ->label('Catatan')
                ->content(fn ($record) => $record?->notes ?? '—')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period_start')
                    ->label('Periode')
                    ->formatStateUsing(fn ($state, $record) =>
                        ($record->period_start?->format('d M Y') ?? '—')
                        . ' – '
                        . ($record->period_end?->format('d M Y') ?? '—')
                    ),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => '⏳ Menunggu Transfer',
                        'paid'    => '✅ Sudah Dibayar',
                        'failed'  => '❌ Gagal',
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
                    ->label('No. Referensi')
                    ->placeholder('—')
                    ->limit(20),

                Tables\Columns\ImageColumn::make('transfer_proof')
                    ->label('Bukti Transfer')
                    ->disk('public')
                    ->height(45)
                    ->width(65)
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu Transfer',
                        'paid'    => 'Sudah Dibayar',
                        'failed'  => 'Gagal',
                    ]),
            ])
            ->recordUrl(fn (Payout $record) => static::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Belum ada payout')
            ->emptyStateDescription('Payout akan muncul di sini setelah admin membuatkan payout untuk Anda.');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Vendor\Resources\PayoutResource\Pages\ListPayouts::route('/'),
            'view'  => \App\Filament\Vendor\Resources\PayoutResource\Pages\ViewPayout::route('/{record}'),
        ];
    }

    // Vendor tidak bisa create/edit payout
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
}
