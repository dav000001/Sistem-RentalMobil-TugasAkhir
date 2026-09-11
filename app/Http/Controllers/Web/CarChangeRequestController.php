<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarChangeRequest;
use App\Models\CustomerRefund;
use App\Models\Payout;
use App\Notifications\CarChangeRequestedNotification;
use App\Notifications\CarChangeApprovedNotification;
use App\Notifications\CarChangeRejectedNotification;
use App\Notifications\CarChangePaymentConfirmedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarChangeRequestController extends Controller
{
    /**
     * Customer mengajukan permintaan ganti mobil.
     */
    public function store(Request $request, string $code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        // Pastikan customer yang bersangkutan
        $customer = auth()->user()->customer;
        abort_if($booking->customer_id !== $customer?->id, 403);

        // Hanya bisa dilakukan pada booking confirmed
        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Permintaan ganti mobil hanya bisa dilakukan pada booking yang sudah dikonfirmasi.');
        }

        // Batas waktu: harus sebelum H-1
        if (now()->gte($booking->start_at->subDay())) {
            return back()->with('error', 'Permintaan ganti mobil hanya dapat diajukan maksimal H-1 sebelum masa sewa berlangsung.');
        }

        // Cek jika sudah ada permintaan aktif (pending/approved)
        if ($booking->carChangeRequest && in_array($booking->carChangeRequest->status, ['pending', 'approved'])) {
            return back()->with('error', 'Sudah ada permintaan ganti mobil yang sedang diproses untuk booking ini.');
        }

        $validated = $request->validate([
            'passenger_count' => ['required', 'integer', 'min:1', 'max:100'],
            'with_driver'     => ['nullable', 'boolean'],
            'reason'          => ['required', 'string', 'max:1000'],
        ]);

        $withDriver = isset($validated['with_driver']) ? (bool) $validated['with_driver'] : $booking->with_driver;

        $changeRequest = CarChangeRequest::create([
            'booking_id'      => $booking->id,
            'customer_id'     => $customer->id,
            'vendor_id'       => $booking->vendor_id,
            'old_car_id'      => $booking->car_id,
            'passenger_count' => $validated['passenger_count'],
            'with_driver'     => $withDriver,
            'reason'          => $validated['reason'],
            'status'          => 'pending',
            'original_total'  => $booking->total,
            'requested_at'    => now(),
        ]);

        // Notifikasi ke vendor (Database Notification + Filament Native)
        try {
            $user = $booking->vendor?->user;
            if ($user) {
                $user->notify(new CarChangeRequestedNotification($changeRequest));

                \Filament\Notifications\Notification::make()
                    ->title('🔄 Permintaan Ganti Mobil!')
                    ->body('Customer ' . ($customer->full_name ?? 'Customer') . ' meminta ganti mobil pada booking #' . $booking->code . ' (penumpang: ' . $validated['passenger_count'] . ' orang).')
                    ->warning()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->label('Lihat Detail Booking')
                            ->button()
                            ->url('/vendor/bookings/' . $booking->id . '/edit'),
                    ])
                    ->sendToDatabase($user);
            }
        } catch (\Throwable) {}

        return back()->with('success', 'Permintaan ganti mobil berhasil dikirim. Silakan tunggu respon dari vendor.');
    }

    /**
     * Customer upload bukti bayar selisih harga.
     */
    public function uploadProof(Request $request, string $code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();
        $customer = auth()->user()->customer;
        abort_if($booking->customer_id !== $customer?->id, 403);

        $changeRequest = $booking->carChangeRequest;
        abort_if(!$changeRequest || $changeRequest->status !== 'approved', 403);
        abort_if($changeRequest->additional_payment_at, 403, 'Bukti bayar sudah pernah diupload.');

        $request->validate([
            'payment_proof' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('payment_proof')->store('car-change-proofs', 'public');
        $changeRequest->update(['additional_payment_proof' => $path]);

        // Notifikasi ke semua Admin bahwa bukti bayar selisih sudah diupload
        try {
            \App\Models\User::where('role', 'admin')->get()->each(function ($admin) use ($booking, $changeRequest) {
                \Filament\Notifications\Notification::make()
                    ->title('💳 Bukti Bayar Selisih Mobil Diupload!')
                    ->body('Customer ' . ($booking->customer?->full_name ?? 'Customer') . ' telah mengunggah bukti bayar selisih Rp ' . number_format($changeRequest->price_difference, 0, ',', '.') . ' pada booking #' . $booking->code . '.')
                    ->info()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->label('Verifikasi di Panel Admin')
                            ->button()
                            ->url('/admin/bookings/' . $booking->id . '/edit'),
                    ])
                    ->sendToDatabase($admin);
            });
        } catch (\Throwable) {}

        return back()->with('success', 'Bukti pembayaran berhasil diupload. Admin akan segera memverifikasi.');
    }

    /**
     * Customer membatalkan permintaan (hanya jika masih pending).
     */
    public function cancel(string $code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();
        $customer = auth()->user()->customer;
        abort_if($booking->customer_id !== $customer?->id, 403);

        $changeRequest = $booking->carChangeRequest;
        abort_if(!$changeRequest || !$changeRequest->isCancellable(), 403);

        $changeRequest->update([
            'status'       => 'cancelled_by_customer',
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Permintaan ganti mobil berhasil dibatalkan.');
    }

    /**
     * Vendor menyetujui permintaan dan menentukan mobil baru.
     * Dipanggil dari Filament action vendor panel.
     */
    public static function vendorApprove(CarChangeRequest $changeRequest, int $newCarId, ?string $vendorNotes = null): void
    {
        $booking = $changeRequest->booking;
        $newCar  = Car::with('pricing')->findOrFail($newCarId);

        // Validasi ketat: Mobil pengganti WAJIB berasal dari vendor yang sama
        abort_if($newCar->vendor_id !== $changeRequest->vendor_id, 403, 'Mobil pengganti harus berasal dari vendor yang sama.');

        // Hitung ulang harga dengan mobil baru & opsi sopir pilihan customer
        $days       = (int) $booking->start_at->diffInDays($booking->end_at) ?: 1;
        $withDriver = isset($changeRequest->with_driver) ? (bool) $changeRequest->with_driver : $booking->with_driver;

        $newPrice = ($newCar->pricing->daily_price ?? 0) * $days;
        if ($withDriver) {
            $newPrice += ($newCar->pricing->with_driver_price ?? 0) * $days;
        }

        // Platform fee (ambil dari persentase lama)
        $originalPlatformFeeRate = $booking->total > 0
            ? ($booking->platform_fee / $booking->total)
            : 0.05;
        $newPlatformFee   = round($newPrice * $originalPlatformFeeRate, 2);
        $newVendorPayout  = $newPrice - $newPlatformFee;
        $priceDifference  = max(0, $newPrice - $booking->total);

        DB::transaction(function () use ($changeRequest, $booking, $newCar, $withDriver, $newPrice, $newPlatformFee, $newVendorPayout, $priceDifference, $vendorNotes) {
            $changeRequest->update([
                'status'           => 'approved',
                'new_car_id'       => $newCar->id,
                'new_total'        => $newPrice,
                'price_difference' => $priceDifference,
                'vendor_notes'     => $vendorNotes,
                'responded_at'     => now(),
                'approved_at'      => now(),
            ]);

            // Jika tidak ada selisih, langsung update mobil & opsi driver di booking
            if ($priceDifference <= 0) {
                $bookingData = [
                    'car_id'               => $newCar->id,
                    'with_driver'          => $withDriver,
                    'subtotal'             => $newPrice,
                    'total'                => $newPrice,
                    'platform_fee'         => $newPlatformFee,
                    'vendor_payout_amount' => $newVendorPayout,
                ];

                if (!$withDriver) {
                    $bookingData['driver_id'] = null;
                } elseif ($withDriver && !$booking->driver_id) {
                    $availableDriver = \App\Models\Driver::where('vendor_id', $newCar->vendor_id)
                        ->where('status', 'active')
                        ->get()
                        ->first(fn ($d) => $d->isAvailableOn($booking->start_at, $booking->end_at));
                    if ($availableDriver) {
                        $bookingData['driver_id'] = $availableDriver->id;
                    }
                }

                $booking->update($bookingData);
                $changeRequest->update(['approved_at' => now()]);
            }
        });

        // Notifikasi ke customer
        try {
            $changeRequest->customer->user->notify(new CarChangeApprovedNotification($changeRequest));
        } catch (\Throwable) {}

        // Notifikasi ke semua Admin
        try {
            \App\Models\User::where('role', 'admin')->get()->each(function ($admin) use ($booking, $changeRequest, $priceDifference) {
                $msg = $priceDifference > 0
                    ? 'Vendor menyetujui ganti mobil pada booking #' . $booking->code . '. Customer diminta bayar selisih Rp ' . number_format($priceDifference, 0, ',', '.') . '.'
                    : 'Vendor menyetujui ganti mobil tanpa selisih harga pada booking #' . $booking->code . '.';

                \Filament\Notifications\Notification::make()
                    ->title('🚗 Vendor Menyetujui Ganti Mobil')
                    ->body($msg)
                    ->success()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->label('Lihat Detail Booking')
                            ->button()
                            ->url('/admin/bookings/' . $booking->id . '/edit'),
                    ])
                    ->sendToDatabase($admin);
            });
        } catch (\Throwable) {}
    }

    /**
     * Vendor menolak (tidak punya mobil pengganti) → batalkan booking + refund 50%.
     * Pembagian dana:
     *  - 50% dikembalikan ke customer (CustomerRefund)
     *  - 40% masuk ke vendor sebagai kompensasi (Payout)
     *  - 10% tetap di platform/admin sebagai penalty fee
     * Dipanggil dari Filament action vendor panel.
     */
    public static function vendorReject(CarChangeRequest $changeRequest, ?string $vendorNotes = null): void
    {
        $booking = $changeRequest->booking;

        DB::transaction(function () use ($changeRequest, $booking, $vendorNotes) {
            // Update status permintaan
            $changeRequest->update([
                'status'       => 'rejected',
                'vendor_notes' => $vendorNotes,
                'responded_at' => now(),
            ]);

            // Batalkan booking
            $booking->update([
                'status'              => 'cancelled',
                'cancellation_reason' => 'Vendor tidak memiliki mobil pengganti yang sesuai kapasitas. '
                                       . ($vendorNotes ? 'Catatan: ' . $vendorNotes : ''),
                'cancelled_at'        => now(),
            ]);

            // Pembagian penalty dari total pembayaran
            if ($booking->payment?->status === 'paid') {
                $paidAmount = (float) $booking->payment->amount;

                // 50% → refund ke customer
                $refundAmount = round($paidAmount * 0.50, 2);

                // 40% → kompensasi ke vendor (Payout)
                $vendorPenalty = round($paidAmount * 0.40, 2);

                // 10% → tetap di platform/admin (tidak diproses ke mana-mana)
                $platformPenalty = round($paidAmount * 0.10, 2);

                // Buat Payout record untuk vendor (kompensasi 40%)
                $payout = Payout::create([
                    'vendor_id'    => $changeRequest->vendor_id,
                    'period_start' => $booking->start_at->toDateString(),
                    'period_end'   => $booking->end_at->toDateString(),
                    'amount'       => $vendorPenalty,
                    'status'       => 'pending',
                    'notes'        => 'Kompensasi pembatalan booking #' . $booking->code
                                    . ' (40% dari Rp ' . number_format($paidAmount, 0, ',', '.') . ')'
                                    . ' — customer tidak dapat mobil pengganti.',
                ]);

                // Buat CustomerRefund record (50%)
                CustomerRefund::updateOrCreate(
                    ['booking_id' => $booking->id],
                    [
                        'customer_id'       => $booking->customer_id,
                        'amount'            => $refundAmount,
                        'status'            => 'pending',
                        'notes'             => 'Refund 50% karena vendor tidak memiliki mobil pengganti yang sesuai kapasitas penumpang.',
                        'bank_name'         => $booking->customer->bank_name,
                        'bank_account_no'   => $booking->customer->bank_account_no,
                        'bank_account_name' => $booking->customer->bank_account_name,
                    ]
                );

                // Catat rincian pembagian di change request
                $changeRequest->update([
                    'vendor_penalty_amount'   => $vendorPenalty,
                    'platform_penalty_amount' => $platformPenalty,
                    'vendor_payout_id'        => $payout->id,
                ]);
            }
        });

        // Notifikasi ke customer
        try {
            $changeRequest->customer->user->notify(new CarChangeRejectedNotification($changeRequest));
        } catch (\Throwable) {}

        // Notifikasi ke semua Admin
        try {
            \App\Models\User::where('role', 'admin')->get()->each(function ($admin) use ($booking) {
                \Filament\Notifications\Notification::make()
                    ->title('❌ Ganti Mobil Ditolak — Refund 50% Pending')
                    ->body('Vendor menolak ganti mobil pada booking #' . $booking->code . '. Booking dibatalkan & refund 50% menunggu transfer admin.')
                    ->warning()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->label('Proses Refund Customer')
                            ->button()
                            ->url('/admin/customer-refunds'),
                    ])
                    ->sendToDatabase($admin);
            });
        } catch (\Throwable) {}
    }

    /**
     * Admin mengkonfirmasi bukti pembayaran selisih dari customer.
     * Dipanggil dari Filament action admin panel.
     */
    public static function adminConfirmPayment(CarChangeRequest $changeRequest): void
    {
        $booking = $changeRequest->booking;

        DB::transaction(function () use ($changeRequest, $booking) {
            // Hitung ulang platform fee
            $newTotal = $changeRequest->new_total ?? $booking->total;
            $platformFeeRate = $booking->total > 0
                ? ($booking->platform_fee / $booking->total)
                : 0.05;
            $newPlatformFee   = round($newTotal * $platformFeeRate, 2);
            $newVendorPayout  = $newTotal - $newPlatformFee;

            $withDriver = isset($changeRequest->with_driver) ? (bool) $changeRequest->with_driver : $booking->with_driver;
            $bookingData = [
                'car_id'               => $changeRequest->new_car_id,
                'with_driver'          => $withDriver,
                'subtotal'             => $newTotal,
                'total'                => $newTotal,
                'platform_fee'         => $newPlatformFee,
                'vendor_payout_amount' => $newVendorPayout,
            ];

            if (!$withDriver) {
                $bookingData['driver_id'] = null;
            } elseif ($withDriver && !$booking->driver_id) {
                $availableDriver = \App\Models\Driver::where('vendor_id', $booking->vendor_id)
                    ->where('status', 'active')
                    ->get()
                    ->first(fn ($d) => $d->isAvailableOn($booking->start_at, $booking->end_at));
                if ($availableDriver) {
                    $bookingData['driver_id'] = $availableDriver->id;
                }
            }

            // Update booking dengan mobil baru, opsi driver baru, dan total baru
            $booking->update($bookingData);

            // Tandai pembayaran selisih sudah dikonfirmasi
            $changeRequest->update(['additional_payment_at' => now()]);
        });

        // Notifikasi ke customer
        try {
            $changeRequest->customer->user->notify(new CarChangePaymentConfirmedNotification($changeRequest));
        } catch (\Throwable) {}
    }
}
