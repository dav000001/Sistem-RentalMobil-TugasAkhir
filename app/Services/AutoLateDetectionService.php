<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\LateReturnReport;
use App\Notifications\AutoLateDetectionNotification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoLateDetectionService
{
    private GpsValidationService $gpsValidator;
    private TrafficAnalysisService $trafficAnalysis;

    public function __construct(GpsValidationService $gpsValidator, TrafficAnalysisService $trafficAnalysis)
    {
        $this->gpsValidator = $gpsValidator;
        $this->trafficAnalysis = $trafficAnalysis;
    }

    /**
     * Deteksi otomatis keterlambatan untuk semua booking yang sedang ongoing
     */
    public function detectAllLateReturns(): array
    {
        $results = [
            'checked' => 0,
            'late_detected' => 0,
            'notifications_sent' => 0,
            'errors' => []
        ];

        try {
            $ongoingBookings = Booking::where('status', 'ongoing')
                ->where('end_at', '<=', now()->addHours(2)) // Check bookings ending within 2 hours
                ->with(['customer.user', 'vendor.user', 'car', 'lateReturnReport'])
                ->get();

            foreach ($ongoingBookings as $booking) {
                $results['checked']++;
                
                $detection = $this->detectLateReturn($booking);
                
                if ($detection['is_late'] || $detection['will_be_late']) {
                    $results['late_detected']++;
                    
                    if ($this->handleLateDetection($booking, $detection)) {
                        $results['notifications_sent']++;
                    }
                }
            }

            Log::info('Auto late detection completed', $results);
            return $results;

        } catch (\Throwable $e) {
            $error = 'Auto late detection failed: ' . $e->getMessage();
            Log::error($error, ['exception' => $e]);
            $results['errors'][] = $error;
            
            return $results;
        }
    }

    /**
     * Deteksi keterlambatan untuk booking spesifik
     */
    public function detectLateReturn(Booking $booking): array
    {
        $now = now();
        $endTime = $booking->end_at;
        $toleranceMinutes = 60; // 1 jam toleransi

        $detection = [
            'is_late' => false,
            'will_be_late' => false,
            'severity' => 'none', // none, warning, critical
            'estimated_late_hours' => 0,
            'confidence' => 0,
            'reasons' => [],
            'recommendations' => [],
            'metadata' => []
        ];

        try {
            // 1. Basic time-based detection
            $minutesOverdue = $now->diffInMinutes($endTime, false);
            
            if ($minutesOverdue < -$toleranceMinutes) {
                // Already late
                $detection['is_late'] = true;
                $detection['estimated_late_hours'] = ceil(abs($minutesOverdue) / 60);
                $detection['severity'] = abs($minutesOverdue) > 180 ? 'critical' : 'warning';
                $detection['reasons'][] = 'Melewati batas waktu pengembalian + toleransi';
                $detection['confidence'] = 95;
            } elseif ($minutesOverdue <= 120) {
                // Will be late soon (within 2 hours)
                $detection['will_be_late'] = true;
                $detection['severity'] = 'warning';
                $detection['reasons'][] = 'Mendekati batas waktu pengembalian';
                $detection['confidence'] = 70;
            }

            // 2. GPS-based prediction (if available)
            if ($booking->lateReturnReport?->hasLocation()) {
                $gpsAnalysis = $this->analyzeGpsBasedPrediction($booking);
                $detection['metadata']['gps_analysis'] = $gpsAnalysis;
                
                if ($gpsAnalysis['will_be_late']) {
                    $detection['will_be_late'] = true;
                    $detection['estimated_late_hours'] = max(
                        $detection['estimated_late_hours'], 
                        $gpsAnalysis['estimated_late_hours']
                    );
                    $detection['reasons'][] = $gpsAnalysis['reason'];
                    $detection['confidence'] = max($detection['confidence'], $gpsAnalysis['confidence']);
                }
            }

            // 3. Traffic-based prediction
            $trafficAnalysis = $this->analyzeTrafficImpact($booking);
            if ($trafficAnalysis['has_delay']) {
                $detection['will_be_late'] = true;
                $detection['reasons'][] = $trafficAnalysis['reason'];
                $detection['confidence'] = max($detection['confidence'], 60);
                $detection['metadata']['traffic'] = $trafficAnalysis;
            }

            // 4. Historical pattern analysis
            $patternAnalysis = $this->analyzeHistoricalPatterns($booking);
            if ($patternAnalysis['high_risk']) {
                $detection['confidence'] += 15;
                $detection['reasons'][] = $patternAnalysis['reason'];
                $detection['metadata']['pattern'] = $patternAnalysis;
            }

            // 5. Generate recommendations
            $detection['recommendations'] = $this->generateRecommendations($booking, $detection);

            return $detection;

        } catch (\Throwable $e) {
            Log::error('Late detection analysis failed', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage()
            ]);

            return $detection;
        }
    }

    /**
     * Analisis prediksi berdasarkan GPS customer
     */
    private function analyzeGpsBasedPrediction(Booking $booking): array
    {
        $report = $booking->lateReturnReport;
        
        if (!$report || !$report->hasLocation()) {
            return ['will_be_late' => false];
        }

        try {
            // Calculate distance from current location to return point
            $vendorLat = $booking->vendor->latitude ?? $booking->car->latitude;
            $vendorLng = $booking->vendor->longitude ?? $booking->car->longitude;

            if (!$vendorLat || !$vendorLng) {
                return ['will_be_late' => false, 'reason' => 'Lokasi vendor tidak tersedia'];
            }

            $distance = $this->gpsValidator->calculateDistance(
                $report->latitude, $report->longitude,
                $vendorLat, $vendorLng
            );

            // Estimate travel time (average city speed ~20km/h including traffic)
            $estimatedTravelHours = $distance / 20;
            $timeUntilDeadline = now()->diffInHours($booking->end_at, false);

            if ($estimatedTravelHours > $timeUntilDeadline) {
                $lateHours = ceil($estimatedTravelHours - $timeUntilDeadline);
                
                return [
                    'will_be_late' => true,
                    'estimated_late_hours' => $lateHours,
                    'reason' => "Jarak {$distance}km membutuhkan ~{$estimatedTravelHours}jam perjalanan",
                    'confidence' => min(85, 50 + ($lateHours * 10)), // Higher confidence for longer delays
                    'distance_km' => round($distance, 2),
                    'estimated_travel_hours' => round($estimatedTravelHours, 1)
                ];
            }

            return [
                'will_be_late' => false,
                'distance_km' => round($distance, 2),
                'estimated_travel_hours' => round($estimatedTravelHours, 1)
            ];

        } catch (\Throwable $e) {
            Log::warning('GPS prediction analysis failed', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage()
            ]);

            return ['will_be_late' => false];
        }
    }

    /**
     * Analisis dampak traffic terhadap keterlambatan
     */
    private function analyzeTrafficImpact(Booking $booking): array
    {
        try {
            return $this->trafficAnalysis->analyzeTrafficDelayRisk($booking);
        } catch (\Throwable $e) {
            Log::warning('Traffic analysis failed', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage()
            ]);

            return ['has_delay' => false];
        }
    }

    /**
     * Analisis pola historis customer/vendor
     */
    private function analyzeHistoricalPatterns(Booking $booking): array
    {
        try {
            // Check customer's late return history
            $customerLateCount = Booking::where('customer_id', $booking->customer_id)
                ->where('status', 'completed')
                ->where('is_late', true)
                ->where('created_at', '>=', now()->subMonths(6))
                ->count();

            $customerTotalBookings = Booking::where('customer_id', $booking->customer_id)
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->subMonths(6))
                ->count();

            if ($customerTotalBookings >= 3) {
                $lateRate = $customerLateCount / $customerTotalBookings;
                
                if ($lateRate >= 0.5) { // 50% late rate
                    return [
                        'high_risk' => true,
                        'reason' => "Customer sering terlambat ({$customerLateCount}/{$customerTotalBookings} booking terakhir)",
                        'late_rate' => round($lateRate * 100),
                        'confidence_boost' => 20
                    ];
                }
            }

            // Check time/day patterns
            $currentHour = now()->hour;
            $currentDay = now()->dayOfWeek;

            // Rush hour analysis
            if (in_array($currentHour, [7, 8, 17, 18, 19])) {
                return [
                    'high_risk' => true,
                    'reason' => 'Jam sibuk - risiko traffic tinggi',
                    'confidence_boost' => 10
                ];
            }

            // Weekend patterns
            if (in_array($currentDay, [0, 6])) { // Sunday, Saturday
                return [
                    'high_risk' => false,
                    'reason' => 'Akhir pekan - traffic relatif lancar',
                    'confidence_boost' => -5
                ];
            }

            return ['high_risk' => false];

        } catch (\Throwable $e) {
            Log::warning('Historical pattern analysis failed', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage()
            ]);

            return ['high_risk' => false];
        }
    }

    /**
     * Generate actionable recommendations
     */
    private function generateRecommendations(Booking $booking, array $detection): array
    {
        $recommendations = [];

        if ($detection['is_late']) {
            $recommendations[] = 'Hubungi customer segera untuk konfirmasi status pengembalian';
            $recommendations[] = 'Siapkan perhitungan denda keterlambatan';
            
            if ($detection['estimated_late_hours'] >= 4) {
                $recommendations[] = 'Pertimbangkan untuk menghubungi customer via telepon';
                $recommendations[] = 'Periksa apakah ada customer lain yang membutuhkan mobil ini';
            }
        } elseif ($detection['will_be_late']) {
            $recommendations[] = 'Kirim reminder ke customer tentang batas waktu pengembalian';
            $recommendations[] = 'Berikan informasi traffic/rute alternatif jika tersedia';
            
            if ($detection['confidence'] >= 80) {
                $recommendations[] = 'Siap-siap untuk potensial late return - informasikan ke customer berikutnya jika ada';
            }
        }

        // GPS-specific recommendations
        if (isset($detection['metadata']['gps_analysis']['distance_km'])) {
            $distance = $detection['metadata']['gps_analysis']['distance_km'];
            if ($distance > 50) {
                $recommendations[] = 'Customer berada >50km dari lokasi return - berikan estimasi waktu perjalanan';
            }
        }

        // Traffic-specific recommendations
        if (isset($detection['metadata']['traffic']['severity']) && $detection['metadata']['traffic']['severity'] === 'high') {
            $recommendations[] = 'Traffic padat terdeteksi - sarankan rute alternatif ke customer';
        }

        return $recommendations;
    }

    /**
     * Handle late detection results - create reports and send notifications
     */
    private function handleLateDetection(Booking $booking, array $detection): bool
    {
        try {
            // Create or update auto-generated late return report
            $existingReport = $booking->lateReturnReport;
            
            if (!$existingReport || $existingReport->reporter_type !== 'system') {
                // Create new system-generated report
                LateReturnReport::create([
                    'booking_id' => $booking->id,
                    'reporter_type' => 'system',
                    'reported_by_user_id' => 1, // System user ID
                    'estimated_late_hours' => $detection['estimated_late_hours'],
                    'reason' => 'Deteksi otomatis: ' . implode(', ', $detection['reasons']),
                    'status' => 'reported',
                    'auto_detection_data' => json_encode($detection)
                ]);
            }

            // Send notifications to vendor
            try {
                $booking->vendor?->user?->notify(
                    new AutoLateDetectionNotification($booking, $detection)
                );
                return true;
            } catch (\Throwable $e) {
                Log::error('Failed to send auto late detection notification', [
                    'booking_id' => $booking->id,
                    'exception' => $e->getMessage()
                ]);
                return false;
            }

        } catch (\Throwable $e) {
            Log::error('Failed to handle late detection', [
                'booking_id' => $booking->id,
                'detection' => $detection,
                'exception' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get summary statistics for dashboard
     */
    public function getDetectionStats(Carbon $since = null): array
    {
        $since = $since ?? now()->subDays(7);

        try {
            $totalOngoing = Booking::where('status', 'ongoing')->count();
            $potentialLateBookings = Booking::where('status', 'ongoing')
                ->where('end_at', '<=', now()->addHours(4))
                ->count();

            $recentLateDetections = LateReturnReport::where('reporter_type', 'system')
                ->where('created_at', '>=', $since)
                ->count();

            $accuracyData = $this->calculateDetectionAccuracy($since);

            return [
                'total_ongoing_bookings' => $totalOngoing,
                'potential_late_bookings' => $potentialLateBookings,
                'recent_auto_detections' => $recentLateDetections,
                'detection_accuracy' => $accuracyData['accuracy'],
                'true_positives' => $accuracyData['true_positives'],
                'false_positives' => $accuracyData['false_positives'],
                'last_updated' => now()->toISOString()
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to get detection stats', ['exception' => $e->getMessage()]);
            
            return [
                'total_ongoing_bookings' => 0,
                'potential_late_bookings' => 0,
                'recent_auto_detections' => 0,
                'detection_accuracy' => 0,
                'error' => 'Failed to load statistics'
            ];
        }
    }

    /**
     * Calculate accuracy of auto detection system
     */
    private function calculateDetectionAccuracy(Carbon $since): array
    {
        try {
            // Find completed bookings that had auto detection reports
            $autoDetectedBookings = Booking::whereHas('lateReturnReport', function ($q) use ($since) {
                $q->where('reporter_type', 'system')
                  ->where('created_at', '>=', $since);
            })
            ->where('status', 'completed')
            ->with('lateReturnReport')
            ->get();

            $truePositives = 0;
            $falsePositives = 0;

            foreach ($autoDetectedBookings as $booking) {
                if ($booking->is_late) {
                    $truePositives++; // Correctly predicted late
                } else {
                    $falsePositives++; // Incorrectly predicted late
                }
            }

            $total = $truePositives + $falsePositives;
            $accuracy = $total > 0 ? ($truePositives / $total) * 100 : 0;

            return [
                'accuracy' => round($accuracy, 1),
                'true_positives' => $truePositives,
                'false_positives' => $falsePositives,
                'total_predictions' => $total
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to calculate detection accuracy', ['exception' => $e->getMessage()]);
            
            return [
                'accuracy' => 0,
                'true_positives' => 0,
                'false_positives' => 0,
                'total_predictions' => 0
            ];
        }
    }
}