<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class CleanupNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:cleanup {--days=90 : Number of days to keep notifications}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old notifications from the database';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $days = (int) $this->option('days');
        
        if ($days < 1) {
            $this->error('Days must be a positive integer');
            return self::FAILURE;
        }

        $this->info("Cleaning up notifications older than {$days} days...");

        try {
            $deletedCount = $notificationService->cleanupOldNotifications($days);
            
            $this->info("Successfully deleted {$deletedCount} old notifications.");
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to cleanup notifications: {$e->getMessage()}");
            
            return self::FAILURE;
        }
    }
}