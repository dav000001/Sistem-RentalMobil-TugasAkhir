<x-filament-widgets::widget>
    <x-filament::section heading="💳 Tagihan Vendor yang Belum Dibayar">

        @if($vendors->isEmpty())
            <p class="text-sm text-gray-500 text-center py-4">Semua vendor sudah dibayar. Tidak ada tagihan yang tertunggak.</p>
        @else
            @foreach($vendors as $v)
            <x-filament::section class="mb-4">
                {{-- Header --}}
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px;">
                    <div>
                        <p style="font-weight:700; font-size:15px; color:#111827;">{{ $v['nama'] }}</p>
                        <p style="font-size:12px; color:#6b7280; margin-top:2px;">🏦 {{ $v['bank'] }}</p>
                    </div>
                    <div style="text-align:right;">
                        <span style="font-size:11px; font-weight:700; padding:3px 10px; border-radius:999px;
                            background:{{ $v['paket']==='PREMIUM' ? '#fef3c7' : ($v['paket']==='BASIC' ? '#dbeafe' : '#f3f4f6') }};
                            color:{{ $v['paket']==='PREMIUM' ? '#92400e' : ($v['paket']==='BASIC' ? '#1e40af' : '#374151') }};">
                            {{ $v['paket'] }}
                        </span>
                        <p style="font-size:11px; color:#6b7280; margin-top:4px;">Komisi {{ $v['komisi_pct'] }}</p>
                    </div>
                </div>

                {{-- Stats --}}
                <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px;">
                    <div style="background:#f9fafb; border-radius:8px; padding:12px; text-align:center;">
                        <p style="font-size:11px; color:#6b7280; margin-bottom:4px;">Booking</p>
                        <p style="font-weight:700; font-size:18px; color:#111827;">{{ $v['jumlah_booking'] }}</p>
                    </div>
                    <div style="background:#f9fafb; border-radius:8px; padding:12px; text-align:center;">
                        <p style="font-size:11px; color:#6b7280; margin-bottom:4px;">Uang Masuk</p>
                        <p style="font-weight:700; color:#111827;">Rp {{ number_format($v['total_masuk'], 0, ',', '.') }}</p>
                    </div>
                    <div style="background:#eff6ff; border-radius:8px; padding:12px; text-align:center;">
                        <p style="font-size:11px; color:#2563eb; margin-bottom:4px;">Komisi Platform</p>
                        <p style="font-weight:700; color:#1d4ed8;">Rp {{ number_format($v['komisi'], 0, ',', '.') }}</p>
                    </div>
                    <div style="background:#f0fdf4; border:2px solid #bbf7d0; border-radius:8px; padding:12px; text-align:center;">
                        <p style="font-size:11px; color:#16a34a; font-weight:600; margin-bottom:4px;">Belum Dibayar</p>
                        <p style="font-weight:900; font-size:16px; color:#15803d;">Rp {{ number_format($v['sisa'], 0, ',', '.') }}</p>
                    </div>
                </div>

                {{-- Footer --}}
                <div style="display:flex; justify-content:space-between; align-items:center; padding-top:12px; border-top:1px solid #e5e7eb;">
                    <div>
                        @if($v['sudah_dibayar'] > 0)
                            <span style="font-size:12px; font-weight:700; background:#dcfce7; color:#166534; padding:4px 12px; border-radius:999px;">
                                ✅ Lunas — Rp {{ number_format($v['sudah_dibayar'], 0, ',', '.') }}
                            </span>
                        @elseif($v['pending'] > 0)
                            <span style="font-size:12px; font-weight:700; background:#fef9c3; color:#854d0e; padding:4px 12px; border-radius:999px;">
                                ⏳ Pending — Rp {{ number_format($v['pending'], 0, ',', '.') }}
                            </span>
                        @else
                            <span style="font-size:12px; font-weight:700; background:#fee2e2; color:#991b1b; padding:4px 12px; border-radius:999px;">
                                ❌ Belum Dibuatkan Payout
                            </span>
                        @endif
                    </div>
                    <div>
                        @if($v['sudah_dibayar'] == 0 && $v['pending'] == 0)
                            <a href="/admin/payouts/create"
                               style="font-size:13px; font-weight:700; background:#2563eb; color:#fff; padding:8px 16px; border-radius:8px; text-decoration:none;">
                                + Buat Payout
                            </a>
                        @elseif($v['pending'] > 0)
                            <a href="/admin/payouts"
                               style="font-size:13px; font-weight:700; background:#d97706; color:#fff; padding:8px 16px; border-radius:8px; text-decoration:none;">
                                ✅ Konfirmasi Transfer
                            </a>
                        @else
                            <span style="font-size:12px; color:#9ca3af;">Selesai</span>
                        @endif
                    </div>
                </div>
            </x-filament::section>
            @endforeach

            {{-- Total --}}
            <div style="background:#f9fafb; border:2px solid #d1d5db; border-radius:12px; padding:16px;">
                <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:12px; text-align:center;">
                    <div>
                        <p style="font-size:11px; color:#6b7280; margin-bottom:4px;">Total Vendor</p>
                        <p style="font-weight:700; font-size:18px; color:#111827;">{{ $vendors->count() }}</p>
                    </div>
                    <div>
                        <p style="font-size:11px; color:#6b7280; margin-bottom:4px;">Total Uang Masuk</p>
                        <p style="font-weight:700; color:#111827;">Rp {{ number_format($vendors->sum('total_masuk'), 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p style="font-size:11px; color:#2563eb; margin-bottom:4px;">Total Komisi</p>
                        <p style="font-weight:700; color:#1d4ed8;">Rp {{ number_format($vendors->sum('komisi'), 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p style="font-size:11px; color:#16a34a; font-weight:600; margin-bottom:4px;">Total Belum Dibayar</p>
                        <p style="font-weight:900; font-size:16px; color:#15803d;">Rp {{ number_format($vendors->sum('sisa'), 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>
