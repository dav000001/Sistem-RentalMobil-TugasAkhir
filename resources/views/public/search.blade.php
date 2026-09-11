@extends('layouts.app')

@section('title', 'Cari Mobil - Rental Mobil')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Search Form -->
    <div class="mb-8">
        <x-search-form :cities="$cities" />
    </div>
    
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Filters -->
        <div class="lg:w-1/4">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="font-semibold text-lg mb-4">Filter</h3>
                
                <form method="GET" action="{{ route('search') }}">
                    <!-- Keep existing search params -->
                    @foreach(request()->except(['category_id', 'transmission', 'seats_min', 'sort']) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    
                    <!-- Category Filter -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                        <select name="category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2" onchange="this.form.submit()">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Transmission Filter -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Transmisi</label>
                        <select name="transmission" class="w-full border border-gray-300 rounded-lg px-3 py-2" onchange="this.form.submit()">
                            <option value="">Semua</option>
                            <option value="manual" {{ request('transmission') == 'manual' ? 'selected' : '' }}>Manual</option>
                            <option value="automatic" {{ request('transmission') == 'automatic' ? 'selected' : '' }}>Automatic</option>
                        </select>
                    </div>
                    
                    <!-- Seats Filter -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Minimal Kursi</label>
                        <select name="seats_min" class="w-full border border-gray-300 rounded-lg px-3 py-2" onchange="this.form.submit()">
                            <option value="">Semua</option>
                            <option value="5" {{ request('seats_min') == '5' ? 'selected' : '' }}>5+ kursi</option>
                            <option value="7" {{ request('seats_min') == '7' ? 'selected' : '' }}>7+ kursi</option>
                        </select>
                    </div>

                    {{-- ── Filter Tanggal Ketersediaan ── --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            📅 Cek Ketersediaan Tanggal
                        </label>
                        <div class="space-y-2">
                            <div>
                                <label class="text-xs text-gray-500">Tanggal Mulai</label>
                                <input type="date" name="start_at"
                                    value="{{ request('start_at') }}"
                                    min="{{ now()->addDay()->toDateString() }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Tanggal Selesai</label>
                                <input type="date" name="end_at"
                                    value="{{ request('end_at') }}"
                                    min="{{ now()->addDays(2)->toDateString() }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            </div>
                            <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 rounded-lg transition">
                                Cek Ketersediaan
                            </button>
                            @if(request('start_at') || request('end_at'))
                                <a href="{{ request()->fullUrlWithoutQuery(['start_at','end_at']) }}"
                                    class="block text-center text-xs text-gray-400 hover:text-gray-600 mt-1">
                                    ✕ Hapus filter tanggal
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="lg:w-3/4">
            <!-- Livewire Search Component -->
            <livewire:search-cars 
                :city_id="request('city_id', '')" 
                :category_id="request('category_id', '')"
                :transmission="request('transmission', '')"
                :seats_min="request('seats_min', '')"
                :sort="request('sort', 'newest')"
            />
            
            <!-- CTA Vendor -->
            <div class="mt-8 text-center text-sm text-gray-500">
                Tidak menemukan yang cocok? Punya mobil sendiri?
                <a href="{{ route('vendor.landing') }}" class="text-orange-600 hover:text-orange-700 font-medium">Daftar sebagai vendor →</a>
            </div>
        </div>
    </div>
</div>
@endsection