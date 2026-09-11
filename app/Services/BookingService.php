<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CustomerRefund;
use App\Notifications\BookingCancelledNotification;
use App\Services\LateReturnService;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Batalkan booking oleh vendor dengan alasan tertentu.
     * Otomatis buat CustomerRefund record jika payment sudah paid.
     * Admin juga menggunakan method ini (bisa cancel dari status apapun kecuali completed/cancelled).
     */
    public function cancelByVendor(Booking $booking, string $reason): bool
    {
        if (!in_array($booking->status, ['awaiting_payment', 'awaiting_vendor', 'confirmed'])) {
            return false;
        }

        return DB::transaction(function () use ($booking, $reason) {
            // Update booking status
            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            // Reload relasi untuk memastikan data terbaru
            $booking->load(['payment', 'customer', 'car']);

            // Buat refund record jika payment sudah paid
            // JANGAN update payment.status ke 'refunded' di sini
            $booking->createRefundIfNeeded();

            // ── FITUR BARU: Cari & simpan rekomendasi mobil serupa ────
            try {
                $recommendationService = app(CarRecommendationService::class);
                $similarCars = $recommendationService->findSimilarCars($booking);
                
                if ($similarCars->isNotEmpty()) {
                    $recommendationService->saveRecommendations($booking, $similarCars);
                }
            } catch (\Throwable $e) {
                // Log error tapi jangan gagalkan proses
                \Log::error('Failed to generate car recommendations for booking ' . $booking->id, [
                    'error' => $e->getMessage()
                ]);
            }

            // Notifikasi customer
            try {
                $booking->customer->user->notify(
                    new BookingCancelledNotification($booking)
                );
            } catch (\Throwable) {
                // Log error tapi jangan gagalkan proses
            }

            return true;
        });
    }

    /**
     * Batalkan booking oleh customer dengan alasan tertentu.
     * Otomatis buat CustomerRefund record jika payment sudah paid.
     */
    public function cancelByCustomer(Booking $booking, string $reason): bool
    {
        if (!in_array($booking->status, ['awaiting_payment', 'awaiting_vendor', 'confirmed'])) {
            return false;
        }

        return DB::transaction(function () use ($booking, $reason) {
            // Update booking status
            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            // Reload relasi untuk memastikan data terbaru
            $booking->load(['payment', 'customer', 'vendor']);

            // Buat refund record jika payment sudah paid
            // JANGAN update payment.status ke 'refunded' di sini
            $booking->createRefundIfNeeded();

            // Notifikasi vendor
            try {
                $booking->vendor->user->notify(
                    new BookingCancelledNotification($booking)
                );
            } catch (\Throwable) {
                // Log error tapi jangan gagalkan proses
            }

            return true;
        });
    }

    /**
     * Konfirmasi booking oleh vendor.
     */
    public function confirmByVendor(Booking $booking): bool
    {
        if ($booking->status !== 'awaiting_vendor') {
            return false;
        }

        return DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            // Notifikasi customer
            try {
                $booking->customer->user->notify(
                    new \App\Notifications\BookingConfirmedNotification($booking)
                );
            } catch (\Throwable) {
                // Log error tapi jangan gagalkan proses
            }

            return true;
        });
    }

    /**
     * Tandai booking sebagai sedang berlangsung (pickup).
     */
    public function markAsOngoing(Booking $booking): bool
    {
        if ($booking->status !== 'confirmed') {
            return false;
        }

        $booking->update([
            'status' => 'ongoing',
            'picked_up_at' => now(),
        ]);

        return true;
    }

    /**
     * Tandai booking sebagai selesai (return) dengan deteksi keterlambatan otomatis.
     *
     * @param  Booking          $booking
     * @param  \Carbon\Carbon|null  $actualReturnAt  Default = now()
     */
    public function markAsCompleted(Booking $booking, ?\Carbon\Carbon $actualReturnAt = null): bool
    {
        if ($booking->status !== 'ongoing') {
            return false;
        }

        $actualReturnAt = $actualReturnAt ?? now();

        $lateService = app(LateReturnService::class);
        return $lateService->completeWithReturn($booking, $actualReturnAt);
    }

    /**
     * Cek apakah booking bisa dibatalkan (oleh vendor/admin).
     */
    public function canBeCancelled(Booking $booking): bool
    {
        return in_array($booking->status, [
            'awaiting_payment',
            'awaiting_vendor',
            'confirmed',
            // 'ongoing' tidak bisa dibatalkan — mobil sudah diserahkan ke customer
        ]);
    }

    /**
     * Cek apakah booking memerlukan refund.
     */
    public function needsRefund(Booking $booking): bool
    {
        return $booking->status === 'cancelled' 
            && $booking->payment?->status === 'paid'
            && !$booking->refund()->whereIn('status', ['pending', 'paid'])->exists();
    }
}