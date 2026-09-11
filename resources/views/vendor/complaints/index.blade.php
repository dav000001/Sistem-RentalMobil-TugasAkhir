@extends('layouts.vendor')
@section('title', 'Komplain dari Customer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Komplain dari Customer</h1>
            <p class="text-sm text-gray-500 mt-1">Komplain yang diteruskan admin untuk Anda tanggapi.</p>
        </div>
        <span class="text-sm text-gray-500">{{ $complaints->total() }} komplain</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    @if($complaints->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
            <p class="text-4xl mb-3">📋</p>
            <p class="text-gray-500 font-medium">Belum ada komplain dari customer.</p>
            <p class="text-gray-400 text-sm mt-1">Komplain akan muncul di sini jika admin meneruskan ke Anda.</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Referensi</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Customer</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Pesanan</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Kategori</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Tanggal</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($complaints as $complaint)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono font-semibold text-blue-700">
                                {{ $complaint->reference }}
                                @if($complaint->status === 'forwarded_to_vendor' && $complaint->responses->isEmpty())
                                    <span class="ml-1 inline-block w-2 h-2 rounded-full bg-red-500 align-middle"></span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $complaint->reporter?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                @if($complaint->booking)
                                    {{ $complaint->booking->car?->brand }} {{ $complaint->booking->car?->model }}
                                    <br><span class="text-xs text-gray-400">{{ $complaint->booking->code }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">{{ $complaint->category?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColor = match($complaint->status) {
                                        'forwarded_to_vendor' => 'orange',
                                        'vendor_responded'    => 'blue',
                                        'under_admin_review'  => 'purple',
                                        'resolved'            => 'green',
                                        'rejected'            => 'gray',
                                        default               => 'yellow',
                                    };
                                    $statusLabel = match($complaint->status) {
                                        'forwarded_to_vendor' => '⚠️ Perlu Respons',
                                        'vendor_responded'    => '💬 Sudah Direspons',
                                        'under_admin_review'  => '🔍 Ditinjau Admin',
                                        'resolved'            => '✅ Selesai',
                                        'rejected'            => '⚫ Ditolak',
                                        default               => $complaint->statusLabel(),
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                    bg-{{ $statusColor }}-100 text-{{ $statusColor }}-700">
                                    {{ $statusLabel }}
                                </span>
                                @if($complaint->isOverdue())
                                    <br><span class="text-red-600 text-xs font-semibold">⚠ Lewat batas!</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $complaint->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('vendor.complaints.show', $complaint->id) }}"
                                   class="text-blue-600 hover:underline text-xs font-medium">
                                    Lihat & Respons
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $complaints->links() }}
        </div>
    @endif

</div>
@endsection
