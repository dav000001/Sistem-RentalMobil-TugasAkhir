<x-filament-panels::page>

@php $stats = $this->getSummaryStats(); @endphp

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
            <a href="{{ route('admin.recap.vendor-pdf', ['from' => $date_from, 'to' => $date_to]) }}"
               target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;background:#dc2626;color:#fff;font-weight:600;font-size:13px;padding:8px 14px;border-radius:8px;text-decoration:none;">
                📄 Vendor PDF
            </a>
            <a href="{{ route('admin.recap.vendor-csv', ['from' => $date_from, 'to' => $date_to]) }}"
               style="display:inline-flex;align-items:center;gap:6px;background:#16a34a;color:#fff;font-weight:600;font-size:13px;padding:8px 14px;border-radius:8px;text-decoration:none;">
                📊 Vendor CSV
            </a>
            <a href="{{ route('admin.recap.customer-pdf', ['from' => $date_from, 'to' => $date_to]) }}"
               target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;background:#7c3aed;color:#fff;font-weight:600;font-size:13px;padding:8px 14px;border-radius:8px;text-decoration:none;">
                📄 Customer PDF
            </a>
            <a href="{{ route('admin.recap.customer-csv', ['from' => $date_from, 'to' => $date_to]) }}"
               style="display:inline-flex;align-items:center;gap:6px;background:#0891b2;color:#fff;font-weight:600;font-size:13px;padding:8px 14px;border-radius:8px;text-decoration:none;">
                📊 Customer CSV
            </a>
        </div>

    </div>
    <p style="font-size:11px; color:#9ca3af; margin-top:12px;">
        Data diperbarui otomatis · Periode: <strong>{{ \Carbon\Carbon::parse($date_from)->format('d M Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($date_to)->format('d M Y') }}</strong>
    </p>
</x-filament::section>

{{-- ══════════════════════════════════════════════════════
     SUMMARY STATS
══════════════════════════════════════════════════════ --}}
@php
$summaryCards = [
    ['icon'=>'🗓️','value'=> number_format($stats['total_bookings']),  'label'=>'Total Booking',      'bg'=>'#eff6ff','border'=>'#bfdbfe','val_color'=>'#1d4ed8'],
    ['icon'=>'✅','value'=> number_format($stats['completed']),        'label'=>'Selesai',             'bg'=>'#f0fdf4','border'=>'#bbf7d0','val_color'=>'#15803d'],
    ['icon'=>'💰','value'=> 'Rp '.number_format($stats['total_revenue'],0,',','.'), 'label'=>'Total Pendapatan', 'bg'=>'#faf5ff','border'=>'#ddd6fe','val_color'=>'#7c3aed'],
    ['icon'=>'🏦','value'=> 'Rp '.number_format($stats['platform_fee'],0,',','.'),  'label'=>'Komisi Platform',  'bg'=>'#fff7ed','border'=>'#fed7aa','val_color'=>'#ea580c'],
    ['icon'=>'❌','value'=> number_format($stats['cancelled']),        'label'=>'Dibatalkan',          'bg'=>'#fff1f2','border'=>'#fecdd3','val_color'=>'#e11d48'],
    ['icon'=>'⚠️','value'=> number_format($stats['late_returns']),     'label'=>'Keterlambatan',       'bg'=>'#fef2f2','border'=>'#fecaca','val_color'=>'#dc2626'],
    ['icon'=>'🏢','value'=> number_format($stats['active_vendors']),   'label'=>'Vendor Aktif',        'bg'=>'#ecfeff','border'=>'#a5f3fc','val_color'=>'#0891b2'],
    ['icon'=>'👤','value'=> number_format($stats['active_customers']), 'label'=>'Customer Aktif',      'bg'=>'#f5f3ff','border'=>'#ddd6fe','val_color'=>'#6d28d9'],
];
@endphp

<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-top:20px;">
    @foreach($summaryCards as $card)
    <div style="background:{{ $card['bg'] }}; border:1px solid {{ $card['border'] }}; border-radius:12px; padding:16px 18px;">
        <div style="font-size:22px; margin-bottom:8px;">{{ $card['icon'] }}</div>
        <div style="font-size:20px; font-weight:800; color:{{ $card['val_color'] }}; line-height:1.2; word-break:break-all;">
            {{ $card['value'] }}
        </div>
        <div style="font-size:12px; color:#6b7280; margin-top:4px; font-weight:500;">{{ $card['label'] }}</div>
    </div>
    @endforeach
</div>

{{-- ══════════════════════════════════════════════════════
     TABS
══════════════════════════════════════════════════════ --}}
<div style="margin-top:20px;">
<x-filament::section>

    {{-- Tab Navigation --}}
    <div style="display:flex; border-bottom:2px solid #e5e7eb; margin:-24px -24px 20px -24px; padding:0 24px;">
        <button wire:click="$set('active_tab','vendor')"
            style="padding:12px 20px; font-size:14px; font-weight:600; border:none; background:transparent; cursor:pointer;
                   {{ $active_tab === 'vendor'
                       ? 'color:#2563eb; border-bottom:2px solid #2563eb; margin-bottom:-2px;'
                       : 'color:#9ca3af;' }}">
            🏢 Rekapitulasi Vendor
        </button>
        <button wire:click="$set('active_tab','customer')"
            style="padding:12px 20px; font-size:14px; font-weight:600; border:none; background:transparent; cursor:pointer;
                   {{ $active_tab === 'customer'
                       ? 'color:#2563eb; border-bottom:2px solid #2563eb; margin-bottom:-2px;'
                       : 'color:#9ca3af;' }}">
            👤 Rekapitulasi Customer
        </button>
    </div>

    {{-- ── TAB: VENDOR ──────────────────────────────────── --}}
    @if($active_tab === 'vendor')
    @php $vendorData = $this->getVendorRecapData(); @endphp

    @if($vendorData->isEmpty())
        <div style="text-align:center; padding:48px 0; color:#9ca3af;">
            <div style="font-size:40px; margin-bottom:12px;">🏢</div>
            <p style="font-size:14px;">Tidak ada data vendor untuk periode ini.</p>
        </div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#f9fafb; border-bottom:2px solid #e5e7eb;">
                    @foreach(['#','Nama Vendor','Total Mobil','Total Booking','Selesai','Pendapatan Vendor','Keterlambatan','Rating'] as $h)
                    <th style="padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:#6b7280; white-space:nowrap;">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($vendorData as $i => $row)
                <tr style="border-bottom:1px solid #f3f4f6; background:{{ $i % 2 === 0 ? '#fff' : '#fafafa' }};">
                    <td style="padding:10px 14px; font-weight:700; color:#d1d5db; font-size:12px;">{{ $i + 1 }}</td>
                    <td style="padding:10px 14px; font-weight:700; color:#111827; white-space:nowrap;">{{ $row['name'] }}</td>
                    <td style="padding:10px 14px; text-align:center; color:#374151;">{{ $row['total_cars'] }}</td>
                    <td style="padding:10px 14px; text-align:center; font-weight:600; color:#374151;">{{ $row['total_bookings'] }}</td>
                    <td style="padding:10px 14px; text-align:center;">
                        <span style="background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;">
                            {{ $row['completed'] }}
                        </span>
                    </td>
                    <td style="padding:10px 14px; text-align:right; font-weight:700; color:#2563eb; white-space:nowrap;">
                        Rp {{ number_format($row['total_revenue'], 0, ',', '.') }}
                    </td>
                    <td style="padding:10px 14px; text-align:center;">
                        @if($row['late_count'] > 0)
                            <span style="background:#fee2e2;color:#b91c1c;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;">
                                ⚠️ {{ $row['late_count'] }}
                            </span>
                        @else
                            <span style="color:#16a34a;font-size:16px;">✓</span>
                        @endif
                    </td>
                    <td style="padding:10px 14px; text-align:center; font-weight:700; color:#d97706;">
                        {{ $row['avg_rating'] > 0 ? '⭐ '.$row['avg_rating'] : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f9fafb; border-top:2px solid #e5e7eb;">
                    <td colspan="2" style="padding:12px 14px; font-weight:800; color:#374151; font-size:12px;">TOTAL KESELURUHAN</td>
                    <td style="padding:12px 14px; text-align:center; font-weight:700; color:#374151;">{{ $vendorData->sum('total_cars') }}</td>
                    <td style="padding:12px 14px; text-align:center; font-weight:700; color:#374151;">{{ $vendorData->sum('total_bookings') }}</td>
                    <td style="padding:12px 14px; text-align:center; font-weight:700; color:#15803d;">{{ $vendorData->sum('completed') }}</td>
                    <td style="padding:12px 14px; text-align:right; font-weight:800; color:#1d4ed8; white-space:nowrap;">
                        Rp {{ number_format($vendorData->sum('total_revenue'), 0, ',', '.') }}
                    </td>
                    <td style="padding:12px 14px; text-align:center; font-weight:700; color:#dc2626;">{{ $vendorData->sum('late_count') }}</td>
                    <td style="padding:12px 14px; text-align:center; color:#9ca3af;">—</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
    @endif

    {{-- ── TAB: CUSTOMER ────────────────────────────────── --}}
    @if($active_tab === 'customer')
    @php $customerData = $this->getCustomerRecapData(); @endphp

    @if($customerData->isEmpty())
        <div style="text-align:center; padding:48px 0; color:#9ca3af;">
            <div style="font-size:40px; margin-bottom:12px;">👤</div>
            <p style="font-size:14px;">Tidak ada data customer untuk periode ini.</p>
        </div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#f9fafb; border-bottom:2px solid #e5e7eb;">
                    @foreach(['#','Customer','Email','Total Booking','Selesai','Total Pengeluaran','Keterlambatan','Mobil Favorit'] as $h)
                    <th style="padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:#6b7280; white-space:nowrap;">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($customerData as $i => $row)
                <tr style="border-bottom:1px solid #f3f4f6; background:{{ $i % 2 === 0 ? '#fff' : '#fafafa' }};">
                    <td style="padding:10px 14px; font-weight:700; color:#d1d5db; font-size:12px;">{{ $i + 1 }}</td>
                    <td style="padding:10px 14px; font-weight:700; color:#111827; white-space:nowrap;">{{ $row['name'] }}</td>
                    <td style="padding:10px 14px; font-size:12px; color:#9ca3af;">{{ $row['email'] }}</td>
                    <td style="padding:10px 14px; text-align:center; font-weight:600; color:#374151;">{{ $row['total_bookings'] }}</td>
                    <td style="padding:10px 14px; text-align:center;">
                        <span style="background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;">
                            {{ $row['completed'] }}
                        </span>
                    </td>
                    <td style="padding:10px 14px; text-align:right; font-weight:700; color:#2563eb; white-space:nowrap;">
                        Rp {{ number_format($row['total_spent'], 0, ',', '.') }}
                    </td>
                    <td style="padding:10px 14px; text-align:center;">
                        @if($row['late_count'] > 0)
                            <span style="background:#fee2e2;color:#b91c1c;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;">
                                ⚠️ {{ $row['late_count'] }}
                            </span>
                        @else
                            <span style="color:#16a34a;font-size:16px;">✓</span>
                        @endif
                    </td>
                    <td style="padding:10px 14px; font-size:12px; color:#6b7280;">{{ $row['fav_car'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f9fafb; border-top:2px solid #e5e7eb;">
                    <td colspan="3" style="padding:12px 14px; font-weight:800; color:#374151; font-size:12px;">TOTAL KESELURUHAN</td>
                    <td style="padding:12px 14px; text-align:center; font-weight:700; color:#374151;">{{ $customerData->sum('total_bookings') }}</td>
                    <td style="padding:12px 14px; text-align:center; font-weight:700; color:#15803d;">{{ $customerData->sum('completed') }}</td>
                    <td style="padding:12px 14px; text-align:right; font-weight:800; color:#1d4ed8; white-space:nowrap;">
                        Rp {{ number_format($customerData->sum('total_spent'), 0, ',', '.') }}
                    </td>
                    <td style="padding:12px 14px; text-align:center; font-weight:700; color:#dc2626;">{{ $customerData->sum('late_count') }}</td>
                    <td style="padding:12px 14px; color:#9ca3af;">—</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
    @endif

</x-filament::section>
</div>

</x-filament-panels::page>
