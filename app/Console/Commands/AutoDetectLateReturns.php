<?php

namespace App\Console\Commands;

use App\Services\AutoLateDetectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoDetectLateReturns extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bookings:auto-detect-late
                          {--dry-run : Show what would be detected without sending notifications}
                          {--verbose : Show detailed output}
                          {--force : Force detection even if recently run}';

    /**
     * The console command description.
     */
    protected $description = 'Automatically detect late returns and send notifications to vendors';

    private AutoLateDetectionService $autoLateDetection;

    public function __construct(AutoLateDetectionService $autoLateDetection)
    {
        parent::__construct();
        $this->autoLateDetection = $autoLateDetection;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Starting auto late return detection...');
        
        try {
            // Check if recently run (unless forced)
            if (!$this->option('force') && $this->wasRecentlyRun()) {
                $this->warn('Auto detection was run recently. Use --force to override.');
                return Command::SUCCESS;
            }

            $startTime = microtime(true);
            
            // Run detection
            if ($this->option('dry-run')) {
                $this->info('🔍 DRY RUN MODE - No notifications will be sent');
                $results = $this->runDryRun();
            } else {
                $results = $this->autoLateDetection->detectAllLateReturns();
                $this->recordRun();
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Display results
            $this->displayResults($results, $executionTime);

            // Log results
            Log::info('Auto late detection completed', array_merge($results, [
                'execution_time_ms' => $executionTime,
                'dry_run' => $this->option('dry-run')
            ]));

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('❌ Auto late detection failed: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            Log::error('Auto late detection command failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Run dry run mode to preview what would be detected
     */
    private function runDryRun(): array
    {
        $this->info('Scanning ongoing bookings...');
        
        $bookings = \App\Models\Booking::where('status', 'ongoing')
            ->where('end_at', '<=', now()->addHours(2))
            ->with(['customer.user', 'vendor.user', 'car', 'lateReturnReport'])
            ->get();

        $results = [
            'checked' => 0,
            'late_detected' => 0,
            'notifications_sent' => 0,
            'errors' => [],
            'detections' => []
        ];

        foreach ($bookings as $booking) {
            $results['checked']++;
            
            try {
                $detection = $this->autoLateDetection->detectLateReturn($booking);
                
                if ($detection['is_late'] || $detection['will_be_late']) {
                    $results['late_detected']++;
                    $results['detections'][] = [
                        'booking_id' => $booking->id,
                        'customer' => $booking->customer->user->name,
                        'car' => "{$booking->car->brand} {$booking->car->model}",
                        'end_time' => $booking->end_at->format('d M Y H:i'),
                        'is_late' => $detection['is_late'],
                        'severity' => $detection['severity'],
                        'confidence' => $detection['confidence'],
                        'reasons' => $detection['reasons'],
                        'estimated_late_hours' => $detection['estimated_late_hours']
                    ];

                    if ($this->option('verbose')) {
                        $this->displayDetection($booking, $detection);
                    }
                }

            } catch (\Throwable $e) {
                $results['errors'][] = "Booking #{$booking->id}: " . $e->getMessage();
                
                if ($this->option('verbose')) {
                    $this->error("Error detecting booking #{$booking->id}: " . $e->getMessage());
                }
            }
        }

        return $results;
    }

    /**
     * Display detection details for verbose mode
     */
    private function displayDetection($booking, array $detection): void
    {
        $status = $detection['is_late'] ? '🚨 LATE' : '⚠️  POTENTIAL LATE';
        $severity = strtoupper($detection['severity']);
        $confidence = $detection['confidence'];

        $this->line('');
        $this->line("─────────────────────────────────────");
        $this->line("📋 Booking #{$booking->id} - {$status}");
        $this->line("👤 Customer: {$booking->customer->user->name}");
        $this->line("🚗 Car: {$booking->car->brand} {$booking->car->model}");
        $this->line("⏰ End Time: {$booking->end_at->format('d M Y H:i')}");
        $this->line("🎯 Confidence: {$confidence}%");
        $this->line("⚡ Severity: {$severity}");
        
        if ($detection['estimated_late_hours'] > 0) {
            $this->line("⏱️  Estimated Late: {$detection['estimated_late_hours']} hours");
        }

        if (!empty($detection['reasons'])) {
            $this->line("📝 Reasons:");
            foreach ($detection['reasons'] as $reason) {
                $this->line("   • {$reason}");
            }
        }

        if (!empty($detection['recommendations'])) {
            $this->line("💡 Recommendations:");
            foreach (array_slice($detection['recommendations'], 0, 3) as $recommendation) {
                $this->line("   • {$recommendation}");
            }
        }
    }

    /**
     * Display command results
     */
    private function displayResults(array $results, float $executionTime): void
    {
        $this->line('');
        $this->line('📊 <fg=cyan>DETECTION RESULTS</fg=cyan>');
        $this->line('─────────────────────────────────────');
        
        $this->info("✅ Bookings checked: {$results['checked']}");
        
        if ($results['late_detected'] > 0) {
            $this->warn("⚠️  Late/potential late detected: {$results['late_detected']}");
        } else {
            $this->info("✅ No late returns detected");
        }

        if (!$this->option('dry-run')) {
            $this->info("📧 Notifications sent: {$results['notifications_sent']}");
        }

        if (!empty($results['errors'])) {
            $this->error("❌ Errors occurred: " . count($results['errors']));
            
            if ($this->option('verbose')) {
                foreach ($results['errors'] as $error) {
                    $this->error("   • {$error}");
                }
            }
        }

        $this->line("⏱️  Execution time: {$executionTime}ms");

        // Show detection summary in dry run mode
        if ($this->option('dry-run') && !empty($results['detections'])) {
            $this->line('');
            $this->line('📋 <fg=yellow>DETECTION SUMMARY (DRY RUN)</fg=yellow>');
            $this->line('─────────────────────────────────────');

            $table = [];
            foreach ($results['detections'] as $detection) {
                $status = $detection['is_late'] ? 'LATE' : 'POTENTIAL';
                $severity = strtoupper($detection['severity']);
                
                $table[] = [
                    $detection['booking_id'],
                    substr($detection['customer'], 0, 20),
                    substr($detection['car'], 0, 15),
                    $status,
                    $severity,
                    $detection['confidence'] . '%',
                    $detection['estimated_late_hours'] . 'h'
                ];
            }

            $this->table([
                'ID', 'Customer', 'Car', 'Status', 'Severity', 'Confidence', 'Late Hours'
            ], $table);
        }

        $this->line('');
        $this->info('🏁 Auto late detection completed successfully!');
    }

    /**
     * Check if command was run recently (within last 15 minutes)
     */
    private function wasRecentlyRun(): bool
    {
        $cacheKey = 'auto_late_detection_last_run';
        $lastRun = cache()->get($cacheKey);
        
        if (!$lastRun) {
            return false;
        }

        $lastRunTime = \Carbon\Carbon::parse($lastRun);
        return $lastRunTime->diffInMinutes(now()) < 15;
    }

    /**
     * Record when command was run
     */
    private function recordRun(): void
    {
        $cacheKey = 'auto_late_detection_last_run';
        cache()->put($cacheKey, now()->toISOString(), 3600); // Cache for 1 hour
    }
}