<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'driver/late-report/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 419 PAGE EXPIRED — session/CSRF token expired
        $exceptions->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->is('driver/*')) {
                return back()->withInput()->withErrors(['driver_phone' => 'Sesi halaman kedaluwarsa. Silakan coba tekan Kirim Laporan sekali lagi.']);
            }
            if (auth()->check() || auth('vendor')->check() || auth('admin')->check()) {
                return back()->withInput()->withErrors(['message' => 'Sesi halaman telah berakhir. Silakan ulangi tindakan Anda.']);
            }
            return redirect()->route('login')
                ->withErrors(['email' => 'Sesi Anda telah berakhir. Silakan login kembali.']);
        });

        // Log unauthorized access attempts (IDOR detection)
        $exceptions->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            try {
                $userId = auth('vendor')->id() ?? auth('admin')->id() ?? auth()->id();
                if ($userId) {
                    \App\Models\UnauthorizedAccessLog::create([
                        'user_id'    => $userId,
                        'action'     => $request->method(),
                        'route'      => $request->path(),
                        'ip'         => $request->ip(),
                        'user_agent' => substr($request->userAgent() ?? '', 0, 500),
                    ]);
                }
            } catch (\Throwable) {
                // Jangan sampai logging error merusak response
            }
        });
    })->create();
