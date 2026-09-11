<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Vendor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RecapExportController extends Controller
{
    private function getRange(Request $request): array
    {
        $from = Carbon::parse($request->get('from', now()->startOfMonth()))->startOfDay();
        $to   = Carbon::parse($request->get('to',   now()->endOfMonth()))->endOfDay();
        return [$from, $to];
    }

    private function buildVendorData(Carbon $from, Carbon $to): \Illuminate\Support\Collection
    {
        return Vendor::with([
            'cars',
            'bookings' => fn ($q) => $q->whereBetween('created_at', [$from, $to]),
        ])->get()->map(function (Vendor $vendor) use ($from, $to) {
            $bookings  = $vendor->bookings->whereBetween('created_at', [$from, $to]);
            $completed = $bookings->where('status', 'completed');

            return [
                'name'           => $vendor->business_name,
                'total_cars'     => $vendor->cars->count(),
                'total_bookings' => $bookings->count(),
                'completed'      => $completed->count(),
                'total_revenue'  => $completed->sum('vendor_payout_amount'),
                'late_count'     => $bookings->where('is_late', true)->count(),
                'avg_rating'     => round(
                    \App\Models\Review::whereHas('booking', fn ($q) => $q->where('vendor_id', $vendor->id))
                        ->whereBetween('created_at', [$from, $to])
                        ->avg('rating') ?? 0, 1
                ),
            ];
        })->sortByDesc('total_revenue');
    }

    private function buildCustomerData(Carbon $from, Carbon $to): \Illuminate\Support\Collection
    {
        return Customer::with([
            'bookings' => fn ($q) => $q->whereBetween('created_at', [$from, $to])->with('car'),
        ])->get()
        ->filter(fn ($c) => $c->bookings->isNotEmpty())
        ->map(function (Customer $customer) {
            $bookings  = $customer->bookings;
            $completed = $bookings->where('status', 'completed');

            $favCar     = $bookings->groupBy('car_id')->sortByDesc(fn ($g) => $g->count())->keys()->first();
            $favCarName = $bookings->firstWhere('car_id', $favCar)?->car
                ? ($bookings->firstWhere('car_id', $favCar)->car->brand . ' ' . $bookings->firstWhere('car_id', $favCar)->car->model)
                : '—';

            return [
                'name'           => $customer->full_name,
                'email'          => $customer->user?->email,
                'total_bookings' => $bookings->count(),
                'completed'      => $completed->count(),
                'total_spent'    => $completed->sum('total'),
                'late_count'     => $bookings->where('is_late', true)->count(),
                'fav_car'        => $favCarName,
            ];
        })->sortByDesc('total_spent');
    }

    // ── Vendor PDF ───────────────────────────────────────────────────
    public function vendorPdf(Request $request)
    {
        [$from, $to] = $this->getRange($request);
        $data = $this->buildVendorData($from, $to);

        $pdf = Pdf::loadView('pdf.recap-vendor', [
            'data' => $data,
            'from' => $from->toDateString(),
            'to'   => $to->toDateString(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('rekap-vendor-' . $from->format('Ymd') . '-sd-' . $to->format('Ymd') . '.pdf');
    }

    // ── Vendor CSV ───────────────────────────────────────────────────
    public function vendorCsv(Request $request)
    {
        [$from, $to] = $this->getRange($request);
        $data = $this->buildVendorData($from, $to);

        $filename = 'rekap-vendor-' . $from->format('Ymd') . '-sd-' . $to->format('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nama Vendor', 'Total Mobil', 'Total Booking', 'Selesai', 'Pendapatan Vendor (Rp)', 'Keterlambatan', 'Rating']);
            foreach ($data as $row) {
                fputcsv($out, [
                    $row['name'], $row['total_cars'], $row['total_bookings'],
                    $row['completed'], $row['total_revenue'], $row['late_count'], $row['avg_rating'],
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    // ── Customer PDF ─────────────────────────────────────────────────
    public function customerPdf(Request $request)
    {
        [$from, $to] = $this->getRange($request);
        $data = $this->buildCustomerData($from, $to);

        $pdf = Pdf::loadView('pdf.recap-customer', [
            'data' => $data,
            'from' => $from->toDateString(),
            'to'   => $to->toDateString(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('rekap-customer-' . $from->format('Ymd') . '-sd-' . $to->format('Ymd') . '.pdf');
    }

    // ── Customer CSV ─────────────────────────────────────────────────
    public function customerCsv(Request $request)
    {
        [$from, $to] = $this->getRange($request);
        $data = $this->buildCustomerData($from, $to);

        $filename = 'rekap-customer-' . $from->format('Ymd') . '-sd-' . $to->format('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nama Customer', 'Email', 'Total Booking', 'Selesai', 'Total Pengeluaran (Rp)', 'Keterlambatan', 'Mobil Favorit']);
            foreach ($data as $row) {
                fputcsv($out, [
                    $row['name'], $row['email'], $row['total_bookings'],
                    $row['completed'], $row['total_spent'], $row['late_count'], $row['fav_car'],
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }
}
