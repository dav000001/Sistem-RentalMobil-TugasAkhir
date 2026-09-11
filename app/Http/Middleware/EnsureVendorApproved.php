<?php

namespace App\Http\Middleware;

use App\Enums\VendorStatus;
use Closure;
use Illuminate\Http\Request;

class EnsureVendorApproved
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $vendor = $user->vendor;

        if (!$vendor) {
            return redirect()->route('home')->with('error', 'Akun vendor tidak ditemukan.');
        }

        $status = $vendor->status instanceof VendorStatus
            ? $vendor->status
            : VendorStatus::tryFrom($vendor->status ?? 'pending');

        // Approved → lanjutkan
        if ($status?->isOperational()) {
            return $next($request);
        }

        // needs_revision → redirect ke onboarding untuk upload ulang dokumen
        if ($status?->value === 'needs_revision') {
            return redirect()->route('vendor.onboarding')
                ->with('warning', 'Beberapa dokumen Anda perlu diperbaiki. Silakan upload ulang dokumen yang diminta.');
        }

        // Status lain → redirect ke onboarding dengan pesan
        $message = match ($status?->value) {
            'pending'   => 'Akun Anda sedang menunggu verifikasi admin. Estimasi 1×24 jam kerja.',
            'rejected'  => 'Pendaftaran Anda ditolak. Hubungi support untuk informasi lebih lanjut.',
            'suspended' => 'Akun Anda dibekukan sementara. Hubungi support.',
            default     => 'Akun Anda belum aktif.',
        };

        return redirect()->route('vendor.onboarding')->with('error', $message);
    }
}
