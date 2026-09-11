<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingTrackingPoint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class LiveTrackingService
{
    private GpsValidationService $gpsValidator;
    private string $redisPrefix = 'live_tracking:';

    public function __construct(GpsValidationService $gpsValidator)
    {
        $this->gpsValidator = $gpsValidator;
    }

    /**
     * Record a GPS tracking point for ongoing booking
     */
    public function recordTrackingPoint(
        Booking $booking,
        float $latitude,
        float $longitude,
        ?string $address = null,
        array $metadata = []
    ): array {
        try {
            // Only track ongoing bookings
            if ($booking->status !== 'ongoing') {
                return [
                    'success' => false,
                    'error' => 'Tracking hanya tersedia untuk booking yang sedang berlangsung'
                ];
            }

            // Validate GPS
            $gpsValidation = $this->gpsValidator->validateLocation(
                $latitude, $longitude, $booking, 'live_tracking'
            );

            // Don't record obviously fake locations
            if (!$gpsValidation['is_valid'] || $gpsValidation['accuracy_score'] < 40) {
                return [
                    'success' => false,
                    'error' => 'Lokasi GPS tidak valid untuk tracking',
                    'gps_validation' => $gpsValidation
                ];
            }

            // Check if this point is too close to the last recorded point (prevent spam)
            $lastPoint = $this->getLastTrackingPoint($booking);
            if ($lastPoint && $this->isPointTooClose($lastPoint, $latitude, $longitude)) {
                return [
                    'success' => true,
                    'message' => 'Point terlalu dekat dengan lokasi sebelumnya',
                    'skipped' => true
                ];
            }

            // Create tracking point record
            $trackingPoint = BookingTrackingPoint::create([
                'booking_id' => $booking->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address' => $address,
                'accuracy_score' => $gpsValidation['accuracy_score'],
                'gps_validation_data' => json_encode($gpsValidation),
                'metadata' => json_encode($metadata),
                'recorded_at' => now()
            ]);

            // Store in Redis for real-time access
            $this->storeInRedis($booking, $trackingPoint);

            // Analyze tracking data for insights
            $insights = $this->analyzeTrackingData($booking);

            Log::info('Tracking point recorded', [
                'booking_id' => $booking->id,
                'tracking_point_id' => $trackingPoint->id,
                'accuracy_score' => $gpsValidation['accuracy_score'],
                'insights' => $insights
            ]);

            return [
                'success' => true,
                'tracking_point_id' => $trackingPoint->id,
                'accuracy_score' => $gpsValidation['accuracy_score'],
                'insights' => $insights,
                'gps_validation' => $gpsValidation
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to record tracking point', [
                'booking_id' => $booking->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'exception' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Gagal merekam titik tracking'
            ];
        }
    }

    /**
     * Get real-time tracking data for a booking
     */
    public function getLiveTrackingData(Booking $booking): array
    {
        try {
            // Get from Redis first (real-time)
            $liveData = $this->getFromRedis($booking);
            
            // Get recent points from database
            $recentPoints = BookingTrackingPoint::where('booking_id', $booking->id)
                ->where('recorded_at', '>=', now()->subHours(4))
                ->orderBy('recorded_at', 'desc')
                ->limit(20)
                ->get();

            // Calculate tracking statistics
            $stats = $this->calculateTrackingStats($booking, $recentPoints);

            return [
                'booking_id' => $booking->id,
                'is_being_tracked' => $booking->status === 'ongoing' && $booking->customer->tracking_enabled ?? false,
                'last_update' => $liveData['last_update'] ?? null,
                'current_location' => $liveData['current_location'] ?? null,
                'recent_points' => $recentPoints->map(function ($point) {
                    return [
                        'id' => $point->id,
                        'latitude' => $point->latitude,
                        'longitude' => $point->longitude,
                        'address' => $point->address,
                        'accuracy_score' => $point->accuracy_score,
                        'recorded_at' => $point->recorded_at->toISOString(),
                        'time_ago' => $point->recorded_at->diffForHumans()
                    ];
                }),
                'statistics' => $stats,
                'insights' => $this->generateTrackingInsights($booking, $recentPoints)
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to get live tracking data', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage()
            ]);

            return [
                'booking_id' => $booking->id,
                'is_being_tracked' => false,
                'error' => 'Gagal memuat data tracking'
            ];
        }
    }

    /**
     * Enable/disable live tracking for a customer
     */
    public function toggleTracking(Booking $booking, bool $enabled): array
    {
        try {
            if ($booking->status !== 'ongoing') {
                return [
                    'success' => false,
                    'error' => 'Live tracking hanya tersedia untuk booking yang sedang berlangsung'
                ];
            }

            // Update customer preference (assuming there's a tracking_enabled field)
            $booking->customer->update(['tracking_enabled' => $enabled]);

            if (!$enabled) {
                // Clear Redis data when tracking is disabled
                $this->clearRedisData($booking);
            }

            return [
                'success' => true,
                'tracking_enabled' => $enabled,
                'message' => $enabled 
                    ? 'Live tracking diaktifkan. Lokasi Anda akan dibagikan dengan vendor.'
                    : 'Live tracking dinonaktifkan.'
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to toggle tracking', [
                'booking_id' => $booking->id,
                'enabled' => $enabled,
                'exception' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Gagal mengubah pengaturan tracking'
            ];
        }
    }

    /**
     * Get tracking journey summary
     */
    public function getTrackingJourney(Booking $booking): array
    {
        try {
            $points = BookingTrackingPoint::where('booking_id', $booking->id)
                ->orderBy('recorded_at')
                ->get();

            if ($points->isEmpty()) {
                return [
                    'has_tracking' => false,
                    'message' => 'Tidak ada data tracking untuk booking ini'
                ];
            }

            $journey = [
                'has_tracking' => true,
                'total_points' => $points->count(),
                'journey_start' => $points->first()->recorded_at,
                'journey_end' => $points->last()->recorded_at,
                'duration_hours' => $points->first()->recorded_at->diffInHours($points->last()->recorded_at),
                'estimated_distance_km' => $this->calculateTotalDistance($points),
                'points' => $points->map(function ($point) {
                    return [
                        'latitude' => $point->latitude,
                        'longitude' => $point->longitude,
                        'address' => $point->address,
                        'recorded_at' => $point->recorded_at->toISOString(),
                        'accuracy_score' => $point->accuracy_score
                    ];
                }),
                'summary' => $this->generateJourneySummary($points)
            ];

            return $journey;

        } catch (\Throwable $e) {
            Log::error('Failed to get tracking journey', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage()
            ]);

            return [
                'has_tracking' => false,
                'error' => 'Gagal memuat riwayat tracking'
            ];
        }
    }

    /**
     * Store tracking point in Redis for real-time access
     */
    private function storeInRedis(Booking $booking, BookingTrackingPoint $point): void
    {
        try {
            $key = $this->redisPrefix . $booking->id;
            
            $data = [
                'booking_id' => $booking->id,
                'last_update' => now()->toISOString(),
                'current_location' => [
                    'latitude' => $point->latitude,
                    'longitude' => $point->longitude,
                    'address' => $point->address,
                    'accuracy_score' => $point->accuracy_score,
                    'recorded_at' => $point->recorded_at->toISOString()
                ]
            ];

            Redis::setex($key, 3600, json_encode($data)); // Expire after 1 hour

        } catch (\Throwable $e) {
            Log::warning('Failed to store tracking in Redis', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get tracking data from Redis
     */
    private function getFromRedis(Booking $booking): ?array
    {
        try {
            $key = $this->redisPrefix . $booking->id;
            $data = Redis::get($key);
            
            return $data ? json_decode($data, true) : null;

        } catch (\Throwable $e) {
            Log::warning('Failed to get tracking from Redis', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Clear Redis data for booking
     */
    private function clearRedisData(Booking $booking): void
    {
        try {
            $key = $this->redisPrefix . $booking->id;
            Redis::del($key);
        } catch (\Throwable $e) {
            Log::warning('Failed to clear Redis tracking data', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get last tracking point for booking
     */
    private function getLastTrackingPoint(Booking $booking): ?BookingTrackingPoint
    {
        return BookingTrackingPoint::where('booking_id', $booking->id)
            ->orderBy('recorded_at', 'desc')
            ->first();
    }

    /**
     * Check if point is too close to previous point (prevent spam)
     */
    private function isPointTooClose(?BookingTrackingPoint $lastPoint, float $lat, float $lng): bool
    {
        if (!$lastPoint) return false;

        // If last point was recorded less than 2 minutes ago
        if ($lastPoint->recorded_at > now()->subMinutes(2)) {
            // Calculate distance
            $distance = $this->gpsValidator->calculateDistance(
                $lastPoint->latitude, $lastPoint->longitude, $lat, $lng
            );

            // If distance is less than 100 meters, consider it too close
            return $distance < 0.1;
        }

        return false;
    }

    /**
     * Analyze tracking data for insights
     */
    private function analyzeTrackingData(Booking $booking): array
    {
        try {
            $recentPoints = BookingTrackingPoint::where('booking_id', $booking->id)
                ->where('recorded_at', '>=', now()->subHours(2))
                ->orderBy('recorded_at')
                ->get();

            if ($recentPoints->count() < 2) {
                return ['status' => 'insufficient_data'];
            }

            $insights = [];

            // Calculate movement speed
            $totalDistance = $this->calculateTotalDistance($recentPoints);
            $timeHours = $recentPoints->first()->recorded_at->diffInHours($recentPoints->last()->recorded_at);
            
            if ($timeHours > 0) {
                $averageSpeed = $totalDistance / $timeHours;
                $insights['average_speed_kmh'] = round($averageSpeed, 1);

                if ($averageSpeed > 80) {
                    $insights['warnings'][] = 'Kecepatan tinggi terdeteksi';
                } elseif ($averageSpeed < 5) {
                    $insights['warnings'][] = 'Customer mungkin sedang berhenti lama';
                }
            }

            // Check direction towards return point
            $lastPoint = $recentPoints->last();
            $vendorLat = $booking->vendor->latitude ?? $booking->car->latitude;
            $vendorLng = $booking->vendor->longitude ?? $booking->car->longitude;

            if ($vendorLat && $vendorLng) {
                $distanceToReturn = $this->gpsValidator->calculateDistance(
                    $lastPoint->latitude, $lastPoint->longitude, $vendorLat, $vendorLng
                );
                $insights['distance_to_return_km'] = round($distanceToReturn, 1);

                $timeLeft = now()->diffInHours($booking->end_at, false);
                if ($timeLeft > 0) {
                    $requiredSpeed = $distanceToReturn / $timeLeft;
                    $insights['required_average_speed_kmh'] = round($requiredSpeed, 1);

                    if ($requiredSpeed > 60) {
                        $insights['warnings'][] = 'Perlu kecepatan tinggi untuk tepat waktu';
                    }
                }
            }

            return $insights;

        } catch (\Throwable $e) {
            Log::warning('Failed to analyze tracking data', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage()
            ]);

            return ['status' => 'analysis_failed'];
        }
    }

    /**
     * Calculate total distance from tracking points
     */
    private function calculateTotalDistance($points): float
    {
        if ($points->count() < 2) return 0;

        $totalDistance = 0;
        $previousPoint = null;

        foreach ($points as $point) {
            if ($previousPoint) {
                $distance = $this->gpsValidator->calculateDistance(
                    $previousPoint->latitude, $previousPoint->longitude,
                    $point->latitude, $point->longitude
                );
                $totalDistance += $distance;
            }
            $previousPoint = $point;
        }

        return $totalDistance;
    }

    /**
     * Calculate tracking statistics
     */
    private function calculateTrackingStats(Booking $booking, $recentPoints): array
    {
        $stats = [
            'total_points' => $recentPoints->count(),
            'tracking_duration_hours' => 0,
            'estimated_distance_km' => 0,
            'average_accuracy_score' => 0,
            'last_update' => null
        ];

        if ($recentPoints->isEmpty()) return $stats;

        $stats['last_update'] = $recentPoints->first()->recorded_at->diffForHumans();
        $stats['average_accuracy_score'] = round($recentPoints->avg('accuracy_score'), 1);

        if ($recentPoints->count() >= 2) {
            $firstPoint = $recentPoints->last(); // last() because we ordered desc
            $lastPoint = $recentPoints->first();
            
            $stats['tracking_duration_hours'] = round($firstPoint->recorded_at->diffInHours($lastPoint->recorded_at), 1);
            $stats['estimated_distance_km'] = round($this->calculateTotalDistance($recentPoints->reverse()), 1);
        }

        return $stats;
    }

    /**
     * Generate tracking insights
     */
    private function generateTrackingInsights(Booking $booking, $recentPoints): array
    {
        $insights = [];

        if ($recentPoints->isEmpty()) {
            return ['message' => 'Belum ada data tracking terbaru'];
        }

        $latestPoint = $recentPoints->first();
        
        // Check if customer is moving towards return location
        $vendorLat = $booking->vendor->latitude ?? $booking->car->latitude;
        $vendorLng = $booking->vendor->longitude ?? $booking->car->longitude;

        if ($vendorLat && $vendorLng) {
            $distanceToReturn = $this->gpsValidator->calculateDistance(
                $latestPoint->latitude, $latestPoint->longitude, $vendorLat, $vendorLng
            );

            $timeLeft = now()->diffInHours($booking->end_at, false);
            
            if ($timeLeft > 0) {
                if ($distanceToReturn <= 5) {
                    $insights[] = '✅ Customer sudah dekat lokasi pengembalian';
                } elseif ($distanceToReturn <= 20) {
                    $insights[] = '🚗 Customer dalam perjalanan pulang';
                } else {
                    $requiredSpeed = $distanceToReturn / $timeLeft;
                    if ($requiredSpeed > 50) {
                        $insights[] = '⚠️ Customer perlu bergegas agar tidak terlambat';
                    } else {
                        $insights[] = '📍 Customer masih dalam jarak wajar';
                    }
                }
            } else {
                $insights[] = '🕐 Waktu pengembalian sudah lewat';
            }
        }

        // Check GPS accuracy
        if ($latestPoint->accuracy_score >= 80) {
            $insights[] = '📍 GPS sangat akurat';
        } elseif ($latestPoint->accuracy_score >= 60) {
            $insights[] = '📍 GPS cukup akurat';
        } else {
            $insights[] = '⚠️ GPS kurang akurat, data mungkin tidak tepat';
        }

        return $insights;
    }

    /**
     * Generate journey summary
     */
    private function generateJourneySummary($points): array
    {
        if ($points->count() < 2) {
            return ['message' => 'Data perjalanan tidak cukup untuk membuat ringkasan'];
        }

        $startPoint = $points->first();
        $endPoint = $points->last();
        $totalDistance = $this->calculateTotalDistance($points);
        $duration = $startPoint->recorded_at->diffInHours($endPoint->recorded_at);

        $summary = [
            'start_time' => $startPoint->recorded_at->format('d M Y H:i'),
            'end_time' => $endPoint->recorded_at->format('d M Y H:i'),
            'duration_hours' => round($duration, 1),
            'estimated_distance_km' => round($totalDistance, 1),
            'average_speed_kmh' => $duration > 0 ? round($totalDistance / $duration, 1) : 0,
            'total_stops' => $this->countStops($points),
        ];

        // Add insights
        if ($summary['average_speed_kmh'] > 60) {
            $summary['insights'][] = 'Perjalanan dengan kecepatan tinggi';
        } elseif ($summary['average_speed_kmh'] < 20) {
            $summary['insights'][] = 'Perjalanan santai dengan banyak berhenti';
        }

        if ($summary['total_stops'] > 5) {
            $summary['insights'][] = 'Banyak pemberhentian selama perjalanan';
        }

        return $summary;
    }

    /**
     * Count stops during journey
     */
    private function countStops($points): int
    {
        if ($points->count() < 3) return 0;

        $stops = 0;
        $stopThreshold = 0.1; // 100 meters
        $timeThreshold = 10; // 10 minutes

        for ($i = 1; $i < $points->count() - 1; $i++) {
            $prevPoint = $points[$i - 1];
            $currPoint = $points[$i];
            $nextPoint = $points[$i + 1];

            $distToPrev = $this->gpsValidator->calculateDistance(
                $currPoint->latitude, $currPoint->longitude,
                $prevPoint->latitude, $prevPoint->longitude
            );

            $distToNext = $this->gpsValidator->calculateDistance(
                $currPoint->latitude, $currPoint->longitude,
                $nextPoint->latitude, $nextPoint->longitude
            );

            $timeDiff = $prevPoint->recorded_at->diffInMinutes($nextPoint->recorded_at);

            // If both distances are small and time gap is significant, count as a stop
            if ($distToPrev < $stopThreshold && $distToNext < $stopThreshold && $timeDiff >= $timeThreshold) {
                $stops++;
            }
        }

        return $stops;
    }
}