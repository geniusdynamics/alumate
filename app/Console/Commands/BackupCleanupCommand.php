<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupCleanupCommand extends Command
{
    protected $signature = 'backup:cleanup';

    protected $description = 'Clean up old backups based on retention policy';

    public function handle(BackupService $backupService): int
    {
        $this->info('Cleaning up old backups...');

        $results = $backupService->cleanupOldBackups();

        $this->info("Deleted {$results['deleted']} old backups");

        if (!empty($results['errors'])) {
            $this->warn('Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->error("  Backup {$error['backup_id']}: {$error['error']}");
            }
        }

        return self::SUCCESS;
    }
}
