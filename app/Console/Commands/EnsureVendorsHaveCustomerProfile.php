<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Console\Command;

class EnsureVendorsHaveCustomerProfile extends Command
{
    protected $signature   = 'vendors:ensure-customer-profile';
    protected $description = 'Buatkan customer profile untuk vendor yang belum punya, agar bisa browse/booking sebagai customer';

    public function handle(): int
    {
        // Semua user yang punya vendor profile tapi tidak punya customer profile
        $vendors = User::has('vendor')->doesntHave('customer')->get();

        if ($vendors->isEmpty()) {
            $this->info('Semua vendor sudah punya customer profile. Tidak ada yang perlu difix.');
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$vendors->count()} vendor tanpa customer profile. Membuat...");

        $bar = $this->output->createProgressBar($vendors->count());
        $bar->start();

        foreach ($vendors as $user) {
            Customer::create([
                'user_id'             => $user->id,
                'full_name'           => $user->name,
                'verification_status' => 'pending',
            ]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Selesai. Semua vendor sekarang punya customer profile.');

        return self::SUCCESS;
    }
}
