@extends('layouts.vendor')

@section('title', 'Laporan Keuangan - Vendor Panel')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">📊 Laporan Keuangan</h1>
            <p class="text-gray-500 mt-1">{{ auth('vendor')->user()?->vendor?->business_name ?? '-' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('vendor.reports.export-pdf', request()->query()) }}"
               class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm font-semibold transition">
                📄 Export PDF
            </a>
            <a href="{{ route('vendor.reports.export-csv', request()->query()) }}"
               class="inline-flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm font-semibold transition">
                📊 Export CSV/Excel
            </a>
        </div>
    </div>

    {{-- Filter Periode --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('vendor.reports.index') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Periode</label>
                <select name="preset" id="preset-select"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                        onchange="toggleCustom(this.value)">
                    <option value="today"      {{ $preset === 'today'      ? 'selected' : '' }}>Hari Ini</option>
                    <option value="7d"         {{ $preset === '7d'         ? 'selected' : '' }}>7 Hari Terakhir</option>
                    <option value="30d"        {{ $preset === '30d'        ? 'selected' : '' }}>30 Hari Terakhir</option>
                    <option value="this_month" {{ $preset === 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                    <option value="last_month" {{ $preset === 'last_month' ? 'selected' : '' }}>Bulan Lalu</option>
                    <option value="custom"     {{ $preset === 'custom'     ? 'selected' : '' }}>Kustom</option>
                </select>
            </div>
            <div id="custom-range" class="{{ $preset === 'custom' ? 'flex' : 'hidden' }} gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Dari</label>
                    <input type="date" name="from" value="{{ request('from', $from->format('Y-m-d')) }}"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Sampai</label>
                    <input type="date" name="to" value="{{ request('to', $to->format('Y-m-d')) }}"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 text-sm font-semibold">
                Tampilkan
            </button>
        </form>
        <p class="text-xs text-gray-400 mt-2">
            Menampilkan data: <strong>{{ $from->format('d M Y') }}</strong> — <strong>{{ $to->format('d M Y') }}</strong>
        </p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Total Booking</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $bookingCount }}</p>
            <p class="text-xs text-gray-400 mt-1">transaksi terkonfirmasi</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-green-500">
            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Total Pendapatan</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ formatRupiah($totalRevenue) }}</p>
            <p class="text-xs text-gray-400 mt-1">gross dari customer</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-orange-500">
            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Komisi Platform</p>
            <p class="text-2xl font-bold text-orange-600 mt-1">{{ formatRupiah($totalCommission) }}</p>
            <p class="text-xs text-gray-400 mt-1">dipotong platform</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-purple-500">
            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Payout Anda</p>
            <p class="text-2xl font-bold text-purple-600 mt-1">{{ formatRupiah($totalPayout) }}</p>
            <p class="text-xs text-gray-400 mt-1">setelah komisi</p>
        </div>
    </div>

    {{-- Payout Status --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="bg-green-50 border border-green-200 rounded-xl p-5 flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-2xl">✅</div>
            <div>
                <p class="text-sm text-green-700 font-semibold">Sudah Diterima (periode ini)</p>
                <p class="text-2xl font-bold text-green-800">{{ formatRupiah($payoutReceived) }}</p>
            </div>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5 flex items-center gap-4">
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center text-2xl">⏳</div>
            <div>
                <p class="text-sm text-yellow-700 font-semibold">Menunggu Transfer (semua waktu)</p>
                <p class="text-2xl font-bold text-yellow-800">{{ formatRupiah($payoutPending) }}</p>
            </div>
        </div>
    </div>

    {{-- Grafik Harian --}}
    @if($dailyData->count() > 0)
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">📈 Pendapatan Harian</h2>
        <div class="overflow-x-auto">
            <div class="flex items-end gap-1 min-w-max" style="height: 160px;">
                @php
                    $maxPayout = $dailyData->max('payout') ?: 1;
                @endphp
                @foreach($dailyData as $date => $day)
                    @php $height = max(4, round(($day->payout / $maxPayout) * 140)); @endphp
                    <div class="flex flex-col items-center gap-1 group" style="min-width: 32px;">
                        <div class="relative">
                            <div class="hidden group-hover:block absolute bottom-full mb-1 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap z-10">
                                {{ \Carbon\Carbon::parse($date)->format('d M') }}<br>
                                {{ formatRupiah($day->payout) }}<br>
                                {{ $day->count }} booking
                            </div>
                            <div class="bg-blue-500 hover:bg-blue-600 rounded-t transition-colors cursor-pointer"
                                 style="width: 28px; height: {{ $height }}px;"></div>
                        </div>
                        <span class="text-xs text-gray-400 rotate-45 origin-left" style="font-size:9px;">
                            {{ \Carbon\Carbon::parse($date)->format('d/m') }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Per Mobil --}}
    @if($perCar->count() > 0)
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">🚗 Performa Per Mobil</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-2 text-gray-600 font-semibold">Mobil</th>
                        <th class="text-right py-3 px-2 text-gray-600 font-semibold">Booking</th>
                        <th class="text-right py-3 px-2 text-gray-600 font-semibold">Total Pendapatan</th>
                        <th class="text-right py-3 px-2 text-gray-600 font-semibold">Payout Anda</th>
                        <th class="text-right py-3 px-2 text-gray-600 font-semibold">Kontribusi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($perCar as $car)
                        @php $pct = $totalRevenue > 0 ? round(($car->total_revenue / $totalRevenue) * 100) : 0; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-2 font-medium text-gray-900">{{ $car->car_name }}</td>
                            <td class="py-3 px-2 text-right text-gray-700">{{ $car->total_bookings }}</td>
                            <td class="py-3 px-2 text-right text-gray-700">{{ formatRupiah($car->total_revenue) }}</td>
                            <td class="py-3 px-2 text-right font-semibold text-green-600">{{ formatRupiah($car->total_payout) }}</td>
                            <td class="py-3 px-2 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 w-8 text-right">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-gray-300">
                    <tr class="font-bold">
                        <td class="py-3 px-2 text-gray-900">Total</td>
                        <td class="py-3 px-2 text-right text-gray-900">{{ $bookingCount }}</td>
                        <td class="py-3 px-2 text-right text-gray-900">{{ formatRupiah($totalRevenue) }}</td>
                        <td class="py-3 px-2 text-right text-green-600">{{ formatRupiah($totalPayout) }}</td>
                        <td class="py-3 px-2 text-right text-gray-500">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    {{-- Tabel Transaksi --}}
    @if($recentBookings->count() > 0)
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900">📋 Riwayat Transaksi</h2>
            <span class="text-xs text-gray-400">Menampilkan 20 terbaru</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-2 text-gray-600 font-semibold">Kode</th>
                        <th class="text-left py-3 px-2 text-gray-600 font-semibold">Tanggal Bayar</th>
                        <th class="text-left py-3 px-2 text-gray-600 font-semibold">Customer</th>
                        <th class="text-left py-3 px-2 text-gray-600 font-semibold">Mobil</th>
                        <th class="text-right py-3 px-2 text-gray-600 font-semibold">Total</th>
                        <th class="text-right py-3 px-2 text-gray-600 font-semibold">Payout</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($recentBookings as $b)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-2 font-mono text-xs text-gray-600">{{ $b->code }}</td>
                            <td class="py-3 px-2 text-gray-600">{{ $b->payment?->paid_at?->format('d M Y') ?? '-' }}</td>
                            <td class="py-3 px-2 text-gray-700">{{ $b->customer?->full_name ?? '-' }}</td>
                            <td class="py-3 px-2 text-gray-700">{{ $b->car->brand }} {{ $b->car->model }}</td>
                            <td class="py-3 px-2 text-right text-gray-700">{{ formatRupiah($b->total) }}</td>
                            <td class="py-3 px-2 text-right font-semibold text-green-600">{{ formatRupiah($b->vendor_payout_amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <div class="text-5xl mb-3">📊</div>
            <p class="text-gray-500">Belum ada transaksi pada periode ini</p>
        </div>
    @endif
</div>

@push('scripts')
<script>
function toggleCustom(val) {
    const el = document.getElementById('custom-range');
    el.classList.toggle('hidden', val !== 'custom');
    el.classList.toggle('flex', val === 'custom');
}
</script>
@endpush
@endsection
