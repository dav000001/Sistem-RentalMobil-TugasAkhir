<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TrafficAnalysisService
{
    private string $googleMapsApiKey;
    
    public function __construct()
    {
        $this->googleMapsApiKey = config('services.google_maps.api_key', '');
    }

    /**
     * Analyze traffic delay risk for a booking
     */
    public function analyzeTrafficDelayRisk(Booking $booking): array
    {
        $result = [
            'has_delay' => false,
            'severity' => 'none', // none, low, medium, high
            'reason' => '',
            'estimated_delay_minutes' => 0,
            'alternative_routes' => [],
            'metadata' => []
        ];

        try {
            // 1. Time-based analysis (rush hour, etc.)
            $timeAnalysis = $this->analyzeTimeBasedTraffic();
            $result['metadata']['time_analysis'] = $timeAnalysis;

            if ($timeAnalysis['is_rush_hour']) {
                $result['has_delay'] = true;
                $result['severity'] = 'medium';
                $result['reason'] = $timeAnalysis['reason'];
                $result['estimated_delay_minutes'] = $timeAnalysis['estimated_delay'];
            }

            // 2. Live traffic data (if Google Maps API available)
            if ($this->googleMapsApiKey && $booking->lateReturnReport?->hasLocation()) {
                $liveTrafficAnalysis = $this->analyzeLiveTraffic($booking);
                $result['metadata']['live_traffic'] = $liveTrafficAnalysis;

                if ($liveTrafficAnalysis['has_delay']) {
                    $result['has_delay'] = true;
                    $result['severity'] = max($result['severity'], $liveTrafficAnalysis['severity']);
                    $result['estimated_delay_minutes'] = max(
                        $result['estimated_delay_minutes'], 
                        $liveTrafficAnalysis['delay_minutes']
                    );
                    $result['alternative_routes'] = $liveTrafficAnalysis['alternative_routes'];
                }
            }

            // 3. Historical traffic patterns
            $historicalAnalysis = $this->analyzeHistoricalTraffic();
            $result['metadata']['historical'] = $historicalAnalysis;

            if ($historicalAnalysis['high_traffic_probability']) {
                $result['has_delay'] = true;
                $result['severity'] = max($result['severity'], 'low');
                $result['reason'] = $result['reason'] 
                    ? $result['reason'] . '; ' . $historicalAnalysis['reason']
                    : $historicalAnalysis['reason'];
            }

            // 4. Weather impact (basic)
            $weatherAnalysis = $this->analyzeWeatherImpact();
            if ($weatherAnalysis['affects_traffic']) {
                $result['has_delay'] = true;
                $result['estimated_delay_minutes'] += $weatherAnalysis['delay_minutes'];
                $result['reason'] .= ($result['reason'] ? '; ' : '') . $weatherAnalysis['reason'];
            }

            return $result;

        } catch (\Throwable $e) {
            Log::error('Traffic analysis failed', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage()
            ]);

            return $result;
        }
    }

    /**
     * Analyze time-based traffic patterns
     */
    private function analyzeTimeBasedTraffic(): array
    {
        $now = now();
        $hour = $now->hour;
        $dayOfWeek = $now->dayOfWeek; // 0 = Sunday, 6 = Saturday

        $analysis = [
            'is_rush_hour' => false,
            'traffic_level' => 'normal',
            'estimated_delay' => 0,
            'reason' => ''
        ];

        // Rush hour detection
        $morningRush = in_array($hour, [7, 8, 9]); // 7-9 AM
        $eveningRush = in_array($hour, [17, 18, 19]); // 5-7 PM
        $isWeekday = in_array($dayOfWeek, [1, 2, 3, 4, 5]); // Monday-Friday

        if ($isWeekday && ($morningRush || $eveningRush)) {
            $analysis['is_rush_hour'] = true;
            $analysis['traffic_level'] = 'heavy';
            $analysis['estimated_delay'] = $morningRush ? 15 : 25; // Evening usually worse
            $analysis['reason'] = $morningRush ? 'Jam sibuk pagi hari' : 'Jam sibuk sore hari';
        } elseif ($isWeekday && in_array($hour, [12, 13])) {
            // Lunch hour
            $analysis['traffic_level'] = 'medium';
            $analysis['estimated_delay'] = 10;
            $analysis['reason'] = 'Jam makan siang';
        } elseif (!$isWeekday && in_array($hour, [10, 11, 12, 13, 14, 15, 16])) {
            // Weekend afternoon - shopping/recreation traffic
            $analysis['traffic_level'] = 'medium';
            $analysis['estimated_delay'] = 8;
            $analysis['reason'] = 'Traffic weekend - aktivitas rekreasi';
        }

        return $analysis;
    }

    /**
     * Analyze live traffic using Google Maps API
     */
    private function analyzeLiveTraffic(Booking $booking): array
    {
        $result = [
            'has_delay' => false,
            'severity' => 'none',
            'delay_minutes' => 0,
            'alternative_routes' => []
        ];

        try {
            if (!$this->googleMapsApiKey) {
                return $result;
            }

            $report = $booking->lateReturnReport;
            $vendorLat = $booking->vendor->latitude ?? $booking->car->latitude;
            $vendorLng = $booking->vendor->longitude ?? $booking->car->longitude;

            if (!$report->hasLocation() || !$vendorLat || !$vendorLng) {
                return $result;
            }

            // Use cached result if available (5-minute cache)
            $cacheKey = "traffic_analysis_{$report->latitude}_{$report->longitude}_{$vendorLat}_{$vendorLng}";
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                return $cached;
            }

            // Get directions with traffic data
            $response = Http::timeout(15)->get('https://maps.googleapis.com/maps/api/directions/json', [
                'origin' => "{$report->latitude},{$report->longitude}",
                'destination' => "{$vendorLat},{$vendorLng}",
                'key' => $this->googleMapsApiKey,
                'departure_time' => 'now',
                'traffic_model' => 'best_guess',
                'alternatives' => true
            ]);

            if ($response->successful() && $response->json('status') === 'OK') {
                $routes = $response->json('routes', []);
                
                if (!empty($routes)) {
                    $mainRoute = $routes[0];
                    $durationInTraffic = $mainRoute['legs'][0]['duration_in_traffic']['value'] ?? 0;
                    $durationNormal = $mainRoute['legs'][0]['duration']['value'] ?? 0;
                    
                    if ($durationInTraffic > $durationNormal) {
                        $delaySeconds = $durationInTraffic - $durationNormal;
                        $delayMinutes = ceil($delaySeconds / 60);
                        
                        $result['has_delay'] = true;
                        $result['delay_minutes'] = $delayMinutes;
                        
                        if ($delayMinutes >= 30) {
                            $result['severity'] = 'high';
                        } elseif ($delayMinutes >= 15) {
                            $result['severity'] = 'medium';
                        } else {
                            $result['severity'] = 'low';
                        }

                        // Get alternative routes
                        foreach (array_slice($routes, 1, 2) as $altRoute) {
                            $altDuration = $altRoute['legs'][0]['duration_in_traffic']['value'] ?? 0;
                            $altDistance = $altRoute['legs'][0]['distance']['value'] ?? 0;
                            
                            $result['alternative_routes'][] = [
                                'duration_minutes' => ceil($altDuration / 60),
                                'distance_km' => round($altDistance / 1000, 1),
                                'summary' => $altRoute['summary'] ?? 'Rute alternatif'
                            ];
                        }
                    }
                }
            }

            // Cache for 5 minutes
            Cache::put($cacheKey, $result, 300);
            return $result;

        } catch (\Throwable $e) {
            Log::error('Live traffic analysis failed', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage()
            ]);

            return $result;
        }
    }

    /**
     * Analyze historical traffic patterns
     */
    private function analyzeHistoricalTraffic(): array
    {
        $now = now();
        $hour = $now->hour;
        $dayOfWeek = $now->dayOfWeek;

        // Simple historical pattern rules
        $patterns = [
            // Friday evening - heavy traffic
            ['day' => 5, 'hours' => [16, 17, 18, 19, 20], 'probability' => 0.8, 'reason' => 'Jumat sore - traffic padat'],
            
            // Monday morning - medium traffic
            ['day' => 1, 'hours' => [7, 8, 9], 'probability' => 0.6, 'reason' => 'Senin pagi - awal pekan'],
            
            // Weekend mall hours
            ['days' => [0, 6], 'hours' => [11, 12, 13, 14, 15, 16], 'probability' => 0.5, 'reason' => 'Weekend - jam mall'],
            
            // General rush hours
            ['hours' => [7, 8, 17, 18], 'probability' => 0.7, 'reason' => 'Jam sibuk umum'],
        ];

        foreach ($patterns as $pattern) {
            $matchesDay = !isset($pattern['day']) || $pattern['day'] === $dayOfWeek;
            $matchesDays = !isset($pattern['days']) || in_array($dayOfWeek, $pattern['days']);
            $matchesHour = in_array($hour, $pattern['hours']);

            if (($matchesDay || $matchesDays) && $matchesHour) {
                return [
                    'high_traffic_probability' => $pattern['probability'] >= 0.6,
                    'probability' => $pattern['probability'],
                    'reason' => $pattern['reason']
                ];
            }
        }

        return [
            'high_traffic_probability' => false,
            'probability' => 0.2,
            'reason' => 'Traffic normal'
        ];
    }

    /**
     * Analyze weather impact on traffic (basic implementation)
     */
    private function analyzeWeatherImpact(): array
    {
        // This is a simplified implementation
        // In production, you'd integrate with weather APIs
        
        $now = now();
        $hour = $now->hour;

        // Simple heuristics based on time and season
        $rainProbability = $this->estimateRainProbability();
        
        if ($rainProbability >= 0.6) {
            return [
                'affects_traffic' => true,
                'delay_minutes' => 10,
                'reason' => 'Kemungkinan hujan - traffic melambat'
            ];
        }

        // Evening hours often have more weather-related delays
        if (in_array($hour, [17, 18, 19]) && $rainProbability >= 0.4) {
            return [
                'affects_traffic' => true,
                'delay_minutes' => 5,
                'reason' => 'Sore hari dengan kemungkinan hujan'
            ];
        }

        return [
            'affects_traffic' => false,
            'delay_minutes' => 0,
            'reason' => 'Cuaca tidak mempengaruhi traffic'
        ];
    }

    /**
     * Estimate rain probability based on simple heuristics
     */
    private function estimateRainProbability(): float
    {
        $now = now();
        $hour = $now->hour;
        $month = $now->month;

        // Rainy season in Indonesia (roughly Oct-Mar)
        $isRainySeason = in_array($month, [10, 11, 12, 1, 2, 3]);
        
        // Afternoon/evening rain more common
        $isAfternoonEvening = in_array($hour, [14, 15, 16, 17, 18]);

        if ($isRainySeason && $isAfternoonEvening) {
            return 0.7;
        } elseif ($isRainySeason) {
            return 0.4;
        } elseif ($isAfternoonEvening) {
            return 0.3;
        }

        return 0.2;
    }

    /**
     * Get traffic recommendations for customer
     */
    public function getTrafficRecommendations(Booking $booking): array
    {
        $analysis = $this->analyzeTrafficDelayRisk($booking);
        $recommendations = [];

        if (!$analysis['has_delay']) {
            $recommendations[] = 'Traffic saat ini cukup lancar';
            return $recommendations;
        }

        if ($analysis['estimated_delay_minutes'] >= 30) {
            $recommendations[] = 'Traffic sangat padat - pertimbangkan berangkat lebih awal';
        } elseif ($analysis['estimated_delay_minutes'] >= 15) {
            $recommendations[] = 'Traffic cukup padat - tambahkan 15-30 menit perjalanan';
        }

        if (!empty($analysis['alternative_routes'])) {
            $fastestAlt = collect($analysis['alternative_routes'])->sortBy('duration_minutes')->first();
            $recommendations[] = "Rute alternatif tersedia: {$fastestAlt['summary']} ({$fastestAlt['duration_minutes']} menit)";
        }

        if (str_contains($analysis['reason'], 'rush')) {
            $recommendations[] = 'Hindari jam sibuk jika memungkinkan';
        }

        return $recommendations;
    }

    /**
     * Get traffic statistics for admin dashboard
     */
    public function getTrafficStats(): array
    {
        try {
            $currentTraffic = $this->analyzeTimeBasedTraffic();
            
            return [
                'current_traffic_level' => $currentTraffic['traffic_level'],
                'is_rush_hour' => $currentTraffic['is_rush_hour'],
                'estimated_delay' => $currentTraffic['estimated_delay'],
                'reason' => $currentTraffic['reason'],
                'last_updated' => now()->toISOString()
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to get traffic stats', ['exception' => $e->getMessage()]);
            
            return [
                'current_traffic_level' => 'unknown',
                'is_rush_hour' => false,
                'estimated_delay' => 0,
                'error' => 'Failed to load traffic data'
            ];
        }
    }
}