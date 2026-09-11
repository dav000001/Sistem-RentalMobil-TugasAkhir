<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function show(Booking $booking)
    {
        Gate::authorize('pay', $booking);
        $booking->load(['car.photos', 'vendor']);

        // Rekening tujuan transfer dari config (bukan env() langsung)
        $bankInfo = [
            'bank_name'    => config('payment.bank_name', 'BCA'),
            'account_no'   => config('payment.account_no', '1234567890'),
            'account_name' => config('payment.account_name', 'PT Rental Mobil Indonesia'),
        ];

        return view('public.payments.show', compact('booking', 'bankInfo'));
    }

    public function uploadProof(Request $request, Booking $booking)
    {
        Gate::authorize('pay', $booking);

        $request->validate([
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'sender_name'   => ['required', 'string', 'max:100'],
        ], [
            'payment_proof.required' => 'Bukti transfer wajib diupload.',
            'payment_proof.image'    => 'File harus berupa gambar.',
            'payment_proof.mimes'    => 'Format file harus JPG atau PNG.',
            'payment_proof.max'      => 'Ukuran file maksimal 2MB.',
            'sender_name.required'   => 'Nama pengirim wajib diisi.',
        ]);

        // Simpan file bukti transfer
        $path = $request->file('payment_proof')->store('payment-proofs', 'public');

        // Buat atau update payment dengan status pending (menunggu konfirmasi admin)
        Payment::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'gateway'       => 'manual',
                'gateway_ref'   => 'MANUAL-' . strtoupper(uniqid()),
                'method'        => 'Transfer Bank',
                'amount'        => $booking->total,
                'status'        => 'pending',
                'payment_proof' => $path,
                'sender_name'   => $request->sender_name,
            ]
        );

        // Status tetap awaiting_payment — admin yang akan konfirmasi dan ubah ke awaiting_vendor
        // (jangan ubah status di sini)

        // Notifikasi ke admin untuk konfirmasi
        \App\Models\User::where('role', 'admin')->get()->each(function ($admin) use ($booking) {
            try {
                $admin->notify(new \App\Notifications\PaymentReceivedNotification($booking));
            } catch (\Throwable) {}
        });

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', 'Bukti transfer berhasil diupload! Menunggu konfirmasi admin (1x24 jam).');
    }

    public function demoPayment(Request $request, Booking $booking)
    {
        Gate::authorize('pay', $booking);

        $method = $request->input('method', 'Demo Payment');

        // Simulasi pembayaran berhasil
        Payment::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'gateway'     => 'midtrans',
                'gateway_ref' => 'DEMO-' . strtoupper(uniqid()),
                'method'      => $method,
                'amount'      => $booking->total,
                'status'      => 'paid',
                'paid_at'     => now(),
            ]
        );

        $booking->update(['status' => 'awaiting_vendor']);

        // Notifikasi ke vendor
        try {
            $booking->vendor?->user?->notify(
                new \App\Notifications\BookingCreatedNotification($booking)
            );
        } catch (\Throwable) {}

        // Notifikasi ke admin
        $this->notifyAdmins($booking);

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', "Pembayaran via {$method} berhasil! Menunggu konfirmasi vendor.");
    }

    /**
     * Midtrans webhook handler
     * Verifikasi signature dan update status booking
     */
    public function midtransWebhook(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');
        $orderId = $request->order_id;
        $statusCode = $request->status_code;
        $grossAmount = $request->gross_amount;

        // Verify signature
        $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($signatureKey !== $request->signature_key) {
            Log::warning('Midtrans webhook: invalid signature', ['order_id' => $orderId]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $booking = Booking::where('code', $orderId)->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $transactionStatus = $request->transaction_status;
        $fraudStatus = $request->fraud_status ?? 'accept';

        if ($transactionStatus === 'capture' && $fraudStatus === 'accept') {
            $this->markPaid($booking, $request);
        } elseif ($transactionStatus === 'settlement') {
            $this->markPaid($booking, $request);
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            $this->markFailed($booking, $request);
        }

        return response()->json(['message' => 'OK']);
    }

    private function markPaid(Booking $booking, Request $request): void
    {
        Payment::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'gateway' => 'midtrans',
                'gateway_ref' => $request->transaction_id,
                'method' => $request->payment_type,
                'amount' => $booking->total,
                'status' => 'paid',
                'paid_at' => now(),
            ]
        );

        $booking->update(['status' => 'awaiting_vendor']);

        // Notify vendor
        try {
            $booking->vendor?->user?->notify(
                new \App\Notifications\BookingCreatedNotification($booking)
            );
        } catch (\Throwable) {}

        // Notify admin
        $this->notifyAdmins($booking);
    }

    private function notifyAdmins(Booking $booking): void
    {
        \App\Models\User::where('role', 'admin')->get()->each(function ($admin) use ($booking) {
            try {
                $admin->notify(new \App\Notifications\PaymentReceivedNotification($booking));
            } catch (\Throwable) {}
        });
    }

    private function markFailed(Booking $booking, Request $request): void
    {
        Payment::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'gateway' => 'midtrans',
                'gateway_ref' => $request->transaction_id,
                'method' => $request->payment_type,
                'amount' => $booking->total,
                'status' => 'failed',
            ]
        );
    }
}
