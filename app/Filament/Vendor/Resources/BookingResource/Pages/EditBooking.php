<?php

namespace App\Filament\Vendor\Resources\BookingResource\Pages;

use App\Filament\Vendor\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        $status = $this->record?->status;
        if (in_array($status, ['completed', 'cancelled'])) {
            return 'Riwayat Booking';
        }
        return 'Detail Booking';
    }

    public function getBreadcrumb(): string
    {
        $status = $this->record?->status;
        if (in_array($status, ['completed', 'cancelled'])) {
            return 'Riwayat Booking';
        }
        return 'Detail Booking';
    }

    // Sembunyikan tombol Simpan dan Batal — semua aksi via header actions
    protected function getFormActions(): array
    {
        return [];
    }

    // Status yang sudah final — tidak boleh diubah oleh siapapun
    const FINAL_STATUSES = ['cancelled', 'completed', 'awaiting_payment'];

    // Transisi yang diizinkan untuk vendor
    const ALLOWED_TRANSITIONS = [
        'awaiting_vendor' => ['awaiting_vendor', 'confirmed', 'cancelled'],
        'confirmed'       => ['confirmed', 'ongoing', 'cancelled'],
        'ongoing'         => ['ongoing', 'completed'],
    ];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $currentStatus = $this->record->status;
        $newStatus     = $data['status'] ?? $currentStatus;

        // Jika status sudah final, tolak perubahan apapun
        if (in_array($currentStatus, self::FINAL_STATUSES)) {
            Notification::make()
                ->title('Tidak dapat mengubah status')
                ->body('Booking dengan status "' . $currentStatus . '" sudah final dan tidak dapat diubah.')
                ->danger()
                ->send();
            $this->halt();
        }

        // Validasi transisi yang diizinkan
        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];
        if (!in_array($newStatus, $allowed)) {
            Notification::make()
                ->title('Perubahan status tidak diizinkan')
                ->body('Tidak dapat mengubah status dari "' . $currentStatus . '" ke "' . $newStatus . '".')
                ->danger()
                ->send();
            $this->halt();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        // Ownership check — pastikan booking ini milik vendor yang login
        $vendor = auth('vendor')->user()?->vendor;
        if (!$vendor || $this->record->vendor_id !== $vendor->id) {
            return [
                Action::make('unauthorized')
                    ->label('Akses Ditolak')
                    ->color('danger')
                    ->disabled(),
            ];
        }

        return [
            // Konfirmasi pesanan (awaiting_vendor → confirmed)
            Action::make('confirm')
                ->label('✅ Konfirmasi Pesanan')
                ->color('success')
                ->modalHeading('Konfirmasi Pesanan')
                ->modalDescription(null)
                ->form(function () {
                    $vendor  = auth('vendor')->user()?->vendor;
                    $booking = $this->record;
                    // Ambil sopir aktif vendor yang tersedia di tanggal booking ini
                    $drivers = \App\Models\Driver::where('vendor_id', $vendor?->id)
                        ->where('status', 'active')
                        ->get()
                        ->filter(fn ($d) => $d->isAvailableOn($booking->start_at, $booking->end_at, $booking->id))
                        ->pluck('name', 'id')
                        ->toArray();

                    $fields = [
                        \Filament\Forms\Components\Placeholder::make('confirm_info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<p class="text-sm text-gray-600">Apakah Anda yakin ingin mengkonfirmasi pesanan ini? Customer akan mendapat notifikasi.</p>'
                            )),
                    ];

                    // Tampilkan pilihan sopir hanya jika booking with_driver
                    if ($this->record->with_driver) {
                        if (!empty($drivers)) {
                            $fields[] = \Filament\Forms\Components\Select::make('driver_id')
                                ->label('Tugaskan Sopir')
                                ->options($drivers)
                                ->default($this->record->driver_id)
                                ->required()
                                ->helperText('Pilih sopir yang akan bertugas untuk booking ini.');
                        } else {
                            $fields[] = \Filament\Forms\Components\Placeholder::make('no_driver_warning')
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString(
                                    '<div class="bg-orange-50 border border-orange-200 rounded-lg p-3 text-sm text-orange-700">'
                                    . '⚠️ Tidak ada sopir aktif yang tersedia untuk tanggal ini. '
                                    . 'Tambahkan sopir terlebih dahulu di menu <strong>Data Sopir</strong>.'
                                    . '</div>'
                                ));
                        }
                    }

                    return $fields;
                })
                ->visible(fn () => $this->record->status === 'awaiting_vendor')
                ->action(function (array $data) {
                    // Assign sopir jika ada
                    if ($this->record->with_driver && !empty($data['driver_id'])) {
                        $this->record->update(['driver_id' => $data['driver_id']]);
                    }
                    app(BookingService::class)->confirmByVendor($this->record);
                    Notification::make()
                        ->title('Pesanan dikonfirmasi')
                        ->success()
                        ->send();
                    $this->redirect(BookingResource::getUrl('index'));
                }),

            // ── Setujui Permintaan Ganti Mobil ──
            Action::make('approve_car_change')
                ->label('✅ Setujui Ganti Mobil')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => $this->record->carChangeRequest?->status === 'pending')
                ->modalHeading('Setujui Permintaan Ganti Mobil')
                ->modalDescription('Pilih mobil pengganti dari armada Anda yang berkapasitas sesuai. Sistem akan menghitung selisih harga secara otomatis.')
                ->form(function () {
                    $vendor  = auth('vendor')->user()?->vendor;
                    $booking = $this->record;
                    $req     = $booking->carChangeRequest;

                    // Ambil mobil vendor yang dipublikasikan dan kosong pada rentang tanggal booking (selain mobil saat ini)
                    $cars = \App\Models\Car::where('vendor_id', $vendor?->id)
                        ->where('status', 'published')
                        ->where('id', '!=', $booking->car_id)
                        ->with('pricing')
                        ->get()
                        ->filter(fn ($car) => $car->isAvailableOn($booking->start_at, $booking->end_at))
                        ->mapWithKeys(function ($car) {
                            $price = $car->pricing?->daily_price ? ' (Rp ' . number_format($car->pricing->daily_price, 0, ',', '.') . '/hari)' : '';
                            return [$car->id => $car->brand . ' ' . $car->model . ' (' . $car->seats . ' seat)' . $price];
                        })
                        ->toArray();

                    if (empty($cars)) {
                        return [
                            \Filament\Forms\Components\Placeholder::make('no_car_warn')
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString(
                                    '<div class="bg-orange-50 border border-orange-200 rounded-lg p-3 text-sm text-orange-700">'
                                    . '⚠️ Anda tidak memiliki unit mobil lain yang dipublikasikan. Jika tidak ada armada pengganti, silakan pilih <strong>"❌ Tolak & Batalkan (Refund 50%)"</strong>.'
                                    . '</div>'
                                )),
                        ];
                    }

                    return [
                        \Filament\Forms\Components\Placeholder::make('info_req')
                            ->label('Detail Permintaan Customer')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div class="text-sm text-gray-700 bg-gray-50 border rounded-lg p-3 mb-2">'
                                . '👥 <strong>Jumlah Penumpang:</strong> ' . ($req?->passenger_count ?? 1) . ' orang<br>'
                                . '📝 <strong>Alasan:</strong> ' . e($req?->reason ?? '—')
                                . '</div>'
                            )),

                        \Filament\Forms\Components\Select::make('new_car_id')
                            ->label('Pilih Mobil Pengganti')
                            ->options($cars)
                            ->required()
                            ->searchable()
                            ->helperText('Pilih unit dengan kapasitas yang sesuai dengan kebutuhan penumpang customer.'),

                        \Filament\Forms\Components\Textarea::make('vendor_notes')
                            ->label('Catatan ke Customer (opsional)')
                            ->rows(2)
                            ->placeholder('Contoh: Kami ganti ke Toyota HiAce berkapasitas 12 seat...'),
                    ];
                })
                ->action(function (array $data) {
                    if (empty($data['new_car_id'])) return;

                    $changeReq = $this->record->carChangeRequest;
                    \App\Http\Controllers\Web\CarChangeRequestController::vendorApprove(
                        $changeReq,
                        (int) $data['new_car_id'],
                        $data['vendor_notes'] ?? null
                    );

                    Notification::make()
                        ->title('Permintaan ganti mobil disetujui')
                        ->body('Customer telah diberi notifikasi untuk melakukan pembayaran selisih (jika ada).')
                        ->success()
                        ->send();

                    $this->redirect(BookingResource::getUrl('edit', ['record' => $this->record]));
                }),

            // ── Tolak Permintaan Ganti Mobil (Batalkan + Refund 50%) ──
            Action::make('reject_car_change')
                ->label('❌ Tolak & Batalkan (Refund 50%)')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn () => $this->record->carChangeRequest?->status === 'pending')
                ->modalHeading('Tolak Permintaan & Batalkan Booking')
                ->modalDescription('Gunakan opsi ini jika armada Anda tidak memiliki mobil dengan kapasitas yang memadai. Pembagian dana: Customer mendapat refund 50%, Anda (vendor) mendapat kompensasi 40%, dan 10% masuk ke platform.')
                ->form([
                    \Filament\Forms\Components\Textarea::make('vendor_notes')
                        ->label('Alasan Penolakan / Catatan ke Customer')
                        ->required()
                        ->rows(3)
                        ->placeholder('Contoh: Maaf, armada kami yang berkapasitas 12 orang sedang tidak tersedia pada tanggal tersebut...'),
                ])
                ->action(function (array $data) {
                    $changeReq = $this->record->carChangeRequest;
                    \App\Http\Controllers\Web\CarChangeRequestController::vendorReject(
                        $changeReq,
                        $data['vendor_notes']
                    );

                    Notification::make()
                        ->title('Booking dibatalkan — Dana terbagi otomatis')
                        ->body('Customer: refund 50% | Anda: kompensasi 40% (Payout pending) | Platform: 10%.')
                        ->warning()
                        ->send();

                    $this->redirect(BookingResource::getUrl('edit', ['record' => $this->record]));
                }),

            // ── Acknowledge laporan keterlambatan ──
            Action::make('acknowledge_late_report')
                ->label('✅ Tandai Sudah Dilihat')
                ->color('info')
                ->icon('heroicon-o-eye')
                ->visible(fn () => $this->record->lateReturnReport?->status === 'reported')
                ->requiresConfirmation()
                ->modalHeading('Akui Laporan Keterlambatan')
                ->modalDescription(function () {
                    $report = $this->record->lateReturnReport;
                    $desc = 'Tandai bahwa Anda sudah menerima dan melihat laporan keterlambatan ini.';
                    if ($report) {
                        $extendedEndAt = (clone $this->record->end_at)->addHours((int) $report->estimated_late_hours);
                        $conflict = \App\Models\Booking::where('car_id', $this->record->car_id)
                            ->where('id', '!=', $this->record->id)
                            ->whereIn('status', ['confirmed', 'ongoing', 'awaiting_vendor'])
                            ->where('start_at', '<', $extendedEndAt)
                            ->where('end_at', '>', $this->record->end_at)
                            ->first();

                        if ($conflict) {
                            $desc .= " ⚠️ BENTROK JADWAL: Keterlambatan ini bentrok dengan booking (" . $conflict->code . ") atas nama " . ($conflict->customer?->full_name ?? 'Customer') . " pada " . $conflict->start_at?->format('d M Y H:i') . ". Segera siapkan unit pengganti!";
                        }
                    }
                    return $desc;
                })
                ->action(function () {
                    $this->record->lateReturnReport?->update([
                        'status'           => 'acknowledged',
                        'acknowledged_at'  => now(),
                    ]);
                    Notification::make()
                        ->title('Laporan berhasil diakui')
                        ->success()
                        ->send();
                    $this->redirect(BookingResource::getUrl('edit', ['record' => $this->record]));
                }),

            // Ganti/Assign Sopir (hanya untuk booking with_driver yang CONFIRMED saja)
            Action::make('assign_driver')
                ->label('🧑‍✈️ Ganti Sopir')
                ->color('info')
                ->modalHeading('Tugaskan / Ganti Sopir')
                ->form(function () {
                    $vendor  = auth('vendor')->user()?->vendor;
                    $booking = $this->record;
                    $drivers = \App\Models\Driver::where('vendor_id', $vendor?->id)
                        ->where('status', 'active')
                        ->get()
                        ->filter(fn ($d) => $d->isAvailableOn($booking->start_at, $booking->end_at, $booking->id))
                        ->pluck('name', 'id')
                        ->toArray();

                    if (empty($drivers)) {
                        return [
                            \Filament\Forms\Components\Placeholder::make('no_driver')
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString(
                                    '<div class="bg-orange-50 border border-orange-200 rounded-lg p-3 text-sm text-orange-700">'
                                    . '⚠️ Tidak ada sopir aktif yang tersedia untuk periode ini.'
                                    . '</div>'
                                )),
                        ];
                    }

                    return [
                        \Filament\Forms\Components\Select::make('driver_id')
                            ->label('Pilih Sopir')
                            ->options($drivers)
                            ->default($this->record->driver_id)
                            ->required()
                            ->helperText('Sopir yang dipilih akan langsung diinformasikan ke customer.'),
                    ];
                })
                ->visible(fn () => $this->record->with_driver && $this->record->status === 'confirmed')
                ->action(function (array $data) {
                    if (!empty($data['driver_id'])) {
                        $this->record->update(['driver_id' => $data['driver_id']]);

                        // Notifikasi ke customer bahwa sopir sudah ditugaskan
                        try {
                            $driver  = \App\Models\Driver::find($data['driver_id']);
                            $booking = $this->record->fresh();
                            $booking->customer?->user?->notify(
                                new \App\Notifications\DriverAssignedNotification($booking, $driver)
                            );
                        } catch (\Throwable) {}

                        Notification::make()
                            ->title('Sopir berhasil ditugaskan')
                            ->body('Customer akan mendapat notifikasi.')
                            ->success()
                            ->send();
                        $this->redirect(BookingResource::getUrl('edit', ['record' => $this->record]));
                    }
                }),

            // Tandai berlangsung (confirmed → ongoing)
            Action::make('ongoing')
                ->label('🚗 Tandai Berlangsung')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Tandai Mobil Diserahkan')
                ->modalDescription('Konfirmasi bahwa mobil sudah diserahkan ke customer.')
                ->visible(fn () => $this->record->status === 'confirmed')
                ->action(function () {
                    app(BookingService::class)->markAsOngoing($this->record);
                    Notification::make()
                        ->title('Status diubah ke Berlangsung')
                        ->success()
                        ->send();
                    $this->redirect(BookingResource::getUrl('index'));
                }),

            // Tandai selesai (ongoing → completed) dengan deteksi keterlambatan
            Action::make('complete')
                ->label('🏁 Tandai Selesai')
                ->color('primary')
                ->modalHeading('Tandai Pesanan Selesai')
                ->form([
                    \Filament\Forms\Components\Placeholder::make('driver_arrival_info')
                        ->label('')
                        ->content(function () {
                            $report = $this->record->lateReturnReport;
                            if ($report && $report->return_confirmed_at) {
                                $timeStr = $report->return_confirmed_at->format('d M Y, H:i');
                                $reporter = $report->reporterLabel();
                                $gpsStr = $report->hasReturnLocation() ? ' (📍 GPS Terverifikasi)' : '';
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="p-3 bg-green-50 border border-green-300 rounded-lg text-xs text-green-800 mb-2">'
                                    .'<strong>📍 Catatan Pengembalian Lapangan:</strong><br>'
                                    .$reporter.' telah mengonfirmasi tiba di lokasi pada <strong>'.$timeStr.' WIB</strong>'.$gpsStr
                                    .'</div>'
                                );
                            }
                            if ($report && $report->estimated_late_hours > 0) {
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="p-3 bg-amber-50 border border-amber-300 rounded-lg text-xs text-amber-800 mb-2">'
                                    .'<strong>⚠️ Laporan Keterlambatan Lapangan:</strong><br>'
                                    .$report->reporterLabel().' melaporkan estimasi terlambat '.$report->estimated_late_hours.' jam (Alasan: "'.$report->reason.'")'
                                    .'</div>'
                                );
                            }
                            return null;
                        }),
                    \Filament\Forms\Components\DateTimePicker::make('actual_return_at')
                        ->label('Waktu Aktual Pengembalian')
                        ->required()
                        ->default(fn () => $this->record->lateReturnReport?->return_confirmed_at ?? now())
                        ->native(false)
                        ->displayFormat('d/m/Y H:i')
                        ->seconds(false)
                        ->helperText('Isi sesuai waktu mobil benar-benar dikembalikan customer (Format 24 Jam WIB).'),
                ])
                ->visible(fn () => $this->record->status === 'ongoing')
                ->action(function (array $data) {
                    $actualReturn = \Carbon\Carbon::parse($data['actual_return_at']);
                    $result = app(\App\Services\BookingService::class)->markAsCompleted($this->record, $actualReturn);

                    if ($this->record->fresh()->is_late) {
                        $hours = $this->record->fresh()->late_duration_hours;
                        $fee   = number_format($this->record->fresh()->late_fee, 0, ',', '.');
                        Notification::make()
                            ->title('⚠️ Selesai — Terlambat ' . $hours . ' jam')
                            ->body('Denda keterlambatan: Rp ' . $fee . '. Customer dan Anda telah mendapat notifikasi.')
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('🏁 Pesanan selesai! Payout akan diproses.')
                            ->success()
                            ->send();
                    }
                    $this->redirect(BookingResource::getUrl('index'));
                }),

            // ── Lapor Keterlambatan Sopir (ongoing) ──
            Action::make('report_driver_late')
                ->label('⏰ Lapor Terlambat')
                ->color('warning')
                ->icon('heroicon-o-clock')
                ->visible(fn () => $this->record->status === 'ongoing' && !$this->record->lateReturnReport)
                ->modalHeading('Catat Laporan Keterlambatan dari Sopir / Vendor')
                ->modalDescription('Fitur ini digunakan saat sopir mengabarkan ke vendor bahwa pengembalian mobil akan mengalami keterlambatan.')
                ->form([
                    \Filament\Forms\Components\TextInput::make('estimated_late_hours')
                        ->label('Estimasi Terlambat (Jam)')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(72)
                        ->default(2),
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Alasan Keterlambatan')
                        ->required()
                        ->placeholder('Contoh: Terjebak kemacetan parah di tol / kendala cuaca / customer minta tambahan durasi...')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    \App\Models\LateReturnReport::create([
                        'booking_id'           => $this->record->id,
                        'reporter_type'        => 'driver',
                        'reported_by_user_id'  => auth('vendor')->id(),
                        'estimated_late_hours' => (int) $data['estimated_late_hours'],
                        'reason'               => $data['reason'],
                        'status'               => 'acknowledged',
                    ]);

                    Notification::make()
                        ->title('Laporan keterlambatan sopir tercatat')
                        ->body('Laporan keterlambatan armada berhasil disimpan di sistem.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // ── Kirim WA Portal ke Sopir ──
            Action::make('share_driver_portal')
                ->label('💬 WA Sopir')
                ->color('success')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->visible(fn () => in_array($this->record->status, ['confirmed', 'ongoing']) && $this->record->with_driver && $this->record->driver)
                ->url(function () {
                    $driverPhone = preg_replace('/[^0-9]/', '', $this->record->driver?->phone ?? '');
                    if (str_starts_with($driverPhone, '0')) {
                        $driverPhone = '62' . substr($driverPhone, 1);
                    }
                    $msg = rawurlencode("Halo " . ($this->record->driver?->name ?? 'Sopir') . ", berikut link Portal Laporan Keterlambatan/Pengembalian untuk booking " . $this->record->code . ": " . route('driver.late-report.show', $this->record->code));
                    return "https://wa.me/" . $driverPhone . "?text=" . $msg;
                })
                ->openUrlInNewTab(),

            // ── Laporkan Masalah Customer (ongoing/completed) ──
            Action::make('vendor_dispute')
                ->label('⚠️ Komplain')
                ->color('danger')
                ->icon('heroicon-o-exclamation-triangle')
                ->visible(function () {
                    if (!in_array($this->record->status, ['ongoing', 'completed'])) {
                        return false;
                    }
                    $existing = \App\Models\Complaint::where('booking_id', $this->record->id)
                        ->where('reporter_id', auth('vendor')->id())
                        ->whereNotIn('status', ['rejected'])
                        ->exists();
                    return !$existing;
                })
                ->modalHeading('Laporkan Masalah — Kerusakan / Masalah oleh Customer')
                ->modalDescription('Gunakan fitur ini untuk melaporkan kerusakan mobil atau masalah serius yang disebabkan oleh customer. Admin akan meninjau dan memutuskan tindakan selanjutnya.')
                ->form([
                    \Filament\Forms\Components\Select::make('dispute_category')
                        ->label('Kategori Masalah')
                        ->required()
                        ->options([
                            'Kerusakan Fisik Mobil'           => 'Kerusakan Fisik Mobil',
                            'Kerusakan Interior / Aksesori'   => 'Kerusakan Interior / Aksesori',
                            'Mobil Dikembalikan Terlambat'    => 'Mobil Dikembalikan Terlambat',
                            'Pelanggaran Perjanjian Sewa'     => 'Pelanggaran Perjanjian Sewa',
                            'Mobil Digunakan di Luar Wilayah' => 'Mobil Digunakan di Luar Wilayah',
                            'Customer Tidak Bisa Dihubungi'   => 'Customer Tidak Bisa Dihubungi',
                            'Lainnya'                         => 'Lainnya',
                        ])
                        ->placeholder('Pilih kategori...'),

                    \Filament\Forms\Components\Textarea::make('dispute_reason')
                        ->label('Uraian Masalah')
                        ->required()
                        ->minLength(30)
                        ->rows(5)
                        ->placeholder('Jelaskan masalah secara detail. Contoh: Saat mobil dikembalikan ditemukan kerusakan pada bumper depan...'),

                    \Filament\Forms\Components\TextInput::make('estimated_cost')
                        ->label('Estimasi Biaya Perbaikan (Rp)')
                        ->numeric()
                        ->prefix('Rp')
                        ->minValue(1)
                        ->required()
                        ->placeholder('Contoh: 500000')
                        ->helperText('Masukkan perkiraan biaya perbaikan berdasarkan kondisi kerusakan. Admin akan meninjau dan menyesuaikan jika diperlukan.'),

                    \Filament\Forms\Components\FileUpload::make('evidence_files')
                        ->label('Bukti Pendukung (opsional)')
                        ->helperText('Foto kerusakan, screenshot chat, dll. Format: JPG, PNG, PDF — maks. 5 MB per file.')
                        ->multiple()
                        ->maxFiles(5)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('complaints/evidence'),
                ])
                ->modalSubmitActionLabel('Kirim Laporan ke Admin')
                ->action(function (array $data) {
                    $vendor       = auth('vendor')->user()?->vendor;
                    $vendorUser   = auth('vendor')->user();
                    $booking      = $this->record;

                    $categoryId = \App\Models\ComplaintCategory::where('code', 'damage_dispute')->value('id')
                        ?? \App\Models\ComplaintCategory::where('code', 'other')->value('id')
                        ?? 9;

                    $description = $data['dispute_category'] . "\n\n" . $data['dispute_reason'];

                    $attachmentPaths = !empty($data['evidence_files'])
                        ? array_values((array) $data['evidence_files'])
                        : null;

                    $complaint = \App\Models\Complaint::create([
                        'id'                    => \Illuminate\Support\Str::uuid(),
                        'reference'             => \App\Models\Complaint::generateReference(),
                        'booking_id'            => $booking->id,
                        'reporter_id'           => $vendorUser->id,
                        'vendor_id'             => $vendor->id,
                        'category_id'           => $categoryId,
                        'severity'              => 'high',
                        'status'                => 'submitted',
                        'description'           => $description,
                        'attachments'           => $attachmentPaths,
                        'customer_demand'       => 'other',
                        'demanded_refund_amount' => !empty($data['estimated_cost']) ? (int) $data['estimated_cost'] : null,
                        'customer_demand_note'  => 'Estimasi biaya perbaikan dari vendor: ' . ($vendor->business_name ?? ''),
                        'ip_address'            => request()->ip(),
                    ]);

                    \App\Models\ComplaintLog::create([
                        'complaint_id' => $complaint->id,
                        'actor_id'     => $vendorUser->id,
                        'actor_role'   => 'vendor',
                        'action'       => 'submitted',
                        'context'      => ['source' => 'vendor_panel', 'category' => $data['dispute_category']],
                        'ip_address'   => request()->ip(),
                    ]);

                    $admins = \App\Models\User::whereDoesntHave('vendor')
                        ->whereDoesntHave('customer')
                        ->get();
                    foreach ($admins as $admin) {
                        try {
                            $admin->notify(new \App\Notifications\Complaint\ComplaintSubmitted($complaint));
                        } catch (\Throwable) {}
                    }

                    Notification::make()
                        ->title('Laporan berhasil dikirim — ' . $complaint->reference)
                        ->body('Admin akan meninjau laporan Anda dalam 1–3 hari kerja.')
                        ->success()
                        ->send();

                    $this->redirect(BookingResource::getUrl('index'));
                }),
            // Tolak pesanan (awaiting_vendor → cancelled)
            Action::make('reject')
                ->label('❌ Tolak Pesanan')
                ->color('danger')
                ->modalHeading('Tolak Pesanan')
                ->modalDescription('Pesanan akan dibatalkan dan customer akan mendapat refund. Tindakan ini tidak bisa dibatalkan.')
                ->form([
                    \Filament\Forms\Components\Select::make('reject_reason')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->options([
                            'Mobil rusak/dalam perbaikan'       => 'Mobil rusak/dalam perbaikan',
                            'Mobil terlibat kecelakaan'         => 'Mobil terlibat kecelakaan',
                            'Konflik jadwal tidak terdeteksi'   => 'Konflik jadwal tidak terdeteksi',
                            'Customer tidak memenuhi syarat'    => 'Customer tidak memenuhi syarat',
                            'Lainnya'                           => 'Lainnya',
                        ])
                        ->placeholder('Pilih alasan...'),
                    \Filament\Forms\Components\Textarea::make('reject_note')
                        ->label('Keterangan Tambahan (opsional)')
                        ->rows(3)
                        ->placeholder('Jelaskan lebih detail jika perlu...'),
                ])
                ->visible(fn () => $this->record->status === 'awaiting_vendor')
                ->action(function (array $data) {
                    $reason = $data['reject_reason'];
                    if (!empty($data['reject_note'])) {
                        $reason .= ': ' . $data['reject_note'];
                    }

                    $bookingService = app(BookingService::class);
                    $bookingService->cancelByVendor($this->record, $reason);

                    \App\Models\User::where('role', 'admin')->get()->each(function ($admin) use ($reason) {
                        try {
                            $admin->notify(
                                new \App\Notifications\BookingRejectedByVendorNotification($this->record, $reason)
                            );
                        } catch (\Throwable) {}
                    });

                    Notification::make()
                        ->title('Pesanan ditolak')
                        ->body('Admin telah diberitahu untuk memproses refund ke customer.')
                        ->warning()
                        ->send();

                    $this->redirect(BookingResource::getUrl('index'));
                }),
        ];
    }
}
