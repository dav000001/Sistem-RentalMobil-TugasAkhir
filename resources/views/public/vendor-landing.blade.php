@extends('layouts.app')

@section('title', 'Jadi Vendor — Rental Mobil | Daftarkan Mobil Anda Hari Ini')

@section('content')

{{-- ─── HERO ─────────────────────────────────────────────────────────────── --}}
<section class="bg-gradient-to-br from-blue-700 via-blue-600 to-blue-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="inline-block bg-blue-500 bg-opacity-50 text-blue-100 text-sm font-medium px-3 py-1 rounded-full mb-4">
                    🚗 Tanpa biaya pendaftaran
                </span>
                <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-6">
                    Punya Mobil?<br>
                    <span class="text-yellow-300">Jadikan Aset Penghasil Cuan.</span>
                </h1>
                <p class="text-xl text-blue-100 mb-8 leading-relaxed">
                    Bergabung dengan Rental Mobil dan jangkau ribuan penyewa di seluruh Indonesia. Tanpa biaya pendaftaran, pencairan mingguan.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    @auth
                        @if(auth()->user()->vendor && auth()->user()->vendor->isApproved())
                            <a href="/vendor" class="bg-yellow-400 text-gray-900 px-8 py-4 rounded-xl font-bold text-lg hover:bg-yellow-300 transition text-center">
                                Buka Dashboard Vendor →
                            </a>
                        @elseif(auth()->user()->vendor)
                            <div class="bg-white bg-opacity-15 rounded-xl px-6 py-4 text-center">
                                <p class="text-sm text-blue-100 mb-1">Status pendaftaran vendor:</p>
                                <p class="font-bold text-yellow-300 text-lg">{{ auth()->user()->vendor->status->label() }}</p>
                                <a href="{{ route('vendor.onboarding') }}" class="inline-block mt-3 bg-white bg-opacity-20 text-white px-6 py-2 rounded-lg font-medium hover:bg-opacity-30 transition text-sm">
                                    Lihat Detail Pendaftaran →
                                </a>
                            </div>
                        @elseif(auth()->user()->customer)
                            <button onclick="document.getElementById('upgrade-modal').classList.remove('hidden')"
                                    class="bg-yellow-400 text-gray-900 px-8 py-4 rounded-xl font-bold text-lg hover:bg-yellow-300 transition">
                                Upgrade Akun ke Vendor →
                            </button>
                        @else
                            <p class="text-blue-200 text-sm italic">Akun admin tidak bisa mendaftar sebagai vendor.</p>
                        @endif
                    @else
                        <a href="{{ route('vendor.register') }}"
                           class="bg-yellow-400 text-gray-900 px-8 py-4 rounded-xl font-bold text-lg hover:bg-yellow-300 transition text-center">
                            Daftar Sebagai Vendor →
                        </a>
                    @endauth
                    <a href="#cara-kerja"
                       class="border-2 border-white text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-white hover:text-blue-700 transition text-center">
                        Pelajari Cara Kerja
                    </a>
                </div>
            </div>
            <div class="hidden lg:flex items-center justify-center">
                <div class="bg-white bg-opacity-10 rounded-2xl p-8 text-center">
                    <div class="text-8xl mb-4">🚗</div>
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="bg-white bg-opacity-20 rounded-xl p-4">
                            <p class="text-3xl font-bold text-yellow-300">250+</p>
                            <p class="text-sm text-blue-100">Vendor Aktif</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-xl p-4">
                            <p class="text-3xl font-bold text-yellow-300">1.500+</p>
                            <p class="text-sm text-blue-100">Unit Armada</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-xl p-4">
                            <p class="text-3xl font-bold text-yellow-300">4.8★</p>
                            <p class="text-sm text-blue-100">Rating Platform</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-xl p-4">
                            <p class="text-3xl font-bold text-yellow-300">Rp 0</p>
                            <p class="text-sm text-blue-100">Biaya Daftar</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ─── TRUST STRIP ──────────────────────────────────────────────────────── --}}
