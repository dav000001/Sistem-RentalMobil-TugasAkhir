<x-emails.layouts.branded>
    {{-- Hero Banner --}}
    <div style="background: linear-gradient(135deg, #059669, #047857); border-radius: 10px; padding: 24px; text-align: center; margin-bottom: 24px;">
        <div style="font-size: 48px; margin-bottom: 8px;">🎉</div>
        <h2 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 700;">Akun Vendor Anda Disetujui!</h2>
        <p style="color: #a7f3d0; margin: 6px 0 0; font-size: 14px;">Selamat bergabung sebagai mitra resmi {{ config('app.name') }}</p>
    </div>

    <p>Halo, <strong>{{ $notifiable->name }}</strong>!</p>
    <p>
        Kami dengan senang hati memberitahu bahwa akun vendor
        <strong>{{ $vendor->business_name }}</strong> telah berhasil diverifikasi dan sekarang <strong>aktif</strong>.
    </p>

    {{-- Info Box --}}
    <div class="info-box">
        <strong>✅ Status Akun:</strong> Aktif & Terverifikasi<br>
        <strong>🏪 Nama Bisnis:</strong> {{ $vendor->business_name }}<br>
        <strong>📦 Paket Saat Ini:</strong> {{ $planLabel }} — Komisi {{ $commissionRate }}%<br>
        <strong>📅 Disetujui:</strong> {{ now()->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
    </div>

    <p style="font-weight: 600; color: #111827; margin-top: 24px;">Langkah selanjutnya:</p>
    <ol style="padding-left: 20px; color: #374151; font-size: 14px; line-height: 2;">
        <li>Login ke dashboard vendor Anda</li>
        <li>Tambahkan mobil pertama Anda ke dalam armada</li>
        <li>Atur harga dan ketersediaan</li>
        <li>Mulai terima booking dari customer!</li>
    </ol>

    <div style="text-align: center; margin: 28px 0;">
        <a href="{{ url('/vendor') }}" class="btn" style="background: #059669;">
            🚀 Buka Dashboard Vendor
        </a>
    </div>

    <hr class="divider">

    <p style="font-size: 13px; color: #6b7280;">
        Jika ada pertanyaan, jangan ragu menghubungi tim support kami melalui
        <a href="{{ url('/hubungi-kami') }}" style="color: #2563eb;">halaman kontak</a>
        atau WhatsApp kami.
    </p>
</x-emails.layouts.branded>
