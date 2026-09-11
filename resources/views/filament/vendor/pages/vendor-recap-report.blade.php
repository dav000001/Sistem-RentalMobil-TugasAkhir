<x-filament-panels::page>

@php $stats = $this->getStats(); @endphp

{{-- ══════════════════════════════════════════════════════
     FILTER BAR
══════════════════════════════════════════════════════ --}}
<x-filament::section>
    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">

        <div style="display:flex; flex-direction:column; gap:4px;">
            <label style="font-size:12px; font-weight:600; color:#6b7280;">Periode</label>
            <select wire:model.live="period" wire:change="applyPeriod"
                style="border:1px solid #d1d5db; border-radius:8px; padding:7px 12px; font-size:13px; min-width:140px; background:#fff; color:#111827;">
                <option value="daily">📅 Harian</option>
                <option value="monthly">📆 Bulanan</option>
                <option value="yearly">🗓️ Tahunan</option>
                <option value="custom">✏️ Kustom</option>
            </select>
        </div>

        <div style="display:flex; flex-direction:column; gap:4px;">
            <label style="font-size:12px; font-weight:600; color:#6b7280;">Dari Tanggal</label>
            <input type="date" wire:model.live="date_from"
                style="border:1px solid #d1d5db; border-radius:8px; padding:7px 12px; font-size:13px; background:#fff; color:#111827;">
        </div>

        <div style="display:flex; flex-direction:column; gap:4px;">
            <label style="font-size:12px; font-weight:600; color:#6b7280;">Sampai Tanggal</label>
            <input type="date" wire:model.live="date_to"
                style="border:1px solid #d1d5db; border-radius:8px; padding:7px 12px; font-size:13px; background:#fff; color:#111827;">
        </div>

        <div style="display:flex; gap:8px; flex-wrap:wrap; padding-bottom:2px;">
            <a href="{{ route('vendor.recap.export-pdf', ['from' => $date_from, 'to' => $date_to]) }}"
               target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;background:#dc2626;color:#fff;font-weight:600;font-size:13px;padding:8px 16px;border-radius:8px;text-decoration:none;">
                📄 Export PDF
            </a>
            <a href="{{ route('vendor.recap.export-csv', ['from' => $date_from, 'to' => $date_to]) }}"
               style="display:inline-flex;align-items:center;gap:6px;background:#16a34a;color:#fff;font-weight:600;font-size:13px;padding:8px 16px;border-radius:8px;text-decoration:none;">
                📊 Export CSV
            </a>
        </div>

    </div>
    <p style="font-size:11px; color:#9ca3af; margin-top:12px;">
        Data diperbarui otomatis · Periode: <strong>{{ \Carbon\Carbon::parse($date_from)->format('d M Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($date_to)->format('d M Y') }}</strong>
    </p>
</x-filament::section>

{{-- ══════════════════════════════════════════════════════
     STATS CARDS — 2 baris × 4 kolom
══════════════════════════════════════════════════════ --}}
<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-top:20px;">

    @php
    $cards = [
        ['icon'=>'🗓️', 'value'=> number_format($stats['total_bookings'] ?? 0),
         'label'=>'Total Booking', 'bg'=>'#eff6ff', 'border'=>'#bfdbfe', 'val_color'=>'#1d4ed8'],

        ['icon'=>'✅', 'value'=> number_format($stats['completed'] ?? 0),
         'label'=>'Selesai', 'bg'=>'#f0fdf4', 'border'=>'#bbf7d0', 'val_color'=>'#15803d'],

        ['icon'=>'💰', 'value'=> 'Rp ' . number_format($stats['total_revenue'] ?? 0, 0, ',', '.'),
         'label'=>'Total Payout Anda', 'bg'=>'#faf5ff', 'border'=>'#ddd6fe', 'val_color'=>'#7c3aed'],

        ['icon'=>'⭐', 'value'=> ($stats['avg_rating'] ?? 0) > 0 ? number_format($stats['avg_rating'], 1) : '—',
         'label'=>'Rating Rata-rata', 'bg'=>'#fffbeb', 'border'=>'#fde68a', 'val_color'=>'#d97706'],

        ['icon'=>'🚗', 'value'=> number_format($stats['ongoing'] ?? 0),
         'label'=>'Berlangsung', 'bg'=>'#ecfeff', 'border'=>'#a5f3fc', 'val_color'=>'#0891b2'],

        ['icon'=>'❌', 'value'=> number_format($stats['cancelled'] ?? 0),
         'label'=>'Dibatalkan', 'bg'=>'#fff1f2', 'border'=>'#fecdd3', 'val_color'=>'#e11d48'],

        ['icon'=>'⚠️', 'value'=> number_format($stats['late_count'] ?? 0),
         'label'=>'Keterlambatan', 'bg'=>'#fff7ed', 'border'=>'#fed7aa', 'val_color'=>'#ea580c'],

        ['icon'=>'💸', 'value'=> 'Rp ' . number_format($stats['total_late_fee'] ?? 0, 0, ',', '.'),
         'label'=>'Total Denda', 'bg'=>'#fef2f2', 'border'=>'#fecaca', 'val_color'=>'#dc2626'],
    ];
    @endphp

    @foreach($cards as $card)
    <div style="background:{{ $card['bg'] }}; border:1px solid {{ $card['border'] }}; border-radius:12px; padding:16px 18px;">
        <div style="font-size:22px; margin-bottom:8px;">{{ $card['icon'] }}</div>
        <div style="font-size:20px; font-weight:800; color:{{ $card['val_color'] }}; line-height:1.1; word-break:break-all;">
            {{ $card['value'] }}
        </div>
        <div style="font-size:12px; color:#6b7280; margin-top:4px; font-weight:500;">{{ $card['label'] }}</div>
    </div>
    @endforeach

</div>

{{-- ══════════════════════════════════════════════════════
     LAPORAN KETERLAMBATAN
══════════════════════════════════════════════════════ --}}
@php $lateBookings = $this->getLateBookings(); @endphp

@if($lateBookings->isNotEmpty())
<div style="margin-top:20px;">
<x-filament::section>
    <x-slot name="heading">
        <span style="color:#b91c1c; font-weight:700;">⚠️ Pengembalian Terlambat</span>
        <span style="font-size:12px; font-weight:500; background:#fee2e2; color:#b91c1c; padding:2px 10px; border-radius:999px; margin-left:8px;">
            {{ $lateBookings->count() }} transaksi
        </span>
    </x-slot>

    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#fef2f2; border-bottom:2px solid #fecaca;">
                    @foreach(['Kode Booking','Customer','Mobil','Jatuh Tempo','Dikembalikan','Terlambat','Denda'] as $h)
                    <th style="padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:#9f1239; white-space:nowrap;">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($lateBookings as $i => $b)
                <tr style="border-bottom:1px solid #fee2e2; background:{{ $i % 2 === 0 ? '#fff' : '#fff7f7' }};">
                    <td style="padding:10px 14px; font-family:monospace; font-size:11px; color:#6b7280;">{{ $b->code }}</td>
                    <td style="padding:10px 14px; font-weight:600; color:#111827;">{{ $b->customer?->full_name }}</td>
                    <td style="padding:10px 14px; color:#374151;">{{ $b->car?->brand }} {{ $b->car?->model }}</td>
                    <td style="padding:10px 14px; font-size:12px; color:#6b7280; white-space:nowrap;">{{ $b->end_at?->format('d M Y, H:i') }}</td>
                    <td style="padding:10px 14px; font-size:12px; font-weight:700; color:#dc2626; white-space:nowrap;">{{ $b->actual_return_at?->format('d M Y, H:i') ?? '—' }}</td>
                    <td style="padding:10px 14px; text-align:center;">
                        <span style="background:#fee2e2; color:#b91c1c; font-size:11px; font-weight:700; padding:3px 10px; border-radius:999px;">
                            {{ $b->late_duration_hours }} jam
                        </span>
                    </td>
                    <td style="padding:10px 14px; text-align:right; font-weight:700; color:#dc2626; white-space:nowrap;">
                        Rp {{ number_format($b->late_fee, 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#fef2f2; border-top:2px solid #fecaca;">
                    <td colspan="5" style="padding:10px 14px; font-size:12px; font-weight:700; color:#9f1239;">Total</td>
                    <td style="padding:10px 14px; text-align:center; font-weight:700; color:#b91c1c;">{{ $lateBookings->sum('late_duration_hours') }} jam</td>
                    <td style="padding:10px 14px; text-align:right; font-weight:700; color:#dc2626;">
                        Rp {{ number_format($lateBookings->sum('late_fee'), 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-filament::section>
</div>
@endif

{{-- ══════════════════════════════════════════════════════
     SEMUA TRANSAKSI
══════════════════════════════════════════════════════ --}}
@php $bookings = $this->getBookingList(); @endphp

<div style="margin-top:20px;">
<x-filament::section>
    <x-slot name="heading">
        <span style="font-weight:700;">📋 Semua Transaksi</span>
        <span style="font-size:12px; font-weight:500; background:#f3f4f6; color:#6b7280; padding:2px 10px; border-radius:999px; margin-left:8px;">
            {{ $bookings->count() }} transaksi
        </span>
    </x-slot>

    @if($bookings->isEmpty())
        <div style="text-align:center; padding:48px 0; color:#9ca3af;">
            <div style="font-size:40px; margin-bottom:12px;">📋</div>
            <p style="font-size:14px;">Tidak ada transaksi untuk periode ini.</p>
        </div>
    @else

    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#f9fafb; border-bottom:2px solid #e5e7eb;">
                    @foreach(['Kode','Customer','Mobil','Tanggal Sewa','Status','Total','Payout','Keterlambatan'] as $h)
                    <th style="padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:#6b7280; white-space:nowrap;">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $i => $b)
                @php
                    $st = match($b->status) {
                        'completed'        => ['bg'=>'#dcfce7','color'=>'#15803d','label'=>'Selesai'],
                        'cancelled'        => ['bg'=>'#fee2e2','color'=>'#b91c1c','label'=>'Dibatalkan'],
                        'ongoing'          => ['bg'=>'#ede9fe','color'=>'#6d28d9','label'=>'Berlangsung'],
                        'confirmed'        => ['bg'=>'#dbeafe','color'=>'#1d4ed8','label'=>'Dikonfirmasi'],
                        'awaiting_vendor'  => ['bg'=>'#fef9c3','color'=>'#a16207','label'=>'Menunggu Konfirmasi'],
                        'awaiting_payment' => ['bg'=>'#fef3c7','color'=>'#b45309','label'=>'Menunggu Bayar'],
                        default            => ['bg'=>'#f3f4f6','color'=>'#374151','label'=>ucfirst($b->status)],
                    };
                    $rowBg = $b->is_late
                        ? ($i % 2 === 0 ? '#fff7f7' : '#fff0f0')
                        : ($i % 2 === 0 ? '#ffffff' : '#fafafa');
                @endphp
                <tr style="border-bottom:1px solid #f3f4f6; background:{{ $rowBg }};">
                    <td style="padding:10px 14px; font-family:monospace; font-size:11px; color:#9ca3af;">{{ $b->code }}</td>
                    <td style="padding:10px 14px; font-weight:600; color:#111827; white-space:nowrap;">{{ $b->customer?->full_name }}</td>
                    <td style="padding:10px 14px; color:#374151; white-space:nowrap;">{{ $b->car?->brand }} {{ $b->car?->model }}</td>
                    <td style="padding:10px 14px; font-size:12px; color:#6b7280; white-space:nowrap;">
                        {{ $b->start_at?->format('d M Y') }}<br>
                        <span style="color:#9ca3af;">→ {{ $b->end_at?->format('d M Y') }}</span>
                    </td>
                    <td style="padding:10px 14px;">
                        <span style="background:{{ $st['bg'] }}; color:{{ $st['color'] }}; font-size:11px; font-weight:700; padding:3px 10px; border-radius:999px; white-space:nowrap;">
                            {{ $st['label'] }}
                        </span>
                    </td>
                    <td style="padding:10px 14px; text-align:right; font-weight:600; color:#111827; white-space:nowrap;">
                        Rp {{ number_format($b->total, 0, ',', '.') }}
                    </td>
                    <td style="padding:10px 14px; text-align:right; font-weight:700; color:#2563eb; white-space:nowrap;">
                        Rp {{ number_format($b->vendor_payout_amount, 0, ',', '.') }}
                    </td>
                    <td style="padding:10px 14px; text-align:center;">
                        @if($b->is_late)
                            <span style="background:#fee2e2; color:#b91c1c; font-size:11px; font-weight:700; padding:3px 8px; border-radius:999px; white-space:nowrap;">
                                ⚠️ {{ $b->late_duration_hours }}j
                            </span>
                        @else
                            <span style="color:#d1d5db; font-size:14px;">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f9fafb; border-top:2px solid #e5e7eb;">
                    <td colspan="5" style="padding:12px 14px; font-size:12px; font-weight:700; color:#374151;">
                        TOTAL — {{ $bookings->count() }} transaksi
                    </td>
                    <td style="padding:12px 14px; text-align:right; font-weight:800; color:#111827; white-space:nowrap;">
                        Rp {{ number_format($bookings->sum('total'), 0, ',', '.') }}
                    </td>
                    <td style="padding:12px 14px; text-align:right; font-weight:800; color:#2563eb; white-space:nowrap;">
                        Rp {{ number_format($bookings->sum('vendor_payout_amount'), 0, ',', '.') }}
                    </td>
                    <td style="padding:12px 14px; text-align:center; font-weight:700; color:#dc2626;">
                        @if($bookings->where('is_late', true)->count() > 0)
                            {{ $bookings->where('is_late', true)->count() }}x terlambat
                        @else
                            <span style="color:#16a34a;">✅ Semua tepat waktu</span>
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

</x-filament::section>
</div>

</x-filament-panels::page>
