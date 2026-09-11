<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Payout;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPayoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Vendor $vendor,
        public $periodStart,
        public $periodEnd
    ) {}

    public function handle(): void
    {
        // Hitung total payout untuk booking completed dalam periode ini
        // yang belum masuk ke payout manapun (cek via periode payout yang sudah ada)
        $alreadyPayoutAmount = Payout::where('vendor_id', $this->vendor->id)
            ->whereIn('status', ['pending', 'paid'])
            ->where(function ($q) {
                $q->whereBetween('period_start', [$this->periodStart, $this->periodEnd])
                  ->orWhereBetween('period_end', [$this->periodStart, $this->periodEnd])
                  ->orWhere(function ($q2) {
                      $q2->where('period_start', '<=', $this->periodStart)
                         ->where('period_end', '>=', $this->periodEnd);
                  });
            })
            ->sum('amount');

        $totalEarned = Booking::where('vendor_id', $this->vendor->id)
            ->where('status', 'completed')
            ->whereBetween('end_at', [$this->periodStart, $this->periodEnd])
            ->whereHas('payment', fn ($q) => $q->where('status', 'paid'))
            ->sum('vendor_payout_amount');

        $totalAmount = max(0, $totalEarned - $alreadyPayoutAmount);

        if ($totalAmount > 0) {
            $payout = Payout::create([
                'vendor_id'    => $this->vendor->id,
                'period_start' => $this->periodStart,
                'period_end'   => $this->periodEnd,
                'amount'       => $totalAmount,
                'status'       => 'pending',
            ]);

            // Notify vendor
            try {
                $this->vendor->user->notify(
                    new \App\Notifications\PayoutCreatedNotification($payout)
                );
            } catch (\Throwable) {}
        }
    }
}
