<?php

namespace App\Console\Commands;

use App\Models\SecurityEvent;
use App\Models\SystemHealthLog;
use App\Services\SecurityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class MonitorSystemHealth extends Command
{
    protected $signature = 'system:health-check {--alert : Send alerts for critical issues}';

    protected $description = 'Monitor system health and log status';

    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        parent::__construct();
        $this->securityService = $securityService;
    }

    public function handle()
    {
        $this->info('Starting system health check...');

        $components = [
            'database' => [$this, 'checkDatabase'],
            'cache' => [$this, 'checkCache'],
            'storage' => [$this, 'checkStorage'],
            'queue' => [$this, 'checkQueue'],
            'memory' => [$this, 'checkMemory'],
            'disk_space' => [$this, 'checkDiskSpace'],
        ];

        $criticalIssues = [];

        foreach ($components as $component => $checker) {
            $this->info("Checking {$component}...");

            try {
                $result = call_user_func($checker);

                SystemHealthLog::create([
                    'component' => $component,
                    'status' => $result['status'],
                    'metrics' => $result['metrics'] ?? [],
                    'message' => $result['message'],
                    'checked_at' => now(),
                ]);

                $this->displayResult($component, $result);

                if ($result['status'] === 'critical') {
                    $criticalIssues[] = $component;
                }

            } catch (\Exception $e) {
                $result = [
                    'status' => 'critical',
                    'message' => 'Health check failed: '.$e->getMessage(),
                    'metrics' => [],
                ];

                SystemHealthLog::create([
                    'component' => $component,
                    'status' => 'critical',
                    'metrics' => [],
                    'message' => $result['message'],
                    'checked_at' => now(),
                ]);

                $this->displayResult($component, $result);
                $criticalIssues[] = $component;
            }
        }

        // Send alerts for critical issues
        if (! empty($criticalIssues) && $this->option('alert')) {
            $this->sendCriticalAlerts($criticalIssues);
        }

        $this->info('Health check completed.');

        return empty($criticalIssues) ? 0 : 1;
    }

    private function checkDatabase()
    {
        $startTime = microtime(true);

        try {
            // Test connection
            DB::connection()->getPdo();

            // Test query performance
            $queryStart = microtime(true);
            DB::select('SELECT 1');
            $queryTime = (microtime(true) - $queryStart) * 1000;

            // Get database size
            $config = config('database.connections.'.config('database.default'));
            $dbSize = 0;

            if ($config['driver'] === 'mysql') {
                $result = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'size_mb' FROM information_schema.tables WHERE table_schema = ?", [$config['database']]);
                $dbSize = $result[0]->size_mb ?? 0;
            }

            $responseTime = (microtime(true) - $startTime) * 1000;

            $status = 'healthy';
            if ($queryTime > 1000) {
                $status = 'critical';
            } elseif ($queryTime > 500) {
                $status = 'warning';
            }

            return [
                'status' => $status,
                'message' => "Database connection successful. Query time: {$queryTime}ms",
                'metrics' => [
                    'response_time_ms' => round($responseTime, 2),
                    'query_time_ms' => round($queryTime, 2),
                    'database_size_mb' => $dbSize,
                ],
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Database connection failed: '.$e->getMessage(),
                'metrics' => [],
            ];
        }
    }

    private function checkCache()
    {
        $startTime = microtime(true);

        try {
            $testKey = 'health_check_'.time();
            $testValue = 'test_value_'.rand(1000, 9999);

            // Test write
            Cache::put($testKey, $testValue, 60);

            // Test read
            $retrievedValue = Cache::get($testKey);

            // Test delete
            Cache::forget($testKey);

            $responseTime = (microtime(true) - $startTime) * 1000;

            if ($retrievedValue !== $testValue) {
                return [
                    'status' => 'critical',
                    'message' => 'Cache read/write test failed',
                    'metrics' => ['response_time_ms' => round($responseTime, 2)],
                ];
            }

            $status = 'healthy';
            if ($responseTime > 500) {
                $status = 'warning';
            }
            if ($responseTime > 1000) {
                $status = 'critical';
            }

            return [
                'status' => $status,
                'message' => "Cache is working properly. Response time: {$responseTime}ms",
                'metrics' => ['response_time_ms' => round($responseTime, 2)],
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Cache error: '.$e->getMessage(),
                'metrics' => [],
            ];
        }
    }

    private function checkStorage()
    {
        $startTime = microtime(true);

        try {
            $testFile = 'health_check_'.time().'.txt';
            $testContent = 'Health check test content';

            // Test write
            Storage::put($testFile, $testContent);

            // Test read
            $retrievedContent = Storage::get($testFile);

            // Test delete
            Storage::delete($testFile);

            $responseTime = (microtime(true) - $startTime) * 1000;

            if ($retrievedContent !== $testContent) {
                return [
                    'status' => 'critical',
                    'message' => 'Storage read/write test failed',
                    'metrics' => ['response_time_ms' => round($responseTime, 2)],
                ];
            }

            $status = 'healthy';
            if ($responseTime > 1000) {
                $status = 'warning';
            }
            if ($responseTime > 2000) {
                $status = 'critical';
            }

            return [
                'status' => $status,
                'message' => "Storage is working properly. Response time: {$responseTime}ms",
                'metrics' => ['response_time_ms' => round($responseTime, 2)],
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Storage error: '.$e->getMessage(),
                'metrics' => [],
            ];
        }
    }

    private function checkQueue()
    {
        try {
            $queueSize = Queue::size();
            $failedJobs = DB::table('failed_jobs')->count();

            $status = 'healthy';
            if ($queueSize > 1000) {
                $status = 'warning';
            }
            if ($queueSize > 5000 || $failedJobs > 100) {
                $status = 'critical';
            }

            return [
                'status' => $status,
                'message' => "Queue size: {$queueSize}, Failed jobs: {$failedJobs}",
                'metrics' => [
                    'queue_size' => $queueSize,
                    'failed_jobs' => $failedJobs,
                ],
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Queue monitoring unavailable: '.$e->getMessage(),
                'metrics' => [],
            ];
        }
    }

    private function checkMemory()
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memoryPercent = ($memoryUsage / $memoryLimit) * 100;

        $status = 'healthy';
        if ($memoryPercent > 80) {
            $status = 'warning';
        }
        if ($memoryPercent > 90) {
            $status = 'critical';
        }

        return [
            'status' => $status,
            'message' => sprintf(
                'Memory usage: %s / %s (%.1f%%)',
                $this->formatBytes($memoryUsage),
                $this->formatBytes($memoryLimit),
                $memoryPercent
            ),
            'metrics' => [
                'memory_usage_bytes' => $memoryUsage,
                'memory_limit_bytes' => $memoryLimit,
                'memory_usage_percent' => round($memoryPercent, 2),
            ],
        ];
    }

    private function checkDiskSpace()
    {
        $storagePath = storage_path();
        $freeBytes = disk_free_space($storagePath);
        $totalBytes = disk_total_space($storagePath);
        $usedBytes = $totalBytes - $freeBytes;
        $usedPercent = ($usedBytes / $totalBytes) * 100;

        $status = 'healthy';
        if ($usedPercent > 80) {
            $status = 'warning';
        }
        if ($usedPercent > 90) {
            $status = 'critical';
        }

        return [
            'status' => $status,
            'message' => sprintf(
                'Disk usage: %s / %s (%.1f%%)',
                $this->formatBytes($usedBytes),
                $this->formatBytes($totalBytes),
                $usedPercent
            ),
            'metrics' => [
                'disk_free_bytes' => $freeBytes,
                'disk_total_bytes' => $totalBytes,
                'disk_used_percent' => round($usedPercent, 2),
            ],
        ];
    }

    private function displayResult($component, $result)
    {
        $status = $result['status'];
        $message = $result['message'];

        switch ($status) {
            case 'healthy':
                $this->info("✓ {$component}: {$message}");
                break;
            case 'warning':
                $this->warn("⚠ {$component}: {$message}");
                break;
            case 'critical':
                $this->error("✗ {$component}: {$message}");
                break;
        }
    }

    private function sendCriticalAlerts($criticalIssues)
    {
        $this->securityService->logSecurityEvent(
            'system_health_critical',
            SecurityEvent::SEVERITY_CRITICAL,
            'Critical system health issues detected: '.implode(', ', $criticalIssues),
            [
                'components' => $criticalIssues,
                'timestamp' => now()->toISOString(),
            ]
        );

        Log::critical('Critical system health issues detected', [
            'components' => $criticalIssues,
        ]);
    }

    private function parseMemoryLimit($limit)
    {
        if ($limit == -1) {
            return PHP_INT_MAX;
        }

        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $limit = (int) $limit;

        switch ($last) {
            case 'g':
                $limit *= 1024;
            case 'm':
                $limit *= 1024;
            case 'k':
                $limit *= 1024;
        }

        return $limit;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