<section class="bg-gray-50 border-b border-gray-200 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap justify-center items-center gap-8 text-gray-600 text-sm font-medium">
            <div class="flex items-center space-x-2">
                <span class="text-2xl">✅</span>
                <span>250+ Vendor Aktif</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-2xl">🚗</span>
                <span>1.500+ Unit Armada</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-2xl">⭐</span>
                <span>Rating 4.8/5</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-2xl">🔒</span>
                <span>Pembayaran Aman via Midtrans</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-2xl">💸</span>
                <span>Payout Mingguan</span>
            </div>
        </div>
    </div>
</section>

{{-- ─── BENEFIT GRID ─────────────────────────────────────────────────────── --}}
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Kenapa Bergabung dengan Kami?</h2>
            <p class="text-gray-500 text-lg max-w-2xl mx-auto">Platform terpercaya untuk vendor rental mobil di Indonesia</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach([
                ['icon' => '💰', 'title' => 'Pendapatan Tambahan', 'desc' => 'Terima orderan setiap hari. Atur harga sendiri sesuai pasar. Tidak ada batas penghasilan.', 'color' => 'yellow'],
                ['icon' => '🛡️', 'title' => 'Aman & Terjamin', 'desc' => 'Escrow payment — uang aman di platform sampai rental selesai. Asuransi opsional tersedia.', 'color' => 'blue'],
                ['icon' => '📊', 'title' => 'Dashboard Lengkap', 'desc' => 'Kelola armada, jadwal, pesanan, dan laporan keuangan dari satu dashboard yang mudah digunakan.', 'color' => 'green'],
                ['icon' => '🤝', 'title' => 'Pencairan Cepat', 'desc' => 'Payout mingguan otomatis ke rekening Anda. Tidak perlu menunggu lama untuk menerima hasil.', 'color' => 'purple'],
            ] as $benefit)
                <div class="bg-gray-50 rounded-2xl p-6 hover:shadow-lg transition-shadow border border-gray-100">
                    <div class="text-4xl mb-4">{{ $benefit['icon'] }}</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $benefit['title'] }}</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">{{ $benefit['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ─── CARA KERJA ───────────────────────────────────────────────────────── --}}
