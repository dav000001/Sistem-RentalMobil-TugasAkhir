<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\LiveTrackingService;
use App\Services\GpsValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class LiveTrackingController extends Controller
{
    private LiveTrackingService $liveTracking;
    private GpsValidationService $gpsValidator;

    public function __construct(LiveTrackingService $liveTracking, GpsValidationService $gpsValidator)
    {
        $this->liveTracking = $liveTracking;
        $this->gpsValidator = $gpsValidator;
    }

    /**
     * Record GPS tracking point for ongoing booking
     * POST /api/v1/bookings/{booking}/tracking/record
     */
    public function recordTrackingPoint(Request $request, Booking $booking): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude'  => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'address'   => 'nullable|string|max:500',
                'accuracy'  => 'nullable|numeric|min:0',
                'heading'   => 'nullable|numeric|between:0,359',
                'speed'     => 'nullable|numeric|min:0',
                'altitude'  => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid GPS data',
                    'validation_errors' => $validator->errors(),
                ], 400);
            }

            $user = auth()->user();

            if (!$user || $booking->customer->user_id !== $user->id) {
                return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
            }

            if ($booking->status !== 'ongoing') {
                return response()->json(['success' => false, 'error' => 'Tracking only available for ongoing bookings'], 400);
            }

            if (!$booking->customer->tracking_enabled) {
                return response()->json(['success' => false, 'error' => 'Live tracking is disabled'], 400);
            }

            $metadata = [
                'device_accuracy' => $request->input('accuracy'),
                'heading'         => $request->input('heading'),
                'speed_kmh'       => $request->input('speed'),
                'altitude'        => $request->input('altitude'),
                'ip_address'      => $request->ip(),
            ];

            $result = $this->liveTracking->recordTrackingPoint(
                $booking,
                $request->input('latitude'),
                $request->input('longitude'),
                $request->input('address'),
                $metadata
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Throwable $e) {
            \Log::error('recordTrackingPoint failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Server error'], 500);
        }
    }

    /**
     * Get live tracking data for booking
     * GET /api/bookings/{booking}/tracking
     */
    public function getTrackingData(Request $request, Booking $booking): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Check authorization - customer or vendor can view tracking
            if (!$user || ($booking->customer->user_id !== $user->id && $booking->vendor->user_id !== $user->id)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to view tracking for this booking'
                ], 403);
            }

            // Get tracking data
            $trackingData = $this->liveTracking->getLiveTrackingData($booking);

            // Filter sensitive data based on user role
            if ($booking->customer->user_id === $user->id) {
                // Customer can see all their own tracking data
                $responseData = $trackingData;
            } else {
                // Vendor gets limited tracking data
                $responseData = [
                    'booking_id' => $trackingData['booking_id'],
                    'is_being_tracked' => $trackingData['is_being_tracked'],
                    'last_update' => $trackingData['last_update'] ?? null,
                    'current_location' => $trackingData['current_location'] ?? null,
                    'statistics' => $trackingData['statistics'] ?? [],
                    'insights' => $trackingData['insights'] ?? []
                ];
                
                // Only include recent points (last 2 hours) for vendor
                if (isset($trackingData['recent_points'])) {
                    $twoHoursAgo = now()->subHours(2);
                    $responseData['recent_points'] = array_filter(
                        $trackingData['recent_points'],
                        fn($point) => \Carbon\Carbon::parse($point['recorded_at']) >= $twoHoursAgo
                    );
                }
            }

            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to get tracking data via API', [
                'booking_id' => $booking->id,
                'user_id' => auth()->user()?->id,
                'exception' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get tracking data'
            ], 500);
        }
    }

    /**
     * Toggle live tracking on/off for booking
     * POST /api/bookings/{booking}/tracking/toggle
     */
    public function toggleTracking(Request $request, Booking $booking): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'enabled' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid request data',
                    'validation_errors' => $validator->errors()
                ], 400);
            }

            $user = auth()->user();
            
            // Only customer can toggle their own tracking
            if (!$user || $booking->customer->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to modify tracking settings'
                ], 403);
            }

            $enabled = $request->input('enabled');
            $result = $this->liveTracking->toggleTracking($booking, $enabled);

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'tracking_enabled' => $result['tracking_enabled'],
                    'booking_id' => $booking->id,
                    'updated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to toggle tracking via API', [
                'booking_id' => $booking->id,
                'user_id' => auth()->user()?->id,
                'exception' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to toggle tracking'
            ], 500);
        }
    }

    /**
     * Get tracking journey summary
     * GET /api/bookings/{booking}/tracking/journey
     */
    public function getTrackingJourney(Request $request, Booking $booking): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Check authorization
            if (!$user || ($booking->customer->user_id !== $user->id && $booking->vendor->user_id !== $user->id)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to view tracking journey'
                ], 403);
            }

            $journey = $this->liveTracking->getTrackingJourney($booking);

            return response()->json([
                'success' => true,
                'data' => $journey
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to get tracking journey via API', [
                'booking_id' => $booking->id,
                'user_id' => auth()->user()?->id,
                'exception' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get tracking journey'
            ], 500);
        }
    }

    /**
     * Validate GPS location (for testing/debugging)
     * POST /api/gps/validate
     */
    public function validateGps(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'booking_id' => 'nullable|exists:bookings,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid GPS coordinates',
                    'validation_errors' => $validator->errors()
                ], 400);
            }

            $booking = null;
            if ($request->has('booking_id')) {
                $booking = Booking::find($request->input('booking_id'));
                
                // Check authorization for booking context
                $user = auth()->user();
                if ($booking && $user && 
                    $booking->customer->user_id !== $user->id && 
                    $booking->vendor->user_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Unauthorized access to booking'
                    ], 403);
                }
            }

            $validation = $this->gpsValidator->validateLocation(
                $request->input('latitude'),
                $request->input('longitude'),
                $booking,
                'api_validation'
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'validation' => $validation,
                    'summary' => $this->gpsValidator->getValidationSummary($validation),
                    'coordinates' => [
                        'latitude' => $request->input('latitude'),
                        'longitude' => $request->input('longitude')
                    ]
                ]
            ]);

        } catch (\Throwable $e) {
            \Log::error('GPS validation API failed', [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'exception' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'GPS validation failed'
            ], 500);
        }
    }
}
