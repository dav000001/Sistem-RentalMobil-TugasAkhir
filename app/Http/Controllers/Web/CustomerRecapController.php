<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CustomerRecapController extends Controller
{
    /**
     * Halaman rekap pribadi customer.
     */
    public function index(Request $request)
    {
        $customer = auth()->user()->customer;

        if (!$customer) {
            return redirect()->route('home')->with('error', 'Anda bukan customer.');
        }

        [$from, $to] = $this->getRange($request);

        $bookings = $customer->bookings()
            ->with(['car', 'vendor'])
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->get();

        $stats = $this->buildStats($bookings);

        return view('public.recap.index', compact('bookings', 'stats', 'from', 'to'));
    }

    /**
     * Export PDF rekap pribadi customer.
     */
    public function exportPdf(Request $request)
    {
        $customer = auth()->user()->customer;

        if (!$customer) {
            abort(403);
        }

        [$from, $to] = $this->getRange($request);

        $bookings = $customer->bookings()
            ->with(['car', 'vendor'])
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->get();

        $stats       = $this->buildStats($bookings);
        $customerName = $customer->full_name;

        $pdf = Pdf::loadView('pdf.recap-customer-personal', compact('bookings', 'stats', 'from', 'to', 'customerName'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('rekap-pesanan-' . $from->format('Ymd') . '-sd-' . $to->format('Ymd') . '.pdf');
    }

    /**
     * Export CSV rekap pribadi customer.
     */
    public function exportCsv(Request $request)
    {
        $customer = auth()->user()->customer;

        if (!$customer) {
            abort(403);
        }

        [$from, $to] = $this->getRange($request);

        $bookings = $customer->bookings()
            ->with(['car', 'vendor'])
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->get();

        $filename = 'rekap-pesanan-' . $from->format('Ymd') . '-sd-' . $to->format('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($bookings) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8 for Excel

            fputcsv($out, [
                'Kode Booking', 'Tanggal Pesan', 'Nama Mobil', 'Vendor',
                'Mulai Sewa', 'Selesai Sewa', 'Durasi (hari)', 'Status',
                'Keterlambatan', 'Total Bayar (Rp)',
            ]);

            foreach ($bookings as $booking) {
                $statusLabel = match ($booking->status) {
                    'awaiting_payment' => 'Menunggu Pembayaran',
                    'awaiting_vendor'  => 'Menunggu Konfirmasi Vendor',
                    'confirmed'        => 'Dikonfirmasi',
                    'ongoing'          => 'Dalam Perjalanan',
                    'completed'        => 'Selesai',
                    'cancelled'        => 'Dibatalkan',
                    default            => $booking->status,
                };

                fputcsv($out, [
                    $booking->code,
                    $booking->created_at->format('d/m/Y'),
                    ($booking->car->brand ?? '') . ' ' . ($booking->car->model ?? ''),
                    $booking->vendor->business_name ?? '—',
                    $booking->start_at?->format('d/m/Y H:i'),
                    $booking->end_at?->format('d/m/Y H:i'),
                    $booking->start_at && $booking->end_at
                        ? $booking->start_at->diffInDays($booking->end_at) ?: 1
                        : '—',
                    $statusLabel,
                    $booking->is_late ? 'Ya' : 'Tidak',
                    $booking->total ?? 0,
                ]);
            }

            fclose($out);
        }, 200, $headers);
    }

    // ── Private Helpers ───────────────────────────────────────────────

    private function getRange(Request $request): array
    {
        $from = Carbon::parse($request->get('from', now()->startOfMonth()))->startOfDay();
        $to   = Carbon::parse($request->get('to',   now()->endOfMonth()))->endOfDay();
        return [$from, $to];
    }

    private function buildStats(\Illuminate\Support\Collection $bookings): array
    {
        $completed = $bookings->where('status', 'completed');
        $cancelled = $bookings->where('status', 'cancelled');

        return [
            'total_bookings' => $bookings->count(),
            'completed'      => $completed->count(),
            'cancelled'      => $cancelled->count(),
            'ongoing'        => $bookings->whereIn('status', ['awaiting_payment', 'awaiting_vendor', 'confirmed', 'ongoing'])->count(),
            'total_spent'    => $completed->sum('total'),
            'late_count'     => $completed->where('is_late', true)->count(),
            'avg_duration'   => $completed->avg(fn ($b) =>
                $b->start_at && $b->end_at
                    ? max(1, $b->start_at->diffInDays($b->end_at))
                    : 0
            ) ?? 0,
        ];
    }
}
