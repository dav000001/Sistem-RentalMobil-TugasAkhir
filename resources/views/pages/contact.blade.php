@extends('layouts.app')
@section('title', 'Hubungi Kami - Rental Mobil')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <h1 class="text-4xl font-bold text-gray-900 mb-4">Hubungi Kami</h1>
    <p class="text-gray-500 mb-12">Kami siap membantu Anda 7 hari seminggu</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Info Kontak -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Informasi Kontak</h2>
                <div class="space-y-5">
                    @foreach([
                        ['icon' => '📧', 'label' => 'Email', 'value' => 'support@rentalmobil.com', 'link' => 'mailto:support@rentalmobil.com'],
                        ['icon' => '💬', 'label' => 'WhatsApp', 'value' => '+62 812-3456-7890', 'link' => 'https://wa.me/6281234567890'],
                        ['icon' => '🕐', 'label' => 'Jam Operasional', 'value' => 'Senin–Minggu, 08.00–22.00 WIB', 'link' => null],
                        ['icon' => '📍', 'label' => 'Alamat', 'value' => 'Jakarta, Indonesia', 'link' => null],
                    ] as $contact)
                        <div class="flex items-start space-x-4">
                            <span class="text-2xl flex-shrink-0">{{ $contact['icon'] }}</span>
                            <div>
                                <p class="text-sm text-gray-500">{{ $contact['label'] }}</p>
                                @if($contact['link'])
                                    <a href="{{ $contact['link'] }}" class="font-medium text-blue-600 hover:underline">{{ $contact['value'] }}</a>
                                @else
                                    <p class="font-medium text-gray-900">{{ $contact['value'] }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- Form Kontak -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Kirim Pesan</h2>
            @if(session('contact_success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 text-sm">
                    ✅ Pesan Anda berhasil dikirim. Kami akan membalas dalam 1×24 jam.
                </div>
            @endif
            <form method="POST" action="{{ route('contact.send') }}" class="space-y-4">
                @csrf
                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                        @foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
                    </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('name') border-red-400 @enderror"
                           placeholder="Nama lengkap Anda">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('email') border-red-400 @enderror"
                           placeholder="email@contoh.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subjek</label>
                    <select name="subject" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="Pertanyaan Umum" {{ old('subject') === 'Pertanyaan Umum' ? 'selected' : '' }}>Pertanyaan Umum</option>
                        <option value="Masalah Pemesanan" {{ old('subject') === 'Masalah Pemesanan' ? 'selected' : '' }}>Masalah Pemesanan</option>
                        <option value="Pendaftaran Vendor" {{ old('subject') === 'Pendaftaran Vendor' ? 'selected' : '' }}>Pendaftaran Vendor</option>
                        <option value="Pembayaran & Refund" {{ old('subject') === 'Pembayaran & Refund' ? 'selected' : '' }}>Pembayaran & Refund</option>
                        <option value="Lainnya" {{ old('subject') === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pesan</label>
                    <textarea name="message" rows="4" required
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('message') border-red-400 @enderror"
                              placeholder="Tuliskan pesan Anda...">{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 font-semibold text-sm transition">
                    Kirim Pesan
                </button>
                <p class="text-xs text-gray-400 text-center">Atau hubungi langsung via WhatsApp untuk respons lebih cepat</p>
            </form>
        </div>
    </div>
</div>
@endsection
