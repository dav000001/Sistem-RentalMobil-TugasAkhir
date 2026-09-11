<?php

namespace App\Http\Controllers\Web;

use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterVendorRequest;
use App\Models\City;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Auth\MultiAccountSession;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class VendorRegistrationController extends Controller
{
    public function create()
    {
        $cities = City::orderBy('name')->get();
        return view('auth.register-vendor', compact('cities'));
    }

    public function store(RegisterVendorRequest $request)
    {
        // Rate limit: 5 per minute per IP
        $key = 'vendor-register:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['email' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik."]);
        }
        RateLimiter::hit($key, 60);

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name'     => $request->string('name'),
                'email'    => $request->string('email'),
                'phone'    => $request->string('phone'),
                'password' => Hash::make($request->string('password')),
                'role'     => 'vendor',
                'status'   => 'active',
            ]);

            // Jika vendor tulis kota sendiri, buat kota baru di tabel cities
            $cityId = $request->integer('city_id') ?: null;
            if (!$cityId && $request->filled('city_name')) {
                $newCity = \App\Models\City::firstOrCreate(
                    ['name' => Str::title(trim($request->string('city_name')))],
                    ['province' => 'Lainnya']
                );
                $cityId = $newCity->id;
            }

            Vendor::create([
                'user_id'             => $user->id,
                'business_name'       => $request->string('business_name'),
                'business_type'       => $request->string('business_type'),
                'city_id'             => $cityId,
                'fleet_size_estimate' => $request->string('fleet_size_estimate'),
                'lead_source'         => $request->string('source') ?: null,
                'status'              => VendorStatus::Pending->value,
                'address'             => '',
            ]);

            // Otomatis buat customer profile agar vendor bisa juga booking sebagai customer
            Customer::create([
                'user_id'             => $user->id,
                'full_name'           => $request->string('name'),
                'verification_status' => 'pending',
            ]);

            return $user;        });

        event(new Registered($user));
        Auth::login($user);

        // Add to account switcher
        app(MultiAccountSession::class)->addAccount($user, $request);

        RateLimiter::clear($key);

        // Kirim notifikasi ke semua admin: vendor baru mendaftar
        try {
            User::where('role', 'admin')->get()->each(function ($admin) use ($user) {
                $admin->notify(new \App\Notifications\NewVendorRegisteredNotification($user->load('vendor')));
            });
        } catch (\Throwable) {}

        return redirect()->route('vendor.onboarding')
            ->with('success', 'Selamat datang, ' . $user->name . '! Lengkapi dokumen verifikasi untuk mengaktifkan akun vendor Anda.');
    }

    /**
     * Upgrade existing customer to vendor
     */
    public function upgrade(Request $request)
    {
        $validated = $request->validate([
            'business_name'       => ['required', 'string', 'min:3', 'max:120'],
            'business_type'       => ['required', 'in:perorangan,cv,pt,komunitas'],
            'city_id'             => ['required', 'exists:cities,id'],
            'fleet_size_estimate' => ['required', 'in:1-2,3-5,6-10,>10'],
        ]);

        $user = auth()->user();

        if ($user->vendor) {
            return redirect()->route('vendor.onboarding')
                ->with('info', 'Anda sudah memiliki akun vendor.');
        }

        Vendor::create([
            'user_id'             => $user->id,
            'business_name'       => $validated['business_name'],
            'business_type'       => $validated['business_type'],
            'city_id'             => $validated['city_id'],
            'fleet_size_estimate' => $validated['fleet_size_estimate'],
            'status'              => VendorStatus::Pending->value,
            'address'             => '',
        ]);

        // Update role ke vendor
        $user->update(['role' => 'vendor']);

        return redirect()->route('vendor.onboarding')
            ->with('success', 'Akun Anda berhasil diupgrade ke Vendor. Lengkapi dokumen verifikasi.');
    }
}
