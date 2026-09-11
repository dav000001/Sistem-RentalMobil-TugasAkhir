@props(['cities', 'action' => null])

<form action="{{ $action ?? route('search') }}" method="GET" class="bg-white p-6 rounded-lg shadow-lg">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">Kota</label>
            <select name="city_id" class="w-full bg-white text-gray-900 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="" class="text-gray-900">Pilih Kota</option>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}" class="text-gray-900" {{ request('city_id') == $city->id ? 'selected' : '' }}>
                        {{ $city->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">Tanggal Mulai</label>
            <input type="date" name="start_date"
                   value="{{ request('start_date', now()->addDay()->format('Y-m-d')) }}"
                   style="color: #111827; background-color: #ffffff;"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">Tanggal Selesai</label>
            <input type="date" name="end_date"
                   value="{{ request('end_date', now()->addDays(2)->format('Y-m-d')) }}"
                   style="color: #111827; background-color: #ffffff;"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div class="flex items-end">
            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors font-semibold">
                🔍 Cari Mobil
            </button>
        </div>
    </div>
</form>