<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function create(Booking $booking)
    {
        $user = auth()->user();

        // Validasi 1: Booking harus milik customer yang login
        if (!$booking->customer || $booking->customer->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        // Validasi 2: Booking harus sudah selesai
        if ($booking->status !== 'completed') {
            return redirect()->route('bookings.show', $booking->code)
                ->with('error', 'Review hanya bisa diberikan untuk pesanan yang sudah selesai.');
        }

        // Validasi 3: Belum pernah review booking ini
        $existingReview = Review::where('booking_id', $booking->id)->first();
        if ($existingReview) {
            return redirect()->route('bookings.show', $booking->code)
                ->with('info', 'Anda sudah memberikan review untuk pesanan ini.');
        }

        $booking->load(['car.photos', 'vendor']);

        return view('public.reviews.create', compact('booking'));
    }

    public function store(Request $request, Booking $booking)
    {
        $user = auth()->user();

        // Validasi kepemilikan booking
        if (!$booking->customer || $booking->customer->user_id !== $user->id) {
            abort(403);
        }

        if ($booking->status !== 'completed') {
            return redirect()->route('bookings.show', $booking->code)
                ->with('error', 'Review hanya bisa diberikan untuk pesanan yang sudah selesai.');
        }

        // Cegah review duplikat
        if (Review::where('booking_id', $booking->id)->exists()) {
            return redirect()->route('bookings.show', $booking->code)
                ->with('info', 'Anda sudah memberikan review untuk pesanan ini.');
        }

        $validated = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        Review::create([
            'booking_id'  => $booking->id,
            'customer_id' => $booking->customer_id,
            'vendor_id'   => $booking->vendor_id,
            'car_id'      => $booking->car_id,
            'rating'      => $validated['rating'],
            'comment'     => $validated['comment'] ?? null,
        ]);

        return redirect()->route('bookings.show', $booking->code)
            ->with('success', 'Terima kasih! Review Anda berhasil disimpan.');
    }
}
