@auth
@php
    try {
        $switcher = app(\App\Services\Auth\MultiAccountSession::class);
        $accounts = $switcher->listAccounts(request());
        $activeId = $switcher->activeAccountId(request());
    } catch (\Throwable) {
        $accounts = [];
        $activeId = null;
    }
    $accountCount = count($accounts);
    $currentUser = auth()->user();
    // Selalu tampilkan nama asli user, bukan nama bisnis vendor
    $displayName = $currentUser->name;
    $initial = strtoupper(substr($displayName, 0, 1));

    // Tentukan role dan status
    // Jika punya vendor profile, tentukan status vendor dulu
    $statusValue = null;
    if ($currentUser->vendor) {
        $vendorStatus = $currentUser->vendor->status;
        $statusValue  = $vendorStatus instanceof \App\Enums\VendorStatus ? $vendorStatus->value : $vendorStatus;
    }

    // Tampilkan mode vendor HANYA jika sedang di area /vendor (sama dengan navbar)
    $isOnVendorArea = request()->is('vendor*');

    if ($currentUser->vendor && $isOnVendorArea) {
        // Di area vendor — tampilkan status vendor
        $roleLabel = match($statusValue) {
            'approved'       => 'Vendor · Aktif',
            'pending'        => 'Vendor · Menunggu Verifikasi',
            'needs_revision' => 'Vendor · Perlu Revisi',
            'rejected'       => 'Vendor · Ditolak — Hubungi Admin',
            'suspended'      => 'Vendor · Dibekukan',
            default          => 'Vendor',
        };
        $roleBadgeColor = match($statusValue) {
            'approved'  => 'text-green-600',
            'rejected', 'suspended' => 'text-red-600',
            default     => 'text-yellow-600',
        };
    } elseif ($currentUser->customer) {
        // Di area customer (atau punya dua role, sedang di halaman customer)
        $roleLabel = 'Customer';
        $roleBadgeColor = 'text-blue-600';
    } elseif ($currentUser->vendor) {
        // Punya vendor profile tapi tidak ada customer, dan di luar area /vendor
        $roleLabel = 'Vendor';
        $roleBadgeColor = 'text-green-600';
    } else {
        $roleLabel = 'Admin';
        $roleBadgeColor = 'text-purple-600';
    }
@endphp

