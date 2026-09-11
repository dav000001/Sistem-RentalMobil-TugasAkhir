<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Rental Mobil - Sewa Mobil Mudah & Terpercaya')</title>
    <meta name="description" content="@yield('meta', 'Platform rental mobil terpercaya dengan berbagai pilihan kendaraan di seluruh Indonesia')">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    {{-- Leaflet Maps --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @stack('head')
</head>
<body class="bg-gray-50">
    <x-navbar />
    
    <x-multi-session-banner />
    
    <x-flash />
    
    <main>
        @yield('content')
    </main>
    
    <x-footer />
    
    @livewireScripts
    @stack('scripts')
</body>
</html>
