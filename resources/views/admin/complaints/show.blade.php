@extends('layouts.admin')

@section('title', 'Komplain ' . $complaint->reference . ' - Admin')

@section('content')
<div>
    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('admin.complaints.index') }}" class="text-blue-600 hover:underline text-sm">← Daftar Komplain</a>
        <div class="flex flex-wrap items-center gap-3 mt-2">
            <h1 class="text-2xl font-bold">{{ $complaint->reference }}</h1>
            @php $color = $complaint->statusColor(); @endphp
            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-{{ $color }}-100 text-{{ $color }}-700">
                {{ $complaint->statusLabel() }}
            </span>
            @php $sc = $complaint->severityColor(); @endphp
            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-{{ $sc }}-100 text-{{ $sc }}-700">
                {{ $complaint->severityLabel() }}
            </span>
            @if($complaint->isOverdue())
                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-700">⚠ Overdue</span>
            @endif
            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-700">👤 Komplain Customer</span>
        </div>
        <p class="text-gray-500 text-sm mt-1">
            Diajukan {{ $complaint->created_at->format('d M Y H:i') }} · Kategori: {{ $complaint->category?->name }}
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

            {{-- Detail Komplain --}}
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="font-semibold text-lg mb-3">Detail Laporan</h2>
                <p class="text-gray-700 text-sm leading-relaxed">{{ $complaint->description }}</p>

                {{-- Bukti --}}
                @if($complaint->attachments && count($complaint->attachments) > 0)
                    <div class="mt-4 pt-4 border-t">
                        <p class="text-sm font-medium text-gray-600 mb-2">📎 Bukti yang Dilampirkan</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach($complaint->attachments as $path)
                                @php
                                    $url   = asset('storage/' . ltrim($path, '/'));
                                    $ext   = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                    $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                                @endphp
                                @if($isImg)
                                    <a href="{{ $url }}" target="_blank">
                                        <img src="{{ $url }}" alt="Bukti"
                                             class="h-28 w-auto rounded-lg border border-gray-200 object-cover hover:opacity-80 transition cursor-pointer">
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

                {{-- Permintaan Customer --}}
                <div class="mt-4 pt-4 border-t grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Permintaan Customer</p>
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
                            <p class="text-gray-500">Catatan Customer</p>
                            <p class="font-medium">{{ $complaint->customer_demand_note }}</p>
                        </div>
                    @endif
                    @if($complaint->admin_notes)
                        <div class="col-span-2">
                            <p class="text-gray-500">Catatan Admin</p>
                            <p class="font-medium text-red-700">{{ $complaint->admin_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Customer & Vendor --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg shadow-sm border p-5">
                    <h3 class="font-semibold mb-3">👤 Customer</h3>
                    <p class="font-medium text-sm">{{ $complaint->reporter?->name }}</p>
                    <p class="text-gray-500 text-xs">{{ $complaint->reporter?->email }}</p>
                    <p class="text-gray-500 text-xs">{{ $complaint->reporter?->phone }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm border p-5">
                    <h3 class="font-semibold mb-3">🏪 Vendor</h3>
                    <p class="font-medium text-sm">{{ $complaint->vendor?->business_name }}</p>
                    <p class="text-gray-500 text-xs">{{ $complaint->vendor?->user?->email }}</p>
                    <p class="text-gray-500 text-xs">{{ $complaint->vendor?->user?->phone }}</p>
                </div>
            </div>

            {{-- Pesanan --}}
            @if($complaint->booking)
                <div class="bg-white rounded-lg shadow-sm border p-5">
                    <h3 class="font-semibold mb-3">Pesanan Terkait</h3>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-gray-500 text-xs">Kode</p>
                            <p class="font-medium">{{ $complaint->booking->code }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Mobil</p>
                            <p class="font-medium">{{ $complaint->booking->car?->brand }} {{ $complaint->booking->car?->model }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Periode</p>
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

            {{-- Keputusan --}}
            @if($complaint->resolution)
                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <h2 class="font-semibold text-lg mb-3 text-green-800">✅ Keputusan</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Keputusan</span>
                            <span class="font-semibold">{{ $complaint->resolution->decisionLabel() }}</span>
                        </div>
                        @if($complaint->resolution->refund_amount)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Refund</span>
                                <span class="font-semibold">Rp {{ number_format($complaint->resolution->refund_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if($complaint->resolution->voucher_code)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Kode Voucher</span>
                                <span class="font-mono font-semibold">{{ $complaint->resolution->voucher_code }}</span>
                            </div>
                        @endif
                        <div class="pt-2 border-t">
                            <p class="text-gray-600 mb-1">Alasan</p>
                            <p>{{ $complaint->resolution->reasoning }}</p>
                        </div>
                        <p class="text-gray-400 text-xs">
                            Diputuskan oleh {{ $complaint->resolution->admin?->name }}
                            · {{ $complaint->resolution->created_at->format('d M Y H:i') }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- Riwayat Komunikasi --}}
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="font-semibold text-lg mb-4">Riwayat Komunikasi</h2>
                @if($complaint->responses->isEmpty())
                    <p class="text-gray-500 text-sm">Belum ada respons.</p>
                @else
                    <div class="space-y-4">
                        @foreach($complaint->responses as $response)
                            <div class="border rounded-lg p-4 {{ $response->visibility === 'internal_admin' ? 'bg-purple-50 border-purple-200' : 'bg-gray-50' }}">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold
                                            @if($response->author_role === 'admin') bg-purple-100 text-purple-700
                                            @elseif($response->author_role === 'vendor') bg-blue-100 text-blue-700
                                            @else bg-green-100 text-green-700 @endif">
                                            {{ $response->authorRoleLabel() }}
                                        </span>
                                        <span class="text-sm font-medium">{{ $response->author?->name }}</span>
                                        @if($response->visibility === 'internal_admin')
                                            <span class="text-xs text-purple-600 font-semibold">🔒 Internal</span>
                                        @endif
                                    </div>
                                    <span class="text-xs text-gray-400">{{ $response->created_at->format('d M Y H:i') }}</span>
                                </div>
                                <p class="text-sm text-gray-700">{{ $response->message }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($complaint->isOpen())
                    <div class="mt-6 pt-6 border-t">
                        <h3 class="font-medium text-sm mb-1">Tambah Respons Admin</h3>
                        <p class="text-xs text-gray-500 mb-3">Respons publik akan terlihat oleh customer dan vendor.</p>
                        <form method="POST" action="{{ route('admin.complaints.respond', $complaint->id) }}">
                            @csrf
                            <textarea name="message" rows="3" required minlength="5"
                                      placeholder="Tulis respons admin..."
                                      class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                            <div class="flex items-center justify-between mt-2">
                                <label class="flex items-center gap-2 text-sm text-gray-600">
                                    <input type="checkbox" name="visibility" value="internal_admin" class="rounded">
                                    <span>Internal (hanya admin)</span>
                                </label>
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

        {{-- Sidebar Aksi --}}
        <div class="space-y-4">
            @if($complaint->isOpen())

                {{-- Teruskan ke Vendor --}}
                @if(in_array($complaint->status, ['submitted', 'under_admin_review']))
                    <div class="bg-white rounded-lg shadow-sm border p-5">
                        <h3 class="font-semibold mb-3">Teruskan ke Vendor</h3>
                        <p class="text-gray-500 text-xs mb-3">Minta vendor merespons komplain ini.</p>
                        <form method="POST" action="{{ route('admin.complaints.forward', $complaint->id) }}">
                            @csrf
                            <button type="submit"
                                    class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 text-sm font-medium"
                                    onclick="return confirm('Teruskan komplain ini ke vendor?')">
                                📤 Teruskan ke Vendor
                            </button>
                        </form>
                    </div>
                @endif

                {{-- Selesaikan --}}
                <div class="bg-white rounded-lg shadow-sm border p-5">
                    <h3 class="font-semibold mb-3">Selesaikan Komplain</h3>

                    @if($complaint->booking)
                        @php $bookingTotal = $complaint->booking->payment?->amount ?? $complaint->booking->total ?? 0; @endphp
                        <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 mb-3 text-xs text-blue-700">
                            💰 Total: <strong>Rp {{ number_format($bookingTotal, 0, ',', '.') }}</strong>
                            · 30% = <strong>Rp {{ number_format($bookingTotal * 0.30, 0, ',', '.') }}</strong>
                            · 50% = <strong>Rp {{ number_format($bookingTotal * 0.50, 0, ',', '.') }}</strong>
                        </div>
                    @endif

                    <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 mb-3 text-xs text-gray-600">
                        Customer meminta:
                        <strong>
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
                        </strong>
                        @if($complaint->demanded_refund_amount)
                            — Rp {{ number_format($complaint->demanded_refund_amount, 0, ',', '.') }}
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.complaints.resolve', $complaint->id) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Keputusan *</label>
                            <select name="decision" id="decision-select" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Pilih keputusan...</option>
                                <option value="refund_full">Refund Penuh</option>
                                <option value="refund_partial">Refund Sebagian</option>
                                <option value="discount_voucher">Voucher Diskon</option>
                                <option value="warning_vendor">Peringatan Vendor</option>
                                <option value="suspend_vendor">Pembekuan Vendor</option>
                                <option value="mutual_agreement">Kesepakatan Bersama</option>
                                <option value="escalated_legal">Eskalasi Legal</option>
                                <option value="escalated_insurance">Eskalasi Asuransi</option>
                            </select>
                        </div>
                        <div id="refund-field" class="hidden">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah Refund (Rp) *</label>
                            <input type="number" name="refund_amount" id="refund-amount-input" min="1"
                                   placeholder="Masukkan jumlah..."
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p id="refund-hint" class="text-xs text-gray-400 mt-1"></p>
                        </div>
                        <div id="voucher-field" class="hidden">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nilai Voucher (Rp)</label>
                            <input type="number" name="voucher_amount" min="1"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div id="suspend-field" class="hidden">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Durasi Pembekuan (hari)</label>
                            <input type="number" name="vendor_suspend_days" min="1" max="365"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Alasan Keputusan *</label>
                            <textarea name="reasoning" rows="3" required minlength="20"
                                      placeholder="Jelaskan alasan keputusan..."
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                        </div>
                        <button type="submit"
                                class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 text-sm font-medium">
                            ✅ Selesaikan
                        </button>
                    </form>
                </div>

                {{-- Tolak --}}
                <div class="bg-white rounded-lg shadow-sm border p-5">
                    <h3 class="font-semibold mb-3 text-red-700">Tolak Komplain</h3>
                    <form method="POST" action="{{ route('admin.complaints.reject', $complaint->id) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Alasan Penolakan *</label>
                            <textarea name="reason" rows="3" required minlength="10"
                                      placeholder="Jelaskan alasan penolakan..."
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                        </div>
                        <button type="submit"
                                class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 text-sm font-medium"
                                onclick="return confirm('Yakin ingin menolak komplain ini?')">
                            ✕ Tolak Komplain
                        </button>
                    </form>
                </div>
            @endif

            {{-- Log Aktivitas --}}
            <div class="bg-white rounded-lg shadow-sm border p-5">
                <h3 class="font-semibold mb-4">Log Aktivitas</h3>
                <div class="space-y-3">
                    @foreach($complaint->logs as $log)
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 rounded-full bg-blue-500 mt-1.5 flex-shrink-0"></div>
                            <div>
                                <p class="text-sm font-medium">{{ $log->actionLabel() }}</p>
                                <p class="text-xs text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</p>
                                @if($log->actor)
                                    <p class="text-xs text-gray-400">{{ $log->actor->name }} ({{ $log->actor_role }})</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const bookingTotal   = {{ $complaint->booking?->payment?->amount ?? $complaint->booking?->total ?? 0 }};
    const demandedAmount = {{ $complaint->demanded_refund_amount ?? 0 }};
    const decisionSelect = document.getElementById('decision-select');
    const refundInput    = document.getElementById('refund-amount-input');
    const refundHint     = document.getElementById('refund-hint');

    if (decisionSelect) {
        decisionSelect.addEventListener('change', function () {
            const val = this.value;
            document.getElementById('refund-field').classList.toggle('hidden', !['refund_full', 'refund_partial'].includes(val));
            document.getElementById('voucher-field').classList.toggle('hidden', val !== 'discount_voucher');
            document.getElementById('suspend-field').classList.toggle('hidden', val !== 'suspend_vendor');

            if (val === 'refund_full' && refundInput) {
                refundInput.value = bookingTotal;
                if (refundHint) refundHint.textContent = 'Otomatis: 100% dari total pembayaran';
            } else if (val === 'refund_partial' && refundInput) {
                const suggested = demandedAmount > 0 ? demandedAmount : Math.round(bookingTotal * 0.30);
                refundInput.value = suggested;
                if (refundHint) refundHint.textContent = demandedAmount > 0
                    ? 'Dari permintaan customer. Bisa diubah.'
                    : 'Default 30%. Bisa diubah sesuai keputusan.';
            }
        });
    }
</script>
@endpush
@endsection
