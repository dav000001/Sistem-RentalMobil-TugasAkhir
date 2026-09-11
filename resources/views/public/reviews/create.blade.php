@extends('layouts.app')
@section('title', 'Beri Review - ' . $booking->code)

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('bookings.show', $booking->code) }}" class="text-blue-600 hover:underline text-sm">← Kembali ke Pesanan</a>
        <h1 class="text-2xl font-bold mt-2">Beri Review</h1>
        <p class="text-gray-500 text-sm mt-1">Bagikan pengalaman Anda agar membantu penyewa lain</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center space-x-4">
            <img src="{{ $booking->car->photos->first()?->path ?? '/images/cars/toyota-avanza.jpg' }}"
                 alt="{{ $booking->car->brand }}" class="w-16 h-16 object-cover rounded-lg">
            <div>
                <h3 class="font-semibold">{{ $booking->car->brand }} {{ $booking->car->model }} {{ $booking->car->year }}</h3>
                <p class="text-gray-500 text-sm">{{ $booking->vendor->business_name }}</p>
                <p class="text-gray-400 text-xs">{{ $booking->start_at->format('d M Y') }} — {{ $booking->end_at->format('d M Y') }}</p>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            @foreach ($errors->all() as $error)
                <p class="text-red-700 text-sm">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('bookings.review.store', $booking->code) }}"
          class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6"
          x-data="{ rating: {{ old('rating', 0) }}, hovered: 0 }">
        @csrf

        {{-- Rating Bintang --}}
        <div>
            <label class="block text-sm font-semibold text-gray-800 mb-3">
                Rating <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center space-x-2">
                @for($i = 1; $i <= 5; $i++)
                    <button type="button"
                            @click="rating = {{ $i }}"
                            @mouseenter="hovered = {{ $i }}"
                            @mouseleave="hovered = 0"
                            class="text-4xl transition-transform hover:scale-110 focus:outline-none">
                        <span :class="(hovered >= {{ $i }} || rating >= {{ $i }}) ? 'text-yellow-400' : 'text-gray-300'">★</span>
                    </button>
                @endfor
                <span class="ml-3 text-sm text-gray-600" x-text="['', 'Sangat Buruk', 'Buruk', 'Cukup', 'Bagus', 'Sangat Bagus'][rating]"></span>
            </div>
            <input type="hidden" name="rating" :value="rating">
            @error('rating')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Komentar --}}
        <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">
                Komentar <span class="text-gray-400 font-normal">(opsional)</span>
            </label>
            <textarea name="comment" rows="4"
                      placeholder="Ceritakan pengalaman Anda — kondisi mobil, pelayanan vendor, ketepatan waktu, dll."
                      class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                      maxlength="1000">{{ old('comment') }}</textarea>
            <p class="text-gray-400 text-xs mt-1">Maks. 1000 karakter</p>
        </div>

        <div class="flex justify-between items-center pt-2">
            <a href="{{ route('bookings.show', $booking->code) }}"
               class="text-gray-500 hover:text-gray-700 text-sm">Batal</a>
            <button type="submit"
                    :disabled="rating === 0"
                    :class="rating === 0 ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                    class="text-white px-8 py-3 rounded-lg font-semibold transition">
                Kirim Review
            </button>
        </div>
    </form>
</div>
@endsection
