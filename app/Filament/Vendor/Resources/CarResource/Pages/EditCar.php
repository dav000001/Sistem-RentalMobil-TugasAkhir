<?php

namespace App\Filament\Vendor\Resources\CarResource\Pages;

use App\Filament\Vendor\Resources\CarResource;
use App\Models\CarPhoto;
use App\Models\CarPricing;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditCar extends EditRecord
{
    protected static string $resource = CarResource::class;

    protected array $pricingData = [];
    protected array $photoFiles = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus Mobil')
                ->requiresConfirmation()
                ->modalHeading('Hapus Mobil')
                ->modalDescription(fn () => 'Yakin ingin menghapus ' . $this->record->brand . ' ' . $this->record->model . ' (' . $this->record->plate_number . ')? Semua foto dan data harga akan ikut terhapus. Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus Permanen')
                ->before(function ($action) {
                    // Cek booking aktif
                    $activeBookings = $this->record->bookings()
                        ->whereIn('status', ['pending', 'confirmed', 'ongoing'])
                        ->count();

                    if ($activeBookings > 0) {
                        Notification::make()
                            ->title('Tidak Dapat Dihapus')
                            ->body("Mobil ini masih memiliki {$activeBookings} pemesanan aktif. Selesaikan semua pemesanan terlebih dahulu.")
                            ->danger()
                            ->persistent()
                            ->send();
                        $action->halt();
                    }

                    // Hapus semua foto dari storage sebelum record dihapus
                    foreach ($this->record->photos as $photo) {
                        $path = ltrim(str_replace('/storage/', '', $photo->path), '/');
                        if (Storage::disk('public')->exists($path)) {
                            Storage::disk('public')->delete($path);
                        }
                    }
                }),
        ];
    }

    // ── Hapus satu foto (dipanggil via wire:click dari preview) ──────────

    public function deletePhoto(int $photoId): void
    {
        $vendor = auth('vendor')->user()?->vendor;
        $photo  = CarPhoto::find($photoId);

        if (!$photo) {
            Notification::make()->title('Foto tidak ditemukan')->danger()->send();
            return;
        }

        // Pastikan foto milik mobil yang sedang diedit dan milik vendor ini
        if ($photo->car_id !== $this->record->id || $this->record->vendor_id !== $vendor?->id) {
            Notification::make()->title('Akses ditolak')->danger()->send();
            return;
        }

        // Hapus file dari storage
        $path = ltrim(str_replace('/storage/', '', $photo->path), '/');
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $photo->delete();

        // Update sort_order foto yang tersisa
        $this->record->photos()->orderBy('sort_order')->get()
            ->each(fn ($p, $i) => $p->update(['sort_order' => $i]));

        // Refresh record agar preview update
        $this->record->refresh();
        $this->record->load('photos');

        Notification::make()->title('Foto berhasil dihapus')->success()->send();
    }

    // ── Load data saat edit ───────────────────────────────────────────────

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ownership check
        $vendor = auth('vendor')->user()?->vendor;
        if (!$vendor || $this->record->vendor_id !== $vendor->id) {
            $this->halt();
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki akses ke mobil ini.')
                ->danger()
                ->send();
            return $data;
        }

        $pricing = $this->record->pricing;
        if ($pricing) {
            $data['daily_price']         = $pricing->daily_price;
            $data['monthly_price']       = $pricing->monthly_price;
            $data['with_driver_price']   = $pricing->with_driver_price;
            $data['fuel_included']       = $pricing->fuel_included;
        }

        // Load is_monthly_available dari record mobil
        $data['is_monthly_available'] = (bool) $this->record->is_monthly_available;

        // Load rental_option
        $data['rental_option'] = $this->record->rental_option ?? 'self_drive_only';

        // Isi kembali car_brand_id dan car_model_id untuk dropdown
        $data['car_brand_id'] = $this->record->car_brand_id;
        $data['car_model_id'] = $this->record->car_model_id;
        $data['brand_manual'] = null;
        $data['model_manual'] = null;

        // Pastikan field unavailability ter-load dari record
        $data['unavailability_reason'] = $this->record->unavailability_reason;
        $data['unavailability_notes']  = $this->record->unavailability_notes;
        $data['unavailable_until']     = $this->record->unavailable_until?->format('Y-m-d');

        $data['car_photos'] = [];

        return $data;
    }

    // ── Simpan data ───────────────────────────────────────────────────────

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Guard: jangan izinkan vendor mengubah status mobil yang di-suspend karena subscription
        $restrictedReasons = ['subscription_expired', 'plan_downgrade'];
        if (in_array($this->record->unavailability_reason, $restrictedReasons)) {
            $data['status']                = $this->record->status;
            $data['unavailability_reason'] = $this->record->unavailability_reason;
            $data['unavailability_notes']  = $this->record->unavailability_notes;
            $data['unavailable_until']     = $this->record->unavailable_until;
        } else {
            $reason = $data['unavailability_reason'] ?? null;

            if (!empty($reason)) {
                $data['status']                = 'unavailable';
                $data['unavailability_reason'] = $reason;
            } else {
                $data['unavailability_reason'] = null;
                $data['unavailability_notes']  = null;
                $data['unavailable_until']     = null;
                if (empty($data['status']) || $data['status'] === 'unavailable') {
                    $data['status'] = 'published';
                }
            }
        }

        // ── Resolve brand & model (dropdown atau manual) ─────────────
        $data = $this->resolveBrandAndModel($data);

        // ── Bersihkan monthly_price jika sewa bulanan tidak aktif ────
        if (empty($data['is_monthly_available'])) {
            $data['is_monthly_available'] = false;
            $data['monthly_price'] = null;
        }

        // ── Bersihkan with_driver_price jika self_drive_only ─────────
        if (($data['rental_option'] ?? 'self_drive_only') === 'self_drive_only') {
            $data['with_driver_price'] = null;
        }

        $this->pricingData = [
            'daily_price'         => $data['daily_price'] ?? null,
            'monthly_price'       => $data['monthly_price'] ?? null,
            'with_driver_price'   => $data['with_driver_price'] ?? null,
            'fuel_included'       => $data['fuel_included'] ?? false,
        ];

        $this->photoFiles = $data['car_photos'] ?? [];

        unset(
            $data['daily_price'],
            $data['monthly_price'],
            $data['with_driver_price'],
            $data['fuel_included'],
            $data['car_photos'],
            $data['brand_manual'],
            $data['model_manual'],
        );

        return $data;
    }

    /**
     * Resolve brand_id dan model_id dari input dropdown atau manual ketik.
     */
    protected function resolveBrandAndModel(array $data): array
    {
        $brandIdRaw  = $data['car_brand_id'] ?? null;
        $modelIdRaw  = $data['car_model_id'] ?? null;
        $brandManual = trim($data['brand_manual'] ?? '');
        $modelManual = trim($data['model_manual'] ?? '');

        if ($brandIdRaw === 'other' || empty($brandIdRaw)) {
            $brand = \App\Models\CarBrand::findOrCreateFromInput($brandManual ?: 'Unknown');
            $data['car_brand_id'] = $brand->id;
            $data['brand']        = $brand->name;

            $carModel = \App\Models\CarModel::findOrCreateFromInput($brand->id, $modelManual ?: 'Unknown');
            $data['car_model_id'] = $carModel->id;
            $data['model']        = $carModel->name;
        } else {
            $brand = \App\Models\CarBrand::find($brandIdRaw);
            $data['brand'] = $brand?->name ?? $brandManual;

            if ($modelIdRaw === 'other' || empty($modelIdRaw)) {
                $carModel = \App\Models\CarModel::findOrCreateFromInput((int) $brandIdRaw, $modelManual ?: 'Unknown');
                $data['car_model_id'] = $carModel->id;
                $data['model']        = $carModel->name;
            } else {
                $carModel = \App\Models\CarModel::find($modelIdRaw);
                $data['model'] = $carModel?->name ?? $modelManual;
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Update pricing
        CarPricing::updateOrCreate(
            ['car_id' => $this->record->id],
            $this->pricingData
        );

        // Update foto HANYA jika ada foto baru yang diupload
        if (!empty($this->photoFiles)) {
            $this->record->photos()->delete();
            foreach ($this->photoFiles as $index => $path) {
                \App\Models\CarPhoto::create([
                    'car_id'     => $this->record->id,
                    'path'       => '/storage/' . $path,
                    'sort_order' => $index,
                ]);
            }
        }

        // Kirim notifikasi ke admin jika status berubah ke unavailable
        if ($this->record->status === 'unavailable') {
            $admins = \App\Models\User::whereDoesntHave('vendor')
                ->whereDoesntHave('customer')
                ->get();

            $car    = $this->record;
            $vendor = auth('vendor')->user()?->vendor;
            $reason = match ($car->unavailability_reason) {
                'service'    => 'Service / Perawatan',
                'rusak'      => 'Rusak',
                'kecelakaan' => 'Kecelakaan',
                default      => 'Lainnya',
            };

            foreach ($admins as $admin) {
                try {
                    $admin->notifications()->create([
                        'id'              => \Illuminate\Support\Str::uuid(),
                        'type'            => \App\Notifications\CarUnavailableNotification::class,
                        'notifiable_type' => \App\Models\User::class,
                        'notifiable_id'   => $admin->id,
                        'data'            => json_encode([
                            'title'     => '🚗 Mobil Tidak Tersedia',
                            'body'      => ($vendor?->business_name ?? 'Vendor') . ' melaporkan ' . $car->brand . ' ' . $car->model . ' (' . $car->plate_number . ') tidak tersedia. Alasan: ' . $reason,
                            'car_id'    => $car->id,
                            'vendor_id' => $vendor?->id,
                            'url'       => '/admin/cars/' . $car->id . '/edit',
                        ]),
                        'read_at'         => null,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                } catch (\Throwable) {}
            }
        }
    }
}
