@extends('layouts.app')

@section('title', 'Tambah Akun - Rental Mobil')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">

        <!-- Header -->
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Tambah Akun Lain</h2>
            <p class="text-gray-500 text-sm mt-1">
                Akun <strong>{{ $currentUser->vendor?->business_name ?? $currentUser->name }}</strong> tetap aktif
            </p>
        </div>

        <!-- Active accounts info -->
        @if(count($accounts) > 0)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-6">
                <p class="text-blue-800 text-sm font-medium mb-2">Akun aktif di browser ini:</p>
                @foreach($accounts as $account)
                    <div class="flex items-center space-x-2 text-sm text-blue-700">
                        <span class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold">
                            {{ strtoupper(substr($account['label'], 0, 1)) }}
                        </span>
                        <span>{{ $account['label'] }}</span>
                        <span class="text-xs bg-blue-200 px-1 rounded">{{ $account['role'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                @foreach ($errors->all() as $error)
                    <p class="text-sm">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('auth.add-account') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="email" name="email" type="email" required
                       value="{{ old('email') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input id="password" name="password" type="password" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 font-semibold">
                Masuk ke Akun Lain
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('home') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Kembali</a>
        </div>
    </div>
</div>
@endsection
