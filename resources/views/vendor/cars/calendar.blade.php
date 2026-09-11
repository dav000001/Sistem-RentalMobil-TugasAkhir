@extends('layouts.vendor')
@section('title', 'Kalender Ketersediaan - ' . $car->brand . ' ' . $car->model)

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <a href="/vendor/cars" class="text-blue-600 hover:underline text-sm">← Kembali ke Armada</a>
            <h1 class="text-2xl font-bold mt-2 text-gray-900">📅 Kalender Ketersediaan</h1>
            <p class="text-gray-500 text-sm mt-0.5">
                {{ $car->brand }} {{ $car->model }} {{ $car->year }}
                <span class="mx-1 text-gray-300">|</span>
                <span class="font-mono">{{ $car->plate_number }}</span>
            </p>
        </div>
        {{-- Quick stats bulan ini --}}
        @php
            $bookedCount = collect($calendar)->filter(fn($s) => $s === 'booked')->count();
            $blockedCount = collect($calendar)->filter(fn($s) => in_array($s, ['blocked','maintenance']))->count();
            $availableCount = collect($calendar)->filter(fn($s) => $s === 'available')->count();
            $totalDays = count($calendar);
            $occupancyRate = $totalDays > 0 ? round(($bookedCount / $totalDays) * 100) : 0;
        @endphp
        <div class="flex gap-3 text-center">
            <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-2">
                <p class="text-xl font-bold text-green-700">{{ $availableCount }}</p>
                <p class="text-xs text-green-600">Tersedia</p>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-2">
                <p class="text-xl font-bold text-blue-700">{{ $bookedCount }}</p>
                <p class="text-xs text-blue-600">Dipesan</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-2">
                <p class="text-xl font-bold text-red-700">{{ $blockedCount }}</p>
                <p class="text-xs text-red-600">Diblokir</p>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-lg px-4 py-2">
                <p class="text-xl font-bold text-purple-700">{{ $occupancyRate }}%</p>
                <p class="text-xs text-purple-600">Terisi</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── Kalender Utama ── --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

                {{-- Navigasi Bulan --}}
                @php
                    $prevMonth = $month == 1 ? 12 : $month - 1;
                    $prevYear  = $month == 1 ? $year - 1 : $year;
                    $nextMonth = $month == 12 ? 1 : $month + 1;
                    $nextYear  = $month == 12 ? $year + 1 : $year;
                    $monthName = \Carbon\Carbon::create($year, $month, 1)->locale('id')->translatedFormat('F Y');
                @endphp
                <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <a href="{{ route('vendor.cars.calendar', [$car->id, 'year' => $prevYear, 'month' => $prevMonth]) }}"
                       class="p-2 rounded-lg hover:bg-gray-200 text-gray-600 transition font-bold text-lg">‹</a>
                    <div class="text-center">
                        <h2 class="text-lg font-bold text-gray-900">{{ $monthName }}</h2>
                        <p class="text-xs text-gray-400">Klik tanggal untuk detail</p>
                    </div>
                    <a href="{{ route('vendor.cars.calendar', [$car->id, 'year' => $nextYear, 'month' => $nextMonth]) }}"
                       class="p-2 rounded-lg hover:bg-gray-200 text-gray-600 transition font-bold text-lg">›</a>
                </div>

                <div class="p-4">
                    {{-- Header hari --}}
                    <div class="grid grid-cols-7 mb-2">
                        @foreach(['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $dayName)
                            <div class="text-center text-xs font-bold text-gray-400 py-2 uppercase tracking-wide">{{ $dayName }}</div>
                        @endforeach
                    </div>

                    {{-- Grid kalender --}}
                    @php
                        $firstDay    = \Carbon\Carbon::create($year, $month, 1)->dayOfWeek;
                        $daysInMonth = \Carbon\Carbon::create($year, $month, 1)->daysInMonth;

                        // Ambil detail booking per tanggal untuk tooltip
                        $bookingDetails = \App\Models\Booking::where('car_id', $car->id)
                            ->whereIn('status', ['awaiting_vendor','confirmed','ongoing'])
                            ->where('end_at', '>=', \Carbon\Carbon::create($year, $month, 1)->startOfMonth())
                            ->where('start_at', '<=', \Carbon\Carbon::create($year, $month, 1)->endOfMonth())
                            ->with('customer')
                            ->get();
                    @endphp

                    <div class="grid grid-cols-7 gap-1" x-data="{ selected: null }">
                        {{-- Padding awal --}}
                        @for($i = 0; $i < $firstDay; $i++)
                            <div class="aspect-square"></div>
                        @endfor

                        {{-- Hari-hari --}}
                        @for($day = 1; $day <= $daysInMonth; $day++)
                            @php
                                $dateStr  = \Carbon\Carbon::create($year, $month, $day)->toDateString();
                                $status   = $calendar[$dateStr] ?? 'available';
                                $isToday  = $dateStr === now()->toDateString();
                                $isPast   = $dateStr < now()->toDateString();

                                // Cari booking yang mencakup tanggal ini
                                $dayBooking = $bookingDetails->first(function ($b) use ($dateStr) {
                                    return $b->start_at->toDateString() <= $dateStr
                                        && $b->end_at->toDateString() >= $dateStr;
                                });

                                $bgClass = match($status) {
                                    'booked'      => 'bg-blue-500 text-white hover:bg-blue-600',
                                    'blocked'     => 'bg-red-400 text-white hover:bg-red-500',
                                    'maintenance' => 'bg-yellow-400 text-white hover:bg-yellow-500',
                                    default       => $isPast
                                        ? 'bg-gray-100 text-gray-400 cursor-default'
                                        : 'bg-green-50 text-green-800 hover:bg-green-100 border border-green-200',
                                };

                                $tooltipText = match($status) {
                                    'booked'      => $dayBooking ? 'Dipesan: ' . ($dayBooking->customer?->full_name ?? '—') . ' (' . $dayBooking->code . ')' : 'Dipesan',
                                    'blocked'     => 'Diblokir',
                                    'maintenance' => 'Maintenance',
                                    default       => 'Tersedia',
                                };
                            @endphp

                            <div class="aspect-square flex flex-col items-center justify-center rounded-lg text-sm font-semibold
                                        cursor-pointer transition-all relative group
                                        {{ $bgClass }}
                                        {{ $isToday ? 'ring-2 ring-offset-1 ring-blue-500' : '' }}"
                                 @click="selected = selected === '{{ $dateStr }}' ? null : '{{ $dateStr }}'"
                                 title="{{ $tooltipText }}">

                                {{-- Nomor hari --}}
                                <span class="text-sm leading-none">{{ $day }}</span>

                                {{-- Dot indicator untuk booking --}}
                                @if($status === 'booked' && $dayBooking && $dayBooking->start_at->toDateString() === $dateStr)
                                    <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-white rounded-full opacity-80"></span>
                                @endif

                                {{-- Tooltip hover --}}
                                <div class="hidden group-hover:block absolute bottom-full mb-1 left-1/2 -translate-x-1/2
                                            bg-gray-900 text-white text-xs rounded-lg px-2 py-1.5 whitespace-nowrap z-20
                                            shadow-lg pointer-events-none" style="min-width: 120px; text-align:center;">
                                    <p class="font-semibold">{{ \Carbon\Carbon::parse($dateStr)->format('d M Y') }}</p>
                                    <p class="opacity-80 mt-0.5">{{ $tooltipText }}</p>
                                    @if($status === 'booked' && $dayBooking)
                                        <p class="opacity-70 text-xs mt-0.5">
                                            {{ $dayBooking->start_at->format('d M') }} → {{ $dayBooking->end_at->format('d M') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endfor
                    </div>

                    {{-- Legend --}}
                    <div class="flex flex-wrap gap-4 mt-5 pt-4 border-t border-gray-100 text-xs text-gray-600">
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded bg-green-100 border border-green-300"></span> Tersedia
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded bg-blue-500"></span> Dipesan Customer
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded bg-red-400"></span> Diblokir
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded bg-yellow-400"></span> Maintenance
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded bg-gray-100 border border-gray-300"></span> Sudah Lewat
                        </div>
                    </div>
                </div>
            </div>

            {{-- Daftar Booking Bulan Ini --}}
            @php
                $monthBookings = \App\Models\Booking::where('car_id', $car->id)
                    ->whereIn('status', ['awaiting_vendor','confirmed','ongoing','completed'])
                    ->where(function ($q) use ($year, $month) {
                        $from = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
                        $to   = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();
                        $q->whereBetween('start_at', [$from, $to])
                          ->orWhereBetween('end_at', [$from, $to]);
                    })
                    ->with('customer')
                    ->orderBy('start_at')
                    ->get();
            @endphp

            @if($monthBookings->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mt-4 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-semibold text-gray-900">📋 Booking Bulan Ini ({{ $monthBookings->count() }})</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($monthBookings as $b)
                        @php
                            $days = max(1, $b->start_at->diffInDays($b->end_at));
                            $statusColor = match($b->status) {
                                'awaiting_vendor' => 'bg-blue-100 text-blue-700',
                                'confirmed'       => 'bg-green-100 text-green-700',
                                'ongoing'         => 'bg-purple-100 text-purple-700',
                                'completed'       => 'bg-gray-100 text-gray-600',
                                default           => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <div class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-gray-50">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-2 h-10 rounded-full flex-shrink-0
                                    {{ $b->status === 'confirmed' ? 'bg-green-400' : ($b->status === 'ongoing' ? 'bg-purple-400' : 'bg-blue-400') }}">
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-sm text-gray-900 truncate">{{ $b->customer?->full_name ?? '—' }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $b->start_at->format('d M') }} → {{ $b->end_at->format('d M Y') }}
                                        <span class="mx-1">·</span> {{ $days }} hari
                                    </p>
                                    <p class="text-xs font-mono text-gray-400">{{ $b->code }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusColor }}">
                                    {{ match($b->status) {
                                        'awaiting_vendor' => 'Menunggu',
                                        'confirmed'       => 'Dikonfirmasi',
                                        'ongoing'         => 'Berlangsung',
                                        'completed'       => 'Selesai',
                                        default           => ucfirst($b->status),
                                    } }}
                                </span>
                                <span class="text-sm font-bold text-gray-700">{{ formatRupiah($b->total) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- ── Panel Aksi ── --}}
        <div class="space-y-4">

            {{-- Navigasi Cepat --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-900 mb-3 text-sm">⚡ Navigasi Cepat</h3>
                <div class="grid grid-cols-2 gap-2">
                    @php
                        $thisMonth = now()->month;
                        $thisYear  = now()->year;
                    @endphp
                    @for($m = 0; $m < 4; $m++)
                        @php
                            $mMonth = (($thisMonth - 1 + $m) % 12) + 1;
                            $mYear  = $thisYear + intdiv($thisMonth - 1 + $m, 12);
                            $mName  = \Carbon\Carbon::create($mYear, $mMonth, 1)->locale('id')->translatedFormat('M Y');
                            $isActive = $mMonth === $month && $mYear === $year;
                        @endphp
                        <a href="{{ route('vendor.cars.calendar', [$car->id, 'year' => $mYear, 'month' => $mMonth]) }}"
                           class="text-center py-2 px-3 rounded-lg text-xs font-semibold transition
                               {{ $isActive ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            {{ $mName }}
                        </a>
                    @endfor
                </div>
            </div>

            {{-- Block Tanggal --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="font-semibold text-gray-900 mb-4">🔒 Blokir Tanggal</h3>
                <form method="POST" action="{{ route('vendor.cars.calendar.block', $car->id) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Dari Tanggal</label>
                        <input type="date" name="from" required min="{{ now()->toDateString() }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                               style="color:#111827;">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Sampai Tanggal</label>
                        <input type="date" name="to" required min="{{ now()->toDateString() }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                               style="color:#111827;">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tipe Blokir</label>
                        <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="blocked">⛔ Tidak Tersedia</option>
                            <option value="maintenance">🔧 Maintenance / Servis</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Alasan (opsional)</label>
                        <input type="text" name="reason" placeholder="Contoh: Libur lebaran"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit"
                            class="w-full bg-red-600 text-white py-2.5 rounded-lg hover:bg-red-700 text-sm font-semibold transition">
                        🔒 Blokir Tanggal
                    </button>
                </form>
            </div>

            {{-- Hapus Blokir --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="font-semibold text-gray-900 mb-4">🔓 Hapus Blokir</h3>
                <form method="POST" action="{{ route('vendor.cars.calendar.unblock', $car->id) }}" class="space-y-3">
                    @csrf
                    @method('DELETE')
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Dari Tanggal</label>
                        <input type="date" name="from" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                               style="color:#111827;">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Sampai Tanggal</label>
                        <input type="date" name="to" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                               style="color:#111827;">
                    </div>
                    <button type="submit"
                            class="w-full bg-green-600 text-white py-2.5 rounded-lg hover:bg-green-700 text-sm font-semibold transition">
                        🔓 Hapus Blokir
                    </button>
                </form>
            </div>

            {{-- Info Mobil --}}
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm">
                <h3 class="font-semibold text-blue-900 mb-2">ℹ️ Info Mobil</h3>
                <div class="space-y-1 text-blue-800 text-xs">
                    <p>🚗 {{ $car->brand }} {{ $car->model }} {{ $car->year }}</p>
                    <p>🔢 {{ $car->plate_number }}</p>
                    <p>⚙️ {{ ucfirst($car->transmission) }}</p>
                    <p>💰 {{ formatRupiah($car->pricing->daily_price) }}/hari</p>
                    <p class="mt-2 text-blue-600">
                        Status:
                        <span class="font-semibold {{ $car->status === 'published' ? 'text-green-700' : 'text-red-700' }}">
                            {{ $car->status === 'published' ? '✅ Aktif' : '⛔ ' . ucfirst($car->status) }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
