<?php

namespace App\Filament\Admin\Resources;

use App\Models\VendorSupportTicket;
use App\Notifications\VendorSupportReplyNotification;
use Filament\Actions\Action as FilamentAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class VendorSupportTicketResource extends Resource
{
    protected static ?string $model = VendorSupportTicket::class;
    protected static ?string $navigationLabel = 'Pesan Vendor';
    protected static ?int    $navigationSort  = 13;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chat-bubble-bottom-center-text';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = VendorSupportTicket::where('status', 'open')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Pesan dari Vendor')
                ->schema([
                    Forms\Components\TextInput::make('vendor.business_name')
                        ->label('Nama Vendor')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($record) => $record?->vendor?->business_name ?? '—'),
                    Forms\Components\TextInput::make('vendor.user.email')
                        ->label('Email Vendor')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($record) => $record?->vendor?->user?->email ?? '—'),
                    Forms\Components\TextInput::make('subject')
                        ->label('Topik')
                        ->disabled(),
                    Forms\Components\Textarea::make('message')
                        ->label('Isi Pesan')
                        ->disabled()
                        ->rows(5)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Balasan Admin')
                ->schema([
                    Forms\Components\Textarea::make('admin_reply')
                        ->label('Balasan Admin')
                        ->rows(5)
                        ->columnSpanFull()
                        ->placeholder('Tulis balasan untuk vendor...'),
                    Forms\Components\Select::make('status')
                        ->label('Status Tiket')
                        ->options([
                            'open'    => 'Terbuka',
                            'replied' => 'Sudah Dibalas',
                            'closed'  => 'Ditutup',
                        ]),
                ]),
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
                Tables\Columns\TextColumn::make('subject')
                    ->label('Topik')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('message')
                    ->label('Pesan')
                    ->limit(40),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open'    => 'warning',
                        'replied' => 'success',
                        'closed'  => 'gray',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'open'    => 'Terbuka',
                        'replied' => 'Dibalas',
                        'closed'  => 'Ditutup',
                        default   => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dikirim')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('replied_at')
                    ->label('Dibalas')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open'    => 'Terbuka (Belum Dibalas)',
                        'replied' => 'Sudah Dibalas',
                        'closed'  => 'Ditutup',
                    ]),
            ])
            ->actions([
                // ── Tombol Balas ────────────────────────────────────
                FilamentAction::make('reply')
                    ->label('Balas')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->visible(fn (VendorSupportTicket $record) => $record->status === 'open')
                    ->modalHeading(fn (VendorSupportTicket $record) => '💬 Balas: ' . $record->subject)
                    ->modalDescription(fn (VendorSupportTicket $record) =>
                        "Dari: {$record->vendor?->business_name}\n\n" .
                        "Pesan:\n{$record->message}"
                    )
                    ->form([
                        Forms\Components\Textarea::make('admin_reply')
                            ->label('Balasan Anda')
                            ->required()
                            ->minLength(5)
                            ->rows(5)
                            ->placeholder('Tulis balasan untuk vendor...'),
                    ])
                    ->action(function (VendorSupportTicket $record, array $data) {
                        $record->update([
                            'admin_reply' => $data['admin_reply'],
                            'status'      => 'replied',
                            'replied_by'  => auth('admin')->id() ?? auth()->id(),
                            'replied_at'  => now(),
                        ]);

                        // Kirim notifikasi lonceng ke vendor
                        try {
                            $record->vendor?->user?->notify(
                                new VendorSupportReplyNotification($record)
                            );
                        } catch (\Throwable) {}

                        Notification::make()
                            ->title('Balasan berhasil dikirim ke vendor')
                            ->success()
                            ->send();
                    }),

                // ── Tombol Lihat Detail ──────────────────────────
                FilamentAction::make('view_detail')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (VendorSupportTicket $record) => '📋 Detail Tiket: ' . $record->subject)
                    ->modalContent(fn (VendorSupportTicket $record) => view(
                        'filament.admin.support-ticket-detail',
                        ['ticket' => $record]
                    ))
                    ->modalSubmitAction(false),

                // ── Tombol Tutup Tiket ──────────────────────────
                FilamentAction::make('close')
                    ->label('Tutup')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (VendorSupportTicket $record) => $record->status !== 'closed')
                    ->requiresConfirmation()
                    ->modalHeading('Tutup Tiket Support?')
                    ->modalDescription('Tiket ini akan ditandai sebagai selesai dan ditutup.')
                    ->action(function (VendorSupportTicket $record) {
                        $record->update(['status' => 'closed']);

                        Notification::make()
                            ->title('Tiket berhasil ditutup')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\VendorSupportTicketResource\Pages\ListVendorSupportTickets::route('/'),
            'edit'  => \App\Filament\Admin\Resources\VendorSupportTicketResource\Pages\EditVendorSupportTicket::route('/{record}/edit'),
        ];
    }
}
