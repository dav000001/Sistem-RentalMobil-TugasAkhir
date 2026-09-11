@extends('layouts.app')

@section('title', 'Profil Saya - Rental Mobil')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold mb-8">Profil Saya</h1>

    <div class="space-y-6">
        <!-- Profile Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-lg mb-4">Informasi Pribadi</h3>
            
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" id="name" name="name" required
                           value="{{ old('name', $user->name) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" value="{{ $user->email }}" disabled
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50">
                    <p class="text-gray-500 text-sm mt-1">Email tidak bisa diubah</p>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Nomor HP</label>
                    <input type="tel" id="phone" name="phone" required
                           value="{{ old('phone', $user->phone) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    Simpan Perubahan
                </button>
            </form>
        </div>

        <!-- Rekening Bank untuk Refund -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-lg mb-1">Rekening Bank untuk Refund</h3>
            <p class="text-gray-500 text-sm mb-4">Digunakan jika pesanan dibatalkan dan Anda berhak mendapat pengembalian dana.</p>

            @if(session('bank_success'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4 text-sm text-green-800">
                    ✅ {{ session('bank_success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('profile.bank.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nama Bank <span class="text-red-500">*</span>
                    </label>
                    <select id="bank_name" name="bank_name"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 @error('bank_name') border-red-500 @enderror">
                        <option value="">-- Pilih Bank / E-Wallet --</option>
                        <optgroup label="🏦 Bank">
                            @foreach(['BCA','BRI','BNI','Mandiri','BSI','CIMB Niaga','Danamon','Permata','BTN','Maybank','OCBC','Jenius (BTPN)'] as $bank)
                                <option value="{{ $bank }}" {{ old('bank_name', $customer->bank_name) === $bank ? 'selected' : '' }}>
                                    {{ $bank }}
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="💳 E-Wallet">
                            @foreach(['GoPay','OVO','Dana','ShopeePay','LinkAja'] as $ewallet)
                                <option value="{{ $ewallet }}" {{ old('bank_name', $customer->bank_name) === $ewallet ? 'selected' : '' }}>
                                    {{ $ewallet }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                    @error('bank_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="bank_account_no" class="block text-sm font-medium text-gray-700 mb-1">
                        Nomor Rekening / Nomor HP E-Wallet <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="bank_account_no" name="bank_account_no"
                           value="{{ old('bank_account_no', $customer->bank_account_no) }}"
                           placeholder="Nomor rekening atau nomor HP terdaftar"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 @error('bank_account_no') border-red-500 @enderror">
                    @error('bank_account_no')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-400 text-xs mt-1">Untuk e-wallet (GoPay, OVO, Dana, dll) isi dengan nomor HP yang terdaftar.</p>
                </div>

                <div>
                    <label for="bank_account_name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nama Pemilik Rekening / Akun <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="bank_account_name" name="bank_account_name"
                           value="{{ old('bank_account_name', $customer->bank_account_name) }}"
                           placeholder="Nama sesuai buku tabungan"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 @error('bank_account_name') border-red-500 @enderror">
                    @error('bank_account_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    Simpan Rekening
                </button>
            </form>
        </div>

        <!-- Verification Status -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-lg mb-4">Verifikasi Identitas</h3>
            
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-gray-600">Status Verifikasi</p>
                        <p class="font-semibold text-lg">
                            @if($customer->verification_status === 'verified')
                                <span class="text-green-600">✓ Terverifikasi</span>
                            @elseif($customer->verification_status === 'pending')
                                <span class="text-yellow-600">⏳ Menunggu Persetujuan</span>
                            @else
                                <span class="text-red-600">✕ Ditolak</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($customer->verification_status !== 'verified')
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <p class="text-blue-800 text-sm">
                            Verifikasi identitas diperlukan untuk dapat melakukan pemesanan. Silakan upload dokumen KTP, SIM, dan selfie Anda.
                        </p>
                    </div>

                    {{-- Tampilkan alasan penolakan jika ada --}}
                    @if($customer->verification_status === 'rejected' && $customer->rejection_reason)
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <p class="text-red-800 font-semibold text-sm mb-1">❌ Verifikasi Ditolak</p>
                            <p class="text-red-700 text-sm"><strong>Alasan:</strong> {{ $customer->rejection_reason }}</p>
                            <p class="text-red-600 text-xs mt-2">Silakan upload ulang dokumen yang sesuai dengan alasan di atas.</p>
                        </div>
                    @elseif($customer->verification_status === 'pending')
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                            <p class="text-yellow-800 text-sm">⏳ Dokumen Anda sedang dalam proses verifikasi oleh admin. Biasanya selesai dalam 1×24 jam.</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('profile.verification.submit') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label for="ktp_file" class="block text-sm font-medium text-gray-700 mb-2">Foto KTP</label>
                            <input type="file" id="ktp_file" name="ktp_file" accept="image/*" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('ktp_file') border-red-500 @enderror">
                            @error('ktp_file')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-gray-500 text-xs mt-1">Format: JPG, PNG (Max 5MB)</p>
                        </div>

                        <div>
                            <label for="sim_file" class="block text-sm font-medium text-gray-700 mb-2">Foto SIM</label>
                            <input type="file" id="sim_file" name="sim_file" accept="image/*" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('sim_file') border-red-500 @enderror">
                            @error('sim_file')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-gray-500 text-xs mt-1">Format: JPG, PNG (Max 5MB)</p>
                        </div>

                        <div>
                            <label for="selfie_file" class="block text-sm font-medium text-gray-700 mb-2">Selfie Memegang KTP</label>
                            <input type="file" id="selfie_file" name="selfie_file" accept="image/*" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('selfie_file') border-red-500 @enderror">
                            @error('selfie_file')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-gray-500 text-xs mt-1">Format: JPG, PNG (Max 5MB)</p>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                            Upload Dokumen
                        </button>
                    </form>
                @else
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <p class="text-green-800">✓ Akun Anda sudah terverifikasi. Anda dapat melakukan pemesanan.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
