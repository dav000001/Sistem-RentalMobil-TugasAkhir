<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\LateReturnReport;
use App\Services\GpsValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DriverReportController extends Controller
{
    /**
     * Tampilkan halaman portal laporan pengembalian khusus Sopir
     */
    public function show(Booking $booking)
    {
        abort_unless($booking->status === 'ongoing', 404, 'Booking tidak sedang berlangsung.');
        abort_unless($booking->with_driver && $booking->driver_id, 403, 'Booking ini tidak menggunakan sopir.');

        $booking->load(['car', 'vendor', 'driver', 'customer', 'lateReturnReport']);

        return view('public.driver.report', compact('booking'));
    }

    /**
     * Simpan laporan keterlambatan / konfirmasi pengembalian dari Sopir
     */
    public function store(Request $request, Booking $booking)
    {
        abort_unless($booking->status === 'ongoing', 422, 'Booking tidak sedang berlangsung.');
        abort_unless($booking->with_driver && $booking->driver_id, 403, 'Booking ini tidak menggunakan sopir.');

        $validated = $request->validate([
            'driver_phone'         => ['required', 'string'],
            'estimated_late_hours' => ['required', 'integer', 'min:0', 'max:72'],
            'reason'               => ['required', 'string', 'max:500'],
            'latitude'             => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'            => ['nullable', 'numeric', 'between:-180,180'],
            'location_address'     => ['nullable', 'string', 'max:300'],
            'report_type'          => ['required', 'in:late_warning,return_arrival'],
        ]);

        // Verifikasi simpel nomor telepon sopir
        $registeredPhone = preg_replace('/[^0-9]/', '', $booking->driver->phone ?? '');
        $inputPhone      = preg_replace('/[^0-9]/', '', $validated['driver_phone']);

        if (!str_ends_with($registeredPhone, substr($inputPhone, -4)) && $registeredPhone !== $inputPhone) {
            return back()
                ->withErrors(['driver_phone' => 'Nomor telepon tidak cocok dengan nomor sopir yang terdaftar untuk booking ini.'])
                ->withInput();
        }

        // Validasi GPS jika ada
        $gpsValidation = null;
        if ($validated['latitude'] && $validated['longitude']) {
            $gpsValidator = app(GpsValidationService::class);
            $gpsValidation = $gpsValidator->validateLocation(
                $validated['latitude'],
                $validated['longitude'],
                $booking,
                $validated['report_type'] === 'return_arrival' ? 'return_confirmation' : 'report'
            );
        }

        $report = $booking->lateReturnReport;

        if ($validated['report_type'] === 'return_arrival') {
            // Konfirmasi sudah sampai lokasi pengembalian
            if ($report) {
                $report->update([
                    'return_latitude'         => $validated['latitude'] ?? null,
                    'return_longitude'        => $validated['longitude'] ?? null,
                    'return_location_address' => $validated['location_address'] ?? null,
                    'return_confirmed_at'     => now(),
                    'return_gps_validation'   => $gpsValidation ? json_encode($gpsValidation) : null,
                    'status'                  => 'reported',
                ]);
            } else {
                $report = LateReturnReport::create([
                    'booking_id'              => $booking->id,
                    'reporter_type'           => 'driver',
                    'reported_by_user_id'     => auth()->id() ?? $booking->vendor?->user_id ?? $booking->customer?->user_id,
                    'estimated_late_hours'    => (int) $validated['estimated_late_hours'],
                    'reason'                  => '[Sopir] ' . $validated['reason'],
                    'return_latitude'         => $validated['latitude'] ?? null,
                    'return_longitude'        => $validated['longitude'] ?? null,
                    'return_location_address' => $validated['location_address'] ?? null,
                    'return_confirmed_at'     => now(),
                    'status'                  => 'reported',
                    'return_gps_validation'   => $gpsValidation ? json_encode($gpsValidation) : null,
                ]);
            }
            $successMsg = 'Konfirmasi lokasi pengembalian armada oleh sopir berhasil dikirim ke Vendor!';
        } else {
            // Laporan peringatan awal akan terlambat
            if ($report) {
                $report->update([
                    'estimated_late_hours' => (int) $validated['estimated_late_hours'],
                    'reason'               => '[Sopir] ' . $validated['reason'],
                    'latitude'             => $validated['latitude'] ?? null,
                    'longitude'            => $validated['longitude'] ?? null,
                    'location_address'     => $validated['location_address'] ?? null,
                    'status'               => 'reported',
                    'gps_validation_data'  => $gpsValidation ? json_encode($gpsValidation) : null,
                ]);
            } else {
                $report = LateReturnReport::create([
                    'booking_id'           => $booking->id,
                    'reporter_type'        => 'driver',
                    'reported_by_user_id'  => auth()->id() ?? $booking->vendor?->user_id ?? $booking->customer?->user_id,
                    'estimated_late_hours' => (int) $validated['estimated_late_hours'],
                    'reason'               => '[Sopir] ' . $validated['reason'],
                    'latitude'             => $validated['latitude'] ?? null,
                    'longitude'            => $validated['longitude'] ?? null,
                    'location_address'     => $validated['location_address'] ?? null,
                    'status'               => 'reported',
                    'gps_validation_data'  => $gpsValidation ? json_encode($gpsValidation) : null,
                ]);
            }
            $successMsg = 'Laporan keterlambatan armada oleh sopir berhasil dikirim ke Vendor!';
        }

        // Notifikasi ke Vendor
        try {
            $vendorUser = $booking->vendor?->user;
            if ($vendorUser) {
                $driverName = $booking->driver?->name ?? 'Sopir';
                $carName    = trim(($booking->car?->brand ?? '') . ' ' . ($booking->car?->model ?? ''));

                $title = $validated['report_type'] === 'return_arrival'
                    ? '📍 Sopir Konfirmasi Tiba di Garasi!'
                    : '⚠️ Laporan Keterlambatan dari Sopir (' . $driverName . ')';

                $body = $validated['report_type'] === 'return_arrival'
                    ? "Sopir ({$driverName}) mengonfirmasi armada {$carName} (Booking {$booking->code}) telah tiba di lokasi pengembalian."
                    : "Sopir ({$driverName}) melaporkan estimasi keterlambatan {$validated['estimated_late_hours']} jam untuk booking {$booking->code}. Alasan: \"{$validated['reason']}\".";

                // 1. Kirim Notifikasi Filament ke lonceng Vendor Panel
                \Filament\Notifications\Notification::make()
                    ->title($title)
                    ->body($body)
                    ->warning()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->label('🔍 Lihat Detail Booking')
                            ->url(url('/vendor/bookings/' . $booking->id . '/edit'))
                    ])
                    ->sendToDatabase($vendorUser);

                // 2. Kirim Notifikasi Email / Database Standard
                $vendorUser->notify(
                    new \App\Notifications\LateReturnReportedToVendorNotification($report)
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal notifikasi vendor laporan sopir', ['exception' => $e->getMessage()]);
        }

        return back()->with('success', $successMsg);
    }
}
