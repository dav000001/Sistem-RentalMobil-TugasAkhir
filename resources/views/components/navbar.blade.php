@php
    // Tampilkan mode vendor HANYA jika user sedang berada di area /vendor
    // Ini mencegah navbar vendor muncul saat user buka halaman customer (home, search, dll)
    $hasVendorProfile = auth()->check() && auth()->user()->vendor !== null;
    $isOnVendorArea   = request()->is('vendor*');
    $isVendorUser     = $hasVendorProfile && $isOnVendorArea;
@endphp
<nav class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- ── Kiri: Logo + Menu ── --}}
            <div class="flex items-center">
                <a href="{{ $isVendorUser ? '/vendor' : route('home') }}" class="text-2xl font-bold text-blue-600">
                    🚗 Rental Mobil
                </a>
                <div class="hidden md:ml-10 md:flex md:space-x-8">
                    @if($isVendorUser)
                        <a href="/vendor"          class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm">Dashboard</a>
                        <a href="/vendor/cars"     class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm">Armada Saya</a>
                        <a href="/vendor/bookings" class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm">Pemesanan</a>
                    @else
                        <a href="{{ route('home') }}"   class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm">Home</a>
                        <a href="{{ route('search') }}" class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm">Cari Mobil</a>
                    @endif
                </div>
            </div>

            {{-- ── Kanan: Aksi User ── --}}
            <div class="flex items-center space-x-3">

                @guest
                    {{-- Tamu --}}
                    <a href="{{ route('vendor.landing') }}"
                       class="hidden md:inline-flex items-center text-sm font-medium text-gray-600 hover:text-blue-600 border border-gray-300 px-3 py-1.5 rounded-lg hover:border-blue-400 transition">
                        🚗 Jadi Vendor
                    </a>
                    <a href="{{ route('login') }}"    class="text-gray-700 hover:text-blue-600 text-sm">Masuk</a>
                    <a href="{{ route('register') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">Daftar</a>

                @elseif($isVendorUser)
                    {{-- ── Vendor ── --}}
                    <a href="/vendor/billing" class="text-gray-700 hover:text-blue-600 hidden md:block text-sm">Billing</a>
                    <a href="/vendor/support" class="text-gray-700 hover:text-blue-600 hidden md:block text-sm">Support</a>
                    {{-- Link ke tampilan customer jika vendor juga punya profil customer --}}
                    @if(auth()->user()->customer)
                        <a href="{{ route('home') }}"
                           class="hidden md:inline-flex items-center text-xs font-medium text-gray-500 hover:text-blue-600 border border-gray-200 px-2.5 py-1.5 rounded-lg hover:border-blue-400 transition"
                           title="Berpindah ke mode customer">
                            👤 Customer
                        </a>
                    @endif

                    <x-account-switcher />

                @else
                    {{-- ── Customer ── --}}
                    {{-- Link ke dashboard vendor jika user juga punya profil vendor --}}
                    @if($hasVendorProfile)
                        <a href="/vendor"
                           class="hidden md:inline-flex items-center text-xs font-medium text-green-600 hover:text-green-700 border border-green-200 px-2.5 py-1.5 rounded-lg hover:border-green-400 transition"
                           title="Buka dashboard vendor">
                            🚗 Vendor
                        </a>
                    @endif
                    <a href="{{ route('bookings.index') }}" class="text-gray-700 hover:text-blue-600 hidden md:block text-sm">Pesanan</a>

                    {{-- Wishlist --}}
                    @php $wishlistCount = \App\Models\Wishlist::where('user_id', auth()->id())->count(); @endphp
                    <a href="{{ route('wishlist.index') }}"
                       class="relative p-2 text-gray-500 hover:text-red-500 hover:bg-gray-100 rounded-full transition"
                       title="Wishlist">
                        <svg class="w-5 h-5" fill="{{ $wishlistCount > 0 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        @if($wishlistCount > 0)
                            <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                                {{ $wishlistCount > 9 ? '9+' : $wishlistCount }}
                            </span>
                        @endif
                    </a>

                    {{-- Compare --}}
                    @php $compareCount = count(session('compare_ids', [])); @endphp
                    @if($compareCount > 0)
                        <a href="{{ route('compare.index') }}"
                           class="relative p-2 text-gray-500 hover:text-blue-600 hover:bg-gray-100 rounded-full transition"
                           title="Bandingkan Mobil">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-blue-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                                {{ $compareCount }}
                            </span>
                        </a>
                    @endif

                    {{-- Notifikasi --}}
                    @php
                        $unreadReplies = \App\Models\ContactMessage::where('user_id', auth()->id())
                            ->whereNotNull('admin_reply')
                            ->where('is_read_by_customer', false)
                            ->count();

                        // Notifikasi database Laravel (tagihan kompensasi, komplain, dll)
                        $unreadDbNotifs = auth()->user()->unreadNotifications()
                            ->whereIn('type', [
                                \App\Notifications\CompensationChargeNotification::class,
                                \App\Notifications\Complaint\ComplaintAdminReplied::class,
                                \App\Notifications\Complaint\ComplaintResolved::class,
                                \App\Notifications\Complaint\ComplaintRejected::class,
                                \App\Notifications\RefundProcessedNotification::class,
                            ])
                            ->count();

                        $totalUnread = $unreadReplies + $unreadDbNotifs;
                    @endphp
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="relative p-2 text-gray-500 hover:text-blue-600 hover:bg-gray-100 rounded-full transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($totalUnread > 0)
                                <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                                    {{ $totalUnread > 9 ? '9+' : $totalUnread }}
                                </span>
                            @endif
                        </button>

                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             @click.outside="open = false"
                             class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-200 z-50"
                             style="display:none; top: 100%">

                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <p class="font-semibold text-gray-900 text-sm">Notifikasi</p>
                                @if($totalUnread > 0)
                                    <span class="bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-full">
                                        {{ $totalUnread }} baru
                                    </span>
                                @endif
                            </div>

                            @php
                                // Notifikasi database terbaru
                                $recentDbNotifs = auth()->user()->notifications()
                                    ->whereIn('type', [
                                        \App\Notifications\CompensationChargeNotification::class,
                                        \App\Notifications\Complaint\ComplaintAdminReplied::class,
                                        \App\Notifications\Complaint\ComplaintResolved::class,
                                        \App\Notifications\Complaint\ComplaintRejected::class,
                                        \App\Notifications\RefundProcessedNotification::class,
                                    ])
                                    ->latest()
                                    ->take(3)
                                    ->get();

                                $recentReplies = \App\Models\ContactMessage::where('user_id', auth()->id())
                                    ->whereNotNull('admin_reply')
                                    ->latest('replied_at')
                                    ->take(3)
                                    ->get();

                                $hasAny = $recentDbNotifs->isNotEmpty() || $recentReplies->isNotEmpty();
                            @endphp

                            @if(!$hasAny)
                                <div class="px-4 py-6 text-center">
                                    <p class="text-2xl mb-1">🔔</p>
                                    <p class="text-gray-400 text-xs">Belum ada notifikasi</p>
                                </div>
                            @else
                                <div class="divide-y divide-gray-50 max-h-72 overflow-y-auto">
                                    {{-- Notifikasi database --}}
                                    @foreach($recentDbNotifs as $notif)
                                        @php
                                            $data     = $notif->data;
                                            $url      = $data['url'] ?? '#';
                                            $title    = $data['title'] ?? 'Notifikasi';
                                            $body     = $data['body'] ?? '';
                                            $isUnread = is_null($notif->read_at);

                                            $icon = match($notif->type) {
                                                \App\Notifications\CompensationChargeNotification::class     => '⚠️',
                                                \App\Notifications\Complaint\ComplaintAdminReplied::class    => '💬',
                                                \App\Notifications\Complaint\ComplaintResolved::class        => '✅',
                                                \App\Notifications\Complaint\ComplaintRejected::class        => '❌',
                                                \App\Notifications\RefundProcessedNotification::class        => '💸',
                                                default => '🔔',
                                            };
                                            $bgColor = match($notif->type) {
                                                \App\Notifications\CompensationChargeNotification::class => 'bg-orange-100',
                                                default => 'bg-blue-100',
                                            };
                                        @endphp
                                        <a href="{{ $url }}"
                                           class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition {{ $isUnread ? 'bg-yellow-50' : '' }}">
                                            <div class="w-8 h-8 {{ $bgColor }} rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                                <span class="text-sm">{{ $icon }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold text-gray-900 truncate">{{ $title }}</p>
                                                @if($body)
                                                    <p class="text-xs text-gray-500 truncate mt-0.5">{{ \Illuminate\Support\Str::limit($body, 60) }}</p>
                                                @endif
                                                <p class="text-xs text-gray-400 mt-0.5">{{ $notif->created_at->diffForHumans() }}</p>
                                            </div>
                                            @if($isUnread)
                                                <div class="w-2 h-2 bg-orange-500 rounded-full flex-shrink-0 mt-1.5"></div>
                                            @endif
                                        </a>
                                    @endforeach

                                    {{-- Balasan pesan admin --}}
                                    @foreach($recentReplies as $reply)
                                        <a href="{{ route('contact.messages') }}"
                                           class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition {{ !$reply->is_read_by_customer ? 'bg-blue-50' : '' }}">
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                                <span class="text-sm">💬</span>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold text-gray-900 truncate">
                                                    Admin membalas: {{ $reply->subject }}
                                                </p>
                                                <p class="text-xs text-gray-500 truncate mt-0.5">
                                                    {{ \Illuminate\Support\Str::limit($reply->admin_reply, 60) }}
                                                </p>
                                                <p class="text-xs text-gray-400 mt-0.5">
                                                    {{ $reply->replied_at?->diffForHumans() }}
                                                </p>
                                            </div>
                                            @if(!$reply->is_read_by_customer)
                                                <div class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-1.5"></div>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            <div class="px-4 py-2 border-t border-gray-100">
                                <a href="{{ route('contact.messages') }}"
                                   class="block text-center text-xs text-blue-600 hover:underline font-medium py-1">
                                    Lihat semua pesan →
                                </a>
                            </div>
                        </div>
                    </div>

                    <x-account-switcher />
                @endauth
            </div>

        </div>
    </div>
</nav>
