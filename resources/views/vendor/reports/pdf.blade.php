<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan - {{ $vendor->business_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; background: #fff; }
        .header { background: #1d4ed8; color: white; padding: 20px 24px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; font-weight: bold; }
        .header p { font-size: 11px; opacity: 0.85; margin-top: 4px; }
        .section { padding: 0 24px; margin-bottom: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
        .stat-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; text-align: center; }
        .stat-card .label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-card .value { font-size: 14px; font-weight: bold; color: #1d4ed8; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #f3f4f6; padding: 8px 6px; text-align: left; font-weight: 600; color: #374151; border-bottom: 2px solid #d1d5db; }
        th.right, td.right { text-align: right; }
        td { padding: 7px 6px; border-bottom: 1px solid #f3f4f6; color: #374151; }
        tr:nth-child(even) td { background: #f9fafb; }
        tfoot td { font-weight: bold; border-top: 2px solid #d1d5db; background: #f3f4f6; }
        .section-title { font-size: 13px; font-weight: bold; color: #111827; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid #e5e7eb; }
        .footer { margin-top: 30px; padding: 12px 24px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 600; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>

<div class="header">
    <h1>📊 Laporan Keuangan Vendor</h1>
    <p>{{ $vendor->business_name }} &nbsp;|&nbsp; Periode: {{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</p>
    <p>Dicetak: {{ now()->format('d M Y H:i') }}</p>
</div>

<div class="section">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Total Booking</div>
            <div class="value">{{ $bookingCount }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Total Pendapatan</div>
            <div class="value" style="font-size:11px;">{{ formatRupiah($totalRevenue) }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Komisi Platform</div>
            <div class="value" style="font-size:11px; color:#d97706;">{{ formatRupiah($totalCommission) }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Payout Anda</div>
            <div class="value" style="font-size:11px; color:#059669;">{{ formatRupiah($totalPayout) }}</div>
        </div>
    </div>
</div>

@if($perCar->count() > 0)
<div class="section">
    <div class="section-title">🚗 Performa Per Mobil</div>
    <table>
        <thead>
            <tr>
                <th>Mobil</th>
                <th class="right">Booking</th>
                <th class="right">Total Pendapatan</th>
                <th class="right">Payout Anda</th>
            </tr>
        </thead>
        <tbody>
            @foreach($perCar as $car)
            <tr>
                <td>{{ $car->car_name }}</td>
                <td class="right">{{ $car->total_bookings }}</td>
                <td class="right">{{ formatRupiah($car->total_revenue) }}</td>
                <td class="right" style="color:#059669; font-weight:600;">{{ formatRupiah($car->total_payout) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="right">{{ $bookingCount }}</td>
                <td class="right">{{ formatRupiah($totalRevenue) }}</td>
                <td class="right" style="color:#059669;">{{ formatRupiah($totalPayout) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

<div class="section">
    <div class="section-title">📋 Riwayat Transaksi</div>
    <table>
        <thead>
            <tr>
                <th>Kode Booking</th>
                <th>Tgl Bayar</th>
                <th>Customer</th>
                <th>Mobil</th>
                <th>Durasi</th>
                <th class="right">Total</th>
                <th class="right">Payout</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentBookings as $b)
            @php $days = max(1, $b->start_at->diffInDays($b->end_at)); @endphp
            <tr>
                <td style="font-family: monospace; font-size:9px;">{{ $b->code }}</td>
                <td>{{ $b->payment?->paid_at?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $b->customer?->full_name ?? '-' }}</td>
                <td>{{ $b->car->brand }} {{ $b->car->model }}</td>
                <td>{{ $days }}h</td>
                <td class="right">{{ formatRupiah($b->total) }}</td>
                <td class="right" style="color:#059669; font-weight:600;">{{ formatRupiah($b->vendor_payout_amount) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="footer">
    Laporan ini dibuat otomatis oleh sistem Rental Mobil &nbsp;|&nbsp; {{ now()->format('d M Y H:i') }}
</div>

</body>
</html>
