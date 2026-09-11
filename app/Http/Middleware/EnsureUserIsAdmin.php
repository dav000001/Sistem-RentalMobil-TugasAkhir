<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('admin')->user();

        // User harus login di admin guard
        if (!$user) {
            // Cek apakah user login di guard lain (vendor/web)
            $vendorUser = auth('vendor')->user();
            $webUser = auth('web')->user();

            if ($vendorUser || $webUser) {
                // User sudah login di guard lain, tapi bukan di admin guard
                return redirect('/admin/login')
                    ->with('info', 'Anda login sebagai ' . ($vendorUser ? 'vendor' : 'customer') . '. Silakan login dengan akun admin untuk mengakses panel admin.');
            }

            // Belum login sama sekali
            return redirect('/admin/login');
        }

        // Hanya user dengan role 'admin' yang boleh akses panel ini
        if ($user->role !== 'admin') {
            auth('admin')->logout();

            return redirect('/admin/login')
                ->withErrors(['email' => 'Akun ini tidak memiliki akses ke panel admin.']);
        }

        return $next($request);
    }
}
