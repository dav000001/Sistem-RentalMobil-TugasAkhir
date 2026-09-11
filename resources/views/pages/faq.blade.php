@extends('layouts.app')
@section('title', 'FAQ - Rental Mobil')
@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <h1 class="text-4xl font-bold text-gray-900 mb-4">Pertanyaan yang Sering Diajukan</h1>
    <p class="text-gray-500 mb-12">Temukan jawaban atas pertanyaan umum seputar layanan kami</p>

    @php
    $faqs = [
        'Untuk Penyewa' => [
            ['q' => 'Bagaimana cara menyewa mobil?', 'a' => 'Cari mobil sesuai kota dan tanggal, klik "Pesan Sekarang", isi detail pemesanan, lalu selesaikan pembayaran. Vendor akan mengkonfirmasi dalam beberapa jam.'],
            ['q' => 'Dokumen apa yang diperlukan untuk menyewa?', 'a' => 'Anda perlu upload KTP dan SIM A yang masih berlaku saat verifikasi akun. Proses verifikasi dilakukan sekali dan berlaku untuk semua pemesanan berikutnya.'],
            ['q' => 'Apakah bisa menyewa dengan sopir?', 'a' => 'Ya, beberapa vendor menyediakan opsi dengan sopir. Pilih opsi "Dengan Sopir" saat pemesanan. Biaya sopir akan ditampilkan secara transparan.'],
            ['q' => 'Bagaimana jika saya ingin membatalkan pesanan?', 'a' => 'Pembatalan bisa dilakukan selama status masih "Menunggu Pembayaran" atau "Menunggu Konfirmasi Vendor". Kebijakan refund mengikuti ketentuan masing-masing vendor.'],
            ['q' => 'Bagaimana jika jumlah penumpang melebihi kapasitas mobil?', 'a' => 'Jika jumlah penumpang melebihi kapasitas mobil yang dipesan, Anda dapat mengajukan permintaan Pergantian Mobil melalui halaman detail pesanan maksimal H-1 sebelum masa sewa. Anda perlu membayar selisih biaya jika mobil pengganti lebih mahal. Jika vendor tidak memiliki unit pengganti yang sesuai dan membatalkan booking, Anda akan menerima refund sebesar 50% dari total pembayaran.'],
            ['q' => 'Metode pembayaran apa yang tersedia?', 'a' => 'Kami mendukung transfer bank (BCA, BNI, BRI, Mandiri, dll), e-wallet (GoPay, OVO, Dana, ShopeePay), kartu kredit/debit, QRIS, dan pembayaran di minimarket.'],
        ],
        'Untuk Vendor' => [
            ['q' => 'Bagaimana cara mendaftar sebagai vendor?', 'a' => 'Klik "Jadi Vendor" di footer atau navbar, isi form pendaftaran, upload dokumen verifikasi (KTP, NPWP, SIUP/NIB), dan tunggu persetujuan admin dalam 1×24 jam kerja.'],
            ['q' => 'Berapa komisi yang dikenakan?', 'a' => 'Paket Free: 12% per transaksi. Paket Pro (Rp 99k/bulan): 8%. Paket Premium (Rp 299k/bulan): 5%. Komisi hanya dipotong saat ada transaksi berhasil.'],
            ['q' => 'Kapan saya menerima pembayaran?', 'a' => 'Payout dilakukan setiap minggu (setiap Senin) untuk semua transaksi yang sudah selesai di minggu sebelumnya. Dana langsung ditransfer ke rekening yang terdaftar.'],
            ['q' => 'Apakah saya bisa mengatur harga sendiri?', 'a' => 'Ya, Anda bebas menentukan harga sewa per hari, per minggu, dan per bulan. Anda juga bisa mengatur harga sopir dan biaya antar.'],
        ],
        'Keamanan & Privasi' => [
            ['q' => 'Apakah data saya aman?', 'a' => 'Ya. Semua data pribadi dienkripsi dan disimpan dengan aman. Dokumen identitas (KTP, SIM) hanya bisa diakses oleh pemilik akun dan admin yang berwenang.'],
            ['q' => 'Bagaimana jika terjadi sengketa?', 'a' => 'Kami menyediakan sistem mediasi. Ajukan sengketa melalui halaman detail pesanan, dan tim admin akan membantu menyelesaikan dalam 3×24 jam kerja.'],
        ],
    ];
    @endphp

    <div class="space-y-10" x-data="{ open: null }">
        @foreach($faqs as $category => $items)
            <div>
                <h2 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b border-gray-200">{{ $category }}</h2>
                <div class="space-y-3">
                    @foreach($items as $i => $faq)
                        @php $key = $category . $i; @endphp
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <button @click="open = open === '{{ $key }}' ? null : '{{ $key }}'"
                                    class="w-full flex justify-between items-center px-5 py-4 text-left font-medium text-gray-900 hover:bg-gray-50 transition">
                                <span>{{ $faq['q'] }}</span>
                                <svg class="w-5 h-5 text-gray-400 flex-shrink-0 ml-4 transition-transform"
                                     :class="open === '{{ $key }}' ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open === '{{ $key }}'" x-transition
                                 class="px-5 pb-4 text-gray-600 text-sm leading-relaxed border-t border-gray-100">
                                <p class="pt-3">{{ $faq['a'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-12 bg-blue-50 rounded-2xl p-6 text-center">
        <p class="text-gray-700 mb-3">Tidak menemukan jawaban yang Anda cari?</p>
        <a href="{{ route('contact') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium inline-block">
            Hubungi Kami
        </a>
    </div>
</div>
@endsection
