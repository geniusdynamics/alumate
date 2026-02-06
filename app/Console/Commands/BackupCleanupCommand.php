<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command for cleaning up old backups
 */
class BackupCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:cleanup
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old backups based on retention policy';

    /**
     * Backup service instance
     */
    private BackupService $backupService;

    /**
     * Create a new command instance.
     */
    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Starting backup cleanup...');

        // Get retention policy
        $retentionPolicy = [
            'database' => config('production.backup.retention.database', 30),
            'files' => config('production.backup.retention.files', 90),
            'config' => config('production.backup.retention.config', 365),
        ];

        $this->info("Retention policy:");
        foreach ($retentionPolicy as $type => $days) {
            $this->info("  - {$type}: {$days} days");
        }

        if ($dryRun) {
            $this->warn('DRY RUN - No files will be deleted');
            // Just show what would be deleted
            $backups = $this->backupService->listBackups();
            $toDelete = [];

            foreach ($backups as $backup) {
                $backupAge = now()->diffInDays(\Carbon\Carbon::parse($backup['created_at']));
                $maxAge = $retentionPolicy[$backup['type']] ?? 30;

                if ($backupAge > $maxAge) {
                    $toDelete[] = $backup;
                }
            }

            if (empty($toDelete)) {
                $this->info('No backups would be deleted');
            } else {
                $this->warn('Backups that would be deleted:');
                foreach ($toDelete as $backup) {
                    $this->line("  - {$backup['path']} ({$backup['created_at']})");
                }
                $this->info('Total: ' . count($toDelete) . ' backups');
            }

            return Command::SUCCESS;
        }

        try {
            $result = $this->backupService->cleanupOldBackups($retentionPolicy);

            $this->info("✅ Cleanup completed!");
            $this->info("Deleted {$result['count']} backups");
            $this->info("Space freed: " . number_format($result['total_space_freed'] / 1024 / 1024, 2) . " MB");

            Log::info('Backup cleanup completed', $result);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Cleanup failed: {$e->getMessage()}");
            Log::error('Backup cleanup failed', ['error' => $e->getMessage()]);

            return Command::FAILURE;
        }
    }
}
