<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BookingResource\Pages;
use App\Filament\Exports\BookingExporter;
use App\Models\Booking;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationLabel = 'Pemesanan';

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-calendar';
    }

    public static function getNavigationBadge(): ?string
    {
        // Bukti transfer awal yang belum diverifikasi
        $pendingInitialPayments = Booking::whereHas('payment', fn ($q) => $q->where('status', 'pending'))
            ->where('status', 'awaiting_payment')
            ->count();

        // Bukti bayar selisih ganti mobil yang belum diverifikasi admin
        $pendingCarChangeProofs = \App\Models\CarChangeRequest::whereNotNull('additional_payment_proof')
            ->whereNull('additional_payment_at')
            ->count();

        $total = $pendingInitialPayments + $pendingCarChangeProofs;
        return $total > 0 ? (string) $total : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // ── HIGHLIGHT ALERT: Bukti Bayar Selisih Ganti Mobil Pending (Paling Atas) ──
            Section::make('💳 BUKTI BAYAR SELISIH MOBIL MENUNGGU VERIFIKASI ADMIN')
                ->description('Customer telah mengunggah bukti bayar selisih harga ganti mobil. Mohon periksa dan konfirmasi.')
                ->visible(fn ($record) => $record?->carChangeRequest?->additional_payment_proof !== null && $record?->carChangeRequest?->additional_payment_at === null)
                ->components([
                    Forms\Components\Placeholder::make('admin_car_change_alert')
                        ->hiddenLabel()
                        ->content(fn ($record) => new HtmlString('
                            <div class="bg-blue-50 border-2 border-blue-400 rounded-xl p-4 space-y-3">
                                <div class="flex items-center justify-between border-b border-blue-200 pb-2">
                                    <p class="font-bold text-base text-blue-900 flex items-center gap-2">
                                        <span>💳</span> Bukti Transfer Selisih Harga (Rp ' . number_format($record->carChangeRequest?->price_difference ?? 0, 0, ',', '.') . ')
                                    </p>
                                    <a href="' . asset('storage/' . $record->carChangeRequest?->additional_payment_proof) . '" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1">
                                        🔍 Lihat Foto Bukti Transfer
                                    </a>
                                </div>
                                <div class="text-xs text-blue-900 space-y-1">
                                    <p>Mobil Pengganti: <strong>' . ($record->carChangeRequest?->newCar ? $record->carChangeRequest->newCar->brand . ' ' . $record->carChangeRequest->newCar->model : '—') . '</strong></p>
                                    <p>👉 <strong>Tindakan Admin:</strong> Gunakan tombol <strong>"✅ Konfirmasi Bayar Selisih Mobil"</strong> di kanan atas halaman ini untuk memperbarui data booking secara resmi.</p>
                                </div>
                            </div>
                        ')),
                ]),

            // ── Info Penyewa ──────────────────────────────────────────
            Section::make('👤 Informasi Penyewa')
                ->columns(3)
                ->components([
                    Forms\Components\Placeholder::make('customer_name')
                        ->label('Nama Lengkap')
                        ->content(fn ($record) => $record?->customer?->full_name ?? '—'),
                    Forms\Components\Placeholder::make('customer_email')
                        ->label('Email')
                        ->content(fn ($record) => $record?->customer?->user?->email ?? '—'),
                    Forms\Components\Placeholder::make('customer_phone')
                        ->label('No. WhatsApp')
                        ->content(fn ($record) => $record?->customer?->user?->phone ?? '—'),
                ]),

            // ── Info Vendor ───────────────────────────────────────────
            Section::make('🏢 Vendor')
                ->columns(2)
                ->components([
                    Forms\Components\Placeholder::make('vendor_name')
                        ->label('Nama Bisnis')
                        ->content(fn ($record) => $record?->vendor?->business_name ?? '—'),
                    Forms\Components\Placeholder::make('vendor_phone')
                        ->label('Kontak Vendor')
                        ->content(fn ($record) => $record?->vendor?->user?->phone ?? '—'),
                ]),

            // ── Info Mobil ────────────────────────────────────────────
            Section::make('🚗 Informasi Mobil')
                ->columns(3)
                ->components([
                    Forms\Components\Placeholder::make('car_name')
                        ->label('Mobil')
                        ->content(fn ($record) => $record?->car
                            ? $record->car->brand.' '.$record->car->model.' ('.$record->car->year.')'
                            : '—'),
                    Forms\Components\Placeholder::make('car_plate')
                        ->label('Plat Nomor')
                        ->content(fn ($record) => $record?->car?->plate_number ?? '—'),
                    Forms\Components\Placeholder::make('with_driver')
                        ->label('Dengan Sopir')
                        ->content(fn ($record) => $record?->with_driver ? '✅ Ya' : '❌ Tidak'),
                    Forms\Components\Placeholder::make('driver_info')
                        ->label('Sopir')
                        ->content(fn ($record) => $record?->driver
                            ? new HtmlString(
                                '<strong>'.e($record->driver->name).'</strong>'
                                .' | 📱 '.e($record->driver->phone)
                            )
                            : ($record?->with_driver ? '⚠️ Belum ditugaskan' : '— Lepas kunci'))
                        ->columnSpanFull(),
                ]),

            // ── Detail Pemesanan ──────────────────────────────────────
            Section::make('📋 Detail Pemesanan')
                ->columns(2)
                ->components([
                    Forms\Components\Placeholder::make('booking_code')
                        ->label('Kode Booking')
                        ->content(fn ($record) => $record?->code ?? '—'),
                    Forms\Components\Placeholder::make('booking_duration')
                        ->label('Durasi')
                        ->content(fn ($record) => $record?->start_at && $record?->end_at
                            ? $record->start_at->format('d M Y').' → '.$record->end_at->format('d M Y')
                              .' ('.$record->start_at->diffInDays($record->end_at).' hari)'
                            : '—'),
                    Forms\Components\Placeholder::make('passenger_count')
                        ->label('Jumlah Penumpang')
                        ->content(fn ($record) => $record?->passenger_count
                            ? '👥 ' . $record->passenger_count . ' orang'
                            : '1 orang'),
                    Forms\Components\Placeholder::make('pickup_location')
                        ->label('Lokasi Pickup')
                        ->content(fn ($record) => $record?->pickup_location ?? '—')
                        ->columnSpanFull(),
                ]),

            // ── Permintaan Ganti Mobil (Car Upgrade Request) ──────────────
            Section::make('🔄 Permintaan Ganti Mobil')
                ->description('Pengajuan pergantian mobil dari customer karena kapasitas penumpang')
                ->visible(fn ($record) => (bool) $record?->carChangeRequest)
                ->columns(2)
                ->components([
                    Forms\Components\Placeholder::make('cr_status')
                        ->label('Status Permintaan')
                        ->content(fn ($record) => $record?->carChangeRequest
                            ? new HtmlString(
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
                        ->label('Jumlah Penumpang')
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

                    Forms\Components\Placeholder::make('cr_payment_proof')
                        ->label('Bukti Transfer Selisih')
                        ->content(function ($record) {
                            $path = $record?->carChangeRequest?->additional_payment_proof;
                            if (!$path) return new HtmlString('<span style="color:#9ca3af;">— Belum ada bukti transfer selisih</span>');

                            $url = e(asset('storage/' . $path));
                            return new HtmlString(
                                '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">'
                                . '<img src="' . $url . '" style="max-width:250px;max-height:180px;border-radius:8px;border:1px solid #e5e7eb;">'
                                . '<br><small style="color:#2563eb;">🔍 Klik untuk lihat ukuran penuh</small>'
                                . '</a>'
                            );
                        })
                        ->columnSpanFull(),
                ]),

            // ── Bukti Pembayaran ──────────────────────────────────────
            Section::make('💳 Bukti Pembayaran')
                ->components([
                    Forms\Components\Placeholder::make('payment_method')
                        ->label('Metode Pembayaran')
                        ->content(fn ($record) => $record?->payment?->method ?? '—'),

                    Forms\Components\Placeholder::make('payment_status')
                        ->label('Status Pembayaran')
                        ->content(fn ($record) => match ($record?->payment?->status) {
                            'paid' => '✅ Sudah Dibayar',
                            'pending' => '⏳ Menunggu Konfirmasi',
                            'failed' => '❌ Gagal',
                            'refunded' => '💸 Refund',
                            default => '— Belum Ada Pembayaran',
                        }),

                    Forms\Components\Placeholder::make('sender_name')
                        ->label('Nama Pengirim')
                        ->content(fn ($record) => $record?->payment?->sender_name ?? '—'),

                    Forms\Components\Placeholder::make('payment_amount')
                        ->label('Jumlah Transfer')
                        ->content(fn ($record) => $record?->payment?->amount
                            ? 'Rp '.number_format($record->payment->amount, 0, ',', '.')
                            : '—'),

                    Forms\Components\Placeholder::make('payment_proof')
                        ->label('Foto Bukti Transfer')
                        ->content(function ($record) {
                            $path = $record?->payment?->payment_proof;
                            if (! $path) {
                                return new HtmlString('<span style="color:#9ca3af;">— Belum ada bukti transfer</span>');
                            }

                            $url = e(asset('storage/' . $path));

                            return new HtmlString(
                                '<a href="'.$url.'" target="_blank" rel="noopener noreferrer">'
                                .'<img src="'.$url.'" alt="Bukti transfer" '
                                .'style="max-width:300px; max-height:200px; border-radius:8px; border:1px solid #e5e7eb; cursor:pointer;" '
                                .'title="Klik untuk lihat ukuran penuh">'
                                .'<br><small style="color:#2563eb;">🔍 Klik untuk lihat ukuran penuh</small>'
                                .'</a>'
                            );
                        })
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible(fn ($record) => $record !== null),

            // ── Rincian Pembayaran ────────────────────────────────────
            Section::make('💰 Rincian Pembayaran')
                ->columns(2)
                ->components([
                    Forms\Components\Placeholder::make('subtotal')
                        ->label('Subtotal')
                        ->content(fn ($record) => $record?->subtotal
                            ? 'Rp '.number_format($record->subtotal, 0, ',', '.')
                            : '—'),
                    Forms\Components\Placeholder::make('platform_fee')
                        ->label('Komisi Platform')
                        ->content(fn ($record) => $record?->platform_fee
                            ? 'Rp '.number_format($record->platform_fee, 0, ',', '.')
                            : '—'),
                    Forms\Components\Placeholder::make('total')
                        ->label('Total Dibayar Customer')
                        ->content(fn ($record) => $record?->total
                            ? 'Rp '.number_format($record->total, 0, ',', '.')
                            : '—'),
                    Forms\Components\Placeholder::make('vendor_payout')
                        ->label('Payout Vendor')
                        ->content(fn ($record) => $record?->vendor_payout_amount
                            ? 'Rp '.number_format($record->vendor_payout_amount, 0, ',', '.')
                            : '—'),
                ]),

            // ── Info Refund (muncul saat booking cancelled & sudah ada pembayaran) ──
            Section::make('💸 Info Refund ke Customer')
                ->columns(3)
                ->components([
                    Forms\Components\Placeholder::make('refund_amount')
                        ->label('Jumlah Refund')
                        ->content(fn ($record) => $record?->payment?->amount
                            ? 'Rp '.number_format($record->payment->amount, 0, ',', '.')
                            : '—'),
                    Forms\Components\Placeholder::make('refund_bank')
                        ->label('Bank Tujuan')
                        ->content(fn ($record) => $record?->customer?->bank_name
                            ? $record->customer->bank_name
                            : new HtmlString('<span class="text-red-500 font-medium">⚠️ Belum diisi customer</span>')),
                    Forms\Components\Placeholder::make('refund_account_no')
                        ->label('No. Rekening')
                        ->content(fn ($record) => $record?->customer?->bank_account_no
                            ? $record->customer->bank_account_no
                            : new HtmlString('<span class="text-red-500 font-medium">⚠️ Belum diisi customer</span>')),
                    Forms\Components\Placeholder::make('refund_account_name')
                        ->label('Atas Nama')
                        ->content(fn ($record) => $record?->customer?->bank_account_name
                            ? $record->customer->bank_account_name
                            : new HtmlString('<span class="text-red-500 font-medium">⚠️ Belum diisi customer</span>')),
                    Forms\Components\Placeholder::make('refund_contact')
                        ->label('Kontak Customer')
                        ->content(fn ($record) => $record?->customer?->user?->phone
                            ? $record->customer->user->phone
                            : ($record?->customer?->user?->email ?? '—')),
                    Forms\Components\Placeholder::make('refund_note')
                        ->label('Catatan')
                        ->content(fn ($record) => match ($record?->payment?->status) {
                            'refunded' => new HtmlString(
                                '<span class="text-green-700 font-medium">✅ Refund sudah diproses pada '
                                .($record->payment->refunded_at?->format('d M Y H:i') ?? '—')
                                .($record->payment->refund_ref ? ' | Ref: '.e($record->payment->refund_ref) : '')
                                .'</span>'
                            ),
                            default => $record?->customer?->bank_account_no
                                ? new HtmlString('<span class="text-green-700">✅ Info rekening tersedia. Silakan proses transfer refund lalu klik tombol "💸 Tandai Sudah Direfund" di atas.</span>')
                                : new HtmlString('<span class="text-orange-600">Hubungi customer via WhatsApp/email untuk mendapatkan nomor rekening refund.</span>'),
                        }),
                ])
                ->visible(fn ($record) => $record?->status === 'cancelled' && in_array($record?->payment?->status, ['paid', 'refunded'])),

            // ── Info Keterlambatan (muncul jika booking completed & terlambat) ──
            Section::make('⚠️ Info Keterlambatan')
                ->columns(3)
                ->components([
                    Forms\Components\Placeholder::make('actual_return_at')
                        ->label('Waktu Pengembalian Aktual')
                        ->content(fn ($record) => $record?->actual_return_at?->format('d M Y, H:i') ?? '—'),
                    Forms\Components\Placeholder::make('late_duration_hours')
                        ->label('Durasi Terlambat')
                        ->content(fn ($record) => $record?->late_duration_hours
                            ? $record->late_duration_hours.' jam'
                            : '—'),
                    Forms\Components\Placeholder::make('late_fee')
                        ->label('Denda')
                        ->content(fn ($record) => $record?->late_fee > 0
                            ? 'Rp '.number_format($record->late_fee, 0, ',', '.')
                            : '—'),
                ])
                ->visible(fn ($record) => $record?->status === 'completed' && $record?->is_late),

            // ── Laporan Keterlambatan dari Customer/Sopir ─────────────────────
            Section::make('📋 Laporan Keterlambatan')
                ->description('Laporan yang disampaikan customer atau sopir sebelum pengembalian')
                ->columns(2)
                ->components([
                    Forms\Components\Placeholder::make('lr_reporter_type')
                        ->label('Dilaporkan Oleh')
                        ->content(fn ($record) => $record?->lateReturnReport
                            ? new HtmlString(
                                '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold '
                                .($record->lateReturnReport->reporter_type === 'customer'
                                    ? 'bg-blue-100 text-blue-700'
                                    : 'bg-purple-100 text-purple-700')
                                .'">'
                                .e($record->lateReturnReport->reporterLabel())
                                .'</span>'
                            )
                            : '—'),

                    Forms\Components\Placeholder::make('lr_status')
                        ->label('Status Laporan')
                        ->content(fn ($record) => match ($record?->lateReturnReport?->status) {
                            'reported' => new HtmlString('<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">⏳ Belum Diakui Vendor</span>'),
                            'acknowledged' => new HtmlString('<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">✅ Vendor Sudah Melihat</span>'),
                            default => '—',
                        }),

                    Forms\Components\Placeholder::make('lr_estimated_hours')
                        ->label('Perkiraan Terlambat')
                        ->content(fn ($record) => $record?->lateReturnReport?->estimated_late_hours
                            ? '~'.$record->lateReturnReport->estimated_late_hours.' jam'
                            : '—'),

                    Forms\Components\Placeholder::make('lr_reported_at')
                        ->label('Waktu Laporan')
                        ->content(fn ($record) => $record?->lateReturnReport?->created_at?->format('d M Y, H:i') ?? '—'),

                    Forms\Components\Placeholder::make('lr_reason')
                        ->label('Alasan')
                        ->content(fn ($record) => $record?->lateReturnReport?->reason ?? '—')
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('lr_location')
                        ->label('Lokasi GPS Saat Lapor')
                        ->content(fn ($record) => $record?->lateReturnReport?->hasLocation()
                            ? new HtmlString(
                                '<div>'
                                .'<a href="'.e($record->lateReturnReport->getMapUrl()).'" target="_blank" rel="noopener noreferrer" '
                                .'style="color:#2563eb;text-decoration:underline;font-size:13px;">📍 '
                                .e($record->lateReturnReport->location_address ?? $record->lateReturnReport->latitude.', '.$record->lateReturnReport->longitude)
                                .' ↗</a>'
                                .'<div style="margin-top:8px;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb;">'
                                .'<iframe width="100%" height="200" frameborder="0" scrolling="no" '
                                .'src="https://www.openstreetmap.org/export/embed.html'
                                .'?bbox='.($record->lateReturnReport->longitude - 0.005).'%2C'
                                .($record->lateReturnReport->latitude - 0.005).'%2C'
                                .($record->lateReturnReport->longitude + 0.005).'%2C'
                                .($record->lateReturnReport->latitude + 0.005)
                                .'&layer=mapnik&marker='.$record->lateReturnReport->latitude.'%2C'.$record->lateReturnReport->longitude
                                .'" style="border:0;"></iframe></div></div>'
                            )
                            : new HtmlString(
                                '<span style="color:#f59e0b;font-size:12px;font-weight:600;">⚠️ Tidak ada GPS</span>'
                            ))
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('lr_return_location')
                        ->label('Lokasi GPS Pengembalian (Opsi B)')
                        ->content(fn ($record) => $record?->lateReturnReport?->hasReturnLocation()
                            ? new HtmlString(
                                '<div>'
                                .'<a href="'.e($record->lateReturnReport->getReturnMapUrl()).'" target="_blank" rel="noopener noreferrer" '
                                .'style="color:#2563eb;text-decoration:underline;font-size:13px;">📍 '
                                .e($record->lateReturnReport->return_location_address ?? $record->lateReturnReport->return_latitude.', '.$record->lateReturnReport->return_longitude)
                                .' ↗</a>'
                                .'<p style="font-size:11px;color:#6b7280;margin-top:2px;">Dikonfirmasi: '
                                .$record->lateReturnReport->return_confirmed_at?->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB</p>'
                                .'<div style="margin-top:8px;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb;">'
                                .'<iframe width="100%" height="200" frameborder="0" scrolling="no" '
                                .'src="https://www.openstreetmap.org/export/embed.html'
                                .'?bbox='.($record->lateReturnReport->return_longitude - 0.005).'%2C'
                                .($record->lateReturnReport->return_latitude - 0.005).'%2C'
                                .($record->lateReturnReport->return_longitude + 0.005).'%2C'
                                .($record->lateReturnReport->return_latitude + 0.005)
                                .'&layer=mapnik&marker='.$record->lateReturnReport->return_latitude.'%2C'.$record->lateReturnReport->return_longitude
                                .'" style="border:0;"></iframe></div></div>'
                            )
                            : new HtmlString(
                                '<span style="color:#9ca3af;font-size:12px;">— Belum ada konfirmasi pengembalian</span>'
                            ))
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => $record?->lateReturnReport !== null),

            // ── Tagihan Denda (muncul jika ada LateFeeCharge) ──────────────
            Section::make('💸 Tagihan Denda Keterlambatan')
                ->columns(2)
                ->components([
                    Forms\Components\Placeholder::make('charge_status')
                        ->label('Status Tagihan')
                        ->content(fn ($record) => $record?->lateFeeCharge?->statusLabel() ?? '—'),

                    Forms\Components\Placeholder::make('charge_amount')
                        ->label('Jumlah Denda')
                        ->content(fn ($record) => $record?->lateFeeCharge?->amount
                            ? new HtmlString(
                                '<span style="font-size:1.1rem;font-weight:700;color:#dc2626;">Rp '
                                .number_format($record->lateFeeCharge->amount, 0, ',', '.')
                                .'</span>'
                            )
                            : '—'),

                    Forms\Components\Placeholder::make('charge_bank')
                        ->label('Rekening Tujuan (Vendor)')
                        ->content(fn ($record) => $record?->lateFeeCharge
                            ? ($record->lateFeeCharge->bank_name ?? '—')
                              .' · '.($record->lateFeeCharge->bank_account_no ?? '—')
                              .' a.n. '.($record->lateFeeCharge->bank_account_name ?? '—')
                            : '—'),

                    Forms\Components\Placeholder::make('charge_proof')
                        ->label('Bukti Bayar Customer')
                        ->content(function ($record) {
                            $charge = $record?->lateFeeCharge;
                            if (! $charge?->payment_proof) {
                                return new HtmlString('<span style="color:#9ca3af;">Belum ada bukti</span>');
                            }

                            $url = e(asset('storage/' . $charge->payment_proof));
                            $uploadedAt = $charge->proof_uploaded_at?->format('d M Y H:i') ?? '—';

                            return new HtmlString(
                                '<a href="'.$url.'" target="_blank" rel="noopener noreferrer">'
                                .'<img src="'.$url.'" alt="Bukti pembayaran denda" '
                                .'style="max-width:200px;border-radius:8px;border:1px solid #e5e7eb;cursor:pointer;">'
                                .'<br><small style="color:#2563eb;">🔍 Klik untuk perbesar</small>'
                                .'</a>'
                                .'<p style="font-size:11px;color:#6b7280;margin-top:4px;">Diupload: '
                                .$uploadedAt
                                .'</p>'
                            );
                        }),

                    Forms\Components\Placeholder::make('charge_hint')
                        ->label('')
                        ->columnSpanFull()
                        ->content(fn ($record) => match ($record?->lateFeeCharge?->status) {
                            'pending' => new HtmlString(
                                '<div style="background:#fef9c3;border:1px solid #fde68a;border-radius:8px;padding:12px;font-size:13px;color:#92400e;">'
                                .'⏳ Tagihan denda menunggu konfirmasi Anda. Klik tombol <strong>"💸 Konfirmasi Tagihan Denda"</strong> di atas untuk mengirim notifikasi ke customer.'
                                .'</div>'
                            ),
                            'confirmed' => new HtmlString(
                                '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px;font-size:13px;color:#1e40af;">'
                                .'🔔 Customer sudah ditagih. Tunggu customer upload bukti, lalu klik <strong>"✅ Denda Lunas"</strong>.'
                                .'</div>'
                            ),
                            'paid' => new HtmlString(
                                '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;font-size:13px;color:#15803d;">'
                                .'✅ Denda sudah dibayar dan dikonfirmasi lunas.'
                                .'</div>'
                            ),
                            'waived' => new HtmlString(
                                '<div style="background:#f3f4f6;border:1px solid #d1d5db;border-radius:8px;padding:12px;font-size:13px;color:#374151;">'
                                .'🎁 Denda dibebaskan. Alasan: <strong>'.e($record->lateFeeCharge->waive_reason ?? '—').'</strong>'
                                .'</div>'
                            ),
                            default => null,
                        }),
                ])
                ->visible(fn ($record) => $record?->lateFeeCharge !== null),

            // ── Status & Catatan ─────────────────────────────────────
            Section::make('🔄 Status Booking')
                ->components([
                    // Status hanya tampil sebagai info — diubah via tombol aksi di atas
                    Forms\Components\Placeholder::make('status_display')
                        ->label('Status Saat Ini')
                        ->content(fn ($record) => match ($record?->status) {
                            'awaiting_payment' => '⏳ Menunggu Pembayaran Customer',
                            'awaiting_vendor' => '🔔 Menunggu Konfirmasi Vendor',
                            'confirmed' => '✅ Dikonfirmasi Vendor',
                            'ongoing' => '🚗 Sedang Berlangsung',
                            'completed' => '🏁 Selesai',
                            'cancelled' => '❌ Dibatalkan',
                            'refunded' => '💸 Refund',
                            'disputed' => '⚠️ Sengketa',
                            default => ucfirst($record?->status ?? '—'),
                        }),

                    Forms\Components\Placeholder::make('status_hint')
                        ->label('')
                        ->content(function ($record) {
                            if ($record?->status === 'awaiting_payment') {
                                $hasProof = $record->payment?->payment_proof !== null;
                                if ($hasProof) {
                                    return new HtmlString(
                                        '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-800">'
                                        .'📎 Customer sudah upload bukti transfer. Silakan cek dan klik <strong>"✅ Konfirmasi Pembayaran"</strong> di atas jika sudah sesuai.'
                                        .'</div>'
                                    );
                                }
                                $deadline = $record->created_at->copy()->addDay();
                                $remaining = now()->diff($deadline);
                                $isExpired = now()->gt($deadline);

                                return new HtmlString(
                                    '<div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-600">'
                                    .'⏳ Menunggu customer upload bukti transfer.'
                                    .($isExpired
                                        ? ' <span class="text-red-600 font-medium">Batas waktu sudah lewat — akan di-cancel otomatis oleh sistem.</span>'
                                        : ' Batas waktu: <strong>'.$deadline->format('d M Y H:i').'</strong> (sisa '.$remaining->h.' jam '.$remaining->i.' menit).'
                                    )
                                    .'</div>'
                                );
                            }

                            return match ($record?->status) {
                                'awaiting_vendor' => new HtmlString('<div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-700">✅ Pembayaran sudah dikonfirmasi. Menunggu vendor konfirmasi pesanan ke customer.</div>'),
                                'confirmed' => new HtmlString('<div class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-700">✅ Vendor sudah konfirmasi. Pesanan aktif berjalan.</div>'),
                                'ongoing' => new HtmlString('<div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-700">🚗 Mobil sedang digunakan customer.</div>'),
                                'completed' => new HtmlString('<div class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-700">🏁 Pesanan selesai. Payout akan diproses ke vendor.</div>'),
                                'cancelled' => new HtmlString('<div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">❌ Pesanan ini sudah dibatalkan dan tidak dapat diubah.</div>'),
                                default => new HtmlString(''),
                            };
                        })
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan Admin')
                        ->placeholder('Catatan internal (tidak terlihat customer)')
                        ->disabled()
                        ->helperText('Catatan ditampilkan sebagai informasi karena halaman detail tidak memiliki aksi simpan.')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                // Eager load semua relasi untuk menghindari N+1 query
                return $query->with([
                    'customer.user',
                    'vendor.user',
                    'car',
                    'driver',
                    'payment',
                    'lateFeeCharge',
                    'lateReturnReport',
                    'refund',
                ]);
            })
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Booking')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Kode booking disalin!'),
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Penyewa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('car.brand')
                    ->label('Mobil')
                    ->formatStateUsing(fn ($state, Booking $record) => $record->car
                        ? trim($record->car->brand.' '.$record->car->model)
                        : '—')
                    ->searchable(['brand', 'model']),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'awaiting_payment' => 'warning',
                        'awaiting_vendor' => 'info',
                        'confirmed', 'completed' => 'success',
                        'cancelled', 'disputed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'awaiting_payment' => '⏳ Menunggu Pembayaran',
                        'awaiting_vendor' => '🔔 Menunggu Vendor',
                        'confirmed' => '✅ Dikonfirmasi',
                        'ongoing' => '🚗 Berlangsung',
                        'completed' => '🏁 Selesai',
                        'cancelled' => '❌ Dibatalkan',
                        'refunded' => '💸 Refund',
                        'disputed' => '⚠️ Sengketa',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('start_at')
                    ->label('Tanggal Mulai')
                    ->dateTime('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment.status')
                    ->label('Pembayaran')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'paid' => '✅ Lunas',
                        'pending' => '⏳ Pending',
                        'failed' => '❌ Gagal',
                        'refunded' => '💸 Refund',
                        default => '—',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Booking')
                    ->options([
                        'awaiting_payment' => '⏳ Menunggu Pembayaran',
                        'awaiting_vendor' => '🔔 Menunggu Konfirmasi Vendor',
                        'confirmed' => '✅ Dikonfirmasi',
                        'ongoing' => '🚗 Sedang Berlangsung',
                        'completed' => '🏁 Selesai',
                        'cancelled' => '❌ Dibatalkan',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->relationship('payment', 'status')
                    ->options([
                        'pending' => '⏳ Pending',
                        'paid' => '✅ Lunas',
                        'failed' => '❌ Gagal',
                        'refunded' => '💸 Refund',
                    ])
                    ->multiple(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Tanggal Booking Dari'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Booking dari '.Carbon::parse($data['created_from'])->format('d M Y');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Booking sampai '.Carbon::parse($data['created_until'])->format('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Detail'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Export Excel')
                        ->exporter(BookingExporter::class),
                ]),
            ])
            ->recordUrl(fn (Booking $record) => static::getUrl('edit', ['record' => $record]))
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
