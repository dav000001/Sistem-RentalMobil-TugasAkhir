@extends('layouts.app')

@section('title', 'Portal Sopir — Lapor Pengembalian Armada - Rental Mobil')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
        {{-- Header Portal --}}
        <div class="border-b pb-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-purple-100 text-purple-700 rounded-full flex items-center justify-center text-2xl font-bold">
                    🧑‍✈️
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Portal Laporan Sopir</h1>
                    <p class="text-sm text-gray-500">Konfirmasi pengembalian & keterlambatan armada rental</p>
                </div>
            </div>
        </div>

        {{-- Info Ringkas Booking --}}
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6 space-y-2 text-sm">
            <div class="flex justify-between items-center">
                <span class="text-purple-700 font-semibold">Kode Pesanan:</span>
                <span class="font-mono font-bold text-purple-900">{{ $booking->code }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-purple-700">Mobil:</span>
                <span class="font-medium text-gray-900">{{ $booking->car?->brand }} {{ $booking->car?->model }} ({{ $booking->car?->license_plate }})</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-purple-700">Sopir Bertugas:</span>
                <span class="font-semibold text-purple-900">🧑‍✈️ {{ $booking->driver?->name }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-purple-700">Customer:</span>
                <span class="font-medium text-gray-900">{{ $booking->customer?->full_name }}</span>
            </div>
            <div class="flex justify-between items-center pt-2 border-t border-purple-200">
                <span class="text-purple-700 font-semibold">Jatuh Tempo Pengembalian:</span>
                <span class="font-bold text-red-600">📅 {{ $booking->end_at->format('d M Y, H:i') }} WIB</span>
            </div>
        </div>

        {{-- Status Laporan Saat Ini --}}
        @if($booking->lateReturnReport)
            <div class="bg-amber-50 border border-amber-300 rounded-lg p-4 mb-6">
                <div class="flex items-start gap-3">
                    <span class="text-xl mt-0.5">📋</span>
                    <div>
                        <p class="font-semibold text-amber-900 text-sm">Laporan Terakhir di Sistem:</p>
                        <p class="text-xs text-amber-800 mt-1">
                            <strong>Dilaporkan Oleh:</strong> {{ $booking->lateReturnReport->reporterLabel() }}<br>
                            <strong>Alasan:</strong> "{{ $booking->lateReturnReport->reason }}"<br>
                            @if($booking->lateReturnReport->return_confirmed_at)
                                <strong>Sampai Lokasi:</strong> ✅ {{ $booking->lateReturnReport->return_confirmed_at->format('d M Y, H:i') }} WIB<br>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Form Laporan Sopir --}}
        <form method="POST" action="{{ route('driver.late-report.store', $booking->code) }}" class="space-y-5">
            @csrf

            {{-- Jenis Laporan --}}
            <div>
                <label class="block text-sm font-semibold text-gray-800 mb-2">Pilih Jenis Laporan <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-start p-3 border border-gray-300 rounded-lg cursor-pointer hover:border-amber-500 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50">
                        <input type="radio" name="report_type" value="late_warning" checked onchange="toggleReportTypeFields()" class="mt-1 mr-2 text-amber-600">
                        <div>
                            <span class="text-sm font-semibold text-gray-900">⚠️ Lapor Terlambat di Jalan</span>
                            <p class="text-xs text-gray-500 mt-0.5">Armada masih di jalan & akan terlambat tiba.</p>
                        </div>
                    </label>
                    <label class="flex items-start p-3 border border-gray-300 rounded-lg cursor-pointer hover:border-green-500 has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                        <input type="radio" name="report_type" value="return_arrival" onchange="toggleReportTypeFields()" class="mt-1 mr-2 text-green-600">
                        <div>
                            <span class="text-sm font-semibold text-gray-900">📍 Sampai di Lokasi Kembali</span>
                            <p class="text-xs text-gray-500 mt-0.5">Konfirmasi armada telah tiba di garasi/vendor.</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Nomor Telepon Sopir (Verifikasi Identitas) --}}
            <div>
                <label class="block text-sm font-semibold text-gray-800 mb-1">Nomor Telepon Sopir (Verifikasi) <span class="text-red-500">*</span></label>
                <input type="text" name="driver_phone" required value="{{ old('driver_phone') }}"
                       placeholder="Masukkan no. HP sopir (misal: 0812...)"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
                <p class="text-xs text-gray-500 mt-1">Gunakan nomor telepon sopir yang terdaftar di pesanan ini.</p>
                @error('driver_phone')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Estimasi Terlambat (Jam) --}}
            <div id="late-hours-container">
                <label class="block text-sm font-semibold text-gray-800 mb-1">Perkiraan Keterlambatan (Jam)</label>
                <select name="estimated_late_hours" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
                    <option value="1">1 Jam</option>
                    <option value="2" selected>2 Jam</option>
                    <option value="3">3 Jam</option>
                    <option value="4">4 Jam</option>
                    <option value="5">5 Jam</option>
                    <option value="6">6+ Jam (Terlambat Berat)</option>
                </select>
            </div>

            {{-- Alasan Keterlambatan / Catatan Sopir --}}
            <div>
                <label class="block text-sm font-semibold text-gray-800 mb-1">Alasan / Catatan Sopir <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="3" required placeholder="Jelaskan kondisi di lapangan (misal: kemacetan parah di tol, kendala ban, rute tambahan dari customer...)" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500"></textarea>
                @error('reason')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Lokasi GPS Sopir --}}
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p class="text-sm font-semibold text-gray-800 mb-2">📍 Lokasi GPS Sopir (Bukti Lapangan)</p>
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="location_address" id="location_address">

                <button type="button" onclick="getDriverGpsLocation()" id="gps-btn"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2 rounded-lg inline-flex items-center gap-2 transition">
                    🌐 Ambil Lokasi GPS HP Saya Saat Ini
                </button>
                <p id="gps-status" class="text-xs text-gray-500 mt-2">Belum ada lokasi GPS terdeteksi (Klik tombol di atas).</p>
            </div>

            {{-- Submit Button --}}
            <div class="pt-4">
                <button type="submit"
                        style="background-color: #7c3aed; color: #ffffff; width: 100%; font-weight: 700; padding: 14px 24px; border-radius: 12px; border: none; cursor: pointer; display: block; font-size: 16px; text-align: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);"
                        onmouseover="this.style.backgroundColor='#6d28d9'"
                        onmouseout="this.style.backgroundColor='#7c3aed'">
                    <span id="submit-btn-text" style="color: #ffffff; font-weight: 700; font-size: 16px;">Kirim Laporan Sopir ke Vendor</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function getDriverGpsLocation() {
    var statusEl = document.getElementById('gps-status');
    var btn = document.getElementById('gps-btn');

    if (!navigator.geolocation) {
        statusEl.innerText = "Browser tidak mendukung geolokasi GPS.";
        return;
    }

    btn.disabled = true;
    btn.innerText = "Mendapatkan Koordinat GPS...";
    statusEl.innerText = "Sedang mengambil lokasi GPS terbaru dari HP...";

    navigator.geolocation.getCurrentPosition(
        function(position) {
            document.getElementById('latitude').value = position.coords.latitude;
            document.getElementById('longitude').value = position.coords.longitude;
            statusEl.className = "text-xs text-green-600 font-semibold mt-2";
            statusEl.innerText = "Lokasi GPS Berhasil Diambil: " + position.coords.latitude.toFixed(6) + ", " + position.coords.longitude.toFixed(6);
            btn.disabled = false;
            btn.innerText = "Update Ulang Lokasi GPS";
        },
        function(error) {
            btn.disabled = false;
            btn.innerText = "Ambil Lokasi GPS HP Saya Saat Ini";
            statusEl.className = "text-xs text-red-500 mt-2";
            statusEl.innerText = "Gagal mengambil GPS: " + error.message + ". Pastikan fitur lokasi HP aktif.";
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

function toggleReportTypeFields() {
    var isReturnArrival = document.querySelector('input[name="report_type"]:checked')?.value === 'return_arrival';
    var lateContainer = document.getElementById('late-hours-container');
    var reasonTextarea = document.querySelector('textarea[name="reason"]');
    var submitBtnText = document.getElementById('submit-btn-text');

    if (isReturnArrival) {
        if (lateContainer) lateContainer.style.display = 'none';
        if (reasonTextarea) reasonTextarea.placeholder = 'Contoh: Mobil sudah terparkir di garasi vendor, kunci diserahkan ke staf...';
        if (submitBtnText) submitBtnText.innerText = 'Konfirmasi Tiba di Garasi Vendor';
    } else {
        if (lateContainer) lateContainer.style.display = 'block';
        if (reasonTextarea) reasonTextarea.placeholder = 'Jelaskan kondisi di lapangan (misal: kemacetan parah di tol, kendala ban, rute tambahan dari customer...)';
        if (submitBtnText) submitBtnText.innerText = 'Kirim Laporan Sopir ke Vendor';
    }
}

document.addEventListener('DOMContentLoaded', toggleReportTypeFields);
</script>
@endpush
@endsection
