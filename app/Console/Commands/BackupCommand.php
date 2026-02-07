<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupCommand extends Command
{
    protected $signature = 'backup:create 
                            {--type=database : Type of backup (database, files, all)}
                            {--tenant= : Tenant ID for tenant-specific backup}
                            {--subtype=scheduled : Backup subtype (manual, scheduled)}';

    protected $description = 'Create a backup of the application';

    public function handle(BackupService $backupService): int
    {
        $type = $this->option('type');
        $tenantId = $this->option('tenant');
        $subtype = $this->option('subtype');

        $this->info("Creating {$type} backup...");

        try {
            $tenant = $tenantId ? \App\Models\Tenant::find($tenantId) : null;

            if ($type === 'database' || $type === 'all') {
                $backup = $backupService->createDatabaseBackup($subtype, $tenant);
                $this->info("Database backup created: {$backup->filename}");
                $this->info("Size: {$backup->getFormattedSize()}");
            }

            if ($type === 'files' || $type === 'all') {
                $backup = $backupService->createFilesBackup($subtype);
                $this->info("Files backup created: {$backup->filename}");
                $this->info("Size: {$backup->getFormattedSize()}");
            }

            $this->info('Backup completed successfully!');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
