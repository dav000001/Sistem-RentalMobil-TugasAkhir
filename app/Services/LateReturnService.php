<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;

class LateReturnService
{
    /**
     * Hitung dan simpan data keterlambatan pada booking.
     * Dipanggil saat vendor menandai booking selesai (actual_return_at diisi).
     *
     * @param  Booking    $booking
     * @param  Carbon     $actualReturnAt  Waktu aktual pengembalian
     * @return array{is_late: bool, hours: int, fee: float}
     */
    public function calculate(Booking $booking, Carbon $actualReturnAt): array
    {
        $endAt = $booking->end_at;

        // Grace period 60 menit — terlambat dalam batas ini tidak kena denda
        $gracePeriodMinutes = 60;

        // diffInMinutes dengan false = negatif jika belum jatuh tempo, positif jika sudah lewat
        $diffMinutes = $endAt->diffInMinutes($actualReturnAt, false);

        $isLate    = $diffMinutes > $gracePeriodMinutes;
        $lateHours = 0;
        $lateFee   = 0.0;

        if ($isLate) {
            // Hitung jam terlambat dari TOTAL menit sejak jatuh tempo (bukan sejak selesai grace period)
            // ceil agar pecahan jam dihitung penuh (misal: 1j10m = 2 jam)
            $lateHours = (int) ceil($diffMinutes / 60);

            $dailyPrice = $booking->car?->pricing?->daily_price ?? 0;
            $hourlyRate = $dailyPrice / 24;

            if ($lateHours >= 6) {
                // Keterlambatan berat (>= 6 jam): Denda dihitung per kelipatan 1 hari penuh sewa
                $days = (int) ceil($lateHours / 24);
                $calculatedFee = round($dailyPrice * $days, 2);
            } else {
                // Keterlambatan ringan/sedang (< 6 jam): Tarif per jam x penalti 1.5x
                $calculatedFee = round(($hourlyRate * 1.5) * $lateHours, 2);
            }

            // Fee Cap: Maksimal 3x harga sewa harian agar denda tidak membengkak tanpa batas
            $maxFeeCap = $dailyPrice * 3;
            $lateFee   = ($maxFeeCap > 0 && $calculatedFee > $maxFeeCap) ? $maxFeeCap : $calculatedFee;
        }

        return [
            'is_late' => $isLate,
            'hours'   => $lateHours,
            'fee'     => $lateFee,
        ];
    }

    /**
     * Simpan hasil kalkulasi ke booking dan tandai sebagai completed.
     */
    public function completeWithReturn(Booking $booking, Carbon $actualReturnAt): bool
    {
        if ($booking->status !== 'ongoing') {
            return false;
        }

        $result = $this->calculate($booking, $actualReturnAt);

        $booking->update([
            'status'              => 'completed',
            'completed_at'        => now(),
            'actual_return_at'    => $actualReturnAt,
            'is_late'             => $result['is_late'],
            'late_duration_hours' => $result['hours'],
            'late_fee'            => $result['fee'],
        ]);

        if ($result['is_late'] && $result['fee'] > 0) {
            // Buat tagihan denda — status pending, menunggu konfirmasi admin
            \App\Models\LateFeeCharge::create([
                'booking_id'       => $booking->id,
                'customer_id'      => $booking->customer_id,
                'vendor_id'        => $booking->vendor_id,
                'amount'           => $result['fee'],
                'late_hours'       => $result['hours'],
                'status'           => 'pending',
                // Isi rekening vendor sebagai tujuan pembayaran denda
                'bank_name'        => $booking->vendor?->bank_name,
                'bank_account_no'  => $booking->vendor?->bank_account_number,
                'bank_account_name'=> $booking->vendor?->bank_account_name,
            ]);

            $this->notifyLateReturn($booking);
        }

        return true;
    }

    /**
     * Kirim notifikasi ke customer, vendor, dan semua admin saat keterlambatan terdeteksi.
     */
    protected function notifyLateReturn(Booking $booking): void
    {
        // Notifikasi ke customer
        try {
            $booking->customer?->user?->notify(
                new \App\Notifications\LateReturnCustomerNotification($booking)
            );
        } catch (\Throwable) {}

        // Notifikasi ke vendor
        try {
            $booking->vendor?->user?->notify(
                new \App\Notifications\LateReturnVendorNotification($booking)
            );
        } catch (\Throwable) {}

        // Notifikasi ke semua admin — ada tagihan denda pending yang perlu dikonfirmasi
        $charge = $booking->lateFeeCharge;
        if ($charge) {
            \App\Models\User::where('role', 'admin')->get()->each(function ($admin) use ($charge) {
                try {
                    $admin->notify(new \App\Notifications\LateFeeAdminNotification($charge));
                } catch (\Throwable) {}
            });
        }
    }
}
