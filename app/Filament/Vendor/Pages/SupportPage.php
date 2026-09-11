<?php

namespace App\Filament\Vendor\Pages;

use App\Models\VendorSupportTicket;
use App\Notifications\VendorSupportMessageNotification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use App\Models\User;

class SupportPage extends Page
{
    protected string $view = 'filament.vendor.pages.support-page';

    protected static ?string $navigationLabel = 'Bantuan & Support';
    protected static ?int    $navigationSort  = 100;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-question-mark-circle';
    }

    public static function getNavigationBadge(): ?string
    {
        $vendor = auth('vendor')->user()?->vendor;
        if (!$vendor) return null;

        $count = VendorSupportTicket::where('vendor_id', $vendor->id)
            ->where('status', 'replied')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'success';
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Bantuan & Support';
    }

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('subject')
                    ->label('Topik Pertanyaan')
                    ->options([
                        'Bantuan Upload Mobil'       => 'Bantuan Upload Mobil (Tolong Upload-kan Mobil)',
                        'Cara Upload Mobil'           => 'Cara Upload Mobil',
                        'Cara Konfirmasi Booking'     => 'Cara Konfirmasi Booking',
                        'Masalah Payout / Pembayaran' => 'Masalah Payout / Pembayaran',
                        'Paket & Komisi'              => 'Paket & Komisi',
                        'Verifikasi Dokumen'          => 'Verifikasi Dokumen',
                        'Bug / Error di Sistem'       => 'Bug / Error di Sistem',
                        'Lainnya'                     => 'Lainnya',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state === 'Bantuan Upload Mobil') {
                            $set('message', "- Merek: \n- Model: \n- Tahun: \n- Transmisi: Manual / Automatic\n- Bahan Bakar: Bensin / Diesel / Listrik / Hybrid\n- Plat Nomor: \n- Kapasitas Kursi: \n- Kapasitas Bagasi: \n- Opsi Sewa: Lepas Kunci Saja / Dengan Sopir Saja / Keduanya\n- Harga per Hari: Rp \n- Harga Sopir/Hari: Rp (kosongkan jika tidak ada sopir)\n- Bisa Disewa Bulanan: Ya / Tidak\n- Harga per Bulan: Rp (kosongkan jika tidak)\n- Termasuk BBM: Ya / Tidak\n- Fitur Tambahan: \n- Deskripsi: ");
                        } else {
                            $set('message', null);
                        }
                    })
                    ->placeholder('Pilih topik pertanyaan'),

                Textarea::make('message')
                    ->label(fn ($get) => $get('subject') === 'Bantuan Upload Mobil' ? 'Deskripsi Mobil & Kelengkapan Detail' : 'Pertanyaan / Kendala')
                    ->required()
                    ->minLength(10)
                    ->rows(12)
                    ->default(fn ($get) => $get('subject') === 'Bantuan Upload Mobil'
                        ? "- Merek: \n- Model: \n- Tahun: \n- Transmisi: Manual / Automatic\n- Bahan Bakar: Bensin / Diesel / Listrik / Hybrid\n- Plat Nomor: \n- Kapasitas Kursi: \n- Kapasitas Bagasi: \n- Opsi Sewa: Lepas Kunci Saja / Dengan Sopir Saja / Keduanya\n- Harga per Hari: Rp \n- Harga Sopir/Hari: Rp (kosongkan jika tidak ada sopir)\n- Bisa Disewa Bulanan: Ya / Tidak\n- Harga per Bulan: Rp (kosongkan jika tidak)\n- Termasuk BBM: Ya / Tidak\n- Fitur Tambahan: \n- Deskripsi: "
                        : null)
                    ->afterStateUpdated(function ($state, $set, $get) {
                        // Isi template otomatis saat subject berubah ke Bantuan Upload Mobil
                        if ($get('subject') === 'Bantuan Upload Mobil' && empty($state)) {
                            $set('message', "- Merek: \n- Model: \n- Tahun: \n- Transmisi: Manual / Automatic\n- Bahan Bakar: Bensin / Diesel / Listrik / Hybrid\n- Plat Nomor: \n- Kapasitas Kursi: \n- Kapasitas Bagasi: \n- Opsi Sewa: Lepas Kunci Saja / Dengan Sopir Saja / Keduanya\n- Harga per Hari: Rp \n- Harga Sopir/Hari: Rp (kosongkan jika tidak ada sopir)\n- Bisa Disewa Bulanan: Ya / Tidak\n- Harga per Bulan: Rp (kosongkan jika tidak)\n- Termasuk BBM: Ya / Tidak\n- Fitur Tambahan: \n- Deskripsi: ");
                        }
                    })
                    ->placeholder('Jelaskan pertanyaan atau kendala Anda secara detail...'),

                FileUpload::make('attachments')
                    ->label('Upload Gambar / Foto Mobil')
                    ->image()
                    ->multiple()
                    ->maxFiles(6)
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->directory('support-attachments')
                    ->disk('public')
                    ->reorderable()
                    ->required(fn ($get) => $get('subject') === 'Bantuan Upload Mobil')
                    ->visible(fn ($get) => $get('subject') === 'Bantuan Upload Mobil')
                    ->helperText('Silakan upload foto mobil tampak depan, samping, dan dalam untuk memudahkan admin.'),
            ]);
    }

    public function send(): void
    {
        $validated = $this->form->getState();

        $vendor = auth('vendor')->user()?->vendor;
        if (!$vendor) return;

        // Simpan tiket ke database
        $ticket = VendorSupportTicket::create([
            'vendor_id'   => $vendor->id,
            'subject'     => $validated['subject'],
            'message'     => $validated['message'],
            'attachments' => $validated['attachments'] ?? null,
            'status'      => 'open',
        ]);

        // Kirim notifikasi ke semua admin
        $admins = User::where('role', 'admin')->get();

        $notification = new VendorSupportMessageNotification(
            vendorName:  $vendor->business_name,
            vendorEmail: auth('vendor')->user()->email,
            subject:     $validated['subject'],
            message:     $validated['message'],
            vendorId:    $vendor->id,
            attachments: $validated['attachments'] ?? null,
        );

        foreach ($admins as $admin) {
            try {
                $admin->notify($notification);
            } catch (\Throwable) {}
        }

        $this->form->fill(); // reset form

        Notification::make()
            ->title('Pesan terkirim!')
            ->body('Admin akan merespons pertanyaan Anda segera. Cek riwayat tiket di bawah.')
            ->success()
            ->send();
    }

    /**
     * Data tiket untuk ditampilkan di blade view.
     */
    public function getTicketsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        $vendor = auth('vendor')->user()?->vendor;
        if (!$vendor) return collect();

        return VendorSupportTicket::where('vendor_id', $vendor->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }
}
