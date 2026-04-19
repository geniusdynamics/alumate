<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command for running scheduled backup operations
 *
 * This command should be called by Laravel's scheduler
 */
class BackupSchedulerCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:schedule {--type=all : Backup type (database, files, config, all)}';

    /**
     * The console command description.
     */
    protected $description = 'Execute scheduled backup operations';

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
        $type = $this->option('type');

        $this->info('Starting scheduled backup...');

        try {
            if ($type === 'all') {
                // Run full backup
                $result = $this->backupService->createFullBackup();
            } else {
                // Run specific backup type
                $result = match ($type) {
                    'database' => $this->backupService->createDatabaseBackup(),
                    'files' => $this->backupService->createFilesBackup(),
                    'config' => $this->backupService->createConfigBackup(),
                    default => throw new \InvalidArgumentException("Unknown backup type: {$type}"),
                };
            }

            $this->displayResult($result);

            Log::info('Scheduled backup completed', ['type' => $type, 'result' => $result]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            Log::error('Scheduled backup failed', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Display backup result
     */
    private function displayResult(array $result): void
    {
        $this->info("Backup completed with status: {$result['status']}");

        if (isset($result['database']) && $result['database']['status'] === 'completed') {
            $this->info("✅ Database backup: {$result['database']['path']}");
        }

        if (isset($result['files']) && $result['files']['status'] === 'completed') {
            $this->info("✅ Files backup: {$result['files']['path']}");
        }

        if (isset($result['config']) && $result['config']['status'] === 'completed') {
            $this->info("✅ Config backup: {$result['config']['path']}");
        }

        if (! empty($result['errors'])) {
            $this->warn('Errors encountered:');
            foreach ($result['errors'] as $error) {
                $this->error("  - {$error}");
            }
        }
    }
}
