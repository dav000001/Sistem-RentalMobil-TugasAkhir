<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Services\ComplaintService;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function __construct(private ComplaintService $service) {}

    private function vendor()
    {
        return auth('vendor')->user()?->vendor;
    }

    public function index()
    {
        $vendor = $this->vendor();
        if (!$vendor) return redirect('/vendor/login');

        $complaints = Complaint::where('vendor_id', $vendor->id)
            ->with(['category', 'reporter', 'booking.car', 'responses'])
            ->latest()
            ->paginate(15);

        return view('vendor.complaints.index', compact('complaints'));
    }

    public function show(Complaint $complaint)
    {
        $vendor = $this->vendor();
        if (!$vendor || $complaint->vendor_id !== $vendor->id) {
            abort(403, 'Anda tidak memiliki akses ke komplain ini.');
        }

        $complaint->load([
            'category',
            'reporter',
            'booking.car',
            'responses.author',
            'resolution',
            'logs.actor',
        ]);

        return view('vendor.complaints.show', compact('complaint'));
    }

    public function respond(Request $request, Complaint $complaint)
    {
        $vendor = $this->vendor();
        if (!$vendor || $complaint->vendor_id !== $vendor->id) {
            abort(403, 'Anda tidak memiliki akses ke komplain ini.');
        }

        if (!$complaint->isOpen()) {
            return back()->with('error', 'Komplain ini sudah ditutup.');
        }

        if (!in_array($complaint->status, ['forwarded_to_vendor', 'vendor_responded', 'under_admin_review'])) {
            return back()->with('error', 'Komplain belum diteruskan ke Anda oleh admin.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:20', 'max:3000'],
        ]);

        $user = auth('vendor')->user();
        $this->service->addResponse($complaint, $user, [
            'message'    => $validated['message'],
            'visibility' => 'public',
        ]);

        return back()->with('success', 'Respons berhasil dikirim. Admin akan meninjau jawaban Anda.');
    }
}
