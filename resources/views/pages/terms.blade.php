@extends('layouts.app')
@section('title', 'Syarat & Ketentuan - Rental Mobil')
@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <h1 class="text-4xl font-bold text-gray-900 mb-2">Syarat & Ketentuan</h1>
    <p class="text-gray-500 mb-2">Terakhir diperbarui: {{ now()->format('d M Y') }}</p>
    <p class="text-gray-600 mb-12 text-sm">Dengan menggunakan layanan Rental Mobil, Anda menyetujui syarat dan ketentuan berikut.</p>

    <div class="space-y-8 text-gray-700">
        @foreach([
            ['title' => '1. Ketentuan Umum', 'content' => 'Rental Mobil adalah platform marketplace yang menghubungkan penyewa (customer) dengan penyedia armada (vendor). Kami bertindak sebagai perantara dan tidak bertanggung jawab atas kondisi fisik kendaraan yang disewakan oleh vendor.'],
            ['title' => '2. Pendaftaran Akun', 'content' => 'Pengguna wajib memberikan informasi yang akurat dan lengkap saat mendaftar. Setiap akun hanya boleh digunakan oleh satu orang. Kami berhak menangguhkan akun yang terbukti memberikan informasi palsu atau melanggar ketentuan.'],
            ['title' => '3. Verifikasi Identitas', 'content' => 'Penyewa wajib melakukan verifikasi identitas dengan mengupload KTP dan SIM A yang masih berlaku sebelum dapat melakukan pemesanan. Vendor wajib melengkapi dokumen bisnis (KTP, NPWP, SIUP/NIB) untuk dapat menayangkan armada.'],
            ['title' => '4. Pemesanan & Pembayaran', 'content' => 'Pemesanan dianggap sah setelah pembayaran berhasil diproses. Dana pembayaran disimpan dalam sistem escrow dan akan diteruskan ke vendor setelah rental selesai dikonfirmasi. Kami menggunakan Midtrans sebagai payment gateway yang telah tersertifikasi PCI-DSS.'],
            ['title' => '5. Pembatalan & Refund', 'content' => 'Pembatalan sebelum vendor mengkonfirmasi: refund penuh. Pembatalan setelah konfirmasi vendor: mengikuti kebijakan pembatalan masing-masing vendor yang tertera di halaman detail mobil. Refund diproses dalam 3-7 hari kerja.'],
            ['title' => '6. Kapasitas Penumpang & Pergantian Mobil', 'content' => 'Penyewa wajib memastikan jumlah penumpang sesuai dengan kapasitas kursi kendaraan yang dipesan. Jika jumlah penumpang melebihi kapasitas, penyewa dapat mengajukan pergantian kendaraan berkapasitas lebih besar maksimal H-1 sebelum masa sewa. Penyewa wajib membayar selisih harga jika unit pengganti lebih mahal. Apabila vendor tidak memiliki unit pengganti yang memadai dan membatalkan pesanan, pengembalian dana (refund) yang diberikan adalah sebesar 50% dari total pembayaran.'],
            ['title' => '7. Tanggung Jawab Vendor', 'content' => 'Vendor bertanggung jawab atas kondisi kendaraan yang disewakan, keakuratan informasi yang ditampilkan, dan kepatuhan terhadap SOP serah terima yang ditetapkan platform. Vendor wajib memiliki asuransi kendaraan yang masih berlaku.'],
            ['title' => '8. Tanggung Jawab Penyewa', 'content' => 'Penyewa bertanggung jawab atas kerusakan kendaraan yang terjadi selama masa sewa akibat kelalaian. Penyewa wajib mengembalikan kendaraan tepat waktu dan dalam kondisi yang sama seperti saat diterima.'],
            ['title' => '9. Privasi Data', 'content' => 'Kami mengumpulkan dan memproses data pribadi sesuai dengan Kebijakan Privasi kami dan Undang-Undang Perlindungan Data Pribadi (UU PDP No. 27/2022). Data Anda tidak akan dijual kepada pihak ketiga.'],
            ['title' => '10. Perubahan Ketentuan', 'content' => 'Kami berhak mengubah syarat dan ketentuan ini sewaktu-waktu. Perubahan akan diberitahukan melalui email atau notifikasi dalam aplikasi. Penggunaan layanan setelah perubahan dianggap sebagai persetujuan atas ketentuan baru.'],
            ['title' => '11. Hukum yang Berlaku', 'content' => 'Syarat dan ketentuan ini diatur oleh hukum Republik Indonesia. Setiap sengketa yang timbul akan diselesaikan melalui mediasi terlebih dahulu, dan jika tidak tercapai kesepakatan, akan diselesaikan melalui Pengadilan Negeri Jakarta.'],
        ] as $section)
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-3">{{ $section['title'] }}</h2>
                <p class="leading-relaxed text-sm">{{ $section['content'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-10 text-center">
        <p class="text-gray-500 text-sm">Ada pertanyaan tentang syarat & ketentuan?</p>
        <a href="{{ route('contact') }}" class="text-blue-600 hover:underline text-sm font-medium">Hubungi kami →</a>
    </div>
</div>
@endsection
