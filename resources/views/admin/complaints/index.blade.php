@extends('layouts.admin')

@section('title', 'Manajemen Komplain - Admin')

@section('content')
<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Manajemen Komplain</h1>
        <a href="/admin" class="text-blue-600 hover:underline text-sm">← Dashboard Admin</a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-lg shadow-sm border p-4 mb-6 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status</option>
                @foreach($statuses as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Tingkat Keparahan</label>
            <select name="severity" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua</option>
                @foreach($severities as $val => $label)
                    <option value="{{ $val }}" {{ request('severity') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-gray-600 mb-1">Cari</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Referensi atau nama pelanggan..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">Filter</button>
        @if(request()->hasAny(['status', 'severity', 'search']))
            <a href="{{ route('admin.complaints.index') }}" class="text-gray-500 hover:text-gray-700 text-sm py-2">Reset</a>
        @endif
    </form>

    {{-- Table --}}
    @if($complaints->isEmpty())
        <div class="bg-white rounded-lg shadow-sm border p-12 text-center">
            <div class="text-5xl mb-4">📋</div>
            <p class="text-gray-500">Tidak ada komplain ditemukan.</p>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Referensi</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Pelanggan</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Vendor</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Kategori</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Tingkat</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Tanggal</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($complaints as $complaint)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono font-semibold text-blue-700">{{ $complaint->reference }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $complaint->reporter?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $complaint->vendor?->business_name ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $complaint->category?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @php $sc = $complaint->severityColor(); @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-{{ $sc }}-100 text-{{ $sc }}-700">
                                    {{ $complaint->severityLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @php $stc = $complaint->statusColor(); @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-{{ $stc }}-100 text-{{ $stc }}-700">
                                    {{ $complaint->statusLabel() }}
                                </span>
                                @if($complaint->isOverdue())
                                    <span class="ml-1 text-red-600 text-xs font-semibold">⚠ Overdue</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $complaint->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.complaints.show', $complaint->id) }}"
                                   class="text-blue-600 hover:underline text-xs font-medium">Detail</a>
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
