<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\ComplaintResponse;
use App\Models\ComplaintResolution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ComplaintService
{
    public function submit(Booking $booking, User $customer, array $data): Complaint
    {
        return DB::transaction(function () use ($booking, $customer, $data) {
            $category = \App\Models\ComplaintCategory::findOrFail($data['category_id']);

            $complaint = Complaint::create([
                'id'                    => Str::uuid(),
                'reference'             => Complaint::generateReference(),
                'booking_id'            => $booking->id,
                'reporter_id'           => $customer->id,
                'vendor_id'             => $booking->vendor_id,
                'category_id'           => $category->id,
                'severity'              => $category->severity,
                'status'                => 'submitted',
                'description'           => $data['description'],
                'attachments'           => $data['attachments'] ?? null,
                'customer_demand'       => $data['customer_demand'],
                'demanded_refund_amount' => $data['demanded_refund_amount'] ?? null,
                'customer_demand_note'  => $data['customer_demand_note'] ?? null,
                'ip_address'            => request()->ip(),
            ]);

            $this->log($complaint, $customer, 'customer', 'submitted', ['category' => $category->name]);

            return $complaint;
        });
    }

    public function forward(Complaint $complaint, User $admin): void
    {
        DB::transaction(function () use ($complaint, $admin) {
            $slaHours = $complaint->category->vendor_sla_hours;
            $complaint->update([
                'status'       => 'forwarded_to_vendor',
                'forwarded_at' => now(),
                'vendor_due_at' => now()->addHours($slaHours),
                'admin_due_at'  => now()->addHours(48),
            ]);
            $this->log($complaint, $admin, 'admin', 'forwarded', ['sla_hours' => $slaHours]);

            // Notify vendor
            try {
                $complaint->vendor->user->notify(new \App\Notifications\Complaint\ComplaintForwardedToVendor($complaint));
            } catch (\Throwable) {}
        });
    }

    public function addResponse(Complaint $complaint, User $author, array $data): ComplaintResponse
    {
        return DB::transaction(function () use ($complaint, $author, $data) {
            $role = $this->getUserRole($author, $complaint);

            $response = ComplaintResponse::create([
                'complaint_id'  => $complaint->id,
                'author_id'     => $author->id,
                'author_role'   => $role,
                'visibility'    => $data['visibility'] ?? 'public',
                'message'       => $data['message'],
                'attachments'   => $data['attachments'] ?? null,
                'is_offer'      => $data['is_offer'] ?? false,
                'offer_payload' => $data['offer_payload'] ?? null,
            ]);

            // Saat vendor merespons → notifikasi admin
            if ($role === 'vendor' && $complaint->status === 'forwarded_to_vendor') {
                $complaint->update(['status' => 'vendor_responded']);

                $admins = \App\Models\User::whereDoesntHave('vendor')
                    ->whereDoesntHave('customer')
                    ->get();
                foreach ($admins as $admin) {
                    try {
                        $admin->notify(new \App\Notifications\Complaint\ComplaintVendorResponded($complaint));
                    } catch (\Throwable) {}
                }
            }

            // Saat admin merespons (public) → notifikasi ke pihak yang relevan
            if ($role === 'admin' && ($data['visibility'] ?? 'public') === 'public') {
                $isVendorReport = $complaint->reporter?->vendor !== null;

                if ($isVendorReport) {
                    // Laporan dari vendor → notifikasi ke vendor pelapor
                    try {
                        $complaint->reporter?->notify(
                            new \App\Notifications\Complaint\ComplaintAdminReplied($complaint, $response)
                        );
                    } catch (\Throwable) {}
                } else {
                    // Komplain dari customer → notifikasi ke customer pelapor
                    try {
                        $complaint->reporter?->notify(
                            new \App\Notifications\Complaint\ComplaintAdminReplied($complaint, $response)
                        );
                    } catch (\Throwable) {}
                    // Dan ke vendor yang terkena komplain
                    try {
                        $complaint->vendor?->user?->notify(
                            new \App\Notifications\Complaint\ComplaintAdminReplied($complaint, $response)
                        );
                    } catch (\Throwable) {}
                }
            }

            $this->log($complaint, $author, $role, 'responded', ['is_offer' => $response->is_offer]);

            return $response;
        });
    }

    public function resolve(Complaint $complaint, User $admin, array $data): ComplaintResolution
    {
        return DB::transaction(function () use ($complaint, $admin, $data) {
            $resolution = ComplaintResolution::create([
                'complaint_id'          => $complaint->id,
                'admin_id'              => $admin->id,
                'decision'              => $data['decision'],
                'refund_amount'         => $data['refund_amount'] ?? null,
                'vendor_penalty_amount' => $data['vendor_penalty_amount'] ?? null,
                'voucher_amount'        => $data['voucher_amount'] ?? null,
                'voucher_code'          => isset($data['voucher_amount']) ? 'VCH-' . strtoupper(Str::random(8)) : null,
                'vendor_suspend_days'   => $data['vendor_suspend_days'] ?? null,
                'reasoning'             => $data['reasoning'],
            ]);

            $complaint->update([
                'status'      => 'resolved',
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
            ]);

            // ── Otomatis buat CustomerRefund jika keputusan refund ────────────
            if (in_array($data['decision'], ['refund_full', 'refund_partial']) && $resolution->refund_amount > 0) {
                $booking  = $complaint->booking;
                $customer = $booking?->customer;

                if ($booking && $customer) {
                    // Cek apakah sudah ada refund dari komplain ini — bukan dari booking lain
                    // Gunakan notes sebagai penanda agar tidak duplikat jika admin klik 2x
                    $alreadyExists = \App\Models\CustomerRefund::where('booking_id', $booking->id)
                        ->where('notes', 'like', '%' . $complaint->reference . '%')
                        ->whereIn('status', ['pending', 'paid'])
                        ->exists();

                    if (!$alreadyExists) {
                        \App\Models\CustomerRefund::create([
                            'booking_id'        => $booking->id,
                            'customer_id'       => $customer->id,
                            'amount'            => $resolution->refund_amount,
                            'status'            => 'pending',
                            'bank_name'         => $customer->bank_name,
                            'bank_account_no'   => $customer->bank_account_no,
                            'bank_account_name' => $customer->bank_account_name,
                            'notes'             => 'Refund dari penyelesaian komplain ' . $complaint->reference,
                        ]);
                    }
                }
            }

            // Handle vendor suspension
            if ($data['decision'] === 'suspend_vendor' && !empty($data['vendor_suspend_days'])) {
                try {
                    app(\App\Services\Vendor\VendorVerificationService::class)
                        ->suspend($complaint->vendor, $admin, 'Pelanggaran: ' . $complaint->category->name);
                } catch (\Throwable) {}
            }

            $this->log($complaint, $admin, 'admin', 'resolved', ['decision' => $data['decision']]);

            // Notify reporter (customer atau vendor pelapor)
            try {
                $complaint->reporter?->notify(new \App\Notifications\Complaint\ComplaintResolved($complaint, $resolution));
            } catch (\Throwable) {}

            // Notify vendor yang terkena komplain (jika reporter bukan vendor itu sendiri)
            try {
                if ($complaint->vendor?->user && $complaint->vendor->user->id !== $complaint->reporter_id) {
                    $complaint->vendor->user->notify(new \App\Notifications\Complaint\ComplaintResolved($complaint, $resolution));
                }
            } catch (\Throwable) {}

            return $resolution;
        });
    }

    public function reject(Complaint $complaint, User $admin, string $reason): void
    {
        DB::transaction(function () use ($complaint, $admin, $reason) {
            $complaint->update([
                'status'      => 'rejected',
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
                'admin_notes' => $reason,
            ]);
            $this->log($complaint, $admin, 'admin', 'rejected', ['reason' => $reason]);

            try {
                $complaint->reporter->notify(new \App\Notifications\Complaint\ComplaintRejected($complaint, $reason));
            } catch (\Throwable) {}
        });
    }

    private function getUserRole(User $user, Complaint $complaint): string
    {
        // Cek apakah user adalah vendor (punya vendor profile)
        $isVendorUser = $user->vendor !== null;

        if ($user->id === $complaint->reporter_id) {
            // Reporter bisa customer atau vendor — tentukan dari profil
            return $isVendorUser ? 'vendor' : 'customer';
        }

        if ($user->vendor && $user->vendor->id === $complaint->vendor_id) {
            return 'vendor';
        }

        return 'admin';
    }

    private function log(Complaint $complaint, ?User $actor, string $role, string $action, array $context = []): void
    {
        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id'     => $actor?->id,
            'actor_role'   => $role,
            'action'       => $action,
            'context'      => $context ?: null,
            'ip_address'   => request()->ip(),
        ]);
    }
}
