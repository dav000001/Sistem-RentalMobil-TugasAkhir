<footer class="bg-gray-800 text-white mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-xl font-bold mb-4">🚗 Rental Mobil</h3>
                <p class="text-gray-400">Platform rental mobil terpercaya di Indonesia</p>
            </div>
            <div>
                <h4 class="font-semibold mb-4">Perusahaan</h4>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="{{ route('about') }}" class="hover:text-white">Tentang Kami</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-white">Hubungi Kami</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-4">Bantuan</h4>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="{{ route('faq') }}" class="hover:text-white">FAQ</a></li>
                    <li><a href="{{ route('terms') }}" class="hover:text-white">Syarat & Ketentuan</a></li>
                </ul>
            </div>
        <div>
                <h4 class="font-semibold mb-4">Jadi Vendor</h4>
                <p class="text-gray-400 mb-4">Daftarkan mobil Anda dan dapatkan penghasilan tambahan</p>
                @auth
                    @if(auth()->user()->vendor && auth()->user()->vendor->isApproved())
                        <a href="/vendor" class="bg-blue-600 text-white px-4 py-2 rounded-lg inline-block hover:bg-blue-700">Dashboard Vendor</a>
                    @elseif(auth()->user()->vendor)
                        <p class="text-sm text-gray-400 mb-2">Status: <span class="text-yellow-400 font-medium">{{ auth()->user()->vendor->status->label() }}</span></p>
                        <a href="{{ route('vendor.onboarding') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg inline-block hover:bg-gray-500 text-sm">Lihat Status Pendaftaran</a>
                    @else
                        <a href="{{ route('vendor.landing') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg inline-block hover:bg-blue-700">Upgrade ke Vendor</a>
                    @endif
                @else
                    <a href="{{ route('vendor.landing') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg inline-block hover:bg-blue-700">Daftar Sekarang</a>
                @endauth
            </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; {{ date('Y') }} Rental Mobil. All rights reserved.</p>
        </div>
    </div>
</footer>
