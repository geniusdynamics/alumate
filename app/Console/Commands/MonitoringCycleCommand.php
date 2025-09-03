<?php

namespace App\Console\Commands;

use App\Services\ProductionMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Console command to run production monitoring cycles
 */
class MonitoringCycleCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'monitoring:cycle
                            {--frequency=daily : How often to run (daily|hourly|realtime)}
                            {--alert-threshold=critical : Alert threshold level (low|medium|high|critical)}
                            {--dry-run : Run without sending alerts}';

    /**
     * The console command description.
     */
    protected $description = 'Execute a complete production monitoring cycle with alerts and dashboard updates';

    protected ProductionMonitoringService $monitoringService;

    public function __construct(ProductionMonitoringService $monitoringService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $frequency = $this->option('frequency');
        $alertThreshold = $this->option('alert-threshold');
        $dryRun = $this->option('dry-run');

        $this->info("Starting monitoring cycle with frequency: {$frequency}");

        if ($dryRun) {
            $this->warn('Running in dry-run mode - no alerts will be sent');
        }

        try {
            $this->info('Executing monitoring cycle...');
            $results = $this->monitoringService->executeMonitoringCycle();

            $this->info('✓ Monitoring cycle completed successfully');
            $this->displayResults($results);

            // Handle alerts based on threshold
            $this->handleAlerts($results['alerts'] ?? [], $alertThreshold, $dryRun);

            // Update dashboard cache
            $this->info('Updating real-time dashboard data...');
            $dashboardData = $this->monitoringService->getDashboardData();
            $this->info('✓ Dashboard updated');

            // Log summary
            Log::info('Monitoring cycle completed', [
                'cycle_id' => $results['cycle_id'] ?? null,
                'execution_time' => $results['execution_time'] ?? 0,
                'alerts_count' => count($results['alerts']['details'] ?? []),
            ]);

        } catch (\Exception $e) {
            $this->error("Monitoring cycle failed: {$e->getMessage()}");
            Log::error('Monitoring cycle command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }

        $this->info('Monitoring cycle completed successfully!');
        return Command::SUCCESS;
    }

    /**
     * Display monitoring results in console
     */
    private function displayResults(array $results): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Cycle ID', $results['cycle_id'] ?? 'N/A'],
                ['Execution Time', sprintf('%.2f seconds', $results['execution_time'] ?? 0)],
                ['Timestamp', $results['timestamp'] ?? 'N/A'],
            ]
        );

        // Display alert summary
        if (isset($results['alerts'])) {
            $alerts = $results['alerts'];
            $this->info("\nAlert Summary:");
            $this->table(
                ['Priority', 'Count'],
                [
                    ['Critical', $alerts['critical'] ?? 0],
                    ['Medium', $alerts['medium'] ?? 0],
                    ['Low', $alerts['low'] ?? 0],
                    ['Total', $alerts['total'] ?? 0],
                ]
            );
        }

        // Display system health
        if (isset($results['system_health'])) {
            $health = $results['system_health'];
            $this->info("\nSystem Health:");
            foreach ($health as $service => $status) {
                $statusIcon = ($status['status'] ?? 'unknown') === 'healthy' ? '✅' : '❌';
                $this->line("  {$statusIcon} {$service}: " . ($status['status'] ?? 'unknown'));
            }
        }
    }

    /**
     * Handle alert processing based on threshold
     */
    private function handleAlerts(array $alerts, string $threshold, bool $dryRun): void
    {
        if ($dryRun) {
            $this->warn('Skipping alert processing in dry-run mode');
            return;
        }

        $details = $alerts['details'] ?? [];

        $priorities = [
            'critical' => 5,
            'high' => 4,
            'medium' => 3,
            'low' => 2,
        ];

        $thresholdLevel = $priorities[$threshold] ?? 3;

        foreach ($details as $alert) {
            $alertLevel = $priorities[$alert['priority'] ?? 'low'] ?? 2;

            if ($alertLevel >= $thresholdLevel) {
                $this->notifyAlert($alert);
            }
        }
    }

    /**
     * Send alert notification
     */
    private function notifyAlert(array $alert): void
    {
        $priority = $alert['priority'] ?? 'medium';
        $title = $alert['message'] ?? $alert['title'] ?? 'System Alert';

        switch ($priority) {
            case 'critical':
                $this->error("🚨 CRITICAL: {$title}");
                break;
            case 'high':
                $this->warn("⚠️ HIGH: {$title}");
                break;
            case 'medium':
                $this->comment("ℹ️ MEDIUM: {$title}");
                break;
            default:
                $this->info("ℹ️ LOW: {$title}");
        }
    }
}