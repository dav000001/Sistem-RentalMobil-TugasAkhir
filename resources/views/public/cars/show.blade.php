@extends('layouts.app')

@section('title', $car->brand . ' ' . $car->model . ' ' . $car->year . ' - Rental Mobil')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 text-sm text-gray-600">
            <li><a href="{{ route('home') }}" class="hover:text-blue-600">Home</a></li>
            <li>/</li>
            <li><a href="{{ route('search', ['city_id' => $car->city_id]) }}" class="hover:text-blue-600">{{ $car->city->name }}</a></li>
            <li>/</li>
            <li><a href="{{ route('search', ['category_id' => $car->category_id]) }}" class="hover:text-blue-600">{{ $car->category->name }}</a></li>
            <li>/</li>
            <li class="text-gray-900">{{ $car->brand }} {{ $car->model }}</li>
        </ol>
    </nav>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <!-- Car Images -->
            <div class="mb-8">
                <div class="bg-gray-200 rounded-lg overflow-hidden">
                    <img src="{{ $car->photos->first()?->path ?? 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=800&q=80' }}" 
                         alt="{{ $car->brand }} {{ $car->model }}" 
                         loading="lazy"
                         class="w-full h-96 object-cover">
                </div>
                @if($car->photos->count() > 1)
                    <div class="grid grid-cols-4 gap-2 mt-4">
                        @foreach($car->photos->take(4) as $photo)
                            <img src="{{ $photo->path }}" 
                                 alt="Photo {{ $loop->iteration }}" 
                                 loading="lazy"
                                 class="w-full h-20 object-cover rounded cursor-pointer hover:opacity-75">
                        @endforeach
                    </div>
                @endif
            </div>
            
            <!-- Car Info -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">{{ $car->brand }} {{ $car->model }} {{ $car->year }}</h1>
                        <p class="text-gray-600">Plat: {{ substr($car->plate_number, 0, -3) }}***</p>
                    </div>
                    <div class="text-right">
                        @php $activeBooking = $car->activeBooking(); @endphp
                        @if($car->status === 'unavailable')
                            <span class="inline-block bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm font-semibold mb-2">
                                {{ match($car->unavailability_reason) {
                                    'service'    => '🔧 Sedang Service',
                                    'rusak'      => '⚠️ Sedang Rusak',
                                    'kecelakaan' => '🚨 Kecelakaan',
                                    default      => '⛔ Tidak Tersedia',
                                } }}
                            </span>
                            @if($car->unavailable_until)
                                <p class="text-xs text-gray-500">Estimasi tersedia: {{ $car->unavailable_until->format('d M Y') }}</p>
                            @endif
                        @elseif($dateAvailability === true)
                            {{-- Ada filter tanggal dan tersedia --}}
                            <span class="inline-block bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-semibold mb-2">
                                ✅ Tersedia untuk Tanggal Ini
                            </span>
                        @elseif($dateAvailability === false)
                            {{-- Ada filter tanggal tapi tidak tersedia --}}
                            <span class="inline-block bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm font-semibold mb-2">
                                🚫 Tidak Tersedia
                            </span>
                            @if($activeBooking)
                                <p class="text-xs text-gray-500">Tersedia: {{ $activeBooking->end_at->format('d M Y') }}</p>
                            @endif
                        @elseif($activeBooking)
                            {{-- Tidak ada filter tanggal, ada booking aktif — info saja --}}
                            <span class="inline-block bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-sm font-semibold mb-2">
                                🕐 Ada Pemesanan Aktif
                            </span>
                            <p class="text-xs text-gray-500">Sampai: {{ $activeBooking->end_at->format('d M Y') }}</p>
                            <p class="text-xs text-blue-500 mt-0.5">Pilih tanggal lain untuk cek ketersediaan</p>
                        @else
                            <span class="inline-block bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-semibold mb-2">
                                ✓ Tersedia Sekarang
                            </span>
                        @endif
                        @if($car->reviews_avg_rating)
                            <div class="flex items-center justify-end mt-1">
                                <span class="text-2xl">⭐</span>
                                <span class="text-xl font-semibold ml-1">{{ number_format($car->reviews_avg_rating, 1) }}</span>
                                <span class="text-sm text-gray-500 ml-1">({{ $car->reviews_count }} ulasan)</span>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Specifications -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="text-center p-3 bg-gray-50 rounded">
                        <div class="text-2xl mb-1">⚙️</div>
                        <p class="text-sm text-gray-600">Transmisi</p>
                        <p class="font-semibold">{{ ucfirst($car->transmission) }}</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded">
                        <div class="text-2xl mb-1">⛽</div>
                        <p class="text-sm text-gray-600">Bahan Bakar</p>
                        <p class="font-semibold">{{ ucfirst($car->fuel) }}</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded">
                        <div class="text-2xl mb-1">👥</div>
                        <p class="text-sm text-gray-600">Kapasitas</p>
                        <p class="font-semibold">{{ $car->seats }} kursi</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded">
                        <div class="text-2xl mb-1">🧳</div>
                        <p class="text-sm text-gray-600">Bagasi</p>
                        <p class="font-semibold">{{ $car->luggage }} koper</p>
                    </div>
                </div>
                
                <!-- Features -->
                @if($car->features)
                    <div class="mb-6">
                        <h3 class="font-semibold mb-3">Fitur</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($car->features as $feature)
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">{{ $feature }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                <!-- Description -->
                @if($car->description)
                    <div>
                        <h3 class="font-semibold mb-3">Deskripsi</h3>
                        <p class="text-gray-700">{{ $car->description }}</p>
                    </div>
                @endif
            </div>
            
            <!-- Vendor Info -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="font-semibold text-lg mb-4">Informasi Vendor</h3>
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-xl flex-shrink-0">
                            {{ substr($car->vendor->business_name, 0, 1) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="font-semibold">{{ $car->vendor->business_name }}</h4>
                                @php
                                    $vendorSub = $car->vendor->currentSubscription;
                                    $hasBadge  = $vendorSub && ($vendorSub->snapshot_features['badge_verified'] ?? false);
                                @endphp
                                @if($hasBadge)
                                    <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">✓ Vendor Terverifikasi</span>
                                @endif
                            </div>
                            <p class="text-gray-600 text-sm">📍 {{ $car->vendor->city->name }}</p>
                            @if($car->vendor->user->phone)
                                <p class="text-gray-600 text-sm mt-0.5">📱 {{ $car->vendor->user->phone }}</p>
                            @endif
                        </div>
                    </div>
                    @if($car->vendor->user->phone)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $car->vendor->user->phone) }}"
                           target="_blank"
                           class="flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            Chat WhatsApp
                        </a>
                    @endif
                </div>
            </div>
            
            <!-- Reviews -->
            @if($reviews->total() > 0)
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-lg">Ulasan</h3>
                        <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                            {{ $reviews->total() }} ulasan
                        </span>
                    </div>
                    @foreach($reviews as $review)
                        <div class="border-b border-gray-200 pb-4 mb-4 last:border-b-0 last:mb-0">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h5 class="font-semibold">{{ $review->customer->user->name }}</h5>
                                    <div class="flex items-center gap-0.5">
                                        @for($i = 1; $i <= 5; $i++)
                                            <span class="{{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                                        @endfor
                                        <span class="text-sm text-gray-500 ml-1">{{ $review->rating }}/5</span>
                                    </div>
                                </div>
                                <span class="text-sm text-gray-500">{{ $review->created_at->diffForHumans() }}</span>
                            </div>
                            @if($review->comment)
                                <p class="text-gray-700 text-sm">{{ $review->comment }}</p>
                            @endif
                        </div>
                    @endforeach

                    @if($reviews->hasPages())
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            {{ $reviews->links() }}
                        </div>
                    @endif
                </div>
            @else
                <div class="bg-white rounded-lg shadow-md p-6 text-center text-gray-500">
                    <p class="text-3xl mb-2">⭐</p>
                    <p class="text-sm">Belum ada ulasan untuk mobil ini.</p>
                </div>
            @endif
        </div>
        
        <!-- Booking Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                <h3 class="font-semibold text-lg mb-4">Pesan Mobil Ini</h3>
                
                <!-- Price -->
                <div class="mb-6">
                    <div class="text-3xl font-bold text-blue-600 mb-1">{{ formatRupiah($car->pricing->daily_price) }}</div>
                    <div class="text-gray-600">per hari</div>
                    @if($car->is_monthly_available && $car->pricing->monthly_price)
                        <div class="text-sm text-green-600 font-medium mt-1">
                            📅 {{ formatRupiah($car->pricing->monthly_price) }}/bulan
                        </div>
                    @endif
                    @if(in_array($car->rental_option, ['with_driver_only', 'both']) && $car->pricing->with_driver_price)
                        <div class="text-sm text-gray-500 mt-1">
                            + sopir {{ formatRupiah($car->pricing->with_driver_price) }}/hari
                        </div>
                    @endif
                    <!-- Opsi Sewa Badge -->
                    <div class="mt-2">
                        @if($car->rental_option === 'self_drive_only')
                            <span class="inline-block bg-blue-100 text-blue-700 text-xs font-semibold px-2 py-1 rounded-full">🔑 Lepas Kunci</span>
                        @elseif($car->rental_option === 'with_driver_only')
                            <span class="inline-block bg-purple-100 text-purple-700 text-xs font-semibold px-2 py-1 rounded-full">👨‍✈️ Dengan Sopir</span>
                        @else
                            <span class="inline-block bg-blue-100 text-blue-700 text-xs font-semibold px-2 py-1 rounded-full mr-1">🔑 Lepas Kunci</span>
                            <span class="inline-block bg-purple-100 text-purple-700 text-xs font-semibold px-2 py-1 rounded-full">👨‍✈️ Dengan Sopir</span>
                        @endif
                    </div>
                </div>
                
                <!-- Quick Booking Form -->
                @php $activeBooking = $car->activeBooking(); @endphp
                @auth
                    @if($car->status === 'unavailable')
                        {{-- Mobil tidak tersedia (service/rusak/dll) --}}
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <p class="text-red-700 font-semibold mb-1">
                                {{ match($car->unavailability_reason) {
                                    'service'    => '🔧 Mobil Sedang Service',
                                    'rusak'      => '⚠️ Mobil Sedang Rusak',
                                    'kecelakaan' => '🚨 Mobil Mengalami Kecelakaan',
                                    default      => '⛔ Mobil Tidak Tersedia',
                                } }}
                            </p>
                            @if($car->unavailability_notes)
                                <p class="text-red-600 text-sm mt-1">{{ $car->unavailability_notes }}</p>
                            @endif
                            @if($car->unavailable_until)
                                <p class="text-red-600 text-sm mt-1">📅 Estimasi tersedia kembali: <strong>{{ $car->unavailable_until->format('d M Y') }}</strong></p>
                            @endif
                        </div>
                        <button disabled class="w-full bg-gray-300 text-gray-500 py-3 px-4 rounded-lg cursor-not-allowed font-semibold">
                            Tidak Tersedia Saat Ini
                        </button>

                    @elseif($dateAvailability === false)
                        {{-- Ada filter tanggal dan mobil tidak tersedia di tanggal tersebut --}}
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <p class="text-red-700 font-semibold mb-1">🚫 Mobil Tidak Tersedia</p>
                            <p class="text-red-600 text-sm">
                                Mobil sudah dipesan pada tanggal yang Anda pilih.
                            </p>
                            @if($activeBooking)
                                <p class="text-red-600 text-sm mt-1">
                                    📅 Tersedia kembali: <strong>{{ $activeBooking->end_at->format('d M Y H:i') }}</strong>
                                </p>
                            @endif
                        </div>
                        <button disabled class="w-full bg-gray-300 text-gray-500 py-3 px-4 rounded-lg cursor-not-allowed font-semibold">
                            Tidak Tersedia untuk Tanggal Ini
                        </button>

                    @elseif(!auth()->user()->customer)
                        {{-- User bukan customer (misal vendor) --}}
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                            <p class="text-gray-700 text-sm">Akun Anda terdaftar sebagai vendor. Gunakan akun customer untuk memesan.</p>
                        </div>
                    @elseif(auth()->user()->customer->verification_status === 'pending')
                        {{-- Sedang menunggu verifikasi --}}
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                            <p class="text-yellow-800 font-semibold text-sm mb-1">⏳ Verifikasi Sedang Diproses</p>
                            <p class="text-yellow-700 text-sm">Dokumen identitas Anda sedang ditinjau oleh admin. Biasanya selesai dalam 1×24 jam.</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="w-full block text-center bg-yellow-500 text-white py-3 px-4 rounded-lg hover:bg-yellow-600 font-semibold">
                            Cek Status Verifikasi
                        </a>
                    @elseif(auth()->user()->customer->verification_status === 'rejected')
                        {{-- Verifikasi ditolak --}}
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <p class="text-red-800 font-semibold text-sm mb-1">❌ Verifikasi Ditolak</p>
                            @if(auth()->user()->customer->rejection_reason)
                                <p class="text-red-700 text-sm mb-2"><strong>Alasan:</strong> {{ auth()->user()->customer->rejection_reason }}</p>
                            @endif
                            <p class="text-red-600 text-sm">Silakan upload ulang dokumen yang sesuai.</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="w-full block text-center bg-red-600 text-white py-3 px-4 rounded-lg hover:bg-red-700 font-semibold">
                            Upload Ulang Dokumen
                        </a>
                    @elseif(auth()->user()->customer->hasUnpaidLateFee())
                        {{-- Memiliki Denda Belum Lunas --}}
                        @php
                            $unpaidCharge = auth()->user()->customer->getUnpaidLateFeeCharge();
                        @endphp
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <p class="text-red-800 font-semibold text-sm mb-1">⚠️ Memiliki Denda Keterlambatan Belum Lunas</p>
                            <p class="text-red-700 text-sm mb-3">
                                Anda tidak dapat memesan mobil baru karena masih memiliki denda keterlambatan yang belum dilunasi.
                            </p>
                            @if($unpaidCharge && $unpaidCharge->booking)
                                <a href="{{ route('bookings.show', $unpaidCharge->booking->code) }}" class="w-full block text-center bg-red-600 text-white py-2.5 px-4 rounded-lg hover:bg-red-700 font-semibold text-sm">
                                    💸 Lihat & Lunasi Denda (Rp {{ number_format($unpaidCharge->amount, 0, ',', '.') }})
                                </a>
                            @else
                                <a href="{{ route('bookings.index') }}" class="w-full block text-center bg-red-600 text-white py-2.5 px-4 rounded-lg hover:bg-red-700 font-semibold text-sm">
                                    📋 Lihat Pesanan Saya
                                </a>
                            @endif
                        </div>
                    @elseif(auth()->user()->customer->verification_status === 'verified')
                        {{-- Sudah terverifikasi — tampilkan form booking --}}

                        {{-- Info jika ada booking aktif tapi tanggal berbeda (atau tidak ada filter tanggal) --}}
                        @if($activeBooking && $dateAvailability === null)
                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-4">
                                <p class="text-orange-700 text-sm font-medium">📅 Sudah ada yang menyewa</p>
                                <p class="text-orange-600 text-xs mt-0.5">
                                    Dipesan: {{ $activeBooking->start_at->format('d M Y') }} – {{ $activeBooking->end_at->format('d M Y') }}
                                </p>
                                <p class="text-orange-600 text-xs mt-0.5">
                                    Anda tetap bisa pesan di tanggal lain. Pilih tanggal di bawah.
                                </p>
                            </div>
                        @elseif($dateAvailability === true)
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                                <p class="text-green-700 text-sm font-medium">✅ Tersedia untuk tanggal yang dipilih</p>
                            </div>
                        @endif

                        <form action="{{ route('bookings.create', $car->slug) }}" method="GET">
                            <div class="space-y-4 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                                    <input type="date" name="start_date"
                                           id="quick_start_date"
                                           value="{{ $defaultPeriod['start_at']->format('Y-m-d') }}"
                                           min="{{ now()->addDay()->format('Y-m-d') }}"
                                           style="color:#111827;"
                                           onchange="updateCarAvailability()"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                                    <input type="date" name="end_date"
                                           id="quick_end_date"
                                           value="{{ $defaultPeriod['end_at']->format('Y-m-d') }}"
                                           min="{{ now()->addDays(2)->format('Y-m-d') }}"
                                           style="color:#111827;"
                                           onchange="updateCarAvailability()"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                </div>

                                @if($car->rental_option === 'with_driver_only')
                                    {{-- Wajib sopir, tampilkan info saja --}}
                                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
                                        <p class="text-sm text-purple-700 font-medium">👨‍✈️ Mobil ini hanya tersedia dengan sopir</p>
                                        @if($car->pricing->with_driver_price)
                                            <p class="text-xs text-purple-600 mt-1">+ {{ formatRupiah($car->pricing->with_driver_price) }}/hari</p>
                                        @endif
                                        {{-- Tampilkan daftar sopir aktif milik vendor --}}
                                        @php
                                            $activeDrivers = $car->vendor->drivers()->where('status','active')->get();
                                        @endphp
                                        @if($activeDrivers->isNotEmpty())
                                            <div class="mt-2 pt-2 border-t border-purple-200">
                                                <p class="text-xs text-purple-600 font-semibold mb-1">Sopir Tersedia:</p>
                                                @foreach($activeDrivers as $d)
                                                    <div class="flex items-center gap-2 text-xs text-purple-700 mt-1">
                                                        <span>🧑‍✈️</span>
                                                        <span><strong>{{ $d->name }}</strong>
                                                            @if($d->experience_years > 0) · {{ $d->experience_years }} thn @endif
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <input type="hidden" name="with_driver" value="1">

                                    {{-- Dropdown Pilih Sopir --}}
                                    @if($activeDrivers->isNotEmpty())
                                    <div class="mt-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Pilih Sopir <span class="text-red-500">*</span></label>
                                        <select name="driver_id" required
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
                                            <option value="">— Pilih Sopir —</option>
                                            @foreach($activeDrivers as $d)
                                                <option value="{{ $d->id }}">
                                                    🧑‍✈️ {{ $d->name }}{{ $d->experience_years > 0 ? ' — '.$d->experience_years.' thn' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="text-xs text-gray-400 mt-1">Ketersediaan sopir akan divalidasi saat konfirmasi booking.</p>
                                    </div>
                                    @endif

                                @elseif($car->rental_option === 'both')
                                    {{-- Customer bisa pilih --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Opsi Sewa</label>
                                        <div class="space-y-2">
                                            <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-blue-400 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                                                <input type="radio" name="with_driver" value="0" checked class="mr-2 text-blue-600">
                                                <span class="text-sm">🔑 Lepas kunci (tanpa sopir)</span>
                                            </label>
                                            <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-purple-400 has-[:checked]:border-purple-500 has-[:checked]:bg-purple-50">
                                                <input type="radio" name="with_driver" value="1" class="mr-2 text-purple-600">
                                                <span class="text-sm">👨‍✈️ Dengan sopir
                                                    @if($car->pricing->with_driver_price)
                                                        <span class="text-gray-500">(+ {{ formatRupiah($car->pricing->with_driver_price) }}/hari)</span>
                                                    @endif
                                                </span>
                                            </label>
                                        </div>
                                        {{-- Dropdown sopir — muncul jika pilih "dengan sopir" --}}
                                        @php $activeDrivers = $car->vendor->drivers()->where('status','active')->get(); @endphp
                                        @if($activeDrivers->isNotEmpty())
                                        <div id="driver-select-section" class="hidden mt-2">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Pilih Sopir <span class="text-red-500">*</span></label>
                                            <select id="driver_id_show" name="driver_id"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
                                                <option value="">— Pilih Sopir —</option>
                                                @foreach($activeDrivers as $d)
                                                    <option value="{{ $d->id }}">
                                                        🧑‍✈️ {{ $d->name }}{{ $d->experience_years > 0 ? ' — '.$d->experience_years.' thn' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <p class="text-xs text-gray-400 mt-1">Ketersediaan sopir akan divalidasi saat konfirmasi booking.</p>
                                        </div>
                                        @endif
                                    </div>

                                    <script>
                                    document.querySelectorAll('input[name="with_driver"]').forEach(function(radio) {
                                        radio.addEventListener('change', function() {
                                            var section = document.getElementById('driver-select-section');
                                            var select  = document.getElementById('driver_id_show');
                                            if (!section) return;
                                            if (this.value === '1') {
                                                section.classList.remove('hidden');
                                                if (select) select.setAttribute('required', 'required');
                                            } else {
                                                section.classList.add('hidden');
                                                if (select) {
                                                    select.removeAttribute('required');
                                                    select.value = '';
                                                }
                                            }
                                        });
                                    });
                                    </script>

                                @else
                                    {{-- self_drive_only — tidak ada pilihan sopir --}}
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                        <p class="text-sm text-blue-700 font-medium">🔑 Lepas kunci (tanpa sopir)</p>
                                    </div>
                                    <input type="hidden" name="with_driver" value="0">
                                @endif
                            </div>
                            <button type="submit" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-semibold">
                                Pesan Sekarang
                            </button>
                        </form>
                    @else
                        {{-- Belum upload dokumen sama sekali --}}
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <p class="text-blue-800 font-semibold text-sm mb-1">📋 Verifikasi Identitas Diperlukan</p>
                            <p class="text-blue-700 text-sm">Upload KTP, SIM, dan selfie untuk mulai memesan mobil. Proses verifikasi 1×24 jam.</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="w-full block text-center bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 font-semibold">
                            Verifikasi Sekarang
                        </a>
                    @endif
                @else
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                        <p class="text-gray-700 text-sm">Silakan login untuk memesan mobil ini.</p>
                    </div>
                    <a href="{{ route('login') }}" class="w-full block text-center bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 font-semibold">
                        Login untuk Memesan
                    </a>
                    <a href="{{ route('register') }}" class="w-full block text-center mt-2 border border-blue-600 text-blue-600 py-3 px-4 rounded-lg hover:bg-blue-50 font-semibold">
                        Daftar Sekarang
                    </a>
                @endauth
                
                <div class="mt-4 text-center">
                    <p class="text-xs text-gray-500">Pembayaran aman dengan berbagai metode</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Cars -->
    @if($relatedCars->count() > 0)
        <div class="mt-16">
            <h2 class="text-2xl font-bold mb-8">Mobil Serupa di {{ $car->city->name }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedCars as $relatedCar)
                    <x-car-card :car="$relatedCar" />
                @endforeach
            </div>
        </div>
    @endif
</div>

<script>
// Reload halaman dengan parameter tanggal saat input berubah
// Ini memicu CarController::show() untuk cek availability via $dateAvailability
function updateCarAvailability() {
    const startEl = document.getElementById('quick_start_date');
    const endEl   = document.getElementById('quick_end_date');
    if (!startEl || !endEl) return;

    const start = startEl.value;
    const end   = endEl.value;
    if (!start || !end) return;

    // Pastikan end > start
    if (new Date(end) <= new Date(start)) return;

    // Update URL dengan parameter tanggal dan reload
    const url = new URL(window.location.href);
    url.searchParams.set('start_at', start);
    url.searchParams.set('end_at', end);

    // Gunakan Livewire-style debounce agar tidak reload terlalu cepat
    clearTimeout(window._availTimeout);
    window._availTimeout = setTimeout(() => {
        window.location.href = url.toString();
    }, 800);
}

// Sinkronisasi nilai dari URL ke input saat halaman dimuat
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const startEl = document.getElementById('quick_start_date');
    const endEl   = document.getElementById('quick_end_date');

    if (startEl && params.get('start_at')) {
        startEl.value = params.get('start_at');
    }
    if (endEl && params.get('end_at')) {
        endEl.value = params.get('end_at');
    }
});
</script>
@endsection