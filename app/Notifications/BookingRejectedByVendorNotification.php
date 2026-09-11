<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke admin saat vendor menolak pesanan.
 * Berisi semua info yang dibutuhkan admin untuk memproses refund ke customer.
 */
class BookingRejectedByVendorNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public string  $rejectReason
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $customer = $this->booking->customer;
        $vendor   = $this->booking->vendor;

        // Info rekening customer untuk refund
        $bankInfo = $customer->bank_account_no
            ? $customer->bank_name . ' — ' . $customer->bank_account_no . ' a.n. ' . $customer->bank_account_name
            : '⚠️ Customer belum mengisi info rekening. Hubungi customer untuk mendapatkan nomor rekening refund.';

        return [
            'format'       => 'filament',
            'title'        => '🔴 Vendor Tolak Pesanan — Perlu Refund',
            'body'         => 'Booking ' . $this->booking->code
                            . ' ditolak oleh ' . $vendor->business_name
                            . ' | Alasan: ' . $this->rejectReason
                            . ' | Total refund: Rp ' . number_format($this->booking->total, 0, ',', '.')
                            . ' | Rekening customer: ' . $bankInfo,
            'icon'         => 'heroicon-o-banknotes',
            'color'        => 'danger',
            'url'          => url('/admin/bookings/' . $this->booking->id . '/edit'),
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->code,
            'refund_amount'       => $this->booking->total,
            'reject_reason'       => $this->rejectReason,
            'customer_bank'       => $customer->bank_name,
            'customer_account_no' => $customer->bank_account_no,
            'customer_account_name' => $customer->bank_account_name,
        ];
    }
}
