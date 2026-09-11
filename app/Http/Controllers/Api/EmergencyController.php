<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\EmergencyReport;
use App\Services\SmartRoutingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class EmergencyController extends Controller
{
    private SmartRoutingService $smartRouting;

    public function __construct(SmartRoutingService $smartRouting)
    {
        $this->smartRouting = $smartRouting;
    }

    /**
     * Create emergency report
     * POST /api/bookings/{booking}/emergency
     */
    public function createEmergencyReport(Request $request, Booking $booking): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:breakdown,accident,theft,harassment,medical,other',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'description' => 'nullable|string|max:1000',
                'urgency_level' => 'nullable|in:low,medium,high,critical'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid emergency data',
                    'validation_errors' => $validator->errors()
                ], 400);
            }

            $user = auth()->user();
            
            // Check authorization - only customer of this booking can create emergency report
            if (!$user || $booking->customer->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to create emergency report for this booking'
                ], 403);
            }

            // Check if booking is ongoing
            if ($booking->status !== 'ongoing') {
                return response()->json([
                    'success' => false,
                    'error' => 'Emergency reports can only be created for ongoing bookings'
                ], 400);
            }

            // Check for existing active emergency
            $existingEmergency = EmergencyReport::where('booking_id', $booking->id)
                ->where('status', 'active')
                ->first();

            if ($existingEmergency) {
                return response()->json([
                    'success' => false,
                    'error' => 'An active emergency already exists for this booking',
                    'existing_emergency' => [
                        'id' => $existingEmergency->id,
                        'type' => $existingEmergency->type,
                        'emergency_code' => $existingEmergency->generateEmergencyCode(),
                        'reported_at' => $existingEmergency->reported_at->toISOString()
                    ]
                ], 409);
            }

            // Create emergency report
            $result = $this->smartRouting->createEmergencyReport(
                $booking,
                $request->input('type'),
                $request->input('latitude'),
                $request->input('longitude'),
                $request->input('description')
            );

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'emergency_id' => $result['emergency_id'],
                    'emergency_code' => $result['emergency_code'],
                    'next_steps' => $result['next_steps'],
                    'created_at' => now()->toISOString()
                ]
            ], 201);

        } catch (\Throwable $e) {
            \Log::error('Failed to create emergency report via API', [
                'booking_id' => $booking->id,
                'user_id' => auth()->user()?->id,
                'exception' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create emergency report'
            ], 500);
        }
    }

    /**
     * Get emergency reports for booking
     * GET /api/bookings/{booking}/emergency
     */
    public function getEmergencyReports(Request $request, Booking $booking): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Check authorization - customer, vendor, or admin can view
            if (!$user || ($booking->customer->user_id !== $user->id && 
                         $booking->vendor->user_id !== $user->id && 
                         !$user->hasRole('admin'))) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to view emergency reports for this booking'
                ], 403);
            }

            $emergencies = EmergencyReport::where('booking_id', $booking->id)
                ->orderBy('reported_at', 'desc')
                ->get()
                ->map(function ($emergency) {
                    return [
                        'id' => $emergency->id,
                        'emergency_code' => $emergency->generateEmergencyCode(),
                        'type' => $emergency->type,
                        'type_label' => $emergency->getTypeLabel(),
                        'status' => $emergency->status,
                        'status_label' => $emergency->getStatusLabel(),
                        'priority_level' => $emergency->getPriorityLevel(),
                        'description' => $emergency->description,
                        'location' => [
                            'latitude' => $emergency->latitude,
                            'longitude' => $emergency->longitude,
                            'map_url' => $emergency->getMapUrl()
                        ],
                        'resolution' => $emergency->resolution,
                        'response_time' => $emergency->getResponseTimeFormatted(),
                        'reported_at' => $emergency->reported_at->toISOString(),
                        'resolved_at' => $emergency->resolved_at?->toISOString(),
                        'is_active' => $emergency->isActive(),
                        'is_resolved' => $emergency->isResolved()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'booking_id' => $booking->id,
                    'emergencies' => $emergencies,
                    'active_count' => $emergencies->where('is_active', true)->count(),
                    'total_count' => $emergencies->count()
                ]
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to get emergency reports via API', [
                'booking_id' => $booking->id,
                'user_id' => auth()->user()?->id,
                'exception' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get emergency reports'
            ], 500);
        }
    }

    /**
     * Update emergency status (for vendors/admins)
     * PUT /api/emergency/{emergency}/status
     */
    public function updateEmergencyStatus(Request $request, EmergencyReport $emergency): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:active,responding,resolved,closed',
                'resolution' => 'nullable|string|max:1000|required_if:status,resolved,closed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid status update data',
                    'validation_errors' => $validator->errors()
                ], 400);
            }

            $user = auth()->user();
            
            // Check authorization - vendor, admin, or emergency responder can update
            if (!$user || ($emergency->vendor->user_id !== $user->id && !$user->hasRole(['admin', 'emergency_responder']))) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to update emergency status'
                ], 403);
            }

            $newStatus = $request->input('status');
            $resolution = $request->input('resolution');

            // Validate status transition
            $validTransitions = $this->getValidStatusTransitions($emergency->status);
            if (!in_array($newStatus, $validTransitions)) {
                return response()->json([
                    'success' => false,
                    'error' => "Cannot change status from '{$emergency->status}' to '{$newStatus}'"
                ], 400);
            }

            $result = $this->smartRouting->updateEmergencyStatus(
                $emergency->id,
                $newStatus,
                $resolution
            );

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            // Refresh emergency data
            $emergency->refresh();

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'emergency_id' => $emergency->id,
                    'emergency_code' => $emergency->generateEmergencyCode(),
                    'old_status' => $result['previous_status'] ?? null,
                    'new_status' => $emergency->status,
                    'status_label' => $emergency->getStatusLabel(),
                    'resolution' => $emergency->resolution,
                    'updated_at' => $emergency->updated_at->toISOString(),
                    'updated_by' => $user->name
                ]
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to update emergency status via API', [
                'emergency_id' => $emergency->id,
                'user_id' => auth()->user()?->id,
                'exception' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update emergency status'
            ], 500);
        }
    }

    /**
     * Get emergency statistics (for admins)
     * GET /api/emergency/stats
     */
    public function getEmergencyStats(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Only admins and emergency responders can view stats
            if (!$user || !$user->hasRole(['admin', 'emergency_responder'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to view emergency statistics'
                ], 403);
            }

            $stats = $this->smartRouting->getEmergencyStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to get emergency stats via API', [
                'user_id' => auth()->user()?->id,
                'exception' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get emergency statistics'
            ], 500);
        }
    }

    /**
     * Get smart routing recommendations
     * POST /api/bookings/{booking}/routing/recommendations
     */
    public function getRoutingRecommendations(Request $request, Booking $booking): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_latitude' => 'required|numeric|between:-90,90',
                'current_longitude' => 'required|numeric|between:-180,180'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid location data',
                    'validation_errors' => $validator->errors()
                ], 400);
            }

            $user = auth()->user();
            
            // Check authorization - customer or vendor can get routing
            if (!$user || ($booking->customer->user_id !== $user->id && $booking->vendor->user_id !== $user->id)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to get routing for this booking'
                ], 403);
            }

            $recommendations = $this->smartRouting->getReturnRouteRecommendations(
                $booking,
                $request->input('current_latitude'),
                $request->input('current_longitude')
            );

            if (!$recommendations['success']) {
                return response()->json($recommendations, 400);
            }

            return response()->json([
                'success' => true,
                'data' => $recommendations
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to get routing recommendations via API', [
                'booking_id' => $booking->id,
                'user_id' => auth()->user()?->id,
                'exception' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get routing recommendations'
            ], 500);
        }
    }

    /**
     * Get valid status transitions for emergency
     */
    private function getValidStatusTransitions(string $currentStatus): array
    {
        return match($currentStatus) {
            'active' => ['responding', 'resolved', 'closed'],
            'responding' => ['resolved', 'closed', 'active'], // Can go back to active if needed
            'resolved' => ['closed', 'responding'], // Can reopen if issue recurs
            'closed' => [], // Final state, no transitions allowed
            default => []
        };
    }
}
