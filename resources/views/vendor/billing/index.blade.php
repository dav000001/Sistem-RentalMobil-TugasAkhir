@extends('layouts.vendor')
@section('title', 'Billing & Paket — Vendor')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6">
        <a href="/vendor" class="text-sm text-blue-600 hover:underline">← Kembali ke Dashboard</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-3">Billing & Paket Berlangganan</h1>
        <p class="text-gray-500 text-sm mt-1">Kelola paket berlangganan dan riwayat pembayaran Anda.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">✅ {{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-sm">ℹ️ {{ session('info') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
        </div>
    @endif

    {{-- ── SECTION 1: Status Saat Ini ──────────────────────────────── --}}
    @if($currentSub)
    @php
        $daysLeft   = $currentSub->daysRemaining();
        $progress   = $currentSub->progressPercent();
        $isExpired  = $currentSub->isExpiredLocked();
        $isGrace    = $currentSub->isInGracePeriod();
        $isPending  = $currentSub->isPendingPayment();
    @endphp

    @if($isExpired)
        <div class="mb-6 bg-red-600 text-white rounded-2xl p-5">
            <p class="font-bold text-lg">⚠️ Paket Anda Dikunci</p>
            <p class="text-red-100 text-sm mt-1">Mobil Anda tidak tampil di pencarian. Pilih paket baru untuk melanjutkan operasi.</p>
        </div>
    @elseif($isGrace)
        <div class="mb-6 bg-yellow-500 text-white rounded-2xl p-5">
            <p class="font-bold text-lg">⏰ Grace Period — Segera Perpanjang</p>
            <p class="text-yellow-100 text-sm mt-1">Paket berakhir. Anda masih bisa beroperasi hingga {{ $currentSub->grace_until?->format('d M Y') }}.</p>
        </div>
    @elseif($isPending)
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-2xl p-5">
            <p class="font-bold text-blue-900">💳 Pembayaran Tertunda</p>
            <p class="text-blue-700 text-sm mt-1">Paket {{ $currentSub->package->name }} menunggu pembayaran.
                <a href="{{ route('vendor.billing.payment', $currentSub->uuid) }}" class="underline font-bold">Bayar sekarang →</a>
            </p>
        </div>
    @endif

    <div class="bg-white border-2 border-gray-200 rounded-2xl p-6 mb-8 shadow-sm">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-widest">Paket Aktif Anda</p>
                <h2 class="text-4xl font-black mt-1 text-gray-900">{{ $currentSub->package->name }}</h2>
                <div class="flex items-center gap-3 mt-3 flex-wrap">
                    {{-- Komisi --}}
                    <span class="flex items-center gap-1 bg-blue-100 border border-blue-200 rounded-full px-3 py-1 text-sm font-bold text-blue-800">
                        💸 Komisi: {{ $currentSub->package->commission_rate }}% per transaksi
                    </span>
                    {{-- Biaya bulanan --}}
                    @if($currentSub->package->price_per_month > 0)
                        <span class="flex items-center gap-1 bg-purple-100 border border-purple-200 rounded-full px-3 py-1 text-sm font-bold text-purple-800">
                            💳 Rp {{ number_format($currentSub->package->price_per_month, 0, ',', '.') }} / bulan
                        </span>
                    @else
                        <span class="flex items-center gap-1 bg-green-100 border border-green-200 rounded-full px-3 py-1 text-sm font-bold text-green-800">
                            🎉 Gratis Selamanya
                        </span>
                    @endif
                    {{-- Status badge --}}
                    <span @class([
                        'text-xs font-bold px-3 py-1 rounded-full',
                        'bg-green-100 text-green-800 border border-green-200' => $currentSub->isActive(),
                        'bg-yellow-100 text-yellow-800 border border-yellow-200' => $isGrace,
                        'bg-red-100 text-red-800 border border-red-200' => $isExpired,
                        'bg-blue-100 text-blue-800 border border-blue-200' => $isPending,
                    ])>{{ $currentSub->statusLabel() }}</span>
                </div>
            </div>
            <div class="text-right">
                @if($currentSub->expires_at)
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-widest">Berlaku Sampai</p>
                    <p class="text-2xl font-black mt-1 text-gray-900">{{ $currentSub->expires_at->format('d M Y') }}</p>
                    @if($daysLeft > 0)
                        <p class="text-sm mt-1 font-bold {{ $daysLeft <= 7 ? 'text-red-600' : 'text-gray-600' }}">
                            ⏳ Sisa {{ $daysLeft }} hari lagi
                        </p>
                    @else
                        <p class="text-sm mt-1 text-red-600 font-bold">⚠️ Sudah berakhir</p>
                    @endif
                @else
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-widest">Masa Berlaku</p>
                    <p class="text-2xl font-black mt-1 text-gray-900">Selamanya ♾️</p>
                    <p class="text-gray-400 text-xs mt-1">Tidak ada tanggal kadaluarsa</p>
                @endif
            </div>
        </div>

        @if($currentSub->expires_at)
        <div class="mt-5">
            <div class="flex justify-between text-xs font-semibold text-gray-500 mb-1.5">
                <span>Mulai: {{ $currentSub->started_at?->format('d M Y') }}</span>
                <span class="text-gray-700">{{ $progress }}% masa berlaku terpakai</span>
                <span>Berakhir: {{ $currentSub->expires_at->format('d M Y') }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="rounded-full h-3 transition-all {{ $progress >= 80 ? 'bg-red-500' : 'bg-blue-500' }}"
                     style="width: {{ $progress }}%"></div>
            </div>
        </div>
        @endif


    </div>
    @endif

    {{-- Pending upgrade requests --}}
    @if($pendingRequests->isNotEmpty())
    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-xl p-4">
        <p class="font-semibold text-yellow-900 text-sm mb-2">⏳ Permintaan Perubahan Paket Menunggu Review</p>
        @foreach($pendingRequests as $req)
            <p class="text-yellow-800 text-xs">• {{ $req->typeLabel() }} ke <strong>{{ $req->targetPackage->name }}</strong> — menunggu admin</p>
        @endforeach
    </div>
    @endif

    {{-- ── SECTION 2: Daftar Paket ──────────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8 items-stretch">
        @foreach($packages as $pkg)
            @php
                $check     = $packageChecks[$pkg->id];
                $isCurrent = $currentSub && $currentSub->package_id === $pkg->id && $currentSub->isActive();
                $allowed   = $check['allowed'];
                $mode      = $check['mode'];
                $reason    = $check['reason'];
                $proration = $check['proration_amount'];
            @endphp

            <div @class([
                'bg-white rounded-2xl border-2 overflow-hidden relative flex flex-col',
                'border-blue-500 shadow-lg ring-2 ring-blue-200' => $isCurrent,
                'border-gray-200' => !$isCurrent,
            ])>
                @if($isCurrent)
                    {{-- Banner atas paket aktif --}}
                    <div class="bg-blue-500 text-white text-center text-xs font-bold py-1.5 tracking-wide">
                        ✓ PAKET ANDA SAAT INI
                    </div>
                @endif
                @if($pkg->code === 'premium' && !$isCurrent)
                    <div class="absolute top-3 right-3">
                        <span class="bg-yellow-400 text-yellow-900 text-xs font-bold px-2.5 py-1 rounded-full">TERPOPULER</span>
                    </div>
                @endif

                {{-- Konten atas — flex-1 agar mengisi ruang --}}
                <div class="p-5 flex flex-col flex-1">
                    <span @class([
                        'inline-block text-xs font-bold px-3 py-1 rounded-full mb-3 self-start',
                        'bg-gray-100 text-gray-700' => $pkg->code === 'free',
                        'bg-blue-100 text-blue-700' => $pkg->code === 'basic',
                        'bg-gradient-to-r from-yellow-400 to-orange-400 text-white' => $pkg->code === 'premium',
                    ])>{{ $pkg->name }}</span>

                    @if($isCurrent)
                        <div class="flex items-center gap-1.5 mb-3">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-full">
                                ✅ Aktif
                            </span>
                            <span class="text-xs text-gray-400">Komisi Anda saat ini</span>
                        </div>
                    @endif

                    <p class="text-4xl font-black text-gray-900">{{ $pkg->commission_rate }}%</p>
                    <p class="text-sm text-gray-500 mb-3">komisi per transaksi</p>

                    @if($pkg->price_per_month > 0)
                        <p class="text-xl font-bold text-gray-900">Rp {{ number_format($pkg->price_per_month, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 mb-4">per bulan</p>
                    @else
                        <p class="text-xl font-bold text-green-600 mb-4">Gratis selamanya</p>
                    @endif

                    {{-- Fitur — min-h agar semua card sama tinggi --}}
                    <div class="space-y-1.5 mb-5 flex-1" style="min-height: 96px">
                        @php $features = $pkg->features ?? []; @endphp
                        @php $maxCars = $features['max_cars'] ?? 0; @endphp
                        @php $maxPhotos = $features['max_photos'] ?? 0; @endphp
                        <div class="flex items-center gap-2 text-xs text-gray-700">
                            <svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $maxCars === -1 ? 'Unlimited mobil' : "Maks {$maxCars} mobil" }}
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-700">
                            <svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $maxPhotos === -1 ? 'Foto unlimited' : "Maks {$maxPhotos} foto/mobil" }}
                        </div>
                        @if($features['priority_search'] ?? false)
                        <div class="flex items-center gap-2 text-xs text-gray-700">
                            <svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Prioritas di pencarian
                        </div>
                        @endif
                        @if($features['badge_verified'] ?? false)
                        <div class="flex items-center gap-2 text-xs text-gray-700">
                            <svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Badge Vendor Terverifikasi
                        </div>
                        @endif
                    </div>

                    {{-- Tombol per kondisi — mt-auto mendorong ke bawah --}}
                    <div class="mt-auto pt-3">
                    @if($isCurrent)
                        <div class="w-full text-center py-2.5 rounded-xl text-sm font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            Paket Aktif Anda
                        </div>
                    @elseif(!$allowed)
                        <div class="relative group">
                            <button disabled class="w-full bg-gray-100 text-gray-400 py-2.5 rounded-xl text-sm font-bold cursor-not-allowed">
                                🔒 Terkunci
                            </button>
                            @if($reason)
                            <div class="absolute bottom-full left-0 right-0 mb-2 bg-gray-900 text-white text-xs rounded-lg p-2 hidden group-hover:block z-10">
                                {{ $reason }}
                            </div>
                            @endif
                        </div>
                        @if($reason)
                        <p class="text-xs text-red-500 mt-1.5 text-center">{{ $reason }}</p>
                        @endif
                    @elseif($mode === 'upgrade' && $proration !== null)
                        <form method="POST" action="{{ route('vendor.billing.purchase') }}">
                            @csrf
                            <input type="hidden" name="package_id" value="{{ $pkg->id }}">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-xl text-sm font-bold transition">
                                ⬆️ Upgrade — Bayar Rp {{ number_format($proration, 0, ',', '.') }}
                            </button>
                        </form>
                        <p class="text-xs text-gray-400 mt-1 text-center">Sudah dipotong sisa paket lama</p>
                    @elseif($mode === 'renewal')
                        <form method="POST" action="{{ route('vendor.billing.purchase') }}">
                            @csrf
                            <input type="hidden" name="package_id" value="{{ $pkg->id }}">
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2.5 rounded-xl text-sm font-bold transition">
                                🔄 Pilih untuk Periode Berikutnya
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('vendor.billing.purchase') }}">
                            @csrf
                            <input type="hidden" name="package_id" value="{{ $pkg->id }}">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-xl text-sm font-bold transition">
                                Pilih Paket Ini
                            </button>
                        </form>
                    @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── SECTION 3: Riwayat Subscription ─────────────────────────── --}}
    @if($subscriptions->isNotEmpty())
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden mb-8">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-900">Riwayat Paket</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-5 py-3 text-left">Paket</th>
                        <th class="px-5 py-3 text-left">Mulai</th>
                        <th class="px-5 py-3 text-left">Berakhir</th>
                        <th class="px-5 py-3 text-right">Dibayar</th>
                        <th class="px-5 py-3 text-left">Metode</th>
                        <th class="px-5 py-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($subscriptions as $sub)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $sub->package->name }}</td>
                            <td class="px-5 py-3 text-gray-500 text-xs">{{ $sub->started_at?->format('d M Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-500 text-xs">{{ $sub->expires_at?->format('d M Y') ?? 'Selamanya' }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900">
                                {{ $sub->amount_paid > 0 ? 'Rp ' . number_format($sub->amount_paid, 0, ',', '.') : 'Gratis' }}
                            </td>
                            <td class="px-5 py-3 text-gray-500 text-xs">{{ ucfirst(str_replace('_', ' ', $sub->payment_method ?? '—')) }}</td>
                            <td class="px-5 py-3">
                                @php $cls = match($sub->status) { 'active' => 'bg-green-100 text-green-700', 'grace_period' => 'bg-yellow-100 text-yellow-700', 'expired_locked' => 'bg-red-100 text-red-700', 'pending_payment' => 'bg-blue-100 text-blue-700', default => 'bg-gray-100 text-gray-600' }; @endphp
                                <span class="inline-block {{ $cls }} text-xs font-semibold px-2.5 py-1 rounded-full">{{ $sub->statusLabel() }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ── SECTION 4: Request Manual ke Admin ──────────────────────── --}}
    @if($currentSub && $currentSub->isActive() && $currentSub->expires_at && $currentSub->daysRemaining() > 7)
    <div class="bg-gray-50 rounded-2xl border border-gray-200 p-6" x-data="{ open: false }">
        <button @click="open = !open" class="text-sm text-gray-500 hover:text-gray-700 underline">
            Butuh ganti paket sebelum masa aktif habis? Ajukan permintaan ke admin →
        </button>

        <div x-show="open" x-transition class="mt-4">
            <form method="POST" action="{{ route('vendor.billing.upgrade-request') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Paket Tujuan</label>
                        <select name="target_package_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Pilih paket</option>
                            @foreach($packages as $pkg)
                                @if(!($currentSub && $currentSub->package_id === $pkg->id))
                                    <option value="{{ $pkg->id }}">{{ $pkg->name }} ({{ $pkg->commission_rate }}%)</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Permintaan</label>
                        <select name="type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="downgrade_early">Downgrade Lebih Awal</option>
                            <option value="cancel_early">Batalkan Paket</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alasan (min. 50 karakter)</label>
                    <textarea name="reason" required minlength="50" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                              placeholder="Jelaskan alasan Anda ingin mengganti paket sebelum masa aktif berakhir..."></textarea>
                </div>
                <button type="submit" class="bg-gray-700 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-gray-800 transition">
                    Kirim Permintaan
                </button>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>window.scrollTo({ top: 0, behavior: 'smooth' });</script>
@endpush
