<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;
    protected static ?string $navigationLabel = 'Pemesanan';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-calendar';
    }

    public static function getNavigationBadge(): ?string
    {
        $vendor = auth('vendor')->user()?->vendor;
        if (!$vendor) return null;

        // Booking butuh konfirmasi
        $pending = \App\Models\Booking::where('vendor_id', $vendor->id)
            ->where('status', 'awaiting_vendor')
            ->count();

        // Laporan keterlambatan belum diakui
        $lateReports = \App\Models\LateReturnReport::whereHas(
            'booking', fn ($q) => $q->where('vendor_id', $vendor->id)
        )->where('status', 'reported')->count();

        // Permintaan ganti mobil pending
        $carChangePending = \App\Models\CarChangeRequest::where('vendor_id', $vendor->id)
            ->where('status', 'pending')
            ->count();

        $total = $pending + $lateReports + $carChangePending;
        return $total > 0 ? (string) $total : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $vendor = auth('vendor')->user()?->vendor;
        return parent::getEloquentQuery()->where('vendor_id', $vendor?->id ?? 0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // ── HIGHLIGHT ALERT: Permintaan Ganti Mobil Pending (Paling Atas) ──
            \Filament\Schemas\Components\Section::make('🚨 PERMINTAAN GANTI MOBIL DARI CUSTOMER (PENDING)')
                ->description('Customer mengajukan pergantian mobil berkapasitas lebih besar. Mohon segera berikan respon!')
                ->visible(fn ($record) => $record?->carChangeRequest?->status === 'pending')
                ->components([
                    Forms\Components\Placeholder::make('alert_car_change_info')
                        ->hiddenLabel()
                        ->content(fn ($record) => new \Illuminate\Support\HtmlString('
                            <div class="bg-amber-50 border-2 border-amber-400 rounded-xl p-4 space-y-3">
                                <div class="flex items-center justify-between border-b border-amber-200 pb-2">
                                    <p class="font-bold text-base text-amber-900 flex items-center gap-2">
                                        <span>⚠️</span> Customer Meminta Pergantian Mobil
                                    </p>
                                    <span class="bg-amber-200 text-amber-900 text-xs font-extrabold px-3 py-1 rounded-full">
                                        👥 ' . ($record->carChangeRequest?->passenger_count ?? 0) . ' Penumpang Aktual
                                    </span>
                                </div>
                                <div class="bg-white p-3 rounded-lg border border-amber-300 text-sm text-gray-800">
                                    <strong>Alasan Customer:</strong> "' . e($record->carChangeRequest?->reason ?? '') . '"
                                </div>
                                <div class="text-xs text-amber-900 font-medium">
                                    👉 <strong>Gunakan Tombol Aksi di Atas Halaman:</strong><br>
                                    • <strong class="text-green-700">"✅ Setujui & Pilih Mobil Pengganti"</strong> — Jika Anda memiliki armada yang sesuai kapasitas.<br>
                                    • <strong class="text-red-700">"❌ Tolak & Batalkan (Refund 50%)"</strong> — Jika armada Anda penuh / tidak memiliki unit pengganti.
                                </div>
                            </div>
                        ')),
                ]),

            // ── Info Penyewa (readonly via Placeholder) ──────────────
            \Filament\Schemas\Components\Section::make('👤 Informasi Penyewa')
                ->description('Data customer yang melakukan pemesanan')
                ->components([
                    Forms\Components\Placeholder::make('customer_name')
                        ->label('Nama Lengkap')
                        ->content(fn ($record) => $record?->customer?->full_name ?? '—'),

                    Forms\Components\Placeholder::make('customer_email')
                        ->label('Email')
                        ->content(fn ($record) => $record?->customer?->user?->email ?? '—'),

                    Forms\Components\Placeholder::make('customer_phone')
                        ->label('No. WhatsApp')
                        ->content(fn ($record) => $record?->customer?->user?->phone
                            ? '📱 ' . $record->customer->user->phone
                            : '—'),

                    Forms\Components\Placeholder::make('customer_verification')
                        ->label('Status Verifikasi')
                        ->content(fn ($record) => match($record?->customer?->verification_status) {
                            'verified'   => '✅ Terverifikasi',
                            'pending'    => '⏳ Menunggu Verifikasi',
                            'rejected'   => '❌ Ditolak',
                            default      => '—',
                        }),

                    Forms\Components\Placeholder::make('customer_late_history')
                        ->label('Riwayat Terlambat Customer')
                        ->content(function ($record) {
                            if (!$record?->customer_id) return '—';
                            $count = \App\Models\Booking::where('customer_id', $record->customer_id)
                                ->where('is_late', true)
                                ->where('status', 'completed')
                                ->count();
                            return $count > 0
                                ? new \Illuminate\Support\HtmlString('<span style="color:#dc2626;font-weight:600;">⚠️ ' . $count . 'x pernah terlambat</span>')
                                : new \Illuminate\Support\HtmlString('<span style="color:#16a34a;font-weight:500;">⚡ Belum pernah terlambat</span>');
                        }),
                ])
                ->columns(2),

            // ── Dokumen Identitas Terverifikasi ───────────────────────
            \Filament\Schemas\Components\Section::make('🪪 Dokumen Identitas Customer')
                ->description('Gunakan untuk mencocokkan identitas customer saat serah terima mobil')
                ->components([
                    Forms\Components\Placeholder::make('identity_documents')
                        ->label('')
                        ->columnSpanFull()
                        ->content(function ($record) {
                            if (!$record) return '—';

                            $customer = $record->customer;
                            if (!$customer) return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-500">Data customer tidak ditemukan.</p>');

                            // Cek ownership — pastikan booking milik vendor yang login
                            $vendor = auth('vendor')->user()?->vendor;
                            if (!$vendor || $record->vendor_id !== $vendor->id) {
                                return new \Illuminate\Support\HtmlString('<p class="text-sm text-red-500">⛔ Akses ditolak.</p>');
                            }

                            // Dokumen hanya tampil jika sudah terverifikasi admin
                            if ($customer->verification_status !== 'verified') {
                                $msg = match ($customer->verification_status) {
                                    'pending'  => '⏳ Dokumen belum dapat ditampilkan karena identitas customer belum diverifikasi admin.',
                                    'rejected' => '❌ Dokumen tidak dapat ditampilkan karena verifikasi customer ditolak.',
                                    default    => 'Status verifikasi tidak diketahui.',
                                };
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">' . $msg . '</div>'
                                );
                            }

                            // Catat audit log akses dokumen
                            \App\Models\DocumentViewLog::record($record);

                            // Tentukan apakah SIM perlu ditampilkan
                            // SIM relevan jika booking lepas kunci (bukan with_driver_only)
                            $showSim = ($record->car?->rental_option ?? 'self_drive_only') !== 'with_driver_only'
                                || !$record->with_driver;

                            $docs = [
                                ['label' => 'KTP',              'url' => $customer->ktp_url,    'show' => true],
                                ['label' => 'SIM',              'url' => $customer->sim_url,    'show' => $showSim],
                                ['label' => 'Selfie + KTP',     'url' => $customer->selfie_url, 'show' => true],
                            ];

                            $html = '<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">';

                            foreach ($docs as $doc) {
                                if (!$doc['show']) continue;

                                $html .= '<div class="flex flex-col gap-2">';
                                $html .= '<p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">' . $doc['label'] . '</p>';

                                if ($doc['url']) {
                                    // Tentukan apakah URL sudah absolut atau perlu prefix storage
                                    $src = str_starts_with($doc['url'], 'http')
                                        ? $doc['url']
                                        : asset('storage/' . ltrim($doc['url'], '/'));

                                    $html .= '<a href="' . $src . '" target="_blank" '
                                        . 'class="block group relative rounded-xl overflow-hidden border-2 border-gray-200 hover:border-blue-400 transition-all shadow-sm"'
                                        . 'title="Klik untuk perbesar">'
                                        . '<img src="' . $src . '" alt="' . $doc['label'] . '" '
                                        . 'class="w-full h-40 object-cover group-hover:opacity-90 transition" '
                                        . 'loading="lazy" />'
                                        . '<div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 bg-black/30 transition">'
                                        . '<span class="text-white text-xs font-semibold bg-black/50 px-2 py-1 rounded">🔍 Perbesar</span>'
                                        . '</div>'
                                        . '</a>';
                                } else {
                                    $html .= '<div class="w-full h-40 rounded-xl bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center">'
                                        . '<span class="text-xs text-gray-400">Belum diupload</span>'
                                        . '</div>';
                                }

                                $html .= '</div>';
                            }

                            $html .= '</div>';
                            $html .= '<p class="text-xs text-gray-400 mt-3">🔒 Dokumen ini hanya ditampilkan kepada Anda selaku vendor pemilik booking ini. Akses tercatat untuk keperluan audit.</p>';

                            return new \Illuminate\Support\HtmlString($html);
                        }),
                ])
                ->columns(1)
                ->visibleOn('edit'),

            // ── Info Mobil ────────────────────────────────────────────
            \Filament\Schemas\Components\Section::make('🚗 Informasi Mobil')
                ->components([
                    Forms\Components\Placeholder::make('car_name')
                        ->label('Mobil')
                        ->content(fn ($record) => $record?->car
                            ? $record->car->brand . ' ' . $record->car->model . ' (' . $record->car->year . ')'
                            : '—'),

                    Forms\Components\Placeholder::make('car_plate')
                        ->label('Plat Nomor')
                        ->content(fn ($record) => $record?->car?->plate_number ?? '—'),

                    Forms\Components\Placeholder::make('car_transmission')
                        ->label('Transmisi')
                        ->content(fn ($record) => ucfirst($record?->car?->transmission ?? '—')),

                    Forms\Components\Placeholder::make('with_driver')
                        ->label('Dengan Sopir')
                        ->content(fn ($record) => $record?->with_driver ? '✅ Ya' : '❌ Tidak'),

                    Forms\Components\Placeholder::make('driver_info')
                        ->label('Sopir Ditugaskan')
                        ->content(fn ($record) => $record?->driver
                            ? new \Illuminate\Support\HtmlString(
                                '<div class="text-sm">'
                                . '<strong>' . e($record->driver->name) . '</strong>'
                                . ' &nbsp;|&nbsp; 📱 ' . e($record->driver->phone)
                                . '</div>'
                            )
                            : ($record?->with_driver
                                ? new \Illuminate\Support\HtmlString('<span class="text-orange-500 text-sm">⚠️ Belum ada sopir yang ditugaskan</span>')
                                : new \Illuminate\Support\HtmlString('<span class="text-gray-400 text-sm">— Lepas kunci</span>')
                            ))
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // ── Detail Pemesanan ──────────────────────────────────────
            \Filament\Schemas\Components\Section::make('📋 Detail Pemesanan')
                ->components([
                    Forms\Components\Placeholder::make('booking_code')
                        ->label('Kode Booking')
                        ->content(fn ($record) => $record?->code ?? '—'),

                    Forms\Components\Placeholder::make('booking_duration')
                        ->label('Durasi')
                        ->content(fn ($record) => $record?->start_at && $record?->end_at
                            ? $record->start_at->format('d M Y') . ' → ' . $record->end_at->format('d M Y')
                              . ' (' . $record->start_at->diffInDays($record->end_at) . ' hari)'
                            : '—'),

                    Forms\Components\Placeholder::make('pickup_location')
                        ->label('Lokasi Pickup')
                        ->content(fn ($record) => $record?->pickup_location ?? '—')
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('dropoff_location')
                        ->label('Lokasi Dropoff')
                        ->content(fn ($record) => $record?->dropoff_location ?? 'Sama dengan pickup')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // ── Rincian Pembayaran ────────────────────────────────────
            \Filament\Schemas\Components\Section::make('💰 Rincian Pembayaran')
                ->components([
                    Forms\Components\Placeholder::make('subtotal')
                        ->label('Subtotal')
                        ->content(fn ($record) => $record?->subtotal
                            ? 'Rp ' . number_format($record->subtotal, 0, ',', '.')
                            : '—'),

                    Forms\Components\Placeholder::make('platform_fee')
                        ->label('Biaya Platform')
                        ->content(fn ($record) => $record?->platform_fee
                            ? 'Rp ' . number_format($record->platform_fee, 0, ',', '.')
                            : '—'),

                    Forms\Components\Placeholder::make('total')
                        ->label('Total Dibayar Customer')
                        ->content(fn ($record) => $record?->total
                            ? 'Rp ' . number_format($record->total, 0, ',', '.')
                            : '—'),

                    Forms\Components\Placeholder::make('vendor_payout')
                        ->label('Payout Vendor (Anda)')
                        ->content(fn ($record) => $record?->vendor_payout_amount
                            ? '✅ Rp ' . number_format($record->vendor_payout_amount, 0, ',', '.')
                            : '—'),

                    Forms\Components\Placeholder::make('notes')
                        ->label('Catatan dari Customer')
                        ->content(fn ($record) => $record?->notes ?: 'Tidak ada catatan')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // ── Permintaan Ganti Mobil (Car Upgrade Request) ──────────────
            \Filament\Schemas\Components\Section::make('🔄 Permintaan Ganti Mobil')
                ->description('Pengajuan pergantian mobil dari customer karena kapasitas penumpang')
                ->visible(fn ($record) => (bool) $record?->carChangeRequest)
                ->columns(2)
                ->components([
                    Forms\Components\Placeholder::make('cr_status')
                        ->label('Status Permintaan')
                        ->content(fn ($record) => $record?->carChangeRequest
                            ? new \Illuminate\Support\HtmlString(
                                '<span class="px-2.5 py-1 rounded-full text-xs font-semibold '
                                . match($record->carChangeRequest->status) {
                                    'pending'              => 'bg-amber-100 text-amber-800',
                                    'approved'             => 'bg-green-100 text-green-800',
                                    'rejected'             => 'bg-red-100 text-red-800',
                                    'cancelled_by_customer'=> 'bg-gray-100 text-gray-700',
                                    default                => 'bg-gray-100 text-gray-700',
                                }
                                . '">' . e($record->carChangeRequest->statusLabel()) . '</span>'
                            )
                            : '—'),

                    Forms\Components\Placeholder::make('cr_passenger_count')
                        ->label('Jumlah Penumpang Aktual')
                        ->content(fn ($record) => $record?->carChangeRequest
                            ? '👥 ' . $record->carChangeRequest->passenger_count . ' orang'
                            : '—'),

                    Forms\Components\Placeholder::make('cr_with_driver')
                        ->label('Opsi Sewa yang Diminta')
                        ->content(fn ($record) => match ($record?->carChangeRequest?->with_driver) {
                            true    => '👨‍✈️ Dengan Sopir',
                            false   => '🔑 Lepas Kunci (Tanpa Sopir)',
                            default => '—',
                        }),

                    Forms\Components\Placeholder::make('cr_reason')
                        ->label('Alasan Pengajuan Customer')
                        ->content(fn ($record) => $record?->carChangeRequest?->reason ?? '—')
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('cr_requested_at')
                        ->label('Waktu Pengajuan')
                        ->content(fn ($record) => $record?->carChangeRequest?->requested_at
                            ? $record->carChangeRequest->requested_at->format('d M Y, H:i')
                            : '—'),

                    Forms\Components\Placeholder::make('cr_new_car')
                        ->label('Mobil Pengganti Disetujui')
                        ->content(fn ($record) => $record?->carChangeRequest?->newCar
                            ? $record->carChangeRequest->newCar->brand . ' ' . $record->carChangeRequest->newCar->model
                            : '—'),

                    Forms\Components\Placeholder::make('cr_price_diff')
                        ->label('Selisih Harga Tambahan')
                        ->content(fn ($record) => $record?->carChangeRequest?->price_difference
                            ? 'Rp ' . number_format($record->carChangeRequest->price_difference, 0, ',', '.')
                            : 'Rp 0'),

                    Forms\Components\Placeholder::make('cr_payment_status')
                        ->label('Status Bayar Selisih')
                        ->content(fn ($record) => match(true) {
                            !$record?->carChangeRequest                              => '—',
                            $record->carChangeRequest->price_difference <= 0         => '✅ Tidak ada selisih',
                            (bool) $record->carChangeRequest->additional_payment_at => '✅ Pembayaran dikonfirmasi admin (' . $record->carChangeRequest->additional_payment_at->format('d M Y H:i') . ')',
                            (bool) $record->carChangeRequest->additional_payment_proof => '⏳ Bukti diupload, menunggu konfirmasi admin',
                            default                                                  => '⚠️ Menunggu customer bayar selisih',
                        }),
                ]),

            // ── Laporan Keterlambatan (muncul jika ada laporan) ──────────────
            \Filament\Schemas\Components\Section::make('⚠️ Laporan Keterlambatan')
                ->columns(2)
                ->components([
                    Forms\Components\Placeholder::make('lr_reporter')
                        ->label('Dilaporkan Oleh')
                        ->content(fn ($record) => $record?->lateReturnReport
                            ? $record->lateReturnReport->reporterLabel()
                            : '—'),

                    Forms\Components\Placeholder::make('lr_estimated')
                        ->label('Perkiraan Terlambat')
                        ->content(fn ($record) => $record?->lateReturnReport?->estimated_late_hours
                            ? '~' . $record->lateReturnReport->estimated_late_hours . ' jam'
                            : '—'),

                    Forms\Components\Placeholder::make('lr_reason')
                        ->label('Alasan')
                        ->content(fn ($record) => $record?->lateReturnReport?->reason ?? '—')
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('lr_location')
                        ->label('Lokasi Saat Lapor (GPS)')
                        ->content(fn ($record) => $record?->lateReturnReport?->hasLocation()
                            ? new \Illuminate\Support\HtmlString(
                                '<div>'
                                . '<a href="' . $record->lateReturnReport->getMapUrl() . '" target="_blank" '
                                . 'style="color:#2563eb;text-decoration:underline;font-size:13px;">📍 '
                                . e($record->lateReturnReport->location_address ?? $record->lateReturnReport->latitude . ', ' . $record->lateReturnReport->longitude)
                                . ' ↗</a>'
                                . '<div style="margin-top:8px;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb;">'
                                . '<iframe width="100%" height="200" frameborder="0" scrolling="no" '
                                . 'src="https://www.openstreetmap.org/export/embed.html'
                                . '?bbox=' . ($record->lateReturnReport->longitude - 0.005) . '%2C'
                                . ($record->lateReturnReport->latitude - 0.005) . '%2C'
                                . ($record->lateReturnReport->longitude + 0.005) . '%2C'
                                . ($record->lateReturnReport->latitude + 0.005)
                                . '&layer=mapnik&marker=' . $record->lateReturnReport->latitude . '%2C' . $record->lateReturnReport->longitude
                                . '" style="border:0;"></iframe></div></div>'
                            )
                            : new \Illuminate\Support\HtmlString(
                                '<span style="color:#f59e0b;font-size:12px;font-weight:600;">⚠️ Tidak ada GPS — customer tidak mengizinkan akses lokasi</span>'
                            ))
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('lr_return_location')
                        ->label('Lokasi Pengembalian (GPS)')
                        ->content(fn ($record) => $record?->lateReturnReport?->hasReturnLocation()
                            ? new \Illuminate\Support\HtmlString(
                                '<div>'
                                . '<a href="' . $record->lateReturnReport->getReturnMapUrl() . '" target="_blank" '
                                . 'style="color:#2563eb;text-decoration:underline;font-size:13px;">📍 '
                                . e($record->lateReturnReport->return_location_address ?? $record->lateReturnReport->return_latitude . ', ' . $record->lateReturnReport->return_longitude)
                                . ' ↗</a>'
                                . '<p style="font-size:11px;color:#6b7280;margin-top:2px;">Dikonfirmasi: '
                                . $record->lateReturnReport->return_confirmed_at?->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB'
                                . '</p>'
                                . '<div style="margin-top:8px;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb;">'
                                . '<iframe width="100%" height="200" frameborder="0" scrolling="no" '
                                . 'src="https://www.openstreetmap.org/export/embed.html'
                                . '?bbox=' . ($record->lateReturnReport->return_longitude - 0.005) . '%2C'
                                . ($record->lateReturnReport->return_latitude - 0.005) . '%2C'
                                . ($record->lateReturnReport->return_longitude + 0.005) . '%2C'
                                . ($record->lateReturnReport->return_latitude + 0.005)
                                . '&layer=mapnik&marker=' . $record->lateReturnReport->return_latitude . '%2C' . $record->lateReturnReport->return_longitude
                                . '" style="border:0;"></iframe></div></div>'
                            )
                            : new \Illuminate\Support\HtmlString(
                                '<span style="color:#9ca3af;font-size:12px;">— Belum ada konfirmasi pengembalian dari customer</span>'
                            ))
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('lr_status')
                        ->label('Status Laporan')
                        ->content(fn ($record) => match($record?->lateReturnReport?->status) {
                            'reported'     => new \Illuminate\Support\HtmlString('<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">⏳ Belum Diakui</span>'),
                            'acknowledged' => new \Illuminate\Support\HtmlString('<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">✅ Sudah Dilihat</span>'),
                            default        => '—',
                        }),
                ])
                ->visible(fn ($record) => $record?->lateReturnReport !== null),

            // ── Update Status ─────────────────────────────────────────
            \Filament\Schemas\Components\Section::make('🔄 Update Status')
                ->description('Gunakan tombol aksi di bagian atas halaman untuk mengubah status pesanan.')
                ->components([
                    Forms\Components\Placeholder::make('status_info')
                        ->label('Status Saat Ini')
                        ->content(fn ($record) => match($record?->status) {
                            'awaiting_vendor' => new \Illuminate\Support\HtmlString('<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold bg-blue-100 text-blue-700">⏳ Menunggu Konfirmasi Anda</span>'),
                            'confirmed'       => new \Illuminate\Support\HtmlString('<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold bg-green-100 text-green-700">✅ Dikonfirmasi</span>'),
                            'ongoing'         => new \Illuminate\Support\HtmlString('<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold bg-purple-100 text-purple-700">🚗 Sedang Berlangsung</span>'),
                            'completed'       => new \Illuminate\Support\HtmlString('<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold bg-gray-100 text-gray-700">🏁 Selesai</span>'),
                            'cancelled'       => new \Illuminate\Support\HtmlString('<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold bg-red-100 text-red-700">❌ Dibatalkan</span>'),
                            default           => '—',
                        }),

                    Forms\Components\Placeholder::make('status_action_hint')
                        ->label('Tindakan')
                        ->content(fn ($record) => match($record?->status) {
                            'awaiting_vendor' => new \Illuminate\Support\HtmlString('<p class="text-sm text-blue-600">👆 Klik tombol <strong>"✅ Konfirmasi Pesanan"</strong> di atas untuk menyetujui, atau <strong>"❌ Tolak"</strong> untuk menolak.</p>'),
                            'confirmed'       => new \Illuminate\Support\HtmlString('<p class="text-sm text-green-600">👆 Klik tombol <strong>"🚗 Tandai Berlangsung"</strong> di atas setelah mobil diserahkan ke customer.</p>'),
                            'ongoing'         => new \Illuminate\Support\HtmlString('<p class="text-sm text-purple-600">👆 Klik tombol <strong>"🏁 Tandai Selesai"</strong> di atas setelah mobil dikembalikan oleh customer.</p>'),
                            'completed'       => new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-500">Pesanan ini sudah selesai. Payout akan diproses secara otomatis.</p>'),
                            'cancelled'       => new \Illuminate\Support\HtmlString('<p class="text-sm text-red-500">Pesanan ini sudah dibatalkan dan tidak dapat diubah.</p>'),
                            default           => '',
                        }),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Penyewa')
                    ->searchable()
                    ->description(fn (Booking $record) => $record->customer?->user?->phone ?? '—'),

                Tables\Columns\TextColumn::make('car.brand')
                    ->label('Mobil')
                    ->formatStateUsing(fn ($state, Booking $record) => $record->car->brand . ' ' . $record->car->model)
                    ->description(fn (Booking $record) => $record->car->plate_number ?? ''),

                Tables\Columns\TextColumn::make('start_at')
                    ->label('Mulai')
                    ->dateTime('d M Y')
                    ->description(fn (Booking $record) => $record->end_at->format('d M Y')),

                Tables\Columns\TextColumn::make('pickup_location')
                    ->label('Lokasi Pickup')
                    ->limit(30)
                    ->tooltip(fn (Booking $record) => $record->pickup_location),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'awaiting_payment'       => 'warning',
                        'awaiting_vendor'        => 'info',
                        'confirmed', 'completed' => 'success',
                        'cancelled'              => 'danger',
                        'ongoing'                => 'primary',
                        default                  => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'awaiting_payment' => 'Menunggu Bayar',
                        'awaiting_vendor'  => 'Menunggu Konfirmasi',
                        'confirmed'        => 'Dikonfirmasi',
                        'ongoing'          => 'Berlangsung',
                        'completed'        => 'Selesai',
                        'cancelled'        => 'Dibatalkan',
                        default            => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('carChangeRequest.status')
                    ->label('Ganti Mobil')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state, Booking $record): string => match ($state) {
                        'pending'  => '🔄 Minta Ganti (' . ($record->carChangeRequest?->passenger_count ?? '') . ' pnp)',
                        'approved' => '✅ Disetujui',
                        'rejected' => '❌ Ditolak',
                        default    => '—',
                    }),

                Tables\Columns\IconColumn::make('is_late')
                    ->label('Terlambat')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('lateReturnReport.status')
                    ->label('Lap. Terlambat')
                    ->options([
                        'heroicon-o-exclamation-triangle' => 'reported',
                        'heroicon-o-check-circle'         => 'acknowledged',
                    ])
                    ->colors([
                        'warning' => 'reported',
                        'success' => 'acknowledged',
                    ])
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'awaiting_payment' => 'Menunggu Bayar',
                        'awaiting_vendor'  => 'Menunggu Konfirmasi',
                        'confirmed'        => 'Dikonfirmasi',
                        'ongoing'          => 'Berlangsung',
                        'completed'        => 'Selesai',
                        'cancelled'        => 'Dibatalkan',
                    ]),
            ])
            ->recordUrl(fn (Booking $record) => static::getUrl('edit', ['record' => $record]))
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'edit'  => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
