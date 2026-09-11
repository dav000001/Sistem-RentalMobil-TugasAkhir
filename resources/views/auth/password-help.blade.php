@extends('layouts.app')
@section('title', 'Minta Bantuan Reset Password - Rental Mobil')
@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">

        <div class="text-center mb-6">
            <div class="text-4xl mb-3">🔐</div>
            <h2 class="text-2xl font-bold text-gray-900">Minta Bantuan Reset Password</h2>
            <p class="text-gray-500 text-sm mt-1">Admin akan memproses permintaan Anda</p>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p class="text-yellow-800 text-sm">
                ⏳ Isi form berikut. Admin akan menerima notifikasi dan memproses permintaan Anda dalam <strong>1×24 jam kerja</strong>.
            </p>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-800 text-sm font-medium">✅ {{ session('success') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                @foreach ($errors->all() as $error)
                    <p class="text-red-700 text-sm">• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.help') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Email Akun <span class="text-red-500">*</span>
                </label>
                <input type="email" name="email" required value="{{ old('email') }}"
                       placeholder="email@bisnis.com"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('email') border-red-400 @enderror">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nama Bisnis <span class="text-gray-400 font-normal">(opsional)</span>
                </label>
                <input type="text" name="business_name" value="{{ old('business_name') }}"
                       placeholder="Contoh: Budi Rent Car"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nomor WhatsApp <span class="text-gray-400 font-normal">(opsional)</span>
                </label>
                <input type="tel" name="whatsapp" value="{{ old('whatsapp') }}"
                       placeholder="08123456789"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Kenapa tidak bisa reset password? <span class="text-red-500">*</span>
                </label>
                <textarea name="reason" rows="4" required
                          placeholder="Contoh: Email lama sudah tidak aktif, tidak bisa akses inbox, dll."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('reason') border-red-400 @enderror">{{ old('reason') }}</textarea>
                @error('reason')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 font-semibold transition">
                Kirim Permintaan
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-gray-500 hover:text-gray-700 text-sm">
                ← Kembali ke halaman login
            </a>
        </div>
    </div>
</div>
@endsection
