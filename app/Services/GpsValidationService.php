<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GpsValidationService
{
    /**
     * Validasi lokasi GPS untuk mencegah fraud dan memastikan akurasi
     */
    public function validateLocation(
        float $latitude,
        float $longitude,
        ?Booking $booking = null,
        string $context = 'report'
    ): array {
        $validations = [
            'is_valid' => false,
            'accuracy_score' => 0,
            'warnings' => [],
            'errors' => [],
            'metadata' => []
        ];

        try {
            // 1. Basic coordinate validation
            if (!$this->isValidCoordinate($latitude, $longitude)) {
                $validations['errors'][] = 'Koordinat GPS tidak valid';
                return $validations;
            }

            // 2. Indonesia bounds check
            if (!$this->isInIndonesiaBounds($latitude, $longitude)) {
                $validations['errors'][] = 'Lokasi di luar Indonesia';
                return $validations;
            }

            // 3. Mock GPS detection (basic heuristics)
            $mockDetection = $this->detectMockGps($latitude, $longitude);
            if ($mockDetection['is_mock']) {
                $validations['warnings'][] = 'Kemungkinan lokasi palsu terdeteksi';
                $validations['accuracy_score'] -= 30;
                $validations['metadata']['mock_indicators'] = $mockDetection['indicators'];
            }

            // 4. Geofencing validation untuk booking context
            if ($booking) {
                $geofenceResult = $this->validateGeofencing($latitude, $longitude, $booking, $context);
                $validations['accuracy_score'] += $geofenceResult['score'];
                $validations['warnings'] = array_merge($validations['warnings'], $geofenceResult['warnings']);
                $validations['metadata']['geofence'] = $geofenceResult['data'];
            }

            // 5. Reverse geocoding & address validation
            $addressValidation = $this->validateAddress($latitude, $longitude);
            if ($addressValidation['is_valid']) {
                $validations['accuracy_score'] += 20;
                $validations['metadata']['address'] = $addressValidation['address'];
            } else {
                $validations['warnings'][] = 'Alamat tidak dapat diverifikasi';
            }

            // 6. Calculate final score
            $validations['accuracy_score'] = max(0, min(100, $validations['accuracy_score'] + 50)); // Base 50
            $validations['is_valid'] = $validations['accuracy_score'] >= 60 && empty($validations['errors']);

            return $validations;

        } catch (\Throwable $e) {
            Log::error('GPS validation error', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'booking_id' => $booking?->id,
                'exception' => $e->getMessage()
            ]);

            return [
                'is_valid' => false,
                'accuracy_score' => 0,
                'warnings' => ['Gagal memvalidasi lokasi GPS'],
                'errors' => ['Error sistem validasi GPS'],
                'metadata' => []
            ];
        }
    }

    /**
     * Basic coordinate validation
     */
    private function isValidCoordinate(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    /**
     * Check if coordinates are within Indonesia bounds
     */
    private function isInIndonesiaBounds(float $lat, float $lng): bool
    {
        // Indonesia approximate bounding box
        return $lat >= -11.0 && $lat <= 6.0 && $lng >= 95.0 && $lng <= 141.0;
    }

    /**
     * Detect potential mock/fake GPS using heuristics
     */
    private function detectMockGps(float $lat, float $lng): array
    {
        $indicators = [];
        $isMock = false;

        // 1. Check for exact coordinates (too precise)
        if ($this->hasSuspiciousPrecision($lat, $lng)) {
            $indicators[] = 'Koordinat terlalu presisi (kemungkinan fake)';
            $isMock = true;
        }

        // 2. Check for common fake locations
        if ($this->isCommonFakeLocation($lat, $lng)) {
            $indicators[] = 'Lokasi umum yang sering digunakan untuk fake GPS';
            $isMock = true;
        }

        // 3. Check for impossible accuracy (center of city, etc.)
        if ($this->isImpossibleAccuracy($lat, $lng)) {
            $indicators[] = 'Lokasi terlalu akurat (pusat kota/landmark terkenal)';
            $isMock = true;
        }

        return [
            'is_mock' => $isMock,
            'indicators' => $indicators,
            'confidence' => count($indicators) * 25 // 0-100%
        ];
    }

    /**
     * Check if coordinates have suspicious precision (common in fake GPS)
     */
    private function hasSuspiciousPrecision(float $lat, float $lng): bool
    {
        // Check if coordinates are exactly round numbers or have suspicious patterns
        $latStr = (string) $lat;
        $lngStr = (string) $lng;

        // Exactly round numbers
        if (strpos($latStr, '.000000') !== false || strpos($lngStr, '.000000') !== false) {
            return true;
        }

        // Repeating patterns like -6.200000, 106.800000
        if (preg_match('/\.\d{1,2}0{4,}$/', $latStr) || preg_match('/\.\d{1,2}0{4,}$/', $lngStr)) {
            return true;
        }

        return false;
    }

    /**
     * Check for commonly used fake GPS locations
     */
    private function isCommonFakeLocation(float $lat, float $lng): bool
    {
        $commonFakeLocations = [
            // Jakarta center points
            [-6.200000, 106.816666],
            [-6.175110, 106.865036], // Monas area
            [-6.208763, 106.845599], // Bundaran HI area
            
            // Common GPS testing locations
            [0.0, 0.0], // Null Island
            [-6.0, 106.0], // Round Jakarta coordinates
        ];

        $tolerance = 0.001; // ~111 meters

        foreach ($commonFakeLocations as [$fakeLat, $fakeLng]) {
            if (abs($lat - $fakeLat) < $tolerance && abs($lng - $fakeLng) < $tolerance) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for impossibly accurate locations (landmarks, etc.)
     */
    private function isImpossibleAccuracy(float $lat, float $lng): bool
    {
        // Check if location is exactly on major landmarks (suspicious)
        $landmarks = [
            // Major landmarks that are commonly used in fake GPS
            [-6.175110, 106.827153], // Monas exact center
            [-6.200000, 106.816666], // Exact Jakarta center
            [-6.914744, 107.609810], // Bandung center
            [-7.797068, 110.370529], // Yogyakarta center
        ];

        $exactTolerance = 0.0001; // ~11 meters (too accurate for normal GPS)

        foreach ($landmarks as [$landmarkLat, $landmarkLng]) {
            if (abs($lat - $landmarkLat) < $exactTolerance && abs($lng - $landmarkLng) < $exactTolerance) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate geofencing based on booking context
     */
    private function validateGeofencing(float $lat, float $lng, Booking $booking, string $context): array
    {
        $result = [
            'score' => 0,
            'warnings' => [],
            'data' => []
        ];

        try {
            // Get vendor/pickup location for reference
            $vendorLat = $booking->vendor->latitude ?? $booking->car->latitude;
            $vendorLng = $booking->vendor->longitude ?? $booking->car->longitude;

            if (!$vendorLat || !$vendorLng) {
                $result['warnings'][] = 'Tidak ada lokasi vendor untuk validasi geofencing';
                return $result;
            }

            $distance = $this->calculateDistance($lat, $lng, $vendorLat, $vendorLng);
            $result['data']['distance_to_vendor_km'] = round($distance, 2);

            // Context-specific validation
            switch ($context) {
                case 'report':
                    // Late return report - customer should be within reasonable distance from vendor
                    if ($distance <= 50) { // Within 50km - reasonable for city area
                        $result['score'] += 30;
                    } elseif ($distance <= 100) { // Within 100km - acceptable
                        $result['score'] += 15;
                    } else { // Beyond 100km - suspicious but not invalid
                        $result['warnings'][] = "Lokasi cukup jauh dari vendor ({$distance} km)";
                        $result['score'] -= 10;
                    }
                    break;

                case 'return_confirmation':
                    // Return confirmation - should be close to pickup location
                    if ($distance <= 5) { // Within 5km - very good
                        $result['score'] += 40;
                    } elseif ($distance <= 15) { // Within 15km - acceptable
                        $result['score'] += 20;
                    } elseif ($distance <= 50) { // Within 50km - okay but far
                        $result['score'] += 5;
                        $result['warnings'][] = "Pengembalian agak jauh dari lokasi pickup ({$distance} km)";
                    } else { // Beyond 50km - very suspicious
                        $result['warnings'][] = "Lokasi pengembalian sangat jauh dari pickup ({$distance} km)";
                        $result['score'] -= 20;
                    }
                    break;
            }

            return $result;

        } catch (\Throwable $e) {
            Log::error('Geofencing validation error', [
                'booking_id' => $booking->id,
                'context' => $context,
                'exception' => $e->getMessage()
            ]);

            return $result;
        }
    }

    /**
     * Calculate distance between two GPS points in kilometers (Haversine formula)
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Earth radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    /**
     * Validate address through reverse geocoding
     */
    private function validateAddress(float $lat, float $lng): array
    {
        try {
            // Use free OpenStreetMap Nominatim for reverse geocoding
            $response = Http::timeout(10)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'json',
                    'lat' => $lat,
                    'lon' => $lng,
                    'zoom' => 18,
                    'addressdetails' => 1,
                    'accept-language' => 'id,en'
                ]);

            if ($response->successful() && $response->json()) {
                $data = $response->json();
                
                return [
                    'is_valid' => true,
                    'address' => $data['display_name'] ?? 'Alamat tidak ditemukan',
                    'city' => $data['address']['city'] ?? $data['address']['town'] ?? $data['address']['village'] ?? 'Unknown',
                    'country' => $data['address']['country'] ?? 'Unknown'
                ];
            }

            return ['is_valid' => false];

        } catch (\Throwable $e) {
            Log::warning('Reverse geocoding failed', [
                'lat' => $lat,
                'lng' => $lng,
                'exception' => $e->getMessage()
            ]);

            return ['is_valid' => false];
        }
    }

    /**
     * Generate GPS validation summary for display
     */
    public function getValidationSummary(array $validation): string
    {
        if (!$validation['is_valid']) {
            $errors = implode(', ', $validation['errors']);
            return "❌ Lokasi tidak valid: {$errors}";
        }

        $score = $validation['accuracy_score'];
        $icon = $score >= 80 ? '✅' : ($score >= 60 ? '⚠️' : '❌');
        
        $summary = "{$icon} Akurasi: {$score}%";
        
        if (!empty($validation['warnings'])) {
            $warnings = implode(', ', array_slice($validation['warnings'], 0, 2)); // Max 2 warnings
            $summary .= " | Peringatan: {$warnings}";
        }

        return $summary;
    }
}