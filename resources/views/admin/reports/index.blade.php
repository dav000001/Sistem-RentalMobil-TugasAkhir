@extends('layouts.admin')
@section('title', 'Laporan Keuangan - Admin')

@section('content')
<div>

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Laporan Keuangan</h1>
    </div>

    {{-- Filter Preset --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap gap-3 items-end">
            <div class="flex gap-2 flex-wrap">
                @foreach(['today' => 'Hari Ini', '7d' => '7 Hari', '30d' => '30 Hari', 'this_month' => 'Bulan Ini', 'last_month' => 'Bulan Lalu', 'custom' => 'Custom'] as $key => $label)
                    <a href="{{ route('admin.reports.index', ['preset' => $key]) }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                           {{ $preset === $key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
            @if($preset === 'custom')
                <div class="flex gap-2 items-center">
                    <input type="date" name="from" value="{{ $from->toDateString() }}"
                           style="color:#111827;" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <span class="text-gray-500">—</span>
                    <input type="date" name="to" value="{{ $to->toDateString() }}"
                           style="color:#111827;" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <input type="hidden" name="preset" value="custom">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">Terapkan</button>
                </div>
            @endif
        </form>
        <p class="text-xs text-gray-400 mt-2">
            Periode: {{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}
        </p>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach([
            ['label' => 'Total Transaksi (GMV)', 'value' => 'Rp ' . number_format($gmv, 0, ',', '.'), 'icon' => '💰', 'color' => 'blue'],
            ['label' => 'Komisi Platform', 'value' => 'Rp ' . number_format($commission, 0, ',', '.'), 'icon' => '📊', 'color' => 'green'],
            ['label' => 'Booking Selesai', 'value' => number_format($bookingCount), 'icon' => '✅', 'color' => 'purple'],
            ['label' => 'Vendor Aktif', 'value' => number_format($activeVendors), 'icon' => '🏢', 'color' => 'orange'],
        ] as $kpi)
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="text-2xl mb-2">{{ $kpi['icon'] }}</div>
                <p class="text-2xl font-bold text-gray-900">{{ $kpi['value'] }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ $kpi['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Chart Harian --}}
    @if(count($dailyBreakdown) > 0)
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
        <h2 class="font-semibold text-gray-900 mb-4">Transaksi Harian</h2>
        <div class="overflow-x-auto">
            <div class="flex items-end gap-1 h-40 min-w-max">
                @php $maxGmv = max(array_column($dailyBreakdown, 'gmv') ?: [1]); @endphp
                @foreach($dailyBreakdown as $date => $data)
                    @php $height = $maxGmv > 0 ? round(($data['gmv'] / $maxGmv) * 100) : 0; @endphp
                    <div class="flex flex-col items-center gap-1" title="{{ $date }}: Rp {{ number_format($data['gmv'], 0, ',', '.') }}">
                        <div class="bg-blue-500 rounded-t w-6 hover:bg-blue-600 transition"
                             style="height: {{ max($height, 2) }}%"></div>
                        <span class="text-xs text-gray-400 rotate-45 origin-left" style="font-size:9px">
                            {{ \Carbon\Carbon::parse($date)->format('d/m') }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Top Vendor --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-semibold text-gray-900 mb-4">Top Vendor</h2>
            @if(count($topVendors) > 0)
                <div class="space-y-3">
                    @foreach($topVendors as $i => $vendor)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-xs font-bold">{{ $i + 1 }}</span>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $vendor['business_name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $vendor['total_bookings'] }} booking</p>
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-green-600">Rp {{ number_format($vendor['gmv'], 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-400 text-sm">Belum ada data</p>
            @endif
        </div>

        {{-- Top Mobil --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-semibold text-gray-900 mb-4">Top Mobil</h2>
            @if(count($topCars) > 0)
                <div class="space-y-3">
                    @foreach($topCars as $i => $car)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 bg-orange-100 text-orange-700 rounded-full flex items-center justify-center text-xs font-bold">{{ $i + 1 }}</span>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $car['car_name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $car['total_bookings'] }} booking</p>
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-green-600">Rp {{ number_format($car['revenue'], 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-400 text-sm">Belum ada data</p>
            @endif
        </div>
    </div>

    {{-- Status Booking --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Distribusi Status Booking (Semua Waktu)</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @php
                $statusLabels = [
                    'awaiting_payment' => ['label' => 'Menunggu Bayar', 'color' => 'yellow'],
                    'awaiting_vendor'  => ['label' => 'Menunggu Vendor', 'color' => 'blue'],
                    'confirmed'        => ['label' => 'Dikonfirmasi', 'color' => 'green'],
                    'completed'        => ['label' => 'Selesai', 'color' => 'green'],
                    'cancelled'        => ['label' => 'Dibatalkan', 'color' => 'red'],
                    'ongoing'          => ['label' => 'Berlangsung', 'color' => 'blue'],
                ];
            @endphp
            @foreach($byStatus as $status => $count)
                @php $info = $statusLabels[$status] ?? ['label' => ucfirst($status), 'color' => 'gray']; @endphp
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($count) }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $info['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Breakdown Per Vendor --}}
    @if(count($vendorBreakdown) > 0)
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mt-8">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Rincian Per Vendor</h2>
            <p class="text-xs text-gray-400 mt-0.5">Breakdown komisi platform yang diterima dari setiap vendor</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-5 py-3 text-left">Vendor</th>
                        <th class="px-5 py-3 text-right">Booking</th>
                        <th class="px-5 py-3 text-right">Total Dibayar Customer</th>
                        <th class="px-5 py-3 text-right">Subtotal Sewa</th>
                        <th class="px-5 py-3 text-right">Komisi Platform</th>
                        <th class="px-5 py-3 text-right">Payout Vendor</th>
                        <th class="px-5 py-3 text-right">% Komisi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($vendorBreakdown as $v)
                        @php
                            $commissionPct = $v['total_subtotal'] > 0
                                ? round(($v['total_commission'] / $v['total_subtotal']) * 100, 1)
                                : 0;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $v['business_name'] }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ $v['total_bookings'] }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900">
                                Rp {{ number_format($v['total_gmv'], 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-right text-gray-600">
                                Rp {{ number_format($v['total_subtotal'], 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-green-600">
                                Rp {{ number_format($v['total_commission'], 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-right text-blue-600">
                                Rp {{ number_format($v['total_payout'], 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded-full">
                                    {{ $commissionPct }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 font-bold border-t-2 border-gray-200">
                    <tr>
                        <td class="px-5 py-3 text-gray-900">Total</td>
                        <td class="px-5 py-3 text-right text-gray-900">
                            {{ array_sum(array_column($vendorBreakdown, 'total_bookings')) }}
                        </td>
                        <td class="px-5 py-3 text-right text-gray-900">
                            Rp {{ number_format(array_sum(array_column($vendorBreakdown, 'total_gmv')), 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3 text-right text-gray-900">
                            Rp {{ number_format(array_sum(array_column($vendorBreakdown, 'total_subtotal')), 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3 text-right text-green-700">
                            Rp {{ number_format(array_sum(array_column($vendorBreakdown, 'total_commission')), 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3 text-right text-blue-700">
                            Rp {{ number_format(array_sum(array_column($vendorBreakdown, 'total_payout')), 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
