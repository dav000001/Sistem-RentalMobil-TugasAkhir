@extends('layouts.app')

@section('title', 'Laporkan Masalah - ' . $booking->code)

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('bookings.show', $booking->code) }}" class="text-blue-600 hover:underline text-sm">← Kembali ke Pesanan</a>
        <h1 class="text-2xl font-bold mt-2">Laporkan Masalah</h1>
        <p class="text-gray-600 text-sm mt-1">Pesanan: <strong>{{ $booking->code }}</strong> — {{ $booking->car->brand }} {{ $booking->car->model }}</p>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('complaints.store', $booking->id) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Step 1: Category --}}
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h2 class="font-semibold text-lg mb-4">1. Pilih Kategori Masalah</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($categories as $cat)
                    <label class="flex items-start space-x-3 p-3 border rounded-lg cursor-pointer hover:bg-blue-50 transition
                        {{ old('category_id') == $cat->id ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                        <input type="radio" name="category_id" value="{{ $cat->id }}"
                               {{ old('category_id') == $cat->id ? 'checked' : '' }}
                               class="mt-1 text-blue-600" required>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-sm">{{ $cat->name }}</span>
                                <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-semibold
                                    @if($cat->severity === 'low') bg-green-100 text-green-700
                                    @elseif($cat->severity === 'medium') bg-yellow-100 text-yellow-700
                                    @elseif($cat->severity === 'high') bg-orange-100 text-orange-700
                                    @else bg-red-100 text-red-700
                                    @endif">
                                    {{ ucfirst($cat->severity) }}
                                </span>
                            </div>
                            @if($cat->description)
                                <p class="text-gray-500 text-xs mt-1">{{ $cat->description }}</p>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
            @error('category_id')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        {{-- Step 2: Description --}}
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h2 class="font-semibold text-lg mb-4">2. Jelaskan Masalah Anda</h2>
            <textarea name="description" rows="6"
                      placeholder="Ceritakan masalah yang Anda alami secara detail. Minimal 50 karakter."
                      class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none
                             @error('description') border-red-500 @enderror"
                      required minlength="50" maxlength="5000">{{ old('description') }}</textarea>
            <p class="text-gray-400 text-xs mt-1">Minimal 50 karakter. Semakin detail, semakin cepat ditangani.</p>
            @error('description')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Step 3: Evidence --}}
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h2 class="font-semibold text-lg mb-2">3. Bukti Pendukung <span class="text-gray-400 font-normal text-sm">(opsional)</span></h2>
            <p class="text-gray-500 text-sm mb-3">Unggah foto atau dokumen pendukung (maks. 5 file, maks. 5MB per file).</p>
            <input type="file" name="evidence[]" multiple accept="image/jpeg,image/png,application/pdf"
                   class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            <p class="text-gray-400 text-xs mt-1">Format: JPG, PNG, PDF. Maksimal 5 file, masing-masing 5MB.</p>
            @error('evidence')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
            @error('evidence.*')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Step 4: Demand --}}
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h2 class="font-semibold text-lg mb-4">4. Apa yang Anda Harapkan?</h2>
            <div class="space-y-3">
                @php
                    $demands = [
                        'refund_full'    => ['label' => 'Refund Penuh', 'desc' => 'Pengembalian seluruh biaya sewa'],
                        'refund_partial' => ['label' => 'Refund Sebagian', 'desc' => 'Pengembalian sebagian biaya sewa'],
                        'discount_next'  => ['label' => 'Diskon Sewa Berikutnya', 'desc' => 'Voucher diskon untuk sewa berikutnya'],
                        'apology'        => ['label' => 'Permintaan Maaf', 'desc' => 'Permohonan maaf resmi dari vendor'],
                        'other'          => ['label' => 'Lainnya', 'desc' => 'Jelaskan di kolom catatan'],
                    ];
                @endphp
                @foreach($demands as $value => $demand)
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="radio" name="customer_demand" value="{{ $value }}"
                               {{ old('customer_demand', 'apology') === $value ? 'checked' : '' }}
                               class="mt-1 text-blue-600" required>
                        <div>
                            <span class="font-medium text-sm">{{ $demand['label'] }}</span>
                            <p class="text-gray-500 text-xs">{{ $demand['desc'] }}</p>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('customer_demand')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror

            {{-- Refund amount (shown conditionally) --}}
            <div id="refund-amount-field" class="mt-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Refund yang Diminta (Rp)</label>
                <input type="number" name="demanded_refund_amount" value="{{ old('demanded_refund_amount') }}"
                       min="1" placeholder="Contoh: 150000"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan <span class="text-gray-400 font-normal">(opsional)</span></label>
                <textarea name="customer_demand_note" rows="3"
                          placeholder="Informasi tambahan mengenai permintaan Anda..."
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('customer_demand_note') }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('bookings.show', $booking->code) }}"
               class="text-gray-600 hover:text-gray-800 text-sm">Batal</a>
            <button type="submit"
                    class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 font-semibold transition">
                Kirim Laporan
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const demandRadios = document.querySelectorAll('input[name="customer_demand"]');
    const refundField  = document.getElementById('refund-amount-field');

    function toggleRefundField() {
        const val = document.querySelector('input[name="customer_demand"]:checked')?.value;
        refundField.classList.toggle('hidden', !['refund_full', 'refund_partial'].includes(val));
    }

    demandRadios.forEach(r => r.addEventListener('change', toggleRefundField));
    toggleRefundField();
</script>
@endpush
@endsection
