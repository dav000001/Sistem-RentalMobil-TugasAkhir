<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Services\Auth\MultiAccountSession;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->with('error', 'Gagal login dengan Google. Silakan coba lagi.');
        }

        // Cari user berdasarkan email
        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            // Buat user baru sebagai customer
            $user = User::create([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'provider'          => 'google',
                'provider_id'       => $googleUser->getId(),
                'avatar_url'        => $googleUser->getAvatar(),
                'email_verified_at' => now(), // Google email sudah verified
                'status'            => 'active',
                'password'          => null,
            ]);

            // Buat profil customer
            Customer::create([
                'user_id'             => $user->id,
                'full_name'           => $googleUser->getName(),
                'verification_status' => 'pending',
            ]);
        } elseif (!$user->provider) {
            // User sudah ada via email manual — link ke Google
            $user->update([
                'provider'          => 'google',
                'provider_id'       => $googleUser->getId(),
                'avatar_url'        => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        }

        Auth::login($user, true);
        request()->session()->regenerate();
        request()->session()->forget('url.intended');

        // Add to account switcher
        app(MultiAccountSession::class)->addAccount($user, request());

        // Jika vendor tanpa profil customer → redirect ke panel vendor
        if ($user->vendor && ! $user->customer) {
            return redirect('/vendor')
                ->with('success', 'Selamat datang, ' . $user->vendor->business_name . '! Anda diarahkan ke panel vendor.');
        }

        // Jika customer baru (belum pernah upload dokumen) → arahkan ke profil untuk verifikasi
        $customer = $user->customer;
        if ($customer && $customer->verification_status === 'pending'
            && is_null($customer->ktp_url)
            && is_null($customer->sim_url)) {
            return redirect()->route('profile.edit')
                ->with('info', 'Selamat datang! Silakan upload dokumen identitas (KTP, SIM, Selfie) untuk mulai memesan mobil.');
        }

        $name = $user->vendor?->business_name ?? $user->name;
        return redirect()->route('home')
            ->with('success', "Selamat datang, {$name}!");
    }
}
