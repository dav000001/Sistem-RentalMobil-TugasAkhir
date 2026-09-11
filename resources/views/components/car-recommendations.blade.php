@props(['booking'])

@php
    use App\Services\CarRecommendationService;
    
    $recommendationService = app(CarRecommendationService::class);
    $recommendations = $recommendationService->getRecommendations($booking);
@endphp

@if($booking->status === 'cancelled' && $recommendations->isNotEmpty())
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 mb-8">
        <div class="mb-4">
            <h3 class="text-lg font-bold text-gray-900 mb-2">🚗 Mobil Serupa yang Tersedia</h3>
            <p class="text-sm text-gray-600">
                Kami menemukan beberapa mobil serupa dari vendor lain yang mungkin Anda minati:
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($recommendations as $rec)
                @php $car = $rec->car; @endphp
                <div class="bg-white rounded-lg border border-gray-200 hover:border-blue-400 hover:shadow-md transition overflow-hidden">
                    <div class="relative h-40 bg-gray-200">
                        <img src="{{ $car->photos->first()?->path ?? 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=400&q=80' }}"
                             alt="{{ $car->brand }} {{ $car->model }}"
                             class="w-full h-full object-cover"
                             loading="lazy">
                        <div class="absolute top-2 right-2 bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded-full">
                            #{{ $rec->rank }}
                        </div>
                    </div>
                    <div class="p-4">
                        <h4 class="font-bold text-gray-900 mb-1">{{ $car->brand }} {{ $car->model }}</h4>
                        <p class="text-xs text-gray-500 mb-2">{{ $car->year }} • {{ ucfirst($car->transmission) }}</p>
                        
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs text-gray-600">📍 {{ $car->city->name }}</span>
                            @if($car->vendor->reviews_avg_rating)
                                <span class="text-xs text-gray-600">⭐ {{ number_format($car->vendor->reviews_avg_rating, 1) }}</span>
                            @endif
                        </div>

                        <p class="text-xs text-gray-500 mb-3">{{ $car->vendor->business_name }}</p>

                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-lg font-bold text-blue-600">{{ formatRupiah($car->pricing->daily_price) }}</p>
                                <p class="text-xs text-gray-500">per hari</p>
                            </div>
                            <a href="{{ route('cars.show', $car->slug) }}?from_recommendation={{ $rec->id }}"
                               onclick="markRecommendationClicked({{ $rec->id }}, '{{ csrf_token() }}')"
                               class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('search', ['city_id' => $booking->car->city_id, 'category_id' => $booking->car->category_id]) }}"
               class="text-sm text-blue-600 hover:text-blue-700 font-medium hover:underline">
                Lihat lebih banyak mobil di {{ $booking->car->city->name }} →
            </a>
        </div>
    </div>

    <script>
        function markRecommendationClicked(recId, csrfToken) {
            fetch('/recommendations/' + recId + '/clicked', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            }).catch(() => {});
        }
    </script>
@elseif($booking->status === 'cancelled' && $recommendations->isEmpty())
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-8 text-center">
        <div class="text-4xl mb-3">🚗</div>
        <p class="font-semibold text-gray-700 mb-2">Belum ada mobil serupa yang tersedia</p>
        <p class="text-sm text-gray-600 mb-4">Silakan jelajahi mobil lain yang tersedia di {{ $booking->car->city->name }}</p>
        <a href="{{ route('search', ['city_id' => $booking->car->city_id]) }}"
           class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg transition">
            Jelajahi Mobil Lainnya
        </a>
    </div>
@endif
