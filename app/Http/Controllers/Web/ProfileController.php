<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        $customer = $user->customer;
        return view('public.profile.edit', compact('user', 'customer'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        auth()->user()->update($validated);
        auth()->user()->customer->update(['full_name' => $validated['name']]);

        return back()->with('success', 'Profil berhasil diperbarui');
    }

    public function updateBank(Request $request)
    {
        $validated = $request->validate([
            'bank_name'         => ['required', 'string', 'max:50'],
            'bank_account_no'   => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s]+$/'],
            'bank_account_name' => ['required', 'string', 'max:100'],
        ], [
            'bank_account_no.regex' => 'Nomor rekening/HP hanya boleh berisi angka.',
        ]);

        auth()->user()->customer->update($validated);

        return back()->with('bank_success', 'Informasi rekening berhasil disimpan.');
    }

    public function submitVerification(Request $request)
    {
        $validated = $request->validate([
            'ktp_file'    => ['required', 'image', 'max:5120'],
            'sim_file'    => ['required', 'image', 'max:5120'],
            'selfie_file' => ['required', 'image', 'max:5120'],
        ]);

        $customer = auth()->user()->customer;

        // Store files (simplified - in production use S3)
        if ($request->hasFile('ktp_file')) {
            $path = $request->file('ktp_file')->store('verifications/ktp', 'public');
            $customer->ktp_url = $path;
        }

        if ($request->hasFile('sim_file')) {
            $path = $request->file('sim_file')->store('verifications/sim', 'public');
            $customer->sim_url = $path;
        }

        if ($request->hasFile('selfie_file')) {
            $path = $request->file('selfie_file')->store('verifications/selfie', 'public');
            $customer->selfie_url = $path;
        }

        $customer->verification_status = 'pending';
        $customer->save();

        return back()->with('success', 'Dokumen verifikasi berhasil diunggah. Menunggu persetujuan admin.');
    }
}
