<?php

namespace App\Policies;

use App\Models\Car;
use App\Models\User;

class CarPolicy
{
    /**
     * Admin (guard admin) bypass semua policy.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Admin diidentifikasi dari guard admin Filament
        if (auth('admin')->check() && auth('admin')->id() === $user->id) {
            return true;
        }
        return null;
    }

    /**
     * Customer bisa booking mobil yang published.
     */
    public function book(User $user, Car $car): bool
    {
        $customer = $user->customer;
        return $customer
            && $customer->verification_status === 'verified'
            && $car->status === 'published'
            && $car->status !== 'unavailable';
    }

    /**
     * Vendor hanya bisa lihat mobil miliknya.
     */
    public function view(User $user, Car $car): bool
    {
        return $this->ownsCar($user, $car);
    }

    /**
     * Vendor hanya bisa edit mobil miliknya.
     */
    public function update(User $user, Car $car): bool
    {
        return $this->ownsCar($user, $car);
    }

    /**
     * Vendor hanya bisa hapus mobil miliknya.
     */
    public function delete(User $user, Car $car): bool
    {
        return $this->ownsCar($user, $car);
    }

    /**
     * Vendor bisa tambah mobil jika approved, punya subscription aktif, dan belum melebihi limit.
     */
    public function create(User $user): bool
    {
        $vendor = $user->vendor;
        if (!$vendor || !$vendor->isApproved()) {
            return false;
        }

        // Cek subscription aktif
        $sub = app(\App\Services\VendorSubscriptionService::class)->getCurrent($vendor);
        if (!$sub || !$sub->canAcceptBookings()) {
            return false;
        }

        return $vendor->canAddMoreCars();
    }

    private function ownsCar(User $user, Car $car): bool
    {
        $vendor = $user->vendor;
        return $vendor && $vendor->id === $car->vendor_id;
    }
}
