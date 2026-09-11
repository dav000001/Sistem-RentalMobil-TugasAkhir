@extends('layouts.app')

@section('title', 'Komplain Saya')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Komplain Saya</h1>
        <a href="{{ route('bookings.index') }}" class="text-blue-600 hover:underline text-sm">← Pesanan Saya</a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($complaints->isEmpty())
        <div class="bg-white rounded-lg shadow-sm border p-12 text-center">
            <div class="text-5xl mb-4">📋</div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Belum Ada Komplain</h3>
            <p class="text-gray-500 text-sm">Anda belum pernah mengajukan komplain.</p>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Referensi</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Kategori</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Pesanan</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Tanggal</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($complaints as $complaint)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono font-semibold text-blue-700">{{ $complaint->reference }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $complaint->category?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                @if($complaint->booking)
                                    {{ $complaint->booking->car?->brand }} {{ $complaint->booking->car?->model }}
                                    <br><span class="text-xs text-gray-400">{{ $complaint->booking->code }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php $color = $complaint->statusColor(); @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-semibold
                                    bg-{{ $color }}-100 text-{{ $color }}-700">
                                    {{ $complaint->statusLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $complaint->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('complaints.show', $complaint->id) }}"
                                   class="text-blue-600 hover:underline text-xs font-medium">Lihat Detail</a>
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
