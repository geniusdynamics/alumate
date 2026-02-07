<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command for restoring backups
 */
class BackupRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:restore
                            {type : Backup type to restore (database, files, config)}
                            {path : Path to backup file}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     */
    protected $description = 'Restore backup files';

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
        $type = $this->argument('type');
        $path = $this->argument('path');
        $force = $this->option('force');

        $this->info("Starting restore for {$type} backup from: {$path}");

        // Confirm if not forced
        if (! $force) {
            if (! $this->confirm('Are you sure you want to restore this backup? Current data will be overwritten.')) {
                $this->info('Restore cancelled.');

                return Command::SUCCESS;
            }
        }

        try {
            $result = match ($type) {
                'database' => $this->backupService->restoreDatabase($path),
                'files' => $this->backupService->restoreFiles($path),
                default => throw new \InvalidArgumentException("Unknown backup type: {$type}"),
            };

            if ($result['status'] === 'completed') {
                $this->info('✅ Restore completed successfully!');
                $this->info("Message: {$result['message']}");

                Log::info('Backup restore completed', [
                    'type' => $type,
                    'path' => $path,
                    'result' => $result,
                ]);

                return Command::SUCCESS;
            } else {
                $this->error('❌ Restore failed!');
                $this->error("Error: {$result['error']}");

                Log::error('Backup restore failed', [
                    'type' => $type,
                    'path' => $path,
                    'error' => $result['error'],
                ]);

                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("Restore failed: {$e->getMessage()}");
            Log::error('Backup restore failed', [
                'type' => $type,
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
