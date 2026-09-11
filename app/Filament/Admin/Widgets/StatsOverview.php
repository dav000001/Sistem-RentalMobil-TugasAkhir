<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\CustomerRefund;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // ── Helper scope: booking yang benar-benar menghasilkan pendapatan ──
        // Exclude booking yang sudah di-refund (payment.status = refunded)
        $paidScope = fn ($q) => $q->where('status', 'paid');
        $paidNotRefundedScope = fn ($q) => $q->whereIn('status', ['paid']); // refunded sudah exclude otomatis karena status berubah

        // ── Booking bulan ini yang sudah dibayar (exclude refunded) ──
        $bookingsBulanIni = Booking::whereHas('payment', function ($q) {
            $q->where('status', 'paid')
              ->whereMonth('paid_at', now()->month)
              ->whereYear('paid_at', now()->year);
        })->with('payment')->get();

        $pemasukanBulanIni   = $bookingsBulanIni->sum(fn ($b) => $b->payment?->amount ?? 0);
        
        // Komisi platform hanya dari booking yang tidak di-refund
        $totalKomisiPlatform = $bookingsBulanIni->sum('platform_fee');

        // ── Refund bulan ini (uang yang sudah dikembalikan ke customer) ──
        $refundBulanIni = CustomerRefund::where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        // Uang masuk bersih = pemasukan - refund
        $pemasukanBersih = max(0, $pemasukanBulanIni - $refundBulanIni);

        // ── Semua booking paid (exclude refunded) untuk kalkulasi vendor ──
        $semuaBookingPaid = Booking::whereHas('payment', fn ($q) =>
            $q->where('status', 'paid') // exclude refunded
        )->get();

        // Belum dibayar ke vendor = total hak vendor dari semua booking
        //   dikurangi semua payout yang sudah dibuat (paid + pending)
        //   hasilnya tidak boleh negatif
        $totalHakVendor    = $semuaBookingPaid->sum('vendor_payout_amount');
        $totalSudahDipayout = Payout::whereIn('status', ['paid', 'pending'])->sum('amount');
        $totalUntukVendor  = max(0, $totalHakVendor - $totalSudahDipayout);

        // ── Payout sudah dibayar bulan ini ───────────────────────────
        // Fallback: jika paid_at NULL tapi status = paid, tetap hitung
        $sudahDibayarKeVendor = Payout::where('status', 'paid')
            ->where(function ($q) {
                $q->whereMonth('paid_at', now()->month)
                  ->whereYear('paid_at', now()->year)
                  ->orWhereNull('paid_at'); // payout paid tapi paid_at belum diisi
            })
            ->sum('amount');

        $payoutPending    = Payout::where('status', 'pending')->sum('amount');
        $sisaBelumDipayout = max(0, $totalUntukVendor - $payoutPending);

        // ── Transaksi hari ini ────────────────────────────────────────
        $bayarHariIni = Payment::where('status', 'paid')
            ->whereDate('paid_at', today())
            ->count();

        // ── Refund pending (belum diproses) ───────────────────────────
        $refundPending      = CustomerRefund::where('status', 'pending')->count();
        $refundPendingTotal = CustomerRefund::where('status', 'pending')->sum('amount');

        return [
            // ── Stat 1: Uang Masuk Bersih (sudah dikurangi refund) ───
            Stat::make(
                '💰 Uang Masuk Bulan Ini',
                'Rp ' . number_format($pemasukanBersih, 0, ',', '.')
            )
                ->description(
                    $bookingsBulanIni->count() . ' booking · ' . $bayarHariIni . ' transaksi hari ini'
                    . ($refundBulanIni > 0 ? ' · Refund: Rp ' . number_format($refundBulanIni, 0, ',', '.') : '')
                )
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            // ── Stat 2: Komisi Platform ───────────────────────────────
            Stat::make(
                '🏦 Komisi Platform',
                'Rp ' . number_format($totalKomisiPlatform, 0, ',', '.')
            )
                ->description(
                    'Rp ' . number_format($pemasukanBulanIni, 0, ',', '.')
                    . ' − Rp ' . number_format($totalUntukVendor, 0, ',', '.')
                )
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('info'),

            // ── Stat 3: Belum Dibayar ke Vendor ──────────────────────
            Stat::make(
                '👥 Belum Dibayar ke Vendor',
                'Rp ' . number_format($totalUntukVendor, 0, ',', '.')
            )
                ->description('Total semua booking — sudah di-payout')
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color($totalUntukVendor > 0 ? 'warning' : 'success'),

            // ── Stat 4: Sudah Dibayar ke Vendor ──────────────────────
            Stat::make(
                '✅ Sudah Dibayar ke Vendor',
                'Rp ' . number_format($sudahDibayarKeVendor, 0, ',', '.')
            )
                ->description(
                    Payout::where('status', 'paid')
                        ->where(function ($q) {
                            $q->whereMonth('paid_at', now()->month)
                              ->whereYear('paid_at', now()->year)
                              ->orWhereNull('paid_at');
                        })
                        ->count() . ' payout selesai bulan ini'
                )
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            // ── Stat 5: Payout Pending ────────────────────────────────
            Stat::make(
                '⏳ Payout Pending',
                'Rp ' . number_format($payoutPending, 0, ',', '.')
            )
                ->description(Payout::where('status', 'pending')->count() . ' vendor menunggu transfer')
                ->descriptionIcon('heroicon-o-clock')
                ->color($payoutPending > 0 ? 'warning' : 'gray'),

            // ── Stat 6: Belum Dibuatkan Payout ───────────────────────
            Stat::make(
                '📋 Belum Dibuatkan Payout',
                'Rp ' . number_format($sisaBelumDipayout, 0, ',', '.')
            )
                ->description('Vendor belum dibuatkan payout bulan ini')
                ->descriptionIcon('heroicon-o-document-plus')
                ->color($sisaBelumDipayout > 0 ? 'danger' : 'success'),

            // ── Stat 7: Refund Pending ────────────────────────────────
            Stat::make(
                '💸 Refund Pending',
                'Rp ' . number_format($refundPendingTotal, 0, ',', '.')
            )
                ->description($refundPending . ' customer menunggu refund')
                ->descriptionIcon('heroicon-o-arrow-uturn-left')
                ->color($refundPending > 0 ? 'danger' : 'success'),
        ];
    }
}
