<?php

namespace App\Filament\Admin\Resources;

use App\Models\ContactMessage;
use App\Notifications\AdminReplyNotification;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;
    protected static ?string $navigationLabel = 'Pesan Customer';
    protected static ?int    $navigationSort  = 12;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-envelope';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::whereNull('admin_reply')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('sender_name')->label('Nama')->disabled(),
            Forms\Components\TextInput::make('sender_email')->label('Email')->disabled(),
            Forms\Components\TextInput::make('subject')->label('Subjek')->disabled(),
            Forms\Components\Textarea::make('message')->label('Pesan')->disabled()->rows(4)->columnSpanFull(),
            Forms\Components\Textarea::make('admin_reply')->label('Balasan Admin')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sender_name')->label('Nama')->searchable(),
                Tables\Columns\TextColumn::make('sender_email')->label('Email')->searchable(),
                Tables\Columns\TextColumn::make('subject')->label('Subjek')->limit(40),
                Tables\Columns\TextColumn::make('message')->label('Pesan')->limit(50),
                Tables\Columns\IconColumn::make('admin_reply')
                    ->label('Dibalas')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->getStateUsing(fn ($record) => !is_null($record->admin_reply)),
                Tables\Columns\TextColumn::make('created_at')->label('Dikirim')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('belum_dibalas')
                    ->label('Belum Dibalas')
                    ->query(fn ($query) => $query->whereNull('admin_reply')),
            ])
            ->actions([
                FilamentAction::make('reply')
                    ->label('Balas')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->visible(fn (ContactMessage $record) => is_null($record->admin_reply))
                    ->form([
                        Forms\Components\Textarea::make('admin_reply')
                            ->label('Balasan Anda')
                            ->required()
                            ->minLength(5)
                            ->rows(4)
                            ->placeholder('Tulis balasan untuk customer...'),
                    ])
                    ->action(function (ContactMessage $record, array $data) {
                        $record->update([
                            'admin_reply' => $data['admin_reply'],
                            'replied_by'  => auth('admin')->id() ?? auth()->id(),
                            'replied_at'  => now(),
                        ]);

                        // Kirim notifikasi ke customer jika dia login user
                        if ($record->user_id && $record->user) {
                            try {
                                $record->user->notify(new AdminReplyNotification($record));
                            } catch (\Throwable) {}
                        }

                        Notification::make()->title('Balasan berhasil dikirim ke customer')->success()->send();
                    }),

                EditAction::make()->label('Edit'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\ContactMessageResource\Pages\ListContactMessages::route('/'),
            'edit'  => \App\Filament\Admin\Resources\ContactMessageResource\Pages\EditContactMessage::route('/{record}/edit'),
        ];
    }
}
