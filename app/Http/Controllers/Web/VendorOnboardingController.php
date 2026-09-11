<?php

namespace App\Http\Controllers\Web;

use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Models\VendorDocument;
use App\Services\Vendor\VendorVerificationService;
use Illuminate\Http\Request;

class VendorOnboardingController extends Controller
{
    public function __construct(private VendorVerificationService $service) {}

    public function show()
    {
        $user = auth()->user();
        $vendor = $user->vendor;

        if (!$vendor) {
            return redirect()->route('home')->with('error', 'Akun vendor tidak ditemukan.');
        }

        $status = $vendor->status instanceof VendorStatus
            ? $vendor->status
            : VendorStatus::from($vendor->status ?? 'pending');

        // If approved, redirect to vendor dashboard
        if ($status->isOperational()) {
            return redirect('/vendor')->with('success', 'Akun Anda sudah aktif!');
        }

        $documents     = $vendor->documents()->get()->keyBy('type');
        $businessType  = $vendor->business_type ?? 'badan_usaha';
        $requiredTypes = VendorDocument::requiredTypes($businessType);
        $optionalTypes = VendorDocument::optionalTypes($businessType);
        $businessTypeLabel = VendorDocument::businessTypeLabel($businessType);
        $statusLogs    = $vendor->statusLogs()->with('actor')->latest()->take(10)->get();

        return view('vendor.onboarding', compact(
            'vendor', 'documents', 'requiredTypes', 'optionalTypes',
            'businessType', 'businessTypeLabel', 'status', 'statusLogs'
        ));
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'business_name'       => ['required', 'string', 'max:255'],
            'address'             => ['required', 'string'],
            'city_id'             => ['required', 'exists:cities,id'],
            'bank_name'           => ['nullable', 'string'],
            'bank_account_number' => ['nullable', 'string'],
            'bank_account_name'   => ['nullable', 'string'],
        ]);

        auth()->user()->vendor->update($validated);

        return back()->with('success', 'Profil bisnis berhasil disimpan.');
    }

    public function uploadDocument(Request $request)
    {
        $vendor       = auth()->user()->vendor;
        $businessType = $vendor->business_type ?? 'badan_usaha';
        $allowedTypes = VendorDocument::allowedTypes($businessType);

        $request->validate([
            'type' => ['required', 'in:' . implode(',', $allowedTypes)],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:3072'],
        ]);

        $type = $request->type;
        $file = $request->file('file');

        // Store in private disk
        $path = $file->store("vendor-documents/{$vendor->id}/{$type}", 'local');

        // Update or create document record
        VendorDocument::updateOrCreate(
            ['vendor_id' => $vendor->id, 'type' => $type],
            [
                'path'             => $path,
                'original_name'    => $file->getClientOriginalName(),
                'size'             => $file->getSize(),
                'mime'             => $file->getMimeType(),
                'status'           => 'pending',
                'rejection_reason' => null,
                'reviewed_by'      => null,
                'reviewed_at'      => null,
            ]
        );

        return back()->with('success', 'Dokumen ' . strtoupper($type) . ' berhasil diupload.');
    }

    public function submitForReview(Request $request)
    {
        $vendor        = auth()->user()->vendor;
        $requiredTypes = VendorDocument::requiredTypes($vendor->business_type ?? 'badan_usaha');

        // Check all required documents uploaded
        foreach ($requiredTypes as $type) {
            if (!$vendor->documents()->where('type', $type)->exists()) {
                return back()->with('error', "Dokumen {$type} belum diupload.");
            }
        }

        $this->service->submitForReview($vendor);

        return back()->with('success', 'Dokumen berhasil disubmit. Menunggu review admin (estimasi 1x24 jam).');
    }
}
