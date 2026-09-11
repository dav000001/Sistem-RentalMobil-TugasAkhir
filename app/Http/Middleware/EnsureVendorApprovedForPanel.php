<?php

namespace App\Http\Middleware;

use App\Enums\VendorStatus;
use Closure;
use Illuminate\Http\Request;

class EnsureVendorApprovedForPanel
{
    public function handle(Request $request, Closure $next)
    {
        // Halaman auth (login, logout, dll) selalu dilewatkan tanpa pengecekan
        if ($request->routeIs('filament.vendor.auth.*')) {
            return $next($request);
        }

        $user = auth('vendor')->user();

        // Belum login → biarkan Filament Authenticate middleware yang handle redirect
        if (!$user) {
            return $next($request);
        }

        // Akses panel vendor: user harus punya role 'vendor' ATAU punya vendor profile yang approved
        // (toleransi untuk data lama yang rolenya belum terupdate)
        $hasVendorRole    = $user->role === 'vendor';
        $hasVendorProfile = $user->vendor !== null;

        if (!$hasVendorRole && !$hasVendorProfile) {
            auth('vendor')->logout();

            return redirect('/vendor/login')
                ->withErrors(['email' => 'Akun ini tidak memiliki akses ke panel vendor.']);
        }

        $vendor = $user->vendor;

        // Jika tidak punya profil vendor sama sekali
        if (!$vendor) {
            auth('vendor')->logout();

            return redirect('/vendor/login')
                ->withErrors(['email' => 'Akun Anda tidak terdaftar sebagai vendor.']);
        }

        $status = $vendor->status instanceof VendorStatus
            ? $vendor->status
            : VendorStatus::tryFrom($vendor->status ?? 'pending');

        // Jika sudah approved, lanjutkan
        if ($status?->isOperational()) {
            return $next($request);
        }

        // Status tidak aktif → logout dan redirect dengan pesan
        $message = match ($status?->value) {
            'pending'        => 'Akun vendor Anda sedang menunggu verifikasi admin. Estimasi 1×24 jam kerja.',
            'needs_revision' => 'Dokumen Anda perlu diperbaiki. Silakan login ke akun customer dan buka halaman onboarding.',
            'rejected'       => 'Pendaftaran vendor Anda ditolak. Hubungi support untuk informasi lebih lanjut.',
            'suspended'      => 'Akun vendor Anda dibekukan sementara. Hubungi support.',
            default          => 'Akun vendor Anda belum aktif.',
        };

        auth('vendor')->logout();

        return redirect('/vendor/login')->withErrors(['email' => $message]);
    }
}
