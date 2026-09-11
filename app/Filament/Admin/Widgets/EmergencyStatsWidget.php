<?php

namespace App\Filament\Admin\Widgets;

use App\Models\EmergencyReport;
use App\Services\SmartRoutingService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class EmergencyStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '30s'; // Refresh every 30 seconds
    
    protected function getStats(): array
    {
        // Cache emergency stats for 1 minute to reduce DB load
        $stats = Cache::remember('emergency_stats_widget', 60, function () {
            try {
                $smartRouting = app(SmartRoutingService::class);
                return $smartRouting->getEmergencyStats();
            } catch (\Throwable $e) {
                \Log::error('Failed to load emergency stats for widget', [
                    'exception' => $e->getMessage()
                ]);
                
                return [
                    'active_emergencies' => 0,
                    'today_emergencies' => 0,
                    'month_emergencies' => 0,
                    'response_time_avg' => 0,
                    'resolution_rate' => 0,
                    'error' => true
                ];
            }
        });

        return [
            Stat::make('Emergency Aktif', $stats['active_emergencies'])
                ->description($stats['active_emergencies'] > 0 ? 'Butuh penanganan segera!' : 'Tidak ada emergency aktif')
                ->descriptionIcon($stats['active_emergencies'] > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($stats['active_emergencies'] > 0 ? 'danger' : 'success')
                ->chart($this->getActiveEmergencyTrend())
                ->url(url('/admin')),

            Stat::make('Laporan Hari Ini', $stats['today_emergencies'])
                ->description('Laporan darurat hari ini')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color($stats['today_emergencies'] > 5 ? 'warning' : 'primary')
                ->chart($this->getTodayEmergencyTrend()),

            Stat::make('Total Bulan Ini', $stats['month_emergencies'])
                ->description('Laporan darurat bulan ini')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('info')
                ->chart($this->getMonthlyEmergencyTrend()),

            Stat::make('Rata-rata Waktu Respon', $this->formatResponseTime($stats['response_time_avg'] ?? 0))
                ->description('Rata-rata waktu respon')
                ->descriptionIcon('heroicon-o-clock')
                ->color($this->getResponseTimeColor($stats['response_time_avg'] ?? 0)),

            Stat::make('Tingkat Penyelesaian', number_format($stats['resolution_rate'] ?? 0, 1) . '%')
                ->description('Tingkat penyelesaian bulanan')
                ->descriptionIcon('heroicon-o-chart-pie')
                ->color($this->getResolutionRateColor($stats['resolution_rate'] ?? 0))
                ->chart($this->getResolutionRateTrend()),

            // Breakdown tipe emergency
            Stat::make('Prioritas Tinggi', $this->getHighPriorityCount())
                ->description('Kritis & prioritas tinggi hari ini')
                ->descriptionIcon('heroicon-o-fire')
                ->color('danger')
        ];
    }

    /**
     * Get trend data for active emergencies (last 7 data points)
     */
    private function getActiveEmergencyTrend(): array
    {
        try {
            $trend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->startOfDay();
                $count = EmergencyReport::whereDate('reported_at', $date)
                    ->where('status', 'active')
                    ->count();
                $trend[] = $count;
            }
            return $trend;
        } catch (\Throwable $e) {
            return [0, 0, 0, 0, 0, 0, 0];
        }
    }

    /**
     * Get trend data for today's emergencies (last 24 hours by hour)
     */
    private function getTodayEmergencyTrend(): array
    {
        try {
            $trend = [];
            for ($i = 11; $i >= 0; $i--) { // Last 12 hours
                $hour = now()->subHours($i)->startOfHour();
                $count = EmergencyReport::where('reported_at', '>=', $hour)
                    ->where('reported_at', '<', $hour->copy()->addHour())
                    ->count();
                $trend[] = $count;
            }
            return $trend;
        } catch (\Throwable $e) {
            return array_fill(0, 12, 0);
        }
    }

    /**
     * Get monthly trend (last 7 days)
     */
    private function getMonthlyEmergencyTrend(): array
    {
        try {
            $trend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $count = EmergencyReport::whereDate('reported_at', $date)
                    ->count();
                $trend[] = $count;
            }
            return $trend;
        } catch (\Throwable $e) {
            return array_fill(0, 7, 0);
        }
    }

    /**
     * Get resolution rate trend (last 7 days)
     */
    private function getResolutionRateTrend(): array
    {
        try {
            $trend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $total = EmergencyReport::whereDate('reported_at', $date)->count();
                $resolved = EmergencyReport::whereDate('reported_at', $date)
                    ->whereIn('status', ['resolved', 'closed'])
                    ->count();
                
                $rate = $total > 0 ? ($resolved / $total) * 100 : 100;
                $trend[] = round($rate);
            }
            return $trend;
        } catch (\Throwable $e) {
            return array_fill(0, 7, 0);
        }
    }

    /**
     * Get high priority emergency count for today
     */
    private function getHighPriorityCount(): int
    {
        try {
            return EmergencyReport::whereDate('reported_at', today())
                ->whereIn('type', ['medical', 'accident', 'theft', 'harassment'])
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Format response time for display
     */
    private function formatResponseTime(float $minutes): string
    {
        if ($minutes < 60) {
            return number_format($minutes, 0) . 'm';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($remainingMinutes == 0) {
            return $hours . 'h';
        }
        
        return $hours . 'h ' . number_format($remainingMinutes, 0) . 'm';
    }

    /**
     * Get color for response time based on performance
     */
    private function getResponseTimeColor(float $minutes): string
    {
        if ($minutes <= 15) {
            return 'success'; // Excellent response time
        } elseif ($minutes <= 30) {
            return 'warning'; // Good response time
        } elseif ($minutes <= 60) {
            return 'danger'; // Needs improvement
        } else {
            return 'gray'; // Poor response time
        }
    }

    /**
     * Get color for resolution rate
     */
    private function getResolutionRateColor(float $rate): string
    {
        if ($rate >= 90) {
            return 'success';
        } elseif ($rate >= 75) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    /**
     * Check if user can view this widget
     */
    public static function canView(): bool
    {
        $user = auth('admin')->user() ?? auth()->user();
        return $user?->isAdmin() ?? false;
    }
}