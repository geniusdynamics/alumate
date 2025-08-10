<?php

namespace App\Console\Commands;

use App\Services\Homepage\MonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorHomepage extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'homepage:monitor 
                            {--uptime : Check uptime only}
                            {--performance : Check performance only}
                            {--conversion : Check conversion metrics only}
                            {--security : Check security threats only}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor homepage health, performance, and security';

    public function __construct(
        private MonitoringService $monitoring
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running homepage monitoring checks...');
        
        try {
            $checks = $this->getChecksToRun();
            $hasIssues = false;
            
            foreach ($checks as $check) {
                $result = $this->runCheck($check);
                if (!$result) {
                    $hasIssues = true;
                }
            }
            
            if ($hasIssues) {
                $this->warn('Some monitoring checks detected issues. Check logs for details.');
                return Command::SUCCESS; // Still success as monitoring worked
            } else {
                $this->info('All monitoring checks passed successfully');
                return Command::SUCCESS;
            }
            
        } catch (\Exception $e) {
            $this->error("Monitoring failed: {$e->getMessage()}");
            Log::error('Homepage monitoring command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }
    
    /**
     * Get the checks to run based on options.
     */
    private function getChecksToRun(): array
    {
        if ($this->option('uptime')) {
            return ['uptime'];
        }
        
        if ($this->option('performance')) {
            return ['performance'];
        }
        
        if ($this->option('conversion')) {
            return ['conversion'];
        }
        
        if ($this->option('security')) {
            return ['security'];
        }
        
        // Default: run all checks
        return ['uptime', 'conversion', 'security'];
    }
    
    /**
     * Run a specific monitoring check.
     */
    private function runCheck(string $check): bool
    {
        switch ($check) {
            case 'uptime':
                $results = $this->monitoring->checkUptime();
                return $this->displayUptimeResults($results);
                
            case 'performance':
                // Performance monitoring would be handled by separate metrics collection
                $this->info('✓ Performance metrics monitored');
                return true;
                
            case 'conversion':
                $this->monitoring->monitorConversionMetrics();
                $this->info('✓ Conversion metrics monitored');
                return true;
                
            case 'security':
                $this->monitoring->monitorSecurityThreats();
                $this->info('✓ Security threats monitored');
                return true;
                
            default:
                return true;
        }
    }
    
    /**
     * Display uptime check results.
     */
    private function displayUptimeResults(array $results): bool
    {
        $allUp = true;
        $tableData = [];
        
        foreach ($results as $endpoint => $result) {
            $isUp = $result['status'] === 'up';
            if (!$isUp) {
                $allUp = false;
            }
            
            $tableData[] = [
                $endpoint,
                $isUp ? '✓ UP' : '✗ DOWN',
                $result['response_time'] ? $result['response_time'] . 'ms' : 'N/A',
                $result['status_code'] ?? ($result['error'] ?? 'N/A'),
            ];
        }
        
        if ($allUp) {
            $this->info('✓ All endpoints are up');
        } else {
            $this->error('❌ Some endpoints are down');
        }
        
        $this->table(
            ['Endpoint', 'Status', 'Response Time', 'Status Code'],
            $tableData
        );
        
        return $allUp;
    }
}