<div class="relative" x-data="{ open: false }">

    {{-- Avatar + Nama Button --}}
    <button @click="open = !open" @click.outside="open = false"
            class="flex items-center space-x-2 focus:outline-none group">
        {{-- Avatar --}}
        <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm relative flex-shrink-0">
            {{ $initial }}
            @if($accountCount > 1)
                <span class="absolute -top-1 -right-1 bg-orange-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center leading-none">
                    {{ $accountCount }}
                </span>
            @endif
        </div>
        {{-- Nama --}}
        <div class="hidden md:flex flex-col items-start">
            <span class="text-sm font-medium text-gray-800 leading-tight max-w-[120px] truncate">{{ $displayName }}</span>
            <span class="text-xs leading-tight {{ $roleBadgeColor }}">{{ $roleLabel }}</span>
        </div>
        {{-- Chevron --}}
        <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-transform flex-shrink-0"
             :class="open ? 'rotate-180' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-200 z-50 overflow-hidden"
         style="display: none;">

        {{-- Current User Header --}}
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                    {{ $initial }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $displayName }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ $currentUser->email }}</p>
                    <span class="text-xs font-medium {{ $roleBadgeColor }}">{{ $roleLabel }}</span>
                </div>
            </div>
        </div>

        {{-- Banner untuk vendor bermasalah --}}
        @if($currentUser->vendor && in_array($statusValue ?? '', ['rejected', 'suspended', 'pending', 'needs_revision']))
            <div class="px-4 py-2 border-b border-gray-100
                {{ ($statusValue === 'rejected' || $statusValue === 'suspended') ? 'bg-red-50' : 'bg-yellow-50' }}">
                @if($statusValue === 'rejected')
                    <p class="text-xs text-red-700 font-medium">❌ Pendaftaran vendor Anda ditolak.</p>
                    <a href="{{ route('vendor.onboarding') }}" class="text-xs text-red-600 underline">Lihat detail & hubungi admin →</a>
                @elseif($statusValue === 'suspended')
                    <p class="text-xs text-red-700 font-medium">🚫 Akun vendor Anda dibekukan.</p>
                    <a href="{{ route('vendor.onboarding') }}" class="text-xs text-red-600 underline">Lihat detail →</a>
                @elseif($statusValue === 'needs_revision')
                    <p class="text-xs text-yellow-700 font-medium">⚠️ Dokumen perlu diperbaiki.</p>
                    <a href="{{ route('vendor.onboarding') }}" class="text-xs text-yellow-600 underline">Perbaiki sekarang →</a>
                @elseif($statusValue === 'pending')
                    <p class="text-xs text-yellow-700 font-medium">⏳ Menunggu verifikasi admin.</p>
                    <a href="{{ route('vendor.onboarding') }}" class="text-xs text-yellow-600 underline">Lihat status →</a>
                @endif
            </div>
        @endif

        {{-- Other Accounts --}}
        @if($accountCount > 1)
            <div class="p-2 border-b border-gray-100">
                <p class="text-xs text-gray-400 px-2 py-1 font-medium uppercase tracking-wide">Akun Lain</p>
                @foreach($accounts as $account)
                    @if($account['id'] !== $activeId)
                        <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50">
                            <div class="flex items-center space-x-3 flex-1 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-gray-400 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($account['label'], 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $account['label'] }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ $account['email'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-1 flex-shrink-0 ml-2">
                                {{-- Switch --}}
                                <form method="POST" action="{{ route('auth.switch', $account['id']) }}">
                                    @csrf
                                    <button type="submit" title="Pindah ke akun ini"
                                            class="text-blue-600 hover:text-blue-800 p-1.5 rounded-lg hover:bg-blue-50 text-xs font-medium">
                                        Pindah
                                    </button>
                                </form>
                                {{-- Forget --}}
                                <form method="POST" action="{{ route('auth.forget', $account['id']) }}">
                                    @csrf
                                    <button type="submit" title="Hapus dari daftar"
                                            class="text-gray-300 hover:text-red-400 p-1 rounded hover:bg-red-50"
                                            onclick="return confirm('Hapus akun ini dari daftar?')">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif

        {{-- Actions --}}
        <div class="p-2 space-y-0.5">
            {{-- Profil --}}
            <a href="{{ route('profile.edit') }}"
               class="flex items-center space-x-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg w-full">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>Profil Saya</span>
            </a>

            {{-- Pesanan --}}
            <a href="{{ route('bookings.index') }}"
               class="flex items-center space-x-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg w-full">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Pesanan Saya</span>
            </a>

            {{-- Pesan Saya (balasan admin) --}}
            @if($currentUser->customer)
            @php
                $unreadMessages = \App\Models\ContactMessage::where('user_id', $currentUser->id)
                    ->whereNotNull('admin_reply')
                    ->where('is_read_by_customer', false)
                    ->count();
            @endphp
            <a href="{{ route('contact.messages') }}"
               class="flex items-center space-x-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg w-full">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span>Pesan Saya</span>
                @if($unreadMessages > 0)
                    <span class="ml-auto bg-blue-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold">
                        {{ $unreadMessages > 9 ? '9+' : $unreadMessages }}
                    </span>
                @endif
            </a>
            @endif

            {{-- Laporan Masalah --}}
            @if($currentUser->customer)
            <a href="{{ route('complaints.index') }}"
               class="flex items-center space-x-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg w-full">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Laporan Masalah</span>
                @php
                    $openComplaintsCount = \App\Models\Complaint::where('reporter_id', $currentUser->id)
                        ->whereNotIn('status', ['resolved', 'rejected'])
                        ->count();
                @endphp
                @if($openComplaintsCount > 0)
                    <span class="ml-auto bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold">
                        {{ $openComplaintsCount > 9 ? '9+' : $openComplaintsCount }}
                    </span>
                @endif
            </a>
            @endif

            {{-- Tambah Akun — hanya tampil untuk vendor/admin, bukan customer --}}
            @if($accountCount < \App\Services\Auth\MultiAccountSession::MAX_ACCOUNTS && !$currentUser->customer)
                <a href="{{ route('auth.add-account') }}"
                   class="flex items-center space-x-3 px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-lg w-full">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    <span>Tambah Akun Lain</span>
                </a>
            @endif

            <div class="border-t border-gray-100 my-1"></div>

            {{-- Logout current --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex items-center space-x-3 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg w-full text-left">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Keluar</span>
                </button>
            </form>

            {{-- Logout all --}}
            @if($accountCount > 1)
                <form method="POST" action="{{ route('auth.logout-all') }}">
                    @csrf
                    <button type="submit"
                            class="flex items-center space-x-3 px-3 py-2 text-sm text-red-500 hover:bg-red-50 rounded-lg w-full text-left"
                            onclick="return confirm('Keluar dari semua akun?')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Keluar dari Semua Akun</span>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endauth
