@extends('layouts.app')
@section('title', 'Wishlist Saya - Rental Mobil')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">❤️ Wishlist Saya</h1>
            <p class="text-gray-500 mt-1">{{ $cars->count() }} mobil tersimpan</p>
        </div>
        @if($cars->count() > 0)
            <a href="{{ route('search') }}"
               class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition font-semibold text-sm">
                🔍 Cari Mobil Lain
            </a>
        @endif
    </div>

    @if($cars->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($cars as $car)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow group">
                    {{-- Foto --}}
                    <div class="relative">
                        <img src="{{ $car->photos->first()?->path ?? 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=400&q=80' }}"
                             alt="{{ $car->brand }} {{ $car->model }}"
                             class="w-full h-44 object-cover group-hover:scale-105 transition-transform duration-300">

                        {{-- Tombol hapus wishlist --}}
                        <form method="POST" action="{{ route('wishlist.remove', $car->id) }}" class="absolute top-2 right-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-8 h-8 bg-white rounded-full shadow flex items-center justify-center text-red-500 hover:bg-red-50 transition"
                                    title="Hapus dari wishlist">
                                ❤️
                            </button>
                        </form>

                        {{-- Status badge --}}
                        @php $isBooked = $car->activeBooking() !== null; @endphp
                        <div class="absolute top-2 left-2">
                            @if($car->status === 'unavailable')
                                <span class="bg-red-500 text-white px-2 py-0.5 rounded-full text-xs font-semibold">⛔ Tidak Tersedia</span>
                            @elseif($isBooked)
                                <span class="bg-orange-500 text-white px-2 py-0.5 rounded-full text-xs font-semibold">🕐 Sedang Disewa</span>
                            @else
                                <span class="bg-green-500 text-white px-2 py-0.5 rounded-full text-xs font-semibold">✓ Tersedia</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-4">
                        <h3 class="font-bold text-gray-900 truncate">{{ $car->brand }} {{ $car->model }} {{ $car->year }}</h3>
                        <p class="text-sm text-gray-500 mt-0.5">🏙️ {{ $car->city->name }} · ⚙️ {{ ucfirst($car->transmission) }}</p>
                        <p class="text-sm text-gray-500 mt-0.5 truncate">🏢 {{ $car->vendor->business_name }}</p>

                        @if($car->reviews_avg_rating)
                            <div class="flex items-center gap-1 mt-1">
                                <x-rating-stars :rating="$car->reviews_avg_rating" size="sm" />
                                <span class="text-xs text-gray-500">{{ number_format($car->reviews_avg_rating, 1) }}</span>
                            </div>
                        @endif

                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                            <div>
                                <p class="text-lg font-bold text-blue-600">{{ formatRupiah($car->pricing->daily_price) }}</p>
                                <p class="text-xs text-gray-400">per hari</p>
                            </div>
                            <a href="{{ route('cars.show', $car->slug) }}"
                               class="bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 text-xs font-semibold transition">
                                Lihat Detail →
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-16 bg-white rounded-xl shadow-sm">
            <div class="text-6xl mb-4">❤️</div>
            <h3 class="text-xl font-semibold mb-2 text-gray-900">Wishlist masih kosong</h3>
            <p class="text-gray-500 mb-6">Simpan mobil favorit Anda dengan menekan tombol ❤️ di halaman detail mobil</p>
            <a href="{{ route('search') }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 inline-block font-semibold">
                🔍 Jelajahi Mobil
            </a>
        </div>
    @endif
</div>
@endsection
