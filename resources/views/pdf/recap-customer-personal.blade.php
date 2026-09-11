<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Pemesanan Saya</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1f2937; line-height: 1.4; }
        .header { background: #2563eb; color: white; padding: 20px 24px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .header p  { font-size: 11px; opacity: 0.9; }
        .period-badge { display: inline-block; background: rgba(255,255,255,0.2); padding: 3px 10px; border-radius: 12px; font-size: 10px; margin-top: 6px; }
        .stats-grid { display: table; width: 100%; margin-bottom: 20px; }
        .stats-col { display: table-cell; width: 25%; padding: 0 6px; }
        .stats-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; text-align: center; }
        .stats-card .val { font-size: 14px; font-weight: bold; color: #1e3a8a; }
        .stats-card .lbl { font-size: 9px; color: #64748b; margin-top: 2px; }
        .section-title { font-size: 12px; font-weight: bold; color: #1e3a8a; border-bottom: 2px solid #93c5fd; padding-bottom: 4px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #eff6ff; color: #1e40af; font-weight: bold; padding: 8px; text-align: left; font-size: 9px; border: 1px solid #bfdbfe; }
        td { padding: 7px 8px; border: 1px solid #e2e8f0; font-size: 9px; }
        tr:nth-child(even) td { background: #f8fafc; }
        .tfoot td { background: #dbeafe; font-weight: bold; color: #1e40af; border-top: 2px solid #93c5fd; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 8px; font-size: 8px; font-weight: bold; }
        .badge-green { background: #dcfce7; color: #15803d; }
        .badge-blue  { background: #dbeafe; color: #1e40af; }
        .badge-red   { background: #fee2e2; color: #b91c1c; }
        .badge-yellow{ background: #fef9c3; color: #a16207; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 24px; font-size: 9px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    </style>
</head>
<body>

<div class="header">
    <h1>📊 LAPORAN REKAPITULASI PEMESANAN</h1>
    <p>Nama Customer: <strong>{{ $customerName }}</strong></p>
    <span class="period-badge">Periode: {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</span>
</div>

<div style="padding: 0 16px;">

    {{-- Stats Cards --}}
    <div class="stats-grid">
        <div class="stats-col" style="padding-left:0;">
            <div class="stats-card">
                <div class="val">{{ number_format($stats['total_bookings']) }}</div>
                <div class="lbl">Total Pemesanan</div>
            </div>
        </div>
        <div class="stats-col">
            <div class="stats-card">
                <div class="val" style="color:#15803d;">{{ number_format($stats['completed']) }}</div>
                <div class="lbl">Selesai Berhasil</div>
            </div>
        </div>
        <div class="stats-col">
            <div class="stats-card">
                <div class="val" style="color:#2563eb;">Rp {{ number_format($stats['total_spent'], 0, ',', '.') }}</div>
                <div class="lbl">Total Pengeluaran</div>
            </div>
        </div>
        <div class="stats-col" style="padding-right:0;">
            <div class="stats-card">
                <div class="val" style="color:#d97706;">{{ number_format($stats['avg_duration'], 1) }} Hari</div>
                <div class="lbl">Rata-rata Durasi</div>
            </div>
        </div>
    </div>

    <p class="section-title">Detail Riwayat Transaksi</p>
    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Kode Booking</th>
                <th style="width: 12%;">Tanggal Pesan</th>
                <th style="width: 25%;">Mobil & Vendor</th>
                <th style="width: 20%;">Tanggal Sewa</th>
                <th class="text-center" style="width: 12%;">Status</th>
                <th class="text-right" style="width: 19%;">Total Biaya</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $b)
                <tr>
                    <td><strong>{{ $b->code }}</strong></td>
                    <td>{{ $b->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <strong>{{ $b->car->brand ?? '' }} {{ $b->car->model ?? '' }}</strong><br>
                        <span style="color:#64748b; font-size:8px;">{{ $b->vendor->business_name ?? '—' }}</span>
                    </td>
                    <td>
                        Mulai: {{ $b->start_at?->format('d/m/Y H:i') }}<br>
                        Selesai: {{ $b->end_at?->format('d/m/Y H:i') }}
                    </td>
                    <td class="text-center">
                        @php
                            $badgeClass = match($b->status) {
                                'completed' => 'badge-green',
                                'cancelled' => 'badge-red',
                                'confirmed', 'ongoing' => 'badge-blue',
                                default => 'badge-yellow',
                            };
                            $statusText = match($b->status) {
                                'awaiting_payment' => 'Menunggu Bayar',
                                'awaiting_vendor'  => 'Menunggu Vendor',
                                'confirmed'        => 'Dikonfirmasi',
                                'ongoing'          => 'Dalam Perjalanan',
                                'completed'        => 'Selesai',
                                'cancelled'        => 'Dibatalkan',
                                default            => $b->status,
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
                    </td>
                    <td class="text-right"><strong>Rp {{ number_format($b->total ?? 0, 0, ',', '.') }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px; color:#9ca3af;">Tidak ada transaksi pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="tfoot">
                <td colspan="5"><strong>TOTAL PENGELUARAN (TRANSAKSI SELESAI)</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($stats['total_spent'], 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="footer">
    Dokumen ini dicetak otomatis dari Sistem Rental Mobil Multi-Vendor pada {{ now()->format('d M Y, H:i') }} WIB.
</div>

</body>
</html>
