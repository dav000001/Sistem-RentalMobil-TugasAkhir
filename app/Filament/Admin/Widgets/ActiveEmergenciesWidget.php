<?php

namespace App\Filament\Admin\Widgets;

use App\Models\EmergencyReport;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ActiveEmergenciesWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected ?string $pollingInterval = '15s';
    protected int | string | array $columnSpan = 'full';

    public function getHeading(): string
    {
        $count = EmergencyReport::whereIn('status', ['active', 'responding'])->count();

        if ($count === 0) {
            return '✅ Laporan Emergency Aktif';
        }
        return "🚨 Laporan Emergency Aktif ({$count} butuh penanganan)";
    }

    public function getDescription(): ?string
    {
        $active     = EmergencyReport::where('status', 'active')->count();
        $responding = EmergencyReport::where('status', 'responding')->count();

        if ($active === 0 && $responding === 0) {
            return 'Tidak ada emergency aktif. Auto-refresh setiap 15 detik.';
        }

        $parts = [];
        if ($active > 0)     $parts[] = "{$active} menunggu respon";
        if ($responding > 0) $parts[] = "{$responding} sedang ditangani";

        return implode(' • ', $parts) . ' • Auto-refresh setiap 15 detik';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                EmergencyReport::query()
                    ->whereIn('status', ['active', 'responding'])
                    ->with(['booking.car', 'customer.user', 'vendor'])
                    ->orderByRaw("CASE
                        WHEN type IN ('medical','accident') THEN 1
                        WHEN type IN ('theft','harassment') THEN 2
                        WHEN type = 'breakdown' THEN 3
                        ELSE 4
                    END")
                    ->orderBy('reported_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->getStateUsing(fn (EmergencyReport $r) => $r->generateEmergencyCode())
                    ->badge()
                    ->color('danger')
                    ->copyable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->getStateUsing(fn (EmergencyReport $r) => $r->getTypeLabel())
                    ->badge()
                    ->color(fn (EmergencyReport $r) => match ($r->getPriorityLevel()) {
                        'critical' => 'danger',
                        'high'     => 'warning',
                        'medium'   => 'info',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn (EmergencyReport $r) => $r->getStatusLabel())
                    ->badge()
                    ->color(fn (EmergencyReport $r) => $r->getStatusColor()),

                Tables\Columns\TextColumn::make('customer.user.name')
                    ->label('Customer')
                    ->limit(20)
                    ->searchable(),

                Tables\Columns\TextColumn::make('vehicle')
                    ->label('Kendaraan')
                    ->getStateUsing(fn (EmergencyReport $r) =>
                        trim(
                            ($r->booking->car->brand ?? '') . ' ' .
                            ($r->booking->car->model ?? '') . ' ' .
                            ($r->booking->car->license_plate ? "({$r->booking->car->license_plate})" : '')
                        )
                    )
                    ->limit(25),

                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->getStateUsing(fn (EmergencyReport $r) =>
                        $r->latitude ? "📍 {$r->latitude}, {$r->longitude}" : '—'
                    )
                    ->url(fn (EmergencyReport $r) => $r->latitude ? $r->getMapUrl() : null, shouldOpenInNewTab: true)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('reported_at')
                    ->label('Dilaporkan')
                    ->since()
                    ->color(fn (EmergencyReport $r) =>
                        $r->reported_at->diffInMinutes() > 30 ? 'danger' : 'warning'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('elapsed')
                    ->label('Waktu Tunggu')
                    ->getStateUsing(function (EmergencyReport $r) {
                        $mins = $r->reported_at->diffInMinutes();
                        return $r->status === 'responding'
                            ? '⏱️ Ditangani'
                            : "{$mins}m (menunggu)";
                    })
                    ->color(fn (EmergencyReport $r) =>
                        $r->status === 'active' && $r->reported_at->diffInMinutes() > 15
                            ? 'danger' : 'warning'
                    ),
            ])
            ->actions([
                Action::make('respond')
                    ->label('🚑 Tangani')
                    ->color('success')
                    ->visible(fn (EmergencyReport $r) => $r->status === 'active')
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Sedang Ditangani')
                    ->modalDescription('Status emergency akan diubah menjadi "responding".')
                    ->action(function (EmergencyReport $record) {
                        $record->update(['status' => 'responding']);

                        try {
                            $record->customer?->user?->notify(
                                new \App\Notifications\EmergencyStatusUpdateNotification($record, 'active')
                            );
                        } catch (\Throwable $e) {
                            \Log::warning('Failed to notify customer on emergency respond', ['id' => $record->id]);
                        }

                        Notification::make()
                            ->success()
                            ->title('Emergency sedang ditangani')
                            ->send();
                    }),

                Action::make('resolve')
                    ->label('✅ Selesai')
                    ->color('primary')
                    ->visible(fn (EmergencyReport $r) => in_array($r->status, ['active', 'responding']))
                    ->form([
                        \Filament\Forms\Components\Textarea::make('resolution')
                            ->label('Detail Penyelesaian')
                            ->required()
                            ->placeholder('Jelaskan cara emergency diselesaikan...')
                            ->rows(3),
                    ])
                    ->action(function (EmergencyReport $record, array $data) {
                        $oldStatus = $record->status;

                        $record->update([
                            'status'      => 'resolved',
                            'resolution'  => $data['resolution'],
                            'resolved_at' => now(),
                        ]);

                        try {
                            $record->customer?->user?->notify(
                                new \App\Notifications\EmergencyStatusUpdateNotification($record, $oldStatus)
                            );
                        } catch (\Throwable $e) {
                            \Log::warning('Failed to notify customer on emergency resolve', ['id' => $record->id]);
                        }

                        Notification::make()
                            ->success()
                            ->title('Emergency berhasil diselesaikan')
                            ->send();
                    }),

                Action::make('view_booking')
                    ->label('📋 Booking')
                    ->color('info')
                    ->icon('heroicon-o-eye')
                    ->url(fn (EmergencyReport $r) =>
                        route('filament.admin.resources.bookings.view', $r->booking)
                    )
                    ->openUrlInNewTab(),

                Action::make('call_customer')
                    ->label('📞 Telepon')
                    ->color('warning')
                    ->icon('heroicon-o-phone')
                    ->visible(fn (EmergencyReport $r) => !empty($r->customer?->user?->phone))
                    ->url(fn (EmergencyReport $r) => "tel:{$r->customer->user->phone}"),
            ])
            ->emptyStateHeading('🎉 Tidak Ada Emergency Aktif')
            ->emptyStateDescription('Semua emergency telah diselesaikan atau belum ada laporan.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->striped();
    }

    public static function canView(): bool
    {
        $user = auth('admin')->user() ?? auth()->user();
        return $user?->isAdmin() ?? false;
    }
}
