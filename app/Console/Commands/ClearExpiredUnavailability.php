<?php

namespace App\Console\Commands;

use App\Models\Car;
use Illuminate\Console\Command;

class ClearExpiredUnavailability extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cars:clear-expired-unavailability';

    /**
     * The console command description.
     */
    protected $description = 'Clear unavailability status for cars where unavailable_until date has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredCars = Car::where('status', 'unavailable')
            ->whereNotNull('unavailable_until')
            ->where('unavailable_until', '<', now()->toDateString())
            ->get();

        if ($expiredCars->isEmpty()) {
            $this->info('No expired unavailability found.');
            return;
        }

        $count = 0;
        foreach ($expiredCars as $car) {
            $car->update([
                'status' => 'published',
                'unavailability_reason' => null,
                'unavailability_notes' => null,
                'unavailable_until' => null,
            ]);
            $count++;
            
            $this->info("Cleared unavailability for car: {$car->brand} {$car->model} ({$car->plate_number})");
        }

        $this->info("Successfully cleared unavailability for {$count} cars.");
    }
}