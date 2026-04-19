<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanUpLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:cleanup
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Force deletion even in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old logs based on retention policies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting log cleanup process...');

        if ($this->option('dry-run')) {
            $this->info('DRY RUN MODE: No logs will be deleted');
        }

        $deletedCount = 0;

        try {
            if ($this->option('dry-run')) {
                // Just count what would be deleted
                $wouldDelete = $this->countDeletableLogs();
                $this->info("Would delete approximately {$wouldDelete} logs based on retention policies");
            } else {
                $deletedCount = ActivityLog::cleanLogsByRetention();
                $this->info("Successfully deleted {$deletedCount} logs");
            }

            $retentionConfig = ActivityLog::getRetentionConfig();
            $this->info('Current retention configuration:');
            foreach ($retentionConfig as $key => $value) {
                $this->info("- {$key}: {$value}");
            }

        } catch (\Exception $e) {
            $this->error('Error during log cleanup: ' . $e->getMessage());
            Log::error('Log cleanup command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }

        $this->info('Log cleanup process completed.');
        return 0;
    }

    /**
     * Count how many logs would be deleted without actually deleting them
     */
    private function countDeletableLogs(): int
    {
        $count = 0;

        // Count low severity logs older than 30 days
        $lowSeverityCutoff = now()->subDays(config('logging.retention.low_severity_days', 30));
        $count += \App\Models\ActivityLog::where('severity', \App\Models\ActivityLog::SEVERITY_LOW)
            ->where('performed_at', '<', $lowSeverityCutoff)
            ->count();

        // Count medium severity logs older than 90 days
        $mediumSeverityCutoff = now()->subDays(config('logging.retention.medium_severity_days', 90));
        $count += \App\Models\ActivityLog::where('severity', \App\Models\ActivityLog::SEVERITY_MEDIUM)
            ->where('performed_at', '<', $mediumSeverityCutoff)
            ->count();

        // Count high severity logs older than 180 days (excluding critical and security)
        $highSeverityCutoff = now()->subDays(config('logging.retention.high_severity_days', 180));
        $count += \App\Models\ActivityLog::where('severity', \App\Models\ActivityLog::SEVERITY_HIGH)
            ->where('performed_at', '<', $highSeverityCutoff)
            ->where('category', '!=', \App\Models\ActivityLog::CATEGORY_SECURITY)
            ->count();

        // Count security category logs older than 365 days (excluding critical)
        $securityCutoff = now()->subDays(config('logging.retention.security_category_days', 365));
        $count += \App\Models\ActivityLog::where('category', \App\Models\ActivityLog::CATEGORY_SECURITY)
            ->where('performed_at', '<', $securityCutoff)
            ->where('severity', '!=', \App\Models\ActivityLog::SEVERITY_CRITICAL)
            ->count();

        return $count;
    }
}
