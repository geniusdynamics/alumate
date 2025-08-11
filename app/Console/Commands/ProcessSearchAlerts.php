<?php

namespace App\Console\Commands;

use App\Services\SearchService;
use Illuminate\Console\Command;

class ProcessSearchAlerts extends Command
{
    protected $signature = 'search:process-alerts';

    protected $description = 'Process and send search alerts to users';

    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        parent::__construct();
        $this->searchService = $searchService;
    }

    public function handle()
    {
        $this->info('Processing search alerts...');

        $alertsSent = $this->searchService->processSearchAlerts();

        $this->info("Processed and sent {$alertsSent} search alerts.");

        return 0;
    }
}
