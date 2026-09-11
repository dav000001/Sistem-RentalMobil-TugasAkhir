<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LateFeeCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LateFeeController extends Controller
{
    /**
     * Customer upload bukti pembayaran denda.
     */
    public function uploadProof(Request $request, LateFeeCharge $charge)
    {
        // Pastikan customer ini pemilik tagihan
        abort_unless(
            $charge->customer_id === auth()->user()?->customer?->id,
            403
        );

        abort_unless($charge->status === 'confirmed', 422);

        $request->validate([
            'payment_proof' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('payment_proof')
            ->store('late-fee-proofs', 'public');

        $charge->update([
            'payment_proof'      => $path,
            'proof_uploaded_at'  => now(),
        ]);

        // Notifikasi ke semua admin
        \App\Models\User::where('role', 'admin')->get()->each(function ($admin) use ($charge) {
            try {
                $admin->notify(new \App\Notifications\LateFeeProofUploadedNotification($charge));
            } catch (\Throwable) {}
        });

        // Notifikasi juga ke vendor pemilik unit
        try {
            $charge->vendor?->user?->notify(new \App\Notifications\LateFeeProofUploadedNotification($charge));
        } catch (\Throwable) {}

        return back()->with('success', 'Bukti pembayaran denda berhasil diupload. Admin dan Vendor akan segera memverifikasi.');
    }

    /**
     * Customer mengajukan keberatan / banding atas denda.
     */
    public function submitDispute(Request $request, LateFeeCharge $charge)
    {
        // Pastikan customer ini pemilik tagihan
        abort_unless(
            $charge->customer_id === auth()->user()?->customer?->id,
            403
        );

        abort_unless(in_array($charge->status, ['pending', 'confirmed']), 422, 'Tagihan tidak dapat diajukan keberatan.');

        $validated = $request->validate([
            'dispute_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'dispute_reason.required' => 'Jelaskan alasan keberatan Anda.',
            'dispute_reason.min'      => 'Alasan keberatan minimal 10 karakter.',
        ]);

        $charge->update([
            'dispute_reason' => $validated['dispute_reason'],
            'disputed_at'    => now(),
        ]);

        // Notifikasi ke Admin
        \App\Models\User::where('role', 'admin')->get()->each(function ($admin) use ($charge) {
            try {
                $admin->notify(new \App\Notifications\LateFeeDisputedNotification($charge));
            } catch (\Throwable) {}
        });

        // Notifikasi ke Vendor
        try {
            $charge->vendor?->user?->notify(new \App\Notifications\LateFeeDisputedNotification($charge));
        } catch (\Throwable) {}

        return back()->with('success', 'Keberatan denda berhasil dikirim. Admin dan Vendor akan meninjau alasan Anda.');
    }
}
