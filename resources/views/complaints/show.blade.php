@extends('layouts.app')

@section('title', 'Komplain ' . $complaint->reference)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('complaints.index') }}" class="text-blue-600 hover:underline text-sm">← Komplain Saya</a>
        <div class="flex flex-wrap items-center gap-3 mt-2">
            <h1 class="text-2xl font-bold">{{ $complaint->reference }}</h1>
            @php $color = $complaint->statusColor(); @endphp
            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-{{ $color }}-100 text-{{ $color }}-700">
                {{ $complaint->statusLabel() }}
            </span>
            @php $sColor = $complaint->severityColor(); @endphp
            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-{{ $sColor }}-100 text-{{ $sColor }}-700">
                Tingkat: {{ $complaint->severityLabel() }}
            </span>
        </div>
        <p class="text-gray-500 text-sm mt-1">Diajukan {{ $complaint->created_at->diffForHumans() }} · Kategori: {{ $complaint->category?->name }}</p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Complaint Detail --}}
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="font-semibold text-lg mb-3">Detail Laporan</h2>
                <div class="prose prose-sm max-w-none text-gray-700">
                    <p>{{ $complaint->description }}</p>
                </div>

                {{-- Bukti yang dilampirkan --}}
                @if($complaint->attachments && count($complaint->attachments) > 0)
                    <div class="mt-4 pt-4 border-t">
                        <p class="text-sm font-medium text-gray-600 mb-2">📎 Bukti yang Dilampirkan</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach($complaint->attachments as $path)
                                @php
                                    $url = asset('storage/' . ltrim($path, '/'));
                                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                    $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                                @endphp
                                @if($isImg)
                                    <a href="{{ $url }}" target="_blank" class="block">
                                        <img src="{{ $url }}" alt="Bukti"
                                             class="h-24 w-auto rounded-lg border border-gray-200 object-cover hover:opacity-80 transition cursor-pointer">
                                    </a>
                                @else
                                    <a href="{{ $url }}" target="_blank"
                                       class="flex items-center gap-1.5 px-3 py-2 bg-gray-100 rounded-lg text-sm text-blue-600 hover:bg-gray-200 transition">
                                        📄 {{ basename($path) }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-4 pt-4 border-t grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Permintaan</p>
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
                            <p class="text-gray-500">Jumlah Refund Diminta</p>
                            <p class="font-medium">Rp {{ number_format($complaint->demanded_refund_amount, 0, ',', '.') }}</p>
                        </div>
                    @endif
                    @if($complaint->customer_demand_note)
                        <div class="col-span-2">
                            <p class="text-gray-500">Catatan</p>
                            <p class="font-medium">{{ $complaint->customer_demand_note }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Booking Info --}}
            @if($complaint->booking)
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="font-semibold text-lg mb-3">Pesanan Terkait</h2>
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                            <img src="{{ $complaint->booking->car?->photos?->first()?->path ?? 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=200&q=80' }}"
                                 alt="Car" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <p class="font-semibold">{{ $complaint->booking->car?->brand }} {{ $complaint->booking->car?->model }}</p>
                            <p class="text-gray-500 text-sm">Kode: {{ $complaint->booking->code }}</p>
                            <p class="text-gray-500 text-sm">
                                {{ $complaint->booking->start_at?->format('d M Y') }} – {{ $complaint->booking->end_at?->format('d M Y') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Resolution --}}
            @if($complaint->resolution)
                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <h2 class="font-semibold text-lg mb-3 text-green-800">✅ Keputusan Admin</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Keputusan</span>
                            <span class="font-semibold">{{ $complaint->resolution->decisionLabel() }}</span>
                        </div>
                        @if($complaint->resolution->refund_amount)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Jumlah Refund</span>
                                <span class="font-semibold">Rp {{ number_format($complaint->resolution->refund_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if($complaint->resolution->voucher_code)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Kode Voucher</span>
                                <span class="font-mono font-semibold text-blue-700">{{ $complaint->resolution->voucher_code }}</span>
                            </div>
                        @endif
                        <div class="pt-2 border-t">
                            <p class="text-gray-600 mb-1">Alasan Keputusan</p>
                            <p class="text-gray-800">{{ $complaint->resolution->reasoning }}</p>
                        </div>
                    </div>

                    {{-- Status Refund --}}
                    @php $complaintRefund = $complaint->booking?->refund; @endphp
                    @if(in_array($complaint->resolution->decision, ['refund_full', 'refund_partial']) && $complaintRefund)
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
                                                         class="max-h-40 rounded-lg border border-blue-200 hover:opacity-90 transition cursor-pointer">
                                                    <p class="text-xs text-blue-500 mt-1">🔍 Klik untuk lihat ukuran penuh</p>
                                                </a>
                                            </div>
                                        @endif
                                    @else
                                        <p class="text-xs text-yellow-700 mt-2">Admin sedang memproses transfer ke rekening Anda. Biasanya 1–3 hari kerja.</p>
                                        @if(!$complaintRefund->bank_account_no)
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
                    @endif
                </div>
            @endif

            {{-- Timeline / Responses --}}
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="font-semibold text-lg mb-4">Riwayat Komunikasi</h2>

                @if($complaint->responses->isEmpty())
                    <p class="text-gray-500 text-sm">Belum ada respons.</p>
                @else
                    <div class="space-y-4">
                        @foreach($complaint->responses as $response)
                            @if($response->visibility === 'public')
                                <div class="flex space-x-3 {{ $response->author_id === auth()->id() ? 'flex-row-reverse space-x-reverse' : '' }}">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0
                                        @if($response->author_role === 'admin') bg-purple-600
                                        @elseif($response->author_role === 'vendor') bg-blue-600
                                        @else bg-green-600
                                        @endif">
                                        {{ strtoupper(substr($response->author?->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div class="flex-1 max-w-sm">
                                        <div class="flex items-center space-x-2 mb-1 {{ $response->author_id === auth()->id() ? 'justify-end' : '' }}">
                                            <span class="text-xs font-semibold text-gray-700">{{ $response->author?->name }}</span>
                                            <span class="text-xs text-gray-400">({{ $response->authorRoleLabel() }})</span>
                                            <span class="text-xs text-gray-400">{{ $response->created_at->diffForHumans() }}</span>
                                        </div>
                                        <div class="rounded-lg p-3 text-sm
                                            @if($response->author_id === auth()->id()) bg-blue-600 text-white
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $response->message }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- Add Response Form --}}
                @if($complaint->isOpen())
                    <div class="mt-6 pt-6 border-t">
                        <h3 class="font-medium text-sm mb-3">Tambah Respons</h3>
                        <form method="POST" action="{{ route('complaints.respond', $complaint->id) }}">
                            @csrf
                            <textarea name="message" rows="3" required minlength="10"
                                      placeholder="Tulis respons Anda..."
                                      class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                            @error('message')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            <div class="flex justify-end mt-2">
                                <button type="submit"
                                        class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium">
                                    Kirim Respons
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">
            {{-- Status Timeline --}}
            <div class="bg-white rounded-lg shadow-sm border p-5">
                <h3 class="font-semibold mb-4">Riwayat Status</h3>
                <div class="space-y-3">
                    @foreach($complaint->logs as $log)
                        <div class="flex items-start space-x-3">
                            <div class="w-2 h-2 rounded-full bg-blue-500 mt-1.5 flex-shrink-0"></div>
                            <div>
                                <p class="text-sm font-medium">{{ $log->actionLabel() }}</p>
                                <p class="text-xs text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</p>
                                @if($log->actor)
                                    <p class="text-xs text-gray-400">oleh {{ $log->actor->name }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- SLA Info --}}
            @if($complaint->vendor_due_at && $complaint->isOpen())
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="font-semibold text-yellow-800 text-sm mb-1">Batas Waktu Vendor</h3>
                    <p class="text-yellow-700 text-sm">{{ $complaint->vendor_due_at->format('d M Y H:i') }}</p>
                    @if($complaint->isOverdue())
                        <p class="text-red-600 text-xs mt-1 font-semibold">⚠ Vendor melewati batas waktu</p>
                    @else
                        <p class="text-yellow-600 text-xs mt-1">{{ $complaint->vendor_due_at->diffForHumans() }}</p>
                    @endif
                </div>
            @endif

            {{-- Vendor Info --}}
            @if($complaint->vendor)
                <div class="bg-white rounded-lg shadow-sm border p-5">
                    <h3 class="font-semibold mb-3">Vendor</h3>
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                            {{ substr($complaint->vendor->business_name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-medium text-sm">{{ $complaint->vendor->business_name }}</p>
                            <p class="text-gray-500 text-xs">{{ $complaint->vendor->city?->name }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
