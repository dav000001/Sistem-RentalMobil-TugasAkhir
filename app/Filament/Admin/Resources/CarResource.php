<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CarResource\Pages;
use App\Models\Car;
use App\Models\CarBrand;
use App\Models\CarModel as CarModelModel;
use App\Models\CarPricing;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CarResource extends Resource
{
    protected static ?string $model = Car::class;
    protected static ?string $navigationLabel = 'Mobil';
    protected static ?string $modelLabel = 'Mobil';
    protected static ?string $pluralModelLabel = 'Daftar Mobil';
    protected static ?string $breadcrumb = 'Mobil';
    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-truck';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Informasi Mobil')->components([
                // Khusus admin — pilih vendor pemilik mobil
                Forms\Components\Select::make('vendor_id')
                    ->relationship('vendor', 'business_name')
                    ->required()
                    ->searchable()
                    ->label('Vendor'),

                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->label('Kategori'),

                Forms\Components\Select::make('city_id')
                    ->relationship('city', 'name')
                    ->required()
                    ->searchable()
                    ->label('Kota'),

                // ── BRAND — cascading dropdown ─────────────────────
                Forms\Components\Select::make('car_brand_id')
                    ->label('Merek / Brand')
                    ->options(function () {
                        $brands = CarBrand::orderBy('name')->pluck('name', 'id')->toArray();
                        $brands['other'] = '— Lainnya (merek tidak ada di daftar) —';
                        return $brands;
                    })
                    ->live()
                    ->afterStateUpdated(function (Set $set) {
                        $set('car_model_id', null);
                        $set('brand_manual', null);
                        $set('model_manual', null);
                    })
                    ->searchable()
                    ->required()
                    ->placeholder('Pilih merek mobil'),

                // Brand manual — muncul jika pilih "Lainnya"
                Forms\Components\TextInput::make('brand_manual')
                    ->label('Nama Merek (Ketik Manual)')
                    ->placeholder('Contoh: Hino, Foton, ...')
                    ->required(fn (Get $get) => $get('car_brand_id') === 'other')
                    ->visible(fn (Get $get) => $get('car_brand_id') === 'other')
                    ->live(),

                // ── MODEL — dependent dari brand ───────────────────
                Forms\Components\Select::make('car_model_id')
                    ->label('Model')
                    ->options(function (Get $get) {
                        $brandId = $get('car_brand_id');
                        if (!$brandId || $brandId === 'other') return [];
                        $models = CarModelModel::where('car_brand_id', $brandId)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                        $models['other'] = '— Lainnya (model tidak ada di daftar) —';
                        return $models;
                    })
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('model_manual', null))
                    ->searchable()
                    ->required(fn (Get $get) => $get('car_brand_id') !== 'other' && filled($get('car_brand_id')))
                    ->visible(fn (Get $get) => filled($get('car_brand_id')) && $get('car_brand_id') !== 'other')
                    ->disabled(fn (Get $get) => !filled($get('car_brand_id')) || $get('car_brand_id') === 'other')
                    ->placeholder('Pilih model mobil')
                    ->helperText('Pilih merek terlebih dahulu'),

                // Model manual
                Forms\Components\TextInput::make('model_manual')
                    ->label('Nama Model (Ketik Manual)')
                    ->placeholder('Contoh: Innova Zenix, ...')
                    ->required(fn (Get $get) =>
                        $get('car_brand_id') === 'other' || $get('car_model_id') === 'other')
                    ->visible(fn (Get $get) =>
                        $get('car_brand_id') === 'other' || $get('car_model_id') === 'other')
                    ->live(),

                Forms\Components\TextInput::make('year')->numeric()->required()->label('Tahun'),
                Forms\Components\TextInput::make('plate_number')->required()->label('Nomor Plat'),
                Forms\Components\Select::make('transmission')
                    ->label('Transmisi')
                    ->options(['manual' => 'Manual', 'automatic' => 'Automatic'])->required(),
                Forms\Components\Select::make('fuel')
                    ->label('Bahan Bakar')
                    ->options(['bensin' => 'Bensin', 'diesel' => 'Diesel', 'listrik' => 'Listrik', 'hybrid' => 'Hybrid'])->required(),
                Forms\Components\TextInput::make('seats')->numeric()->required()->label('Jumlah Kursi'),
                Forms\Components\TextInput::make('luggage')->numeric()->required()->label('Kapasitas Bagasi'),
                Forms\Components\Textarea::make('description')->label('Deskripsi')->columnSpanFull(),

                // ── Opsi Sewa ─────────────────────────────────────
                Forms\Components\Select::make('rental_option')
                    ->label('Opsi Sewa Mobil')
                    ->options([
                        'self_drive_only'  => '🔑 Lepas Kunci Saja (Tanpa Sopir)',
                        'with_driver_only' => '👨‍✈️ Dengan Sopir Saja',
                        'both'             => '🔑👨‍✈️ Keduanya (Customer Bebas Pilih)',
                    ])
                    ->required()
                    ->default('self_drive_only')
                    ->live()
                    ->columnSpanFull(),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft'      => 'Draft',
                        'published'  => 'Published',
                        'suspended'  => 'Suspended',
                    ])
                    ->required()
                    ->default('published'),
            ])->columns(2),

            Section::make('Harga')->components([
                Forms\Components\TextInput::make('daily_price')
                    ->label('Harga per Hari')->numeric()->prefix('Rp')->required(),

                // Harga Sopir — muncul jika butuh sopir
                Forms\Components\TextInput::make('with_driver_price')
                    ->label('Harga Sopir/Hari')
                    ->numeric()
                    ->prefix('Rp')
                    ->visible(fn (Get $get) => in_array($get('rental_option'), ['with_driver_only', 'both']))
                    ->required(fn (Get $get) => in_array($get('rental_option'), ['with_driver_only', 'both'])),

                // Toggle sewa bulanan
                Forms\Components\Toggle::make('is_monthly_available')
                    ->label('Bisa Disewa Bulanan?')
                    ->live()
                    ->default(false),

                // Harga Bulanan — hanya muncul jika toggle aktif
                Forms\Components\TextInput::make('monthly_price')
                    ->label('Harga per Bulan')
                    ->numeric()
                    ->prefix('Rp')
                    ->visible(fn (Get $get) => (bool) $get('is_monthly_available'))
                    ->required(fn (Get $get) => (bool) $get('is_monthly_available')),

                Forms\Components\Toggle::make('fuel_included')
                    ->label('Termasuk BBM'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('brand')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('model')->searchable(),
                Tables\Columns\TextColumn::make('year'),
                Tables\Columns\TextColumn::make('vendor.business_name')->label('Vendor')->searchable(),
                Tables\Columns\TextColumn::make('city.name')->label('Kota'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'      => 'warning',
                        'published'  => 'success',
                        'suspended'  => 'danger',
                        default      => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'published' => 'Published', 'suspended' => 'Suspended']),
            ])
            ->recordUrl(fn (Car $record) => static::getUrl('edit', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCars::route('/'),
            'create' => Pages\CreateCar::route('/create'),
            'edit'   => Pages\EditCar::route('/{record}/edit'),
        ];
    }
}
