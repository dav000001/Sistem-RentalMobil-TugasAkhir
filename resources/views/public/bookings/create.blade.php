@extends('layouts.app')

@section('title', 'Pesan ' . $car->brand . ' ' . $car->model . ' - Rental Mobil')

{{-- Flatpickr CSS --}}
@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Tanggal yang sudah dipesan → merah */
    .flatpickr-day.booked {
        background: #fee2e2 !important;
        color: #dc2626 !important;
        text-decoration: line-through;
        cursor: not-allowed !important;
        border-color: #fca5a5 !important;
    }
    .flatpickr-day.booked:hover {
        background: #fecaca !important;
    }
    /* Tanggal hari ini */
    .flatpickr-day.today {
        border-color: #2563eb;
        font-weight: 700;
    }
    /* Range yang dipilih */
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange {
        background: #2563eb !important;
        border-color: #2563eb !important;
    }
    .flatpickr-day.inRange {
        background: #dbeafe !important;
        border-color: #dbeafe !important;
        color: #1e40af !important;
    }
    /* Override input style */
    .flatpickr-input {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        color: #111827;
        background: #fff;
        cursor: pointer;
    }
    .flatpickr-input:focus {
        outline: none;
        ring: 2px;
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37,99,235,0.2);
    }
    /* Legend */
    .date-legend {
        display: flex;
        gap: 12px;
        font-size: 11px;
        color: #6b7280;
        margin-top: 4px;
    }
    .date-legend span {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .legend-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
    }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold mb-8">Pesan Mobil</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Booking Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <form method="POST" action="{{ route('bookings.store', $car->slug) }}" class="space-y-6">
                    @csrf

                    <!-- Car Info -->
                    <div class="border-b pb-6">
                        <h3 class="font-semibold text-lg mb-4">Mobil yang Dipesan</h3>
                        <div class="flex items-center space-x-4">
                            <img src="{{ $car->photos->first()?->path ?? 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=200&q=80' }}" 
                                 alt="{{ $car->brand }}" class="w-24 h-24 object-cover rounded">
                            <div>
                                <h4 class="font-semibold">{{ $car->brand }} {{ $car->model }} {{ $car->year }}</h4>
                                <p class="text-gray-600">{{ $car->vendor->business_name }}</p>
                                <p class="text-blue-600 font-semibold">{{ formatRupiah($car->pricing->daily_price) }}/hari</p>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Details -->
                    <div class="border-b pb-6">
                        <h3 class="font-semibold text-lg mb-4">Detail Pemesanan</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="start_at" class="block text-sm font-medium text-gray-700 mb-1">Tanggal & Jam Mulai</label>
                                <input type="text" id="start_at" name="start_at" required
                                       placeholder="Pilih tanggal mulai..."
                                       class="flatpickr-input @error('start_at') border-red-500 @enderror"
                                       autocomplete="off">
                                <div id="start-time-badge" class="mt-1.5 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-300">
                                    🌅 Jam Pagi (08:00 WIB)
                                </div>
                                <div class="date-legend">
                                    <span><span class="legend-dot" style="background:#fee2e2;border:1px solid #fca5a5;"></span> Sudah dipesan</span>
                                    <span><span class="legend-dot" style="background:#2563eb;"></span> Dipilih</span>
                                    <span><span class="legend-dot" style="background:#dbeafe;border:1px solid #93c5fd;"></span> Rentang</span>
                                </div>
                                @error('start_at')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="end_at" class="block text-sm font-medium text-gray-700 mb-1">Tanggal & Jam Selesai</label>
                                <input type="text" id="end_at" name="end_at" required
                                       placeholder="Pilih tanggal selesai..."
                                       class="flatpickr-input @error('end_at') border-red-500 @enderror"
                                       autocomplete="off">
                                <div id="end-time-badge" class="mt-1.5 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-300">
                                    🌅 Jam Pagi (08:00 WIB)
                                </div>
                                @error('end_at')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                                @if(!$car->is_monthly_available)
                                    <p class="text-xs text-orange-600 mt-1">⚠️ Mobil ini hanya tersedia untuk sewa maksimal <strong>7 hari</strong>.</p>
                                @endif
                                <div id="max-days-warning" class="hidden mt-2 bg-red-50 border border-red-200 rounded-lg p-3">
                                    <p class="text-sm text-red-700 font-medium">❌ Durasi sewa melebihi batas maksimal 7 hari.</p>
                                    <p class="text-xs text-red-600 mt-1">Mobil ini tidak tersedia untuk sewa bulanan. Silakan pilih tanggal selesai maksimal 7 hari dari tanggal mulai.</p>
                                </div>
                            </div>

                            {{-- ── Petunjuk Keterangan Jam ── --}}
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <p class="text-xs font-semibold text-blue-900 mb-2">💡 Keterangan Jam Sewa (WIB):</p>
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <div class="flex-1 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base leading-none">🌅</span>
                                            <div>
                                                <p class="text-[11px] font-bold text-amber-900">Jam Pagi</p>
                                                <p class="text-[11px] text-amber-800 whitespace-nowrap">05:00 – 11:59</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 bg-sky-50 border border-sky-200 rounded-lg px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base leading-none">☀️</span>
                                            <div>
                                                <p class="text-[11px] font-bold text-sky-900">Jam Siang</p>
                                                <p class="text-[11px] text-sky-800 whitespace-nowrap">12:00 – 17:59</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 bg-indigo-50 border border-indigo-200 rounded-lg px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base leading-none">🌙</span>
                                            <div>
                                                <p class="text-[11px] font-bold text-indigo-900">Jam Malam</p>
                                                <p class="text-[11px] text-indigo-800 whitespace-nowrap">18:00 – 04:59</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ── Cek Ketersediaan Real-time ── --}}
                            <div id="availability-check" class="hidden">
                                <div id="availability-loading" class="hidden flex items-center gap-2 text-sm text-gray-500 bg-gray-50 border rounded-lg p-3">
                                    <svg class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 22 6.477 22 12h-4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Memeriksa ketersediaan...
                                </div>
                                <div id="availability-available" class="hidden bg-green-50 border border-green-200 rounded-lg p-3">
                                    <p class="text-sm font-semibold text-green-700">✅ Tersedia untuk tanggal yang dipilih</p>
                                    <p class="text-xs text-green-600 mt-0.5">Mobil ini tidak memiliki booking aktif pada rentang tanggal tersebut.</p>
                                </div>
                                <div id="availability-unavailable" class="hidden bg-red-50 border border-red-200 rounded-lg p-3">
                                    <p class="text-sm font-semibold text-red-700">❌ Tidak Tersedia untuk tanggal ini</p>
                                    <p class="text-xs text-red-600 mt-0.5">Mobil sudah dipesan pada sebagian atau seluruh rentang tanggal yang dipilih. Silakan pilih tanggal lain.</p>
                                </div>
                            </div>


                            <div>
                                <label for="pickup_location" class="block text-sm font-medium text-gray-700 mb-1">Lokasi Penjemputan</label>
                                <input type="text" id="pickup_location" name="pickup_location" required
                                       value="{{ old('pickup_location') }}"
                                       placeholder="Contoh: Bandara Soekarno-Hatta, Terminal 3"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 @error('pickup_location') border-red-500 @enderror">
                                @error('pickup_location')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Lokasi Pengembalian hanya muncul jika bukan with_driver_only --}}
                            @if($car->rental_option !== 'with_driver_only')
                            <div>
                                <label for="dropoff_location" class="block text-sm font-medium text-gray-700 mb-1">Lokasi Pengembalian (Opsional)</label>
                                <input type="text" id="dropoff_location" name="dropoff_location"
                                       value="{{ old('dropoff_location') }}"
                                       placeholder="Jika berbeda dengan lokasi penjemputan"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                            </div>
                            @endif

                            @if($car->rental_option === 'with_driver_only')
                                <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
                                    <p class="text-sm text-purple-700 font-medium">👨‍✈️ Mobil ini hanya tersedia dengan sopir</p>
                                    @if($car->pricing->with_driver_price)
                                        <p class="text-xs text-purple-600 mt-1">Biaya sopir: {{ formatRupiah($car->pricing->with_driver_price) }}/hari</p>
                                    @endif
                                </div>
                                <input type="hidden" name="with_driver" value="1">

                                {{-- Pilih Sopir (diupdate JS setelah isi tanggal) --}}
                                <div id="driver-select-wrap" class="mt-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Sopir <span class="text-red-500">*</span></label>
                                    <select id="driver_id" name="driver_id"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 text-sm"
                                            required>
                                        <option value="">— Pilih tanggal dulu untuk melihat sopir tersedia —</option>
                                    </select>
                                    <p id="driver-select-hint" class="text-xs text-gray-400 mt-1">Sopir ditugaskan berdasarkan ketersediaan di tanggal yang dipilih.</p>
                                </div>

                            @elseif($car->rental_option === 'both')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Opsi Sewa</label>
                                    <div class="space-y-2">
                                        <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-blue-400">
                                            <input type="radio" id="no_driver" name="with_driver" value="0"
                                                   {{ old('with_driver', '0') === '0' ? 'checked' : '' }}
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                            <label for="no_driver" class="ml-2 text-sm text-gray-700 cursor-pointer">🔑 Lepas kunci (tanpa sopir)</label>
                                        </label>
                                        <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-purple-400">
                                            <input type="radio" id="with_driver" name="with_driver" value="1"
                                                   {{ old('with_driver') === '1' ? 'checked' : '' }}
                                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300">
                                            <label for="with_driver" class="ml-2 text-sm text-gray-700 cursor-pointer">
                                                👨‍✈️ Dengan sopir
                                                @if($car->pricing->with_driver_price)
                                                    <span class="text-gray-500">(+ {{ formatRupiah($car->pricing->with_driver_price) }}/hari)</span>
                                                @endif
                                            </label>
                                        </label>
                                    </div>

                                    {{-- Pilih Sopir — muncul hanya jika pilih "dengan sopir" --}}
                                    <div id="driver-select-wrap" class="hidden mt-3">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Sopir <span class="text-red-500">*</span></label>
                                        <select id="driver_id" name="driver_id"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 text-sm">
                                            <option value="">— Pilih tanggal dulu untuk melihat sopir tersedia —</option>
                                        </select>
                                        <p id="driver-select-hint" class="text-xs text-gray-400 mt-1">Sopir ditugaskan berdasarkan ketersediaan di tanggal yang dipilih.</p>
                                    </div>
                                </div>
                            @else
                                {{-- self_drive_only --}}
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                    <p class="text-sm text-blue-700 font-medium">🔑 Lepas kunci — tanpa sopir</p>
                                </div>
                                <input type="hidden" name="with_driver" value="0">
                            @endif

                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                                <textarea id="notes" name="notes" rows="3"
                                          placeholder="Catatan khusus untuk vendor"
                                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Terms -->
                    <div class="flex items-start">
                        <input type="checkbox" id="agree" required class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mt-1">
                        <label for="agree" class="ml-2 block text-sm text-gray-700">
                            Saya setuju dengan <a href="#" class="text-blue-600 hover:underline">Syarat & Ketentuan</a> dan <a href="#" class="text-blue-600 hover:underline">Kebijakan Privasi</a>
                        </label>
                    </div>

                    <button type="submit" id="submit-booking-btn"
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-semibold">
                        Lanjutkan ke Pembayaran
                    </button>
                </form>

