<?php

namespace App\Filament\Admin\Resources;

use App\Enums\VendorPlan;
use App\Filament\Admin\Resources\VendorPlanPaymentResource\Pages;
use App\Models\VendorPlanPayment;
use App\Services\VendorPlanService;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup as FilamentBulkActionGroup;
use Filament\Actions\DeleteBulkAction as FilamentDeleteBulkAction;
use Filament\Actions\EditAction as FilamentEditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class VendorPlanPaymentResource extends Resource
{
    protected static ?string $model = VendorPlanPayment::class;
    protected static ?string $navigationLabel = 'Tagihan Lama (Legacy)';
    protected static ?int $navigationSort = 7;
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return 'Keuangan';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-archive-box';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = VendorPlanPayment::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('vendor_id')
                ->relationship('vendor', 'business_name')
                ->required()
                ->searchable()
                ->label('Vendor'),

            Forms\Components\Select::make('plan')
                ->options(collect(VendorPlan::cases())
                    ->filter(fn ($p) => $p !== VendorPlan::Free)
                    ->mapWithKeys(fn ($p) => [$p->value => $p->label() . ' (Rp ' . number_format($p->monthlyFee(), 0, ',', '.') . '/bln)']))
                ->required()
                ->label('Paket'),

            Forms\Components\TextInput::make('amount')
                ->numeric()
                ->required()
                ->prefix('Rp')
                ->label('Nominal'),

            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'Menunggu Pembayaran',
                    'paid'    => 'Lunas',
                    'failed'  => 'Gagal',
                    'waived'  => 'Dibebaskan',
                ])
                ->required()
                ->label('Status'),

            Forms\Components\Select::make('method')
                ->options([
                    'transfer'         => 'Transfer Bank',
                    'payout_deduction' => 'Potong Payout',
                    'waived'           => 'Dibebaskan Admin',
                ])
                ->label('Metode Pembayaran'),

            Forms\Components\DatePicker::make('period_start')
                ->required()
                ->label('Awal Periode'),

            Forms\Components\DatePicker::make('period_end')
                ->required()
                ->label('Akhir Periode'),

            Forms\Components\TextInput::make('reference')
                ->label('No. Referensi Transfer')
                ->placeholder('Opsional'),

            Forms\Components\Textarea::make('notes')
                ->label('Catatan')
                ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('plan')
                    ->label('Paket')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof VendorPlan ? $state->label() : VendorPlan::tryFrom($state)?->label() ?? $state)
                    ->color(fn ($state): string => match (($state instanceof VendorPlan ? $state->value : $state)) {
                        'basic'   => 'info',
                        'premium' => 'warning',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Menunggu',
                        'paid'    => 'Lunas',
                        'failed'  => 'Gagal',
                        'waived'  => 'Dibebaskan',
                        default   => ucfirst($state),
                    })
                    ->color(fn ($state): string => match ($state) {
                        'pending' => 'warning',
                        'paid'    => 'success',
                        'failed'  => 'danger',
                        'waived'  => 'gray',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('method')
                    ->label('Metode')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'manual'           => 'Transfer Bank',
                        'payout_deduction' => 'Potong Payout',
                        'transfer'         => 'Transfer Bank',
                        'waived'           => 'Dibebaskan',
                        default            => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('period_start')
                    ->label('Periode')
                    ->formatStateUsing(fn ($state, $record) =>
                        \Carbon\Carbon::parse($state)->format('d M Y') . ' – ' .
                        \Carbon\Carbon::parse($record->period_end)->format('d M Y')
                    ),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Dibayar')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('confirmedBy.name')
                    ->label('Dikonfirmasi')
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'paid'    => 'Lunas',
                        'failed'  => 'Gagal',
                        'waived'  => 'Dibebaskan',
                    ]),
                Tables\Filters\SelectFilter::make('plan')
                    ->options([
                        'basic'   => 'Basic',
                        'premium' => 'Premium',
                    ]),
            ])
            ->actions([
                // Konfirmasi Lunas
                FilamentAction::make('confirm_paid')
                    ->label('Konfirmasi Lunas')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (VendorPlanPayment $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Select::make('method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'transfer'         => 'Transfer Bank',
                                'payout_deduction' => 'Potong Payout',
                            ])
                            ->required()
                            ->default('transfer'),
                        Forms\Components\TextInput::make('reference')
                            ->label('No. Referensi Transfer')
                            ->placeholder('Opsional — nomor transaksi dari m-banking/teller'),
                    ])
                    ->action(function (VendorPlanPayment $record, array $data) {
                        app(VendorPlanService::class)->confirmPayment(
                            $record,
                            auth()->id(),
                            $data['method'],
                            $data['reference'] ?? null
                        );
                        Notification::make()->title('Pembayaran dikonfirmasi')->success()->send();
                    }),

                // Potong Payout
                FilamentAction::make('deduct_payout')
                    ->label('Potong Payout')
                    ->icon('heroicon-o-minus-circle')
                    ->color('info')
                    ->visible(fn (VendorPlanPayment $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Potong dari Payout?')
                    ->modalDescription('Biaya paket akan dipotong dari payout vendor berikutnya.')
                    ->action(function (VendorPlanPayment $record) {
                        app(VendorPlanService::class)->deductFromPayout($record, auth()->id());
                        Notification::make()->title('Akan dipotong dari payout')->success()->send();
                    }),

                // Bebaskan Tagihan
                FilamentAction::make('waive')
                    ->label('Bebaskan')
                    ->icon('heroicon-o-gift')
                    ->color('gray')
                    ->visible(fn (VendorPlanPayment $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Pembebasan')
                            ->required(),
                    ])
                    ->action(function (VendorPlanPayment $record, array $data) {
                        app(VendorPlanService::class)->waivePayment($record, auth()->id(), $data['reason']);
                        Notification::make()->title('Tagihan dibebaskan')->success()->send();
                    }),

                FilamentEditAction::make()->label('Edit'),
            ])
            ->bulkActions([
                FilamentBulkActionGroup::make([
                    FilamentDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVendorPlanPayments::route('/'),
            'create' => Pages\CreateVendorPlanPayment::route('/create'),
            'edit'   => Pages\EditVendorPlanPayment::route('/{record}/edit'),
        ];
    }
}
