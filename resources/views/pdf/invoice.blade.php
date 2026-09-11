<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $booking->code }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; color: #2563eb; }
        .info-section { margin-bottom: 20px; }
        .info-section h3 { margin: 0 0 10px 0; border-bottom: 2px solid #2563eb; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background-color: #f3f4f6; }
        .total-row { font-weight: bold; font-size: 14px; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚗 RENTAL MOBIL</h1>
        <p>Invoice Pemesanan</p>
    </div>

    <div class="info-section">
        <h3>Informasi Pesanan</h3>
        <table>
            <tr>
                <td width="30%"><strong>Kode Pesanan</strong></td>
                <td>{{ $booking->code }}</td>
            </tr>
            <tr>
                <td><strong>Tanggal Pesanan</strong></td>
                <td>{{ $booking->created_at->format('d M Y H:i') }}</td>
            </tr>
            <tr>
                <td><strong>Status Pesanan</strong></td>
                <td>{{ match($booking->status) {
                    'awaiting_payment' => 'Menunggu Pembayaran',
                    'awaiting_vendor'  => 'Menunggu Konfirmasi Vendor',
                    'confirmed'        => 'Dikonfirmasi',
                    'ongoing'          => 'Sedang Berlangsung',
                    'completed'        => 'Selesai',
                    'cancelled'        => 'Dibatalkan',
                    default            => ucfirst(str_replace('_', ' ', $booking->status)),
                } }}</td>
            </tr>
        </table>
    </div>

    <div class="info-section">
        <h3>Informasi Penyewa</h3>
        <table>
            <tr>
                <td width="30%"><strong>Nama</strong></td>
                <td>{{ $booking->customer->full_name }}</td>
            </tr>
            <tr>
                <td><strong>Email</strong></td>
                <td>{{ $booking->customer->user->email }}</td>
            </tr>
            <tr>
                <td><strong>Telepon</strong></td>
                <td>{{ $booking->customer->user->phone }}</td>
            </tr>
        </table>
    </div>

    <div class="info-section">
        <h3>Detail Mobil</h3>
        <table>
            <tr>
                <td width="30%"><strong>Mobil</strong></td>
                <td>{{ $booking->car->brand }} {{ $booking->car->model }} {{ $booking->car->year }}</td>
            </tr>
            <tr>
                <td><strong>Vendor</strong></td>
                <td>{{ $booking->vendor->business_name }}</td>
            </tr>
            <tr>
                <td><strong>Tanggal Mulai</strong></td>
                <td>{{ $booking->start_at->format('d M Y H:i') }}</td>
            </tr>
            <tr>
                <td><strong>Tanggal Selesai</strong></td>
                <td>{{ $booking->end_at->format('d M Y H:i') }}</td>
            </tr>
            <tr>
                <td><strong>Durasi</strong></td>
                <td>{{ durationLabel($booking->start_at, $booking->end_at) }}</td>
            </tr>
            <tr>
                <td><strong>Jumlah Penumpang</strong></td>
                <td>{{ $booking->passenger_count ?? 1 }} orang</td>
            </tr>
            <tr>
                <td><strong>Lokasi Penjemputan</strong></td>
                <td>{{ $booking->pickup_location }}</td>
            </tr>
            @if($booking->with_driver)
            <tr>
                <td><strong>Dengan Sopir</strong></td>
                <td>Ya</td>
            </tr>
            @endif
            @if($booking->carChangeRequest && $booking->carChangeRequest->status === 'approved')
            <tr>
                <td><strong>Status Kendaraan</strong></td>
                <td>
                    Unit Diganti (dari {{ $booking->carChangeRequest->oldCar?->brand }} {{ $booking->carChangeRequest->oldCar?->model }} → {{ $booking->car->brand }} {{ $booking->car->model }})
                </td>
            </tr>
            @endif
        </table>
    </div>

    <div class="info-section">
        <h3>Rincian Biaya</h3>
        <table>
            @if($booking->carChangeRequest && $booking->carChangeRequest->status === 'approved' && $booking->carChangeRequest->price_difference > 0)
            <tr>
                <td width="70%">Biaya Mobil Awal ({{ $booking->carChangeRequest->oldCar?->brand }} {{ $booking->carChangeRequest->oldCar?->model }})</td>
                <td style="text-align: right;">{{ formatRupiah($booking->carChangeRequest->original_total) }}</td>
            </tr>
            <tr>
                <td>Selisih Biaya Upgrade Mobil ({{ $booking->car->brand }} {{ $booking->car->model }})</td>
                <td style="text-align: right;">+ {{ formatRupiah($booking->carChangeRequest->price_difference) }}</td>
            </tr>
            @else
            <tr>
                <td width="70%">Sewa Mobil ({{ $booking->car->brand }} {{ $booking->car->model }})</td>
                <td style="text-align: right;">{{ formatRupiah($booking->subtotal) }}</td>
            </tr>
            @endif
            @if($booking->addon_fees > 0)
            <tr>
                <td>Biaya Tambahan</td>
                <td style="text-align: right;">{{ formatRupiah($booking->addon_fees) }}</td>
            </tr>
            @endif
            @if($booking->discount > 0)
            <tr>
                <td>Diskon</td>
                <td style="text-align: right; color: green;">- {{ formatRupiah($booking->discount) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>TOTAL</td>
                <td style="text-align: right;">{{ formatRupiah($booking->total) }}</td>
            </tr>
        </table>
    </div>

    @if($booking->payment)
    <div class="info-section">
        <h3>Informasi Pembayaran</h3>
        <table>
            <tr>
                <td width="30%"><strong>Status</strong></td>
                <td>{{ match($booking->payment->status) {
                    'pending' => 'Menunggu Konfirmasi Admin',
                    'paid'    => 'Lunas ✓',
                    'failed'  => 'Ditolak',
                    default   => ucfirst($booking->payment->status),
                } }}</td>
            </tr>
            @if($booking->payment->paid_at)
            <tr>
                <td><strong>Tanggal Bayar</strong></td>
                <td>{{ $booking->payment->paid_at->format('d M Y H:i') }}</td>
            </tr>
            @endif
            @if($booking->payment->method)
            <tr>
                <td><strong>Metode</strong></td>
                <td>{{ match($booking->payment->method) {
                    'bank_transfer' => 'Transfer Bank',
                    default         => ucfirst(str_replace('_', ' ', $booking->payment->method)),
                } }}</td>
            </tr>
            @endif
            @if($booking->payment->sender_name)
            <tr>
                <td><strong>Nama Pengirim</strong></td>
                <td>{{ $booking->payment->sender_name }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Terima kasih telah menggunakan layanan Rental Mobil</p>
        <p>Dokumen ini dibuat secara otomatis dan sah tanpa tanda tangan</p>
    </div>
</body>
</html>
