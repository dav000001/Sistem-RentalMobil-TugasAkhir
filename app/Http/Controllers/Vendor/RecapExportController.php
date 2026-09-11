<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RecapExportController extends Controller
{
    private function getVendor()
    {
        $vendor = auth('vendor')->user()?->vendor;
        abort_unless($vendor, 403);
        return $vendor;
    }

    private function getRange(Request $request): array
    {
        $from = Carbon::parse($request->get('from', now()->startOfMonth()))->startOfDay();
        $to   = Carbon::parse($request->get('to',   now()->endOfMonth()))->endOfDay();
        return [$from, $to];
    }

    private function buildData($vendor, Carbon $from, Carbon $to): array
    {
        $bookings = Booking::with(['customer', 'car'])
            ->where('vendor_id', $vendor->id)
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->get();

        $lateBookings = $bookings->where('is_late', true)->where('status', 'completed');

        $stats = [
            'total_bookings' => $bookings->count(),
            'completed'      => $bookings->where('status', 'completed')->count(),
            'cancelled'      => $bookings->where('status', 'cancelled')->count(),
            'ongoing'        => $bookings->whereIn('status', ['confirmed', 'ongoing'])->count(),
            'late_count'     => $lateBookings->count(),
            'total_revenue'  => $bookings->where('status', 'completed')->sum('vendor_payout_amount'),
            'total_late_fee' => $lateBookings->sum('late_fee'),
            'avg_rating'     => round(
                Review::whereHas('booking', fn ($q) => $q->where('vendor_id', $vendor->id))
                    ->whereBetween('created_at', [$from, $to])
                    ->avg('rating') ?? 0, 1
            ),
        ];

        return compact('bookings', 'lateBookings', 'stats');
    }

    public function exportPdf(Request $request)
    {
        $vendor        = $this->getVendor();
        [$from, $to]   = $this->getRange($request);
        $data          = $this->buildData($vendor, $from, $to);

        $pdf = Pdf::loadView('pdf.vendor-recap', array_merge($data, [
            'vendor' => $vendor,
            'from'   => $from->toDateString(),
            'to'     => $to->toDateString(),
        ]))->setPaper('a4', 'landscape');

        $filename = 'rekap-' . str($vendor->business_name)->slug() . '-'
            . $from->format('Ymd') . '-sd-' . $to->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    public function exportCsv(Request $request)
    {
        $vendor        = $this->getVendor();
        [$from, $to]   = $this->getRange($request);
        $data          = $this->buildData($vendor, $from, $to);
        $bookings      = $data['bookings'];

        $filename = 'rekap-' . str($vendor->business_name)->slug() . '-'
            . $from->format('Ymd') . '-sd-' . $to->format('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($bookings) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // BOM untuk Excel UTF-8

            fputcsv($handle, [
                'Kode Booking', 'Customer', 'Mobil',
                'Tanggal Mulai', 'Tanggal Selesai', 'Durasi (hari)',
                'Status', 'Total (Rp)', 'Payout Vendor (Rp)',
                'Terlambat', 'Durasi Terlambat (jam)', 'Denda (Rp)',
            ]);

            foreach ($bookings as $b) {
                $days = max(1, $b->start_at->diffInDays($b->end_at));
                fputcsv($handle, [
                    $b->code,
                    $b->customer?->full_name ?? '-',
                    trim(($b->car?->brand ?? '') . ' ' . ($b->car?->model ?? '')),
                    $b->start_at?->format('d/m/Y'),
                    $b->end_at?->format('d/m/Y'),
                    $days,
                    $b->status,
                    $b->total,
                    $b->vendor_payout_amount,
                    $b->is_late ? 'Ya' : 'Tidak',
                    $b->late_duration_hours ?? 0,
                    $b->late_fee ?? 0,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
