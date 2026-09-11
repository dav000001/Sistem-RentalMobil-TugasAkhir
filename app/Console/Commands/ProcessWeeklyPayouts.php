<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPayoutJob;
use App\Models\Vendor;
use Illuminate\Console\Command;

class ProcessWeeklyPayouts extends Command
{
    protected $signature = 'payouts:process-weekly';
    protected $description = 'Process weekly payouts for all vendors';

    public function handle()
    {
        $periodEnd = now()->subDay()->endOfDay();
        $periodStart = now()->subWeek()->startOfDay();

        $vendors = Vendor::where('status', 'approved')->get();

        foreach ($vendors as $vendor) {
            ProcessPayoutJob::dispatch($vendor, $periodStart, $periodEnd);
        }

        $this->info("Dispatched payout jobs for {$vendors->count()} vendors");
    }
}
