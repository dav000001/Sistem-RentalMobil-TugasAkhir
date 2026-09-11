@props(['car', 'availability' => null])

@php
    $activeBooking  = $car->activeBooking();
    $isUnavailable  = $car->status === 'unavailable';

    // Tentukan apakah mobil benar-benar sedang digunakan SEKARANG
    // (bukan hanya confirmed tapi belum mulai)
    $isCurrentlyOngoing = !is_null($activeBooking)
        && in_array($activeBooking->status, ['ongoing'])
        && $activeBooking->start_at <= now();

    // Mobil sudah dipesan tapi belum mulai
    $isBooked = !is_null($activeBooking) && !$isCurrentlyOngoing;

    $unavailableLabel = match($car->unavailability_reason) {
        'service'    => '🔧 Sedang Service',
        'rusak'      => '⚠️ Sedang Rusak',
        'kecelakaan' => '🚨 Kecelakaan',
        'lainnya'    => '⛔ Tidak Tersedia',
        default      => '⛔ Tidak Tersedia',
    };

    $vendorSub  = $car->vendor?->currentSubscription;
    $hasBadge   = $vendorSub && ($vendorSub->snapshot_features['badge_verified'] ?? false);
    $isPriority = $vendorSub && ($vendorSub->snapshot_features['priority_search'] ?? false);

    // Tentukan badge status akhir
    if ($availability === 'available') {
        $statusBadge = ['label' => '✅ Tersedia', 'class' => 'bg-green-500 text-white'];
    } elseif ($availability === 'unavailable') {
        $statusBadge = ['label' => '❌ Tidak Tersedia', 'class' => 'bg-red-500 text-white'];
    } elseif ($isUnavailable) {
        $statusBadge = ['label' => $unavailableLabel, 'class' => 'bg-red-500 text-white'];
    } elseif ($isCurrentlyOngoing) {
        $statusBadge = ['label' => '🚗 Sedang Disewa', 'class' => 'bg-orange-500 text-white'];
    } else {
        $statusBadge = ['label' => '✓ Tersedia', 'class' => 'bg-green-500 text-white'];
    }
@endphp

