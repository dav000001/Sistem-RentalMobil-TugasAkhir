<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Pengguna';
    protected static ?int $navigationSort = 4;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-users';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label('Nama')
                ->required(),
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('phone')
                ->label('Nomor HP'),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(['active' => 'Aktif', 'suspended' => 'Suspended', 'inactive' => 'Nonaktif'])
                ->required(),
            Forms\Components\Select::make('account_type')
                ->label('Tipe Akun')
                ->options([
                    'vendor'   => 'Vendor (pemilik mobil)',
                    'customer' => 'Customer (penyewa)',
                ])
                ->required()
                ->visibleOn('create')
                ->helperText('Pilih tipe akun yang akan dibuat.'),
            Forms\Components\TextInput::make('password')
                ->label('Password')
                ->password()
                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create')
                ->minLength(8)
                ->hint(fn (string $operation) => $operation === 'edit' ? 'Kosongkan jika tidak ingin mengubah password' : null),
            Forms\Components\TextInput::make('password_confirmation')
                ->label('Konfirmasi Password')
                ->password()
                ->same('password')
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(false),

            // ── Dokumen Verifikasi — tampil sesuai role ──────────────
            \Filament\Schemas\Components\Section::make('📋 Dokumen Verifikasi')
                ->description(fn ($record) => $record?->role === 'vendor'
                    ? 'Dokumen bisnis yang diupload vendor untuk verifikasi.'
                    : 'Foto dokumen yang diupload customer untuk verifikasi identitas.')
                ->components([
                    Forms\Components\Placeholder::make('documents_display')
                        ->label('')
                        ->columnSpanFull()
                        ->content(function ($record) {
                            if (!$record) return '—';

                            // ── VENDOR ────────────────────────────────
                            if ($record->role === 'vendor') {
                                $vendor = $record->vendor;
                                if (!$vendor) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-400 text-sm">Data vendor tidak ditemukan.</p>');
                                }

                                $status = $vendor->status instanceof \App\Enums\VendorStatus
                                    ? $vendor->status->value : $vendor->status;

                                $statusBadge = match($status) {
                                    'approved'       => '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">✅ Disetujui</span>',
                                    'pending'        => '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">⏳ Menunggu Review</span>',
                                    'needs_revision' => '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">✏️ Perlu Revisi</span>',
                                    'rejected'       => '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">❌ Ditolak</span>',
                                    'suspended'      => '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">🔒 Dibekukan</span>',
                                    default          => '<span class="text-gray-400">' . $status . '</span>',
                                };

                                // Baca dari tabel vendor_documents (sistem baru)
                                $docsByType = $vendor->documents->keyBy('type');

                                $docTypes = [
                                    'ktp'    => 'KTP Pemilik',
                                    'siup'   => 'SIUP / NIB',
                                    'npwp'   => 'NPWP',
                                    'selfie' => 'Selfie + KTP',
                                ];

                                $html = '<div class="mb-3"><strong>Status:</strong> ' . $statusBadge . '</div>';
                                $html .= '<div class="grid grid-cols-1 sm:grid-cols-4 gap-4">';
                                foreach ($docTypes as $type => $label) {
                                    $doc = $docsByType->get($type);
                                    $html .= '<div><p class="text-xs font-semibold text-gray-600 mb-1">' . $label . '</p>';
                                    if ($doc) {
                                        $previewUrl  = route('admin.vendor-documents.preview', $doc->id);
                                        $downloadUrl = route('admin.vendor-documents.download', $doc->id);
                                        $docStatusBadge = match($doc->status) {
                                            'approved' => '<span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">✅ Disetujui</span>',
                                            'pending'  => '<span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">⏳ Pending</span>',
                                            'rejected' => '<span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">❌ Ditolak</span>',
                                            default    => '',
                                        };
                                        // Cek apakah file gambar atau PDF
                                        $isImage = in_array($doc->mime, ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']);
                                        if ($isImage) {
                                            $html .= '<a href="' . $previewUrl . '" target="_blank">'
                                                . '<img src="' . $previewUrl . '" class="max-h-40 rounded-lg border border-gray-200 hover:opacity-90 transition" alt="' . e($label) . '">'
                                                . '</a>';
                                        } else {
                                            $html .= '<a href="' . $downloadUrl . '" target="_blank" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline">📄 ' . e($doc->original_name ?? 'Lihat dokumen') . '</a>';
                                        }
                                        $html .= $docStatusBadge;
                                        $html .= '<br><a href="' . $previewUrl . '" target="_blank" class="text-xs text-blue-500 hover:underline mt-1 inline-block">🔍 Klik untuk perbesar</a>';
                                    } else {
                                        $html .= '<span class="text-gray-400 text-sm">Belum diupload</span>';
                                    }
                                    $html .= '</div>';
                                }
                                $html .= '</div>';
                                $html .= '<div class="mt-3"><a href="' . route('filament.admin.resources.vendors.view', $vendor->id) . '" class="text-blue-600 hover:underline text-sm font-medium">🔗 Buka halaman lengkap vendor →</a></div>';
                                return new \Illuminate\Support\HtmlString($html);
                            }

                            // ── CUSTOMER ──────────────────────────────
                            $customer = $record->customer;
                            if (!$customer) {
                                return new \Illuminate\Support\HtmlString('<p class="text-gray-400 text-sm">Data customer tidak ditemukan.</p>');
                            }

                            $statusBadge = match($customer->verification_status) {
                                'verified' => '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">✅ Terverifikasi</span>',
                                'pending'  => '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">⏳ Menunggu Review</span>',
                                'rejected' => '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">❌ Ditolak</span>',
                                default    => '<span class="text-gray-400">—</span>',
                            };

                            $docs = [
                                ['label' => 'Foto KTP',    'url' => $customer->ktp_url],
                                ['label' => 'Foto SIM',    'url' => $customer->sim_url],
                                ['label' => 'Selfie + KTP','url' => $customer->selfie_url],
                            ];

                            $html = '<div class="mb-3"><strong>Status:</strong> ' . $statusBadge . '</div>';

                            if ($customer->verification_status === 'rejected' && $customer->rejection_reason) {
                                $html .= '<div class="mb-3 bg-red-50 border border-red-200 rounded p-2 text-sm text-red-700">⚠️ Alasan penolakan: ' . e($customer->rejection_reason) . '</div>';
                            }

                            $html .= '<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">';
                            foreach ($docs as $doc) {
                                $html .= '<div><p class="text-xs font-semibold text-gray-600 mb-1">' . $doc['label'] . '</p>';
                                if ($doc['url']) {
                                    $src = str_starts_with($doc['url'], 'http') ? $doc['url'] : asset('storage/' . ltrim($doc['url'], '/'));
                                    $html .= '<a href="' . $src . '" target="_blank">'
                                        . '<img src="' . $src . '" class="max-h-40 rounded-lg border border-gray-200 hover:opacity-90 transition" alt="' . $doc['label'] . '">'
                                        . '<p class="text-xs text-blue-600 mt-1">🔍 Klik untuk perbesar</p>'
                                        . '</a>';
                                } else {
                                    $html .= '<span class="text-gray-400 text-sm">Belum diupload</span>';
                                }
                                $html .= '</div>';
                            }
                            $html .= '</div>';
                            return new \Illuminate\Support\HtmlString($html);
                        }),
                ])
                ->visible(fn ($record) => in_array($record?->role, ['vendor', 'customer']))
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone'),

                // Kolom Role — dihitung dari relasi vendor/customer
                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->state(function (User $record): string {
                        if ($record->vendor) return 'vendor';
                        if ($record->customer) return 'customer';
                        return 'admin';
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'vendor'   => '🏢 Vendor',
                        'customer' => '👤 Customer',
                        'admin'    => '🔑 Admin',
                        default    => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'vendor'   => 'warning',
                        'customer' => 'info',
                        'admin'    => 'danger',
                        default    => 'gray',
                    })
                    ->description(function (User $record): ?string {
                        if ($record->vendor) {
                            $status = $record->vendor->status instanceof \App\Enums\VendorStatus
                                ? $record->vendor->status->value
                                : $record->vendor->status;
                            return $record->vendor->business_name . ' · ' . match($status) {
                                'approved'       => 'Aktif',
                                'pending'        => 'Menunggu',
                                'rejected'       => 'Ditolak',
                                'suspended'      => 'Dibekukan',
                                'needs_revision' => 'Perlu Revisi',
                                default          => $status,
                            };
                        }
                        if ($record->customer) {
                            return match($record->customer->verification_status) {
                                'verified' => 'Terverifikasi',
                                'pending'  => 'Belum Verifikasi',
                                'rejected' => 'Verifikasi Ditolak',
                                default    => null,
                            };
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Akun')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'suspended'=> 'danger',
                        default    => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active'    => 'Aktif',
                        'suspended' => 'Suspended',
                        'inactive'  => 'Nonaktif',
                        default     => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Bergabung')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['active' => 'Aktif', 'suspended' => 'Suspended']),
                Tables\Filters\Filter::make('vendor')
                    ->label('Hanya Vendor')
                    ->query(fn ($query) => $query->whereHas('vendor')),
                Tables\Filters\Filter::make('customer')
                    ->label('Hanya Customer')
                    ->query(fn ($query) => $query->whereHas('customer')),
                Tables\Filters\Filter::make('admin')
                    ->label('Hanya Admin')
                    ->query(fn ($query) => $query->whereDoesntHave('vendor')->whereDoesntHave('customer')),
            ])
            ->recordUrl(fn (User $record) => static::getUrl('edit', ['record' => $record]))
            ->actions([
                \Filament\Actions\EditAction::make()->label('Edit'),
                \Filament\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Akun Pengguna')
                    ->modalDescription(fn (User $record) =>
                        'Yakin ingin menghapus akun ' . $record->name . ' (' . $record->email . ')? '
                        . 'Semua data terkait (vendor/customer, mobil, pemesanan) akan ikut terhapus permanen.'
                    )
                    ->modalSubmitActionLabel('Ya, Hapus Permanen')
                    ->before(function (User $record, $action) {
                        if ($record->id === auth('admin')->id()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Tidak Dapat Dihapus')
                                ->body('Anda tidak bisa menghapus akun admin yang sedang login.')
                                ->danger()
                                ->send();
                            $action->halt();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
