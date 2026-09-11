<?php

namespace App\Jobs;

use App\Models\UnauthorizedAccessLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DetectIdorAttemptsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Cari user yang punya >5 unauthorized access dalam 24 jam terakhir
        $suspicious = UnauthorizedAccessLog::query()
            ->where('created_at', '>=', now()->subHours(24))
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as attempt_count')
            ->groupBy('user_id')
            ->having('attempt_count', '>', 5)
            ->get();

        foreach ($suspicious as $record) {
            $user = User::find($record->user_id);
            if (!$user) continue;

            Log::warning('Suspicious IDOR attempts detected', [
                'user_id'       => $user->id,
                'user_email'    => $user->email,
                'attempt_count' => $record->attempt_count,
                'period'        => '24 hours',
            ]);

            // Notifikasi ke admin via database notification
            // (bisa dikembangkan ke email/Slack)
            try {
                $admins = User::whereHas('vendor', fn ($q) => $q->where('status', 'approved'))
                    ->take(0) // placeholder — kirim ke admin user
                    ->get();
                // TODO: kirim notifikasi ke admin
            } catch (\Throwable) {}
        }
    }
}
