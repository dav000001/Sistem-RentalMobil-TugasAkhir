@extends('layouts.app')
@section('title', 'Bandingkan Mobil - Rental Mobil')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">⚖️ Bandingkan Mobil</h1>
            <p class="text-gray-500 mt-1">Bandingkan hingga 3 mobil sekaligus</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('search') }}"
               class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-semibold transition">
                + Tambah Mobil
            </a>
            @if($cars->count() > 0)
                <form method="POST" action="{{ route('compare.clear') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm font-semibold transition">
                        ✕ Hapus Semua
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if($cars->count() >= 2)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    {{-- Header dengan foto --}}
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left p-4 w-40 bg-gray-50 text-sm font-semibold text-gray-600">Spesifikasi</th>
                            @foreach($cars as $car)
                                <th class="p-4 text-center min-w-56 bg-white">
                                    <div class="relative inline-block">
                                        {{-- Hapus dari compare --}}
                                        <form method="POST" action="{{ route('compare.toggle', $car->id) }}" class="absolute -top-1 -right-1 z-10">
                                            @csrf
                                            <button type="submit"
                                                    class="w-6 h-6 bg-red-500 text-white rounded-full text-xs hover:bg-red-600 transition flex items-center justify-center">
                                                ✕
                                            </button>
                                        </form>
                                        <img src="{{ $car->photos->first()?->path ?? 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=300&q=80' }}"
                                             alt="{{ $car->brand }}"
                                             class="w-full h-36 object-cover rounded-lg mb-2">
                                    </div>
                                    <p class="font-bold text-gray-900 text-sm">{{ $car->brand }} {{ $car->model }}</p>
                                    <p class="text-xs text-gray-500">{{ $car->year }}</p>
                                    @if($car->reviews_avg_rating)
                                        <div class="flex items-center justify-center gap-1 mt-1">
                                            <x-rating-stars :rating="$car->reviews_avg_rating" size="sm" />
                                            <span class="text-xs text-gray-500">{{ number_format($car->reviews_avg_rating, 1) }} ({{ $car->reviews_count }})</span>
                                        </div>
                                    @else
                                        <p class="text-xs text-gray-400 mt-1">Belum ada ulasan</p>
                                    @endif
                                </th>
                            @endforeach
                            {{-- Slot kosong jika kurang dari 3 --}}
                            @for($i = $cars->count(); $i < 3; $i++)
                                <th class="p-4 text-center min-w-56 bg-gray-50">
                                    <div class="border-2 border-dashed border-gray-300 rounded-xl h-36 flex items-center justify-center mb-2">
                                        <div class="text-center">
                                            <p class="text-3xl text-gray-300">+</p>
                                            <p class="text-xs text-gray-400 mt-1">Tambah Mobil</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('search') }}"
                                       class="text-blue-600 hover:underline text-xs font-semibold">
                                        Pilih dari pencarian →
                                    </a>
                                </th>
                            @endfor
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        {{-- Harga --}}
                        <tr class="bg-blue-50">
                            <td class="p-4 text-sm font-bold text-gray-700 bg-blue-50">💰 Harga/Hari</td>
                            @foreach($cars as $car)
                                <td class="p-4 text-center">
                                    <p class="text-xl font-bold text-blue-600">{{ formatRupiah($car->pricing->daily_price) }}</p>
                                    @if($car->pricing->with_driver_price)
                                        <p class="text-xs text-gray-500 mt-0.5">+ sopir {{ formatRupiah($car->pricing->with_driver_price) }}</p>
                                    @endif
                                </td>
                            @endforeach
                            @for($i = $cars->count(); $i < 3; $i++)<td class="p-4"></td>@endfor
                        </tr>

                        {{-- Kota --}}
                        <tr>
                            <td class="p-4 text-sm font-semibold text-gray-600 bg-gray-50">🏙️ Kota</td>
                            @foreach($cars as $car)
                                <td class="p-4 text-center text-sm text-gray-700">{{ $car->city->name }}</td>
                            @endforeach
                            @for($i = $cars->count(); $i < 3; $i++)<td class="p-4"></td>@endfor
                        </tr>

                        {{-- Kategori --}}
                        <tr class="bg-gray-50">
                            <td class="p-4 text-sm font-semibold text-gray-600 bg-gray-50">🏷️ Kategori</td>
                            @foreach($cars as $car)
                                <td class="p-4 text-center text-sm text-gray-700">{{ $car->category->name ?? '—' }}</td>
                            @endforeach
                            @for($i = $cars->count(); $i < 3; $i++)<td class="p-4"></td>@endfor
                        </tr>

                        {{-- Transmisi --}}
                        <tr>
                            <td class="p-4 text-sm font-semibold text-gray-600 bg-gray-50">⚙️ Transmisi</td>
                            @foreach($cars as $car)
                                <td class="p-4 text-center text-sm text-gray-700">{{ ucfirst($car->transmission) }}</td>
                            @endforeach
                            @for($i = $cars->count(); $i < 3; $i++)<td class="p-4"></td>@endfor
                        </tr>

                        {{-- Bahan Bakar --}}
                        <tr class="bg-gray-50">
                            <td class="p-4 text-sm font-semibold text-gray-600 bg-gray-50">⛽ Bahan Bakar</td>
                            @foreach($cars as $car)
                                <td class="p-4 text-center text-sm text-gray-700">{{ ucfirst($car->fuel ?? '—') }}</td>
                            @endforeach
                            @for($i = $cars->count(); $i < 3; $i++)<td class="p-4"></td>@endfor
                        </tr>

                        {{-- Kapasitas --}}
                        <tr>
                            <td class="p-4 text-sm font-semibold text-gray-600 bg-gray-50">👥 Kapasitas</td>
                            @foreach($cars as $car)
                                <td class="p-4 text-center text-sm text-gray-700">{{ $car->seats }} penumpang</td>
                            @endforeach
                            @for($i = $cars->count(); $i < 3; $i++)<td class="p-4"></td>@endfor
                        </tr>

                        {{-- Bagasi --}}
                        <tr class="bg-gray-50">
                            <td class="p-4 text-sm font-semibold text-gray-600 bg-gray-50">🧳 Bagasi</td>
                            @foreach($cars as $car)
                                <td class="p-4 text-center text-sm text-gray-700">
                                    {{ $car->luggage ? $car->luggage . ' koper' : '—' }}
                                </td>
                            @endforeach
                            @for($i = $cars->count(); $i < 3; $i++)<td class="p-4"></td>@endfor
                        </tr>

                        {{-- Fitur --}}
                        <tr>
                            <td class="p-4 text-sm font-semibold text-gray-600 bg-gray-50">✨ Fitur</td>
                            @foreach($cars as $car)
                                <td class="p-4 text-center">
                                    @if($car->features && count($car->features) > 0)
                                        <div class="flex flex-wrap gap-1 justify-center">
                                            @foreach(array_slice($car->features, 0, 5) as $feature)
                                                <span class="bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">{{ $feature }}</span>
                                            @endforeach
                                            @if(count($car->features) > 5)
                                                <span class="text-xs text-gray-400">+{{ count($car->features) - 5 }} lagi</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-sm">—</span>
                                    @endif
                                </td>
                            @endforeach
                            @for($i = $cars->count(); $i < 3; $i++)<td class="p-4"></td>@endfor
                        </tr>

                        {{-- Vendor --}}
                        <tr class="bg-gray-50">
                            <td class="p-4 text-sm font-semibold text-gray-600 bg-gray-50">🏢 Vendor</td>
                            @foreach($cars as $car)
                                <td class="p-4 text-center text-sm text-gray-700">{{ $car->vendor->business_name }}</td>
                            @endforeach
                            @for($i = $cars->count(); $i < 3; $i++)<td class="p-4"></td>@endfor
                        </tr>

                        {{-- Dengan Sopir --}}
                        <tr>
                            <td class="p-4 text-sm font-semibold text-gray-600 bg-gray-50">🧑‍✈️ Tersedia Sopir</td>
                            @foreach($cars as $car)
                                <td class="p-4 text-center text-sm">
                                    @if($car->pricing->with_driver_price)
                                        <span class="text-green-600 font-semibold">✅ Ya</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            @endforeach
                            @for($i = $cars->count(); $i < 3; $i++)<td class="p-4"></td>@endfor
                        </tr>

                        {{-- CTA --}}
                        <tr class="bg-blue-50">
                            <td class="p-4 bg-blue-50"></td>
                            @foreach($cars as $car)
                                <td class="p-4 text-center">
                                    <a href="{{ route('cars.show', $car->slug) }}"
                                       class="inline-block bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 text-sm font-bold transition">
                                        Pesan Sekarang →
                                    </a>
                                </td>
                            @endforeach
                            @for($i = $cars->count(); $i < 3; $i++)<td class="p-4"></td>@endfor
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="text-center py-16 bg-white rounded-xl shadow-sm">
            <div class="text-6xl mb-4">⚖️</div>
            <h3 class="text-xl font-semibold mb-2 text-gray-900">
                @if($cars->count() === 1)
                    Tambahkan 1 mobil lagi untuk membandingkan
                @else
                    Pilih minimal 2 mobil untuk dibandingkan
                @endif
            </h3>
            <p class="text-gray-500 mb-6">Klik tombol "⚖️ Bandingkan" di halaman pencarian atau detail mobil</p>
            <a href="{{ route('search') }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 inline-block font-semibold">
                🔍 Cari Mobil
            </a>
        </div>
    @endif
</div>
@endsection
