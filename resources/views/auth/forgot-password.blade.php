@extends('layouts.app')
@section('title', 'Lupa Password - Rental Mobil')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-6">
            <div class="text-4xl mb-3">🔑</div>
            <h2 class="text-2xl font-bold text-gray-900">Lupa Password?</h2>
            <p class="text-gray-500 text-sm mt-1">Masukkan email Anda dan kami akan kirim link reset password</p>
        </div>

        @if(session('status'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-800 text-sm font-medium">✅ {{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                @foreach ($errors->all() as $error)
                    <p class="text-red-700 text-sm">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="email" name="email" type="email" required
                       value="{{ old('email') }}"
                       placeholder="email@contoh.com"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 @error('email') border-red-400 @enderror">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 font-semibold transition">
                Kirim Link Reset Password
            </button>
        </form>

        <div class="mt-6 text-center space-y-2">
            <a href="{{ route('login') }}" class="text-gray-500 hover:text-gray-700 text-sm block">← Kembali ke Login</a>
            <a href="{{ route('password.help') }}" class="text-gray-400 hover:text-gray-600 text-xs block">
                Tidak bisa akses email? Minta bantuan admin
            </a>
        </div>
    </div>
</div>
@endsection
