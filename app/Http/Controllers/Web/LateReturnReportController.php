<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\LateReturnReport;
use App\Services\GpsValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class LateReturnReportController extends Controller
{
    /**
     * Opsi A — Customer lapor akan terlambat + kirim lokasi GPS saat ini
     */
    public function store(Request $request, Booking $booking)
    {
        Gate::authorize('view', $booking);

        abort_unless($booking->status === 'ongoing', 422, 'Booking tidak dalam status berlangsung.');
        abort_unless(
            $booking->customer_id === auth()->user()?->customer?->id,
            403
        );

        // Cegah lapor duplikat
        if ($booking->lateReturnReport) {
            return back()->with('info', 'Laporan keterlambatan sudah pernah dikirim sebelumnya.');
        }

        $validated = $request->validate([
            'estimated_late_hours' => ['required', 'integer', 'min:1', 'max:72'],
            'reason'               => ['required', 'string', 'max:500'],
            'latitude'             => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'            => ['nullable', 'numeric', 'between:-180,180'],
            'location_address'     => ['nullable', 'string', 'max:300'],
        ]);

        // Validasi GPS jika ada koordinat
        $gpsValidation = null;
        if ($validated['latitude'] && $validated['longitude']) {
            $gpsValidator = app(GpsValidationService::class);
            $gpsValidation = $gpsValidator->validateLocation(
                $validated['latitude'], 
                $validated['longitude'], 
                $booking, 
                'report'
            );

            // Log validasi GPS untuk monitoring
            Log::info('GPS validation for late return report', [
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'validation_result' => $gpsValidation
            ]);

            // Jika GPS sangat tidak valid, tolak laporan
            if (!empty($gpsValidation['errors'])) {
                return back()
                    ->withErrors(['gps' => 'Lokasi GPS tidak valid: ' . implode(', ', $gpsValidation['errors'])])
                    ->withInput();
            }

            // Jika ada warning GPS, tampilkan ke user tapi tetap lanjutkan
            if (!empty($gpsValidation['warnings'])) {
                session()->flash('gps_warnings', $gpsValidation['warnings']);
            }
        }

        $report = LateReturnReport::create([
            'booking_id'           => $booking->id,
            'reporter_type'        => 'customer',
            'reported_by_user_id'  => auth()->id(),
            'estimated_late_hours' => $validated['estimated_late_hours'],
            'reason'               => $validated['reason'],
            'latitude'             => $validated['latitude'] ?? null,
            'longitude'            => $validated['longitude'] ?? null,
            'location_address'     => $validated['location_address'] ?? null,
            'status'               => 'reported',
            'gps_validation_data'  => $gpsValidation ? json_encode($gpsValidation) : null,
        ]);

        // Cek apakah ada booking berikutnya yang bentrok jadwal karena keterlambatan ini
        $extendedEndAt = (clone $booking->end_at)->addHours((int) $validated['estimated_late_hours']);
        $conflictingBooking = Booking::where('car_id', $booking->car_id)
            ->where('id', '!=', $booking->id)
            ->whereIn('status', ['confirmed', 'ongoing', 'awaiting_vendor'])
            ->where('start_at', '<', $extendedEndAt)
            ->where('end_at', '>', $booking->end_at)
            ->first();

        // Notifikasi ke vendor (dengan info bentrok jika ada)
        try {
            $booking->vendor?->user?->notify(
                new \App\Notifications\LateReturnReportedToVendorNotification($report, $conflictingBooking)
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send late return notification to vendor', [
                'booking_id' => $booking->id,
                'report_id' => $report->id,
                'exception' => $e->getMessage()
            ]);
        }

        $message = 'Laporan keterlambatan berhasil dikirim ke vendor.';
        
        // Tambahkan pesan GPS jika ada warning
        if (!empty($gpsValidation['warnings'])) {
            $message .= ' Peringatan GPS: ' . implode(', ', array_slice($gpsValidation['warnings'], 0, 2));
        }

        return back()->with('success', $message);
    }

    /**
     * Opsi B — Customer konfirmasi pengembalian + kirim lokasi GPS saat ini
     */
    public function confirmReturn(Request $request, Booking $booking)
    {
        Gate::authorize('view', $booking);

        abort_unless($booking->status === 'ongoing', 422);
        abort_unless(
            $booking->customer_id === auth()->user()?->customer?->id,
            403
        );

        $validated = $request->validate([
            'return_latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'return_longitude'         => ['nullable', 'numeric', 'between:-180,180'],
            'return_location_address'  => ['nullable', 'string', 'max:300'],
        ]);

        // Validasi GPS pengembalian jika ada koordinat
        $gpsValidation = null;
        if ($validated['return_latitude'] && $validated['return_longitude']) {
            $gpsValidator = app(GpsValidationService::class);
            $gpsValidation = $gpsValidator->validateLocation(
                $validated['return_latitude'], 
                $validated['return_longitude'], 
                $booking, 
                'return_confirmation'
            );

            // Log validasi GPS
            Log::info('GPS validation for return confirmation', [
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'validation_result' => $gpsValidation
            ]);

            // Jika GPS sangat tidak valid, tolak konfirmasi
            if (!empty($gpsValidation['errors'])) {
                return back()
                    ->withErrors(['gps' => 'Lokasi GPS tidak valid: ' . implode(', ', $gpsValidation['errors'])])
                    ->withInput();
            }
        }

        $report = $booking->lateReturnReport;

        if ($report) {
            // Update laporan yang sudah ada
            $report->update([
                'return_latitude'         => $validated['return_latitude'] ?? null,
                'return_longitude'        => $validated['return_longitude'] ?? null,
                'return_location_address' => $validated['return_location_address'] ?? null,
                'return_confirmed_at'     => now(),
                'return_gps_validation'   => $gpsValidation ? json_encode($gpsValidation) : null,
            ]);
        } else {
            // Buat laporan baru khusus konfirmasi pengembalian
            $report = LateReturnReport::create([
                'booking_id'              => $booking->id,
                'reporter_type'           => 'customer',
                'reported_by_user_id'     => auth()->id(),
                'estimated_late_hours'    => 0,
                'reason'                  => 'Konfirmasi pengembalian oleh customer',
                'return_latitude'         => $validated['return_latitude'] ?? null,
                'return_longitude'        => $validated['return_longitude'] ?? null,
                'return_location_address' => $validated['return_location_address'] ?? null,
                'return_confirmed_at'     => now(),
                'status'                  => 'reported',
                'return_gps_validation'   => $gpsValidation ? json_encode($gpsValidation) : null,
            ]);
        }

        // Notifikasi ke vendor bahwa customer sudah di lokasi pengembalian
        try {
            $booking->vendor?->user?->notify(
                new \App\Notifications\LateReturnReportedToVendorNotification($report)
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send return confirmation notification to vendor', [
                'booking_id' => $booking->id,
                'report_id' => $report->id,
                'exception' => $e->getMessage()
            ]);
        }

        $message = 'Lokasi pengembalian berhasil dikirim ke vendor.';
        
        // Tambahkan info GPS jika ada
        if ($gpsValidation) {
            $accuracy = $gpsValidation['accuracy_score'] ?? 0;
            if ($accuracy >= 80) {
                $message .= ' GPS terverifikasi dengan baik.';
            } elseif ($accuracy >= 60) {
                $message .= ' GPS cukup akurat.';
            } else {
                $message .= ' GPS memerlukan verifikasi manual oleh vendor.';
            }
        }

        return back()->with('success', $message);
    }
}
