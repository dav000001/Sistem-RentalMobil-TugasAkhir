@extends('layouts.app')

@section('title', 'Detail Pesanan ' . $booking->code . ' - Rental Mobil')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Pesanan {{ $booking->code }}</h1>
        <div class="flex items-center space-x-4">
            <span class="px-3 py-1 rounded-full text-sm font-semibold
                @if($booking->status === 'awaiting_payment') bg-yellow-100 text-yellow-800
                @elseif($booking->status === 'awaiting_vendor') bg-blue-100 text-blue-800
                @elseif($booking->status === 'confirmed') bg-green-100 text-green-800
                @elseif($booking->status === 'completed') bg-gray-100 text-gray-800
                @elseif($booking->status === 'disputed') bg-orange-100 text-orange-800
                @else bg-red-100 text-red-800
                @endif">
                {{ match($booking->status) {
                    'awaiting_payment' => '⏳ Menunggu Pembayaran',
                    'awaiting_vendor'  => '🔔 Menunggu Konfirmasi Vendor',
                    'confirmed'        => '✅ Dikonfirmasi',
                    'ongoing'          => '🚗 Sedang Berlangsung',
                    'completed'        => '🏁 Selesai',
                    'cancelled'        => '❌ Dibatalkan',
                    'disputed'         => '⚠️ Sengketa',
                    default            => ucfirst(str_replace('_', ' ', $booking->status)),
                } }}
            </span>
            <span class="text-gray-600">{{ $booking->created_at->format('d M Y H:i') }}</span>
        </div>
    </div>

    {{-- Timeline Status --}}
    @php
        $steps = [
            ['key' => 'awaiting_payment', 'label' => 'Pesanan Dibuat',       'icon' => '📋', 'desc' => 'Menunggu pembayaran'],
            ['key' => 'awaiting_vendor',  'label' => 'Pembayaran Diterima',  'icon' => '💳', 'desc' => 'Menunggu konfirmasi vendor'],
            ['key' => 'confirmed',        'label' => 'Dikonfirmasi Vendor',  'icon' => '✅', 'desc' => 'Pesanan aktif'],
            ['key' => 'ongoing',          'label' => 'Sedang Berlangsung',   'icon' => '🚗', 'desc' => 'Mobil sedang digunakan'],
            ['key' => 'completed',        'label' => 'Selesai',              'icon' => '🏁', 'desc' => 'Rental selesai'],
        ];
        $statusOrder = ['awaiting_payment','awaiting_vendor','confirmed','ongoing','completed'];
        $currentIndex = array_search($booking->status, $statusOrder);
        $isCancelled = $booking->status === 'cancelled';
    @endphp

    @if(!$isCancelled)
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="font-semibold text-base mb-5 text-gray-700">Progres Pesanan</h3>
        <div class="relative">
            {{-- Garis penghubung --}}
            <div class="absolute top-5 left-5 right-5 h-0.5 bg-gray-200" style="z-index:0"></div>
            <div class="flex justify-between relative" style="z-index:1">
                @foreach($steps as $i => $step)
                    @php
                        $done    = $currentIndex !== false && $i < $currentIndex;
                        $current = $currentIndex !== false && $i === $currentIndex;
                        $pending = $currentIndex !== false && $i > $currentIndex;
                    @endphp
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg font-bold border-2 mb-2
                            {{ $done    ? 'bg-green-500 border-green-500 text-white' : '' }}
                            {{ $current ? 'bg-blue-600 border-blue-600 text-white ring-4 ring-blue-100' : '' }}
                            {{ $pending ? 'bg-white border-gray-300 text-gray-400' : '' }}">
                            @if($done)
                                ✓
                            @else
                                {{ $step['icon'] }}
                            @endif
                        </div>
                        <p class="text-xs font-semibold text-center leading-tight
                            {{ $done ? 'text-green-600' : ($current ? 'text-blue-700' : 'text-gray-400') }}">
                            {{ $step['label'] }}
                        </p>
                        <p class="text-xs text-center text-gray-400 mt-0.5 hidden sm:block">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @else
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8 flex items-start gap-3">
        <span class="text-2xl mt-0.5">❌</span>
        <div>
            <p class="font-semibold text-red-700">Pesanan Dibatalkan</p>
            <p class="text-red-600 text-sm">Pesanan ini telah dibatalkan dan tidak dapat dilanjutkan.</p>

            @if($booking->payment?->status === 'refunded')
                {{-- Sudah direfund --}}
                <div class="mt-3 bg-blue-50 border border-blue-200 rounded-lg p-3 space-y-2">
                    <p class="text-sm font-semibold text-blue-800">💸 Refund Sudah Diproses</p>
                    <p class="text-sm text-blue-700">
                        Dana sebesar <strong>Rp {{ number_format($booking->payment->amount, 0, ',', '.') }}</strong>
                        sudah dikirim ke rekening Anda
                        @if($booking->customer->bank_account_no)
                            (<strong>{{ $booking->customer->bank_name }}</strong> {{ $booking->customer->bank_account_no }})
                        @endif
                        pada {{ $booking->payment->refunded_at?->format('d M Y H:i') ?? '—' }}.
                    </p>
                    @if($booking->payment->refund_ref)
                        <p class="text-xs text-blue-600">Nomor referensi: <strong>{{ $booking->payment->refund_ref }}</strong></p>
                    @endif

                    {{-- Bukti transfer refund dari admin --}}
                    @php $refundRecord = $booking->refund; @endphp
                    @if($refundRecord?->transfer_proof)
                        <div class="pt-2 border-t border-blue-200">
                            <p class="text-xs font-semibold text-blue-700 mb-2">📎 Bukti Transfer dari Admin:</p>
                            <a href="{{ asset('storage/' . $refundRecord->transfer_proof) }}" target="_blank">
                                <img src="{{ asset('storage/' . $refundRecord->transfer_proof) }}"
                                     alt="Bukti Transfer Refund"
                                     class="max-h-48 rounded-lg border border-blue-200 cursor-pointer hover:opacity-90 transition">
                                <p class="text-xs text-blue-500 mt-1">🔍 Klik untuk lihat ukuran penuh</p>
                            </a>
                        </div>
                    @endif

                    <p class="text-xs text-gray-500">Dana biasanya masuk 1–3 hari kerja. Hubungi admin jika belum diterima.</p>
                </div>
            @elseif($booking->payment?->status === 'paid')
                {{-- Sudah bayar → ada refund --}}
                <div class="mt-3 bg-white border border-red-200 rounded-lg p-3 space-y-1">
                    <p class="text-sm font-semibold text-gray-800">💸 Pengembalian Dana (Refund)</p>
                    <p class="text-sm text-gray-700">
                        Uang Anda sebesar <strong>Rp {{ number_format($booking->total, 0, ',', '.') }}</strong>
                        akan dikembalikan oleh admin ke rekening yang Anda daftarkan.
                    </p>
                    @if($booking->customer->bank_account_no)
                        <p class="text-sm text-gray-600">
                            Rekening tujuan:
                            <strong>{{ $booking->customer->bank_name }}</strong>
                            {{ $booking->customer->bank_account_no }}
                            a.n. {{ $booking->customer->bank_account_name }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Proses refund biasanya 1–3 hari kerja. Hubungi admin jika belum diterima.</p>
                    @else
                        <p class="text-sm text-orange-600 font-medium mt-1">
                            ⚠️ Anda belum mengisi info rekening.
                            <a href="{{ route('profile.edit') }}" class="underline text-blue-600">Isi sekarang di halaman Profil</a>
                            agar admin dapat memproses refund.
                        </p>
                    @endif
                </div>
            @elseif($booking->payment && $booking->payment->status !== 'paid')
                {{-- Upload bukti tapi belum dikonfirmasi → tidak ada refund --}}
                <p class="text-sm text-gray-600 mt-2">
                    Pembayaran Anda belum dikonfirmasi admin, sehingga tidak ada dana yang perlu dikembalikan.
                </p>
            @else
                {{-- Belum bayar sama sekali --}}
                <p class="text-sm text-gray-600 mt-2">
                    Anda belum melakukan pembayaran, sehingga tidak ada dana yang perlu dikembalikan.
                </p>
            @endif
        </div>
    </div>
    @endif

    {{-- ── Rekomendasi Mobil Serupa (hanya muncul saat cancelled) ─── --}}
    <x-car-recommendations :booking="$booking" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Car Info -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="font-semibold text-lg mb-4">Mobil</h3>
                <div class="flex items-center space-x-4">
                    <img src="{{ $booking->car->photos->first()?->path ?? 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=200&q=80' }}" 
                         alt="{{ $booking->car->brand }}" class="w-24 h-24 object-cover rounded">
                    <div>
                        <h4 class="font-semibold text-lg">{{ $booking->car->brand }} {{ $booking->car->model }} {{ $booking->car->year }}</h4>
                        <p class="text-gray-600">Plat: {{ substr($booking->car->plate_number, 0, -3) }}***</p>
                        <p class="text-blue-600 font-semibold">{{ $booking->car->pricing ? formatRupiah($booking->car->pricing->daily_price) . '/hari' : '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Booking Details -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="font-semibold text-lg mb-4">Detail Pemesanan</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600 text-sm">Tanggal Mulai</p>
                            <p class="font-semibold">{{ $booking->start_at->format('d M Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">Tanggal Selesai</p>
                            <p class="font-semibold">{{ $booking->end_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Durasi</p>
                        <p class="font-semibold">{{ durationLabel($booking->start_at, $booking->end_at) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Jumlah Penumpang</p>
                        <p class="font-semibold flex items-center gap-1.5">
                            👥 {{ $booking->passenger_count ?? 1 }} orang
                            @if(($booking->passenger_count ?? 1) > $booking->car->seats)
                                <span class="text-xs font-semibold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">Melebihi Kapasitas Mobil ({{ $booking->car->seats }} seat)</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Lokasi Penjemputan</p>
                        <p class="font-semibold">{{ $booking->pickup_location }}</p>
                    </div>
                    @if($booking->dropoff_location)
                        <div>
                            <p class="text-gray-600 text-sm">Lokasi Pengembalian</p>
                            <p class="font-semibold">{{ $booking->dropoff_location }}</p>
                        </div>
                    @endif
                    @if($booking->with_driver)
                        {{-- ── Info Sopir ──────────────────────────────────────── --}}
                        @if($booking->driver)
                            {{-- Sopir sudah di-assign --}}
                            <div class="rounded-lg border-2 border-blue-200 bg-blue-50 p-4">
                                <p class="text-sm font-semibold text-blue-800 mb-3 flex items-center gap-2">
                                    🧑‍✈️ Informasi Sopir
                                    <span class="text-xs font-normal bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Sudah Ditugaskan</span>
                                </p>

                                {{-- Foto Sopir --}}
                                <div class="flex items-center gap-4 mb-4">
                                    @if($booking->driver->photo)
                                        <img src="{{ asset('storage/' . $booking->driver->photo) }}"
                                             alt="Foto {{ $booking->driver->name }}"
                                             class="w-20 h-20 rounded-full object-cover border-2 border-blue-300 shadow-sm flex-shrink-0">
                                    @else
                                        <div class="w-20 h-20 rounded-full bg-blue-200 flex items-center justify-center border-2 border-blue-300 flex-shrink-0">
                                            <span class="text-3xl">🧑‍✈️</span>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-semibold text-gray-900 text-base">{{ $booking->driver->name }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ $booking->driver->experience_years > 0
                                                ? '🏆 ' . $booking->driver->experience_years . ' tahun pengalaman'
                                                : '🏆 Pengalaman baru' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <div>
                                        <p class="text-xs text-gray-500">Nama Sopir</p>
                                        <p class="font-semibold text-gray-900">{{ $booking->driver->name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">No. Telepon</p>
                                        <p class="font-semibold text-gray-900">
                                            <a href="tel:{{ $booking->driver->phone }}"
                                               class="text-blue-600 hover:underline">
                                                📱 {{ $booking->driver->phone }}
                                            </a>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Pengalaman</p>
                                        <p class="font-medium text-gray-700">
                                            {{ $booking->driver->experience_years > 0
                                                ? $booking->driver->experience_years . ' tahun'
                                                : '—' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-3 pt-3 border-t border-blue-200 flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-xs text-blue-700 font-medium">Akses Portal Laporan Sopir:</span>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @php
                                            $driverPhoneDigits = preg_replace('/[^0-9]/', '', $booking->driver->phone ?? '');
                                            if (str_starts_with($driverPhoneDigits, '0')) {
                                                $driverPhoneDigits = '62' . substr($driverPhoneDigits, 1);
                                            }
                                            $waMsg = rawurlencode("Halo " . ($booking->driver->name ?? 'Sopir') . ", berikut link Portal Laporan Keterlambatan/Pengembalian Armada untuk booking " . $booking->code . ": " . route('driver.late-report.show', $booking->code));
                                            $waLink = "https://wa.me/" . $driverPhoneDigits . "?text=" . $waMsg;
                                        @endphp
                                        <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1.5 text-xs text-white bg-green-600 hover:bg-green-700 px-3 py-1.5 rounded-lg font-semibold transition shadow-sm">
                                            💬 Kirim Link ke WA Sopir
                                        </a>
                                        <a href="{{ route('driver.late-report.show', $booking->code) }}" target="_blank"
                                           class="inline-flex items-center gap-1.5 text-xs text-purple-700 bg-purple-100 hover:bg-purple-200 px-3 py-1.5 rounded-lg font-semibold transition shadow-sm">
                                            🧑‍✈️ Buka Portal Laporan Sopir
                                        </a>
                                    </div>
                                </div>
                                @if(in_array($booking->status, ['confirmed','ongoing']))
                                    <div class="mt-3 pt-3 border-t border-blue-200">
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->driver->phone) }}"
                                           target="_blank"
                                           class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                            💬 Chat WhatsApp Sopir
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @else
                            {{-- with_driver = true tapi belum ada sopir di-assign --}}
                            <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
                                <p class="text-sm font-semibold text-orange-800 flex items-center gap-2">
                                    🧑‍✈️ Sewa Dengan Sopir
                                </p>
                                @if(in_array($booking->status, ['awaiting_payment', 'awaiting_vendor']))
                                    <p class="text-sm text-orange-700 mt-1">
                                        ⏳ Sopir akan ditugaskan oleh vendor setelah pesanan dikonfirmasi.
                                        Informasi sopir akan muncul di sini.
                                    </p>
                                @else
                                    <p class="text-sm text-orange-700 mt-1">
                                        ⚠️ Sopir belum ditugaskan. Silakan hubungi vendor untuk konfirmasi.
                                    </p>
                                    @if($booking->vendor?->user?->phone)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->vendor->user->phone) }}"
                                           target="_blank"
                                           class="inline-flex items-center gap-2 mt-2 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                            💬 Hubungi Vendor
                                        </a>
                                    @endif
                                @endif
                            </div>
                        @endif
                    @endif

                    {{-- ── Info Keterlambatan (hanya muncul jika completed & is_late) ── --}}
                    @if($booking->status === 'completed' && $booking->is_late)
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mt-2">
                            <p class="font-semibold text-red-700 flex items-center gap-2 mb-2">
                                ⚠️ Pengembalian Terlambat
                            </p>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-gray-500">Jatuh Tempo</p>
                                    <p class="font-medium">{{ $booking->end_at->format('d M Y, H:i') }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Dikembalikan</p>
                                    <p class="font-medium text-red-600">
                                        {{ $booking->actual_return_at?->format('d M Y, H:i') ?? '—' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Durasi Terlambat</p>
                                    <p class="font-medium text-red-600">{{ $booking->late_duration_hours }} jam</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Denda Keterlambatan</p>
                                    <p class="font-semibold text-red-700">
                                        Rp {{ number_format($booking->late_fee, 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                            <p class="text-xs text-red-500 mt-2">
                                Denda dihitung berdasarkan harga harian dibagi 24 jam, dikali durasi keterlambatan (dibulatkan ke atas per jam, dengan toleransi 1 jam).
                            </p>
                        </div>
                    @elseif($booking->status === 'completed' && $booking->actual_return_at)
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 mt-2">
                            <p class="text-green-700 text-sm flex items-center gap-1">
                                ✅ Dikembalikan tepat waktu pada {{ $booking->actual_return_at->format('d M Y, H:i') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ══════════════════════════════════════════════
                 PERGANTIAN MOBIL (Car Upgrade / Change Request)
            ══════════════════════════════════════════════ --}}
            @php
                $changeReq = $booking->carChangeRequest;
                $canRequest = $booking->status === 'confirmed' && now()->lt($booking->start_at->subDay());
                $isLateRequest = $booking->status === 'confirmed' && now()->gte($booking->start_at->subDay());
            @endphp

            @if($changeReq || $canRequest || $isLateRequest)
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="font-semibold text-lg mb-1 flex items-center gap-2">
                    🔄 Pergantian Mobil
                    @if($changeReq)
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-medium
                            @if($changeReq->status === 'pending') bg-amber-100 text-amber-800
                            @elseif($changeReq->status === 'approved') bg-green-100 text-green-800
                            @elseif($changeReq->status === 'rejected') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-700
                            @endif">
                            {{ $changeReq->statusLabel() }}
                        </span>
                    @endif
                </h3>
                <p class="text-sm text-gray-500 mb-4">
                    Jika penumpang Anda melebihi kapasitas mobil, Anda dapat mengajukan ganti mobil berkapasitas lebih besar (maksimal H-1 sebelum masa sewa).
                </p>

                @if($changeReq)
                    {{-- ── Sudah Ada Permintaan ── --}}
                    <div class="border rounded-lg p-4 space-y-3 bg-gray-50">
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-xs text-gray-500">Jumlah Penumpang Aktual</p>
                                <p class="font-semibold text-gray-900">👥 {{ $changeReq->passenger_count }} orang</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Waktu Pengajuan</p>
                                <p class="font-medium text-gray-800">{{ $changeReq->requested_at?->format('d M Y, H:i') ?? '—' }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-xs text-gray-500">Alasan Pengajuan</p>
                                <p class="text-sm text-gray-700 font-medium">{{ $changeReq->reason }}</p>
                            </div>
                        </div>

                        {{-- Status: PENDING --}}
                        @if($changeReq->status === 'pending')
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                                <p class="text-sm text-amber-800 font-medium flex items-center gap-1.5">
                                    ⏳ Menunggu respon vendor...
                                </p>
                                <p class="text-xs text-amber-700 mt-1">
                                    Vendor akan memilih mobil pengganti berkapasitas sesuai dan menghitung selisih harga jika ada.
                                </p>
                            </div>
                            <form method="POST" action="{{ route('bookings.car-change.cancel', $booking->code) }}" onsubmit="return confirm('Yakin ingin membatalkan permintaan ganti mobil?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-600 hover:underline font-medium">
                                    🚫 Batalkan Permintaan Ganti Mobil
                                </button>
                            </form>

                        {{-- Status: APPROVED --}}
                        @elseif($changeReq->status === 'approved')
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3 space-y-2">
                                <p class="text-sm font-semibold text-green-800 flex items-center gap-1.5">
                                    ✅ Permintaan Diterima Vendor!
                                </p>
                                <p class="text-sm text-green-700">
                                    Mobil pengganti: <strong>{{ $changeReq->newCar?->brand }} {{ $changeReq->newCar?->model }}</strong> ({{ $changeReq->newCar?->seats }} seat)
                                </p>
                                @if($changeReq->price_difference > 0)
                                    <p class="text-sm text-green-800 font-bold">
                                        Selisih harga tambahan: Rp {{ number_format($changeReq->price_difference, 0, ',', '.') }}
                                    </p>
                                @else
                                    <p class="text-xs text-green-600">Tidak ada biaya tambahan untuk pergantian mobil ini.</p>
                                @endif
                                @if($changeReq->vendor_notes)
                                    <p class="text-xs text-gray-600 italic">Catatan Vendor: "{{ $changeReq->vendor_notes }}"</p>
                                @endif
                            </div>

                            {{-- Jika ada selisih harga dan belum bayar --}}
                            @if($changeReq->price_difference > 0 && !$changeReq->additional_payment_at)
                                @if(!$changeReq->additional_payment_proof)
                                    {{-- Form upload bukti bayar selisih --}}
                                    <div class="border-2 border-dashed border-blue-200 rounded-lg p-4 bg-blue-50 mt-3">
                                        <p class="text-sm font-semibold text-blue-900 mb-1">💳 Transfer Selisih Pembayaran</p>
                                        <p class="text-xs text-blue-700 mb-3">
                                            Transfer selisih sebesar <strong>Rp {{ number_format($changeReq->price_difference, 0, ',', '.') }}</strong> ke rekening berikut lalu upload bukti bayar.
                                        </p>

                                        @php
                                            $bankName   = config('payment.bank_name', 'BCA');
                                            $bankAcc    = config('payment.account_no', '1234567890');
                                            $bankHolder = config('payment.account_name', 'PT Rental Mobil Indonesia');
                                        @endphp

                                        <div class="bg-white border border-blue-200 rounded-lg p-3 text-xs text-blue-900 space-y-1 mb-3">
                                            <p class="font-bold text-xs text-blue-900">🏦 Rekening Tujuan Transfer Selisih:</p>
                                            <div class="grid grid-cols-3 gap-2 pt-1 text-xs">
                                                <div>
                                                    <span class="text-gray-500 block text-[11px]">Bank</span>
                                                    <span class="font-bold text-gray-900">{{ $bankName }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500 block text-[11px]">No. Rekening</span>
                                                    <span class="font-bold text-gray-900 tracking-wider">{{ $bankAcc }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500 block text-[11px]">Atas Nama</span>
                                                    <span class="font-bold text-gray-900">{{ $bankHolder }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <form method="POST" action="{{ route('bookings.car-change.upload-proof', $booking->code) }}" enctype="multipart/form-data">
                                            @csrf
                                            <div class="space-y-3">
                                                <div>
                                                    <input type="file" name="payment_proof" accept="image/*" required
                                                           class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:font-semibold hover:file:bg-blue-700 cursor-pointer">
                                                </div>
                                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                                                    📤 Upload Bukti Bayar Selisih
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                @else
                                    {{-- Bukti sudah diupload, menunggu konfirmasi admin --}}
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-center gap-2 mt-3">
                                        <span class="text-xl">📎</span>
                                        <div>
                                            <p class="text-sm font-semibold text-blue-800">Bukti Pembayaran Selisih Diupload</p>
                                            <p class="text-xs text-blue-600">Menunggu konfirmasi admin untuk memperbarui data mobil resmi Anda.</p>
                                        </div>
                                    </div>
                                @endif
                            @elseif($changeReq->additional_payment_at)
                                <div class="bg-green-100 border border-green-300 rounded-lg p-3 mt-3 text-sm text-green-800 font-semibold flex items-center gap-2">
                                    <span>🎉</span> Pembayaran selisih telah dikonfirmasi admin. Mobil rental Anda telah resmi diganti!
                                </div>
                            @endif

                        {{-- Status: REJECTED (Vendor batalkan + refund 50%) --}}
                        @elseif($changeReq->status === 'rejected')
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 space-y-2">
                                <p class="text-sm font-semibold text-red-800 flex items-center gap-1.5">
                                    ❌ Vendor Tidak Memiliki Mobil Pengganti yang Sesuai
                                </p>
                                <p class="text-sm text-red-700">
                                    Booking telah dibatalkan. Pembagian dana dari pembayaran Anda:
                                </p>
                                @php
                                    $paidTotal = (float) ($booking->payment->amount ?? 0);
                                    $refund50  = $paidTotal * 0.5;
                                    $vendor40  = $paidTotal * 0.4;
                                    $admin10   = $paidTotal * 0.1;
                                @endphp
                                <div class="grid grid-cols-3 gap-2 mt-1">
                                    <div class="bg-white border border-red-200 rounded-lg p-2 text-center">
                                        <p class="text-[11px] text-gray-500">Refund Anda</p>
                                        <p class="text-sm font-bold text-green-700">Rp {{ number_format($refund50, 0, ',', '.') }}</p>
                                        <p class="text-[10px] text-gray-400">(50%)</p>
                                    </div>
                                    <div class="bg-white border border-red-200 rounded-lg p-2 text-center">
                                        <p class="text-[11px] text-gray-500">Kompensasi Vendor</p>
                                        <p class="text-sm font-bold text-gray-700">Rp {{ number_format($vendor40, 0, ',', '.') }}</p>
                                        <p class="text-[10px] text-gray-400">(40%)</p>
                                    </div>
                                    <div class="bg-white border border-red-200 rounded-lg p-2 text-center">
                                        <p class="text-[11px] text-gray-500">Biaya Platform</p>
                                        <p class="text-sm font-bold text-gray-700">Rp {{ number_format($admin10, 0, ',', '.') }}</p>
                                        <p class="text-[10px] text-gray-400">(10%)</p>
                                    </div>
                                </div>
                                @if($changeReq->vendor_notes)
                                    <p class="text-xs text-gray-600 italic">Catatan Vendor: "{{ $changeReq->vendor_notes }}"</p>
                                @endif
                                <div class="pt-2 border-t border-red-200">
                                    <p class="text-xs text-red-600 font-medium">
                                        💸 Refund <strong>Rp {{ number_format($refund50, 0, ',', '.') }}</strong> sedang diproses oleh admin. Cek status refund di bagian atas halaman ini.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                @elseif($canRequest)
                    {{-- ── Belum Ada Permintaan & Masih Boleh Request ── --}}
                    <div x-data="{ open: false }">
                        <button @click="open = !open" type="button" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                            🚗 Ajukan Pergantian Mobil
                        </button>

                        <div x-show="open" x-cloak class="mt-4 border border-blue-200 rounded-lg p-4 bg-blue-50/50">
                            <form method="POST" action="{{ route('bookings.car-change.store', $booking->code) }}" class="space-y-4">
                                @csrf
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Jumlah Penumpang Aktual <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="passenger_count" min="1" max="100" required
                                           value="{{ old('passenger_count', $booking->passenger_count ?? 1) }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Mobil saat ini berkapasitas <strong>{{ $booking->car->seats }} seat</strong>.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Opsi Layanan Mobil Pengganti <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <label class="flex items-center p-2.5 border border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 text-xs">
                                            <input type="radio" name="with_driver" value="0" {{ old('with_driver', $booking->with_driver) ? '' : 'checked' }} class="mr-2 text-blue-600">
                                            <span>🔑 Lepas kunci (tanpa sopir)</span>
                                        </label>
                                        <label class="flex items-center p-2.5 border border-gray-300 rounded-lg cursor-pointer hover:border-purple-500 has-[:checked]:border-purple-500 has-[:checked]:bg-purple-50 text-xs">
                                            <input type="radio" name="with_driver" value="1" {{ old('with_driver', $booking->with_driver) ? 'checked' : '' }} class="mr-2 text-purple-600">
                                            <span>👨‍✈️ Dengan sopir</span>
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Alasan Pengajuan <span class="text-red-500">*</span>
                                    </label>
                                    <textarea name="reason" rows="3" required
                                              placeholder="Contoh: Jumlah rombongan bertambah dari 6 menjadi 12 orang..."
                                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"></textarea>
                                </div>

                                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800 flex items-start gap-2">
                                    <span class="text-base leading-none">ℹ️</span>
                                    <div>
                                        <strong>Ketentuan Pergantian:</strong> Vendor (<strong>{{ $booking->vendor->business_name }}</strong>) akan memilihkan mobil pengganti berkapasitas lebih besar dari armada yang sama. Jika harga lebih tinggi, Anda membayar selisihnya. Jika vendor tidak punya unit pengganti: booking dibatalkan dan Anda mendapatkan <strong>refund 50%</strong>.
                                    </div>
                                </div>

                                <div class="flex gap-2 justify-end">
                                    <button type="button" @click="open = false" class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-200">
                                        Batal
                                    </button>
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                        Kirim Pengajuan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                @elseif($isLateRequest)
                    {{-- ── Sudah Terlewat Batas H-1 ── --}}
                    <div class="bg-gray-100 border border-gray-200 rounded-lg p-3 text-xs text-gray-600">
                        ⏱️ Pengajuan pergantian mobil ditutup karena sudah melewati batas maksimal H-1 sebelum tanggal sewa ({{ $booking->start_at->format('d M Y H:i') }}).
                    </div>
                @endif
            </div>
            @endif

            {{-- ══════════════════════════════════════════════
                 PELAPORAN PENGEMBALIAN (status: ongoing)
            ══════════════════════════════════════════════ --}}
            @if($booking->status === 'ongoing')
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="font-semibold text-lg mb-1">⚠️ Pelaporan Pengembalian</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Jika Anda mengetahui akan terlambat mengembalikan mobil, segera laporkan ke vendor.
                    Saat mengembalikan, kirim juga lokasi Anda sebagai konfirmasi.
                </p>

                @if($booking->lateReturnReport)
                    @php $report = $booking->lateReturnReport; @endphp

                    {{-- Laporan sudah ada --}}
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4">
                        <p class="font-semibold text-orange-800 flex items-center gap-2 mb-2">
                            📋 Laporan Keterlambatan Terkirim
                            @if($report->isAcknowledged())
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">✅ Dilihat Vendor</span>
                            @else
                                <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">⏳ Menunggu Vendor</span>
                            @endif
                        </p>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <p class="text-gray-500 text-xs">Perkiraan Terlambat</p>
                                <p class="font-medium text-orange-700">~{{ $report->estimated_late_hours }} jam</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs">Dilaporkan</p>
                                <p class="font-medium">{{ $report->created_at->format('d M Y, H:i') }}</p>
                            </div>
                            @if($report->reason)
                            <div class="col-span-2">
                                <p class="text-gray-500 text-xs">Alasan</p>
                                <p class="font-medium">{{ $report->reason }}</p>
                            </div>
                            @endif
                            @if($report->hasLocation())
                            <div class="col-span-2">
                                <p class="text-gray-500 text-xs">Lokasi Saat Lapor</p>
                                <a href="{{ $report->getMapUrl() }}" target="_blank"
                                   class="text-blue-600 text-sm hover:underline flex items-center gap-1">
                                    📍 {{ $report->location_address ?? $report->latitude.', '.$report->longitude }}
                                    <span class="text-xs">(buka maps)</span>
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Konfirmasi Lokasi Pengembalian (jika belum dikonfirmasi) --}}
                    @if(!$report->return_confirmed_at)
                    <div class="border-2 border-dashed border-blue-200 rounded-lg p-4">
                        <p class="font-semibold text-blue-800 mb-1">📍 Konfirmasi Lokasi Pengembalian</p>
                        <p class="text-sm text-blue-600 mb-3">Saat Anda sudah di lokasi pengembalian, kirim lokasi GPS Anda sebagai bukti.</p>
                        <form method="POST" action="{{ route('late-return.confirm-return', $booking->code) }}" id="form-confirm-return">
                            @csrf
                            <input type="hidden" name="return_latitude" id="return_lat">
                            <input type="hidden" name="return_longitude" id="return_lng">
                            <input type="hidden" name="return_location_address" id="return_addr">
                            <div id="return-location-status" class="text-sm text-gray-500 mb-3">
                                📡 Klik tombol di bawah untuk deteksi lokasi otomatis.
                            </div>
                            <div class="flex gap-2 flex-wrap">
                                <button type="button" id="btn-get-return-location"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-semibold">
                                    📍 Deteksi Lokasi Saya
                                </button>
                                <button type="submit" id="btn-submit-return"
                                    class="hidden bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm font-semibold">
                                    ✅ Kirim Lokasi Pengembalian
                                </button>
                            </div>
                        </form>
                    </div>
                    @else
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                        <p class="text-green-700 text-sm font-medium">
                            ✅ Lokasi pengembalian sudah dikonfirmasi pada {{ $report->return_confirmed_at->format('d M Y, H:i') }}
                        </p>
                        @if($report->hasReturnLocation())
                        <a href="{{ $report->getReturnMapUrl() }}" target="_blank"
                           class="text-blue-600 text-xs hover:underline mt-1 inline-block">
                            📍 {{ $report->return_location_address ?? 'Lihat di Maps' }}
                        </a>
                        @endif
                    </div>
                    @endif

                @else
                    {{-- Belum ada laporan — Tampilkan Form Lapor --}}
                    @if($booking->with_driver)
                        <div class="border border-purple-200 rounded-lg p-3.5 mb-4 bg-purple-50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="text-xs text-purple-900">
                                <strong>🧑‍✈️ Pesanan Menggunakan Sopir:</strong><br>
                                Sopir yang bertugas (<strong>{{ $booking->driver?->name ?? 'Sopir' }}</strong>) dapat melapor keterlambatan atau pengembalian langsung dari HP-nya.
                            </div>
                            @php
                                $driverPhoneDigits = preg_replace('/[^0-9]/', '', $booking->driver?->phone ?? '');
                                if (str_starts_with($driverPhoneDigits, '0')) {
                                    $driverPhoneDigits = '62' . substr($driverPhoneDigits, 1);
                                }
                                $waMsg = rawurlencode("Halo " . ($booking->driver?->name ?? 'Sopir') . ", berikut link Portal Laporan Keterlambatan/Pengembalian Armada untuk booking " . $booking->code . ": " . route('driver.late-report.show', $booking->code));
                                $waLink = $driverPhoneDigits ? "https://wa.me/" . $driverPhoneDigits . "?text=" . $waMsg : '#';
                            @endphp
                            <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer"
                               class="flex-shrink-0 inline-flex items-center gap-1.5 text-xs text-white bg-green-600 hover:bg-green-700 px-3.5 py-2 rounded-lg font-semibold transition shadow-sm">
                                💬 Kirim Link WA ke Sopir
                            </a>
                        </div>
                    @endif

                    <div class="border border-orange-200 rounded-lg p-4 mb-4 bg-orange-50">
                        <p class="font-semibold text-orange-800 mb-1">📋 Laporkan Keterlambatan</p>
                        <p class="text-sm text-orange-600 mb-3">
                            Jika Anda tahu akan terlambat mengembalikan, laporkan sekarang agar vendor bisa bersiap.
                            Anda juga bisa izinkan akses lokasi GPS sebagai bukti posisi Anda.
                        </p>
                        <form method="POST" action="{{ route('late-return.report', $booking->code) }}" id="form-late-report">
                            @csrf
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Perkiraan Terlambat (jam) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="estimated_late_hours"
                                           min="1" max="72" value="1" required
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Alasan Keterlambatan <span class="text-red-500">*</span>
                                    </label>
                                    <textarea name="reason" required rows="2"
                                              placeholder="Contoh: Macet di tol, hujan deras, ban kempes..."
                                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"></textarea>
                                </div>

                                {{-- GPS --}}
                                <input type="hidden" name="latitude" id="report_lat">
                                <input type="hidden" name="longitude" id="report_lng">
                                <input type="hidden" name="location_address" id="report_addr">

                                <div class="bg-white border border-gray-200 rounded-lg p-3">
                                    <p class="text-xs font-medium text-gray-600 mb-1">
                                        📍 Lokasi GPS Saat Ini
                                        <span class="text-xs font-normal text-orange-500 ml-1">(sangat disarankan)</span>
                                    </p>
                                    <div id="report-location-status" class="text-xs text-gray-500 mb-2">
                                        Klik tombol untuk mengirim lokasi Anda ke vendor sebagai bukti.
                                    </div>
                                    <button type="button" id="btn-get-report-location"
                                        class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">
                                        📡 Deteksi & Kirim Lokasi GPS
                                    </button>
                                </div>

                                <button type="submit"
                                    class="w-full bg-orange-500 hover:bg-orange-600 text-white py-2.5 rounded-lg font-semibold text-sm">
                                    ⚠️ Kirim Laporan Keterlambatan
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Konfirmasi Lokasi Pengembalian (tanpa laporan dulu) --}}
                    <div class="border border-blue-200 rounded-lg p-4 bg-blue-50">
                        <p class="font-semibold text-blue-800 mb-1">📍 Konfirmasi Lokasi Pengembalian</p>
                        <p class="text-sm text-blue-600 mb-3">
                            Sudah di lokasi pengembalian? Kirim posisi GPS Anda sebagai bukti bahwa mobil sudah dikembalikan.
                        </p>
                        <form method="POST" action="{{ route('late-return.confirm-return', $booking->code) }}" id="form-confirm-return">
                            @csrf
                            <input type="hidden" name="return_latitude" id="return_lat">
                            <input type="hidden" name="return_longitude" id="return_lng">
                            <input type="hidden" name="return_location_address" id="return_addr">
                            <div id="return-location-status" class="text-sm text-gray-500 mb-3">
                                📡 Klik tombol untuk deteksi lokasi otomatis.
                            </div>
                            <div class="flex gap-2">
                                <button type="button" id="btn-get-return-location"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-semibold">
                                    📍 Deteksi Lokasi Saya
                                </button>
                                <button type="submit" id="btn-submit-return"
                                    class="hidden bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm font-semibold">
                                    ✅ Kirim Konfirmasi
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

            <script>
            // ── GPS: Laporan Keterlambatan (Opsi A) ──────────────
            const btnReport = document.getElementById('btn-get-report-location');
            if (btnReport) {
                btnReport.addEventListener('click', function() {
                    const statusEl = document.getElementById('report-location-status');
                    if (!navigator.geolocation) {
                        statusEl.textContent = '❌ Browser tidak mendukung GPS.';
                        return;
                    }
                    statusEl.innerHTML = '<span class="text-blue-600">⏳ Mendeteksi lokasi...</span>';
                    btnReport.disabled = true;

                    navigator.geolocation.getCurrentPosition(function(pos) {
                        const lat = pos.coords.latitude.toFixed(8);
                        const lng = pos.coords.longitude.toFixed(8);
                        document.getElementById('report_lat').value  = lat;
                        document.getElementById('report_lng').value  = lng;

                        // Reverse geocode via Nominatim (free, no key)
                        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng)
                            .then(r => r.json())
                            .then(data => {
                                const addr = data.display_name || (lat + ', ' + lng);
                                document.getElementById('report_addr').value = addr;
                                statusEl.innerHTML = '✅ Lokasi terdeteksi: <strong>' + addr.substring(0, 80) + '...</strong>';
                            })
                            .catch(() => {
                                document.getElementById('report_addr').value = lat + ', ' + lng;
                                statusEl.innerHTML = '✅ Koordinat: ' + lat + ', ' + lng;
                            });

                        btnReport.textContent = '✅ Lokasi Terdeteksi';
                        btnReport.classList.replace('bg-orange-500','bg-green-500');
                        btnReport.classList.replace('hover:bg-orange-600','hover:bg-green-600');
                        // Sembunyikan peringatan GPS
                        const warn = document.getElementById('gps-report-warning');
                        if (warn) warn.style.display = 'none';
                    }, function() {
                        statusEl.innerHTML = '<span class="text-red-600">❌ Akses lokasi ditolak. GPS tidak disertakan.</span>';
                        btnReport.disabled = false;
                    });
                });
            }

            // ── GPS: Konfirmasi Pengembalian (Opsi B) ──────────────
            const btnReturn = document.getElementById('btn-get-return-location');
            if (btnReturn) {
                btnReturn.addEventListener('click', function() {
                    const statusEl = document.getElementById('return-location-status');
                    if (!navigator.geolocation) {
                        statusEl.textContent = '❌ Browser tidak mendukung GPS.';
                        return;
                    }
                    statusEl.innerHTML = '<span class="text-blue-600">⏳ Mendeteksi lokasi...</span>';
                    btnReturn.disabled = true;

                    navigator.geolocation.getCurrentPosition(function(pos) {
                        const lat = pos.coords.latitude.toFixed(8);
                        const lng = pos.coords.longitude.toFixed(8);
                        document.getElementById('return_lat').value = lat;
                        document.getElementById('return_lng').value = lng;

                        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng)
                            .then(r => r.json())
                            .then(data => {
                                const addr = data.display_name || (lat + ', ' + lng);
                                document.getElementById('return_addr').value = addr;
                                statusEl.innerHTML = '✅ Lokasi terdeteksi: <strong>' + addr.substring(0, 80) + '...</strong>';
                            })
                            .catch(() => {
                                document.getElementById('return_addr').value = lat + ', ' + lng;
                                statusEl.innerHTML = '✅ Koordinat: ' + lat + ', ' + lng;
                            });

                        btnReturn.textContent = '✅ Lokasi Terdeteksi';
                        btnReturn.disabled = false;
                        const btnSubmit = document.getElementById('btn-submit-return');
                        if (btnSubmit) btnSubmit.classList.remove('hidden');
                    }, function() {
                        statusEl.innerHTML = '<span class="text-red-600">❌ Akses lokasi ditolak.</span>';
                        btnReturn.disabled = false;
                        // Tetap tampilkan tombol submit walau tanpa GPS
                        const btnSubmit = document.getElementById('btn-submit-return');
                        if (btnSubmit) btnSubmit.classList.remove('hidden');
                    });
                });
            }
            </script>
            @endif

            {{-- ══════════════════════════════════════════════════════════
                 TAGIHAN DENDA KETERLAMBATAN
                 Muncul jika ada LateFeeCharge untuk booking ini
            ══════════════════════════════════════════════════════════ --}}
            @if($booking->lateFeeCharge)
            @php $charge = $booking->lateFeeCharge; @endphp
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="font-semibold text-lg mb-4 text-red-700">💸 Tagihan Denda Keterlambatan</h3>

                {{-- Status badges --}}
                @php
                    $chargeStatus = $charge->status;
                    $statusConfig = match($chargeStatus) {
                        'pending'   => ['bg'=>'bg-yellow-100','text'=>'text-yellow-800','label'=>'⏳ Menunggu Konfirmasi Admin'],
                        'confirmed' => ['bg'=>'bg-blue-100',  'text'=>'text-blue-800',  'label'=>'🔔 Perlu Dibayar'],
                        'paid'      => ['bg'=>'bg-green-100', 'text'=>'text-green-800', 'label'=>'✅ Sudah Dibayar'],
                        'waived'    => ['bg'=>'bg-gray-100',  'text'=>'text-gray-700',  'label'=>'🎁 Dibebaskan Admin'],
                        default     => ['bg'=>'bg-gray-100',  'text'=>'text-gray-700',  'label'=>$chargeStatus],
                    };
                @endphp

                <div class="flex items-center gap-3 mb-4">
                    <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }}">
                        {{ $statusConfig['label'] }}
                    </span>
                    <span class="text-2xl font-bold text-red-600">
                        Rp {{ number_format($charge->amount, 0, ',', '.') }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm mb-4">
                    <div>
                        <p class="text-gray-500">Durasi Terlambat</p>
                        <p class="font-semibold text-red-600">{{ $charge->late_hours }} jam</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Jumlah Denda</p>
                        <p class="font-semibold text-red-600">Rp {{ number_format($charge->amount, 0, ',', '.') }}</p>
                    </div>
                    @if($chargeStatus === 'paid' && $charge->paid_at)
                        <div class="col-span-2">
                            <p class="text-gray-500">Dibayar Pada</p>
                            <p class="font-medium text-green-600">{{ $charge->paid_at->format('d M Y, H:i') }}</p>
                        </div>
                    @endif
                    @if($chargeStatus === 'waived' && $charge->waive_reason)
                        <div class="col-span-2">
                            <p class="text-gray-500">Alasan Dibebaskan</p>
                            <p class="font-medium text-gray-700">{{ $charge->waive_reason }}</p>
                        </div>
                    @endif
                </div>

                {{-- Instruksi Pembayaran (hanya saat confirmed) --}}
                @if($chargeStatus === 'confirmed')
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <p class="text-sm font-semibold text-blue-800 mb-2">🏦 Rekening Tujuan Pembayaran Denda</p>
                        <div class="grid grid-cols-3 gap-2 text-sm">
                            <div>
                                <p class="text-gray-500 text-xs">Bank</p>
                                <p class="font-semibold">{{ $charge->bank_name ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs">No. Rekening</p>
                                <p class="font-semibold">{{ $charge->bank_account_no ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs">Atas Nama</p>
                                <p class="font-semibold">{{ $charge->bank_account_name ?? '—' }}</p>
                            </div>
                        </div>
                        <p class="text-xs text-blue-600 mt-2">
                            Transfer denda ke rekening di atas lalu upload bukti pembayaran.
                        </p>
                    </div>

                    {{-- Upload Bukti Bayar --}}
                    @if(!$charge->payment_proof)
                        <form method="POST"
                              action="{{ route('late-fee.proof', $charge->id) }}"
                              enctype="multipart/form-data"
                              class="border-2 border-dashed border-red-300 rounded-lg p-4">
                            @csrf
                            <p class="text-sm font-semibold text-gray-700 mb-3">📎 Upload Bukti Transfer Denda</p>
                            <div class="flex gap-3 items-end flex-wrap">
                                <div class="flex-1">
                                    <input type="file"
                                           name="payment_proof"
                                           accept="image/*"
                                           required
                                           class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-red-600 file:text-white file:font-semibold file:cursor-pointer hover:file:bg-red-700">
                                    <p class="text-xs text-gray-400 mt-1">Format: JPG, PNG — maks. 5MB</p>
                                </div>
                                <button type="submit"
                                        class="bg-red-600 text-white px-5 py-2 rounded-lg hover:bg-red-700 font-semibold text-sm whitespace-nowrap">
                                    📤 Upload
                                </button>
                            </div>
                            @error('payment_proof')
                                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                            @enderror
                        </form>
                    @else
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 flex items-start gap-3">
                            <span class="text-xl">📎</span>
                            <div>
                                <p class="text-sm font-semibold text-green-800">Bukti Pembayaran Sudah Diupload</p>
                                <p class="text-xs text-green-600 mt-0.5">
                                    Diupload {{ $charge->proof_uploaded_at?->format('d M Y H:i') ?? '—' }}.
                                    Admin sedang memverifikasi.
                                </p>
                                <a href="{{ asset('storage/' . $charge->payment_proof) }}"
                                   target="_blank"
                                   class="text-xs text-blue-600 underline mt-1 inline-block">
                                    🔍 Lihat bukti yang diupload
                                </a>
                            </div>
                        </div>
                    @endif
                @endif

                @if($charge->dispute_rejection_reason)
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 mt-3">
                        <p class="text-sm font-semibold text-red-800">❌ Keberatan Denda Ditolak oleh Admin</p>
                        <p class="text-xs text-red-700 mt-1"><strong>Alasan Admin:</strong> "{{ $charge->dispute_rejection_reason }}"</p>
                        <p class="text-[11px] text-red-600 mt-1">Denda tetap berlaku dan wajib dibayar. Silakan lakukan pembayaran denda melalui rekening di atas lalu upload bukti transfer.</p>
                    </div>
                @elseif($charge->dispute_reason)
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mt-3">
                        <p class="text-sm font-semibold text-amber-800">⚠️ Keberatan Denda Diajukan</p>
                        <p class="text-xs text-amber-700 mt-1">"{{ $charge->dispute_reason }}"</p>
                        <p class="text-[11px] text-amber-600 mt-1">Diajukan pada {{ $charge->disputed_at?->format('d M Y H:i') ?? '—' }}. Admin & Vendor sedang meninjau alasan Anda.</p>
                    </div>
                @elseif(in_array($chargeStatus, ['pending', 'confirmed']))
                    <details class="mt-3 bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <summary class="text-xs font-semibold text-gray-700 cursor-pointer hover:text-red-600">
                            ⚠️ Merasa Denda Tidak Sesuai? Klik untuk Ajukan Keberatan / Banding
                        </summary>
                        <form method="POST" action="{{ route('late-fee.dispute', $charge->id) }}" class="mt-3 space-y-2">
                            @csrf
                            <label class="block text-xs font-medium text-gray-600">Alasan Keberatan (min. 10 karakter)</label>
                            <textarea name="dispute_reason" rows="2" required placeholder="Jelaskan alasan Anda, misal: terjadi kemacetan parah di jalan tol / salah penulisan jam..." class="w-full text-xs border border-gray-300 rounded p-2 focus:ring-1 focus:ring-amber-500"></textarea>
                            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold px-4 py-1.5 rounded">
                                📩 Kirim Keberatan ke Admin & Vendor
                            </button>
                        </form>
                    </details>
                @endif

                @if($chargeStatus === 'pending')
                    <p class="text-sm text-gray-500 mt-2">
                        Admin sedang meninjau tagihan ini. Anda akan mendapat notifikasi dan instruksi pembayaran setelah dikonfirmasi.
                    </p>
                @endif
            </div>
            @endif

            <!-- Vendor Info -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="font-semibold text-lg mb-4">Vendor</h3>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                            {{ substr($booking->vendor?->business_name ?? 'V', 0, 1) }}
                        </div>
                        <div>
                            <h4 class="font-semibold">{{ $booking->vendor?->business_name ?? '—' }}</h4>
                            <p class="text-gray-600 text-sm">{{ $booking->vendor?->city?->name ?? '—' }}</p>
                        </div>
                    </div>
                    @if($booking->status !== 'awaiting_payment')
                        <a href="tel:{{ $booking->vendor->user->phone }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                            📞 Hubungi
                        </a>
                    @endif
                </div>
            </div>

            @if($booking->status === 'awaiting_payment')
                @if($booking->payment?->status === 'failed')
                    {{-- Pembayaran ditolak admin — minta upload ulang --}}
                    <div class="bg-red-50 border border-red-300 rounded-lg p-6">
                        <div class="flex items-start gap-3 mb-4">
                            <span class="text-2xl">❌</span>
                            <div>
                                <h3 class="font-semibold text-red-800 mb-1">Bukti Transfer Ditolak</h3>
                                <p class="text-red-700 text-sm">Admin menolak bukti transfer yang Anda kirimkan. Silakan periksa kembali dan upload ulang bukti transfer yang valid.</p>
                                <ul class="text-red-600 text-sm mt-2 list-disc list-inside space-y-1">
                                    <li>Pastikan foto bukti transfer jelas dan terbaca</li>
                                    <li>Pastikan jumlah transfer sesuai: <strong>{{ formatRupiah($booking->total) }}</strong></li>
                                    <li>Pastikan transfer ke rekening yang benar</li>
                                </ul>
                            </div>
                        </div>
                        <a href="{{ route('bookings.pay', $booking) }}"
                           class="block w-full text-center bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                            📸 Upload Ulang Bukti Transfer
                        </a>
                    </div>
                @elseif($booking->payment?->payment_proof)
                    {{-- Bukti sudah diupload, menunggu konfirmasi admin --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">📎</span>
                            <div>
                                <h3 class="font-semibold text-blue-800 mb-1">Bukti Transfer Sudah Dikirim</h3>
                                <p class="text-blue-700 text-sm">Admin sedang memverifikasi pembayaran Anda. Biasanya selesai dalam 1×24 jam.</p>
                                @if($booking->payment->sender_name)
                                    <p class="text-blue-600 text-sm mt-1">Pengirim: <strong>{{ $booking->payment->sender_name }}</strong></p>
                                @endif
                                <p class="text-blue-500 text-xs mt-2">Jika lebih dari 24 jam belum dikonfirmasi, hubungi admin melalui halaman <a href="{{ route('contact') }}" class="underline">Hubungi Kami</a>.</p>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Belum bayar sama sekali --}}
                    <div class="bg-amber-50 border-2 border-amber-300 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="text-2xl">💳</span>
                            <div>
                                <h3 class="font-bold text-gray-900 text-lg">Pembayaran Diperlukan</h3>
                                <p class="text-xs text-amber-800">Harap selesaikan pembayaran untuk mengonfirmasi pemesanan Anda</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-700 mb-5 leading-relaxed">
                            Selesaikan pembayaran sebelum <strong class="text-amber-900 bg-amber-100 px-2 py-0.5 rounded">{{ $booking->created_at->addDay()->format('d M Y H:i') }} WIB</strong> atau pesanan otomatis dibatalkan.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                            <a href="{{ route('bookings.pay', $booking) }}"
                               class="flex-1 inline-flex items-center justify-center gap-2 text-center bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold text-sm transition shadow-sm whitespace-nowrap">
                                💳 Bayar Sekarang — {{ formatRupiah($booking->total) }}
                            </a>
                            <form method="POST" action="{{ route('bookings.cancel', $booking) }}" class="inline-block">
                                @csrf
                                <button type="submit"
                                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 bg-red-100 hover:bg-red-200 text-red-700 px-5 py-3 rounded-lg font-semibold text-sm transition whitespace-nowrap"
                                        onclick="return confirm('Yakin ingin membatalkan pesanan ini?')">
                                    ✕ Batalkan
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            @elseif($booking->status === 'awaiting_vendor')
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-blue-800">⏳ Menunggu konfirmasi dari vendor. Biasanya dalam 1-2 jam.</p>
                </div>
            @elseif($booking->status === 'confirmed')
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <p class="text-green-800">✅ Pesanan dikonfirmasi. Silakan datang ke lokasi penjemputan tepat waktu.</p>
                </div>
            @elseif($booking->status === 'completed')
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 flex justify-between items-center flex-wrap gap-3">
                    <p class="text-gray-700">✅ Pesanan selesai.</p>
                    <div class="flex items-center space-x-3 flex-wrap gap-2">
                        {{-- Tombol Review --}}
                        @if(!$booking->review)
                            <a href="{{ route('bookings.review', $booking->code) }}"
                               class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 text-sm font-semibold">
                                ⭐ Beri Review
                            </a>
                        @else
                            <span class="text-sm text-gray-500 bg-yellow-50 border border-yellow-200 px-3 py-2 rounded-lg">
                                ⭐ {{ $booking->review->rating }}/5 — Sudah direview
                            </span>
                        @endif
                        <a href="{{ route('bookings.invoice', $booking) }}"
                           class="bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm">
                            📄 Download Invoice
                        </a>
                        @php
                            $activeComplaint = \App\Models\Complaint::where('booking_id', $booking->id)
                                ->whereNotIn('status', ['rejected'])
                                ->latest()
                                ->first();
                        @endphp
                        @if(!$activeComplaint)
                            <a href="{{ route('complaints.create', $booking->id) }}"
                               class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm font-medium flex items-center gap-1">
                                ⚠ Laporkan Masalah
                            </a>
                        @else
                            <a href="{{ route('complaints.show', $activeComplaint->id) }}"
                               class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 text-sm font-medium flex items-center gap-1">
                                @if($activeComplaint->status === 'resolved')
                                    ✅ Lihat Hasil Laporan
                                @else
                                    📋 Lihat Laporan
                                @endif
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- ── Laporkan Masalah (Complaint) Section ──────────────────── --}}
            @php
                $activeComplaint   = \App\Models\Complaint::where('booking_id', $booking->id)
                    ->whereNotIn('status', ['rejected'])
                    ->with(['resolution'])
                    ->latest()
                    ->first();
                $canOpenComplaint  = in_array($booking->status, ['confirmed', 'ongoing', 'completed'])
                    && ! $activeComplaint;
            @endphp

            {{-- Complaint aktif --}}
            @if($activeComplaint)
                @php
                    $cStatus     = $activeComplaint->status;
                    $isResolved  = $cStatus === 'resolved';
                    $isRejected  = $cStatus === 'rejected';
                    $isClosed    = $isResolved || $isRejected;
                    $cardColor   = $isResolved ? 'green' : ($isRejected ? 'gray' : 'orange');
                    $cardIcon    = $isResolved ? '✅' : ($isRejected ? '⚫' : '⚠️');
                @endphp
                <div class="bg-{{ $cardColor }}-50 border border-{{ $cardColor }}-200 rounded-lg p-5">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl leading-none mt-0.5">{{ $cardIcon }}</span>
                        <div class="flex-1">
                            <div class="flex items-center justify-between flex-wrap gap-2 mb-1">
                                <h3 class="font-semibold text-{{ $cardColor }}-800">
                                    Laporan Masalah
                                </h3>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-{{ $cardColor }}-100 text-{{ $cardColor }}-700">
                                    {{ $activeComplaint->statusLabel() }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mb-2">Ref: <strong>{{ $activeComplaint->reference }}</strong> · Diajukan {{ $activeComplaint->created_at->format('d M Y H:i') }}</p>

                            {{-- Deskripsi singkat --}}
                            <div class="bg-white border border-{{ $cardColor }}-200 rounded-lg p-3 text-sm text-gray-700 mb-3">
                                <p class="font-medium text-gray-500 text-xs mb-1">Masalah yang dilaporkan:</p>
                                <p>{{ \Illuminate\Support\Str::limit($activeComplaint->description, 200) }}</p>
                            </div>

                            {{-- Status proses --}}
                            @if(!$isClosed)
                                <p class="text-sm text-{{ $cardColor }}-700">
                                    @if($cStatus === 'submitted') ⏳ Laporan diterima, admin sedang meninjau.
                                    @elseif($cStatus === 'forwarded_to_vendor') 📤 Diteruskan ke vendor untuk ditanggapi.
                                    @elseif($cStatus === 'vendor_responded') 💬 Vendor sudah merespons, admin sedang meninjau.
                                    @elseif($cStatus === 'under_admin_review') 🔍 Sedang ditinjau admin.
                                    @else ⏳ Sedang diproses...
                                    @endif
                                </p>
                            @endif

                            {{-- Keputusan admin jika selesai --}}
                            @if($isResolved && $activeComplaint->resolution)
                                <div class="mt-3 bg-white border border-green-200 rounded-lg p-3 text-sm">
                                    <p class="font-medium text-gray-500 text-xs mb-1">Keputusan Admin:</p>
                                    <p class="font-semibold text-green-700">{{ $activeComplaint->resolution->decisionLabel() }}</p>
                                    <p class="text-gray-700 mt-1 text-xs">{{ $activeComplaint->resolution->reasoning }}</p>
                                </div>
                            @endif

                            @if($isRejected && $activeComplaint->admin_notes)
                                <div class="mt-3 bg-white border border-gray-200 rounded-lg p-3 text-sm">
                                    <p class="font-medium text-gray-500 text-xs mb-1">Alasan Penolakan:</p>
                                    <p class="text-gray-700">{{ $activeComplaint->admin_notes }}</p>
                                </div>
                            @endif

                            {{-- Status Refund dari Komplain --}}
                            @php $complaintRefund = $booking->refund; @endphp
                            @if($isResolved && $complaintRefund)
                                <div class="mt-4 rounded-lg border p-4
                                    {{ $complaintRefund->status === 'paid' ? 'bg-blue-50 border-blue-200' : 'bg-yellow-50 border-yellow-200' }}">
                                    <div class="flex items-start gap-3">
                                        <span class="text-xl leading-none mt-0.5">
                                            {{ $complaintRefund->status === 'paid' ? '💸' : '⏳' }}
                                        </span>
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold {{ $complaintRefund->status === 'paid' ? 'text-blue-800' : 'text-yellow-800' }}">
                                                {{ $complaintRefund->status === 'paid' ? 'Refund Sudah Ditransfer' : 'Refund Sedang Diproses' }}
                                            </p>
                                            <p class="text-sm mt-1 {{ $complaintRefund->status === 'paid' ? 'text-blue-700' : 'text-yellow-700' }}">
                                                Jumlah: <strong>Rp {{ number_format($complaintRefund->amount, 0, ',', '.') }}</strong>
                                            </p>
                                            @if($complaintRefund->status === 'paid')
                                                @if($complaintRefund->bank_name || $complaintRefund->bank_account_no)
                                                    <p class="text-sm text-blue-700 mt-1">
                                                        Dikirim ke: <strong>{{ $complaintRefund->bank_name }}</strong>
                                                        {{ $complaintRefund->bank_account_no }}
                                                        @if($complaintRefund->bank_account_name) a.n. {{ $complaintRefund->bank_account_name }} @endif
                                                    </p>
                                                @endif
                                                @if($complaintRefund->paid_at)
                                                    <p class="text-xs text-blue-600 mt-1">Ditransfer pada: {{ $complaintRefund->paid_at->format('d M Y H:i') }}</p>
                                                @endif
                                                @if($complaintRefund->transfer_reference)
                                                    <p class="text-xs text-blue-600 mt-0.5">No. Referensi: <strong>{{ $complaintRefund->transfer_reference }}</strong></p>
                                                @endif
                                                @if($complaintRefund->transfer_proof)
                                                    <div class="mt-3">
                                                        <p class="text-xs font-semibold text-blue-700 mb-1">📎 Bukti Transfer:</p>
                                                        <a href="{{ asset('storage/' . $complaintRefund->transfer_proof) }}" target="_blank">
                                                            <img src="{{ asset('storage/' . $complaintRefund->transfer_proof) }}"
                                                                 alt="Bukti Transfer"
                                                                 class="max-h-40 rounded-lg border border-blue-200 cursor-pointer hover:opacity-90 transition">
                                                            <p class="text-xs text-blue-500 mt-1">🔍 Klik untuk lihat ukuran penuh</p>
                                                        </a>
                                                    </div>
                                                @endif
                                            @else
                                                <p class="text-xs text-yellow-700 mt-2">Admin sedang memproses transfer. Biasanya 1–3 hari kerja.</p>
                                                @if($complaintRefund->bank_account_no)
                                                    <p class="text-xs text-yellow-700 mt-1">
                                                        Rekening tujuan: <strong>{{ $complaintRefund->bank_name }}</strong>
                                                        {{ $complaintRefund->bank_account_no }}
                                                        a.n. {{ $complaintRefund->bank_account_name }}
                                                    </p>
                                                @else
                                                    <p class="text-xs text-orange-600 font-medium mt-2">
                                                        ⚠️ Anda belum mengisi info rekening.
                                                        <a href="{{ route('profile.edit') }}" class="underline text-blue-600">Isi di Profil</a>
                                                        agar admin dapat mentransfer refund.
                                                    </p>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @elseif($isResolved && !$complaintRefund)
                                <div class="mt-3 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3">
                                    <p class="text-sm text-gray-600">ℹ️ Berdasarkan keputusan admin, tidak ada pengembalian dana.</p>
                                </div>
                            @endif

                            {{-- Link ke detail --}}
                            <a href="{{ route('complaints.show', $activeComplaint->id) }}"
                               class="inline-block mt-3 text-sm text-blue-600 hover:underline">
                                Lihat Detail Laporan →
                            </a>
                        </div>
                    </div>
                </div>

            @endif
        </div>

        <!-- ── Tagihan Kompensasi (jika ada) ───────────────────────────── -->
        @php $compensation = $booking->compensationCharge; @endphp
        @if($compensation && $compensation->isPending())
            <div class="bg-red-50 border-2 border-red-300 rounded-lg p-5 mt-4">
                <div class="flex items-start gap-3 mb-4">
                    <span class="text-3xl leading-none">⚠️</span>
                    <div class="flex-1">
                        <h3 class="font-bold text-red-800 text-lg mb-1">Tagihan Kompensasi</h3>
                        <p class="text-red-700 text-sm">Admin telah menetapkan tagihan kompensasi untuk Anda berdasarkan hasil keputusan laporan masalah.</p>
                    </div>
                </div>

                <div class="bg-white border border-red-200 rounded-lg p-4 mb-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Alasan</span>
                        <span class="font-semibold text-gray-900">{{ $compensation->reason }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Jumlah</span>
                        <span class="font-bold text-red-700 text-base">{{ formatRupiah($compensation->amount) }}</span>
                    </div>
                    @if($compensation->due_date)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Batas Pembayaran</span>
                            <span class="font-semibold {{ $compensation->isOverdue() ? 'text-red-600' : 'text-gray-900' }}">
                                {{ $compensation->due_date->format('d M Y') }}
                                @if($compensation->isOverdue())
                                    <span class="text-xs font-bold">(Lewat batas!)</span>
                                @endif
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Info rekening tujuan transfer --}}
                <div class="bg-white border border-red-200 rounded-lg p-4 mb-4 text-sm">
                    <p class="font-semibold text-gray-700 mb-2">🏦 Transfer ke Rekening:</p>
                    <div class="space-y-1">
                        <p class="text-gray-800">
                            <span class="text-gray-500">Bank:</span>
                            <strong>{{ env('PAYMENT_BANK_NAME', 'BCA') }}</strong>
                        </p>
                        <p class="text-gray-800">
                            <span class="text-gray-500">No. Rekening:</span>
                            <strong>{{ env('PAYMENT_ACCOUNT_NO', '-') }}</strong>
                        </p>
                        <p class="text-gray-800">
                            <span class="text-gray-500">Atas Nama:</span>
                            <strong>{{ env('PAYMENT_ACCOUNT_NAME', '-') }}</strong>
                        </p>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Cantumkan kode booking <strong>{{ $booking->code }}</strong> sebagai berita transfer.
                    </p>
                </div>

                {{-- Form upload bukti bayar --}}
                @if(!$compensation->payment_proof)
                    <p class="text-sm text-red-700 font-medium mb-3">
                        Setelah transfer, upload bukti pembayaran di bawah agar admin dapat memverifikasi.
                    </p>
                    <form method="POST"
                          action="{{ route('compensation.proof', $compensation->id) }}"
                          enctype="multipart/form-data"
                          class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Upload Bukti Pembayaran <span class="text-red-500">*</span>
                            </label>
                            <label for="compensation_proof_file"
                                   class="flex items-center gap-3 cursor-pointer border-2 border-dashed border-red-300 rounded-lg px-4 py-4 bg-red-50 hover:bg-red-100 transition">
                                <span class="text-2xl">📷</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-red-700" id="compensation_file_label">
                                        Klik untuk pilih foto bukti transfer
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">JPG, PNG — Maks. 2MB</p>
                                </div>
                            </label>
                            <input type="file" id="compensation_proof_file" name="payment_proof"
                                   accept="image/jpg,image/jpeg,image/png"
                                   required
                                   class="sr-only"
                                   onchange="document.getElementById('compensation_file_label').textContent = this.files[0]?.name ?? 'Klik untuk pilih foto bukti transfer'">
                        </div>
                        <button type="submit"
                                class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-bold text-sm transition">
                            📤 Kirim Bukti Pembayaran
                        </button>
                    </form>
                @else
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                        <p class="text-yellow-800 text-sm font-semibold">📎 Bukti pembayaran sudah dikirim</p>
                        <p class="text-yellow-700 text-xs mt-1">Admin sedang memverifikasi pembayaran Anda. Biasanya 1×24 jam.</p>
                    </div>
                @endif
            </div>
        @elseif($compensation && $compensation->isPaid())
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mt-4">
                <p class="text-green-800 font-semibold text-sm">✅ Kompensasi Rp {{ number_format($compensation->amount, 0, ',', '.') }} sudah dikonfirmasi lunas.</p>
                <p class="text-green-700 text-xs mt-1">Dibayar pada {{ $compensation->paid_at?->format('d M Y H:i') ?? '—' }}</p>
            </div>
        @endif

        <!-- Summary -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                <h3 class="font-semibold text-lg mb-4">Ringkasan Biaya</h3>
                
                <div class="space-y-3 border-b pb-4 mb-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Sewa Mobil</span>
                        <span class="font-semibold">{{ formatRupiah($booking->subtotal) }}</span>
                    </div>
                    @if($booking->addon_fees > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Add-on</span>
                            <span class="font-semibold">{{ formatRupiah($booking->addon_fees) }}</span>
                        </div>
                    @endif
                    @if($booking->discount > 0)
                        <div class="flex justify-between text-green-600">
                            <span>Diskon</span>
                            <span class="font-semibold">- {{ formatRupiah($booking->discount) }}</span>
                        </div>
                    @endif
                </div>

                <div class="flex justify-between text-lg font-bold mb-6">
                    <span>Total</span>
                    <span class="text-blue-600">{{ formatRupiah($booking->total) }}</span>
                </div>

                @if($booking->payment)
                    <div class="bg-gray-50 p-3 rounded text-sm">
                        <p class="text-gray-600">Status Pembayaran</p>
                        <p class="font-semibold {{ match($booking->payment->status) {
                            'paid'     => 'text-green-600',
                            'pending'  => 'text-yellow-600',
                            'failed'   => 'text-red-600',
                            'refunded' => 'text-blue-600',
                            default    => 'text-gray-700',
                        } }}">{{ match($booking->payment->status) {
                            'pending'  => '⏳ Menunggu Konfirmasi Admin',
                            'paid'     => '✅ Lunas',
                            'failed'   => '❌ Ditolak',
                            'refunded' => '💸 Sudah Direfund',
                            default    => ucfirst($booking->payment->status),
                        } }}</p>
                        @if($booking->payment->status === 'refunded' && $booking->payment->refunded_at)
                            <p class="text-gray-500 text-xs mt-1">{{ $booking->payment->refunded_at->format('d M Y H:i') }}</p>
                            @if($booking->payment->refund_ref)
                                <p class="text-gray-500 text-xs">Ref: {{ $booking->payment->refund_ref }}</p>
                            @endif
                        @elseif($booking->payment->paid_at)
                            <p class="text-gray-600 text-xs mt-1">{{ $booking->payment->paid_at->format('d M Y H:i') }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
