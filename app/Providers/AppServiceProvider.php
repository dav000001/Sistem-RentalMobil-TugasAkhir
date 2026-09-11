<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Car;
use App\Models\CustomerRefund;
use App\Observers\CustomerRefundObserver;
use App\Policies\BookingPolicy;
use App\Policies\CarPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Set locale ke Bahasa Indonesia untuk seluruh aplikasi termasuk Filament
        App::setLocale('id');

        // Global 24-Hour WIB format for Filament DateTimePicker
        \Filament\Forms\Components\DateTimePicker::configureUsing(function (\Filament\Forms\Components\DateTimePicker $component) {
            $component
                ->native(false)
                ->displayFormat('d/m/Y H:i')
                ->seconds(false);
        });

        // Policy registration
        Gate::policy(Car::class, CarPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);

        // Observers
        CustomerRefund::observe(CustomerRefundObserver::class);

        // Admin (guard admin) dapat bypass semua gate
        Gate::before(function ($user, $ability) {
            if (auth('admin')->check() && auth('admin')->id() === $user->id) {
                return true;
            }
        });
    }
}
