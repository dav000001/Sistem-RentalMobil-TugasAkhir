<?php

namespace App\Filament\Vendor\Pages;

use App\Enums\VendorStatus;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    /**
     * Override authenticate to check vendor status before allowing login.
     * Logout guard vendor lama agar vendor berbeda bisa login tanpa terjebak session sebelumnya.
     */
    public function authenticate(): ?LoginResponse
    {
        // Jika sudah ada session vendor aktif, logout guard vendor saja
        // JANGAN invalidate session global — akan membuang CSRF token dan menyebabkan redirect loop
        if (auth('vendor')->check()) {
            auth('vendor')->logout();
        }

        $response = parent::authenticate();

        // After successful auth, check vendor status
        $user = auth('vendor')->user();

        if ($user) {
            $vendor = $user->vendor;

            if (!$vendor) {
                auth('vendor')->logout();
                throw ValidationException::withMessages([
                    'data.email' => 'Akun ini tidak terdaftar sebagai vendor.',
                ]);
            }

            // Toleransi role: user dengan vendor profile tetap bisa login
            // Role akan diupdate otomatis saat vendor diapprove (untuk data lama)
            if ($user->role !== 'vendor' && !$vendor) {
                auth('vendor')->logout();
                throw ValidationException::withMessages([
                    'data.email' => 'Akun ini tidak memiliki akses ke panel vendor.',
                ]);
            }

            $status = $vendor->status instanceof VendorStatus
                ? $vendor->status
                : VendorStatus::tryFrom($vendor->status ?? 'pending');

            if (!$status?->isOperational()) {
                auth('vendor')->logout();

                $message = match ($status?->value) {
                    'pending'        => '⏳ Akun vendor Anda sedang menunggu verifikasi admin. Estimasi 1×24 jam kerja. Anda akan dihubungi via email/WhatsApp.',
                    'needs_revision' => '✏️ Dokumen Anda perlu diperbaiki. Silakan login ke website utama dan buka halaman onboarding untuk upload ulang.',
                    'rejected'       => '❌ Pendaftaran vendor Anda ditolak. Hubungi support@rentalmobil.com untuk informasi lebih lanjut.',
                    'suspended'      => '🔒 Akun vendor Anda dibekukan sementara. Hubungi support untuk informasi lebih lanjut.',
                    default          => 'Akun vendor Anda belum aktif.',
                };

                throw ValidationException::withMessages([
                    'data.email' => $message,
                ]);
            }
        }

        return $response;
    }
}
