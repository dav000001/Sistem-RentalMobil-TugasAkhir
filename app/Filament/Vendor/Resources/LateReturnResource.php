<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Resources\LateReturnResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LateReturnResource extends Resource
{
    protected static ?string $model = Booking::class;
    protected static ?string $navigationLabel = 'Laporan Keterlambatan';
    protected static ?string $modelLabel = 'Keterlambatan';
    protected static ?string $pluralModelLabel = 'Laporan Keterlambatan';
    protected static ?int $navigationSort = 5;
    protected static ?string $slug = 'late-returns';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-clock';
    }

    public static function getNavigationBadge(): ?string
    {
        $vendor = auth('vendor')->user()?->vendor;
        $count  = Booking::where('vendor_id', $vendor?->id ?? 0)
            ->where('is_late', true)
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->whereHas('lateFeeCharge', fn ($chargeQuery) => 
                    $chargeQuery->whereIn('status', ['pending', 'confirmed'])
                )->orWhereDoesntHave('lateFeeCharge');
            })
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $vendor = auth('vendor')->user()?->vendor;
        return parent::getEloquentQuery()
            ->where('vendor_id', $vendor?->id ?? 0)
            ->where('status', 'completed')
            ->where('is_late', true)
            ->with(['customer', 'car', 'lateReturnReport', 'lateFeeCharge']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Booking')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Nama Customer')
                    ->description(function (Booking $record) {
                        $count = Booking::where('customer_id', $record->customer_id)
                            ->where('is_late', true)
                            ->where('status', 'completed')
                            ->count();
                        return $count > 1
                            ? '⚠️ Total Terlambat: ' . $count . 'x'
                            : '⚡ Keterlambatan ke-1';
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('car.brand')
                    ->label('Mobil')
                    ->formatStateUsing(fn ($state, Booking $record) =>
                        ($record->car?->brand ?? '') . ' ' . ($record->car?->model ?? '')
                    ),

                Tables\Columns\TextColumn::make('end_at')
                    ->label('Jatuh Tempo')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('actual_return_at')
                    ->label('Dikembalikan')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('late_duration_hours')
                    ->label('Terlambat')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state . ' jam' : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('late_fee')
                    ->label('Denda')
                    ->formatStateUsing(fn ($state) => $state > 0
                        ? 'Rp ' . number_format($state, 0, ',', '.')
                        : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('lateFeeCharge.status')
                    ->label('Status Denda')
                    ->badge()
                    ->formatStateUsing(fn ($state, Booking $record) => match ($record->lateFeeCharge?->status) {
                        'paid'      => '✅ Lunas',
                        'confirmed' => '💳 Belum Dibayar',
                        'pending'   => '⏳ Menunggu Konfirmasi',
                        'waived'    => '🎁 Dibebaskan',
                        default     => $record->late_fee > 0 ? '⚠️ Belum Lunas' : '—',
                    })
                    ->color(fn ($state, Booking $record) => match ($record->lateFeeCharge?->status) {
                        'paid'      => 'success',
                        'confirmed' => 'warning',
                        'pending'   => 'info',
                        'waived'    => 'gray',
                        default     => $record->late_fee > 0 ? 'danger' : 'gray',
                    }),

                Tables\Columns\TextColumn::make('lateReturnReport.status')
                    ->label('Laporan')
                    ->badge()
                    ->formatStateUsing(fn ($state, Booking $record) => match(true) {
                        $record->lateReturnReport === null         => '— Tidak Ada',
                        $state === 'acknowledged'                  => '✅ Diakui',
                        default                                    => '⏳ Menunggu',
                    })
                    ->color(fn ($state, Booking $record) => match(true) {
                        $record->lateReturnReport === null => 'gray',
                        $state === 'acknowledged'          => 'success',
                        default                            => 'warning',
                    }),

                Tables\Columns\TextColumn::make('lateReturnReport.latitude')
                    ->label('GPS')
                    ->formatStateUsing(fn ($state, Booking $record) => match(true) {
                        $record->lateReturnReport?->hasReturnLocation() => '📍📍 Lapor+Kembali',
                        $record->lateReturnReport?->hasLocation()       => '📍 Ada GPS',
                        $record->lateReturnReport !== null               => '⚠️ Tanpa GPS',
                        default                                          => '—',
                    })
                    ->color(fn ($state, Booking $record) => match(true) {
                        $record->lateReturnReport?->hasReturnLocation() => 'success',
                        $record->lateReturnReport?->hasLocation()       => 'info',
                        $record->lateReturnReport !== null               => 'warning',
                        default                                          => 'gray',
                    })
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\Filter::make('unpaid')
                    ->label('Belum Lunas')
                    ->query(fn (Builder $query) => $query->where(function ($q) {
                        $q->whereHas('lateFeeCharge', fn ($chargeQuery) => 
                            $chargeQuery->whereIn('status', ['pending', 'confirmed'])
                        )->orWhereDoesntHave('lateFeeCharge');
                    })),

                Tables\Filters\Filter::make('paid')
                    ->label('Sudah Lunas')
                    ->query(fn (Builder $query) => $query->whereHas('lateFeeCharge', fn ($q) => 
                        $q->where('status', 'paid')
                    )),

                Tables\Filters\Filter::make('on_time')
                    ->label('Tepat Waktu')
                    ->query(fn (Builder $query) => $query->where('is_late', false))
                    ->toggle(),

                Tables\Filters\Filter::make('date_range')
                    ->label('Rentang Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['from']) {
                            $query->whereDate('end_at', '>=', $data['from']);
                        }
                        if ($data['until']) {
                            $query->whereDate('end_at', '<=', $data['until']);
                        }
                    }),
            ])
            ->defaultSort('end_at', 'desc')
            ->striped()
            ->recordUrl(fn (Booking $record) => BookingResource::getUrl('edit', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLateReturns::route('/'),
        ];
    }

    // Read-only resource — tidak butuh create/edit
    public static function canCreate(): bool
    {
        return false;
    }
}
