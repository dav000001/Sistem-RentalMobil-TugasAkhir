@extends('layouts.app')

@section('title', 'Pesanan Saya - Rental Mobil')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Pesanan Saya</h1>
            <p class="text-gray-500 mt-1">Kelola semua pesanan rental mobil Anda</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('customer.recap.index') }}"
               style="display:inline-flex;align-items:center;gap:8px;background:#7c3aed;color:#ffffff;padding:10px 18px;border-radius:10px;font-weight:700;font-size:14px;text-decoration:none;box-shadow:0 2px 8px rgba(124,58,237,0.35);border:none;"
               onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">
                <svg style="width:16px;height:16px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Laporan Rekapitulasi
            </a>
            <a href="{{ route('search') }}"
               class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition font-semibold text-sm shadow-sm">
                🔍 Cari Mobil Baru
            </a>
        </div>
    </div>


    {{-- Stats Bar --}}
    @php
        $allBookings = auth()->user()->customer?->bookings ?? collect();
        $stats = [
            'total'     => $allBookings->count(),
            'active'    => $allBookings->whereIn('status', ['awaiting_payment','awaiting_vendor','confirmed','ongoing'])->count(),
            'completed' => $allBookings->where('status','completed')->count(),
            'cancelled' => $allBookings->where('status','cancelled')->count(),
            'late'      => $allBookings->where('status','completed')->where('is_late',true)->count(),
        ];
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <a href="{{ route('bookings.index') }}"
           class="bg-white rounded-xl p-4 shadow-sm border-2 {{ !request('status') && !request('late_filter') ? 'border-blue-500' : 'border-transparent hover:border-gray-200' }} transition text-center">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            <p class="text-sm text-gray-500 mt-1">Semua Pesanan</p>
        </a>
        <a href="{{ route('bookings.index', ['status' => 'active']) }}"
           class="bg-white rounded-xl p-4 shadow-sm border-2 {{ request('status') === 'active' ? 'border-blue-500' : 'border-transparent hover:border-gray-200' }} transition text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['active'] }}</p>
            <p class="text-sm text-gray-500 mt-1">Aktif</p>
        </a>
        <a href="{{ route('bookings.index', ['status' => 'completed']) }}"
           class="bg-white rounded-xl p-4 shadow-sm border-2 {{ request('status') === 'completed' && !request('late_filter') ? 'border-blue-500' : 'border-transparent hover:border-gray-200' }} transition text-center">
            <p class="text-2xl font-bold text-green-600">{{ $stats['completed'] }}</p>
            <p class="text-sm text-gray-500 mt-1">Selesai</p>
        </a>
        <a href="{{ route('bookings.index', ['status' => 'cancelled']) }}"
           class="bg-white rounded-xl p-4 shadow-sm border-2 {{ request('status') === 'cancelled' ? 'border-blue-500' : 'border-transparent hover:border-gray-200' }} transition text-center">
            <p class="text-2xl font-bold text-red-500">{{ $stats['cancelled'] }}</p>
            <p class="text-sm text-gray-500 mt-1">Dibatalkan</p>
        </a>
        <a href="{{ route('bookings.index', ['late_filter' => 'late']) }}"
           class="bg-white rounded-xl p-4 shadow-sm border-2 {{ request('late_filter') === 'late' ? 'border-red-500' : 'border-transparent hover:border-gray-200' }} transition text-center">
            <p class="text-2xl font-bold text-red-600">{{ $stats['late'] }}</p>
            <p class="text-sm text-gray-500 mt-1">⚠️ Pernah Terlambat</p>
        </a>
    </div>

    {{-- Banner Denda Belum Lunas --}}
    @php
        $customer = auth()->user()->customer;
        $unpaidFineCharge = $customer?->getUnpaidLateFeeCharge();
    @endphp
    @if($customer?->hasUnpaidLateFee())
    <div class="bg-red-50 border-2 border-red-400 rounded-xl p-5 mb-6 flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <div class="text-3xl flex-shrink-0">🚨</div>
        <div class="flex-1">
            <p class="font-bold text-red-800 text-base">Anda Memiliki Denda Keterlambatan yang Belum Dilunasi</p>
            <p class="text-red-700 text-sm mt-1">
                Pemesanan mobil baru <strong>tidak dapat dilakukan</strong> sampai denda dilunasi.
                @if($unpaidFineCharge)
                    Jumlah denda: <strong>Rp {{ number_format($unpaidFineCharge->amount, 0, ',', '.') }}</strong>
                    ({{ match($unpaidFineCharge->status) { 'pending' => 'Menunggu Konfirmasi Admin', 'confirmed' => 'Menunggu Pembayaran Anda', default => ucfirst($unpaidFineCharge->status) } }})
                @endif
            </p>
        </div>
        @if($unpaidFineCharge?->booking)
        <a href="{{ route('bookings.show', $unpaidFineCharge->booking->code) }}"
           class="flex-shrink-0 inline-flex items-center gap-2 bg-red-600 text-white px-5 py-2.5 rounded-lg hover:bg-red-700 font-semibold text-sm transition">
            💸 Lunasi Denda Sekarang
        </a>
        @endif
    </div>
    @endif

    {{-- Filter & Search Bar --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-col sm:flex-row gap-3">
        <form method="GET" action="{{ route('bookings.index') }}" class="flex flex-1 gap-3 flex-wrap">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari kode booking atau nama mobil..."
                   class="flex-1 min-w-48 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <select name="sort" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="newest" {{ request('sort','newest') === 'newest' ? 'selected' : '' }}>Terbaru</option>
                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Terlama</option>
                <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>Harga Tertinggi</option>
                <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>Harga Terendah</option>
            </select>
            {{-- Filter status keterlambatan --}}
            <select name="late_filter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status Pengembalian</option>
                <option value="late"   {{ request('late_filter') === 'late'   ? 'selected' : '' }}>⚠️ Terlambat</option>
                <option value="ontime" {{ request('late_filter') === 'ontime' ? 'selected' : '' }}>✅ Tepat Waktu</option>
            </select>
            {{-- Filter rentang tanggal --}}
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   placeholder="Dari tanggal"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   placeholder="Sampai tanggal"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-semibold">
                Filter
            </button>
            @if(request()->hasAny(['search','sort','status','late_filter','date_from','date_to']))
                <a href="{{ route('bookings.index') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm">
                    ✕ Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Booking List --}}
    @if($bookings->count() > 0)
        <div class="space-y-4">
            @foreach($bookings as $booking)
                @php
                    $statusConfig = match($booking->status) {
                        'awaiting_payment' => ['label' => '⏳ Menunggu Pembayaran', 'color' => 'bg-yellow-100 text-yellow-800 border-yellow-200'],
                        'awaiting_vendor'  => ['label' => '🔔 Menunggu Konfirmasi', 'color' => 'bg-blue-100 text-blue-800 border-blue-200'],
                        'confirmed'        => ['label' => '✅ Dikonfirmasi',         'color' => 'bg-green-100 text-green-800 border-green-200'],
                        'ongoing'          => ['label' => '🚗 Sedang Berlangsung',   'color' => 'bg-purple-100 text-purple-800 border-purple-200'],
                        'completed'        => ['label' => '🏁 Selesai',              'color' => 'bg-gray-100 text-gray-700 border-gray-200'],
                        'cancelled'        => ['label' => '❌ Dibatalkan',           'color' => 'bg-red-100 text-red-800 border-red-200'],
                        default            => ['label' => ucfirst($booking->status), 'color' => 'bg-gray-100 text-gray-700 border-gray-200'],
                    };
                    $days = $booking->start_at->diffInDays($booking->end_at) ?: 1;
                @endphp

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                    {{-- Top bar: status + kode --}}
                    <div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-mono text-gray-500">{{ $booking->code }}</span>
                            <span class="text-gray-300">|</span>
                            <span class="text-xs text-gray-500">{{ $booking->created_at->format('d M Y H:i') }}</span>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusConfig['color'] }}">
                            {{ $statusConfig['label'] }}
                        </span>
                    </div>

                    <div class="p-5">
                        <div class="flex flex-col md:flex-row gap-4">
                            {{-- Foto Mobil --}}
                            <div class="flex-shrink-0">
                                <img src="{{ $booking->car->photos->first()?->path ?? 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=200&q=80' }}"
                                     alt="{{ $booking->car->brand }}"
                                     class="w-full md:w-32 h-24 object-cover rounded-lg">
                            </div>

                            {{-- Info Utama --}}
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-lg text-gray-900 truncate">
                                    {{ $booking->car->brand }} {{ $booking->car->model }} {{ $booking->car->year }}
                                </h3>
                                <p class="text-sm text-gray-500 mt-0.5">🏢 {{ $booking->vendor?->business_name ?? '—' }}</p>

                                <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-sm text-gray-600">
                                    <span>📅 {{ $booking->start_at->format('d M Y') }} → {{ $booking->end_at->format('d M Y') }}</span>
                                    <span>⏱ {{ $days }} hari</span>
                                    @if($booking->with_driver)
                                        <span class="text-blue-600">🧑‍✈️ Dengan Sopir</span>
                                    @endif
                                </div>

                                {{-- Progress bar untuk booking aktif --}}
                                @if(in_array($booking->status, ['confirmed','ongoing']))
                                    @php
                                        $totalDays = max(1, $booking->start_at->diffInDays($booking->end_at));
                                        $elapsed   = max(0, min($totalDays, now()->diffInDays($booking->start_at, false) * -1));
                                        $progress  = $booking->status === 'ongoing' ? min(100, round(($elapsed / $totalDays) * 100)) : 0;
                                    @endphp
                                    <div class="mt-2">
                                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                                            <span>Progres Sewa</span>
                                            <span>{{ $progress }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-blue-600 h-1.5 rounded-full transition-all" style="width: {{ $progress }}%"></div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Refund info untuk cancelled --}}
                                @if($booking->status === 'cancelled' && $booking->payment?->status === 'paid')
                                    <div class="mt-2 text-xs text-orange-600 bg-orange-50 px-2 py-1 rounded inline-block">
                                        💸 Refund sedang diproses
                                    </div>
                                @elseif($booking->status === 'cancelled' && $booking->payment?->status === 'refunded')
                                    <div class="mt-2 text-xs text-green-600 bg-green-50 px-2 py-1 rounded inline-block">
                                        ✅ Refund sudah dikirim
                                    </div>
                                @endif

                                {{-- Badge keterlambatan untuk completed --}}
                                @if($booking->status === 'completed')
                                    @if($booking->is_late)
                                        <div class="mt-2 text-xs text-red-600 bg-red-50 border border-red-200 px-2 py-1 rounded inline-flex items-center gap-1">
                                            ⚠️ Terlambat {{ $booking->late_duration_hours }} jam
                                            — Denda: Rp {{ number_format($booking->late_fee, 0, ',', '.') }}
                                        </div>
                                    @elseif($booking->actual_return_at)
                                        <div class="mt-2 text-xs text-green-600 bg-green-50 px-2 py-1 rounded inline-block">
                                            ✅ Dikembalikan tepat waktu
                                        </div>
                                    @endif
                                @endif
                            </div>

                            {{-- Harga + Aksi --}}
                            <div class="flex flex-row md:flex-col items-center md:items-end justify-between md:justify-start gap-3 md:min-w-36">
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">Total</p>
                                    <p class="text-xl font-bold text-blue-600">{{ formatRupiah($booking->total) }}</p>
                                </div>

                                <div class="flex flex-col gap-2 items-end">
                                    @if($booking->status === 'awaiting_payment')
                                        <a href="{{ route('bookings.pay', $booking) }}"
                                           class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-xs font-bold whitespace-nowrap">
                                            💳 Bayar Sekarang
                                        </a>
                                    @endif
                                    <a href="{{ route('bookings.show', $booking) }}"
                                       class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-xs font-semibold whitespace-nowrap">
                                        Lihat Detail →
                                    </a>
                                    @if($booking->status === 'completed' && !$booking->review)
                                        <a href="{{ route('bookings.review', $booking->code) }}"
                                           class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 text-xs font-semibold whitespace-nowrap">
                                            ⭐ Beri Review
                                        </a>
                                    @endif
                                    @if($booking->status === 'completed')
                                        <a href="{{ route('bookings.invoice', $booking) }}"
                                           class="text-gray-600 hover:text-gray-800 text-xs underline whitespace-nowrap">
                                            📄 Invoice
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $bookings->appends(request()->query())->links() }}
        </div>
    @else
        <div class="text-center py-16 bg-white rounded-xl shadow-sm">
            <div class="text-6xl mb-4">📋</div>
            <h3 class="text-xl font-semibold mb-2 text-gray-900">
                @if(request('status') === 'active') Tidak ada pesanan aktif
                @elseif(request('status') === 'completed') Belum ada pesanan selesai
                @elseif(request('status') === 'cancelled') Tidak ada pesanan dibatalkan
                @elseif(request('search')) Tidak ada hasil untuk "{{ request('search') }}"
                @else Belum ada pesanan
                @endif
            </h3>
            <p class="text-gray-500 mb-6">Mulai pesan mobil sekarang dan nikmati perjalanan Anda</p>
            <a href="{{ route('search') }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 inline-block font-semibold">
                🔍 Cari Mobil
            </a>
        </div>
    @endif
</div>
@endsection
