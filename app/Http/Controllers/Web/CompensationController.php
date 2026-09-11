<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CompensationCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompensationController extends Controller
{
    /**
     * Customer upload bukti pembayaran kompensasi.
     */
    public function uploadProof(Request $request, CompensationCharge $charge)
    {
        // Pastikan hanya customer pemilik booking yang bisa upload
        $user = Auth::user();
        if (!$charge->booking || !$charge->booking->customer || $charge->booking->customer->user_id !== $user->id) {
            abort(403);
        }

        if ($charge->status !== 'pending') {
            return back()->with('error', 'Tagihan ini sudah tidak aktif.');
        }

        $request->validate([
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'payment_proof.required' => 'Bukti pembayaran wajib diupload.',
            'payment_proof.image'    => 'File harus berupa gambar.',
            'payment_proof.mimes'    => 'Format harus JPG atau PNG.',
            'payment_proof.max'      => 'Ukuran maksimal 2MB.',
        ]);

        $path = $request->file('payment_proof')->store('compensation-proofs', 'public');

        $charge->update(['payment_proof' => $path]);

        // Notifikasi ke semua admin
        \App\Models\User::where('role', 'admin')->get()->each(function ($admin) use ($charge) {
            try {
                $admin->notify(new \App\Notifications\CompensationProofUploadedNotification($charge));
            } catch (\Throwable) {}
        });

        return back()->with('success', 'Bukti pembayaran berhasil dikirim. Admin akan memverifikasi dalam 1×24 jam.');
    }
}
