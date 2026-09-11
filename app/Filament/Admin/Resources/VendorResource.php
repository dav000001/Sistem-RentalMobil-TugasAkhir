<?php

namespace App\Filament\Admin\Resources;

use App\Enums\VendorStatus;
use App\Filament\Admin\Resources\VendorResource\Pages;
use App\Models\Vendor;
use Filament\Actions\EditAction as FilamentEditAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;
    protected static ?string $navigationLabel = 'Vendor';
    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-building-storefront';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Vendor::whereIn('status', ['pending', 'needs_revision'])
            ->where('documents_complete', true)
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('business_name')->required()->label('Nama Bisnis'),
            Forms\Components\Textarea::make('address')->required()->label('Alamat'),
            Forms\Components\Select::make('city_id')->relationship('city', 'name')->required()->label('Kota'),
            Forms\Components\TextInput::make('bank_name')->label('Nama Bank'),
            Forms\Components\TextInput::make('bank_account_number')->label('No. Rekening'),
            Forms\Components\TextInput::make('bank_account_name')->label('Nama Pemilik Rekening'),
            Forms\Components\Textarea::make('internal_notes')->label('Catatan Internal')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Nama Bisnis')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pemilik')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('Kota'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof VendorStatus ? $state->label() : (VendorStatus::tryFrom($state)?->label() ?? $state))
                    ->color(fn ($state): string => match (($state instanceof VendorStatus ? $state->value : $state)) {
                        'pending'        => 'warning',
                        'needs_revision' => 'warning',
                        'approved'       => 'success',
                        'rejected'       => 'danger',
                        'suspended'      => 'gray',
                        default          => 'gray',
                    }),
                Tables\Columns\IconColumn::make('documents_verified')
                    ->label('Dok. Terverifikasi')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(VendorStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->orderByRaw("
                CASE
                    WHEN status IN ('pending', 'needs_revision') AND documents_complete = 1 THEN 0
                    WHEN status IN ('pending', 'needs_revision') THEN 1
                    ELSE 2
                END ASC
            ")->orderBy('created_at', 'desc'))
            ->actions([
                FilamentEditAction::make()->label('Edit'),
            ])
            ->recordAction(null)
            ->recordUrl(fn (Vendor $record) => static::getUrl('view', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendors::route('/'),
            'edit'  => Pages\EditVendor::route('/{record}/edit'),
            'view'  => Pages\ViewVendor::route('/{record}'),
        ];
    }
}
