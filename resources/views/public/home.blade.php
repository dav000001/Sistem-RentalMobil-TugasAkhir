@extends('layouts.app')

@section('title', 'Rental Mobil - Sewa Mobil Mudah & Terpercaya')

@section('content')
<!-- Hero Section -->
<section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">
                Sewa Mobil Mudah & Terpercaya
            </h1>
            <p class="text-xl md:text-2xl mb-8 text-blue-100">
                Ribuan pilihan mobil dari vendor terpercaya di seluruh Indonesia
            </p>
        </div>
        
        <div class="max-w-4xl mx-auto">
            <x-search-form :cities="$cities" />
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12">Kategori Mobil</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($categories as $category)
                <a href="{{ route('search', ['category_id' => $category->id]) }}" 
                   class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow text-center">
                    <div class="text-4xl mb-4">🚗</div>
                    <h3 class="font-semibold">{{ $category->name }}</h3>
                </a>
            @endforeach
        </div>
    </div>
</section>

<!-- Popular Cities -->
<section class="py-16 bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12">Kota Populer</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($cities as $city)
                <a href="{{ route('search', ['city_id' => $city->id]) }}" 
                   class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow text-center">
                    <h3 class="font-semibold text-lg">{{ $city->name }}</h3>
                    <p class="text-gray-600">{{ $city->cars_count }} mobil</p>
                </a>
            @endforeach
        </div>
    </div>
</section>

<!-- Featured Cars -->
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12">Mobil Pilihan</h2>
        @if($featuredCars->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-stretch">
                @foreach($featuredCars as $car)
                    <x-car-card :car="$car" />
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-500 text-lg">Belum ada mobil tersedia</p>
            </div>
        @endif
    </div>
</section>

<!-- How It Works -->
<section class="py-16 bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12">Cara Kerja</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="bg-blue-600 text-white w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">1</div>
                <h3 class="font-semibold text-lg mb-2">Cari Mobil</h3>
                <p class="text-gray-600">Pilih mobil sesuai kebutuhan dan lokasi Anda</p>
            </div>
            <div class="text-center">
                <div class="bg-blue-600 text-white w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">2</div>
                <h3 class="font-semibold text-lg mb-2">Pesan Online</h3>
                <p class="text-gray-600">Lakukan pemesanan dan pembayaran secara online</p>
            </div>
            <div class="text-center">
                <div class="bg-blue-600 text-white w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">3</div>
                <h3 class="font-semibold text-lg mb-2">Ambil Mobil</h3>
                <p class="text-gray-600">Ambil mobil di lokasi yang telah ditentukan</p>
            </div>
            <div class="text-center">
                <div class="bg-blue-600 text-white w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">4</div>
                <h3 class="font-semibold text-lg mb-2">Nikmati Perjalanan</h3>
                <p class="text-gray-600">Nikmati perjalanan Anda dengan aman dan nyaman</p>
            </div>
        </div>
    </div>
</section>
@endsection