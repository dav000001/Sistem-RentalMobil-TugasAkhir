@props(['icon' => '📭', 'title' => 'Tidak ada data', 'description' => '', 'actionLabel' => null, 'actionUrl' => null])

<div class="text-center py-16">
    <div class="text-6xl mb-4">{{ $icon }}</div>
    <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ $title }}</h3>
    @if($description)
        <p class="text-gray-500 mb-6">{{ $description }}</p>
    @endif
    @if($actionLabel && $actionUrl)
        <a href="{{ $actionUrl }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 inline-block">
            {{ $actionLabel }}
        </a>
    @endif
</div>
