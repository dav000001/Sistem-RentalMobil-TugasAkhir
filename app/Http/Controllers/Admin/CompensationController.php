<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CompensationCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompensationController extends Controller
{
    /**
     * Buat tagihan kompensasi ke customer dari halaman detail komplain vendor.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id'   => ['required', 'exists:bookings,id'],
            'complaint_id' => ['nullable', 'exists:complaints,id'],
            'amount'       => ['required', 'integer', 'min:1'],
            'reason'       => ['required', 'string', 'min:5', 'max:500'],
            'due_date'     => ['nullable', 'date', 'after:today'],
        ]);

        $booking = Booking::with('customer')->findOrFail($validated['booking_id']);

        if (!$booking->customer) {
            return back()->with('error', 'Data customer tidak ditemukan.');
        }

        // Cek apakah sudah ada tagihan pending untuk booking ini
        $alreadyExists = CompensationCharge::where('booking_id', $booking->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyExists) {
            return back()->with('error', 'Sudah ada tagihan kompensasi pending untuk booking ini.');
        }

        $charge = CompensationCharge::create([
            'booking_id'  => $booking->id,
            'customer_id' => $booking->customer_id,
            'dispute_id'  => null, // tidak terkait dispute lama
            'amount'      => $validated['amount'],
            'reason'      => $validated['reason'],
            'status'      => 'pending',
            'due_date'    => $validated['due_date'] ?? now()->addDays(7)->format('Y-m-d'),
            'notes'       => $validated['complaint_id']
                ? 'Dibuat dari laporan vendor — Komplain #' . $validated['complaint_id']
                : 'Dibuat dari panel admin',
        ]);

        // Notifikasi ke customer
        try {
            $booking->customer?->user?->notify(
                new \App\Notifications\CompensationChargeNotification($charge)
            );
        } catch (\Throwable) {}

        // Redirect kembali ke halaman komplain jika ada complaint_id
        if ($validated['complaint_id']) {
            return redirect()
                ->route('admin.complaints.show', $validated['complaint_id'])
                ->with('success', 'Tagihan kompensasi Rp ' . number_format($charge->amount, 0, ',', '.') . ' berhasil dikirim ke customer.');
        }

        return redirect()
            ->route('admin.complaints.index')
            ->with('success', 'Tagihan kompensasi berhasil dibuat.');
    }
}
