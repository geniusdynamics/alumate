<?php

namespace App\Console\Commands;

use App\Services\Homepage\MonitoringService;
use Illuminate\Console\Command;

class HomepageUptimeCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'homepage:uptime-check
                          {--notify : Send notifications for any issues}
                          {--verbose : Show detailed output}';

    /**
     * The console command description.
     */
    protected $description = 'Check homepage uptime and performance';

    /**
     * Execute the console command.
     */
    public function handle(MonitoringService $monitoring): int
    {
        $this->info('Starting homepage uptime check...');

        try {
            $results = $monitoring->checkUptime();

            $allUp = true;
            foreach ($results as $endpoint => $result) {
                $status = $result['status'];
                $responseTime = $result['response_time'] ?? 'N/A';

                if ($status === 'up') {
                    $this->info("✅ {$endpoint}: UP ({$responseTime}ms)");
                } else {
                    $this->error("❌ {$endpoint}: DOWN");
                    $allUp = false;

                    if (isset($result['error'])) {
                        $this->error("   Error: {$result['error']}");
                    }

                    if (isset($result['status_code'])) {
                        $this->error("   Status Code: {$result['status_code']}");
                    }
                }

                if ($this->option('verbose') && isset($result['checked_at'])) {
                    $this->line("   Checked at: {$result['checked_at']}");
                }
            }

            if ($allUp) {
                $this->info('🎉 All homepage endpoints are operational!');

                return Command::SUCCESS;
            } else {
                $this->error('⚠️  Some homepage endpoints are experiencing issues.');

                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('Failed to check homepage uptime: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
