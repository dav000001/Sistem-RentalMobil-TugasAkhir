@extends('layouts.app')
@section('title', 'Bantuan & Support - Vendor')

@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <div class="mb-6">
        <a href="/vendor" class="text-sm text-blue-600 hover:underline">← Kembali ke Dashboard</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-3">Bantuan & Support</h1>
        <p class="text-gray-500 text-sm mt-1">Ada pertanyaan atau kendala? Kirim pesan ke admin dan kami akan segera merespons.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-4 rounded-xl">
            <p class="font-semibold">✅ Pesan terkirim!</p>
            <p class="text-sm mt-1">{{ session('success') }}</p>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6" x-data="{ subject: '{{ old('subject') }}' }">
        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-gray-100">
            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 font-bold text-lg">
                {{ strtoupper(substr($vendor->business_name, 0, 1)) }}
            </div>
            <div>
                <p class="font-semibold text-gray-900 text-sm">{{ $vendor->business_name }}</p>
                <p class="text-xs text-gray-500">{{ auth('vendor')->user()->email }}</p>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('vendor.support.send') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subjek / Topik <span class="text-red-500">*</span></label>
                <select name="subject" required x-model="subject" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-orange-500">
                    <option value="">Pilih topik pertanyaan</option>
                    <option value="Bantuan Upload Mobil" {{ old('subject') === 'Bantuan Upload Mobil' ? 'selected' : '' }}>Bantuan Upload Mobil (Tolong Upload-kan Mobil)</option>
                    <option value="Cara Upload Mobil" {{ old('subject') === 'Cara Upload Mobil' ? 'selected' : '' }}>Cara Upload Mobil</option>
                    <option value="Cara Konfirmasi Booking" {{ old('subject') === 'Cara Konfirmasi Booking' ? 'selected' : '' }}>Cara Konfirmasi Booking</option>
                    <option value="Masalah Payout / Pembayaran" {{ old('subject') === 'Masalah Payout / Pembayaran' ? 'selected' : '' }}>Masalah Payout / Pembayaran</option>
                    <option value="Paket & Komisi" {{ old('subject') === 'Paket & Komisi' ? 'selected' : '' }}>Paket & Komisi</option>
                    <option value="Verifikasi Dokumen" {{ old('subject') === 'Verifikasi Dokumen' ? 'selected' : '' }}>Verifikasi Dokumen</option>
                    <option value="Bug / Error di Sistem" {{ old('subject') === 'Bug / Error di Sistem' ? 'selected' : '' }}>Bug / Error di Sistem</option>
                    <option value="Lainnya" {{ old('subject') === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" x-text="subject === 'Bantuan Upload Mobil' ? 'Deskripsi Mobil & Kelengkapan Detail *' : 'Pertanyaan / Kendala *'">Pertanyaan / Kendala <span class="text-red-500">*</span></label>
                <textarea name="message" rows="5" required minlength="10"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-orange-500"
                          :placeholder="subject === 'Bantuan Upload Mobil' ? 'Contoh:\n- Merk & Model: Toyota Avanza Veloz\n- Tahun: 2022\n- Transmisi: Automatic\n- Bahan Bakar: Bensin\n- Plat Nomor: B 1234 ABC\n- Kapasitas Kursi: 7\n- Bagasi: 2\n- Harga Sewa: Rp 350.000/hari\n- Informasi tambahan lainnya...' : 'Jelaskan pertanyaan atau kendala Anda secara detail...'">{{ old('message') }}</textarea>
                <p class="text-xs text-gray-400 mt-1">Minimal 10 karakter. Semakin detail, semakin cepat kami bisa membantu.</p>
            </div>

            <div x-show="subject === 'Bantuan Upload Mobil'" class="mt-4" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 mb-1">Upload Gambar / Foto Mobil <span class="text-red-500">*</span></label>
                <input type="file" name="attachments[]" multiple accept="image/*" :required="subject === 'Bantuan Upload Mobil'"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 bg-white">
                <p class="text-xs text-gray-400 mt-1">Silakan upload foto mobil tampak depan, samping, dan dalam untuk memudahkan admin.</p>
            </div>

            <button type="submit"
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-xl font-bold text-sm transition">
                📨 Kirim ke Admin
            </button>
        </form>
    </div>

    <div class="mt-4 bg-blue-50 rounded-xl p-4 text-sm text-blue-700">
        <p class="font-semibold mb-1">💡 Tips</p>
        <ul class="space-y-1 text-xs text-blue-600">
            <li>• Admin akan melihat pesan Anda di notifikasi panel admin</li>
            <li>• Respons biasanya dalam 1×24 jam kerja</li>
            <li>• Untuk masalah mendesak, hubungi via WhatsApp</li>
        </ul>
    </div>

</div>
@endsection
