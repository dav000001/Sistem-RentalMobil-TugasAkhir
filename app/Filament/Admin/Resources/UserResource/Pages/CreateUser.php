<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Enums\VendorStatus;
use App\Filament\Admin\Resources\UserResource;
use App\Models\Customer;
use App\Models\Vendor;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    protected function hasCreateAnother(): bool
    {
        return false;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Simpan account_type sementara, hapus dari data user
        $this->accountType = $data['account_type'] ?? 'admin';
        unset($data['account_type']);
        return $data;
    }

    protected string $accountType = 'admin';

    protected function afterCreate(): void
    {
        $user = $this->record;

        match ($this->accountType) {
            'customer' => $this->createCustomerProfile($user),
            'vendor'   => $this->createVendorProfile($user),
            default    => null, // admin — tidak perlu profil tambahan
        };
    }

    private function createCustomerProfile($user): void
    {
        Customer::create([
            'user_id'             => $user->id,
            'full_name'           => $user->name,
            'verification_status' => 'pending',
        ]);

        Notification::make()
            ->title('Akun Customer dibuat')
            ->body('Profil customer untuk ' . $user->name . ' berhasil dibuat. Customer perlu upload KTP & SIM untuk verifikasi.')
            ->success()
            ->send();
    }

    private function createVendorProfile($user): void
    {
        // Buat profil vendor minimal dengan status pending
        // Vendor perlu melengkapi data via /vendor/onboarding
        $defaultCity = \App\Models\City::first();

        Vendor::create([
            'user_id'       => $user->id,
            'business_name' => $user->name,
            'address'       => '',
            'city_id'       => $defaultCity?->id ?? 1,
            'status'        => VendorStatus::Pending->value,
        ]);

        Notification::make()
            ->title('Akun Vendor dibuat')
            ->body('Profil vendor untuk ' . $user->name . ' dibuat dengan status Pending. Vendor perlu login ke ' . url('/vendor/onboarding') . ' untuk melengkapi dokumen.')
            ->warning()
            ->persistent()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
