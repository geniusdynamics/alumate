<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StatisticsController extends Controller
{
    /**
     * Cache duration for statistics (5 minutes)
     */
    private const CACHE_DURATION = 300;

    /**
     * Available statistics with their data sources
     */
    private const AVAILABLE_STATISTICS = [
        'alumni-count' => [
            'query' => 'SELECT COUNT(*) as value FROM users WHERE role = "alumni" AND deleted_at IS NULL',
            'label' => 'Active Alumni',
            'suffix' => '+',
        ],
        'connections-made' => [
            'query' => 'SELECT COUNT(*) as value FROM connections WHERE status = "accepted"',
            'label' => 'Connections Made',
            'suffix' => '+',
        ],
        'job-placements' => [
            'query' => 'SELECT COUNT(*) as value FROM job_applications WHERE status = "hired"',
            'label' => 'Job Placements',
            'suffix' => '+',
        ],
        'institutions-served' => [
            'query' => 'SELECT COUNT(DISTINCT tenant_id) as value FROM users WHERE deleted_at IS NULL',
            'label' => 'Institutions Served',
            'suffix' => '+',
        ],
        'qualified-candidates' => [
            'query' => 'SELECT COUNT(*) as value FROM users WHERE role = "alumni" AND profile_completed = 1 AND deleted_at IS NULL',
            'label' => 'Qualified Candidates',
            'suffix' => '+',
        ],
        'successful-hires' => [
            'query' => 'SELECT COUNT(*) as value FROM job_applications WHERE status = "hired" AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)',
            'label' => 'Successful Hires',
            'suffix' => '+',
        ],
        // Manual statistics (not from database)
        'avg-salary-increase' => [
            'value' => 35,
            'label' => 'Avg Salary Increase',
            'suffix' => '%',
        ],
        'engagement-increase' => [
            'value' => 85,
            'label' => 'Engagement Increase',
            'suffix' => '%',
        ],
        'donation-growth' => [
            'value' => 120,
            'label' => 'Donation Growth',
            'suffix' => '%',
        ],
        'placement-rate' => [
            'value' => 94,
            'label' => 'Placement Rate',
            'suffix' => '%',
        ],
        'time-to-hire' => [
            'value' => 40,
            'label' => 'Faster Hiring',
            'suffix' => '%',
        ],
        'retention-rate' => [
            'value' => 92,
            'label' => 'Retention Rate',
            'suffix' => '%',
        ],
    ];

    /**
     * Get a single statistic by ID
     */
    public function show(string $id): JsonResponse
    {
        try {
            if (!isset(self::AVAILABLE_STATISTICS[$id])) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Statistic not found']
                ], 404);
            }

            $cacheKey = "statistic.{$id}";
            
            $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($id) {
                return $this->fetchStatisticData($id);
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to fetch statistic {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'errors' => ['Failed to fetch statistic data']
            ], 500);
        }
    }

    /**
     * Get multiple statistics by IDs
     */
    public function batch(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => 'required|array|min:1|max:20',
                'ids.*' => 'required|string|max:50'
            ]);

            $ids = $request->input('ids');
            $results = [];
            $errors = [];

            foreach ($ids as $id) {
                if (!isset(self::AVAILABLE_STATISTICS[$id])) {
                    $errors[] = "Statistic '{$id}' not found";
                    continue;
                }

                try {
                    $cacheKey = "statistic.{$id}";
                    
                    $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($id) {
                        return $this->fetchStatisticData($id);
                    });

                    $results[] = $data;
                } catch (\Exception $e) {
                    Log::error("Failed to fetch statistic {$id}: " . $e->getMessage());
                    $errors[] = "Failed to fetch statistic '{$id}'";
                }
            }

            return response()->json([
                'success' => count($errors) === 0,
                'data' => $results,
                'errors' => $errors,
                'timestamp' => now()->toISOString()
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("Failed to fetch statistics batch: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'errors' => ['Failed to fetch statistics data']
            ], 500);
        }
    }

    /**
     * Get platform metrics (commonly used statistics)
     */
    public function platformMetrics(): JsonResponse
    {
        try {
            $cacheKey = 'platform.metrics';
            
            $metrics = Cache::remember($cacheKey, self::CACHE_DURATION, function () {
                $results = [];
                
                // Get key platform metrics
                $keyMetrics = [
                    'alumni-count',
                    'connections-made',
                    'job-placements',
                    'institutions-served'
                ];

                foreach ($keyMetrics as $id) {
                    try {
                        $data = $this->fetchStatisticData($id);
                        $results[$id] = $data['value'];
                    } catch (\Exception $e) {
                        Log::warning("Failed to fetch platform metric {$id}: " . $e->getMessage());
                        // Use fallback value
                        $results[$id] = 0;
                    }
                }

                return $results;
            });

            return response()->json([
                'success' => true,
                'data' => $metrics,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to fetch platform metrics: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'errors' => ['Failed to fetch platform metrics']
            ], 500);
        }
    }

    /**
     * Health check endpoint
     */
    public function health(): JsonResponse
    {
        try {
            // Test database connection
            DB::connection()->getPdo();
            
            return response()->json([
                'success' => true,
                'status' => 'healthy',
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'unhealthy',
                'error' => 'Database connection failed'
            ], 503);
        }
    }

    /**
     * Clear statistics cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            // Clear all statistic caches
            foreach (array_keys(self::AVAILABLE_STATISTICS) as $id) {
                Cache::forget("statistic.{$id}");
            }
            
            Cache::forget('platform.metrics');

            return response()->json([
                'success' => true,
                'message' => 'Statistics cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to clear statistics cache: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'errors' => ['Failed to clear cache']
            ], 500);
        }
    }

    /**
     * Fetch statistic data from database or return manual value
     */
    private function fetchStatisticData(string $id): array
    {
        $config = self::AVAILABLE_STATISTICS[$id];

        if (isset($config['query'])) {
            // Database query
            $result = DB::selectOne($config['query']);
            $value = $result->value ?? 0;
            $source = 'api';
        } else {
            // Manual value
            $value = $config['value'];
            $source = 'manual';
        }

        return [
            'id' => $id,
            'value' => (int) $value,
            'lastUpdated' => now()->toISOString(),
            'source' => $source,
            'metadata' => [
                'label' => $config['label'],
                'suffix' => $config['suffix'] ?? null,
                'prefix' => $config['prefix'] ?? null,
            ]
        ];
    }
}
