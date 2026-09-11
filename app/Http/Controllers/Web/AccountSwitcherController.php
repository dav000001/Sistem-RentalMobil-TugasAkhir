<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\MultiAccountSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AccountSwitcherController extends Controller
{
    public function __construct(
        private MultiAccountSession $switcher
    ) {}

    /**
     * Show add account form
     */
    public function showAddForm(Request $request)
    {
        if ($this->switcher->isMaxReached($request)) {
            return back()->with('error', 'Maksimal ' . MultiAccountSession::MAX_ACCOUNTS . ' akun. Hapus salah satu dulu.');
        }

        $currentUser = Auth::user();
        $accounts = $this->switcher->listAccounts($request);

        return view('auth.add-account', compact('currentUser', 'accounts'));
    }

    /**
     * Process add account login
     */
    public function addAccount(Request $request)
    {
        // Rate limit: 5x per minute per IP
        $key = 'add-account:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['email' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik."]);
        }

        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($key, 60);
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        if ($user->status === 'suspended') {
            return back()->withErrors(['email' => 'Akun ini telah disuspend.']);
        }

        // Check duplicate
        $accounts = $this->switcher->listAccounts($request);
        foreach ($accounts as $account) {
            if ($account['user_id'] === $user->id) {
                return back()->withErrors(['email' => 'Akun ini sudah ada di switcher.']);
        }
        }

        // Check max
        if ($this->switcher->isMaxReached($request)) {
            return back()->with('error', 'Maksimal ' . MultiAccountSession::MAX_ACCOUNTS . ' akun.');
        }

        RateLimiter::clear($key);

        // Regenerate session
        $request->session()->regenerate();

        // Login as new user
        Auth::login($user);

        // Add to switcher
        $this->switcher->addAccount($user, $request);

        $label = $user->vendor?->business_name ?? $user->name;

        return redirect()->route('home')
            ->with('success', "Berhasil masuk sebagai {$label}");
    }

    /**
     * Switch to another account
     */
    public function switch(Request $request, string $accountId)
    {
        $user = $this->switcher->switchTo($accountId, $request);

        if (!$user) {
            return back()->with('error', 'Sesi akun tersebut telah berakhir. Silakan login ulang.');
        }

        $label = $user->vendor?->business_name ?? $user->name;

        return redirect()->route('home')
            ->with('success', "Sekarang Anda masuk sebagai {$label}");
    }

    /**
     * Forget a single account (without logout)
     */
    public function forget(Request $request, string $accountId)
    {
        $this->switcher->forget($accountId, $request);
        return back()->with('success', 'Akun dihapus dari switcher.');
    }

    /**
     * Logout all accounts
     */
    public function logoutAll(Request $request)
    {
        $this->switcher->logoutAll($request);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Berhasil keluar dari semua akun.');
    }
}