<section id="cara-kerja" class="py-20 bg-blue-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Cara Kerja</h2>
            <p class="text-gray-500 text-lg">Mulai dalam 4 langkah mudah</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            @foreach([
                ['step' => '1', 'icon' => '📝', 'title' => 'Daftar Gratis', 'desc' => 'Isi form registrasi dan lengkapi dokumen identitas bisnis Anda.'],
                ['step' => '2', 'icon' => '✅', 'title' => 'Verifikasi Admin', 'desc' => 'Tim kami memverifikasi dokumen dalam ≤24 jam kerja.'],
                ['step' => '3', 'icon' => '🚗', 'title' => 'Tambah Armada', 'desc' => 'Upload foto, atur harga, dan tentukan jadwal ketersediaan mobil.'],
                ['step' => '4', 'icon' => '💰', 'title' => 'Terima Bayaran', 'desc' => 'Terima pesanan, serahkan mobil, dan dapatkan payout mingguan.'],
            ] as $step)
                <div class="text-center relative">
                    <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4 shadow-lg">
                        {{ $step['step'] }}
                    </div>
                    <div class="text-3xl mb-3">{{ $step['icon'] }}</div>
                    <h3 class="font-bold text-gray-900 mb-2">{{ $step['title'] }}</h3>
                    <p class="text-gray-600 text-sm">{{ $step['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ─── KOMISI & BIAYA ───────────────────────────────────────────────────── --}}
<section class="py-20 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Komisi & Paket</h2>
            <p class="text-gray-500 text-lg">Transparan, tanpa biaya tersembunyi</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach([
                ['name' => 'Free', 'price' => 'Gratis', 'commission' => '12%', 'features' => ['Maks. 5 listing', 'Dashboard dasar', 'Payout mingguan', 'Support email'], 'highlight' => false, 'is_free' => true],
                ['name' => 'Basic', 'price' => 'Rp 99.000/bln', 'commission' => '9%', 'features' => ['Listing hingga 10 mobil', 'Prioritas pencarian', 'Dashboard lengkap', 'Support WhatsApp'], 'highlight' => true, 'is_free' => false],
                ['name' => 'Premium', 'price' => 'Rp 249.000/bln', 'commission' => '5%', 'features' => ['Listing unlimited', 'Posisi teratas pencarian', 'Analytics lanjutan', 'Dedicated support'], 'highlight' => false, 'is_free' => false],
            ] as $plan)
                <div class="rounded-2xl border-2 {{ $plan['highlight'] ? 'border-blue-500 shadow-xl scale-105' : 'border-gray-200' }} p-8 relative">
                    @if($plan['highlight'])
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-xs font-bold px-4 py-1 rounded-full">POPULER</span>
                    @endif
                    <h3 class="text-xl font-bold text-gray-900 mb-1">{{ $plan['name'] }}</h3>
                    <p class="text-3xl font-bold {{ $plan['highlight'] ? 'text-blue-600' : 'text-gray-800' }} mb-1">{{ $plan['price'] }}</p>
                    <p class="text-gray-500 text-sm mb-6">Komisi <strong>{{ $plan['commission'] }}</strong> per transaksi</p>
                    <ul class="space-y-3 mb-8">
                        @foreach($plan['features'] as $feature)
                            <li class="flex items-center text-sm text-gray-700">
                                <span class="text-green-500 mr-2">✓</span>{{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    @if($plan['is_free'])
                        {{-- Free: bisa langsung daftar --}}
                        <a href="{{ route('vendor.register') }}"
                           class="block text-center py-3 rounded-xl font-semibold transition border-2 border-gray-300 text-gray-700 hover:border-blue-500 hover:text-blue-600">
                            Daftar Gratis Sekarang
                        </a>
                    @else
                        {{-- Basic & Premium: tampil sebagai tombol tapi klik = pemberitahuan --}}
                        <button type="button"
                            onclick="alert('Paket {{ $plan['name'] }} tersedia setelah akun Anda diverifikasi admin.\n\nCara upgrade:\n1. Daftar dengan Paket Free\n2. Lengkapi dokumen & tunggu verifikasi\n3. Login ke panel vendor\n4. Buka menu Billing & Paket\n5. Pilih upgrade ke {{ $plan['name'] }}')"
                            class="block w-full text-center py-3 rounded-xl font-semibold transition {{ $plan['highlight'] ? 'bg-blue-600 text-white hover:bg-blue-700' : 'border-2 border-gray-300 text-gray-700 hover:border-blue-500 hover:text-blue-600' }}">
                            Mulai dengan {{ $plan['name'] }}
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
        <p class="text-center text-gray-400 text-sm mt-8">
            * Semua vendor mulai dengan Paket Free. Upgrade tersedia setelah akun diverifikasi admin.
        </p>
    </div>
</section>

{{-- ─── SYARAT VENDOR ────────────────────────────────────────────────────── --}}
<section class="py-20 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Syarat Menjadi Vendor</h2>
            <p class="text-gray-500">Pastikan Anda memenuhi persyaratan berikut</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach([
                    'WNI atau badan usaha legal yang terdaftar di Indonesia',
                    'KTP, NPWP, dan SIUP/NIB yang masih berlaku',
                    'Minimal 1 unit mobil siap sewa (STNK & BPKB sah)',
                    'Rekening bank atas nama pribadi atau perusahaan',
                    'Nomor WhatsApp aktif untuk verifikasi OTP',
                    'Bersedia mengikuti SOP serah terima & SLA platform',
                ] as $req)
                    <div class="flex items-start space-x-3">
                        <span class="text-green-500 text-xl mt-0.5 flex-shrink-0">✓</span>
                        <p class="text-gray-700">{{ $req }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ─── TESTIMONI ────────────────────────────────────────────────────────── --}}
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Kata Vendor Kami</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach([
                ['name' => 'Budi Hartono', 'business' => 'BH Rent Car', 'city' => 'Jakarta', 'quote' => 'Sejak bergabung 8 bulan lalu, pendapatan saya naik 3x lipat. Dashboard-nya mudah dipakai dan payout selalu tepat waktu.', 'rating' => 5],
                ['name' => 'Sari Dewi', 'business' => 'Sari Transport', 'city' => 'Bandung', 'quote' => 'Proses verifikasi cepat, hanya 1 hari. Sekarang armada 5 mobil saya selalu penuh booking setiap weekend.', 'rating' => 5],
                ['name' => 'Ahmad Fauzi', 'business' => 'Fauzi Rental', 'city' => 'Surabaya', 'quote' => 'Platform terbaik untuk vendor rental. Support responsif dan sistem escrow membuat saya tenang soal pembayaran.', 'rating' => 5],
            ] as $testimonial)
                <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100">
                    <div class="flex items-center space-x-1 mb-4">
                        @for($i = 0; $i < $testimonial['rating']; $i++)
                            <span class="text-yellow-400">⭐</span>
                        @endfor
                    </div>
                    <p class="text-gray-700 italic mb-6">"{{ $testimonial['quote'] }}"</p>
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                            {{ strtoupper(substr($testimonial['name'], 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ $testimonial['name'] }}</p>
                            <p class="text-sm text-gray-500">{{ $testimonial['business'] }} · {{ $testimonial['city'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ─── FAQ ──────────────────────────────────────────────────────────────── --}}
<section class="py-20 bg-gray-50">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Pertanyaan Umum</h2>
        </div>
        <div class="space-y-4" x-data="{ open: null }">
            @foreach([
                ['q' => 'Berapa lama proses verifikasi?', 'a' => 'Proses verifikasi dokumen membutuhkan waktu maksimal 1×24 jam kerja. Anda akan mendapat notifikasi email dan WhatsApp setelah proses selesai.'],
                ['q' => 'Apakah ada biaya pendaftaran?', 'a' => 'Tidak ada biaya pendaftaran. Anda hanya dikenakan komisi saat ada transaksi berhasil. Paket Free tersedia selamanya tanpa biaya bulanan.'],
                ['q' => 'Bagaimana sistem pembayaran & pencairan?', 'a' => 'Pembayaran dari penyewa masuk ke escrow platform. Setelah rental selesai dan dikonfirmasi, dana dikurangi komisi akan ditransfer ke rekening Anda setiap minggu.'],
                ['q' => 'Bagaimana jika mobil saya rusak saat disewa?', 'a' => 'Platform menyediakan sistem dispute dan mediasi. Penyewa wajib melaporkan kondisi mobil saat serah terima. Kami merekomendasikan asuransi tambahan untuk perlindungan optimal.'],
                ['q' => 'Apakah saya bisa atur harga sendiri?', 'a' => 'Ya, Anda bebas menentukan harga sewa per hari, per minggu, dan per bulan. Anda juga bisa mengatur harga sopir dan biaya antar.'],
                ['q' => 'Apakah bisa daftar sebagai pribadi (bukan PT/CV)?', 'a' => 'Bisa! Anda bisa mendaftar sebagai perorangan dengan KTP dan NPWP pribadi. Tidak harus berbentuk badan usaha.'],
            ] as $i => $faq)
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <button @click="open = open === {{ $i }} ? null : {{ $i }}"
                            class="w-full flex justify-between items-center px-6 py-4 text-left font-semibold text-gray-900 hover:bg-gray-50 transition">
                        <span>{{ $faq['q'] }}</span>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open === {{ $i }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === {{ $i }}" x-transition class="px-6 pb-4 text-gray-600 text-sm leading-relaxed">
                        {{ $faq['a'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ─── CTA PENUTUP ──────────────────────────────────────────────────────── --}}
<section class="py-20 bg-gradient-to-r from-blue-600 to-blue-800 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Siap Memulai?</h2>
        <p class="text-xl text-blue-100 mb-8">Daftar gratis dalam 2 menit. Mulai terima pesanan hari ini.</p>
        @guest
            <a href="{{ route('vendor.register') }}"
               class="inline-block bg-yellow-400 text-gray-900 px-10 py-4 rounded-xl font-bold text-lg hover:bg-yellow-300 transition shadow-lg">
                Daftar Sebagai Vendor →
            </a>
        @else
            @if(auth()->user()->vendor && auth()->user()->vendor->isApproved())
                <a href="/vendor" class="inline-block bg-yellow-400 text-gray-900 px-10 py-4 rounded-xl font-bold text-lg hover:bg-yellow-300 transition shadow-lg">
                    Buka Dashboard Vendor →
                </a>
            @elseif(auth()->user()->vendor)
                <div class="inline-block">
                    <p class="text-blue-200 text-sm mb-2">Status: <span class="text-yellow-300 font-bold">{{ auth()->user()->vendor->status->label() }}</span></p>
                    <a href="{{ route('vendor.onboarding') }}" class="inline-block bg-white bg-opacity-20 text-white px-10 py-4 rounded-xl font-bold text-lg hover:bg-opacity-30 transition shadow-lg">
                        Lihat Status Pendaftaran →
                    </a>
                </div>
            @else
                <button onclick="document.getElementById('upgrade-modal').classList.remove('hidden')"
                        class="inline-block bg-yellow-400 text-gray-900 px-10 py-4 rounded-xl font-bold text-lg hover:bg-yellow-300 transition shadow-lg">
                    Upgrade ke Vendor →
                </button>
            @endif
        @endguest
    </div>
</section>

{{-- ─── UPGRADE MODAL (untuk customer yang sudah login) ─────────────────── --}}
@auth
    @if(auth()->user()->customer && !auth()->user()->vendor)
        <div id="upgrade-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 px-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8">
                <h3 class="text-xl font-bold text-gray-900 mb-2">Upgrade ke Vendor</h3>
                <p class="text-gray-600 text-sm mb-6">Akun Anda saat ini adalah Penyewa. Tambahkan profil vendor tanpa membuat akun baru.</p>

                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('vendor.upgrade') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Bisnis *</label>
                        <input type="text" name="business_name" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Bisnis *</label>
                        <select name="business_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih tipe</option>
                            <option value="perorangan">Perorangan</option>
                            <option value="cv">CV</option>
                            <option value="pt">PT</option>
                            <option value="komunitas">Komunitas</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kota Operasional *</label>
                        <select name="city_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih kota</option>
                            @foreach(\App\Models\City::orderBy('name')->get() as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estimasi Armada *</label>
                        <div class="grid grid-cols-4 gap-2"
                             x-data="{ fleet: '' }">
                            @foreach(['1-2', '3-5', '6-10', '>10'] as $size)
                                <label class="flex items-center justify-center border-2 rounded-lg p-2 cursor-pointer text-sm font-medium select-none transition"
                                       :class="fleet === '{{ $size }}'
                                           ? 'border-blue-500 bg-blue-50 text-blue-700'
                                           : 'border-gray-200 text-gray-700 hover:border-blue-500'"
                                       @click="fleet = '{{ $size }}'">
                                    <input type="radio" name="fleet_size_estimate" value="{{ $size }}" class="sr-only" x-model="fleet">
                                    {{ $size }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex space-x-3 pt-2">
                        <button type="button" onclick="document.getElementById('upgrade-modal').classList.add('hidden')"
                                class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-50 text-sm font-medium">
                            Batal
                        </button>
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 text-sm font-semibold">
                            Upgrade Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endauth

{{-- ─── STICKY MOBILE CTA ────────────────────────────────────────────────── --}}
@guest
    <div class="fixed bottom-0 left-0 right-0 z-40 md:hidden bg-white border-t border-gray-200 p-4 shadow-lg">
        <a href="{{ route('vendor.register') }}"
           class="block w-full bg-blue-600 text-white text-center py-3 rounded-xl font-bold hover:bg-blue-700 transition">
            Daftar Sebagai Vendor — Gratis →
        </a>
    </div>
@endguest

@endsection
