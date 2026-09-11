<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Transaksi Vendor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1f2937; }
        .header { background: #ea580c; color: white; padding: 18px 24px; margin-bottom: 18px; }
        .header h1 { font-size: 16px; font-weight: bold; }
        .header p  { font-size: 10px; opacity: 0.85; margin-top: 3px; }
        .period-badge { display: inline-block; background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 10px; font-size: 9px; margin-top: 5px; }
        .stats-grid { display: table; width: 100%; margin-bottom: 16px; }
        .stat-box { display: table-cell; width: 25%; background: white; border: 1px solid #e5e7eb; padding: 10px; text-align: center; }
        .stat-box .val { font-size: 16px; font-weight: bold; color: #1f2937; }
        .stat-box .lbl { font-size: 9px; color: #6b7280; margin-top: 2px; }
        .section-title { font-size: 12px; font-weight: bold; color: #ea580c; border-bottom: 2px solid #fed7aa; padding-bottom: 5px; margin: 14px 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #fff7ed; color: #c2410c; font-weight: bold; padding: 7px 8px; text-align: left; font-size: 9px; border: 1px solid #fed7aa; }
        td { padding: 6px 8px; border: 1px solid #e5e7eb; font-size: 9px; }
        tr:nth-child(even) td { background: #fffbf7; }
        .badge-green  { background: #dcfce7; color: #15803d; padding: 1px 5px; border-radius: 8px; }
        .badge-red    { background: #fee2e2; color: #b91c1c; padding: 1px 5px; border-radius: 8px; }
        .badge-blue   { background: #dbeafe; color: #1d4ed8; padding: 1px 5px; border-radius: 8px; }
        .badge-gray   { background: #f3f4f6; color: #4b5563; padding: 1px 5px; border-radius: 8px; }
        .badge-orange { background: #fed7aa; color: #c2410c; padding: 1px 5px; border-radius: 8px; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 20px; font-size: 8px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
<div class="header">
    <h1>📋 Rekap Transaksi Vendor — {{ $vendor?->business_name }}</h1>
    <p>Sistem Rental Mobil Multi-Vendor</p>
    <span class="period-badge">Periode: {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</span>
</div>

<div style="padding: 0 12px;">
    {{-- Stats --}}
    <div class="stats-grid">
        <div class="stat-box">
            <div class="val">{{ $stats['total_bookings'] }}</div>
            <div class="lbl">Total Booking</div>
        </div>
        <div class="stat-box">
            <div class="val" style="color:#16a34a;">{{ $stats['completed'] }}</div>
            <div class="lbl">Selesai</div>
        </div>
        <div class="stat-box">
            <div class="val" style="color:#2563eb; font-size:13px;">Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}</div>
            <div class="lbl">Total Payout</div>
        </div>
        <div class="stat-box">
            <div class="val" style="color:#dc2626;">{{ $stats['late_count'] }}</div>
            <div class="lbl">Keterlambatan</div>
        </div>
    </div>

    {{-- Tabel Keterlambatan (jika ada) --}}
    @if(isset($lateBookings) && $lateBookings->isNotEmpty())
    <p class="section-title" style="color:#dc2626; border-color:#fecaca;">⚠️ Daftar Keterlambatan</p>
    <table>
        <thead>
            <tr style="background:#fff1f1;">
                <th style="color:#9f1239;">Kode</th>
                <th style="color:#9f1239;">Customer</th>
                <th style="color:#9f1239;">Mobil</th>
                <th class="text-center" style="color:#9f1239;">Jatuh Tempo</th>
                <th class="text-center" style="color:#9f1239;">Dikembalikan</th>
                <th class="text-center" style="color:#9f1239;">Terlambat</th>
                <th class="text-right" style="color:#9f1239;">Denda</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lateBookings as $b)
            <tr>
                <td style="font-family:monospace;">{{ $b->code }}</td>
                <td>{{ $b->customer?->full_name }}</td>
                <td>{{ $b->car?->brand }} {{ $b->car?->model }}</td>
                <td class="text-center">{{ $b->end_at?->format('d/m/Y H:i') }}</td>
                <td class="text-center" style="color:#dc2626;font-weight:bold;">{{ $b->actual_return_at?->format('d/m/Y H:i') ?? '—' }}</td>
                <td class="text-center"><span class="badge-red">{{ $b->late_duration_hours }} jam</span></td>
                <td class="text-right" style="color:#dc2626;font-weight:bold;">Rp {{ number_format($b->late_fee, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background:#fff1f1; border-top:2px solid #fecaca;">
                <td colspan="5" style="font-weight:bold; color:#9f1239;">Total Denda</td>
                <td class="text-center" style="font-weight:bold; color:#dc2626;">{{ $lateBookings->sum('late_duration_hours') }} jam</td>
                <td class="text-right" style="font-weight:bold; color:#dc2626;">Rp {{ number_format($lateBookings->sum('late_fee'), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    {{-- Tabel Transaksi --}}
    <p class="section-title">Daftar Transaksi</p>
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Customer</th>
                <th>Mobil</th>
                <th class="text-center">Mulai</th>
                <th class="text-center">Selesai</th>
                <th class="text-center">Status</th>
                <th class="text-right">Total</th>
                <th class="text-right">Payout</th>
                <th class="text-center">Terlambat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $b)
            @php
                $badgeClass = match($b->status) {
                    'completed'       => 'badge-green',
                    'cancelled'       => 'badge-red',
                    'ongoing'         => 'badge-orange',
                    'confirmed'       => 'badge-blue',
                    default           => 'badge-gray',
                };
                $statusLabel = match($b->status) {
                    'awaiting_payment' => 'Menunggu Bayar',
                    'awaiting_vendor'  => 'Menunggu Konfirmasi',
                    'confirmed'        => 'Dikonfirmasi',
                    'ongoing'          => 'Berlangsung',
                    'completed'        => 'Selesai',
                    'cancelled'        => 'Dibatalkan',
                    default            => ucfirst($b->status),
                };
            @endphp
            <tr>
                <td style="font-family:monospace;">{{ $b->code }}</td>
                <td>{{ $b->customer?->full_name }}</td>
                <td>{{ $b->car?->brand }} {{ $b->car?->model }}</td>
                <td class="text-center">{{ $b->start_at?->format('d/m/Y') }}</td>
                <td class="text-center">{{ $b->end_at?->format('d/m/Y') }}</td>
                <td class="text-center"><span class="{{ $badgeClass }}">{{ $statusLabel }}</span></td>
                <td class="text-right">Rp {{ number_format($b->total, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($b->vendor_payout_amount, 0, ',', '.') }}</td>
                <td class="text-center">
                    @if($b->is_late)
                        <span class="badge-red">{{ $b->late_duration_hours }}j</span>
                    @else —
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center" style="padding:16px; color:#9ca3af;">Tidak ada transaksi.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="footer">
    Dicetak oleh Sistem pada {{ now()->format('d M Y H:i') }} • Rental Mobil Multi-Vendor
</div>
</body>
</html>
