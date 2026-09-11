<?php

namespace App\Console\Commands;

use App\Services\VendorPlanService;
use Illuminate\Console\Command;

class ProcessVendorPlanBilling extends Command
{
    protected $signature = 'vendor:plan-billing
                            {--generate : Generate tagihan bulanan untuk vendor yang akan expired}
                            {--expire   : Downgrade vendor yang belum bayar setelah grace period}
                            {--all      : Jalankan keduanya}';

    protected $description = 'Proses tagihan paket vendor bulanan';

    public function handle(VendorPlanService $service): int
    {
        $runGenerate = $this->option('generate') || $this->option('all');
        $runExpire   = $this->option('expire')   || $this->option('all');

        if (!$runGenerate && !$runExpire) {
            $this->error('Gunakan --generate, --expire, atau --all');
            return self::FAILURE;
        }

        if ($runGenerate) {
            $count = $service->generateMonthlyBills();
            $this->info("✅ {$count} tagihan bulanan berhasil dibuat.");
        }

        if ($runExpire) {
            $count = $service->expireUnpaidPlans(graceDays: 7);
            $this->info("⚠️  {$count} vendor didowngrade ke Free karena tagihan belum dibayar.");
        }

        return self::SUCCESS;
    }
}
