<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Admin bypass semua policy.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (auth('admin')->check() && auth('admin')->id() === $user->id) {
            return true;
        }
        return null;
    }

    // ── Customer policies ────────────────────────────────────────────

    public function view(User $user, Booking $booking): bool
    {
        // Customer lihat booking miliknya
        if ($user->customer && $user->customer->id === $booking->customer_id) {
            return true;
        }
        // Vendor lihat booking miliknya
        if ($user->vendor && $user->vendor->id === $booking->vendor_id) {
            return true;
        }
        return false;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if (!($user->customer && $user->customer->id === $booking->customer_id)) {
            return false;
        }
        // Konsisten dengan BookingService::cancelByCustomer()
        return in_array($booking->status, ['awaiting_payment', 'awaiting_vendor', 'confirmed']);
    }

    public function pay(User $user, Booking $booking): bool
    {
        if (!($user->customer && $user->customer->id === $booking->customer_id)) {
            return false;
        }
        return $booking->status === 'awaiting_payment';
    }

    // ── Vendor policies ──────────────────────────────────────────────

    public function confirm(User $user, Booking $booking): bool
    {
        return $user->vendor
            && $user->vendor->id === $booking->vendor_id
            && $booking->status === 'awaiting_vendor';
    }

    public function updateStatus(User $user, Booking $booking): bool
    {
        return $user->vendor && $user->vendor->id === $booking->vendor_id;
    }
}
