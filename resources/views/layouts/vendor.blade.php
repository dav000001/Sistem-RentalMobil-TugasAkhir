<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Vendor - Rental Mobil')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">

    {{-- Top bar vendor --}}
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14 items-center">
                {{-- Logo --}}
                <a href="/vendor" class="flex items-center gap-2 text-lg font-bold text-orange-600">
                    🚗 Rental Mobil
                    <span class="text-xs font-normal text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Vendor</span>
                </a>

                {{-- Navigasi --}}
                <div class="hidden md:flex items-center gap-6 text-sm text-gray-600">
                    <a href="/vendor" class="hover:text-orange-600 transition {{ request()->is('vendor') ? 'text-orange-600 font-medium' : '' }}">Dashboard</a>
                    <a href="/vendor/cars" class="hover:text-orange-600 transition {{ request()->is('vendor/cars*') ? 'text-orange-600 font-medium' : '' }}">Armada Saya</a>
                    <a href="/vendor/bookings" class="hover:text-orange-600 transition {{ request()->is('vendor/bookings*') ? 'text-orange-600 font-medium' : '' }}">Pemesanan</a>
                    <a href="{{ route('vendor.complaints.index') }}" class="hover:text-orange-600 transition {{ request()->is('vendor/complaints*') ? 'text-orange-600 font-medium' : '' }}">Komplain Customer</a>
                    <a href="{{ route('vendor.my-reports.index') }}" class="hover:text-orange-600 transition {{ request()->is('vendor/my-reports*') ? 'text-orange-600 font-medium' : '' }}">Laporan Saya</a>
                </div>

                {{-- User info + logout --}}
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-600 hidden md:block">
                        {{ auth('vendor')->user()?->name ?? auth()->user()?->name }}
                    </span>
                    <a href="/vendor" class="text-sm text-orange-600 hover:text-orange-700 font-medium">
                        Panel Vendor →
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="text-sm text-red-500 hover:text-red-700 border border-red-200 px-3 py-1.5 rounded-lg hover:bg-red-50 transition">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <x-flash />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    @livewireScripts
    @stack('scripts')
</body>
</html>
