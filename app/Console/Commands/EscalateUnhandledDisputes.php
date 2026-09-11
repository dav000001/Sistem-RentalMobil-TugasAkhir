<?php

namespace App\Console\Commands;

use App\Models\Dispute;
use App\Models\User;
use App\Notifications\DisputeEscalatedNotification;
use Illuminate\Console\Command;

class EscalateUnhandledDisputes extends Command
{
    /**
     * Batas hari sebelum dispute di-escalate.
     * Bisa dioverride via env DISPUTE_ESCALATE_DAYS (default: 3).
     */
    protected $signature   = 'disputes:auto-escalate {--days= : Jumlah hari batas sebelum escalate}';
    protected $description = 'Escalate disputes yang belum ditangani admin dalam X hari ke in_review dan kirim notifikasi ulang ke admin';

    public function handle(): void
    {
        $days = (int) ($this->option('days') ?? env('DISPUTE_ESCALATE_DAYS', 3));

        // Cari dispute berstatus 'open' yang sudah melebihi batas hari
        $disputes = Dispute::where('status', 'open')
            ->where('created_at', '<', now()->subDays($days))
            ->with(['booking.customer.user', 'booking.vendor.user', 'openedBy'])
            ->get();

        if ($disputes->isEmpty()) {
            $this->info('Tidak ada dispute yang perlu di-escalate.');
            return;
        }

        $escalated = 0;

        foreach ($disputes as $dispute) {
            // Ubah status ke in_review (auto-assigned, admin_id tetap null sampai admin ambil alih)
            $dispute->update(['status' => 'in_review']);

            // Notifikasi ulang ke semua admin
            User::where('role', 'admin')->get()->each(function ($admin) use ($dispute, $days) {
                try {
                    $admin->notify(new DisputeEscalatedNotification($dispute, $days));
                } catch (\Throwable) {}
            });

            $escalated++;
            $this->line("  → Dispute #{$dispute->id} (Booking: {$dispute->booking?->code}) di-escalate ke in_review.");
        }

        $this->info("Total {$escalated} dispute di-escalate.");
    }
}
