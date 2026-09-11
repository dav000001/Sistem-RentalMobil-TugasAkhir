<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Services\ComplaintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorReportController extends Controller
{
    public function __construct(
        private readonly ComplaintService $service
    ) {}

    /**
     * Daftar laporan dari VENDOR saja.
     */
    public function index(Request $request)
    {
        $query = Complaint::with(['category', 'reporter', 'vendor', 'booking.car'])
            ->whereHas('reporter', function ($q) {
                $q->whereHas('vendor'); // reporter adalah vendor
            })
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('reporter', fn ($r) => $r->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('vendor', fn ($r) => $r->where('business_name', 'like', "%{$search}%"));
            });
        }

        $complaints = $query->paginate(20)->withQueryString();

        $statuses = [
            'submitted'          => 'Diajukan',
            'under_admin_review' => 'Ditinjau Admin',
            'resolved'           => 'Selesai',
            'rejected'           => 'Ditolak',
        ];

        return view('admin.vendor-reports.index', compact('complaints', 'statuses'));
    }

    /**
     * Detail laporan vendor.
     */
    public function show(Complaint $complaint)
    {
        // Pastikan memang laporan dari vendor
        if (!$complaint->reporter?->vendor) {
            return redirect()->route('admin.vendor-reports.index')
                ->with('error', 'Laporan ini bukan dari vendor.');
        }

        $complaint->load([
            'category',
            'booking.car',
            'booking.customer.user',
            'reporter.vendor',
            'vendor.user',
            'responses.author',
            'resolution.admin',
            'logs.actor',
        ]);

        return view('admin.vendor-reports.show', compact('complaint'));
    }

    /**
     * Selesaikan laporan vendor.
     */
    public function resolve(Request $request, Complaint $complaint)
    {
        $validated = $request->validate([
            'decision'  => 'required|in:warning_vendor,mutual_agreement,escalated_legal,escalated_insurance',
            'reasoning' => 'required|string|min:20|max:5000',
        ]);

        if (!$complaint->isOpen()) {
            return back()->with('error', 'Laporan ini sudah ditutup.');
        }

        $admin = Auth::guard('admin')->user();
        $this->service->resolve($complaint, $admin, $validated);

        return back()->with('success', 'Laporan berhasil diselesaikan.');
    }

    /**
     * Tolak laporan vendor.
     */
    public function reject(Request $request, Complaint $complaint)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:2000',
        ]);

        if (!$complaint->isOpen()) {
            return back()->with('error', 'Laporan ini sudah ditutup.');
        }

        $admin = Auth::guard('admin')->user();
        $this->service->reject($complaint, $admin, $validated['reason']);

        return back()->with('success', 'Laporan berhasil ditolak.');
    }

    /**
     * Tambah respons admin ke laporan vendor.
     */
    public function addResponse(Request $request, Complaint $complaint)
    {
        $validated = $request->validate([
            'message'    => 'required|string|min:5|max:3000',
            'visibility' => 'nullable|in:public,internal_admin',
        ]);

        if (!$complaint->isOpen()) {
            return back()->with('error', 'Laporan ini sudah ditutup.');
        }

        $admin = Auth::guard('admin')->user();
        $this->service->addResponse($complaint, $admin, $validated);

        return back()->with('success', 'Respons berhasil dikirim.');
    }
}
