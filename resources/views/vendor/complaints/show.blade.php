@extends('layouts.vendor')
@section('title', 'Komplain ' . $complaint->reference)

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Back + Header --}}
    <div class="mb-6">
        <a href="{{ route('vendor.complaints.index') }}"
           class="text-sm text-blue-600 hover:underline">← Daftar Komplain</a>
        <div class="flex flex-wrap items-center gap-3 mt-2">
            <h1 class="text-2xl font-bold text-gray-900">{{ $complaint->reference }}</h1>
            @php
                $color = match($complaint->status) {
                    'forwarded_to_vendor' => 'orange',
                    'vendor_responded'    => 'blue',
                    'under_admin_review'  => 'purple',
                    'resolved'            => 'green',
                    'rejected'            => 'gray',
                    default               => 'yellow',
                };
                $label = match($complaint->status) {
                    'forwarded_to_vendor' => '⚠️ Perlu Respons Anda',
                    'vendor_responded'    => '💬 Sudah Direspons',
                    'under_admin_review'  => '🔍 Sedang Ditinjau Admin',
                    'resolved'            => '✅ Selesai',
                    'rejected'            => '⚫ Ditolak',
                    default               => $complaint->statusLabel(),
                };
            @endphp
            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-{{ $color }}-100 text-{{ $color }}-700">
                {{ $label }}
            </span>
        </div>
        <p class="text-gray-500 text-sm mt-1">
            Diajukan {{ $complaint->created_at->format('d M Y H:i') }} ·
            Kategori: {{ $complaint->category?->name ?? '-' }}
        </p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Konten Utama --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Detail Laporan Customer --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="font-semibold text-lg mb-3">Detail Laporan Customer</h2>
                <p class="text-gray-700 text-sm leading-relaxed">{{ $complaint->description }}</p>

                {{-- Bukti yang dilampirkan --}}
                @if($complaint->attachments && count($complaint->attachments) > 0)
                    <div class="mt-4 pt-4 border-t">
                        <p class="text-sm font-medium text-gray-600 mb-2">📎 Bukti yang Dilampirkan Customer</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach($complaint->attachments as $path)
                                @php
                                    $url = asset('storage/' . ltrim($path, '/'));
                                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                    $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                                @endphp
                                @if($isImg)
                                    <a href="{{ $url }}" target="_blank">
                                        <img src="{{ $url }}" alt="Bukti"
                                             class="h-24 w-auto rounded-lg border border-gray-200 object-cover hover:opacity-80 transition cursor-pointer">
                                    </a>
                                @else
                                    <a href="{{ $url }}" target="_blank"
                                       class="flex items-center gap-1.5 px-3 py-2 bg-gray-100 rounded-lg text-sm text-blue-600 hover:bg-gray-200">
                                        📄 {{ basename($path) }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-4 pt-4 border-t grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500 text-xs">Permintaan Customer</p>
                        <p class="font-medium">
                            @php
                                $demandLabels = [
                                    'refund_full'    => 'Refund Penuh',
                                    'refund_partial' => 'Refund Sebagian',
                                    'discount_next'  => 'Diskon Sewa Berikutnya',
                                    'apology'        => 'Permintaan Maaf',
                                    'other'          => 'Lainnya',
                                ];
                            @endphp
                            {{ $demandLabels[$complaint->customer_demand] ?? $complaint->customer_demand }}
                        </p>
                    </div>
                    @if($complaint->demanded_refund_amount)
                        <div>
                            <p class="text-gray-500 text-xs">Jumlah Refund Diminta</p>
                            <p class="font-medium">Rp {{ number_format($complaint->demanded_refund_amount, 0, ',', '.') }}</p>
                        </div>
                    @endif
                    @if($complaint->customer_demand_note)
                        <div class="col-span-2">
                            <p class="text-gray-500 text-xs">Catatan Customer</p>
                            <p class="font-medium">{{ $complaint->customer_demand_note }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Info Pesanan --}}
            @if($complaint->booking)
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="font-semibold text-lg mb-3">Pesanan Terkait</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500 text-xs">Kode Booking</p>
                            <p class="font-medium">{{ $complaint->booking->code }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Mobil</p>
                            <p class="font-medium">{{ $complaint->booking->car?->brand }} {{ $complaint->booking->car?->model }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Periode Sewa</p>
                            <p class="font-medium">
                                {{ $complaint->booking->start_at?->format('d M Y') }}
                                – {{ $complaint->booking->end_at?->format('d M Y') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Total</p>
                            <p class="font-medium">Rp {{ number_format($complaint->booking->total, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Keputusan Admin --}}
            @if($complaint->resolution)
                <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                    <h2 class="font-semibold text-lg mb-3 text-green-800">✅ Keputusan Admin</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Keputusan</span>
                            <span class="font-semibold">{{ $complaint->resolution->decisionLabel() }}</span>
                        </div>
                        @if($complaint->resolution->refund_amount)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Refund ke Customer</span>
                                <span class="font-semibold">Rp {{ number_format($complaint->resolution->refund_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if($complaint->resolution->vendor_penalty_amount)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Penalti Vendor</span>
                                <span class="font-semibold text-red-600">Rp {{ number_format($complaint->resolution->vendor_penalty_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="pt-2 border-t">
                            <p class="text-gray-600 text-xs mb-1">Alasan</p>
                            <p>{{ $complaint->resolution->reasoning }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Riwayat Komunikasi --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="font-semibold text-lg mb-4">Riwayat Komunikasi</h2>

                @php $publicResponses = $complaint->responses->where('visibility', 'public'); @endphp

                @if($publicResponses->isEmpty())
                    <p class="text-gray-500 text-sm">Belum ada respons.</p>
                @else
                    <div class="space-y-4">
                        @foreach($publicResponses as $response)
                            @php $isMe = $response->author_role === 'vendor'; @endphp
                            <div class="flex gap-3 {{ $isMe ? 'flex-row-reverse' : '' }}">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0
                                    @if($response->author_role === 'admin') bg-purple-600
                                    @elseif($response->author_role === 'vendor') bg-orange-500
                                    @else bg-blue-600
                                    @endif">
                                    {{ strtoupper(substr($response->author?->name ?? '?', 0, 1)) }}
                                </div>
                                <div class="flex-1 max-w-sm">
                                    <div class="flex items-center gap-2 mb-1 {{ $isMe ? 'justify-end' : '' }}">
                                        <span class="text-xs font-semibold text-gray-700">{{ $response->author?->name }}</span>
                                        <span class="text-xs text-gray-400">({{ $response->authorRoleLabel() }})</span>
                                        <span class="text-xs text-gray-400">{{ $response->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="rounded-lg p-3 text-sm
                                        @if($isMe) bg-orange-500 text-white
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ $response->message }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Form Respons Vendor --}}
                @if($complaint->isOpen() && in_array($complaint->status, ['forwarded_to_vendor', 'vendor_responded', 'under_admin_review']))
                    <div class="mt-6 pt-6 border-t">
                        <h3 class="font-medium text-sm mb-2">Berikan Respons Anda</h3>
                        <p class="text-xs text-gray-500 mb-3">Jelaskan situasi dari sudut pandang Anda. Respons akan dibaca oleh admin dan customer.</p>
                        <form method="POST" action="{{ route('vendor.complaints.respond', $complaint->id) }}">
                            @csrf
                            <textarea name="message" rows="4" required minlength="20"
                                      placeholder="Jelaskan situasi secara detail (min. 20 karakter)..."
                                      class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 resize-none">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            <div class="flex justify-end mt-2">
                                <button type="submit"
                                        class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg text-sm font-semibold transition">
                                    Kirim Respons
                                </button>
                            </div>
                        </form>
                    </div>
                @elseif(!$complaint->isOpen())
                    <div class="mt-4 bg-gray-50 rounded-lg p-3">
                        <p class="text-gray-500 text-sm text-center">Komplain ini sudah ditutup.</p>
                    </div>
                @else
                    <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                        <p class="text-yellow-700 text-sm">⏳ Komplain ini belum diteruskan ke Anda oleh admin.</p>
                    </div>
                @endif
            </div>

        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">

            {{-- Info Customer --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="font-semibold mb-3 text-sm">Customer</h3>
                <p class="font-medium text-sm">{{ $complaint->reporter?->name }}</p>
                <p class="text-gray-500 text-xs mt-0.5">{{ $complaint->reporter?->email }}</p>
                <p class="text-gray-500 text-xs">{{ $complaint->reporter?->phone }}</p>
            </div>

            {{-- Batas Waktu --}}
            @if($complaint->vendor_due_at && $complaint->isOpen())
                <div class="rounded-xl border p-4
                    {{ $complaint->isOverdue() ? 'bg-red-50 border-red-300' : 'bg-yellow-50 border-yellow-200' }}">
                    <h3 class="font-semibold text-sm mb-1
                        {{ $complaint->isOverdue() ? 'text-red-800' : 'text-yellow-800' }}">
                        ⏰ Batas Waktu Respons
                    </h3>
                    <p class="text-sm {{ $complaint->isOverdue() ? 'text-red-700' : 'text-yellow-700' }}">
                        {{ $complaint->vendor_due_at->format('d M Y H:i') }}
                    </p>
                    @if($complaint->isOverdue())
                        <p class="text-xs text-red-600 font-semibold mt-1">⚠ Sudah melewati batas waktu!</p>
                    @else
                        <p class="text-xs text-yellow-600 mt-1">{{ $complaint->vendor_due_at->diffForHumans() }}</p>
                    @endif
                </div>
            @endif

            {{-- Log Aktivitas --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="font-semibold mb-4 text-sm">Log Aktivitas</h3>
                <div class="space-y-3">
                    @foreach($complaint->logs as $log)
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 rounded-full bg-blue-400 mt-1.5 flex-shrink-0"></div>
                            <div>
                                <p class="text-sm font-medium">{{ $log->actionLabel() }}</p>
                                <p class="text-xs text-gray-400">{{ $log->created_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
