<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            // Hapus intended URL agar tidak redirect ke vendor/admin panel
            $request->session()->forget('url.intended');

            // Add to account switcher
            app(\App\Services\Auth\MultiAccountSession::class)->addAccount(Auth::user(), $request);

            $user = Auth::user();

            // Admin → langsung ke panel admin
            if ($user->role === 'admin') {
                auth('admin')->login($user, $request->boolean('remember'));
                return redirect('/admin')->with('success', 'Berhasil login sebagai Admin.');
            }

            // Vendor (role vendor atau punya vendor profile)
            if ($user->vendor) {
                $vendorStatus = $user->vendor->status instanceof \App\Enums\VendorStatus
                    ? $user->vendor->status
                    : \App\Enums\VendorStatus::tryFrom($user->vendor->status ?? 'pending');

                if ($vendorStatus?->isOperational()) {
                    // Vendor approved → login guard vendor sekaligus
                    auth('vendor')->login($user, $request->boolean('remember'));

                    // Pastikan vendor punya customer profile (untuk bisa berbelanja/booking)
                    if (! $user->customer) {
                        \App\Models\Customer::create([
                            'user_id'             => $user->id,
                            'full_name'           => $user->name,
                            'verification_status' => 'pending',
                        ]);
                        $user->refresh();
                    }

                    // Vendor approved → langsung ke dashboard vendor
                    return redirect('/vendor')->with('success', 'Berhasil login sebagai Vendor.');

                } else {
                    // Vendor belum/tidak approved → ke onboarding (pending, needs_revision, dll)
                    if (! $user->customer) {
                        return redirect()->route('vendor.onboarding')
                            ->with('info', 'Akun vendor Anda sedang menunggu verifikasi admin.');
                    }
                    // Punya customer juga → ke home, onboarding bisa dibuka manual
                    return redirect(route('home'))
                        ->with('info', 'Login berhasil. Akun vendor Anda sedang dalam proses verifikasi.');
                }
            }

            return redirect(route('home'))->with('success', 'Berhasil login');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'status' => 'active',
        ]);

        // Create customer profile
        Customer::create([
            'user_id' => $user->id,
            'full_name' => $validated['name'],
            'verification_status' => 'pending',
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Kirim notifikasi ke semua admin: customer baru mendaftar
        try {
            User::where('role', 'admin')->get()->each(function ($admin) use ($user) {
                $admin->notify(new \App\Notifications\NewCustomerRegisteredNotification($user));
            });
        } catch (\Throwable) {}

        return redirect(route('home'))->with('success', 'Registrasi berhasil. Silakan lengkapi verifikasi identitas.');
    }

    public function logout(Request $request)
    {
        $switcher = app(\App\Services\Auth\MultiAccountSession::class);
        $nextAccount = $switcher->logoutCurrent($request);

        Auth::logout();
        auth('vendor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // If there's another account, switch to it
        if ($nextAccount) {
            $nextUser = \App\Models\User::find($nextAccount['user_id']);
            if ($nextUser) {
                Auth::login($nextUser);
                $label = $nextUser->vendor?->business_name ?? $nextUser->name;
                return redirect(route('home'))->with('success', "Keluar. Sekarang masuk sebagai {$label}");
            }
        }

        return redirect(route('home'))->with('success', 'Berhasil logout');
    }
}
