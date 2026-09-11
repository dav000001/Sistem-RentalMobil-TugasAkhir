<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Dispute;
use App\Notifications\DisputeOpenedNotification;
use App\Notifications\DisputeOpenedVendorNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DisputeController extends Controller
{
    /**
     * Tampilkan form pengajuan dispute untuk booking tertentu.
     */
    public function create(Booking $booking)
    {
        $user = Auth::user();

        $this->authorizeBookingOwner($user, $booking);

        // Hanya bisa dispute booking yang sudah selesai / sedang berlangsung / dikonfirmasi
        if (! in_array($booking->status, ['confirmed', 'ongoing', 'completed', 'disputed'])) {
            return redirect()->route('bookings.show', $booking->code)
                ->with('error', 'Dispute hanya dapat diajukan untuk pesanan yang sudah dikonfirmasi, sedang berlangsung, atau sudah selesai.');
        }

        // Cek apakah sudah ada dispute aktif
        if ($booking->dispute && ! $booking->dispute->isClosed()) {
            return redirect()->route('bookings.show', $booking->code)
                ->with('info', 'Anda sudah memiliki dispute aktif untuk pesanan ini.');
        }

        return view('public.disputes.create', compact('booking'));
    }

    /**
     * Simpan dispute baru ke database.
     */
    public function store(Request $request, Booking $booking)
    {
        $user = Auth::user();

        $this->authorizeBookingOwner($user, $booking);

        if (! in_array($booking->status, ['confirmed', 'ongoing', 'completed', 'disputed'])) {
            return redirect()->route('bookings.show', $booking->code)
                ->with('error', 'Dispute tidak dapat diajukan untuk pesanan dengan status ini.');
        }

        if ($booking->dispute && ! $booking->dispute->isClosed()) {
            return redirect()->route('bookings.show', $booking->code)
                ->with('info', 'Anda sudah memiliki dispute aktif untuk pesanan ini.');
        }

        $validated = $request->validate([
            'reason'     => 'required|string|min:30|max:5000',
            'evidence'   => 'nullable|array|max:5',
            'evidence.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'reason.min'       => 'Alasan sengketa minimal 30 karakter.',
            'evidence.*.mimes' => 'Bukti hanya boleh berformat JPG, PNG, atau PDF.',
            'evidence.*.max'   => 'Setiap bukti maksimal 5 MB.',
        ]);

        // Upload file bukti
        $evidencePaths = [];
        if ($request->hasFile('evidence')) {
            foreach ($request->file('evidence') as $file) {
                $path = $file->store('disputes/evidence', 'public');
                $evidencePaths[] = $path;
            }
        }

        // Buat dispute
        $dispute = Dispute::create([
            'booking_id'        => $booking->id,
            'opened_by_user_id' => $user->id,
            'opened_by_role'    => 'customer',
            'reason'            => $validated['reason'],
            'evidence'          => ! empty($evidencePaths) ? $evidencePaths : null,
            'status'            => 'open',
        ]);

        // Ubah status booking ke 'disputed'
        $booking->update(['status' => 'disputed']);

        // Notifikasi ke semua admin
        \App\Models\User::where('role', 'admin')->get()->each(function ($admin) use ($dispute) {
            try {
                $admin->notify(new DisputeOpenedNotification($dispute));
            } catch (\Throwable) {}
        });

        // Notifikasi ke vendor dengan notifikasi khusus vendor
        try {
            $booking->vendor->user->notify(new DisputeOpenedVendorNotification($dispute));
        } catch (\Throwable) {}

        return redirect()->route('bookings.show', $booking->code)
            ->with('success', 'Dispute berhasil diajukan. Tim admin akan meninjaunya dalam 1×3 hari kerja.');
    }

    /**
     * Pastikan booking milik customer yang sedang login.
     */
    private function authorizeBookingOwner($user, Booking $booking): void
    {
        if (! $booking->customer || $booking->customer->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }
    }
}
