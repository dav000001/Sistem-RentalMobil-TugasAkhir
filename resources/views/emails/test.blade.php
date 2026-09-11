<x-emails.layouts.branded>
    <h2>✅ Konfigurasi Email Berhasil!</h2>
    <p>Halo,</p>
    <p>Email ini dikirim sebagai konfirmasi bahwa konfigurasi SMTP <strong>{{ $appName }}</strong> sudah berfungsi dengan baik.</p>

    <div class="info-box">
        <strong>Detail Konfigurasi:</strong><br>
        Mailer: {{ config('mail.default') }}<br>
        Host: {{ config('mail.mailers.smtp.host') }}<br>
        Port: {{ config('mail.mailers.smtp.port') }}<br>
        From: {{ config('mail.from.address') }}
    </div>

    <p>Jika Anda menerima email ini, semua notifikasi sistem (booking, verifikasi, reset password, dll) akan berfungsi normal.</p>

    <a href="{{ $appUrl }}" class="btn">Buka {{ $appName }}</a>

    <hr class="divider">
    <p style="font-size: 13px; color: #6b7280;">Email ini dikirim via perintah <code>php artisan mail:test</code></p>
</x-emails.layouts.branded>
