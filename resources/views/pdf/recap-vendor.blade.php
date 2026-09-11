<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Vendor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1f2937; }
        .header { background: #1d4ed8; color: white; padding: 20px 24px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; font-weight: bold; }
        .header p  { font-size: 11px; opacity: 0.85; margin-top: 4px; }
        .period-badge { display: inline-block; background: rgba(255,255,255,0.2); padding: 3px 10px; border-radius: 12px; font-size: 10px; margin-top: 6px; }
        .section-title { font-size: 13px; font-weight: bold; color: #1d4ed8; border-bottom: 2px solid #bfdbfe; padding-bottom: 6px; margin: 18px 0 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #eff6ff; color: #1e40af; font-weight: bold; padding: 8px 10px; text-align: left; font-size: 10px; border: 1px solid #bfdbfe; }
        td { padding: 7px 10px; border: 1px solid #e5e7eb; font-size: 10px; }
        tr:nth-child(even) td { background: #f8fafc; }
        .tfoot td { background: #dbeafe; font-weight: bold; color: #1d4ed8; border-top: 2px solid #93c5fd; }
        .badge-green { background: #dcfce7; color: #15803d; padding: 2px 7px; border-radius: 10px; }
        .badge-red   { background: #fee2e2; color: #b91c1c; padding: 2px 7px; border-radius: 10px; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 24px; font-size: 9px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    </style>
</head>
<body>
<div class="header">
    <h1>📊 Laporan Rekapitulasi Vendor</h1>
    <p>Sistem Rental Mobil Multi-Vendor</p>
    <span class="period-badge">Periode: {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</span>
</div>

<div style="padding: 0 16px;">
    <p class="section-title">Data Per Vendor</p>
    <table>
        <thead>
            <tr>
                <th>Nama Vendor</th>
                <th class="text-center">Total Mobil</th>
                <th class="text-center">Total Booking</th>
                <th class="text-center">Selesai</th>
                <th class="text-right">Pendapatan</th>
                <th class="text-center">Keterlambatan</th>
                <th class="text-center">Rating</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
            <tr>
                <td><strong>{{ $row['name'] }}</strong></td>
                <td class="text-center">{{ $row['total_cars'] }}</td>
                <td class="text-center">{{ $row['total_bookings'] }}</td>
                <td class="text-center"><span class="badge-green">{{ $row['completed'] }}</span></td>
                <td class="text-right">Rp {{ number_format($row['total_revenue'], 0, ',', '.') }}</td>
                <td class="text-center">
                    @if($row['late_count'] > 0)
                        <span class="badge-red">{{ $row['late_count'] }}</span>
                    @else —
                    @endif
                </td>
                <td class="text-center">{{ $row['avg_rating'] > 0 ? '⭐ '.$row['avg_rating'] : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="tfoot">
                <td><strong>TOTAL</strong></td>
                <td class="text-center">{{ collect($data)->sum('total_cars') }}</td>
                <td class="text-center">{{ collect($data)->sum('total_bookings') }}</td>
                <td class="text-center">{{ collect($data)->sum('completed') }}</td>
                <td class="text-right">Rp {{ number_format(collect($data)->sum('total_revenue'), 0, ',', '.') }}</td>
                <td class="text-center">{{ collect($data)->sum('late_count') }}</td>
                <td class="text-center">—</td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="footer">
    Dicetak oleh Sistem pada {{ now()->format('d M Y H:i') }} • Rental Mobil Multi-Vendor
</div>
</body>
</html>
