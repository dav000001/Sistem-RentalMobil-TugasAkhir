<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Services\ComplaintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    public function __construct(
        private readonly ComplaintService $service
    ) {}

    /**
     * Show form to create a new complaint for a booking.
     */
    public function create(Booking $booking)
    {
        $user = Auth::user();

        // Ensure booking belongs to this customer
        if (!$booking->customer || $booking->customer->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        // Izinkan komplain untuk booking confirmed, ongoing, atau completed
        if (!in_array($booking->status, ['confirmed', 'ongoing', 'completed'])) {
            return redirect()->route('bookings.show', $booking->code)
                ->with('error', 'Komplain hanya dapat diajukan untuk pesanan yang sudah dikonfirmasi, sedang berlangsung, atau sudah selesai.');
        }

        // Check if an active complaint already exists
        $existingComplaint = Complaint::where('booking_id', $booking->id)
            ->whereNotIn('status', ['rejected'])
            ->first();

        if ($existingComplaint) {
            return redirect()->route('complaints.show', $existingComplaint->id)
                ->with('info', 'Anda sudah memiliki komplain aktif untuk pesanan ini.');
        }

        $categories = ComplaintCategory::active()->get();

        return view('complaints.create', compact('booking', 'categories'));
    }

    /**
     * Store a new complaint.
     */
    public function store(Request $request, Booking $booking)
    {
        $user = Auth::user();

        // Ensure booking belongs to this customer
        if (!$booking->customer || $booking->customer->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        if (!in_array($booking->status, ['confirmed', 'ongoing', 'completed'])) {
            return redirect()->route('bookings.show', $booking->code)
                ->with('error', 'Komplain hanya dapat diajukan untuk pesanan yang sudah dikonfirmasi, sedang berlangsung, atau sudah selesai.');
        }

        $validated = $request->validate([
            'category_id'            => 'required|exists:complaint_categories,id',
            'description'            => 'required|string|min:50|max:5000',
            'customer_demand'        => 'required|in:refund_full,refund_partial,discount_next,apology,other',
            'demanded_refund_amount' => 'nullable|integer|min:1',
            'customer_demand_note'   => 'nullable|string|max:1000',
            'evidence'               => 'nullable|array|max:5',
            'evidence.*'             => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Upload file bukti
        $attachmentPaths = [];
        if ($request->hasFile('evidence')) {
            foreach ($request->file('evidence') as $file) {
                $path = $file->store('complaints/evidence', 'public');
                $attachmentPaths[] = $path;
            }
        }

        if (!empty($attachmentPaths)) {
            $validated['attachments'] = $attachmentPaths;
        }

        $complaint = $this->service->submit($booking, $user, $validated);

        return redirect()->route('complaints.show', $complaint->id)
            ->with('success', 'Laporan berhasil diajukan dengan referensi ' . $complaint->reference . '. Tim kami akan menanganinya dalam 1-3 hari kerja.');
    }

    /**
     * List all complaints for the authenticated customer.
     */
    public function index()
    {
        $user = Auth::user();

        $complaints = Complaint::where('reporter_id', $user->id)
            ->with(['category', 'booking.car'])
            ->latest()
            ->paginate(10);

        return view('complaints.index', compact('complaints'));
    }

    /**
     * Show a single complaint detail.
     */
    public function show(Complaint $complaint)
    {
        $user = Auth::user();

        // Customer bisa lihat jika:
        // 1. Dia yang melaporkan (reporter)
        // 2. Komplain terkait booking miliknya (sebagai customer yang terkena laporan vendor)
        $isReporter  = $complaint->reporter_id === $user->id;
        $isCustomer  = $complaint->booking?->customer?->user_id === $user->id;

        if (!$isReporter && !$isCustomer) {
            abort(403, 'Anda tidak memiliki akses ke komplain ini.');
        }

        $complaint->load(['category', 'booking.car', 'vendor', 'responses.author', 'resolution', 'logs.actor']);

        return view('complaints.show', compact('complaint'));
    }

    /**
     * Add a response to a complaint.
     */
    public function addResponse(Request $request, Complaint $complaint)
    {
        $user = Auth::user();

        if ($complaint->reporter_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke komplain ini.');
        }

        if (!$complaint->isOpen()) {
            return back()->with('error', 'Komplain ini sudah ditutup dan tidak dapat direspons lagi.');
        }

        $validated = $request->validate([
            'message' => 'required|string|min:10|max:3000',
        ]);

        $this->service->addResponse($complaint, $user, $validated);

        return back()->with('success', 'Respons berhasil ditambahkan.');
    }
}
