@props(['lat', 'lng', 'zoom' => 14, 'height' => '300px', 'label' => 'Lokasi', 'address' => null])

@if($lat && $lng)
    @php $mapId = 'map-' . uniqid(); @endphp
    <div>
        <div id="{{ $mapId }}" style="height: {{ $height }}; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;"></div>
        @if($address)
            <p class="text-sm text-gray-600 mt-2">📍 {{ $address }}</p>
        @endif
        <a href="https://www.openstreetmap.org/?mlat={{ $lat }}&mlon={{ $lng }}#map=16/{{ $lat }}/{{ $lng }}"
           target="_blank" class="text-xs text-blue-600 hover:underline mt-1 inline-block">
            Buka di peta →
        </a>
    </div>

    <script>
        (function() {
            var map = L.map('{{ $mapId }}').setView([{{ $lat }}, {{ $lng }}], {{ $zoom }});
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);
            L.marker([{{ $lat }}, {{ $lng }}])
                .addTo(map)
                .bindPopup('{{ addslashes($label) }}')
                .openPopup();
        })();
    </script>
@else
    <div class="bg-gray-100 rounded-xl flex items-center justify-center text-gray-400 text-sm" style="height: {{ $height }}">
        📍 Lokasi belum diatur
    </div>
@endif
