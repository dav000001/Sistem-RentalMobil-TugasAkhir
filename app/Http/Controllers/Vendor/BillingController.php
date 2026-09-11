<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPackage;
use App\Models\VendorSubscription;
use App\Models\VendorSubscriptionUpgradeRequest;
use App\Services\VendorSubscriptionService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(private VendorSubscriptionService $service) {}

    private function vendor()
    {
        return auth('vendor')->user()?->vendor;
    }

    public function index()
    {
        $vendor = $this->vendor();
        if (!$vendor) return redirect('/vendor/login');

        $packages        = SubscriptionPackage::where('is_active', true)->orderBy('rank')->get();
        $currentSub      = $this->service->getCurrent($vendor);
        $subscriptions   = $vendor->subscriptions()->with('package')->latest()->take(20)->get();
        $pendingRequests = $vendor->upgradeRequests()->where('status', 'pending')->with('targetPackage')->get();

        // Hitung check per paket untuk UI
        $packageChecks = $packages->mapWithKeys(function ($pkg) use ($vendor, $currentSub) {
            $check = $this->service->canChoosePackage($vendor, $pkg);
            $proration = null;
            if ($check['mode'] === 'upgrade' && $currentSub) {
                $proration = $this->service->calculateUpgradeAmount($currentSub, $pkg);
            }
            return [$pkg->id => array_merge($check, ['proration_amount' => $proration])];
        });

        return view('vendor.billing.index', compact(
            'vendor', 'packages', 'currentSub', 'subscriptions', 'pendingRequests', 'packageChecks'
        ));
    }

    public function purchase(Request $request)
    {
        $vendor = $this->vendor();
        if (!$vendor) return redirect('/vendor/login');

        $validated = $request->validate([
            'package_id' => ['required', 'exists:subscription_packages,id'],
        ]);

        $package = SubscriptionPackage::findOrFail($validated['package_id']);
        $check   = $this->service->canChoosePackage($vendor, $package);

        if (!$check['allowed']) {
            return back()->withErrors(['package_id' => $check['reason']]);
        }

        try {
            $sub = $this->service->purchase($vendor, $package, 'manual');

            // Free → langsung aktif
            if ($package->price_per_month === 0) {
                return redirect()->route('vendor.billing.index')
                    ->with('success', "Paket {$package->name} berhasil diaktifkan!");
            }

            // Berbayar → arahkan ke halaman pembayaran
            return redirect()->route('vendor.billing.payment', $sub->uuid)
                ->with('info', "Paket {$package->name} dipilih. Selesaikan pembayaran untuk mengaktifkan.");

        } catch (\RuntimeException $e) {
            return back()->withErrors(['package_id' => $e->getMessage()]);
        }
    }

    public function payment(VendorSubscription $subscription)
    {
        $vendor = $this->vendor();
        if (!$vendor || $subscription->vendor_id !== $vendor->id) abort(403);
        if ($subscription->status !== 'pending_payment') {
            return redirect()->route('vendor.billing.index')
                ->with('info', 'Pembayaran sudah diproses.');
        }

        $subscription->load('package');
        return view('vendor.billing.payment', compact('subscription'));
    }



    /**
     * Vendor upload bukti transfer pembayaran paket.
     */
    public function uploadPaymentProof(Request $request, VendorSubscription $subscription)
    {
        $vendor = $this->vendor();
        if (!$vendor || $subscription->vendor_id !== $vendor->id) abort(403);

        if ($subscription->status !== 'pending_payment') {
            return back()->with('error', 'Pembayaran sudah diproses.');
        }

        $request->validate([
            'payment_proof'   => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'transfer_ref'    => ['nullable', 'string', 'max:100'],
        ], [
            'payment_proof.required' => 'Bukti transfer wajib diupload.',
            'payment_proof.mimes'    => 'Format harus JPG atau PNG.',
            'payment_proof.max'      => 'Ukuran maksimal 2MB.',
        ]);

        $path = $request->file('payment_proof')->store('billing-proofs', 'public');

        // Simpan bukti ke subscription
        $subscription->update([
            'payment_proof' => $path,
            'transfer_ref'  => $request->input('transfer_ref'),
        ]);

        // Notifikasi ke admin
        $admins = \App\Models\User::whereDoesntHave('vendor')->whereDoesntHave('customer')->get();
        foreach ($admins as $admin) {
            try {
                $admin->notify(new \App\Notifications\VendorPaymentProofUploadedNotification($subscription));
            } catch (\Throwable) {}
        }

        return back()->with('success', 'Bukti transfer berhasil dikirim. Admin akan memverifikasi dalam 1×24 jam.');
    }

    public function toggleAutoRenew(Request $request)
    {
        $vendor = $this->vendor();
        if (!$vendor) return redirect('/vendor/login');

        $sub = $this->service->getCurrent($vendor);
        if ($sub) {
            $sub->update(['auto_renew' => !$sub->auto_renew]);
        }

        return back()->with('success', 'Pengaturan auto-renew diperbarui.');
    }

    public function storeUpgradeRequest(Request $request)
    {
        $vendor = $this->vendor();
        if (!$vendor) return redirect('/vendor/login');

        $validated = $request->validate([
            'target_package_id' => ['required', 'exists:subscription_packages,id'],
            'type'              => ['required', 'in:downgrade_early,cancel_early'],
            'reason'            => ['required', 'string', 'min:50'],
        ]);

        $currentSub = $this->service->getCurrent($vendor);

        VendorSubscriptionUpgradeRequest::create([
            'vendor_id'               => $vendor->id,
            'current_subscription_id' => $currentSub?->id,
            'target_package_id'       => $validated['target_package_id'],
            'type'                    => $validated['type'],
            'reason'                  => $validated['reason'],
            'status'                  => 'pending',
        ]);

        return back()->with('success', 'Permintaan perubahan paket berhasil dikirim. Admin akan merespons dalam 48 jam.');
    }
}
