<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Resources\CarResource\Pages;
use App\Models\Car;
use App\Models\CarBrand;
use App\Models\CarModel as CarModelModel;
use App\Models\CarPricing;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\DeleteAction as FilamentDeleteAction;
use Filament\Actions\EditAction as FilamentEditAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CarResource extends Resource
{
    protected static ?string $model = Car::class;
    protected static ?string $navigationLabel = 'Armada Saya';
    protected static ?string $modelLabel = 'Mobil';
    protected static ?string $pluralModelLabel = 'Armada';
    protected static ?string $breadcrumb = 'Armada';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-truck';
    }

    public static function getEloquentQuery(): Builder
    {
        $vendor = auth('vendor')->user()?->vendor;
        return parent::getEloquentQuery()->where('vendor_id', $vendor?->id ?? 0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Foto Mobil')->components([
                // Tampilkan foto existing dengan tombol hapus per foto
                Forms\Components\Placeholder::make('existing_photos_preview')
                    ->label('Foto Saat Ini')
                    ->content(function ($record) {
                        if (!$record || $record->photos->isEmpty()) {
                            return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Belum ada foto. Upload foto di bawah.</p>');
                        }
                        $html = '<div class="flex flex-wrap gap-3">';
                        foreach ($record->photos->sortBy('sort_order') as $i => $photo) {
                            $isMain = $i === 0;
                            $mainBadge = $isMain
                                ? '<span class="absolute top-1 left-1 bg-blue-500 text-white text-xs px-1.5 py-0.5 rounded font-medium">Utama</span>'
                                : '';
                            $html .= '<div class="relative group" style="width:96px">';
                            $html .= '<div class="w-24 h-24 rounded-xl overflow-hidden border-2 ' . ($isMain ? 'border-blue-400' : 'border-gray-200') . '">';
                            $html .= '<img src="' . $photo->path . '" class="w-full h-full object-cover">';
                            $html .= '</div>';
                            $html .= $mainBadge;
                            // Tombol hapus
                            $html .= '<button type="button"
                                wire:click="deletePhoto(' . $photo->id . ')"
                                wire:confirm="Hapus foto ini?"
                                class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full text-xs font-bold shadow-md flex items-center justify-center transition opacity-0 group-hover:opacity-100"
                                title="Hapus foto">✕</button>';
                            $html .= '</div>';
                        }
                        $html .= '</div>';
                        $html .= '<p class="text-xs text-gray-400 mt-2">Arahkan kursor ke foto untuk tombol hapus. Foto pertama otomatis jadi foto utama.</p>';
                        return new \Illuminate\Support\HtmlString($html);
                    })
                    ->columnSpanFull()
                    ->visibleOn('edit'),

                Forms\Components\FileUpload::make('car_photos')
                    ->label('Upload Foto Baru (akan mengganti foto lama)')
                    ->image()
                    ->multiple()
                    ->maxFiles(6)
                    ->maxSize(5120)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->directory('car-photos')
                    ->disk('public')
                    ->reorderable()
                    ->helperText('Upload foto baru untuk mengganti semua foto lama. Kosongkan jika tidak ingin mengubah foto. Foto pertama akan jadi foto utama.')
                    ->columnSpanFull(),
            ]),

            Section::make('Informasi Mobil')->components([
                Forms\Components\Select::make('category_id')->relationship('category', 'name')->required()->label('Kategori'),
                Forms\Components\Select::make('city_id')->relationship('city', 'name')->required()->label('Kota'),

                // ── BRAND — cascading dropdown ─────────────────────
                Forms\Components\Select::make('car_brand_id')
                    ->label('Merek / Brand')
                    ->options(function () {
                        $brands = CarBrand::orderBy('name')->pluck('name', 'id')->toArray();
                        // Tambahkan opsi "Lainnya" di paling bawah
                        $brands['other'] = '— Lainnya (merek tidak ada di daftar) —';
                        return $brands;
                    })
                    ->live()
                    ->afterStateUpdated(function (Set $set) {
                        // Reset model saat brand berubah
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
                        if (!$brandId || $brandId === 'other') {
                            return [];
                        }
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

                // Model manual — muncul jika brand = other, ATAU model = other
                Forms\Components\TextInput::make('model_manual')
                    ->label('Nama Model (Ketik Manual)')
                    ->placeholder('Contoh: Innova Zenix, ...')
                    ->required(fn (Get $get) =>
                        $get('car_brand_id') === 'other' ||
                        $get('car_model_id') === 'other'
                    )
                    ->visible(fn (Get $get) =>
                        $get('car_brand_id') === 'other' ||
                        $get('car_model_id') === 'other'
                    )
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
                    ->columnSpanFull()
                    ->helperText('Pilih opsi yang tersedia untuk mobil ini. "Keduanya" memberi customer kebebasan memilih.'),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft'     => 'Draft',
                        'published' => 'Published',
                    ])
                    ->required()
                    ->default('published')
                    ->live()
                    ->hiddenOn('create')
                    ->hidden(fn ($record) => $record?->status === 'unavailable'
                        || in_array($record?->unavailability_reason, ['subscription_expired', 'plan_downgrade']))
                    ->helperText('Draft = tidak tampil ke customer. Published = tampil ke customer.'),

                Forms\Components\Placeholder::make('status_unavailable_info')
                    ->label('Status')
                    ->content(new \Illuminate\Support\HtmlString(
                        '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-700">🔧 Tidak Tersedia</span>'
                        . '<p class="text-xs text-gray-500 mt-1">Dikelola oleh section "Ketersediaan Mobil" di bawah. Kosongkan alasan untuk mengembalikan ke Published.</p>'
                    ))
                    ->visible(fn ($record) => $record?->status === 'unavailable'),

                Forms\Components\Placeholder::make('status_subscription_info')
                    ->label('Status')
                    ->content(function ($record) {
                        $reason = $record?->unavailability_reason;
                        if ($reason === 'subscription_expired') {
                            return new \Illuminate\Support\HtmlString(
                                '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-700">⚠️ Disembunyikan — Paket Expired</span>'
                                . '<p class="text-xs text-gray-500 mt-1">Mobil ini disembunyikan secara otomatis karena paket berlangganan Anda telah berakhir. '
                                . '<a href="/vendor/billing" class="text-blue-600 underline font-medium">Perpanjang paket →</a> untuk menampilkan kembali.</p>'
                            );
                        }
                        if ($reason === 'plan_downgrade') {
                            return new \Illuminate\Support\HtmlString(
                                '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-semibold bg-orange-100 text-orange-700">📉 Disembunyikan — Melebihi Batas Paket</span>'
                                . '<p class="text-xs text-gray-500 mt-1">Paket aktif Anda memiliki batas jumlah mobil. Mobil ini dinonaktifkan otomatis karena melebihi batas tersebut. '
                                . '<a href="/vendor/billing" class="text-blue-600 underline font-medium">Upgrade paket →</a> untuk mengaktifkan lebih banyak mobil.</p>'
                            );
                        }
                        return null;
                    })
                    ->visible(fn ($record) => in_array($record?->unavailability_reason, ['subscription_expired', 'plan_downgrade'])),
            ])->columns(2),

            // ── Ketersediaan Mobil ────────────────────────────────────
            Section::make('🚗 Ketersediaan Mobil')
                ->description('Isi alasan di bawah jika mobil tidak bisa disewa sementara. Kosongkan untuk menandai mobil kembali tersedia.')
                ->components([
                    Forms\Components\Select::make('unavailability_reason')
                        ->label('Status Ketersediaan')
                        ->options([
                            'service'    => '🔧 Service / Perawatan',
                            'rusak'      => '⚠️ Rusak',
                            'kecelakaan' => '🚨 Kecelakaan',
                            'lainnya'    => '📝 Lainnya',
                        ])
                        ->placeholder('— Mobil Tersedia (tidak ada masalah) —')
                        ->helperText('Pilih alasan jika mobil tidak bisa disewa. Biarkan kosong jika mobil tersedia.')
                        ->live()
                        ->nullable()
                        ->dehydrated(true)
                        ->columnSpanFull()
                        // Sembunyikan jika mobil di-suspend karena subscription/downgrade
                        ->hidden(fn ($record) => in_array($record?->unavailability_reason, ['subscription_expired', 'plan_downgrade'])),

                    Forms\Components\Textarea::make('unavailability_notes')
                        ->label('Keterangan Tambahan')
                        ->placeholder('Jelaskan kondisi lebih detail (opsional)')
                        ->rows(2)
                        ->dehydrated(true)
                        ->hidden(fn ($record) => in_array($record?->unavailability_reason, ['subscription_expired', 'plan_downgrade']))
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => filled($get('unavailability_reason'))),

                    Forms\Components\DatePicker::make('unavailable_until')
                        ->label('Perkiraan Kembali Tersedia')
                        ->helperText('Kosongkan jika belum tahu')
                        ->minDate(now()->addDay())
                        ->dehydrated(true)
                        ->hidden(fn ($record) => in_array($record?->unavailability_reason, ['subscription_expired', 'plan_downgrade']))
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => filled($get('unavailability_reason'))),
                ])
                ->columns(2)
                ->visibleOn('edit'),

            Section::make('Harga')->components([
                Forms\Components\TextInput::make('daily_price')
                    ->label('Harga per Hari')->numeric()->prefix('Rp')->required(),

                // ── Harga Sopir — muncul jika rental_option butuh sopir ──
                Forms\Components\TextInput::make('with_driver_price')
                    ->label('Harga Sopir/Hari')
                    ->numeric()
                    ->prefix('Rp')
                    ->visible(fn (Get $get) => in_array($get('rental_option'), ['with_driver_only', 'both']))
                    ->required(fn (Get $get) => in_array($get('rental_option'), ['with_driver_only', 'both']))
                    ->helperText('Harga tambahan per hari jika customer memilih dengan sopir.'),

                // ── Toggle sewa bulanan ───────────────────────────────
                Forms\Components\Toggle::make('is_monthly_available')
                    ->label('Bisa Disewa Bulanan?')
                    ->live()
                    ->default(false)
                    ->helperText('Aktifkan jika mobil bisa disewa per bulan.'),

                // ── Harga Bulanan — hanya muncul jika toggle aktif ───
                Forms\Components\TextInput::make('monthly_price')
                    ->label('Harga per Bulan')
                    ->numeric()
                    ->prefix('Rp')
                    ->visible(fn (Get $get) => (bool) $get('is_monthly_available'))
                    ->required(fn (Get $get) => (bool) $get('is_monthly_available'))
                    ->helperText('Wajib diisi jika sewa bulanan diaktifkan.'),

                Forms\Components\Toggle::make('fuel_included')
                    ->label('Termasuk BBM'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('brand')->searchable(),
                Tables\Columns\TextColumn::make('model')->searchable(),
                Tables\Columns\TextColumn::make('year'),
                Tables\Columns\TextColumn::make('pricing.daily_price')->money('IDR')->label('Harga/Hari'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'       => 'warning',
                        'published'   => 'success',
                        'suspended'   => 'danger',
                        'unavailable' => 'gray',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft'       => 'Draft',
                        'published'   => 'Published',
                        'suspended'   => 'Disembunyikan',
                        'unavailable' => '🔧 Tidak Tersedia',
                        default       => ucfirst($state),
                    })
                    ->description(fn (Car $record): ?string => match (true) {
                        $record->status === 'suspended' && $record->unavailability_reason === 'subscription_expired'
                            => '⚠️ Paket berlangganan berakhir',
                        $record->status === 'suspended' && $record->unavailability_reason === 'plan_downgrade'
                            => '📉 Melebihi batas paket aktif',
                        $record->status === 'suspended'
                            => 'Dibekukan admin',
                        $record->status === 'unavailable' && $record->unavailability_reason
                            => match ($record->unavailability_reason) {
                                'service'    => 'Service / Perawatan',
                                'rusak'      => 'Rusak',
                                'kecelakaan' => 'Kecelakaan',
                                default      => 'Lainnya',
                            },
                        default => null,
                    }),
            ])
            ->recordUrl(fn (Car $record) => static::getUrl('edit', ['record' => $record]))
            ->actions([
                FilamentAction::make('calendar')
                    ->label('Kalender')
                    ->icon('heroicon-o-calendar-days')
                    ->color('info')
                    ->url(fn (Car $record) => route('vendor.cars.calendar', $record->id))
                    ->openUrlInNewTab(false),
                FilamentEditAction::make(),
                FilamentDeleteAction::make()
                    ->label('Hapus')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Mobil')
                    ->modalDescription(fn (Car $record) => 'Yakin ingin menghapus ' . $record->brand . ' ' . $record->model . ' (' . $record->plate_number . ')? Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->before(function (Car $record, $action) {
                        // Cek ownership
                        $vendor = auth('vendor')->user()?->vendor;
                        if (!$vendor || $record->vendor_id !== $vendor->id) {
                            Notification::make()
                                ->title('Akses Ditolak')
                                ->body('Anda tidak memiliki akses ke mobil ini.')
                                ->danger()
                                ->send();
                            $action->halt();
                        }

                        // Cek booking aktif (pending, confirmed, ongoing)
                        $activeBookings = $record->bookings()
                            ->whereIn('status', ['pending', 'confirmed', 'ongoing'])
                            ->count();

                        if ($activeBookings > 0) {
                            Notification::make()
                                ->title('Tidak Dapat Dihapus')
                                ->body("Mobil ini masih memiliki {$activeBookings} pemesanan aktif. Selesaikan semua pemesanan terlebih dahulu.")
                                ->danger()
                                ->persistent()
                                ->send();
                            $action->halt();
                        }
                    }),
            ]);
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
