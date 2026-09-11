@extends('layouts.app')
@section('title', 'Tentang Kami - Rental Mobil')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <h1 class="text-4xl font-bold text-gray-900 mb-4">Tentang Kami</h1>
    <p class="text-gray-500 mb-12">Platform rental mobil terpercaya di Indonesia</p>

    <div class="prose max-w-none space-y-8">
        <div class="bg-blue-50 rounded-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">🚗 Siapa Kami?</h2>
            <p class="text-gray-700 leading-relaxed">
                Rental Mobil adalah platform marketplace rental mobil yang menghubungkan penyewa dengan vendor armada terpercaya di seluruh Indonesia.
                Kami hadir untuk memudahkan proses sewa mobil — dari pencarian, pemesanan, hingga pembayaran — semua dalam satu platform yang aman dan transparan.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach([
                ['icon' => '🎯', 'title' => 'Misi Kami', 'desc' => 'Menyediakan akses transportasi yang mudah, aman, dan terjangkau bagi seluruh masyarakat Indonesia.'],
                ['icon' => '👁️', 'title' => 'Visi Kami', 'desc' => 'Menjadi platform rental mobil nomor 1 di Indonesia dengan ekosistem vendor yang sehat dan terpercaya.'],
                ['icon' => '💎', 'title' => 'Nilai Kami', 'desc' => 'Transparansi, kepercayaan, dan kemudahan adalah fondasi dari setiap layanan yang kami bangun.'],
            ] as $item)
                <div class="bg-white rounded-xl border border-gray-200 p-6 text-center">
                    <div class="text-4xl mb-3">{{ $item['icon'] }}</div>
                    <h3 class="font-bold text-gray-900 mb-2">{{ $item['title'] }}</h3>
                    <p class="text-gray-600 text-sm">{{ $item['desc'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Mengapa Memilih Kami?</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach([
                    '250+ vendor terverifikasi di seluruh Indonesia',
                    '1.500+ unit armada siap sewa',
                    'Pembayaran aman via Midtrans (escrow)',
                    'Payout mingguan untuk vendor',
                    'Verifikasi identitas penyewa & vendor',
                    'Support 7 hari seminggu',
                ] as $point)
                    <div class="flex items-center space-x-3">
                        <span class="text-green-500 text-xl flex-shrink-0">✓</span>
                        <p class="text-gray-700">{{ $point }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
