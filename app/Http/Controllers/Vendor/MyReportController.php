<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Services\ComplaintService;
use Illuminate\Http\Request;

class MyReportController extends Controller
{
    public function __construct(private ComplaintService $service) {}

    private function vendorUser()
    {
        return auth('vendor')->user();
    }

    /**
     * Daftar laporan yang diajukan vendor ini.
     */
    public function index()
    {
        $user = $this->vendorUser();
        if (!$user) return redirect('/vendor/login');

        $reports = Complaint::where('reporter_id', $user->id)
            ->with(['category', 'booking.car', 'responses'])
            ->latest()
            ->paginate(15);

        return view('vendor.my-reports.index', compact('reports'));
    }

    /**
     * Detail laporan vendor.
     */
    public function show(Complaint $complaint)
    {
        $user = $this->vendorUser();
        if (!$user || $complaint->reporter_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        $complaint->load([
            'category',
            'booking.car',
            'booking.customer.user',
            'responses.author',
            'resolution',
            'logs.actor',
        ]);

        return view('vendor.my-reports.show', compact('complaint'));
    }

    /**
     * Vendor tambah respons ke laporan miliknya.
     */
    public function addResponse(Request $request, Complaint $complaint)
    {
        $user = $this->vendorUser();
        if (!$user || $complaint->reporter_id !== $user->id) {
            abort(403);
        }

        if (!$complaint->isOpen()) {
            return back()->with('error', 'Laporan ini sudah ditutup.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        $this->service->addResponse($complaint, $user, [
            'message'    => $validated['message'],
            'visibility' => 'public',
        ]);

        return back()->with('success', 'Respons berhasil dikirim.');
    }
}
