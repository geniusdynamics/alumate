<?php

namespace App\Console\Commands;

use App\Services\PerformanceOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class OptimizePerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:optimize 
                            {--cache : Only optimize caching strategies}
                            {--queries : Only optimize database queries}
                            {--monitor : Only run performance monitoring}
                            {--clear-cache : Clear all performance caches}
                            {--budget : Show performance budget status}
                            {--cdn : Optimize CDN integration}
                            {--alerts : Setup automated alerts}
                            {--auto : Execute automated optimization}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize platform performance through advanced caching, query optimization, and monitoring';

    public function __construct(
        private PerformanceOptimizationService $performanceService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting Performance Optimization...');

        try {
            // Handle specific options
            if ($this->option('clear-cache')) {
                return $this->clearCaches();
            }

            if ($this->option('budget')) {
                return $this->showPerformanceBudget();
            }

            if ($this->option('monitor')) {
                return $this->runMonitoring();
            }

            if ($this->option('cdn')) {
                return $this->optimizeCdn();
            }

            if ($this->option('alerts')) {
                return $this->setupAlerts();
            }

            if ($this->option('auto')) {
                return $this->executeAutomatedOptimization();
            }

            // Run optimization tasks
            if ($this->option('cache') || ! $this->hasSpecificOptions()) {
                $this->optimizeCaching();
            }

            if ($this->option('queries') || ! $this->hasSpecificOptions()) {
                $this->optimizeQueries();
            }

            if (! $this->hasSpecificOptions()) {
                $this->runMonitoring();
            }

            $this->info('✅ Performance optimization completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Performance optimization failed: '.$e->getMessage());
            Log::error('Performance optimization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Optimize caching strategies.
     */
    private function optimizeCaching(): void
    {
        $this->info('🔄 Optimizing caching strategies...');

        $this->task('Caching user connections', function () {
            $this->performanceService->optimizeSocialGraphCaching();
        });

        $this->info('✅ Caching optimization completed');
    }

    /**
     * Optimize database queries.
     */
    private function optimizeQueries(): void
    {
        $this->info('🔄 Optimizing database queries...');

        $this->task('Creating optimized indexes', function () {
            $this->performanceService->optimizeTimelineQueries();
        });

        $this->info('✅ Query optimization completed');
    }

    /**
     * Run performance monitoring.
     */
    private function runMonitoring(): int
    {
        $this->info('📊 Running performance monitoring...');

        $metrics = $this->performanceService->monitorPerformanceMetrics();

        $this->displayMetrics($metrics);

        // Check for alerts
        $alerts = cache()->get('performance_metrics:alerts', []);
        if (! empty($alerts)) {
            $this->warn('⚠️  Performance Alerts:');
            foreach ($alerts as $alert) {
                $icon = $alert['severity'] === 'critical' ? '🔴' : '🟡';
                $this->line("  {$icon} {$alert['message']}");
            }
        } else {
            $this->info('✅ No performance alerts');
        }

        return Command::SUCCESS;
    }

    /**
     * Clear performance caches.
     */
    private function clearCaches(): int
    {
        $this->info('🧹 Clearing performance caches...');

        $this->task('Clearing caches', function () {
            $this->performanceService->clearPerformanceCaches();
        });

        $this->info('✅ Performance caches cleared');

        return Command::SUCCESS;
    }

    /**
     * Show performance budget status.
     */
    private function showPerformanceBudget(): int
    {
        $this->info('📈 Performance Budget Status');

        $budgets = $this->performanceService->getPerformanceBudgetStatus();

        $headers = ['Metric', 'Budget', 'Current', 'Status', 'Usage %'];
        $rows = [];

        foreach ($budgets as $metric => $budget) {
            $statusIcon = match ($budget['status']) {
                'within_budget' => '✅',
                'approaching_limit' => '⚠️',
                'over_budget' => '❌',
                default => '❓'
            };

            $rows[] = [
                ucfirst(str_replace('_', ' ', $metric)),
                $this->formatBudgetValue($budget['budget'], $metric),
                $this->formatBudgetValue($budget['current'], $metric),
                $statusIcon.' '.ucfirst(str_replace('_', ' ', $budget['status'])),
                number_format($budget['percentage'], 1).'%',
            ];
        }

        $this->table($headers, $rows);

        return Command::SUCCESS;
    }

    /**
     * Display performance metrics in a formatted table.
     */
    private function displayMetrics(array $metrics): void
    {
        $this->info('📊 Current Performance Metrics:');

        $rows = [
            ['Cache Hit Rate', number_format($metrics['cache_hit_rate'], 2).'%'],
            ['Average Query Time', number_format($metrics['average_query_time'], 2).'ms'],
            ['Active DB Connections', $metrics['active_connections']],
            ['Memory Usage', $this->formatBytes($metrics['memory_usage'])],
            ['Redis Memory', $metrics['redis_memory_usage']['used_memory_human']],
            ['Slow Queries Count', $metrics['slow_queries_count']],
            ['Timeline Generation', number_format($metrics['timeline_generation_time'], 2).'ms'],
        ];

        $this->table(['Metric', 'Value'], $rows);
    }

    /**
     * Format budget values based on metric type.
     */
    private function formatBudgetValue(float $value, string $metric): string
    {
        return match ($metric) {
            'timeline_generation' => number_format($value, 0).'ms',
            'cache_hit_rate' => number_format($value, 1).'%',
            'memory_usage_mb' => number_format($value, 1).'MB',
            'active_connections' => number_format($value, 0),
            default => number_format($value, 2)
        };
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Optimize CDN integration.
     */
    private function optimizeCdn(): int
    {
        $this->info('🌐 Optimizing CDN integration...');

        $result = $this->performanceService->optimizeCdnIntegration();

        if ($result['status'] === 'success') {
            $this->info('✅ CDN optimization completed successfully!');

            if (isset($result['assets_analyzed'])) {
                $this->line("  📊 Assets analyzed: {$result['assets_analyzed']}");
            }

            if (isset($result['recommendations'])) {
                $this->line('  💡 Recommendations: '.count($result['recommendations']));
                foreach ($result['recommendations'] as $rec) {
                    $icon = $rec['priority'] === 'high' ? '🔴' : ($rec['priority'] === 'medium' ? '🟡' : '🟢');
                    $this->line("    {$icon} {$rec['message']}");
                }
            }
        } else {
            $this->error('❌ CDN optimization failed: '.($result['message'] ?? 'Unknown error'));
        }

        return $result['status'] === 'success' ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Setup automated alerts.
     */
    private function setupAlerts(): int
    {
        $this->info('🚨 Setting up automated performance alerts...');

        $result = $this->performanceService->setupAutomatedAlerts();

        $this->info('✅ Automated alerts configured successfully!');
        $this->line("  📋 Alert rules: {$result['rules_configured']}");
        $this->line('  📡 Monitoring enabled: '.($result['monitoring_enabled'] ? 'Yes' : 'No'));
        $this->line('  📢 Alert channels: '.implode(', ', $result['alert_channels']));

        return Command::SUCCESS;
    }

    /**
     * Execute automated optimization.
     */
    private function executeAutomatedOptimization(): int
    {
        $this->info('🤖 Executing automated performance optimization...');

        $result = $this->performanceService->executeAutomatedOptimization();

        $this->info('✅ Automated optimization completed!');
        $this->line("  📊 Rules evaluated: {$result['rules_evaluated']}");
        $this->line("  ⚡ Actions triggered: {$result['actions_triggered']}");

        if ($result['actions_triggered'] > 0) {
            $this->line('  🔧 Optimization actions:');
            foreach ($result['results'] as $ruleName => $actionResult) {
                $icon = $actionResult['severity'] === 'critical' ? '🔴' : '🟡';
                $status = $actionResult['result']['status'] === 'success' ? '✅' : '❌';
                $this->line("    {$icon} {$status} {$ruleName}: {$actionResult['result']['message']}");
            }
        } else {
            $this->line('  ✨ No optimization actions needed - system is performing well!');
        }

        return Command::SUCCESS;
    }

    /**
     * Check if any specific options are provided.
     */
    private function hasSpecificOptions(): bool
    {
        return $this->option('cache') ||
               $this->option('queries') ||
               $this->option('monitor') ||
               $this->option('clear-cache') ||
               $this->option('budget') ||
               $this->option('cdn') ||
               $this->option('alerts') ||
               $this->option('auto');
    }
}
