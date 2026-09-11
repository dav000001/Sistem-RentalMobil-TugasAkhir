<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsVendor
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || !$user->vendor || $user->vendor->status !== 'approved') {
            abort(403, 'Akses ditolak. Akun vendor tidak ditemukan atau belum disetujui.');
        }

        return $next($request);
    }
}
