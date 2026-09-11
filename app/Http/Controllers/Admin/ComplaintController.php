<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Services\ComplaintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    public function __construct(
        private readonly ComplaintService $service
    ) {}

    /**
     * List complaints from CUSTOMERS only.
     */
    public function index(Request $request)
    {
        // Hanya komplain dari customer (bukan vendor)
        $query = Complaint::with(['category', 'reporter', 'vendor', 'booking.car'])
            ->whereHas('reporter', function ($q) {
                $q->whereDoesntHave('vendor'); // reporter bukan vendor
            })
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('reporter', fn ($r) => $r->where('name', 'like', "%{$search}%"));
            });
        }

        $complaints = $query->paginate(20)->withQueryString();

        $statuses = [
            'submitted'           => 'Diajukan',
            'forwarded_to_vendor' => 'Diteruskan ke Vendor',
            'vendor_responded'    => 'Vendor Merespons',
            'under_admin_review'  => 'Ditinjau Admin',
            'resolved'            => 'Selesai',
            'rejected'            => 'Ditolak',
        ];

        $severities = [
            'low'      => 'Rendah',
            'medium'   => 'Sedang',
            'high'     => 'Tinggi',
            'critical' => 'Kritis',
        ];

        return view('admin.complaints.index', compact('complaints', 'statuses', 'severities'));
    }

    /**
     * Show a single complaint detail.
     */
    public function show(Complaint $complaint)
    {
        $complaint->load([
            'category',
            'booking.car',
            'reporter',
            'vendor.user',
            'responses.author',
            'resolution.admin',
            'logs.actor',
        ]);

        return view('admin.complaints.show', compact('complaint'));
    }

    /**
     * Forward complaint to vendor.
     */
    public function forward(Request $request, Complaint $complaint)
    {
        if (!in_array($complaint->status, ['submitted', 'under_admin_review'])) {
            return back()->with('error', 'Komplain tidak dapat diteruskan pada status saat ini.');
        }

        $admin = Auth::guard('admin')->user();
        $this->service->forward($complaint, $admin);

        return back()->with('success', 'Komplain berhasil diteruskan ke vendor.');
    }

    /**
     * Resolve a complaint.
     */
    public function resolve(Request $request, Complaint $complaint)
    {
        $validated = $request->validate([
            'decision'              => 'required|in:refund_full,refund_partial,discount_voucher,warning_vendor,suspend_vendor,rejected,escalated_legal,escalated_insurance,mutual_agreement',
            'reasoning'             => 'required|string|min:20|max:5000',
            'refund_amount'         => 'nullable|integer|min:1',
            'vendor_penalty_amount' => 'nullable|integer|min:1',
            'voucher_amount'        => 'nullable|integer|min:1',
            'vendor_suspend_days'   => 'nullable|integer|min:1|max:365',
        ]);

        if (!$complaint->isOpen()) {
            return back()->with('error', 'Komplain ini sudah ditutup.');
        }

        $admin = Auth::guard('admin')->user();
        $this->service->resolve($complaint, $admin, $validated);

        return back()->with('success', 'Komplain berhasil diselesaikan.');
    }

    /**
     * Reject a complaint.
     */
    public function reject(Request $request, Complaint $complaint)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:2000',
        ]);

        if (!$complaint->isOpen()) {
            return back()->with('error', 'Komplain ini sudah ditutup.');
        }

        $admin = Auth::guard('admin')->user();
        $this->service->reject($complaint, $admin, $validated['reason']);

        return back()->with('success', 'Komplain berhasil ditolak.');
    }

    /**
     * Add an admin response to a complaint.
     */
    public function addResponse(Request $request, Complaint $complaint)
    {
        $validated = $request->validate([
            'message'    => 'required|string|min:5|max:3000',
            'visibility' => 'nullable|in:public,internal_admin',
        ]);

        if (!$complaint->isOpen()) {
            return back()->with('error', 'Komplain ini sudah ditutup.');
        }

        $admin = Auth::guard('admin')->user();
        $this->service->addResponse($complaint, $admin, $validated);

        return back()->with('success', 'Respons berhasil ditambahkan.');
    }
}