<script>
    document.querySelector('form').addEventListener('submit', function () {
        const btn = document.getElementById('submit-booking-btn');
        btn.disabled = true;
        btn.textContent = '⏳ Memproses...';
        btn.classList.add('opacity-60', 'cursor-not-allowed');
    });
</script>
            </div>
        </div>

        <!-- Summary -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                <h3 class="font-semibold text-lg mb-4">Ringkasan Pesanan</h3>

                <div class="space-y-3 border-b pb-4 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Harga per hari</span>
                        <span class="font-semibold">{{ formatRupiah($car->pricing->daily_price) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Durasi</span>
                        <span class="font-semibold" id="duration">2 hari</span>
                    </div>
                    <div id="driver-row" class="flex justify-between text-sm hidden">
                        <span class="text-gray-600">Biaya Sopir</span>
                        <span class="font-semibold" id="driver-cost">Rp 0</span>
                    </div>
                </div>

                <div class="space-y-3 border-b pb-4 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Sewa Mobil</span>
                        <span class="font-semibold" id="subtotal">{{ formatRupiah($car->pricing->daily_price * 2) }}</span>
                    </div>
                </div>

                <div class="flex justify-between text-lg font-bold border-t pt-4">
                    <span>Total</span>
                    <span class="text-blue-600" id="total">{{ formatRupiah($car->pricing->daily_price * 2) }}</span>
                </div>

                <p class="text-xs text-gray-400 mt-2 text-center">Harga sudah termasuk semua biaya</p>
            </div>
        </div>
    </div>
</div>

<script>
    const dailyPrice = {{ $car->pricing->daily_price }};
    const withDriverPrice = {{ $car->pricing->with_driver_price ?? 0 }};
    const rentalOption = '{{ $car->rental_option ?? 'self_drive_only' }}';
    const isMonthlyAvailable = {{ $car->is_monthly_available ? 'true' : 'false' }};
    const maxDays = isMonthlyAvailable ? null : 7; // null = tidak ada batas

    const startInput = document.getElementById('start_at');
    const endInput   = document.getElementById('end_at');

    // Set max end date berdasarkan start date
    function updateMaxEndDate() {
        if (!maxDays || !startInput.value) return;
        const startDate = new Date(startInput.value);
        const maxEnd = new Date(startDate);
        maxEnd.setDate(maxEnd.getDate() + maxDays);
        // Format ke datetime-local
        const pad = n => String(n).padStart(2, '0');
        const maxEndStr = maxEnd.getFullYear() + '-' + pad(maxEnd.getMonth()+1) + '-' + pad(maxEnd.getDate())
            + 'T' + pad(maxEnd.getHours()) + ':' + pad(maxEnd.getMinutes());
        endInput.max = maxEndStr;

        // Jika end sudah melebihi max, reset ke max
        if (endInput.value && new Date(endInput.value) > maxEnd) {
            endInput.value = maxEndStr;
        }
    }

    function updateSummary() {
        const startAt = new Date(startInput.value);
        const endAt   = new Date(endInput.value);

        // Deteksi with_driver berdasarkan rental_option
        let withDriver = false;
        if (rentalOption === 'with_driver_only') {
            withDriver = true;
        } else if (rentalOption === 'both') {
            const radioDriver = document.querySelector('input[name="with_driver"]:checked');
            withDriver = radioDriver ? radioDriver.value === '1' : false;
        }

        if (startAt && endAt && endAt > startAt) {
            const days = Math.ceil((endAt - startAt) / (1000 * 60 * 60 * 24)) || 1;

            // Blokir jika melebihi batas
            if (maxDays && days > maxDays) {
                document.getElementById('max-days-warning').classList.remove('hidden');
                return;
            } else {
                document.getElementById('max-days-warning')?.classList.add('hidden');
            }

            let sewa = dailyPrice * days;
            let driverCost = 0;

            if (withDriver && withDriverPrice > 0) {
                driverCost = withDriverPrice * days;
                document.getElementById('driver-row').classList.remove('hidden');
                document.getElementById('driver-cost').textContent = 'Rp ' + driverCost.toLocaleString('id-ID');
            } else {
                document.getElementById('driver-row').classList.add('hidden');
            }

            const subtotal = sewa + driverCost;
            const total = subtotal;

            document.getElementById('duration').textContent = days + ' hari';
            document.getElementById('subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
            document.getElementById('total').textContent = 'Rp ' + total.toLocaleString('id-ID');
        }
    }

    startInput.addEventListener('change', function() {
        updateMaxEndDate();
        updateSummary();
    });
    endInput.addEventListener('change', updateSummary);

    if (rentalOption === 'both') {
        document.querySelectorAll('input[name="with_driver"]').forEach(el => {
            el.addEventListener('change', updateSummary);
        });
    }

    // Inisialisasi
    updateMaxEndDate();
    updateSummary();

    // ── Cek Ketersediaan Real-time ──────────────────────────────────
    const carId           = {{ $car->id }};
    const vendorId        = {{ $car->vendor_id }};
    const availabilityApi = '/api/cars/' + carId + '/check-availability';
    const driverApi       = '/api/cars/' + carId + '/available-drivers';
    let   checkTimeout    = null;
    let   isAvailable     = null; // tracking state

    function showAvailability(state) {
        const checkDiv   = document.getElementById('availability-check');
        const loadingDiv = document.getElementById('availability-loading');
        const okDiv      = document.getElementById('availability-available');
        const noDiv      = document.getElementById('availability-unavailable');
        const submitBtn  = document.getElementById('submit-booking-btn');

        checkDiv.classList.remove('hidden');
        loadingDiv.classList.add('hidden');
        okDiv.classList.add('hidden');
        noDiv.classList.add('hidden');

        if (state === 'loading') {
            loadingDiv.classList.remove('hidden');
            submitBtn.disabled = true;
            isAvailable = null;
        } else if (state === 'available') {
            okDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            isAvailable = true;
        } else if (state === 'unavailable') {
            noDiv.classList.remove('hidden');
            submitBtn.disabled = true;
            isAvailable = false;
        } else {
            checkDiv.classList.add('hidden');
            submitBtn.disabled = false;
        }
    }

    function checkAvailability() {
        const startVal = startInput.value;
        const endVal   = endInput.value;
        if (!startVal || !endVal) { showAvailability('hidden'); return; }
        const s = new Date(startVal), e = new Date(endVal);
        if (e <= s) { showAvailability('hidden'); return; }

        showAvailability('loading');

        fetch(availabilityApi + '?start=' + encodeURIComponent(startVal) + '&end=' + encodeURIComponent(endVal))
            .then(r => r.json())
            .then(data => {
                showAvailability(data.available ? 'available' : 'unavailable');
                if (data.available) loadDriverInfo(startVal, endVal);
            })
            .catch(() => showAvailability('available')); // fallback jika API error
    }

    function loadDriverInfo(startVal, endVal) {
        const rentalOpt      = '{{ $car->rental_option }}';
        const driverWrap     = document.getElementById('driver-select-wrap');
        const driverSelect   = document.getElementById('driver_id');
        const driverHint     = document.getElementById('driver-select-hint');
        if (!driverWrap || !driverSelect) return;

        // Cek apakah user memilih dengan sopir
        let wantDriver = false;
        if (rentalOpt === 'with_driver_only') {
            wantDriver = true;
        } else if (rentalOpt === 'both') {
            const radioDriver = document.querySelector('input[name="with_driver"]:checked');
            wantDriver = radioDriver && radioDriver.value === '1';
        }

        if (!wantDriver) {
            driverWrap.classList.add('hidden');
            driverSelect.removeAttribute('required');
            return;
        }

        driverWrap.classList.remove('hidden');
        driverSelect.setAttribute('required', 'required');
        driverSelect.innerHTML = '<option value="">⏳ Memuat daftar sopir...</option>';
        if (driverHint) driverHint.textContent = 'Sedang memeriksa ketersediaan sopir...';

        fetch(driverApi + '?start=' + encodeURIComponent(startVal) + '&end=' + encodeURIComponent(endVal))
            .then(r => r.json())
            .then(data => {
                driverSelect.innerHTML = '';
                if (data.drivers && data.drivers.length > 0) {
                    // Tambah placeholder option
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = '— Pilih Sopir —';
                    driverSelect.appendChild(placeholder);

                    data.drivers.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = '🧑‍✈️ ' + d.name
                            + (d.experience_years > 0 ? ' — ' + d.experience_years + ' thn' : '');
                        driverSelect.appendChild(opt);
                    });

                    if (driverHint) driverHint.textContent = data.drivers.length + ' sopir tersedia untuk tanggal ini.';
                } else {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = '⚠️ Tidak ada sopir tersedia untuk tanggal ini';
                    driverSelect.appendChild(opt);
                    if (driverHint) driverHint.textContent = 'Silakan pilih tanggal lain atau hubungi vendor.';
                    // Blokir submit jika tidak ada sopir tapi wajib sopir
                    if (rentalOpt === 'with_driver_only') {
                        document.getElementById('submit-booking-btn').disabled = true;
                    }
                }
            })
            .catch(() => {
                driverSelect.innerHTML = '<option value="">— Pilih Sopir —</option>';
            });
    }

    // Trigger cek saat tanggal berubah (debounce 600ms)
    function scheduleCheck() {
        clearTimeout(checkTimeout);
        checkTimeout = setTimeout(checkAvailability, 600);
    }

    startInput.addEventListener('change', scheduleCheck);
    endInput.addEventListener('change', scheduleCheck);

    if ('{{ $car->rental_option }}' === 'both') {
        document.querySelectorAll('input[name="with_driver"]').forEach(el => {
            el.addEventListener('change', function() {
                updateSummary();
                const startVal = startInput.value;
                const endVal   = endInput.value;
                // Sembunyikan dropdown sopir jika pilih lepas kunci
                const driverWrap = document.getElementById('driver-select-wrap');
                if (this.value === '0' && driverWrap) {
                    driverWrap.classList.add('hidden');
                    const ds = document.getElementById('driver_id');
                    if (ds) ds.removeAttribute('required');
                } else if (this.value === '1' && driverWrap && startVal && endVal) {
                    loadDriverInfo(startVal, endVal);
                }
            });
        });
    }

    // Cegah submit jika tidak tersedia
    document.querySelector('form').addEventListener('submit', function(e) {
        if (isAvailable === false) {
            e.preventDefault();
            alert('Mobil tidak tersedia untuk tanggal yang dipilih. Silakan pilih tanggal lain.');
            return;
        }
        const btn = document.getElementById('submit-booking-btn');
        btn.disabled = true;
        btn.textContent = '⏳ Memproses...';
        btn.classList.add('opacity-60', 'cursor-not-allowed');
    });
</script>

{{-- Flatpickr JS + Inisialisasi --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
<script>
(function() {
    const dailyPrice      = {{ $car->pricing->daily_price }};
    const withDriverPrice = {{ $car->pricing->with_driver_price ?? 0 }};
    const rentalOption    = '{{ $car->rental_option ?? 'self_drive_only' }}';
    const isMonthly       = {{ $car->is_monthly_available ? 'true' : 'false' }};
    const maxDays         = isMonthly ? null : 7;
    const carId           = {{ $car->id }};
    const availApi        = '/api/cars/' + carId + '/check-availability';
    const driverApi       = '/api/cars/' + carId + '/available-drivers';
    let   isAvailable     = null;
    let   checkTimeout    = null;
    let   fpStart, fpEnd;

    // ── Ambil tanggal yang sudah dipesan dari API ──────────────
    let bookedDates = [];
    fetch('/api/cars/' + carId + '/availability?from={{ now()->toDateString() }}&to={{ now()->addMonths(3)->toDateString() }}')
        .then(r => r.json())
        .then(data => {
            bookedDates = data.unavailable || [];
            initPickers();
        })
        .catch(() => { initPickers(); });

    function initPickers() {
        const commonConfig = {
            locale: 'id',
            enableTime: true,
            time_24hr: true,
            dateFormat: 'Y-m-d H:i',
            altInput: true,
            altFormat: 'd M Y, H:i',
            minDate: 'today',
            minuteIncrement: 30,
            // Warnai tanggal yang sudah dipesan
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const dateStr = flatpickr.formatDate(dayElem.dateObj, 'Y-m-d');
                if (bookedDates.includes(dateStr)) {
                    dayElem.classList.add('booked');
                    dayElem.title = 'Sudah dipesan';
                }
            },
            // Blokir pilih tanggal yang sudah dipesan
            disable: [
                function(date) {
                    const dateStr = flatpickr.formatDate(date, 'Y-m-d');
                    return bookedDates.includes(dateStr);
                }
            ],
        };

        // Inisialisasi start picker
        fpStart = flatpickr('#start_at', Object.assign({}, commonConfig, {
            defaultDate: '{{ now()->addDay()->format('Y-m-d') }} 08:00',
            onChange: function(selectedDates, dateStr) {
                if (selectedDates[0]) {
                    const startDate = selectedDates[0];
                    const minEnd = new Date(startDate);
                    minEnd.setDate(minEnd.getDate() + 1);
                    fpEnd.set('minDate', minEnd);

                    if (maxDays) {
                        const maxEnd = new Date(startDate);
                        maxEnd.setDate(maxEnd.getDate() + maxDays);
                        fpEnd.set('maxDate', maxEnd);
                    }

                    // Otomatis samakan jam & menit selesai dengan jam & menit mulai (kelipatan 24 jam)
                    if (fpEnd && fpEnd.selectedDates[0]) {
                        const currentEndDate = new Date(fpEnd.selectedDates[0]);
                        currentEndDate.setHours(startDate.getHours(), startDate.getMinutes(), 0, 0);
                        
                        if (currentEndDate <= startDate) {
                            currentEndDate.setDate(startDate.getDate() + 1);
                        }
                        fpEnd.setDate(currentEndDate, false);
                    } else {
                        fpEnd.setDate(minEnd, false);
                    }
                }
                onDateChange();
            }
        }));

        // Inisialisasi end picker
        fpEnd = flatpickr('#end_at', Object.assign({}, commonConfig, {
            defaultDate: '{{ now()->addDays(2)->format('Y-m-d') }} 08:00',
            onChange: function(selectedDates, dateStr) {
                if (selectedDates[0] && fpStart && fpStart.selectedDates[0]) {
                    const startDate = fpStart.selectedDates[0];
                    const currentEndDate = new Date(selectedDates[0]);
                    
                    // Otomatis sesuaikan jam & menit mengikuti jam mulai agar hitungan sewa tepat 24 jam / kelipatannya
                    currentEndDate.setHours(startDate.getHours(), startDate.getMinutes(), 0, 0);
                    fpEnd.setDate(currentEndDate, false);
                }
                onDateChange();
            }
        }));

        updateTimeBadges();
    }

    // ── Helper Label Jam Pagi / Siang / Malam ─────────────────
    function getTimeInfo(dateStr) {
        if (!dateStr) return { text: '—', cls: 'bg-gray-100 text-gray-700 border-gray-300' };
        const parts = dateStr.trim().split(' ');
        if (parts.length < 2) return { text: '—', cls: 'bg-gray-100 text-gray-700 border-gray-300' };
        const timePart = parts[1];
        const [hoursStr, minsStr] = timePart.split(':');
        const hours = parseInt(hoursStr, 10);

        if (isNaN(hours)) return { text: '—', cls: 'bg-gray-100 text-gray-700 border-gray-300' };

        const timeFormatted = String(hours).padStart(2, '0') + ':' + String(minsStr || '00').padStart(2, '0');

        if (hours >= 5 && hours < 12) {
            return { text: `🌅 Jam Pagi (${timeFormatted} WIB)`, cls: 'bg-amber-100 text-amber-800 border-amber-300' };
        } else if (hours >= 12 && hours < 18) {
            return { text: `☀️ Jam Siang / Sore (${timeFormatted} WIB)`, cls: 'bg-sky-100 text-sky-800 border-sky-300' };
        } else {
            return { text: `🌙 Jam Malam / Dini Hari (${timeFormatted} WIB)`, cls: 'bg-indigo-100 text-indigo-800 border-indigo-300' };
        }
    }

    function updateTimeBadges() {
        const startVal = document.getElementById('start_at').value;
        const endVal   = document.getElementById('end_at').value;

        const startBadge = document.getElementById('start-time-badge');
        const endBadge   = document.getElementById('end-time-badge');

        if (startBadge && startVal) {
            const info = getTimeInfo(startVal);
            startBadge.innerHTML = info.text;
            startBadge.className = `mt-1.5 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border ${info.cls}`;
        }
        if (endBadge && endVal) {
            const info = getTimeInfo(endVal);
            endBadge.innerHTML = info.text;
            endBadge.className = `mt-1.5 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border ${info.cls}`;
        }
    }

    // ── Handler saat tanggal berubah ───────────────────────────
    function onDateChange() {
        updateTimeBadges();
        updateSummary();
        clearTimeout(checkTimeout);
        checkTimeout = setTimeout(checkAvailability, 600);
    }

    // ── Update ringkasan harga ─────────────────────────────────
    function updateSummary() {
        const startVal = document.getElementById('start_at').value;
        const endVal   = document.getElementById('end_at').value;
        if (!startVal || !endVal) return;

        const startAt = new Date(startVal.replace(' ', 'T'));
        const endAt   = new Date(endVal.replace(' ', 'T'));
        if (!(endAt > startAt)) return;

        const days = Math.max(1, Math.ceil((endAt - startAt) / (1000 * 60 * 60 * 24)));

        // Cek batas hari
        if (maxDays && days > maxDays) {
            document.getElementById('max-days-warning')?.classList.remove('hidden');
        } else {
            document.getElementById('max-days-warning')?.classList.add('hidden');
        }

        let withDriver = false;
        if (rentalOption === 'with_driver_only') {
            withDriver = true;
        } else if (rentalOption === 'both') {
            const r = document.querySelector('input[name="with_driver"]:checked');
            withDriver = r ? r.value === '1' : false;
        }

        const sewa       = dailyPrice * days;
        const driverCost = withDriver ? withDriverPrice * days : 0;
        const total      = sewa + driverCost;

        if (withDriver && withDriverPrice > 0) {
            document.getElementById('driver-row')?.classList.remove('hidden');
            const dc = document.getElementById('driver-cost');
            if (dc) dc.textContent = 'Rp ' + driverCost.toLocaleString('id-ID');
        } else {
            document.getElementById('driver-row')?.classList.add('hidden');
        }

        const durEl = document.getElementById('duration');
        const subEl = document.getElementById('subtotal');
        const totEl = document.getElementById('total');
        if (durEl) durEl.textContent = days + ' hari';
        if (subEl) subEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
        if (totEl) totEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }

    // ── Cek ketersediaan via API ───────────────────────────────
    function showAvailability(state) {
        const checkDiv = document.getElementById('availability-check');
        const loadDiv  = document.getElementById('availability-loading');
        const okDiv    = document.getElementById('availability-available');
        const noDiv    = document.getElementById('availability-unavailable');
        const btn      = document.getElementById('submit-booking-btn');

        checkDiv?.classList.remove('hidden');
        loadDiv?.classList.add('hidden');
        okDiv?.classList.add('hidden');
        noDiv?.classList.add('hidden');

        if (state === 'loading') {
            loadDiv?.classList.remove('hidden');
            if (btn) btn.disabled = true;
            isAvailable = null;
        } else if (state === 'available') {
            okDiv?.classList.remove('hidden');
            if (btn) btn.disabled = false;
            isAvailable = true;
        } else if (state === 'unavailable') {
            noDiv?.classList.remove('hidden');
            if (btn) btn.disabled = true;
            isAvailable = false;
        } else {
            checkDiv?.classList.add('hidden');
            if (btn) btn.disabled = false;
        }
    }

    function checkAvailability() {
        const startVal = document.getElementById('start_at').value;
        const endVal   = document.getElementById('end_at').value;
        if (!startVal || !endVal) { showAvailability('hidden'); return; }

        showAvailability('loading');
        fetch(availApi + '?start=' + encodeURIComponent(startVal) + '&end=' + encodeURIComponent(endVal))
            .then(r => r.json())
            .then(data => {
                showAvailability(data.available ? 'available' : 'unavailable');
                if (data.available) loadDriverSelect(startVal, endVal);
            })
            .catch(() => showAvailability('available'));
    }

    // ── Load dropdown sopir ────────────────────────────────────
    function loadDriverSelect(startVal, endVal) {
        const driverWrap   = document.getElementById('driver-select-wrap');
        const driverSelect = document.getElementById('driver_id');
        const driverHint   = document.getElementById('driver-select-hint');
        if (!driverWrap || !driverSelect) return;

        let wantDriver = false;
        if (rentalOption === 'with_driver_only') {
            wantDriver = true;
        } else if (rentalOption === 'both') {
            const r = document.querySelector('input[name="with_driver"]:checked');
            wantDriver = r && r.value === '1';
        }

        if (!wantDriver) {
            driverWrap.classList.add('hidden');
            driverSelect.removeAttribute('required');
            return;
        }

        driverWrap.classList.remove('hidden');
        driverSelect.setAttribute('required', 'required');
        driverSelect.innerHTML = '<option value="">⏳ Memuat sopir...</option>';

        fetch(driverApi + '?start=' + encodeURIComponent(startVal) + '&end=' + encodeURIComponent(endVal))
            .then(r => r.json())
            .then(data => {
                driverSelect.innerHTML = '';
                if (data.drivers && data.drivers.length > 0) {
                    const ph = document.createElement('option');
                    ph.value = ''; ph.textContent = '— Pilih Sopir —';
                    driverSelect.appendChild(ph);
                    data.drivers.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = '🧑‍✈️ ' + d.name + (d.experience_years > 0 ? ' — ' + d.experience_years + ' thn' : '');
                        driverSelect.appendChild(opt);
                    });
                    if (driverHint) driverHint.textContent = data.drivers.length + ' sopir tersedia.';
                } else {
                    const opt = document.createElement('option');
                    opt.value = ''; opt.textContent = '⚠️ Tidak ada sopir tersedia';
                    driverSelect.appendChild(opt);
                    if (driverHint) driverHint.textContent = 'Silakan pilih tanggal lain.';
                    if (rentalOption === 'with_driver_only') {
                        const btn = document.getElementById('submit-booking-btn');
                        if (btn) btn.disabled = true;
                    }
                }
            })
            .catch(() => {
                driverSelect.innerHTML = '<option value="">— Pilih Sopir —</option>';
            });
    }

    // ── Event listener radio with_driver ──────────────────────
    if (rentalOption === 'both') {
        document.querySelectorAll('input[name="with_driver"]').forEach(el => {
            el.addEventListener('change', function() {
                updateSummary();
                const driverWrap   = document.getElementById('driver-select-wrap');
                const driverSelect = document.getElementById('driver_id');
                if (this.value === '0' && driverWrap) {
                    driverWrap.classList.add('hidden');
                    if (driverSelect) driverSelect.removeAttribute('required');
                } else if (this.value === '1') {
                    const sv = document.getElementById('start_at').value;
                    const ev = document.getElementById('end_at').value;
                    if (sv && ev) loadDriverSelect(sv, ev);
                }
            });
        });
    }

    // ── Blokir submit jika tidak tersedia ─────────────────────
    document.querySelector('form').addEventListener('submit', function(e) {
        if (isAvailable === false) {
            e.preventDefault();
            alert('Mobil tidak tersedia untuk tanggal yang dipilih. Silakan pilih tanggal lain.');
            return;
        }
        const btn = document.getElementById('submit-booking-btn');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Memproses...'; }
    });

    // ── Check Passenger Capacity vs Car Seats ─────────────────
    const passengerInput = document.getElementById('passenger_count');
    const capacityWarn   = document.getElementById('passenger-capacity-warning');
    const warnVal        = document.getElementById('warn-passenger-val');
    const carSeats       = {{ (int) $car->seats }};

    function checkCapacity() {
        if (!passengerInput || !capacityWarn) return;
        const val = parseInt(passengerInput.value) || 0;
        if (val > carSeats) {
            if (warnVal) warnVal.textContent = val;
            capacityWarn.classList.remove('hidden');
        } else {
            capacityWarn.classList.add('hidden');
        }
    }

    if (passengerInput) {
        passengerInput.addEventListener('input', checkCapacity);
        checkCapacity();
    }

    updateSummary();
})();
</script>
@endsection