<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow flex flex-col h-full
    {{ ($isUnavailable || $availability === 'unavailable') ? 'opacity-80' : '' }}">
    <div class="relative">
        <img src="{{ $car->photos->first()?->path ?? 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=400&q=80' }}"
             alt="{{ $car->brand }} {{ $car->model }}"
             loading="lazy"
             class="w-full h-48 object-cover
                {{ ($isUnavailable || $availability === 'unavailable') ? 'grayscale' : '' }}">

        {{-- Badge status --}}
        <div class="absolute top-2 left-2">
            <span class="px-2 py-1 rounded-full text-xs font-semibold shadow {{ $statusBadge['class'] }}">
                {{ $statusBadge['label'] }}
            </span>
        </div>

        @if($car->reviews_avg_rating)
            <div class="absolute top-2 right-2 bg-white px-2 py-1 rounded-full text-sm font-semibold flex items-center space-x-1 shadow">
                <x-rating-stars :rating="$car->reviews_avg_rating" size="sm" />
                <span class="text-gray-700">{{ number_format($car->reviews_avg_rating, 1) }}</span>
            </div>
        @else
            <div class="absolute top-2 right-2 bg-white px-2 py-1 rounded-full text-xs text-gray-400 shadow">
                Belum ada ulasan
            </div>
        @endif
    </div>
    
    <div class="p-4 flex flex-col flex-1">
        <h3 class="font-semibold text-lg mb-2 line-clamp-1">{{ $car->brand }} {{ $car->model }} {{ $car->year }}</h3>
        
        <div class="flex items-center text-gray-600 text-sm mb-2 flex-wrap gap-x-3 gap-y-1">
            <span class="flex items-center gap-1 whitespace-nowrap">🏙️ {{ $car->city->name }}</span>
            <span class="flex items-center gap-1 whitespace-nowrap">⚙️ {{ ucfirst($car->transmission) }}</span>
            <span class="flex items-center gap-1 whitespace-nowrap">👥 {{ $car->seats }} kursi</span>
        </div>
        
        <div class="flex items-center text-gray-600 text-sm mb-3 truncate">
            <span class="truncate">🏢 {{ $car->vendor->business_name }}</span>
            @if($hasBadge)
                <span class="ml-1.5 flex-shrink-0 bg-blue-100 text-blue-700 text-xs font-bold px-1.5 py-0.5 rounded-full" title="Vendor Terverifikasi">✓ Verified</span>
            @endif
        </div>

        {{-- Info ketidaktersediaan --}}
        @if($isUnavailable)
            <div class="bg-red-50 border border-red-200 rounded-lg p-2 mb-3">
                <p class="text-xs text-red-700 font-medium">{{ $unavailableLabel }}</p>
                @if($car->unavailability_notes)
                    <p class="text-xs text-red-600 mt-0.5">{{ $car->unavailability_notes }}</p>
                @endif
                @if($car->unavailable_until)
                    <p class="text-xs text-red-600 mt-0.5">📅 Estimasi tersedia: {{ $car->unavailable_until->format('d M Y') }}</p>
                @endif
            </div>
        @elseif($isCurrentlyOngoing)
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-2 mb-3">
                <p class="text-xs text-orange-700 font-medium">
                    📅 Tersedia kembali: {{ \Carbon\Carbon::parse($activeBooking->end_at)->timezone('Asia/Jakarta')->format('d M Y') }}
                </p>
            </div>
        @endif
        
        <div class="flex justify-between items-center mt-auto pt-3">
            <div>
                <p class="text-2xl font-bold text-blue-600">{{ formatRupiah($car->pricing->daily_price) }}</p>
                <p class="text-sm text-gray-500">per hari</p>
                @if($car->pricing->with_driver_price)
                    <p class="text-xs text-gray-500">+ sopir {{ formatRupiah($car->pricing->with_driver_price) }}</p>
                @endif
            </div>
            <a href="{{ route('cars.show', $car->slug) }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors text-sm">
                Lihat Detail
            </a>
        </div>

        {{-- Wishlist & Compare (hanya tampil jika auth) --}}
        @auth
        <div class="flex gap-2 mt-3 pt-3 border-t border-gray-100">
            {{-- Wishlist toggle --}}
            @php
                $inWishlist = \App\Models\Wishlist::where('user_id', auth()->id())
                    ->where('car_id', $car->id)->exists();
                $compareIds = session('compare_ids', []);
                $inCompare  = in_array($car->id, $compareIds);
            @endphp
            <form method="POST" action="{{ route('wishlist.toggle', $car->id) }}" class="flex-1">
                @csrf
                <button type="submit"
                        class="w-full flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold transition border
                               {{ $inWishlist
                                   ? 'bg-red-50 border-red-200 text-red-600 hover:bg-red-100'
                                   : 'bg-gray-50 border-gray-200 text-gray-500 hover:bg-gray-100' }}">
                    {{ $inWishlist ? '❤️' : '🤍' }}
                    {{ $inWishlist ? 'Tersimpan' : 'Simpan' }}
                </button>
            </form>
            {{-- Compare toggle --}}
            <form method="POST" action="{{ route('compare.toggle', $car->id) }}" class="flex-1">
                @csrf
                <button type="submit"
                        class="w-full flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold transition border
                               {{ $inCompare
                                   ? 'bg-blue-50 border-blue-200 text-blue-600 hover:bg-blue-100'
                                   : 'bg-gray-50 border-gray-200 text-gray-500 hover:bg-gray-100' }}">
                    ⚖️ {{ $inCompare ? 'Dibandingkan' : 'Bandingkan' }}
                </button>
            </form>
        </div>
        @endauth
    </div>
</div>
