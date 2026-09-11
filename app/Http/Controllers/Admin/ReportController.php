<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private ReportService $service) {}

    public function index(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);

        $data = [
            'gmv'             => $this->service->gmvBetween($from, $to),
            'commission'      => $this->service->commissionBetween($from, $to),
            'bookingCount'    => $this->service->bookingCountBetween($from, $to),
            'activeVendors'   => $this->service->activeVendorsBetween($from, $to),
            'topVendors'      => $this->service->topVendors($from, $to),
            'topCars'         => $this->service->topCars($from, $to),
            'dailyBreakdown'  => $this->service->dailyBreakdown($from, $to),
            'byStatus'        => $this->service->bookingsByStatus(),
            'vendorBreakdown' => $this->service->vendorBreakdown($from, $to),
            'from'            => $from,
            'to'              => $to,
            'preset'          => $request->get('preset', '30d'),
        ];

        return view('admin.reports.index', $data);
    }

    private function getDateRange(Request $request): array
    {
        $preset = $request->get('preset', '30d');

        return match ($preset) {
            'today'     => [now()->startOfDay(), now()->endOfDay()],
            '7d'        => [now()->subDays(7)->startOfDay(), now()->endOfDay()],
            'this_month'=> [now()->startOfMonth(), now()->endOfMonth()],
            'last_month'=> [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'custom'    => [
                Carbon::parse($request->get('from', now()->subDays(30)))->startOfDay(),
                Carbon::parse($request->get('to', now()))->endOfDay(),
            ],
            default     => [now()->subDays(30)->startOfDay(), now()->endOfDay()], // 30d
        };
    }
}
