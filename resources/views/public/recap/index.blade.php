@extends('layouts.app')

@section('title', 'Laporan Rekapitulasi Pemesanan - Rental Mobil')

@push('head')
<style>
    .recap-hero {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #4f46e5 100%) !important;
    }
    .recap-stat-card { transition: transform .2s, box-shadow .2s; }
    .recap-stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,0.12); }
    .recap-tr:hover { background: #f0f7ff; }
    .recap-progress { height: 6px; background: #e2e8f0; border-radius: 9999px; overflow: hidden; margin-top: 6px; }
    .recap-progress-fill { height: 100%; border-radius: 9999px; }
</style>
@endpush

@section('content')

{{-- ═══════════════════════════════════════════
     HERO BANNER
═══════════════════════════════════════════ --}}
<div class="recap-hero" style="padding: 40px 0; margin-bottom: 0; position: relative; overflow: hidden;">
    <div style="position:absolute; top:-80px; right:-80px; width:300px; height:300px; background:rgba(255,255,255,0.06); border-radius:50%;"></div>
    <div style="position:absolute; bottom:-60px; left:-60px; width:200px; height:200px; background:rgba(255,255,255,0.04); border-radius:50%;"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" style="position:relative; z-index:2;">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:24px;">

            {{-- Kiri --}}
            <div>
                <div style="font-size:13px; color:#bfdbfe; margin-bottom:10px; display:flex; align-items:center; gap:6px;">
                    <a href="{{ route('bookings.index') }}" style="color:#93c5fd; text-decoration:none; font-weight:600;">← Pesanan Saya</a>
                    <span style="color:#60a5fa;">/</span>
                    <span>Laporan Rekapitulasi</span>
                </div>
                <h1 style="font-size:32px; font-weight:900; color:#ffffff; letter-spacing:-0.5px; margin:0 0 8px 0; line-height:1.2;">
                    📊 Laporan Rekapitulasi Pemesanan
                </h1>
                <p style="font-size:14px; color:#bfdbfe; margin:0; max-width:520px; line-height:1.6;">
                    Ringkasan riwayat penyewaan mobil Anda — statistik, detail transaksi, dan ekspor data
                    untuk periode
                    <strong style="color:#fff;">{{ $from->format('d M Y') }}</strong> s/d
                    <strong style="color:#fff;">{{ $to->format('d M Y') }}</strong>
                </p>
            </div>

            {{-- Kanan: Tombol Export --}}
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a href="{{ route('customer.recap.pdf', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
                   target="_blank"
                   style="display:inline-flex; align-items:center; gap:8px; background:#ffffff; color:#dc2626; font-weight:800; font-size:14px; padding:12px 22px; border-radius:12px; text-decoration:none; box-shadow:0 4px 16px rgba(0,0,0,0.2); letter-spacing:0.2px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export PDF
                </a>
                <a href="{{ route('customer.recap.csv', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
                   style="display:inline-flex; align-items:center; gap:8px; background:#10b981; color:#ffffff; font-weight:800; font-size:14px; padding:12px 22px; border-radius:12px; text-decoration:none; box-shadow:0 4px 16px rgba(0,0,0,0.2); letter-spacing:0.2px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export CSV
                </a>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ═══════════════════════════════════════
         FILTER FORM
    ═══════════════════════════════════════ --}}
    <div style="background:#fff; border-radius:16px; border:1px solid #e2e8f0; padding:24px; margin-bottom:28px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
            <div style="width:4px; height:20px; background:#2563eb; border-radius:4px;"></div>
            <span style="font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:1px;">Filter Periode</span>
        </div>
        <form method="GET" action="{{ route('customer.recap.index') }}" style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:16px;">
            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.8px; margin-bottom:6px;">Dari Tanggal</label>
                <input type="date" name="from" value="{{ $from->toDateString() }}"
                       style="border:2px solid #e2e8f0; border-radius:10px; padding:9px 14px; font-size:14px; font-weight:600; color:#1e293b; outline:none; transition:border-color .2s;"
                       onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.8px; margin-bottom:6px;">Sampai Tanggal</label>
                <input type="date" name="to" value="{{ $to->toDateString() }}"
                       style="border:2px solid #e2e8f0; border-radius:10px; padding:9px 14px; font-size:14px; font-weight:600; color:#1e293b; outline:none; transition:border-color .2s;"
                       onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            {{-- Shortcut --}}
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                @php
                    $shortcuts = [
                        ['label' => 'Bulan Ini',  'from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()],
                        ['label' => 'Bulan Lalu', 'from' => now()->subMonth()->startOfMonth()->toDateString(), 'to' => now()->subMonth()->endOfMonth()->toDateString()],
                        ['label' => 'Tahun Ini',  'from' => now()->startOfYear()->toDateString(), 'to' => now()->endOfYear()->toDateString()],
                    ];
                @endphp
                @foreach($shortcuts as $s)
                    @php $isActive = $from->toDateString() === $s['from'] && $to->toDateString() === $s['to']; @endphp
                    <a href="{{ route('customer.recap.index', ['from' => $s['from'], 'to' => $s['to']]) }}"
                       style="display:inline-block; font-size:12px; font-weight:700; padding:9px 14px; border-radius:10px; text-decoration:none; border:2px solid;
                              {{ $isActive ? 'background:#2563eb; color:#fff; border-color:#2563eb;' : 'background:#f8fafc; color:#475569; border-color:#e2e8f0;' }}">
                        {{ $s['label'] }}
                    </a>
                @endforeach
            </div>

            <div style="display:flex; gap:8px; margin-left:auto;">
                <button type="submit"
                        style="background:#2563eb; color:#fff; font-size:14px; font-weight:700; padding:10px 22px; border-radius:10px; border:none; cursor:pointer; letter-spacing:.3px;">
                    🔍 Terapkan
                </button>
                <a href="{{ route('customer.recap.index') }}"
                   style="background:#f1f5f9; color:#64748b; font-size:14px; font-weight:700; padding:10px 18px; border-radius:10px; text-decoration:none; display:inline-block;">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════════════════════
         STATS CARDS ROW 1
    ═══════════════════════════════════════ --}}
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:16px;">

        {{-- Total Booking --}}
        <div class="recap-stat-card" style="background:#fff; border-radius:16px; border:1px solid #e2e8f0; padding:22px 20px; box-shadow:0 2px 8px rgba(0,0,0,0.05); position:relative; overflow:hidden;">
            <div style="position:absolute;top:0;right:0;width:60px;height:60px;background:#eff6ff;border-radius:0 16px 0 40px;display:flex;align-items:center;justify-content:center;font-size:22px;">📦</div>
            <p style="font-size:32px; font-weight:900; color:#1e293b; margin:8px 0 4px; line-height:1;">{{ number_format($stats['total_bookings']) }}</p>
            <p style="font-size:13px; font-weight:700; color:#64748b; margin:0;">Total Pemesanan</p>
            <p style="font-size:12px; color:#3b82f6; margin-top:8px; font-weight:600;">● {{ $stats['ongoing'] }} masih aktif</p>
        </div>

        {{-- Selesai --}}
        <div class="recap-stat-card" style="background:#fff; border-radius:16px; border:1px solid #e2e8f0; padding:22px 20px; box-shadow:0 2px 8px rgba(0,0,0,0.05); position:relative; overflow:hidden;">
            <div style="position:absolute;top:0;right:0;width:60px;height:60px;background:#f0fdf4;border-radius:0 16px 0 40px;display:flex;align-items:center;justify-content:center;font-size:22px;">✅</div>
            <p style="font-size:32px; font-weight:900; color:#1e293b; margin:8px 0 4px; line-height:1;">{{ number_format($stats['completed']) }}</p>
            <p style="font-size:13px; font-weight:700; color:#64748b; margin:0;">Berhasil Selesai</p>
            @if($stats['total_bookings'] > 0)
                @php $pct = round(($stats['completed'] / $stats['total_bookings']) * 100); @endphp
                <div style="margin-top:10px;">
                    <div style="display:flex; justify-content:space-between; font-size:11px; color:#94a3b8; margin-bottom:4px;">
                        <span>Tingkat keberhasilan</span>
                        <strong style="color:#16a34a;">{{ $pct }}%</strong>
                    </div>
                    <div class="recap-progress"><div class="recap-progress-fill" style="width:{{ $pct }}%; background:#22c55e;"></div></div>
                </div>
            @endif
        </div>

        {{-- Total Pengeluaran --}}
        <div class="recap-stat-card" style="background:#fff; border-radius:16px; border:1px solid #e2e8f0; padding:22px 20px; box-shadow:0 2px 8px rgba(0,0,0,0.05); position:relative; overflow:hidden;">
            <div style="position:absolute;top:0;right:0;width:60px;height:60px;background:#faf5ff;border-radius:0 16px 0 40px;display:flex;align-items:center;justify-content:center;font-size:22px;">💰</div>
            <p style="font-size:22px; font-weight:900; color:#7c3aed; margin:8px 0 4px; line-height:1.2; word-break:break-all;">
                Rp {{ number_format($stats['total_spent'], 0, ',', '.') }}
            </p>
            <p style="font-size:13px; font-weight:700; color:#64748b; margin:0;">Total Pengeluaran</p>
            <p style="font-size:12px; color:#7c3aed; margin-top:8px; font-weight:600;">● Dari {{ $stats['completed'] }} transaksi selesai</p>
        </div>

        {{-- Rata-rata Durasi --}}
        <div class="recap-stat-card" style="background:#fff; border-radius:16px; border:1px solid #e2e8f0; padding:22px 20px; box-shadow:0 2px 8px rgba(0,0,0,0.05); position:relative; overflow:hidden;">
            <div style="position:absolute;top:0;right:0;width:60px;height:60px;background:#fff7ed;border-radius:0 16px 0 40px;display:flex;align-items:center;justify-content:center;font-size:22px;">⏱️</div>
            <p style="font-size:32px; font-weight:900; color:#1e293b; margin:8px 0 4px; line-height:1;">
                {{ number_format($stats['avg_duration'], 1) }}
                <span style="font-size:16px; font-weight:600; color:#94a3b8;">hari</span>
            </p>
            <p style="font-size:13px; font-weight:700; color:#64748b; margin:0;">Rata-rata Durasi Sewa</p>
            <p style="font-size:12px; font-weight:600; margin-top:8px; color:{{ $stats['late_count'] > 0 ? '#ef4444' : '#16a34a' }};">
                {{ $stats['late_count'] > 0 ? '⚠️ '.$stats['late_count'].'x keterlambatan' : '✅ Tidak ada keterlambatan' }}
            </p>
        </div>
    </div>

    {{-- ═══════════════════════════════════════
         STATS CARDS ROW 2
    ═══════════════════════════════════════ --}}
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:28px;">
        <div style="background:linear-gradient(135deg,#1d4ed8,#4338ca); border-radius:16px; padding:20px 24px; color:#fff; box-shadow:0 4px 16px rgba(37,99,235,0.3);">
            <p style="font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#bfdbfe; margin:0 0 6px;">Pesanan Aktif</p>
            <p style="font-size:36px; font-weight:900; margin:0 0 4px; line-height:1;">{{ $stats['ongoing'] }}</p>
            <p style="font-size:12px; color:#93c5fd; margin:0;">Dalam proses / dikonfirmasi</p>
        </div>
        <div style="background:linear-gradient(135deg,#dc2626,#e11d48); border-radius:16px; padding:20px 24px; color:#fff; box-shadow:0 4px 16px rgba(220,38,38,0.3);">
            <p style="font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#fecaca; margin:0 0 6px;">Dibatalkan</p>
            <p style="font-size:36px; font-weight:900; margin:0 0 4px; line-height:1;">{{ $stats['cancelled'] }}</p>
            <p style="font-size:12px; color:#fca5a5; margin:0;">Pesanan yang dibatalkan</p>
        </div>
        <div style="background:linear-gradient(135deg,#d97706,#ea580c); border-radius:16px; padding:20px 24px; color:#fff; box-shadow:0 4px 16px rgba(217,119,6,0.3);">
            <p style="font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#fed7aa; margin:0 0 6px;">Keterlambatan</p>
            <p style="font-size:36px; font-weight:900; margin:0 0 4px; line-height:1;">{{ $stats['late_count'] }}</p>
            <p style="font-size:12px; color:#fdba74; margin:0;">Total kasus keterlambatan</p>
        </div>
    </div>

    {{-- ═══════════════════════════════════════
         DETAIL TABLE
    ═══════════════════════════════════════ --}}
    <div style="background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.06); margin-bottom:24px;">

        {{-- Table Header --}}
        <div style="padding:20px 24px; border-bottom:1px solid #f1f5f9; background:linear-gradient(to right,#f8fafc,#fff); display:flex; align-items:center; justify-content:space-between;">
            <div>
                <h2 style="font-size:16px; font-weight:800; color:#0f172a; margin:0 0 4px; display:flex; align-items:center; gap:8px;">
                    🗃️ Rincian Riwayat Transaksi
                </h2>
                <p style="font-size:12px; color:#94a3b8; margin:0;">{{ $bookings->count() }} transaksi pada periode ini</p>
            </div>
            <div style="text-align:right;">
                <p style="font-size:11px; color:#94a3b8; margin:0 0 2px;">Total semua transaksi</p>
                <p style="font-size:18px; font-weight:900; color:#1d4ed8; margin:0;">Rp {{ number_format($bookings->sum('total'), 0, ',', '.') }}</p>
            </div>
        </div>

        @if($bookings->isEmpty())
            <div style="text-align:center; padding:60px 20px;">
                <div style="font-size:48px; margin-bottom:16px;">📄</div>
                <h3 style="font-size:18px; font-weight:700; color:#1e293b; margin:0 0 8px;">Tidak Ada Data</h3>
                <p style="font-size:14px; color:#94a3b8; max-width:360px; margin:0 auto 16px;">
                    Tidak ditemukan transaksi pada periode ini. Coba ubah filter tanggal.
                </p>
                <a href="{{ route('customer.recap.index') }}"
                   style="display:inline-block; font-size:13px; font-weight:700; color:#2563eb; text-decoration:none; background:#eff6ff; padding:9px 18px; border-radius:10px;">
                    Reset ke Bulan Ini
                </a>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:2px solid #e2e8f0;">
                            <th style="padding:12px 16px; text-align:left; font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.8px; white-space:nowrap;">#</th>
                            <th style="padding:12px 16px; text-align:left; font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.8px; white-space:nowrap;">Kode Booking</th>
                            <th style="padding:12px 16px; text-align:left; font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.8px; white-space:nowrap;">Mobil & Vendor</th>
                            <th style="padding:12px 16px; text-align:left; font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.8px; white-space:nowrap;">Periode Sewa</th>
                            <th style="padding:12px 16px; text-align:center; font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.8px; white-space:nowrap;">Status</th>
                            <th style="padding:12px 16px; text-align:center; font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.8px; white-space:nowrap;">Ket.</th>
                            <th style="padding:12px 16px; text-align:right; font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.8px; white-space:nowrap;">Total Biaya</th>
                            <th style="padding:12px 16px; text-align:center; font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.8px; white-space:nowrap;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $i => $booking)
                            @php
                                [$bgBadge, $txtBadge] = match($booking->status) {
                                    'completed' => ['#dcfce7', '#15803d'],
                                    'cancelled' => ['#fee2e2', '#b91c1c'],
                                    'confirmed' => ['#dbeafe', '#1d4ed8'],
                                    'ongoing'   => ['#e0e7ff', '#3730a3'],
                                    default     => ['#fef3c7', '#92400e'],
                                };
                                $label = match($booking->status) {
                                    'awaiting_payment' => '⏳ Menunggu Bayar',
                                    'awaiting_vendor'  => '🕐 Menunggu Vendor',
                                    'confirmed'        => '✔️ Dikonfirmasi',
                                    'ongoing'          => '🚗 Berlangsung',
                                    'completed'        => '✅ Selesai',
                                    'cancelled'        => '❌ Dibatalkan',
                                    default            => $booking->status,
                                };
                                $duration = $booking->start_at && $booking->end_at
                                    ? max(1, $booking->start_at->diffInDays($booking->end_at))
                                    : null;
                                $rowBg = $i % 2 === 0 ? '#fff' : '#fafcff';
                            @endphp
                            <tr class="recap-tr" style="border-bottom:1px solid #f1f5f9; background:{{ $rowBg }};">
                                <td style="padding:14px 16px; font-weight:700; color:#cbd5e1; font-size:11px;">{{ $i + 1 }}</td>
                                <td style="padding:14px 16px;">
                                    <code style="font-family:monospace; font-weight:800; color:#2563eb; font-size:12px; background:#eff6ff; padding:3px 8px; border-radius:6px;">{{ $booking->code }}</code>
                                    <div style="font-size:11px; color:#94a3b8; margin-top:4px;">{{ $booking->created_at->format('d M Y, H:i') }}</div>
                                </td>
                                <td style="padding:14px 16px;">
                                    <div style="font-size:14px; font-weight:700; color:#0f172a;">{{ $booking->car->brand ?? '' }} {{ $booking->car->model ?? '' }}</div>
                                    <div style="font-size:11px; color:#64748b; margin-top:2px;">🏢 {{ $booking->vendor->business_name ?? '—' }}</div>
                                </td>
                                <td style="padding:14px 16px; font-size:12px; color:#374151;">
                                    <div><span style="color:#94a3b8; font-weight:600;">Mulai:</span> <strong>{{ $booking->start_at?->format('d M Y, H:i') ?? '—' }}</strong></div>
                                    <div style="margin-top:2px;"><span style="color:#94a3b8; font-weight:600;">Selesai:</span> <strong>{{ $booking->end_at?->format('d M Y, H:i') ?? '—' }}</strong></div>
                                    @if($duration)
                                        <span style="display:inline-block; margin-top:5px; font-size:11px; font-weight:700; background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:20px;">{{ $duration }} hari</span>
                                    @endif
                                </td>
                                <td style="padding:14px 16px; text-align:center;">
                                    <span style="display:inline-block; background:{{ $bgBadge }}; color:{{ $txtBadge }}; font-size:11px; font-weight:700; padding:4px 12px; border-radius:20px; white-space:nowrap;">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td style="padding:14px 16px; text-align:center;">
                                    @if($booking->is_late)
                                        <span style="display:inline-block; background:#fee2e2; color:#b91c1c; font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px; white-space:nowrap;">⚠️ Terlambat</span>
                                    @else
                                        <span style="color:#d1d5db; font-size:18px;">—</span>
                                    @endif
                                </td>
                                <td style="padding:14px 16px; text-align:right;">
                                    <span style="font-size:14px; font-weight:900; color:#1e293b;">Rp {{ number_format($booking->total ?? 0, 0, ',', '.') }}</span>
                                </td>
                                <td style="padding:14px 16px; text-align:center;">
                                    <a href="{{ route('bookings.show', $booking->code) }}"
                                       style="display:inline-flex; align-items:center; gap:4px; font-size:12px; font-weight:700; color:#2563eb; text-decoration:none; background:#eff6ff; padding:6px 14px; border-radius:8px; border:1px solid #bfdbfe; transition:all .15s;"
                                       onmouseover="this.style.background='#2563eb';this.style.color='#fff'" onmouseout="this.style.background='#eff6ff';this.style.color='#2563eb'">
                                        Detail →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background:linear-gradient(to right,#eff6ff,#f5f3ff); border-top:2px solid #bfdbfe;">
                            <td colspan="6" style="padding:14px 16px; text-align:right; font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:.5px;">
                                Total Pengeluaran (Transaksi Selesai)
                            </td>
                            <td style="padding:14px 16px; text-align:right;">
                                <span style="font-size:18px; font-weight:900; color:#1d4ed8;">Rp {{ number_format($stats['total_spent'], 0, ',', '.') }}</span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <div style="text-align:center; font-size:12px; color:#94a3b8; padding-bottom:8px;">
        Data diperbarui secara real-time · Periode: <strong style="color:#475569;">{{ $from->format('d M Y') }}</strong> s/d <strong style="color:#475569;">{{ $to->format('d M Y') }}</strong>
    </div>

</div>
@endsection
