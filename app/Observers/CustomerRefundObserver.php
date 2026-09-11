<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\CustomerRefund;

class CustomerRefundObserver
{
    /**
     * Setiap kali CustomerRefund diupdate ke status 'paid',
     * otomatis update Payment terkait ke status 'refunded'.
     */
    public function updated(CustomerRefund $refund): void
    {
        if ($refund->wasChanged('status') && $refund->status === 'paid') {
            $booking = Booking::with('payment')->find($refund->booking_id);

            if ($booking?->payment && $booking->payment->status !== 'refunded') {
                $booking->payment->update([
                    'status'      => 'refunded',
                    'refunded_at' => $refund->paid_at ?? now(),
                    'refund_ref'  => $refund->transfer_reference ?? null,
                    'refund_note' => $refund->notes ?? null,
                ]);
            }
        }
    }
}
