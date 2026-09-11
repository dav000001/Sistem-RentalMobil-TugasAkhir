<?php

namespace App\Filament\Exports;

use App\Models\Booking;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class BookingExporter extends Exporter
{
    protected static ?string $model = Booking::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('code')
                ->label('Kode Booking'),
            ExportColumn::make('customer.full_name')
                ->label('Nama Penyewa'),
            ExportColumn::make('customer.user.email')
                ->label('Email Penyewa'),
            ExportColumn::make('customer.user.phone')
                ->label('No. HP Penyewa'),
            ExportColumn::make('vendor.business_name')
                ->label('Vendor'),
            ExportColumn::make('car_info')
                ->label('Mobil')
                ->state(fn (Booking $record) => $record->car ? $record->car->brand . ' ' . $record->car->model . ' - ' . $record->car->plate_number : '—'),
            ExportColumn::make('with_driver')
                ->label('Dengan Sopir')
                ->state(fn (Booking $record) => $record->with_driver ? 'Ya' : 'Tidak'),
            ExportColumn::make('driver.name')
                ->label('Nama Sopir'),
            ExportColumn::make('start_at')
                ->label('Tanggal Mulai'),
            ExportColumn::make('end_at')
                ->label('Tanggal Selesai'),
            ExportColumn::make('pickup_location')
                ->label('Lokasi Pickup'),
            ExportColumn::make('subtotal')
                ->label('Subtotal')
                ->state(fn (Booking $record) => 'Rp ' . number_format($record->subtotal, 0, ',', '.')),
            ExportColumn::make('platform_fee')
                ->label('Komisi Platform')
                ->state(fn (Booking $record) => 'Rp ' . number_format($record->platform_fee, 0, ',', '.')),
            ExportColumn::make('total')
                ->label('Total')
                ->state(fn (Booking $record) => 'Rp ' . number_format($record->total, 0, ',', '.')),
            ExportColumn::make('vendor_payout_amount')
                ->label('Payout Vendor')
                ->state(fn (Booking $record) => 'Rp ' . number_format($record->vendor_payout_amount, 0, ',', '.')),
            ExportColumn::make('status')
                ->label('Status Booking')
                ->state(fn (Booking $record) => match($record->status) {
                    'awaiting_payment' => 'Menunggu Pembayaran',
                    'awaiting_vendor'  => 'Menunggu Konfirmasi Vendor',
                    'confirmed'        => 'Dikonfirmasi',
                    'ongoing'          => 'Sedang Berlangsung',
                    'completed'        => 'Selesai',
                    'cancelled'        => 'Dibatalkan',
                    'refunded'         => 'Refund',
                    'disputed'         => 'Sengketa',
                    default            => ucfirst($record->status),
                }),
            ExportColumn::make('payment.status')
                ->label('Status Pembayaran')
                ->state(fn (Booking $record) => match($record->payment?->status) {
                    'paid' => 'Lunas',
                    'pending' => 'Pending',
                    'failed' => 'Gagal',
                    'refunded' => 'Refund',
                    default => '—',
                }),
            ExportColumn::make('payment.method')
                ->label('Metode Pembayaran'),
            ExportColumn::make('is_late')
                ->label('Terlambat')
                ->state(fn (Booking $record) => $record->is_late ? 'Ya' : 'Tidak'),
            ExportColumn::make('late_fee')
                ->label('Denda Keterlambatan')
                ->state(fn (Booking $record) => $record->late_fee > 0 ? 'Rp ' . number_format($record->late_fee, 0, ',', '.') : '—'),
            ExportColumn::make('created_at')
                ->label('Tanggal Booking')
                ->state(fn (Booking $record) => $record->created_at->format('d M Y H:i')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your booking export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
