<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\VendorPlan;
use App\Http\Controllers\Controller;
use App\Services\VendorPlanService;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        // Guard vendor (login via Filament panel)
        $user = auth('vendor')->user();

        if (!$user) {
            return redirect('/vendor/login');
        }

        $vendor = $user->vendor;

        if (!$vendor) {
            abort(403, 'Anda tidak memiliki akun vendor.');
        }

        $plans = [
            VendorPlan::Free,
            VendorPlan::Basic,
            VendorPlan::Premium,
        ];

        return view('vendor.plans.index', compact('vendor', 'plans'));
    }

    public function upgrade(Request $request)
    {
        $user = auth('vendor')->user();

        if (!$user) {
            return redirect('/vendor/login');
        }

        $validated = $request->validate([
            'plan' => ['required', 'in:free,basic,premium'],
        ]);

        $vendor = $user->vendor;

        if (!$vendor) {
            abort(403, 'Anda tidak memiliki akun vendor.');
        }

        $newPlan = VendorPlan::from($validated['plan']);

        // Tidak bisa downgrade ke free jika sudah punya lebih dari 3 mobil
        if ($newPlan === VendorPlan::Free && $vendor->cars()->count() > 3) {
            return back()->withErrors([
                'plan' => 'Tidak dapat downgrade ke paket Free karena Anda memiliki lebih dari 3 mobil. Hapus beberapa mobil terlebih dahulu.'
            ]);
        }

        // Tidak bisa downgrade ke basic jika sudah punya lebih dari 10 mobil
        if ($newPlan === VendorPlan::Basic && $vendor->cars()->count() > 10) {
            return back()->withErrors([
                'plan' => 'Tidak dapat downgrade ke paket Basic karena Anda memiliki lebih dari 10 mobil. Hapus beberapa mobil terlebih dahulu.'
            ]);
        }

        // Gunakan VendorPlanService untuk konsistensi (buat tagihan jika perlu)
        app(VendorPlanService::class)->changePlan($vendor, $newPlan);

        $message = match ($newPlan) {
            VendorPlan::Free    => 'Paket berhasil diubah ke Free. Komisi Anda sekarang 12%.',
            VendorPlan::Basic   => 'Paket berhasil diupgrade ke Basic! Komisi Anda sekarang 9%. Tagihan Rp 99.000 akan segera dikirim.',
            VendorPlan::Premium => 'Paket berhasil diupgrade ke Premium! Komisi Anda sekarang 5%. Tagihan Rp 249.000 akan segera dikirim.',
        };

        return redirect()->route('vendor.plans.index')->with('success', $message);
    }
}
