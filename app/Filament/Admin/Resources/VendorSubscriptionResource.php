<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\VendorSubscriptionResource\Pages;
use App\Models\VendorSubscription;
use App\Services\VendorSubscriptionService;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\EditAction as FilamentEditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class VendorSubscriptionResource extends Resource
{
    protected static ?string $model = VendorSubscription::class;
    protected static ?string $navigationLabel = 'Tagihan Paket';
    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return 'Keuangan';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-credit-card';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = VendorSubscription::where('status', 'pending_payment')
            ->whereNotNull('payment_proof')
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
            \Filament\Schemas\Components\Section::make('Informasi Langganan')
                ->columns(2)
                ->components([
                    Forms\Components\Placeholder::make('vendor_name')
                        ->label('Vendor')
                        ->content(fn ($record) => $record?->vendor?->business_name ?? '—'),

                    Forms\Components\Placeholder::make('package_name')
                        ->label('Paket')
                        ->content(fn ($record) => $record?->package?->name ?? '—'),

                    Forms\Components\Placeholder::make('status')
                        ->label('Status')
                        ->content(fn ($record) => $record?->statusLabel() ?? '—'),

                    Forms\Components\Placeholder::make('started_at')
                        ->label('Tanggal Mulai')
                        ->content(fn ($record) => $record?->started_at ? $record->started_at->format('d M Y H:i') : '—'),

                    Forms\Components\Placeholder::make('expires_at')
                        ->label('Tanggal Kedaluwarsa')
                        ->content(fn ($record) => $record?->expires_at ? $record->expires_at->format('d M Y H:i') : '—'),

                    Forms\Components\Placeholder::make('grace_until')
                        ->label('Batas Grace Period')
                        ->content(fn ($record) => $record?->grace_until ? $record->grace_until->format('d M Y H:i') : '—'),
                ]),

            \Filament\Schemas\Components\Section::make('Rincian Pembayaran')
                ->columns(2)
                ->components([
                    Forms\Components\Placeholder::make('price')
                        ->label('Harga Paket')
                        ->content(fn ($record) => $record?->package ? 'Rp ' . number_format($record->package->price_per_month, 0, ',', '.') : '—'),

                    Forms\Components\Placeholder::make('proration_credit')
                        ->label('Kredit Proration')
                        ->content(fn ($record) => $record ? 'Rp ' . number_format($record->proration_credit, 0, ',', '.') : '—'),

                    Forms\Components\Placeholder::make('amount_paid')
                        ->label('Nominal Yang Dibayar')
                        ->content(fn ($record) => $record ? 'Rp ' . number_format($record->amount_paid, 0, ',', '.') : '—'),

                    Forms\Components\Placeholder::make('payment_method')
                        ->label('Metode Pembayaran')
                        ->content(fn ($record) => $record?->payment_method ?? '—'),

                    Forms\Components\Placeholder::make('transfer_ref')
                        ->label('Referensi Pengirim / Transfer')
                        ->content(fn ($record) => $record?->transfer_ref ?? '—'),

                    Forms\Components\Placeholder::make('payment_reference')
                        ->label('Nomor Referensi Sistem (Mendatang)')
                        ->content(fn ($record) => $record?->payment_reference ?? '—'),
                ]),

            \Filament\Schemas\Components\Section::make('Bukti Transfer Pembayaran')
                ->components([
                    Forms\Components\Placeholder::make('payment_proof_display')
                        ->label('')
                        ->content(fn ($record) => $record?->payment_proof
                            ? view('filament.components.image-preview', ['src' => asset('storage/' . $record->payment_proof)])
                            : '<p class="text-gray-400 text-sm italic">Belum ada bukti transfer diunggah.</p>'
                        )->html(),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vendor.business_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('package.name')
                    ->label('Paket')
                    ->badge()
                    ->color(fn ($record) => match($record->package?->code) {
                        'premium' => 'warning',
                        'basic' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->statusLabel())
                    ->color(fn ($record) => $record->statusColor()),

                Tables\Columns\TextColumn::make('to_be_paid')
                    ->label('Tagihan')
                    ->getStateUsing(fn ($record) => $record->package ? ($record->package->price_per_month - $record->proration_credit) : 0)
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.')),

                Tables\Columns\TextColumn::make('transfer_ref')
                    ->label('Ref / Pengirim')
                    ->placeholder('—'),

                Tables\Columns\ImageColumn::make('payment_proof')
                    ->label('Bukti')
                    ->disk('public')
                    ->height(40)
                    ->placeholder('Belum ada'),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d M Y')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Kedaluwarsa')
                    ->dateTime('d M Y')
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending_payment' => 'Menunggu Pembayaran',
                        'active' => 'Aktif',
                        'grace_period' => 'Grace Period',
                        'expired_locked' => 'Expired',
                        'cancelled_admin' => 'Dibatalkan Admin',
                    ]),
                Tables\Filters\Filter::make('has_proof')
                    ->label('Bukti Diupload')
                    ->query(fn ($query) => $query->whereNotNull('payment_proof')),
            ])
            ->actions([
                // Konfirmasi Pembayaran Lunas
                FilamentAction::make('confirm_paid')
                    ->label('Konfirmasi Lunas')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (VendorSubscription $record) => $record->status === 'pending_payment')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pembayaran Paket?')
                    ->modalDescription(fn (VendorSubscription $record) => 'Konfirmasi pembayaran paket ' . $record->package->name . ' sebesar Rp ' . number_format($record->package->price_per_month - $record->proration_credit, 0, ',', '.') . ' untuk vendor ' . $record->vendor->business_name . '. Paket akan langsung aktif selama 30 hari.')
                    ->action(function (VendorSubscription $record) {
                        $amount = $record->package->price_per_month - $record->proration_credit;
                        app(VendorSubscriptionService::class)->activate($record, [
                            'amount' => $amount,
                            'reference' => $record->transfer_ref ?? 'MANUAL-' . strtoupper(uniqid()),
                        ]);
                        Notification::make()->title('Pembayaran paket berhasil dikonfirmasi')->success()->send();
                    }),

                // Batalkan Paket
                FilamentAction::make('cancel_subscription')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (VendorSubscription $record) => in_array($record->status, ['active', 'grace_period']))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (VendorSubscription $record, array $data) {
                        app(VendorSubscriptionService::class)->adminCancel($record, auth()->user(), $data['reason']);
                        Notification::make()->title('Langganan vendor berhasil dibatalkan')->warning()->send();
                    }),

                FilamentEditAction::make()->label('Detail'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorSubscriptions::route('/'),
            'edit' => Pages\EditVendorSubscription::route('/{record}/edit'),
        ];
    }
}
