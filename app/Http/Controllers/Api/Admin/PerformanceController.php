<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\PerformanceOptimizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PerformanceController extends Controller
{
    public function __construct(
        private readonly PerformanceOptimizationService $performanceService
    ) {}

    /**
     * Get current performance metrics and budget status.
     */
    public function metrics(): JsonResponse
    {
        try {
            $metrics = $this->performanceService->monitorPerformanceMetrics();
            $budgets = $this->performanceService->getPerformanceBudgetStatus();
            $alerts = Cache::get('performance_metrics:alerts', []);

            return response()->json([
                'success' => true,
                'metrics' => $metrics,
                'budgets' => $budgets,
                'alerts' => $alerts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve performance metrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all performance caches.
     */
    public function clearCaches(): JsonResponse
    {
        try {
            $this->performanceService->clearPerformanceCaches();

            return response()->json([
                'success' => true,
                'message' => 'Performance caches cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear performance caches',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Optimize social graph caching.
     */
    public function optimizeSocialGraph(): JsonResponse
    {
        try {
            $this->performanceService->optimizeSocialGraphCaching();

            return response()->json([
                'success' => true,
                'message' => 'Social graph caching optimized successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize social graph caching',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Optimize timeline queries.
     */
    public function optimizeTimeline(): JsonResponse
    {
        try {
            $this->performanceService->optimizeTimelineQueries();

            return response()->json([
                'success' => true,
                'message' => 'Timeline queries optimized successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize timeline queries',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Optimize CDN integration for media assets.
     */
    public function optimizeCdn(): JsonResponse
    {
        try {
            $result = $this->performanceService->optimizeCdnIntegration();

            return response()->json([
                'success' => $result['status'] === 'success',
                'message' => $result['message'] ?? 'CDN integration optimized successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize CDN integration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set up automated performance alerts.
     */
    public function setupAlerts(): JsonResponse
    {
        try {
            $result = $this->performanceService->setupAutomatedAlerts();

            return response()->json([
                'success' => true,
                'message' => 'Automated performance alerts configured successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to setup automated alerts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute automated performance optimization.
     */
    public function executeAutomatedOptimization(): JsonResponse
    {
        try {
            $result = $this->performanceService->executeAutomatedOptimization();

            return response()->json([
                'success' => true,
                'message' => 'Automated optimization executed successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to execute automated optimization',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get performance budget details with recommendations.
     */
    public function getBudgetDetails(): JsonResponse
    {
        try {
            $budgets = $this->performanceService->getPerformanceBudgetStatus();
            $metrics = $this->performanceService->monitorPerformanceMetrics();

            // Generate budget-specific recommendations
            $recommendations = [];
            foreach ($budgets as $metric => $budget) {
                if ($budget['status'] !== 'within_budget') {
                    $recommendations[] = [
                        'metric' => $metric,
                        'status' => $budget['status'],
                        'current' => $budget['current'],
                        'budget' => $budget['budget'],
                        'recommendation' => $this->getBudgetRecommendation($metric, $budget['status']),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'budgets' => $budgets,
                    'current_metrics' => $metrics,
                    'recommendations' => $recommendations,
                    'overall_health' => $this->calculateOverallHealth($budgets),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get budget details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get budget-specific recommendations.
     */
    private function getBudgetRecommendation(string $metric, string $status): string
    {
        $recommendations = [
            'timeline_generation' => [
                'approaching_limit' => 'Consider optimizing social graph caching and database indexes',
                'over_budget' => 'Immediately optimize timeline queries and implement aggressive caching',
            ],
            'cache_hit_rate' => [
                'approaching_limit' => 'Review cache invalidation strategies and increase cache TTL where appropriate',
                'over_budget' => 'Implement multi-layer caching and optimize cache key strategies',
            ],
            'memory_usage_mb' => [
                'approaching_limit' => 'Monitor memory leaks and optimize object lifecycle management',
                'over_budget' => 'Clear unnecessary caches and optimize memory-intensive operations',
            ],
            'active_connections' => [
                'approaching_limit' => 'Implement connection pooling and optimize database query efficiency',
                'over_budget' => 'Scale database resources and implement read replicas',
            ],
        ];

        return $recommendations[$metric][$status] ?? 'Review and optimize this metric';
    }

    /**
     * Calculate overall system health based on budgets.
     */
    private function calculateOverallHealth(array $budgets): array
    {
        $totalMetrics = count($budgets);
        $withinBudget = 0;
        $approachingLimit = 0;
        $overBudget = 0;

        foreach ($budgets as $budget) {
            switch ($budget['status']) {
                case 'within_budget':
                    $withinBudget++;
                    break;
                case 'approaching_limit':
                    $approachingLimit++;
                    break;
                case 'over_budget':
                    $overBudget++;
                    break;
            }
        }

        $healthScore = (($withinBudget * 100) + ($approachingLimit * 50)) / ($totalMetrics * 100) * 100;

        $status = 'excellent';
        if ($overBudget > 0) {
            $status = 'critical';
        } elseif ($approachingLimit > $withinBudget) {
            $status = 'warning';
        } elseif ($approachingLimit > 0) {
            $status = 'good';
        }

        return [
            'score' => round($healthScore, 1),
            'status' => $status,
            'within_budget' => $withinBudget,
            'approaching_limit' => $approachingLimit,
            'over_budget' => $overBudget,
            'total_metrics' => $totalMetrics,
        ];
    }
}
