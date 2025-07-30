<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSearchAlertsJob;
use Illuminate\Console\Command;

class ProcessSearchAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:process-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process search alerts and send notifications for new results';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing search alerts...');

        try {
            ProcessSearchAlertsJob::dispatch();
            
            $this->info('✅ Search alerts processing job dispatched successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to dispatch search alerts job: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}