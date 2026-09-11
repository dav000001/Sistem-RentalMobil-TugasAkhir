<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\EmergencyReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SmartRoutingService
{
    private string $googleMapsApiKey;
    private TrafficAnalysisService $trafficAnalysis;
    
    public function __construct(TrafficAnalysisService $trafficAnalysis)
    {
        $this->googleMapsApiKey = config('services.google_maps.api_key', '');
        $this->trafficAnalysis = $trafficAnalysis;
    }

    /**
     * Get smart routing recommendations for customer return journey
     */
    public function getReturnRouteRecommendations(Booking $booking, float $currentLat, float $currentLng): array
    {
        try {
            $vendorLat = $booking->vendor->latitude ?? $booking->car->latitude;
            $vendorLng = $booking->vendor->longitude ?? $booking->car->longitude;

            if (!$vendorLat || !$vendorLng) {
                return [
                    'success' => false,
                    'error' => 'Lokasi tujuan tidak tersedia'
                ];
            }

            // Get multiple route options
            $routes = $this->getRouteOptions($currentLat, $currentLng, $vendorLat, $vendorLng);
            
            if (empty($routes)) {
                return [
                    'success' => false,
                    'error' => 'Tidak dapat mengambil data rute'
                ];
            }

            // Analyze each route
            $analyzedRoutes = [];
            foreach ($routes as $index => $route) {
                $analysis = $this->analyzeRoute($route, $booking);
                $analyzedRoutes[] = array_merge($route, $analysis);
            }

            // Sort by recommendation score
            usort($analyzedRoutes, fn($a, $b) => ($b['recommendation_score'] ?? 0) <=> ($a['recommendation_score'] ?? 0));

            // Generate navigation links
            $navigationLinks = $this->generateNavigationLinks($currentLat, $currentLng, $vendorLat, $vendorLng);

            return [
                'success' => true,
                'current_location' => ['lat' => $currentLat, 'lng' => $currentLng],
                'destination' => ['lat' => $vendorLat, 'lng' => $vendorLng],
                'routes' => $analyzedRoutes,
                'recommended_route' => $analyzedRoutes[0] ?? null,
                'navigation_links' => $navigationLinks,
                'traffic_summary' => $this->getTrafficSummary($booking),
                'estimated_arrival' => $this->calculateEstimatedArrival($analyzedRoutes[0] ?? null)
            ];

        } catch (\Throwable $e) {
            Log::error('Smart routing failed', [
                'booking_id' => $booking->id,
                'current_lat' => $currentLat,
                'current_lng' => $currentLng,
                'exception' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Gagal menganalisis rute'
            ];
        }
    }

    /**
     * Get multiple route options from Google Directions API
     */
    private function getRouteOptions(float $originLat, float $originLng, float $destLat, float $destLng): array
    {
        if (!$this->googleMapsApiKey) {
            return [];
        }

        try {
            $cacheKey = "routes_{$originLat}_{$originLng}_{$destLat}_{$destLng}";
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                return $cached;
            }

            $response = Http::timeout(15)->get('https://maps.googleapis.com/maps/api/directions/json', [
                'origin' => "{$originLat},{$originLng}",
                'destination' => "{$destLat},{$destLng}",
                'key' => $this->googleMapsApiKey,
                'alternatives' => true,
                'departure_time' => 'now',
                'traffic_model' => 'best_guess',
                'language' => 'id'
            ]);

            if (!$response->successful() || $response->json('status') !== 'OK') {
                return [];
            }

            $routes = [];
            foreach ($response->json('routes', []) as $index => $route) {
                $leg = $route['legs'][0] ?? null;
                if (!$leg) continue;

                $routes[] = [
                    'id' => $index,
                    'summary' => $route['summary'] ?? "Rute " . ($index + 1),
                    'distance_km' => round(($leg['distance']['value'] ?? 0) / 1000, 1),
                    'duration_minutes' => ceil(($leg['duration']['value'] ?? 0) / 60),
                    'duration_in_traffic_minutes' => ceil(($leg['duration_in_traffic']['value'] ?? $leg['duration']['value'] ?? 0) / 60),
                    'steps' => $this->parseSteps($leg['steps'] ?? []),
                    'polyline' => $route['overview_polyline']['points'] ?? '',
                    'warnings' => $route['warnings'] ?? [],
                    'copyrights' => $route['copyrights'] ?? ''
                ];
            }

            // Cache for 10 minutes
            Cache::put($cacheKey, $routes, 600);
            return $routes;

        } catch (\Throwable $e) {
            Log::error('Failed to get route options', [
                'origin' => "{$originLat},{$originLng}",
                'destination' => "{$destLat},{$destLng}",
                'exception' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Analyze route and give it a recommendation score
     */
    private function analyzeRoute(array $route, Booking $booking): array
    {
        $analysis = [
            'recommendation_score' => 0,
            'pros' => [],
            'cons' => [],
            'traffic_level' => 'unknown',
            'is_recommended' => false,
            'arrival_confidence' => 'medium'
        ];

        try {
            // Score based on duration vs traffic delay
            $trafficDelay = $route['duration_in_traffic_minutes'] - $route['duration_minutes'];
            
            if ($trafficDelay <= 5) {
                $analysis['recommendation_score'] += 30;
                $analysis['pros'][] = 'Traffic lancar';
                $analysis['traffic_level'] = 'light';
            } elseif ($trafficDelay <= 15) {
                $analysis['recommendation_score'] += 15;
                $analysis['traffic_level'] = 'moderate';
            } else {
                $analysis['recommendation_score'] -= 10;
                $analysis['cons'][] = 'Traffic padat (+' . $trafficDelay . ' menit)';
                $analysis['traffic_level'] = 'heavy';
            }

            // Score based on distance (shorter is usually better)
            if ($route['distance_km'] <= 20) {
                $analysis['recommendation_score'] += 20;
                $analysis['pros'][] = 'Jarak pendek';
            } elseif ($route['distance_km'] <= 50) {
                $analysis['recommendation_score'] += 10;
            } else {
                $analysis['recommendation_score'] -= 5;
                $analysis['cons'][] = 'Jarak cukup jauh';
            }

            // Check if route will make customer late
            $timeUntilDeadline = now()->diffInMinutes($booking->end_at, false);
            $totalTravelTime = $route['duration_in_traffic_minutes'] + 15; // Add 15min buffer

            if ($totalTravelTime <= $timeUntilDeadline) {
                $analysis['recommendation_score'] += 25;
                $analysis['pros'][] = 'Dapat tiba tepat waktu';
                $analysis['arrival_confidence'] = 'high';
            } elseif ($totalTravelTime <= $timeUntilDeadline + 30) {
                $analysis['recommendation_score'] += 10;
                $analysis['arrival_confidence'] = 'medium';
            } else {
                $analysis['recommendation_score'] -= 20;
                $analysis['cons'][] = 'Berisiko terlambat';
                $analysis['arrival_confidence'] = 'low';
            }

            // Bonus for main/popular routes
            $summary = strtolower($route['summary']);
            $mainRoads = ['toll', 'highway', 'jalan tol', 'bypass', 'ring road'];
            foreach ($mainRoads as $road) {
                if (str_contains($summary, $road)) {
                    $analysis['recommendation_score'] += 5;
                    $analysis['pros'][] = 'Menggunakan jalan utama';
                    break;
                }
            }

            // Check warnings
            if (!empty($route['warnings'])) {
                $analysis['recommendation_score'] -= 10;
                $analysis['cons'] = array_merge($analysis['cons'], $route['warnings']);
            }

            $analysis['is_recommended'] = $analysis['recommendation_score'] >= 30;

            return $analysis;

        } catch (\Throwable $e) {
            Log::error('Route analysis failed', [
                'route_summary' => $route['summary'] ?? 'unknown',
                'exception' => $e->getMessage()
            ]);

            return $analysis;
        }
    }

    /**
     * Parse route steps for navigation
     */
    private function parseSteps(array $steps): array
    {
        $parsedSteps = [];

        foreach (array_slice($steps, 0, 10) as $step) { // Limit to 10 steps
            $parsedSteps[] = [
                'instruction' => strip_tags($step['html_instructions'] ?? ''),
                'distance' => $step['distance']['text'] ?? '',
                'duration' => $step['duration']['text'] ?? '',
                'maneuver' => $step['maneuver'] ?? null
            ];
        }

        return $parsedSteps;
    }

    /**
     * Generate navigation app links
     */
    private function generateNavigationLinks(float $originLat, float $originLng, float $destLat, float $destLng): array
    {
        return [
            'google_maps' => "https://www.google.com/maps/dir/{$originLat},{$originLng}/{$destLat},{$destLng}",
            'waze' => "https://waze.com/ul?ll={$destLat},{$destLng}&navigate=yes&from={$originLat},{$originLng}",
            'apple_maps' => "http://maps.apple.com/?saddr={$originLat},{$originLng}&daddr={$destLat},{$destLng}",
        ];
    }

    /**
     * Get traffic summary
     */
    private function getTrafficSummary(Booking $booking): array
    {
        try {
            return $this->trafficAnalysis->getTrafficStats();
        } catch (\Throwable $e) {
            return [
                'current_traffic_level' => 'unknown',
                'error' => 'Gagal memuat info traffic'
            ];
        }
    }

    /**
     * Calculate estimated arrival time
     */
    private function calculateEstimatedArrival(?array $route): ?array
    {
        if (!$route) return null;

        $estimatedArrival = now()->addMinutes($route['duration_in_traffic_minutes'] ?? 0);
        
        return [
            'time' => $estimatedArrival->format('H:i'),
            'date' => $estimatedArrival->format('d M Y'),
            'datetime' => $estimatedArrival->toISOString(),
            'minutes_from_now' => $route['duration_in_traffic_minutes'] ?? 0
        ];
    }

    /**
     * Emergency panic button - create emergency report
     */
    public function createEmergencyReport(
        Booking $booking,
        string $type,
        float $latitude,
        float $longitude,
        ?string $description = null
    ): array {
        try {
            // Validate emergency type
            $validTypes = ['breakdown', 'accident', 'theft', 'harassment', 'medical', 'other'];
            if (!in_array($type, $validTypes)) {
                return [
                    'success' => false,
                    'error' => 'Jenis emergency tidak valid'
                ];
            }

            // Create emergency report
            $emergency = EmergencyReport::create([
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'vendor_id' => $booking->vendor_id,
                'type' => $type,
                'description' => $description,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'status' => 'active',
                'reported_at' => now()
            ]);

            // Send immediate notifications
            $this->sendEmergencyNotifications($emergency);

            // Log for monitoring
            Log::critical('Emergency report created', [
                'emergency_id' => $emergency->id,
                'booking_id' => $booking->id,
                'type' => $type,
                'location' => "{$latitude},{$longitude}"
            ]);

            return [
                'success' => true,
                'emergency_id' => $emergency->id,
                'emergency_code' => $emergency->generateEmergencyCode(),
                'message' => 'Laporan emergency berhasil dikirim. Tim akan menghubungi Anda segera.',
                'next_steps' => $this->getEmergencyNextSteps($type)
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to create emergency report', [
                'booking_id' => $booking->id,
                'type' => $type,
                'exception' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Gagal mengirim laporan emergency'
            ];
        }
    }

    /**
     * Send emergency notifications to all relevant parties
     */
    private function sendEmergencyNotifications(EmergencyReport $emergency): void
    {
        try {
            // Notify vendor immediately
            $emergency->vendor?->user?->notify(
                new \App\Notifications\EmergencyReportNotification($emergency)
            );

            // Notify admin
            \App\Models\User::where('role', 'admin')->get()->each(function ($admin) use ($emergency) {
                $admin->notify(new \App\Notifications\EmergencyReportNotification($emergency));
            });

            // Send SMS if available (implement SMS service integration)
            // $this->sendEmergencySms($emergency);

        } catch (\Throwable $e) {
            Log::error('Failed to send emergency notifications', [
                'emergency_id' => $emergency->id,
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get next steps for emergency type
     */
    private function getEmergencyNextSteps(string $type): array
    {
        $nextSteps = [
            'breakdown' => [
                'Tetap di lokasi yang aman',
                'Vendor akan menghubungi Anda dalam 10 menit',
                'Jika perlu bantuan segera, hubungi: 0804-1-500-500'
            ],
            'accident' => [
                'Prioritaskan keselamatan - hubungi 112 jika ada cedera',
                'Jangan pindahkan kendaraan kecuali menghalangi traffic',
                'Ambil foto kondisi kendaraan dan lokasi',
                'Vendor dan tim emergency akan datang'
            ],
            'theft' => [
                'Hubungi polisi: 110',
                'Jangan kejar pelaku - prioritaskan keselamatan',
                'Catat ciri-ciri pelaku dan kronologi',
                'Tim akan membantu proses klaim asuransi'
            ],
            'harassment' => [
                'Pindah ke tempat ramai/aman jika memungkinkan',
                'Hubungi polisi: 110 jika merasa terancam',
                'Catat plat nomor/ciri pelaku',
                'Vendor akan koordinasi dengan pihak berwajib'
            ],
            'medical' => [
                'Hubungi ambulans: 118/119 jika darurat medis',
                'Tetap tenang dan ikuti instruksi medis',
                'Vendor akan membantu koordinasi rumah sakit',
                'Asuransi kesehatan dapat dibantu prosesnya'
            ],
            'other' => [
                'Tetap di lokasi yang aman',
                'Tim akan menghubungi dalam 5-10 menit',
                'Jelaskan situasi detail saat dihubungi'
            ]
        ];

        return $nextSteps[$type] ?? $nextSteps['other'];
    }

    /**
     * Update emergency report status
     */
    public function updateEmergencyStatus(int $emergencyId, string $status, ?string $resolution = null): array
    {
        try {
            $emergency = EmergencyReport::find($emergencyId);
            
            if (!$emergency) {
                return [
                    'success' => false,
                    'error' => 'Emergency report tidak ditemukan'
                ];
            }

            $validStatuses = ['active', 'responding', 'resolved', 'closed'];
            if (!in_array($status, $validStatuses)) {
                return [
                    'success' => false,
                    'error' => 'Status tidak valid'
                ];
            }

            $emergency->update([
                'status' => $status,
                'resolution' => $resolution,
                'resolved_at' => in_array($status, ['resolved', 'closed']) ? now() : null
            ]);

            // Notify customer of status update
            try {
                $emergency->customer?->user?->notify(
                    new \App\Notifications\EmergencyStatusUpdateNotification($emergency)
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to send emergency status update notification', [
                    'emergency_id' => $emergencyId,
                    'exception' => $e->getMessage()
                ]);
            }

            return [
                'success' => true,
                'message' => 'Status emergency berhasil diupdate',
                'new_status' => $status
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to update emergency status', [
                'emergency_id' => $emergencyId,
                'status' => $status,
                'exception' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Gagal mengupdate status emergency'
            ];
        }
    }

    /**
     * Get emergency statistics for admin dashboard
     */
    public function getEmergencyStats(): array
    {
        try {
            $today = now()->startOfDay();
            $thisMonth = now()->startOfMonth();

            return [
                'active_emergencies' => EmergencyReport::where('status', 'active')->count(),
                'today_emergencies' => EmergencyReport::where('reported_at', '>=', $today)->count(),
                'month_emergencies' => EmergencyReport::where('reported_at', '>=', $thisMonth)->count(),
                'response_time_avg' => $this->calculateAverageResponseTime(),
                'emergency_types' => $this->getEmergencyTypeBreakdown($thisMonth),
                'resolution_rate' => $this->calculateResolutionRate($thisMonth)
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to get emergency stats', ['exception' => $e->getMessage()]);
            
            return [
                'active_emergencies' => 0,
                'today_emergencies' => 0,
                'month_emergencies' => 0,
                'error' => 'Failed to load emergency statistics'
            ];
        }
    }

    /**
     * Calculate average emergency response time
     */
    private function calculateAverageResponseTime(): float
    {
        try {
            $resolvedEmergencies = EmergencyReport::where('status', 'resolved')
                ->where('reported_at', '>=', now()->subDays(30))
                ->whereNotNull('resolved_at')
                ->get();

            if ($resolvedEmergencies->isEmpty()) {
                return 0;
            }

            $totalMinutes = $resolvedEmergencies->sum(function ($emergency) {
                return $emergency->reported_at->diffInMinutes($emergency->resolved_at);
            });

            return round($totalMinutes / $resolvedEmergencies->count(), 1);

        } catch (\Throwable $e) {
            Log::error('Failed to calculate average response time', ['exception' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Get emergency type breakdown
     */
    private function getEmergencyTypeBreakdown($since): array
    {
        try {
            return EmergencyReport::where('reported_at', '>=', $since)
                ->groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->pluck('count', 'type')
                ->toArray();

        } catch (\Throwable $e) {
            Log::error('Failed to get emergency type breakdown', ['exception' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Calculate emergency resolution rate
     */
    private function calculateResolutionRate($since): float
    {
        try {
            $total = EmergencyReport::where('reported_at', '>=', $since)->count();
            $resolved = EmergencyReport::where('reported_at', '>=', $since)
                ->whereIn('status', ['resolved', 'closed'])
                ->count();

            return $total > 0 ? round(($resolved / $total) * 100, 1) : 0;

        } catch (\Throwable $e) {
            Log::error('Failed to calculate resolution rate', ['exception' => $e->getMessage()]);
            return 0;
        }
    }
}