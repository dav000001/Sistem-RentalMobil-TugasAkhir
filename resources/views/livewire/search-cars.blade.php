<div>
    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <select wire:model.live="city_id" class="border border-gray-300 rounded-lg px-3 py-2">
            <option value="">Semua Kota</option>
            @foreach(\App\Models\City::all() as $city)
                <option value="{{ $city->id }}">{{ $city->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="category_id" class="border border-gray-300 rounded-lg px-3 py-2">
            <option value="">Semua Kategori</option>
            @foreach(\App\Models\Category::all() as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="transmission" class="border border-gray-300 rounded-lg px-3 py-2">
            <option value="">Semua Transmisi</option>
            <option value="manual">Manual</option>
            <option value="automatic">Automatic</option>
        </select>

        <select wire:model.live="sort" class="border border-gray-300 rounded-lg px-3 py-2">
            <option value="newest">Terbaru</option>
            <option value="price_asc">Harga Termurah</option>
            <option value="price_desc">Harga Termahal</option>
            <option value="rating">Rating Tertinggi</option>
        </select>
    </div>

    <!-- Filter Tanggal inline (di atas grid hasil) -->
    <div class="mb-4 flex flex-wrap gap-3 items-end bg-blue-50 border border-blue-200 rounded-xl p-3">
        <div>
            <label class="block text-xs font-medium text-blue-700 mb-1">📅 Tanggal Mulai</label>
            <input wire:model.live="start_at" type="date"
                   min="{{ now()->addDay()->toDateString() }}"
                   class="border border-blue-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-blue-700 mb-1">📅 Tanggal Selesai</label>
            <input wire:model.live="end_at" type="date"
                   min="{{ now()->addDays(2)->toDateString() }}"
                   class="border border-blue-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="flex items-end">
            <p class="text-xs text-blue-600 max-w-xs">
                Isi tanggal untuk melihat badge <strong>Tersedia</strong> / <strong>Tidak Tersedia</strong> berdasarkan tanggal pilihan Anda.
            </p>
        </div>
        @if($start_at || $end_at)
            <button wire:click="$set('start_at', ''); $set('end_at', '')"
                class="text-xs text-blue-400 hover:text-blue-600 self-end pb-0.5">
                ✕ Hapus filter tanggal
            </button>
        @endif
    </div>

    <!-- Results Count -->
    <div class="mb-4 flex items-center justify-between flex-wrap gap-2">
        <p class="text-gray-600">{{ $cars->total() }} mobil ditemukan</p>

        @if($hasDateFilter)
            <div class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg px-3 py-1.5 text-sm text-blue-700">
                📅 Menampilkan ketersediaan untuk:
                <strong>{{ \Carbon\Carbon::parse($start_at)->format('d M Y') }}</strong>
                —
                <strong>{{ \Carbon\Carbon::parse($end_at)->format('d M Y') }}</strong>
                <a href="{{ request()->fullUrlWithoutQuery(['start_at','end_at']) }}"
                   class="ml-1 text-blue-400 hover:text-blue-600">✕</a>
            </div>
        @endif
    </div>

    <!-- Date filter inputs (sync dengan sidebar) -->
    <div class="hidden">
        <input wire:model.live="start_at" type="date" id="lw-start-at">
        <input wire:model.live="end_at"   type="date" id="lw-end-at">
    </div>

    <!-- Cars Grid -->
    @if($cars->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($cars as $car)
                @php
                    $availInfo = null;
                    if ($hasDateFilter && isset($availabilityMap[$car->id])) {
                        $availInfo = $availabilityMap[$car->id] ? 'available' : 'unavailable';
                    }
                @endphp
                <x-car-card :car="$car" :availability="$availInfo" />
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $cars->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <div class="text-6xl mb-4">🔍</div>
            <h3 class="text-xl font-semibold mb-2">Tidak ada mobil ditemukan</h3>
            <p class="text-gray-600 mb-4">Coba ubah filter pencarian Anda</p>
            <button wire:click="$set('city_id', '')" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                Reset Filter
            </button>
        </div>
    @endif
</div>
