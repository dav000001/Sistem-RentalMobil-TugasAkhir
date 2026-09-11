@extends('layouts.vendor')
@section('title', 'Laporan Saya')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Saya</h1>
            <p class="text-sm text-gray-500 mt-1">Laporan masalah yang Anda ajukan ke admin.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    @if($reports->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
            <p class="text-4xl mb-3">📋</p>
            <p class="text-gray-500 font-medium">Belum ada laporan yang diajukan.</p>
            <p class="text-gray-400 text-sm mt-1">Laporan masalah dapat diajukan dari halaman detail pemesanan.</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Referensi</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Pesanan</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Kategori</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Tanggal</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($reports as $report)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono font-semibold text-orange-700">
                                {{ $report->reference }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                @if($report->booking)
                                    {{ $report->booking->car?->brand }} {{ $report->booking->car?->model }}
                                    <br><span class="text-xs text-gray-400">{{ $report->booking->code }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">{{ $report->category?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $color = match($report->status) {
                                        'submitted'          => 'yellow',
                                        'under_admin_review' => 'blue',
                                        'resolved'           => 'green',
                                        'rejected'           => 'red',
                                        default              => 'gray',
                                    };
                                    $label = match($report->status) {
                                        'submitted'          => '⏳ Menunggu Tinjauan Admin',
                                        'under_admin_review' => '🔍 Sedang Ditinjau',
                                        'resolved'           => '✅ Selesai',
                                        'rejected'           => '❌ Ditolak',
                                        default              => $report->statusLabel(),
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-{{ $color }}-100 text-{{ $color }}-700">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $report->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('vendor.my-reports.show', $report->id) }}"
                                   class="text-blue-600 hover:underline text-xs font-medium">
                                    Lihat Detail
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $reports->links() }}</div>
    @endif

</div>
@endsection
