@extends('layouts.vendor')

@section('title', 'Paket & Komisi - Vendor')

@section('content')
<div id="top" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6">
        <a href="/vendor" class="text-sm text-blue-600 hover:underline">← Kembali ke Dashboard</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-3">Paket & Komisi</h1>
        <p class="text-gray-500 text-sm mt-1">Pilih paket yang sesuai dengan skala bisnis Anda.</p>
    </div>

    {{-- Current Plan Info --}}
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-6 text-white mb-8">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <p class="text-blue-200 text-sm mb-1">Paket Saat Ini</p>
                <h2 class="text-3xl font-bold">{{ $vendor->plan->label() }}</h2>
                <p class="text-blue-100 text-sm mt-2">Komisi: <span class="font-bold text-xl">{{ $vendor->plan->commissionRate() * 100 }}%</span> per transaksi</p>
                @if($vendor->plan !== \App\Enums\VendorPlan::Free)
                    <p class="text-blue-100 text-xs mt-1">Biaya: Rp {{ number_format($vendor->plan->monthlyFee(), 0, ',', '.') }}/bulan</p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-blue-200 text-sm">Mobil Terdaftar</p>
                <p class="text-4xl font-black">{{ $vendor->cars()->count() }}</p>
                @if($vendor->plan->maxCars())
                    <p class="text-blue-100 text-xs mt-1">dari {{ $vendor->plan->maxCars() }} maksimal</p>
                @else
                    <p class="text-blue-100 text-xs mt-1">Unlimited</p>
                @endif
            </div>
        </div>

        {{-- Masa Berlaku --}}
        @if($vendor->plan !== \App\Enums\VendorPlan::Free)
            @php
                $expiresAt   = $vendor->plan_expires_at;
                $daysLeft    = $expiresAt ? (int) now()->diffInDays($expiresAt, false) : null;
                $isExpired   = $daysLeft !== null && $daysLeft < 0;
                $isWarning   = $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 7;
            @endphp
            <div class="mt-4 pt-4 border-t border-blue-500">
                @if($expiresAt)
                    <div class="flex items-center gap-3 flex-wrap">
                        <div class="flex items-center gap-2">
                            @if($isExpired)
                                <span class="inline-flex items-center gap-1.5 bg-red-500 text-white text-xs font-bold px-3 py-1.5 rounded-full">
                                    ⚠️ Paket Expired
                                </span>
                            @elseif($isWarning)
                                <span class="inline-flex items-center gap-1.5 bg-yellow-400 text-yellow-900 text-xs font-bold px-3 py-1.5 rounded-full">
                                    ⏰ Segera Berakhir
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 bg-blue-500 text-white text-xs font-bold px-3 py-1.5 rounded-full">
                                    ✅ Aktif
                                </span>
                            @endif
                        </div>
                        <div>
                            <p class="text-blue-100 text-sm">
                                Berlaku hingga:
                                <span class="font-bold text-white">{{ $expiresAt->translatedFormat('d F Y') }}</span>
                            </p>
                            @if($isExpired)
                                <p class="text-red-300 text-xs mt-0.5">Paket sudah berakhir {{ abs($daysLeft) }} hari lalu. Segera lakukan pembayaran.</p>
                            @elseif($isWarning)
                                <p class="text-yellow-200 text-xs mt-0.5">Tersisa <span class="font-bold">{{ $daysLeft }} hari</span> lagi. Segera perpanjang.</p>
                            @else
                                <p class="text-blue-200 text-xs mt-0.5">Tersisa <span class="font-bold">{{ $daysLeft }} hari</span> lagi</p>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="text-blue-200 text-sm">Masa berlaku belum diatur. Hubungi admin.</p>
                @endif
            </div>
        @else
            <div class="mt-4 pt-4 border-t border-blue-500">
                <p class="text-blue-200 text-sm">✅ Paket Free berlaku <span class="font-bold text-white">selamanya</span> tanpa batas waktu.</p>
            </div>
        @endif
    </div>

    {{-- Plans Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        @foreach($plans as $plan)
            @php
                $isCurrent = $vendor->plan === $plan;
                $isUpgrade = $plan->commissionRate() < $vendor->plan->commissionRate();
                $isDowngrade = $plan->commissionRate() > $vendor->plan->commissionRate();
            @endphp
            
            <div class="bg-white rounded-2xl border-2 {{ $isCurrent ? 'border-blue-500 shadow-lg' : 'border-gray-200' }} overflow-hidden relative">
                
                @if($isCurrent)
                    <div class="absolute top-4 right-4 z-10">
                        <span class="bg-blue-500 text-white text-xs font-bold px-3 py-1 rounded-full">AKTIF</span>
                    </div>
                @endif

                @if($plan === \App\Enums\VendorPlan::Premium && !$isCurrent)
                    <div class="absolute top-4 right-4 z-10">
                        <span class="bg-yellow-400 text-yellow-900 text-xs font-bold px-3 py-1 rounded-full">TERPOPULER</span>
                    </div>
                @endif

                <div class="p-6">
                    <div class="mb-4">
                        <span class="inline-block {{ $plan->badge() }} text-xs font-bold px-3 py-1 rounded-full">
                            {{ $plan->label() }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <p class="text-4xl font-black text-gray-900">{{ $plan->commissionRate() * 100 }}%</p>
                        <p class="text-sm text-gray-500">komisi per transaksi</p>
                    </div>

                    <div class="mb-6">
                        @if($plan->monthlyFee() > 0)
                            <p class="text-2xl font-bold text-gray-900">Rp {{ number_format($plan->monthlyFee(), 0, ',', '.') }}</p>
                            <p class="text-sm text-gray-500">per bulan</p>
                        @else
                            <p class="text-2xl font-bold text-green-600">Gratis</p>
                            <p class="text-sm text-gray-500">selamanya</p>
                        @endif
                    </div>

                    <div class="space-y-3 mb-6">
                        @foreach($plan->features() as $feature)
                            <div class="flex items-start gap-2 text-sm text-gray-700">
                                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ $feature }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if($isCurrent)
                        <button disabled class="w-full bg-gray-100 text-gray-400 py-3 rounded-xl font-bold cursor-not-allowed">
                            Paket Aktif
                        </button>
                    @else
                        <form method="POST" action="{{ route('vendor.plans.upgrade') }}">
                            @csrf
                            <input type="hidden" name="plan" value="{{ $plan->value }}">
                            <button type="submit" 
                                    class="w-full {{ $isUpgrade ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-600 hover:bg-gray-700' }} text-white py-3 rounded-xl font-bold transition">
                                {{ $isUpgrade ? 'Upgrade Sekarang' : 'Pilih Paket Ini' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Simulasi Penghasilan --}}
    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl border border-green-200 p-8">
        <h3 class="text-xl font-bold text-gray-900 mb-4">💡 Simulasi Penghasilan (1 Mobil)</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-2">Asumsi:</p>
                <ul class="space-y-1 text-sm text-gray-700">
                    <li>• Harga sewa: Rp 350.000/hari</li>
                    <li>• Booking: 15× per bulan</li>
                    <li>• Durasi rata-rata: 2 hari</li>
                    <li>• Total transaksi: Rp 10.500.000</li>
                </ul>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-2">Pendapatan Bersih:</p>
                <div class="space-y-2">
                    @foreach($plans as $plan)
                        @php
                            $grossRevenue = 10500000;
                            $commission = $grossRevenue * $plan->commissionRate();
                            $monthlyFee = $plan->monthlyFee();
                            $netRevenue = $grossRevenue - $commission - $monthlyFee;
                        @endphp
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">{{ $plan->label() }}</span>
                            <span class="text-lg font-bold {{ $plan === \App\Enums\VendorPlan::Premium ? 'text-green-700' : 'text-gray-900' }}">
                                Rp {{ number_format($netRevenue, 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 mt-3">*Sudah dipotong komisi & biaya paket bulanan</p>
            </div>
        </div>
    </div>

    {{-- Riwayat Tagihan --}}
    @php $payments = $vendor->planPayments()->latest()->take(10)->get(); @endphp
    @if($payments->isNotEmpty())
    <div class="mt-8 bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900">Riwayat Tagihan Paket</h3>
            @if($vendor->hasPendingPlanPayment())
                <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-3 py-1 rounded-full">Ada tagihan belum dibayar</span>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3 text-left">Paket</th>
                        <th class="px-6 py-3 text-left">Periode</th>
                        <th class="px-6 py-3 text-right">Nominal</th>
                        <th class="px-6 py-3 text-left">Metode</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Dibayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($payments as $payment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $payment->plan->label() }}</td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $payment->period_start->format('d M Y') }} –
                                {{ $payment->period_end->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">
                                Rp {{ number_format($payment->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $payment->methodLabel() }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColor = match($payment->status) {
                                        'paid'    => 'bg-green-100 text-green-700',
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'failed'  => 'bg-red-100 text-red-700',
                                        'waived'  => 'bg-gray-100 text-gray-600',
                                        default   => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="inline-block {{ $statusColor }} text-xs font-semibold px-2.5 py-1 rounded-full">
                                    {{ $payment->statusLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $payment->paid_at ? $payment->paid_at->format('d M Y') : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- FAQ --}}
    <div class="mt-8 bg-white rounded-2xl border border-gray-200 p-8">
        <h3 class="text-xl font-bold text-gray-900 mb-6">Pertanyaan Umum</h3>
        <div class="space-y-4">
            <div>
                <p class="font-semibold text-gray-900 mb-1">Kapan komisi dipotong?</p>
                <p class="text-sm text-gray-600">Komisi dipotong otomatis dari setiap transaksi yang berhasil (status completed). Vendor menerima payout bersih setelah dipotong komisi.</p>
            </div>
            <div>
                <p class="font-semibold text-gray-900 mb-1">Bagaimana cara pembayaran biaya paket?</p>
                <p class="text-sm text-gray-600">Biaya paket Basic/Premium dibayarkan setiap bulan. Admin akan mengkonfirmasi pembayaran setelah transfer diterima, atau dapat dipotong otomatis dari payout mingguan Anda.</p>
            </div>
            <div>
                <p class="font-semibold text-gray-900 mb-1">Apa yang terjadi jika tagihan belum dibayar?</p>
                <p class="text-sm text-gray-600">Ada grace period 7 hari setelah paket expired. Jika belum dibayar, paket otomatis turun ke Free dan komisi kembali ke 12%.</p>
            </div>
            <div>
                <p class="font-semibold text-gray-900 mb-1">Apakah bisa downgrade paket?</p>
                <p class="text-sm text-gray-600">Ya, bisa kapan saja. Namun pastikan jumlah mobil Anda tidak melebihi batas paket yang dipilih.</p>
            </div>
            <div>
                <p class="font-semibold text-gray-900 mb-1">Apakah komisi berlaku untuk booking yang sudah ada?</p>
                <p class="text-sm text-gray-600">Tidak. Perubahan komisi hanya berlaku untuk booking baru setelah upgrade/downgrade paket.</p>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Scroll ke atas saat halaman load setelah redirect
    window.scrollTo({ top: 0, behavior: 'smooth' });
</script>
@endpush
