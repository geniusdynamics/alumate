<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationDigestJob;
use Illuminate\Console\Command;

class SendNotificationDigestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:send-digests 
                            {frequency : The digest frequency (daily or weekly)}
                            {--user= : Send digest for specific user ID only}';

    /**
     * The console command description.
     */
    protected $description = 'Send notification digests to users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $frequency = $this->argument('frequency');
        $userId = $this->option('user');

        // Validate frequency
        if (! in_array($frequency, ['daily', 'weekly'])) {
            $this->error('Frequency must be either "daily" or "weekly"');

            return self::FAILURE;
        }

        // Validate user ID if provided
        if ($userId && ! is_numeric($userId)) {
            $this->error('User ID must be a number');

            return self::FAILURE;
        }

        $this->info("Dispatching {$frequency} notification digest job...");

        try {
            SendNotificationDigestJob::dispatch($frequency, $userId ? (int) $userId : null);

            if ($userId) {
                $this->info("Digest job dispatched for user {$userId}");
            } else {
                $this->info("Digest job dispatched for all users with {$frequency} preference");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to dispatch digest job: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
