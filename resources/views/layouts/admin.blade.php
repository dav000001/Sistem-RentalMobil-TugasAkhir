<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - Rental Mobil')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">

    {{-- Top bar admin --}}
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14 items-center">
                {{-- Logo + link ke panel Filament --}}
                <a href="/admin" class="flex items-center gap-2 text-lg font-bold text-blue-600">
                    🚗 Rental Mobil
                    <span class="text-xs font-normal text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Admin</span>
                </a>

                {{-- Navigasi --}}
                <div class="hidden md:flex items-center gap-6 text-sm text-gray-600">
                    <a href="/admin" class="hover:text-blue-600 transition">Dashboard</a>
                    <a href="{{ route('admin.reports.index') }}" class="hover:text-blue-600 transition {{ request()->routeIs('admin.reports*') ? 'text-blue-600 font-medium' : '' }}">Laporan</a>
                    <a href="{{ route('admin.complaints.index') }}" class="hover:text-blue-600 transition {{ request()->routeIs('admin.complaints*') ? 'text-blue-600 font-medium' : '' }}">Komplain Customer</a>
                    <a href="{{ route('admin.vendor-reports.index') }}" class="hover:text-blue-600 transition {{ request()->routeIs('admin.vendor-reports*') ? 'text-orange-600 font-medium' : '' }}">Laporan Vendor</a>
                </div>

                {{-- User info + logout --}}
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-600 hidden md:block">
                        {{ auth('admin')->user()?->name ?? auth()->user()?->name }}
                    </span>
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
