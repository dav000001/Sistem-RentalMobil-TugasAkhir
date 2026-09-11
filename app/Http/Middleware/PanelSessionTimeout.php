<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Timeout sesi khusus untuk panel admin dan vendor.
 * Jika user tidak aktif selama $timeoutMinutes menit, paksa logout dan redirect ke halaman login.
 * Menggunakan key session tersendiri agar tidak bentrok dengan session customer.
 */
class PanelSessionTimeout
{
    /**
     * Batas inaktif dalam menit sebelum sesi habis.
     */
    protected int $timeoutMinutes = 20;

    public function handle(Request $request, Closure $next, string $guard = 'admin'): Response
    {
        $sessionKey = "panel_last_activity_{$guard}";

        if (Auth::guard($guard)->check()) {
            $lastActivity = $request->session()->get($sessionKey);

            if ($lastActivity !== null) {
                $inactiveSeconds = time() - $lastActivity;

                if ($inactiveSeconds > ($this->timeoutMinutes * 60)) {
                    // Logout dari guard yang bersangkutan
                    Auth::guard($guard)->logout();

                    // Hapus key activity dari session
                    $request->session()->forget($sessionKey);

                    // Tentukan URL login berdasarkan guard
                    $loginUrl = match ($guard) {
                        'admin'  => '/admin/login',
                        'vendor' => '/vendor/login',
                        default  => '/login',
                    };

                    return redirect($loginUrl)
                        ->with('error', 'Sesi Anda telah berakhir karena tidak aktif selama ' . $this->timeoutMinutes . ' menit. Silakan login kembali.');
                }
            }

            // Perbarui timestamp aktivitas terakhir
            $request->session()->put($sessionKey, time());
        }

        return $next($request);
    }
}
