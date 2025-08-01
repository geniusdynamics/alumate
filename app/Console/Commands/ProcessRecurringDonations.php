<?php

namespace App\Console\Commands;

use App\Jobs\ProcessRecurringDonationsJob;
use Illuminate\Console\Command;

class ProcessRecurringDonations extends Command
{
    protected $signature = 'donations:process-recurring';
    protected $description = 'Process recurring donations that are due for payment';

    public function handle(): int
    {
        $this->info('Processing recurring donations...');

        ProcessRecurringDonationsJob::dispatch();

        $this->info('Recurring donations processing job has been queued.');

        return Command::SUCCESS;
    }
}