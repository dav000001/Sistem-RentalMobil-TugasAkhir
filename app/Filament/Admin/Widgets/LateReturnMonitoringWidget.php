<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\LateReturnReport;
use App\Services\AutoLateDetectionService;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class LateReturnMonitoringWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected ?string $pollingInterval = '60s'; // Refresh every minute
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Booking')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('customer.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('car_info')
                    ->label('Kendaraan')
                    ->getStateUsing(fn (Booking $record) => 
                        "{$record->car->brand} {$record->car->model} ({$record->car->license_plate})"
                    )
                    ->limit(25),

                Tables\Columns\TextColumn::make('end_at')
                    ->label('Batas Waktu')
                    ->dateTime('d M Y H:i')
                    ->color(fn (Booking $record) => 
                        $record->end_at->isPast() ? 'danger' : 
                        ($record->end_at->diffInHours() <= 2 ? 'warning' : 'success')
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('time_status')
                    ->label('Tingkat Risiko')
                    ->getStateUsing(function (Booking $record) {
                        $now = now();
                        if ($record->end_at->isPast()) {
                            $hoursLate = $record->end_at->diffInHours($now);
                            return "🚨 TERLAMBAT ({$hoursLate} jam)";
                        } else {
                            $hoursLeft = $now->diffInHours($record->end_at);
                            if ($hoursLeft <= 1) {
                                return "⚠️ KETERLAMBATAN TINGGI (sisa {$hoursLeft} jam)";
                            } else {
                                return "✅ TEPAT WAKTU (sisa {$hoursLeft} jam)";
                            }
                        }
                    })
                    ->badge()
                    ->color(fn (Booking $record) => 
                        $record->end_at->isPast() ? 'danger' : 
                        ($record->end_at->diffInHours() <= 2 ? 'warning' : 'success')
                    ),

                Tables\Columns\TextColumn::make('detection_status')
                    ->label('Laporan Terlambat')
                    ->getStateUsing(function (Booking $record) {
                        $autoReport = $record->lateReturnReport()
                            ->where('reporter_type', 'system')
                            ->latest()
                            ->first();
                        
                        if (!$autoReport) {
                            return '⏳ Memantau';
                        }
                        
                        $data = $autoReport->auto_detection_data ? 
                            json_decode($autoReport->auto_detection_data, true) : [];
                        
                        $confidence = $data['confidence'] ?? 0;
                        
                        return "🤖 Terdeteksi ({$confidence}%)";
                    })
                    ->badge()
                    ->color(function (Booking $record) {
                        $autoReport = $record->lateReturnReport()
                            ->where('reporter_type', 'system')
                            ->latest()
                            ->first();
                        
                        if (!$autoReport) return 'gray';
                        
                        $data = $autoReport->auto_detection_data ? 
                            json_decode($autoReport->auto_detection_data, true) : [];
                        $severity = $data['severity'] ?? 'unknown';
                        
                        return match($severity) {
                            'critical' => 'danger',
                            'warning' => 'warning',
                            default => 'info'
                        };
                    }),

                Tables\Columns\TextColumn::make('last_gps_update')
                    ->label('GPS Terakhir')
                    ->getStateUsing(function (Booking $record) {
                        $lastTrackingPoint = $record->trackingPoints()->latest('recorded_at')->first();
                        
                        if (!$lastTrackingPoint) {
                            return '📍 Tanpa data GPS';
                        }
                        
                        $minutes = $lastTrackingPoint->recorded_at->diffInMinutes();
                        if ($minutes < 60) {
                            return "📍 {$minutes}m yang lalu";
                        } else {
                            $hours = round($minutes / 60, 1);
                            return "📍 {$hours} jam yang lalu";
                        }
                    })
                    ->color(function (Booking $record) {
                        $lastPoint = $record->trackingPoints()->latest()->first();
                        if (!$lastPoint) return 'gray';
                        return $lastPoint->recorded_at->diffInMinutes() > 30 ? 'warning' : 'success';
                    }),
            ])
            ->actions([
                Action::make('run_detection')
                    ->label('🔍 Cek Sekarang')
                    ->color('info')
                    ->action(function (Booking $record) {
                        try {
                            $autoLateDetection = app(AutoLateDetectionService::class);
                            $detection = $autoLateDetection->detectLateReturn($record);
                            
                            if ($detection['is_late'] || $detection['will_be_late']) {
                                LateReturnReport::updateOrCreate(
                                    [
                                        'booking_id' => $record->id,
                                        'reporter_type' => 'system'
                                    ],
                                    [
                                        'reported_by_user_id' => 1,
                                        'estimated_late_hours' => $detection['estimated_late_hours'] ?? 0,
                                        'reason' => 'Deteksi manual: ' . implode(', ', $detection['reasons'] ?? []),
                                        'status' => 'reported',
                                        'auto_detection_data' => json_encode($detection)
                                    ]
                                );
                                
                                $this->getSuccessNotification("Deteksi selesai. Potensi keterlambatan terdeteksi dengan akurasi {$detection['confidence']}%.");
                            } else {
                                $this->getSuccessNotification('Deteksi selesai. Tidak ditemukan risiko keterlambatan.');
                            }
                            
                        } catch (\Throwable $e) {
                            \Log::error('Manual late detection failed', [
                                'booking_id' => $record->id,
                                'exception' => $e->getMessage()
                            ]);
                            
                            $this->getDangerNotification('Deteksi gagal: ' . $e->getMessage());
                        }
                    }),

                Action::make('contact_customer')
                    ->label('📞 Kontak')
                    ->color('warning')
                    ->url(fn (Booking $record) => 
                        $record->customer?->user?->phone 
                            ? "tel:{$record->customer->user->phone}" 
                            : null
                    )
                    ->visible(fn (Booking $record) => 
                        !empty($record->customer?->user?->phone)
                    ),

                Action::make('view_booking')
                    ->label('👁️ Lihat Booking')
                    ->color('info')
                    ->url(fn (Booking $record) => 
                        route('filament.admin.resources.bookings.view', $record)
                    )
                    ->openUrlInNewTab(),

                Action::make('view_tracking')
                    ->label('🗺️ Lacak GPS')
                    ->color('primary')
                    ->visible(fn (Booking $record) => 
                        $record->trackingPoints()->exists()
                    )
                    ->url(fn (Booking $record) => 
                        route('filament.admin.resources.bookings.view', [
                            'record' => $record,
                            'activeTab' => 'tracking'
                        ])
                    )
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                Action::make('run_auto_detection')
                    ->label('🤖 Jalankan Deteksi Otomatis')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Jalankan Deteksi Otomatis Keterlambatan')
                    ->modalDescription('Ini akan memindai seluruh penyewaan aktif yang sedang berlangsung untuk mendeteksi risiko keterlambatan.')
                    ->action(function () {
                        try {
                            $autoLateDetection = app(AutoLateDetectionService::class);
                            $results = $autoLateDetection->detectAllLateReturns();
                            
                            $message = "Deteksi selesai! Diperiksa: {$results['checked']}, Terlambat terdeteksi: {$results['late_detected']}, Notifikasi terkirim: {$results['notifications_sent']}";
                            
                            if (!empty($results['errors'])) {
                                $message .= ". Error: " . count($results['errors']);
                            }
                            
                            $this->getSuccessNotification($message);
                            
                        } catch (\Throwable $e) {
                            \Log::error('Auto detection run failed', ['exception' => $e->getMessage()]);
                            $this->getDangerNotification('Deteksi otomatis gagal: ' . $e->getMessage());
                        }
                    }),

                Action::make('view_stats')
                    ->label('📊 Lihat Statistik')
                    ->color('info')
                    ->action(function () {
                        try {
                            $autoLateDetection = app(AutoLateDetectionService::class);
                            $stats = $autoLateDetection->getDetectionStats();
                            
                            $message = "Statistik Deteksi Keterlambatan:\n";
                            $message .= "• Sewa berlangsung: {$stats['total_ongoing_bookings']}\n";
                            $message .= "• Berisiko terlambat: {$stats['potential_late_bookings']}\n";
                            $message .= "• Deteksi otomatis terbaru: {$stats['recent_auto_detections']}\n";
                            $message .= "• Akurasi deteksi: {$stats['detection_accuracy']}%";
                            
                            $this->getInfoNotification($message);
                            
                        } catch (\Throwable $e) {
                            $this->getDangerNotification('Gagal memuat statistik');
                        }
                    })
            ])
            ->emptyStateHeading('📋 Tidak Ada Sewa Berjalan')
            ->emptyStateDescription('Tidak ada transaksi sewa berjalan yang perlu dipantau keterlambatannya.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->striped()
            ->defaultSort('end_at', 'asc');
    }

    /**
     * Get the table query
     */
    protected function getTableQuery(): Builder
    {
        return Booking::query()
            ->where('status', 'ongoing')
            ->where('end_at', '<=', now()->addHours(6)) // Show bookings ending within 6 hours
            ->with([
                'customer.user', 
                'car', 
                'vendor', 
                'lateReturnReport' => fn($q) => $q->where('reporter_type', 'system'),
                'trackingPoints' => fn($q) => $q->latest('recorded_at')->limit(1)
            ])
            ->orderByRaw('CASE 
                WHEN end_at < NOW() THEN 1  -- Already late (highest priority)
                WHEN end_at <= DATE_ADD(NOW(), INTERVAL 2 HOUR) THEN 2  -- Due within 2 hours
                ELSE 3  -- Due later
            END')
            ->orderBy('end_at', 'asc');
    }

    /**
     * Get widget heading with dynamic count
     */
    public function getHeading(): string
    {
        $ongoingCount = Cache::remember('ongoing_bookings_count', 300, function () {
            return Booking::where('status', 'ongoing')->count();
        });
        
        $dueWithin6Hours = Booking::where('status', 'ongoing')
            ->where('end_at', '<=', now()->addHours(6))
            ->count();
        
        return "📊 Pemantauan Risiko Keterlambatan ({$dueWithin6Hours} dari {$ongoingCount} sewa berjalan)";
    }

    /**
     * Get widget description
     */
    public function getDescription(): ?string
    {
        $lateCount = Booking::where('status', 'ongoing')
            ->where('end_at', '<', now())
            ->count();
        
        $dueSoonCount = Booking::where('status', 'ongoing')
            ->whereBetween('end_at', [now(), now()->addHours(2)])
            ->count();
        
        $parts = [];
        
        if ($lateCount > 0) {
            $parts[] = "{$lateCount} sudah terlambat";
        }
        
        if ($dueSoonCount > 0) {
            $parts[] = "{$dueSoonCount} jatuh tempo dalam 2 jam";
        }
        
        if (empty($parts)) {
            return 'Semua penyewaan berjalan lancar • Auto-refresh setiap menit';
        }
        
        return implode(' • ', $parts) . ' • Auto-refresh setiap menit';
    }

    /**
     * Check if user can view this widget
     */
    public static function canView(): bool
    {
        $user = auth('admin')->user() ?? auth()->user();
        return $user?->isAdmin() ?? false;
    }

    protected function getSuccessNotification(string $message): void
    {
        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Berhasil')
            ->body($message)
            ->send();
    }

    protected function getDangerNotification(string $message): void
    {
        \Filament\Notifications\Notification::make()
            ->danger()
            ->title('Gagal')
            ->body($message)
            ->send();
    }

    protected function getInfoNotification(string $message): void
    {
        \Filament\Notifications\Notification::make()
            ->info()
            ->title('Informasi')
            ->body($message)
            ->send();
    }
}