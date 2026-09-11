<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Car;
use App\Http\Requests\Web\StoreBookingRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $customer = auth()->user()->customer;

        if (!$customer) {
            return redirect(route('home'))->with('error', 'Anda bukan customer');
        }

        $query = $customer->bookings()
            ->with(['car.photos', 'vendor', 'payment', 'review']);

        // Filter by status group
        if ($request->filled('status')) {
            match ($request->status) {
                'active'    => $query->whereIn('status', ['awaiting_payment','awaiting_vendor','confirmed','ongoing']),
                'completed' => $query->where('status', 'completed'),
                'cancelled' => $query->where('status', 'cancelled'),
                default     => null,
            };
        }

        // Filter keterlambatan (hanya untuk booking completed)
        if ($request->filled('late_filter')) {
            if ($request->late_filter === 'late') {
                $query->where('status', 'completed')->where('is_late', true);
            } elseif ($request->late_filter === 'ontime') {
                $query->where('status', 'completed')->where('is_late', false);
            }
        }

        // Filter rentang tanggal pengembalian
        if ($request->filled('date_from')) {
            $query->whereDate('end_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('end_at', '<=', $request->date_to);
        }

        // Search by booking code or car name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('car', fn ($c) => $c->where('brand', 'like', "%{$search}%")
                      ->orWhere('model', 'like', "%{$search}%"));
            });
        }

        // Sort
        match ($request->get('sort', 'newest')) {
            'oldest'     => $query->oldest(),
            'price_high' => $query->orderByDesc('total'),
            'price_low'  => $query->orderBy('total'),
            default      => $query->latest(),
        };

        $bookings = $query->paginate(10)->withQueryString();

        return view('public.bookings.index', compact('bookings'));
    }

    public function create(Car $car)
    {
        // Tolak jika bukan published sama sekali
        abort_unless($car->isPublished() || $car->status === 'unavailable', 404);

        // Jika sedang unavailable, redirect ke halaman detail dengan pesan
        if ($car->isCurrentlyUnavailable()) {
            $label = match($car->unavailability_reason) {
                'service'    => 'sedang dalam proses service',
                'rusak'      => 'sedang dalam kondisi rusak',
                'kecelakaan' => 'sedang dalam penanganan kecelakaan',
                default      => 'tidak tersedia saat ini',
            };
            return redirect()
                ->route('cars.show', $car->slug)
                ->with('error', "Maaf, mobil ini {$label} dan tidak dapat dipesan.");
        }

        $customer = auth()->user()->customer;
        if (!$customer || $customer->verification_status !== 'verified') {
            return redirect(route('profile.edit'))->with('error', 'Silakan verifikasi identitas terlebih dahulu.');
        }

        // Blokir booking jika customer memiliki denda keterlambatan yang belum lunas
        if ($customer->hasUnpaidLateFee()) {
            $unpaidCharge = $customer->getUnpaidLateFeeCharge();
            if ($unpaidCharge && $unpaidCharge->booking) {
                return redirect()
                    ->route('bookings.show', $unpaidCharge->booking->code)
                    ->with('error', 'Anda tidak dapat memesan mobil baru karena masih memiliki denda keterlambatan yang belum dilunasi. Silakan lunasi denda Anda terlebih dahulu.');
            }

            return redirect()
                ->route('bookings.index')
                ->with('error', 'Anda tidak dapat memesan mobil baru karena masih memiliki denda keterlambatan yang belum dilunasi. Silakan lunasi denda Anda terlebih dahulu.');
        }

        return view('public.bookings.create', compact('car'));
    }

    public function store(StoreBookingRequest $request, Car $car)
    {
        $customer = auth()->user()->customer;

        // Blokir submit booking jika customer memiliki denda keterlambatan yang belum lunas
        if (!$customer || $customer->hasUnpaidLateFee()) {
            return back()->with('error', 'Anda tidak dapat memesan mobil baru karena masih memiliki denda keterlambatan yang belum dilunasi. Silakan lunasi denda Anda terlebih dahulu.');
        }

        $validated = $request->validated();

        $startAt = \Carbon\Carbon::parse($validated['start_at']);
        $endAt   = \Carbon\Carbon::parse($validated['end_at']);

        // Gunakan atomic lock untuk mencegah race condition double booking
        // Key unik per customer + mobil + tanggal
        $lockKey = "booking:{$customer->id}:{$car->id}:{$startAt->toDateString()}:{$endAt->toDateString()}";

        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 10);

        if (!$lock->get()) {
            return back()->with('error', 'Permintaan sedang diproses. Silakan tunggu sebentar.');
        }

        try {
            // Cek ulang di dalam lock untuk memastikan tidak ada race condition
            $duplicate = $customer->bookings()
                ->where('car_id', $car->id)
                ->whereIn('status', ['awaiting_payment', 'awaiting_vendor', 'confirmed', 'ongoing'])
                ->where(function ($q) use ($startAt, $endAt) {
                    $q->whereBetween('start_at', [$startAt, $endAt])
                      ->orWhereBetween('end_at', [$startAt, $endAt])
                      ->orWhere(function ($q2) use ($startAt, $endAt) {
                          $q2->where('start_at', '<=', $startAt)
                             ->where('end_at', '>=', $endAt);
                      });
                })
                ->exists();

            if ($duplicate) {
                return back()->with('error', 'Anda sudah memiliki pesanan aktif untuk mobil ini pada tanggal tersebut.');
            }

            // Calculate duration
            $days = $startAt->diffInDays($endAt);
            if ($days == 0) $days = 1;

            // Calculate price
            $dailyPrice = $car->pricing->daily_price;
            $subtotal   = $dailyPrice * $days;

            if ($validated['with_driver'] ?? false) {
                $subtotal += ($car->pricing->with_driver_price ?? 0) * $days;
            }

            $vendor         = $car->vendor;
            $commissionRate = $vendor->getCommissionRate();
            $platformFee    = round($subtotal * $commissionRate, 2);
            $total          = $subtotal;
            $vendorPayout   = $subtotal - $platformFee;

            // Gunakan driver_id pilihan customer jika ada, fallback ke auto-assign
            $driverId = null;
            if ($validated['with_driver'] ?? false) {
                // Prioritaskan pilihan customer
                $chosenDriverId = $request->input('driver_id');
                if ($chosenDriverId) {
                    // Validasi sopir milik vendor ini dan tersedia di tanggal booking
                    $chosenDriver = \App\Models\Driver::where('id', $chosenDriverId)
                        ->where('vendor_id', $car->vendor_id)
                        ->where('status', 'active')
                        ->first();

                    if ($chosenDriver && $chosenDriver->isAvailableOn($startAt, $endAt)) {
                        $driverId = $chosenDriver->id;
                    }
                }

                // Fallback auto-assign jika pilihan tidak valid
                if (!$driverId) {
                    $availableDriver = \App\Models\Driver::where('vendor_id', $car->vendor_id)
                        ->where('status', 'active')
                        ->get()
                        ->first(fn ($d) => $d->isAvailableOn($startAt, $endAt));
                    $driverId = $availableDriver?->id;
                }
            }

            $booking = Booking::create([
                'code'                 => 'BK-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'customer_id'          => $customer->id,
                'car_id'               => $car->id,
                'vendor_id'            => $car->vendor_id,
                'driver_id'            => $driverId,
                'start_at'             => $startAt,
                'end_at'               => $endAt,
                'with_driver'          => $validated['with_driver'] ?? false,
                'passenger_count'      => $validated['passenger_count'] ?? 1,
                'pickup_location'      => $validated['pickup_location'],
                'dropoff_location'     => $validated['dropoff_location'] ?? null,
                'subtotal'             => $subtotal,
                'addon_fees'           => 0,
                'discount'             => 0,
                'total'                => $total,
                'platform_fee'         => $platformFee,
                'vendor_payout_amount' => $vendorPayout,
                'status'               => 'awaiting_payment',
                'notes'                => $validated['notes'],
            ]);

        } finally {
            $lock->release();
        }

        // Notifikasi ke vendor (langsung ke DB, non-queue)
        try {
            $car->vendor->user->notify(new \App\Notifications\NewBookingVendorNotification($booking));
        } catch (\Throwable) {}

        // Notifikasi ke semua admin (langsung ke DB, non-queue)
        \App\Models\User::where('role', 'admin')->get()->each(function ($admin) use ($booking) {
            try {
                $admin->notify(new \App\Notifications\NewBookingAdminNotification($booking));
            } catch (\Throwable) {}
        });

        // Schedule auto-cancel job after 24 hours
        // Paksa pakai queue 'database' agar delay benar-benar 24 jam
        // (tidak terpengaruh QUEUE_CONNECTION=sync)
        \App\Jobs\AutoCancelUnconfirmedBookingJob::dispatch($booking)
            ->onQueue('default')
            ->delay(now()->addDay());

        return redirect(route('bookings.show', $booking))->with('success', 'Booking berhasil dibuat');
    }

    public function show(Booking $booking)
    {
        Gate::authorize('view', $booking);
        $booking->load(['car.photos', 'vendor', 'payment', 'handoverLogs', 'customer', 'refund', 'dispute.admin', 'dispute.openedBy', 'compensationCharge', 'driver', 'lateFeeCharge', 'lateReturnReport']);
        return view('public.bookings.show', compact('booking'));
    }

    public function cancel(Booking $booking)
    {
        Gate::authorize('cancel', $booking);

        $service = app(\App\Services\BookingService::class);

        if (!$service->canBeCancelled($booking)) {
            return back()->with('error', 'Booking tidak bisa dibatalkan');
        }

        $success = $service->cancelByCustomer($booking, 'Dibatalkan oleh customer');

        if (!$success) {
            return back()->with('error', 'Booking tidak bisa dibatalkan');
        }

        auth()->user()->notify(new \App\Notifications\BookingCancelledNotification($booking));
        return back()->with('success', 'Booking berhasil dibatalkan');
    }

    public function invoice(Booking $booking)
    {
        Gate::authorize('view', $booking);
        $booking->load(['car.pricing', 'vendor.city', 'customer.user', 'payment']);
        $pdf = Pdf::loadView('pdf.invoice', compact('booking'));
        return $pdf->download('invoice-' . $booking->code . '.pdf');
    }
}
