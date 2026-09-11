@extends('layouts.app')
@section('title', 'Verifikasi Email - Rental Mobil')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8 text-center">
        <div class="text-5xl mb-4">📧</div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Cek Email Anda</h2>
        <p class="text-gray-600 mb-2">
            Kami telah mengirim link verifikasi ke:
        </p>
        <p class="font-semibold text-blue-600 mb-6">{{ auth()->user()->email }}</p>

        @if(session('status'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-800 text-sm">✅ {{ session('status') }}</p>
            </div>
        @endif

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 text-left">
            <p class="text-yellow-800 text-sm">
                ⚠️ Sebelum melanjutkan, silakan verifikasi email Anda dengan mengklik link yang kami kirim.
                Jika tidak menerima email, klik tombol di bawah untuk kirim ulang.
            </p>
        </div>

        <form method="POST" action="{{ route('verification.send') }}" class="mb-4">
            @csrf
            <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 font-semibold transition">
                Kirim Ulang Email Verifikasi
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-gray-500 hover:text-gray-700 text-sm">
                Logout
            </button>
        </form>
    </div>
</div>
@endsection
