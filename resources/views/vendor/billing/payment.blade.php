@extends('layouts.vendor')
@section('title', 'Pembayaran Paket — Vendor')

@section('content')
<div class="max-w-lg mx-auto px-4 py-12">
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-4 mb-6 text-sm flex items-start gap-3 shadow-sm">
            <span class="text-lg">✅</span>
            <div>
                <p class="font-semibold">Berhasil!</p>
                <p class="text-emerald-700/90 mt-0.5">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-xl p-4 mb-6 text-sm flex items-start gap-3 shadow-sm">
            <span class="text-lg">⚠️</span>
            <div>
                <p class="font-semibold">Kesalahan!</p>
                <p class="text-rose-700/90 mt-0.5">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-xl p-4 mb-6 text-sm flex items-start gap-3 shadow-sm">
            <span class="text-lg">⚠️</span>
            <div>
                <p class="font-semibold">Periksa kembali form Anda:</p>
                <ul class="list-disc list-inside text-rose-700/90 mt-1 space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Pembayaran Paket</h1>
        <p class="text-gray-500 text-sm mb-6">Selesaikan pembayaran untuk mengaktifkan paket Anda secara penuh.</p>

        {{-- Info Paket --}}
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl border border-blue-100 p-5 mb-6">
            <p class="text-xs text-blue-600 font-semibold tracking-wider uppercase mb-1">Paket yang dipilih</p>
            <p class="text-2xl font-black text-blue-900">{{ $subscription->package->name }}</p>
            <p class="text-blue-700 text-sm mt-1">Komisi: {{ $subscription->package->commission_rate }}% per transaksi</p>
            <div class="mt-4 pt-3 border-t border-blue-200/60">
                @php
                    $amount = $subscription->package->price_per_month - $subscription->proration_credit;
                @endphp
                @if($subscription->proration_credit > 0)
                    <div class="flex justify-between text-xs text-blue-700 mb-1">
                        <span>Harga paket</span>
                        <span>Rp {{ number_format($subscription->package->price_per_month, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-emerald-700 mb-2">
                        <span>Kredit proration (sisa paket lama)</span>
                        <span>- Rp {{ number_format($subscription->proration_credit, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-blue-950 mt-2 pt-2 border-t border-blue-200">
                        <span>Total yang dibayar</span>
                        <span>Rp {{ number_format($amount, 0, ',', '.') }}</span>
                    </div>
                @else
                    <div class="flex justify-between text-base font-bold text-blue-950">
                        <span>Total yang dibayar</span>
                        <span>Rp {{ number_format($amount, 0, ',', '.') }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Petunjuk Transfer Manual --}}
        <div class="bg-gray-50 rounded-xl border border-gray-100 p-5 mb-6">
            <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-1.5">
                🏦 Petunjuk Transfer Bank
            </h3>
            <div class="space-y-3 text-sm text-gray-600">
                <div class="flex justify-between items-center py-0.5 border-b border-gray-200/50">
                    <span>Bank Tujuan</span>
                    <span class="font-bold text-gray-900">{{ env('PAYMENT_BANK_NAME', 'BCA') }}</span>
                </div>
                <div class="flex justify-between items-center py-0.5 border-b border-gray-200/50">
                    <span>Nomor Rekening</span>
                    <div class="flex items-center gap-1">
                        <span id="accNumber" class="font-bold text-gray-900 select-all">{{ env('PAYMENT_ACCOUNT_NO', '1234567890') }}</span>
                        <button type="button" onclick="copyAccNumber()" class="text-blue-600 hover:text-blue-700 text-xs font-semibold focus:outline-none">
                            [Salin]
                        </button>
                    </div>
                </div>
                <div class="flex justify-between items-center py-0.5">
                    <span>Atas Nama</span>
                    <span class="font-bold text-gray-900">{{ env('PAYMENT_ACCOUNT_NAME', 'PT Rental Mobil Indonesia') }}</span>
                </div>
            </div>
        </div>

        @if($subscription->payment_proof)
            {{-- Status Bukti Pembayaran Sudah Diupload --}}
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <span class="flex h-2.5 w-2.5 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                    </span>
                    <h4 class="text-sm font-bold text-amber-900">Menunggu Konfirmasi Admin</h4>
                </div>
                <p class="text-xs text-amber-800 leading-relaxed mb-4">
                    Bukti transfer telah berhasil dikirim. Admin akan memverifikasi pembayaran Anda dalam kurun waktu 1x24 jam untuk mengaktifkan paket ini.
                </p>
                <div class="border border-amber-200/80 rounded-lg p-3 bg-white">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Catatan Pengirim / Referensi</p>
                    <p class="text-sm font-medium text-gray-900 mb-3">{{ $subscription->transfer_ref ?: '—' }}</p>
                    
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Bukti Transfer</p>
                    <a href="{{ asset('storage/' . $subscription->payment_proof) }}" target="_blank" class="group block relative rounded border border-gray-100 overflow-hidden bg-gray-50 max-h-48">
                        <img src="{{ asset('storage/' . $subscription->payment_proof) }}" class="w-full object-contain max-h-48 group-hover:scale-[1.02] transition" alt="Bukti Transfer">
                        <div class="absolute inset-0 bg-black/10 group-hover:bg-black/20 flex items-center justify-center transition">
                            <span class="text-xs bg-white/90 text-gray-800 px-3 py-1.5 rounded-full font-semibold shadow-sm backdrop-blur-sm opacity-0 group-hover:opacity-100 transition">
                                Lihat Gambar Penuh 👁
                            </span>
                        </div>
                    </a>
                </div>

                {{-- Opsi Upload Ulang jika salah --}}
                <div class="mt-4 text-center">
                    <button type="button" onclick="toggleUploadForm()" class="text-xs text-blue-600 hover:text-blue-700 font-semibold hover:underline">
                        Upload ulang bukti transfer baru
                    </button>
                </div>
            </div>
        @endif

        {{-- Form Upload Pembayaran --}}
        <div id="uploadFormContainer" class="{{ $subscription->payment_proof ? 'hidden' : '' }} mb-6">
            <form method="POST" action="{{ route('vendor.billing.upload-proof', $subscription->uuid) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Upload Bukti Transfer <span class="text-rose-500">*</span></label>
                    <div class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 hover:border-blue-500 transition text-center cursor-pointer bg-gray-50/50 group" id="dropzone">
                        <input type="file" name="payment_proof" id="payment_proof" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/jpeg,image/png,image/jpg" required onchange="previewFile(this)">
                        <div class="space-y-1" id="dropzonePlaceholder">
                            <div class="text-3xl">📸</div>
                            <p class="text-sm font-semibold text-gray-700 group-hover:text-blue-600 transition">Pilih file atau seret ke sini</p>
                            <p class="text-xs text-gray-400">Format: JPG, JPEG, atau PNG (Maks 2MB)</p>
                        </div>
                        <div class="hidden space-y-2" id="previewContainer">
                            <img id="imagePreview" class="mx-auto max-h-32 object-contain rounded border border-gray-200">
                            <p id="fileName" class="text-xs font-semibold text-gray-700 truncate max-w-xs mx-auto"></p>
                            <button type="button" onclick="resetFile()" class="text-xs text-rose-500 hover:text-rose-600 font-semibold mt-1">Hapus</button>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="transfer_ref" class="block text-sm font-bold text-gray-700 mb-1.5">No. Referensi / Nama Pengirim</label>
                    <input type="text" name="transfer_ref" id="transfer_ref" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 placeholder-gray-400" placeholder="Contoh: BCA A/N Budi Setiawan" value="{{ old('transfer_ref') }}">
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3.5 rounded-xl font-bold text-base shadow-sm transition">
                    📤 Kirim Bukti Pembayaran
                </button>
            </form>
        </div>

        <p class="text-xs text-gray-400 mb-6 text-center leading-relaxed">
            Masa aktif paket 30 hari akan berjalan terhitung setelah pembayaran Anda dikonfirmasi oleh admin.
        </p>



        <div class="mt-6 text-center">
            <a href="{{ route('vendor.billing.index') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-700 transition">← Kembali ke Billing</a>
        </div>
    </div>
</div>

<script>
    function copyAccNumber() {
        const text = document.getElementById('accNumber').innerText;
        navigator.clipboard.writeText(text).then(() => {
            alert('Nomor rekening berhasil disalin!');
        });
    }

    function toggleUploadForm() {
        const container = document.getElementById('uploadFormContainer');
        container.classList.toggle('hidden');
    }

    function previewFile(input) {
        const file = input.files[0];
        const placeholder = document.getElementById('dropzonePlaceholder');
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');
        const fileName = document.getElementById('fileName');

        if (file) {
            // Validasi format
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!validTypes.includes(file.type)) {
                alert('Format file tidak sesuai! Pilih gambar JPG, JPEG, atau PNG.');
                input.value = '';
                return;
            }

            // Validasi ukuran (2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran file terlalu besar! Maksimal 2MB.');
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                fileName.innerText = file.name;
                placeholder.classList.add('hidden');
                previewContainer.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    }

    function resetFile() {
        const input = document.getElementById('payment_proof');
        const placeholder = document.getElementById('dropzonePlaceholder');
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');
        const fileName = document.getElementById('fileName');

        input.value = '';
        imagePreview.src = '';
        fileName.innerText = '';
        placeholder.classList.remove('hidden');
        previewContainer.classList.add('hidden');
    }
</script>
@endsection
