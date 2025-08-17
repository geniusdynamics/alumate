<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSearchAlerts as ProcessSearchAlertsJob;
use Illuminate\Console\Command;

class ProcessSearchAlerts extends Command
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
    protected $description = 'Process saved search alerts and send notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Dispatching search alerts processing job...');
        
        ProcessSearchAlertsJob::dispatch();
        
        $this->info('✅ Search alerts processing job dispatched successfully');
        
        return 0;
    }
}