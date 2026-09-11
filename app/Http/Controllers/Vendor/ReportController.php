<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payout;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $vendor = auth('vendor')->user()?->vendor;
        abort_unless($vendor, 403);

        [$from, $to] = $this->getDateRange($request);

        $data = $this->buildReportData($vendor, $from, $to, $request);

        return view('vendor.reports.index', array_merge($data, [
            'from'   => $from,
            'to'     => $to,
            'preset' => $request->get('preset', '30d'),
        ]));
    }

    public function exportPdf(Request $request)
    {
        $vendor = auth('vendor')->user()?->vendor;
        abort_unless($vendor, 403);

        [$from, $to] = $this->getDateRange($request);
        $data = $this->buildReportData($vendor, $from, $to, $request);

        $pdf = Pdf::loadView('vendor.reports.pdf', array_merge($data, [
            'vendor' => $vendor,
            'from'   => $from,
            'to'     => $to,
        ]))->setPaper('a4', 'portrait');

        $filename = 'laporan-' . $vendor->business_name . '-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    public function exportCsv(Request $request)
    {
        $vendor = auth('vendor')->user()?->vendor;
        abort_unless($vendor, 403);

        [$from, $to] = $this->getDateRange($request);

        $bookings = Booking::where('vendor_id', $vendor->id)
            ->whereHas('payment', fn ($q) => $q->where('status', 'paid')->whereBetween('paid_at', [$from, $to]))
            ->with(['car', 'customer.user', 'payment'])
            ->orderBy('created_at')
            ->get();

        $filename = 'laporan-' . $vendor->business_name . '-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($bookings) {
            $handle = fopen('php://output', 'w');
            // BOM untuk Excel agar bisa baca UTF-8
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Kode Booking', 'Tanggal Bayar', 'Customer', 'Mobil',
                'Mulai Sewa', 'Selesai Sewa', 'Durasi (hari)',
                'Subtotal', 'Komisi Platform', 'Payout Vendor', 'Status',
            ]);

            foreach ($bookings as $b) {
                $days = max(1, $b->start_at->diffInDays($b->end_at));
                fputcsv($handle, [
                    $b->code,
                    $b->payment?->paid_at?->format('d/m/Y H:i') ?? '-',
                    $b->customer?->full_name ?? '-',
                    $b->car->brand . ' ' . $b->car->model . ' ' . $b->car->year,
                    $b->start_at->format('d/m/Y'),
                    $b->end_at->format('d/m/Y'),
                    $days,
                    $b->subtotal,
                    $b->platform_fee,
                    $b->vendor_payout_amount,
                    $b->status,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Private helpers ──────────────────────────────────────────────────

    private function buildReportData($vendor, Carbon $from, Carbon $to, Request $request): array
    {
        // Booking paid dalam periode (exclude refunded)
        $bookings = Booking::where('vendor_id', $vendor->id)
            ->whereHas('payment', fn ($q) => $q->where('status', 'paid')->whereBetween('paid_at', [$from, $to]))
            ->with(['car', 'payment'])
            ->get();

        $totalRevenue   = $bookings->sum('total');
        $totalCommission = $bookings->sum('platform_fee');
        $totalPayout    = $bookings->sum('vendor_payout_amount');
        $bookingCount   = $bookings->count();

        // Payout sudah diterima dalam periode
        $payoutReceived = Payout::where('vendor_id', $vendor->id)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        // Payout pending
        $payoutPending = Payout::where('vendor_id', $vendor->id)
            ->where('status', 'pending')
            ->sum('amount');

        // Grafik harian
        $dailyData = Booking::where('vendor_id', $vendor->id)
            ->whereHas('payment', fn ($q) => $q->where('status', 'paid')->whereBetween('paid_at', [$from, $to]))
            ->join('payments', 'bookings.id', '=', 'payments.booking_id')
            ->select(
                DB::raw('DATE(payments.paid_at) as date'),
                DB::raw('COUNT(bookings.id) as count'),
                DB::raw('SUM(bookings.vendor_payout_amount) as payout'),
                DB::raw('SUM(bookings.total) as revenue'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Per mobil
        $perCar = Booking::where('vendor_id', $vendor->id)
            ->whereHas('payment', fn ($q) => $q->where('status', 'paid')->whereBetween('paid_at', [$from, $to]))
            ->join('cars', 'bookings.car_id', '=', 'cars.id')
            ->select(
                'cars.id',
                DB::raw("CONCAT(cars.brand, ' ', cars.model, ' ', cars.year) as car_name"),
                DB::raw('COUNT(bookings.id) as total_bookings'),
                DB::raw('SUM(bookings.total) as total_revenue'),
                DB::raw('SUM(bookings.vendor_payout_amount) as total_payout'),
            )
            ->groupBy('cars.id', 'cars.brand', 'cars.model', 'cars.year')
            ->orderByDesc('total_revenue')
            ->get();

        // Booking terbaru (untuk tabel detail)
        $recentBookings = Booking::where('vendor_id', $vendor->id)
            ->whereHas('payment', fn ($q) => $q->where('status', 'paid')->whereBetween('paid_at', [$from, $to]))
            ->with(['car', 'customer.user', 'payment'])
            ->latest()
            ->take(20)
            ->get();

        return compact(
            'bookings', 'totalRevenue', 'totalCommission', 'totalPayout',
            'bookingCount', 'payoutReceived', 'payoutPending',
            'dailyData', 'perCar', 'recentBookings'
        );
    }

    private function getDateRange(Request $request): array
    {
        $preset = $request->get('preset', '30d');

        return match ($preset) {
            'today'      => [now()->startOfDay(), now()->endOfDay()],
            '7d'         => [now()->subDays(7)->startOfDay(), now()->endOfDay()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'custom'     => [
                Carbon::parse($request->get('from', now()->subDays(30)))->startOfDay(),
                Carbon::parse($request->get('to', now()))->endOfDay(),
            ],
            default      => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
        };
    }
}
