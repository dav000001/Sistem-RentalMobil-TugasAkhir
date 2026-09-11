@extends('layouts.app')

@section('title', 'Pembayaran - ' . $booking->code)

@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-gray-900">Selesaikan Pembayaran</h1>
        <p class="text-gray-500 text-sm mt-1">Kode Pesanan: <span class="font-semibold text-blue-600">{{ $booking->code }}</span></p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Info Mobil --}}
        <div class="flex items-center gap-4 p-5 border-b border-gray-100">
            <img src="{{ $booking->car->photos->first()?->path ?? '/images/cars/default.jpg' }}"
                 alt="{{ $booking->car->brand }}"
                 class="w-16 h-16 object-cover rounded-xl flex-shrink-0">
            <div class="min-w-0">
                <p class="font-semibold text-gray-900 truncate">{{ $booking->car->brand }} {{ $booking->car->model }} {{ $booking->car->year }}</p>
                <p class="text-sm text-gray-500">{{ $booking->start_at->format('d M Y') }} — {{ $booking->end_at->format('d M Y') }}</p>
            </div>
        </div>

        {{-- Rincian Harga --}}
        <div class="p-5 space-y-2 border-b border-gray-100">
            <div class="flex justify-between text-sm text-gray-600">
                <span>Sewa Mobil</span>
                <span>{{ formatRupiah($booking->subtotal) }}</span>
            </div>
            @if($booking->discount > 0)
                <div class="flex justify-between text-sm text-green-600">
                    <span>Diskon</span>
                    <span>- {{ formatRupiah($booking->discount) }}</span>
                </div>
            @endif
            <div class="flex justify-between font-bold text-lg pt-2 border-t border-gray-100">
                <span>Total yang harus dibayar</span>
                <span class="text-blue-600">{{ formatRupiah($booking->total) }}</span>
            </div>
        </div>

        {{-- Batas Waktu + Countdown Timer --}}
        <div class="px-5 py-4 bg-yellow-50 border-b border-yellow-100">
            <div class="flex items-center gap-2 mb-2">
                <span class="text-yellow-600 text-lg">⏰</span>
                <p class="text-yellow-800 text-sm font-medium">Selesaikan pembayaran sebelum:</p>
            </div>
            <p class="text-yellow-900 font-bold text-sm mb-3">{{ $booking->created_at->addDay()->format('d M Y H:i') }}</p>
            {{-- Countdown Timer --}}
            <div id="countdown-box" class="bg-white border border-yellow-200 rounded-xl p-3 text-center">
                <p class="text-xs text-gray-500 mb-2">Sisa waktu pembayaran</p>
                <div class="flex justify-center gap-3" id="countdown-display">
                    <div class="text-center">
                        <span id="cd-hours" class="text-2xl font-black text-yellow-600">--</span>
                        <p class="text-xs text-gray-500">Jam</p>
                    </div>
                    <span class="text-2xl font-black text-yellow-400 mt-0.5">:</span>
                    <div class="text-center">
                        <span id="cd-minutes" class="text-2xl font-black text-yellow-600">--</span>
                        <p class="text-xs text-gray-500">Menit</p>
                    </div>
                    <span class="text-2xl font-black text-yellow-400 mt-0.5">:</span>
                    <div class="text-center">
                        <span id="cd-seconds" class="text-2xl font-black text-yellow-600">--</span>
                        <p class="text-xs text-gray-500">Detik</p>
                    </div>
                </div>
                <div id="countdown-expired" class="hidden">
                    <p class="text-red-600 font-bold text-sm">⚠️ Waktu pembayaran telah habis!</p>
                </div>
            </div>
        </div>

        {{-- Rekening Tujuan Transfer --}}
        <div class="p-5 border-b border-gray-100">
            <p class="text-sm font-bold text-gray-700 mb-3">📋 Transfer ke Rekening Berikut:</p>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Bank</span>
                    <span class="font-bold text-gray-900">{{ $bankInfo['bank_name'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">No. Rekening</span>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-gray-900 text-lg tracking-wider" id="acc-no">{{ $bankInfo['account_no'] }}</span>
                        <button onclick="copyAccNo()" class="text-xs text-blue-600 hover:text-blue-800 font-medium border border-blue-300 px-2 py-0.5 rounded">
                            Salin
                        </button>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Atas Nama</span>
                    <span class="font-bold text-gray-900">{{ $bankInfo['account_name'] }}</span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-blue-200">
                    <span class="text-sm text-gray-600">Jumlah Transfer</span>
                    <span class="font-black text-blue-700 text-lg">{{ formatRupiah($booking->total) }}</span>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">⚠️ Pastikan jumlah transfer <strong>tepat sama</strong> agar mudah diverifikasi.</p>
        </div>

        {{-- Upload Bukti Transfer --}}
        <div class="p-5">
            <p class="text-sm font-bold text-gray-700 mb-3">📸 Upload Bukti Transfer</p>

            @if($booking->payment?->status === 'failed')
                <div class="bg-red-50 border border-red-300 rounded-xl p-4 mb-4">
                    <p class="text-red-800 font-semibold text-sm">❌ Bukti transfer sebelumnya ditolak admin</p>
                    <p class="text-red-700 text-xs mt-1">Pastikan foto jelas, jumlah transfer tepat, dan rekening tujuan benar sebelum upload ulang.</p>
                </div>
            @endif

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 rounded-xl p-3 mb-4 text-sm text-green-800">
                    ✅ {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 text-sm text-red-800">
                    @foreach($errors->all() as $error)
                        <p>❌ {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('bookings.pay.proof', $booking) }}"
                  enctype="multipart/form-data" id="proof-form">
                @csrf

                {{-- Nama Pengirim --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nama Pengirim Transfer <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="sender_name" value="{{ old('sender_name') }}"
                           placeholder="Nama sesuai rekening pengirim"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('sender_name') border-red-400 @enderror"
                           required>
                </div>

                {{-- Upload Bukti --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Foto Bukti Transfer <span class="text-red-500">*</span>
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-blue-400 transition cursor-pointer"
                         onclick="document.getElementById('proof-file').click()">
                        <div id="preview-area">
                            <p class="text-3xl mb-2">📎</p>
                            <p class="text-sm text-gray-600">Klik untuk pilih foto</p>
                            <p class="text-xs text-gray-400 mt-1">JPG, PNG — Maks. 2MB</p>
                        </div>
                        <img id="preview-img" src="" alt="Preview" class="hidden max-h-48 mx-auto rounded-lg mt-2">
                    </div>
                    <input type="file" id="proof-file" name="payment_proof"
                           accept="image/jpg,image/jpeg,image/png"
                           class="hidden" required
                           onchange="previewImage(this)">
                    @error('payment_proof')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" id="submit-btn"
                        class="w-full bg-green-600 hover:bg-green-700 text-white py-4 rounded-xl font-bold text-base transition flex items-center justify-center gap-2">
                    <span>✅</span>
                    <span>Kirim Bukti Transfer</span>
                </button>
            </form>

            <p class="text-center text-xs text-gray-400 mt-3">
                Setelah bukti dikirim, admin akan memverifikasi dalam 1×24 jam
            </p>

            <div class="mt-4 text-center">
                <a href="{{ route('bookings.show', $booking) }}"
                   class="text-sm text-gray-500 hover:text-gray-700 hover:underline">
                    ← Kembali ke Detail Pesanan
                </a>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    // ── Countdown Timer ──────────────────────────────────────────────
    const deadline = new Date("{{ $booking->created_at->addDay()->toIso8601String() }}").getTime();

    function updateCountdown() {
        const now = new Date().getTime();
        const diff = deadline - now;

        if (diff <= 0) {
            document.getElementById('countdown-display').classList.add('hidden');
            document.getElementById('countdown-expired').classList.remove('hidden');
            document.getElementById('submit-btn').disabled = true;
            document.getElementById('submit-btn').classList.add('opacity-50', 'cursor-not-allowed');
            return;
        }

        const hours   = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        document.getElementById('cd-hours').textContent   = String(hours).padStart(2, '0');
        document.getElementById('cd-minutes').textContent = String(minutes).padStart(2, '0');
        document.getElementById('cd-seconds').textContent = String(seconds).padStart(2, '0');

        // Warna merah jika kurang dari 1 jam
        if (hours < 1) {
            ['cd-hours','cd-minutes','cd-seconds'].forEach(id => {
                document.getElementById(id).classList.replace('text-yellow-600', 'text-red-600');
            });
        }
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);

    // ── Copy No. Rekening ────────────────────────────────────────────
    function copyAccNo() {
        const text = document.getElementById('acc-no').textContent.trim();
        navigator.clipboard.writeText(text).then(() => {
            alert('No. rekening berhasil disalin: ' + text);
        });
    }

    // ── Preview Gambar ───────────────────────────────────────────────
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-area').classList.add('hidden');
                const img = document.getElementById('preview-img');
                img.src = e.target.result;
                img.classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.getElementById('proof-form')?.addEventListener('submit', function () {
        const btn = document.getElementById('submit-btn');
        btn.innerHTML = '<span>⏳</span><span>Mengirim...</span>';
        btn.disabled = true;
    });
</script>
@endpush

@endsection
