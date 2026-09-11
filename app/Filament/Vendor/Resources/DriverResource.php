<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Resources\DriverResource\Pages;
use App\Models\Driver;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DriverResource extends Resource
{
    protected static ?string $model = Driver::class;
    protected static ?string $navigationLabel = 'Data Sopir';
    protected static ?string $modelLabel = 'Sopir';
    protected static ?string $pluralModelLabel = 'Data Sopir';
    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-user-group';
    }

    public static function getNavigationBadge(): ?string
    {
        $vendor = auth('vendor')->user()?->vendor;
        $count  = Driver::where('vendor_id', $vendor?->id ?? 0)->where('status', 'active')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'success';
    }

    public static function getEloquentQuery(): Builder
    {
        $vendor = auth('vendor')->user()?->vendor;
        return parent::getEloquentQuery()->where('vendor_id', $vendor?->id ?? 0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Section::make('Informasi Sopir')
                ->columns(2)
                ->components([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('phone')
                        ->label('No. WhatsApp / Telepon')
                        ->required()
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'active'   => '✅ Aktif',
                            'inactive' => '❌ Tidak Aktif',
                        ])
                        ->required()
                        ->default('active'),

                    Forms\Components\TextInput::make('experience_years')
                        ->label('Pengalaman (tahun)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(50)
                        ->default(0),

                    Forms\Components\FileUpload::make('photo')
                        ->label('Foto Sopir')
                        ->image()
                        ->directory('driver-photos')
                        ->disk('public')
                        ->maxSize(2048)
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('license_photo')
                        ->label('Foto SIM')
                        ->helperText('Upload foto SIM sopir (JPG/PNG, maks. 2MB)')
                        ->image()
                        ->directory('driver-license-photos')
                        ->disk('public')
                        ->maxSize(2048)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(asset('images/driver-placeholder.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon'),

                Tables\Columns\IconColumn::make('license_photo')
                    ->label('Foto SIM')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($state) => $state ? 'Foto SIM tersedia' : 'Foto SIM belum diupload'),

                Tables\Columns\TextColumn::make('experience_years')
                    ->label('Pengalaman')
                    ->formatStateUsing(fn ($state) => $state . ' thn')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger'  => 'inactive',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'active' ? 'Aktif' : 'Tidak Aktif'),

                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Total Booking')
                    ->counts('bookings')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active'   => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                    ]),
            ])
            ->recordUrl(fn (Driver $record) => static::getUrl('edit', ['record' => $record]))
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDrivers::route('/'),
            'create' => Pages\CreateDriver::route('/create'),
            'edit'   => Pages\EditDriver::route('/{record}/edit'),
        ];
    }
}
