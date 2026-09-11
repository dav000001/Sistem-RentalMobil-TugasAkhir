@extends('layouts.app')

@section('title', 'Daftar Vendor — Rental Mobil')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4">
    <div class="max-w-5xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

            {{-- ─── FORM (3/5) ──────────────────────────────────────────── --}}
            <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                <div class="mb-8">
                    <a href="{{ route('vendor.landing') }}" class="text-blue-600 text-sm hover:underline">← Kembali ke info vendor</a>
                    <h1 class="text-2xl font-bold text-gray-900 mt-4 mb-1">Daftar sebagai Vendor</h1>
                    <p class="text-gray-500 text-sm">Mulai gratis. Verifikasi dokumen dilakukan di tahap berikutnya.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                        @foreach ($errors->all() as $error)
                            <p>• {{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('vendor.register') }}" class="space-y-5"
                      x-data="{
                          password: '',
                          strength() {
                              if (this.password.length === 0) return 0;
                              let s = 0;
                              if (this.password.length >= 8) s++;
                              if (/[A-Z]/.test(this.password)) s++;
                              if (/[0-9]/.test(this.password)) s++;
                              if (/[^A-Za-z0-9]/.test(this.password)) s++;
                              return s;
                          },
                          strengthLabel() {
                              const l = ['', 'Lemah', 'Cukup', 'Kuat', 'Sangat Kuat'];
                              return l[this.strength()] || '';
                          },
                          strengthColor() {
                              const c = ['', 'bg-red-400', 'bg-yellow-400', 'bg-blue-400', 'bg-green-500'];
                              return c[this.strength()] || '';
                          }
                      }">
                    @csrf

                    {{-- Informasi Akun --}}
                    <div class="border-b border-gray-100 pb-5">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Informasi Akun</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap PIC <span class="text-red-500">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-400 @enderror"
                                       placeholder="Nama penanggung jawab">
                                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                <input type="email" name="email" value="{{ old('email') }}" required
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 @error('email') border-red-400 @enderror"
                                       placeholder="email@bisnis.com">
                                @error('email')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}
                                        @if(str_contains($message, 'sudah terdaftar'))
                                            <a href="{{ route('login') }}" class="underline font-medium">Login di sini</a>
                                        @endif
                                    </p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp <span class="text-red-500">*</span></label>
                                <input type="tel" name="phone" value="{{ old('phone') }}" required
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 @error('phone') border-red-400 @enderror"
                                       placeholder="08123456789">
                                @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                                <input type="password" name="password" x-model="password" required
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 @error('password') border-red-400 @enderror"
                                       placeholder="Minimal 8 karakter">
                                <div class="mt-2 flex items-center space-x-2" x-show="password.length > 0">
                                    <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full transition-all" :class="strengthColor()" :style="`width: ${strength() * 25}%`"></div>
                                    </div>
                                    <span class="text-xs font-medium" :class="strength() >= 3 ? 'text-green-600' : 'text-gray-500'" x-text="strengthLabel()"></span>
                                </div>
                                @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password <span class="text-red-500">*</span></label>
                                <input type="password" name="password_confirmation" required
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500"
                                       placeholder="Ulangi password">
                            </div>
                        </div>
                    </div>

                    {{-- Informasi Bisnis --}}
                    <div class="border-b border-gray-100 pb-5">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Informasi Bisnis</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Bisnis <span class="text-red-500">*</span></label>
                                <input type="text" name="business_name" value="{{ old('business_name') }}" required
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 @error('business_name') border-red-400 @enderror"
                                       placeholder="Contoh: Budi Rent Car">
                                @error('business_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Bisnis <span class="text-red-500">*</span></label>
                                <select name="business_type" required
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 @error('business_type') border-red-400 @enderror">
                                    <option value="">Pilih tipe bisnis</option>
                                    <option value="perorangan" {{ old('business_type') === 'perorangan' ? 'selected' : '' }}>Perorangan</option>
                                    <option value="cv" {{ old('business_type') === 'cv' ? 'selected' : '' }}>CV</option>
                                    <option value="pt" {{ old('business_type') === 'pt' ? 'selected' : '' }}>PT</option>
                                    <option value="komunitas" {{ old('business_type') === 'komunitas' ? 'selected' : '' }}>Komunitas</option>
                                </select>
                                @error('business_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div x-data="{ customCity: {{ old('city_id') === 'other' || old('city_name') ? 'true' : 'false' }} }">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kota Operasional Utama <span class="text-red-500">*</span></label>
                                <select name="city_id"
                                        x-on:change="customCity = ($event.target.value === 'other')"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 @error('city_id') border-red-400 @enderror">
                                    <option value="">Pilih kota</option>
                                    @foreach($cities as $city)
                                        <option value="{{ $city->id }}" {{ old('city_id') == $city->id ? 'selected' : '' }}>
                                            {{ $city->name }}, {{ $city->province }}
                                        </option>
                                    @endforeach
                                    <option value="other" {{ old('city_id') === 'other' || old('city_name') ? 'selected' : '' }}>
                                        ✏️ Kota saya tidak ada di daftar...
                                    </option>
                                </select>
                                @error('city_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror

                                {{-- Input kota kustom -- muncul jika pilih "other" --}}
                                <div x-show="customCity" x-transition class="mt-2">
                                    <input type="text"
                                           name="city_name"
                                           value="{{ old('city_name') }}"
                                           placeholder="Tulis nama kota Anda, contoh: Purwokerto"
                                           class="w-full border border-blue-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 bg-blue-50 @error('city_name') border-red-400 @enderror">
                                    <p class="text-xs text-blue-600 mt-1">✏️ Kota akan ditambahkan ke sistem setelah Anda mendaftar.</p>
                                    @error('city_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estimasi Jumlah Armada <span class="text-red-500">*</span></label>
                                <div class="grid grid-cols-4 gap-2"
                                     x-data="{ fleet: '{{ old('fleet_size_estimate') }}' }">
                                    @foreach(['1-2', '3-5', '6-10', '>10'] as $size)
                                        <label class="flex items-center justify-center border-2 rounded-lg p-3 cursor-pointer transition text-sm font-semibold select-none"
                                               :class="fleet === '{{ $size }}'
                                                   ? 'border-blue-500 bg-blue-50 text-blue-700'
                                                   : 'border-gray-200 text-gray-700 hover:border-blue-300'"
                                               @click="fleet = '{{ $size }}'">
                                            <input type="radio"
                                                   name="fleet_size_estimate"
                                                   value="{{ $size }}"
                                                   class="sr-only"
                                                   x-model="fleet">
                                            {{ $size }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('fleet_size_estimate')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dari mana Anda tahu Rental Mobil?</label>
                                <select name="source" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                                    <option value="">Pilih (opsional)</option>
                                    <option value="google" {{ old('source') === 'google' ? 'selected' : '' }}>Google / Search Engine</option>
                                    <option value="instagram" {{ old('source') === 'instagram' ? 'selected' : '' }}>Instagram</option>
                                    <option value="facebook" {{ old('source') === 'facebook' ? 'selected' : '' }}>Facebook</option>
                                    <option value="teman" {{ old('source') === 'teman' ? 'selected' : '' }}>Rekomendasi Teman</option>
                                    <option value="youtube" {{ old('source') === 'youtube' ? 'selected' : '' }}>YouTube</option>
                                    <option value="lainnya" {{ old('source') === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Persetujuan --}}
                    <div class="space-y-3">
                        <label class="flex items-start space-x-3 cursor-pointer">
                            <input type="checkbox" name="accept_terms" value="1" required
                                   class="mt-0.5 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 @error('accept_terms') border-red-400 @enderror">
                            <span class="text-sm text-gray-700">
                                Saya menyetujui <a href="#" class="text-blue-600 hover:underline">Syarat & Ketentuan</a> platform <span class="text-red-500">*</span>
                            </span>
                        </label>
                        @error('accept_terms')<p class="text-red-500 text-xs ml-7">{{ $message }}</p>@enderror

                        <label class="flex items-start space-x-3 cursor-pointer">
                            <input type="checkbox" name="accept_privacy" value="1" required
                                   class="mt-0.5 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 @error('accept_privacy') border-red-400 @enderror">
                            <span class="text-sm text-gray-700">
                                Saya menyetujui <a href="#" class="text-blue-600 hover:underline">Kebijakan Privasi</a> <span class="text-red-500">*</span>
                            </span>
                        </label>
                        @error('accept_privacy')<p class="text-red-500 text-xs ml-7">{{ $message }}</p>@enderror

                        <label class="flex items-start space-x-3 cursor-pointer">
                            <input type="checkbox" name="newsletter" value="1"
                                   class="mt-0.5 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm text-gray-500">Saya ingin menerima tips & promo via email (opsional)</span>
                        </label>
                    </div>

                    <button type="submit"
                            class="w-full bg-blue-600 text-white py-3.5 rounded-xl font-bold text-base hover:bg-blue-700 transition shadow-sm">
                        Buat Akun Vendor →
                    </button>

                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
                        <p class="font-semibold mb-1">ℹ️ Tentang Paket Berlangganan</p>
                        <p class="text-xs text-blue-700">Semua vendor mulai dengan <strong>Paket Free (gratis)</strong>. Setelah akun diverifikasi admin, Anda bisa upgrade ke paket <strong>Basic (Rp 99.000/bln)</strong> atau <strong>Premium (Rp 249.000/bln)</strong> melalui menu <strong>Billing & Paket</strong> di dashboard vendor. Pembayaran upgrade dilakukan via transfer bank ke rekening platform.</p>
                    </div>

                    <p class="text-center text-sm text-gray-500">
                        Sudah punya akun?
                        <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-medium">Login di sini</a>
                    </p>
                </form>
            </div>

            {{-- ─── PANEL INFO (2/5) ────────────────────────────────────── --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Paket & Komisi --}}
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-5 py-4">
                        <h3 class="text-white font-bold text-base">Paket & Komisi Platform</h3>
                        <p class="text-blue-200 text-xs mt-0.5">Pilih paket sesuai skala bisnis Anda</p>
                    </div>

                    {{-- Paket Free --}}
                    <div class="p-4 border-b border-gray-100">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <span class="inline-block bg-gray-100 text-gray-700 text-xs font-bold px-2.5 py-1 rounded-full">FREE</span>
                                <p class="text-xs text-gray-500 mt-1">Mulai tanpa biaya</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-black text-gray-900">12%</p>
                                <p class="text-xs text-gray-400">komisi/transaksi</p>
                            </div>
                        </div>
                        <div class="space-y-1.5 mt-3">
                            @foreach(['Listing hingga 3 mobil', 'Payout mingguan', 'Dashboard dasar', 'Support via email'] as $f)
                                <div class="flex items-center gap-2 text-xs text-gray-600">
                                    <svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    {{ $f }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Paket Basic --}}
                    <div class="p-4 border-b border-gray-100 bg-blue-50/40">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <span class="inline-block bg-blue-100 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-full">BASIC</span>
                                <p class="text-xs text-gray-500 mt-1">Rp 99.000 / bulan</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-black text-blue-700">9%</p>
                                <p class="text-xs text-gray-400">komisi/transaksi</p>
                            </div>
                        </div>
                        <div class="space-y-1.5 mt-3">
                            @foreach(['Listing hingga 10 mobil', 'Payout mingguan', 'Statistik performa', 'Prioritas tampil di pencarian', 'Support via WhatsApp'] as $f)
                                <div class="flex items-center gap-2 text-xs text-gray-600">
                                    <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    {{ $f }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Paket Premium --}}
                    <div class="p-4 relative">
                        <div class="absolute top-3 right-3">
                            <span class="bg-yellow-400 text-yellow-900 text-xs font-black px-2 py-0.5 rounded-full">TERPOPULER</span>
                        </div>
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <span class="inline-block bg-gradient-to-r from-yellow-400 to-orange-400 text-white text-xs font-bold px-2.5 py-1 rounded-full">PREMIUM</span>
                                <p class="text-xs text-gray-500 mt-1">Rp 249.000 / bulan</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-black text-orange-500">5%</p>
                                <p class="text-xs text-gray-400">komisi/transaksi</p>
                            </div>
                        </div>
                        <div class="space-y-1.5 mt-3">
                            @foreach(['Listing mobil tidak terbatas', 'Payout 2× seminggu', 'Analitik lengkap + laporan', 'Posisi teratas di pencarian', 'Badge "Vendor Terverifikasi"', 'Dedicated account manager'] as $f)
                                <div class="flex items-center gap-2 text-xs text-gray-600">
                                    <svg class="w-3.5 h-3.5 text-orange-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    {{ $f }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="px-4 pb-4">
                        <p class="text-xs text-gray-400 text-center">* Paket dapat diubah kapan saja setelah akun aktif</p>
                    </div>
                </div>

                {{-- Simulasi Penghasilan --}}
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl border border-green-200 p-5">
                    <h3 class="font-bold text-gray-900 text-sm mb-3">💡 Simulasi Penghasilan</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">Harga sewa/hari</span>
                            <span class="font-semibold text-gray-900">Rp 350.000</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">Booking/bulan (est.)</span>
                            <span class="font-semibold text-gray-900">15 booking × 2 hari</span>
                        </div>
                        <div class="border-t border-green-200 pt-2 mt-2 space-y-1">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">Paket Free (komisi 12%)</span>
                                <span class="font-bold text-gray-700">Rp 9.240.000</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">Paket Basic (komisi 9%)</span>
                                <span class="font-bold text-blue-700">Rp 9.555.000</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">Paket Premium (komisi 5%)</span>
                                <span class="font-bold text-green-700">Rp 9.975.000</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">*Estimasi untuk 1 unit mobil, sudah dipotong komisi & biaya paket</p>
                    </div>
                </div>

                {{-- Proses Setelah Daftar --}}
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <h3 class="font-semibold text-gray-900 text-sm mb-3">Proses Setelah Daftar</h3>
                    <div class="space-y-3">
                        @foreach([
                            ['step' => '1', 'text' => 'Buat akun (sekarang)', 'color' => 'bg-blue-100 text-blue-700'],
                            ['step' => '2', 'text' => 'Upload dokumen verifikasi', 'color' => 'bg-blue-100 text-blue-700'],
                            ['step' => '3', 'text' => 'Review admin (≤24 jam kerja)', 'color' => 'bg-blue-100 text-blue-700'],
                            ['step' => '4', 'text' => 'Akun aktif, tambah armada & mulai terima booking', 'color' => 'bg-green-100 text-green-700'],
                        ] as $step)
                            <div class="flex items-start space-x-3">
                                <div class="w-6 h-6 {{ $step['color'] }} rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">
                                    {{ $step['step'] }}
                                </div>
                                <p class="text-sm text-gray-700">{{ $step['text'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Social proof --}}
                <div class="bg-green-50 rounded-2xl border border-green-100 p-4">
                    <p class="text-green-800 text-sm font-semibold mb-1">✅ 250+ vendor aktif bergabung</p>
                    <p class="text-green-700 text-xs">Rata-rata vendor mendapat 15 booking/bulan di bulan pertama.</p>
                </div>

            </div>

        </div>
    </div>
</div>
@endsection
