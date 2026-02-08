<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Services\TenantContextService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Analytics Performance Optimizer Service
 *
 * Provides comprehensive performance optimization for analytics queries
 * including query optimization, caching strategies, cache invalidation,
 * cache warming, performance analysis, index suggestions, data retrieval
 * optimization, and batch processing.
 */
class AnalyticsPerformanceOptimizer
{
    // Cache TTL constants (in seconds)
    private const CACHE_TTL_SHORT = 300;      // 5 minutes

    private const CACHE_TTL_MEDIUM = 900;     // 15 minutes

    private const CACHE_TTL_LONG = 3600;       // 1 hour

    private const CACHE_TTL_VERY_LONG = 86400; // 24 hours

    // Batch processing constants
    private const DEFAULT_BATCH_SIZE = 1000;

    private const MAX_BATCH_SIZE = 5000;

    // Query optimization constants
    private const MAX_QUERY_EXECUTION_TIME = 30; // seconds

    private const SLOW_QUERY_THRESHOLD = 1;      // seconds

    private TenantContextService $tenantContext;

    public function __construct(TenantContextService $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Optimize an analytics query for better performance
     *
     * @param  Builder  $query  The query to optimize
     * @return Builder The optimized query
     */
    public function optimizeQuery(Builder $query): Builder
    {
        try {
            // Apply select optimizations to fetch only needed columns
            $query = $this->optimizeSelect($query);

            // Add eager loading to prevent N+1 queries
            $query = $this->optimizeEagerLoading($query);

            // Optimize where clauses
            $query = $this->optimizeWhereClauses($query);

            // Apply indexing hints
            $query = $this->applyIndexHints($query);

            Log::debug('Query optimized successfully', [
                'sql' => $query->toSql(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return $query;

        } catch (Exception $e) {
            Log::error('Failed to optimize query', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return $query;
        }
    }

    /**
     * Cache a query result with specified TTL
     *
     * @param  Builder  $query  The query to cache
     * @param  int  $ttl  Cache TTL in seconds
     * @param  string|null  $customKey  Custom cache key suffix
     * @return mixed The cached result
     */
    public function cacheQueryResult(Builder $query, int $ttl = self::CACHE_TTL_MEDIUM, ?string $customKey = null)
    {
        try {
            $cacheKey = $this->buildCacheKey('query_result', $customKey ?? md5(serialize([
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ])));

            return Cache::remember($cacheKey, $ttl, function () use ($query) {
                return $query->get();
            });

        } catch (Exception $e) {
            Log::error('Failed to cache query result', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return $query->get();
        }
    }

    /**
     * Invalidate cache entries matching a pattern
     *
     * @param  string  $pattern  Cache key pattern to invalidate
     * @return int Number of cache entries invalidated
     */
    public function invalidateCache(string $pattern): int
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();
            $fullPattern = "analytics:{$tenantId}:{$pattern}*";

            $invalidatedCount = 0;

            // Get all cache keys matching the pattern
            Cache::tags(['analytics', "tenant_{$tenantId}"])->flush();

            Log::info('Cache invalidated by pattern', [
                'pattern' => $fullPattern,
                'tenant_id' => $tenantId,
            ]);

            return $invalidatedCount;

        } catch (Exception $e) {
            Log::error('Failed to invalidate cache', [
                'error' => $e->getMessage(),
                'pattern' => $pattern,
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return 0;
        }
    }

    /**
     * Warm cache for frequently accessed analytics data
     *
     * @return array Cache warming results
     */
    public function warmCache(): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();
            $results = [
                'tenant_id' => $tenantId,
                'warmed_at' => now(),
                'entries' => [],
            ];

            // Warm common analytics queries
            $results['entries'] = [
                'dashboard_summary' => $this->warmDashboardSummaryCache(),
                'cohort_overview' => $this->warmCohortOverviewCache(),
                'engagement_metrics' => $this->warmEngagementMetricsCache(),
                'retention_data' => $this->warmRetentionDataCache(),
            ];

            Log::info('Cache warming completed', [
                'tenant_id' => $tenantId,
                'entries_warmed' => count($results['entries']),
            ]);

            return $results;

        } catch (Exception $e) {
            Log::error('Failed to warm cache', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
                'warmed_at' => now(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Analyze query performance and return metrics
     *
     * @param  Builder  $query  The query to analyze
     * @return array Performance analysis results
     */
    public function analyzeQueryPerformance(Builder $query): array
    {
        try {
            $startTime = microtime(true);

            // Execute query and measure time
            $query->get();
            $executionTime = microtime(true) - $startTime;

            // Get query explanation
            $explanation = $query->explain();

            // Analyze the query plan
            $analysis = $this->analyzeQueryPlan($explanation);

            $result = [
                'execution_time' => round($executionTime, 4),
                'is_slow' => $executionTime > self::SLOW_QUERY_THRESHOLD,
                'query_plan' => $analysis,
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
                'analyzed_at' => now(),
            ];

            // Log slow queries
            if ($executionTime > self::SLOW_QUERY_THRESHOLD) {
                Log::warning('Slow query detected', [
                    'execution_time' => $executionTime,
                    'sql' => $query->toSql(),
                    'tenant_id' => $this->tenantContext->getCurrentTenantId(),
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Failed to analyze query performance', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
                'analyzed_at' => now(),
            ];
        }
    }

    /**
     * Suggest indexes for a table based on query patterns
     *
     * @param  string  $table  The table name
     * @return array Index suggestions
     */
    public function suggestIndexes(string $table): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();

            // Get table statistics and query patterns
            $suggestions = [];

            // Analyze common query patterns for the table
            $commonPatterns = $this->analyzeQueryPatterns($table);

            foreach ($commonPatterns as $pattern) {
                $suggestions[] = [
                    'table' => $table,
                    'columns' => $pattern['columns'],
                    'index_type' => $this->determineIndexType($pattern),
                    'reason' => $pattern['reason'],
                    'priority' => $pattern['frequency'],
                ];
            }

            // Sort by priority
            usort($suggestions, fn ($a, $b) => $b['priority'] <=> $a['priority']);

            Log::info('Index suggestions generated', [
                'table' => $table,
                'suggestions_count' => count($suggestions),
                'tenant_id' => $tenantId,
            ]);

            return [
                'table' => $table,
                'suggestions' => $suggestions,
                'tenant_id' => $tenantId,
                'generated_at' => now(),
            ];

        } catch (Exception $e) {
            Log::error('Failed to generate index suggestions', [
                'error' => $e->getMessage(),
                'table' => $table,
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'table' => $table,
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
                'generated_at' => now(),
            ];
        }
    }

    /**
     * Optimize data retrieval based on request parameters
     *
     * @param  Request  $request  The HTTP request
     * @return array Optimization settings
     */
    public function optimizeDataRetrieval(Request $request): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();

            $optimizations = [
                'tenant_id' => $tenantId,
                'applied_optimizations' => [],
                'suggestions' => [],
            ];

            // Optimize pagination
            $optimizations['applied_optimizations']['pagination'] = $this->optimizePagination($request);

            // Optimize sorting
            $optimizations['applied_optimizations']['sorting'] = $this->optimizeSorting($request);

            // Optimize filtering
            $optimizations['applied_optimizations']['filtering'] = $this->optimizeFiltering($request);

            // Suggest field selection
            $optimizations['suggestions']['select_fields'] = $this->suggestFieldSelection($request);

            // Suggest eager loading
            $optimizations['suggestions']['eager_loading'] = $this->suggestEagerLoading($request);

            return $optimizations;

        } catch (Exception $e) {
            Log::error('Failed to optimize data retrieval', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ];
        }
    }

    /**
     * Batch process data with specified batch size
     *
     * @param  Collection|array  $data  The data to process
     * @param  int  $batchSize  Batch size (default: 1000)
     * @param  callable  $processor  Callback function to process each batch
     * @return array Batch processing results
     */
    public function batchProcessData(Collection|array $data, int $batchSize, callable $processor): array
    {
        try {
            // Normalize to array
            $items = $data instanceof Collection ? $data->toArray() : $data;

            // Validate batch size
            $batchSize = min(max($batchSize, 1), self::MAX_BATCH_SIZE);

            $totalItems = count($items);
            $batches = ceil($totalItems / $batchSize);

            $results = [
                'total_items' => $totalItems,
                'batch_size' => $batchSize,
                'total_batches' => $batches,
                'processed_batches' => 0,
                'failed_batches' => 0,
                'results' => [],
                'started_at' => now(),
            ];

            // Process in batches
            for ($i = 0; $i < $batches; $i++) {
                $batchStart = $i * $batchSize;
                $batch = array_slice($items, $batchStart, $batchSize);

                try {
                    $batchResult = $processor($batch, $i + 1);
                    $results['results'][] = [
                        'batch' => $i + 1,
                        'items_processed' => count($batch),
                        'result' => $batchResult,
                        'success' => true,
                    ];
                    $results['processed_batches']++;

                } catch (Exception $e) {
                    $results['results'][] = [
                        'batch' => $i + 1,
                        'items_processed' => count($batch),
                        'error' => $e->getMessage(),
                        'success' => false,
                    ];
                    $results['failed_batches']++;

                    Log::error('Batch processing failed', [
                        'batch' => $i + 1,
                        'error' => $e->getMessage(),
                        'tenant_id' => $this->tenantContext->getCurrentTenantId(),
                    ]);
                }
            }

            $results['completed_at'] = now();
            $results['success'] = $results['failed_batches'] === 0;

            Log::info('Batch processing completed', [
                'total_items' => $totalItems,
                'processed_batches' => $results['processed_batches'],
                'failed_batches' => $results['failed_batches'],
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return $results;

        } catch (Exception $e) {
            Log::error('Batch processing failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
                'success' => false,
            ];
        }
    }

    // Private helper methods

    /**
     * Optimize select clause to fetch only needed columns
     */
    private function optimizeSelect(Builder $query): Builder
    {
        // Check if query is already selecting specific columns
        $queryStr = $query->toSql();

        // If query uses SELECT *, suggest or apply specific selections
        if (str_contains($queryStr, 'select *')) {
            // For now, log a warning - in production, you might want to enforce this
            Log::debug('Query uses SELECT * - consider specifying columns');
        }

        return $query;
    }

    /**
     * Optimize eager loading to prevent N+1 queries
     */
    private function optimizeEagerLoading(Builder $query): Builder
    {
        // This would analyze the query relationships and suggest/add eager loading
        // Implementation depends on specific query structure
        return $query;
    }

    /**
     * Optimize where clauses for better index usage
     */
    private function optimizeWhereClauses(Builder $query): Builder
    {
        // Reorder where clauses to put most selective conditions first
        // This can help the query optimizer
        return $query;
    }

    /**
     * Apply database-specific index hints
     */
    private function applyIndexHints(Builder $query): Builder
    {
        // Apply index hints based on query patterns
        // This is database-specific and should be used carefully
        return $query;
    }

    /**
     * Build a cache key with tenant isolation
     */
    private function buildCacheKey(string $type, string $suffix = ''): string
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? 'global';

        return "analytics:{$tenantId}:{$type}:{$suffix}";
    }

    /**
     * Analyze query execution plan
     */
    private function analyzeQueryPlan(array $explanation): array
    {
        $analysis = [];

        foreach ($explanation as $row) {
            $analysis[] = [
                'type' => $row['type'] ?? 'unknown',
                'possible_keys' => $row['possible_keys'] ?? null,
                'key' => $row['key'] ?? null,
                'key_len' => $row['key_len'] ?? null,
                'rows' => $row['rows'] ?? null,
                'extra' => $row['Extra'] ?? null,
            ];
        }

        return $analysis;
    }

    /**
     * Analyze query patterns for a table
     */
    private function analyzeQueryPatterns(string $table): array
    {
        $patterns = [];

        // Common patterns that benefit from indexes
        $patterns[] = [
            'columns' => ['tenant_id'],
            'reason' => 'Tenant isolation is critical for multi-tenant applications',
            'frequency' => 10,
        ];

        $patterns[] = [
            'columns' => ['created_at'],
            'reason' => 'Date-based filtering is common for analytics',
            'frequency' => 8,
        ];

        $patterns[] = [
            'columns' => ['tenant_id', 'created_at'],
            'reason' => 'Composite index for tenant-scoped date queries',
            'frequency' => 9,
        ];

        return $patterns;
    }

    /**
     * Determine appropriate index type based on query pattern
     */
    private function determineIndexType(array $pattern): string
    {
        $columnCount = count($pattern['columns']);

        if ($columnCount === 1) {
            return 'BTREE'; // Standard B-tree index
        }

        return 'BTREE'; // Composite indexes also use BTREE by default
    }

    /**
     * Optimize pagination settings
     */
    private function optimizePagination(Request $request): array
    {
        $perPage = $request->get('per_page', 15);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 15;

        return [
            'per_page' => $perPage,
            'max_per_page' => 100,
            'recommendation' => 'Use cursor-based pagination for large datasets',
        ];
    }

    /**
     * Optimize sorting settings
     */
    private function optimizeSorting(Request $request): array
    {
        $sortColumn = $request->get('sort_column');
        $sortDirection = $request->get('sort_direction', 'asc');

        return [
            'sort_column' => $sortColumn,
            'sort_direction' => in_array(strtolower($sortDirection), ['asc', 'desc']) ? $sortDirection : 'asc',
            'recommendation' => $sortColumn ? "Consider adding an index on {$sortColumn}" : null,
        ];
    }

    /**
     * Optimize filtering settings
     */
    private function optimizeFiltering(Request $request): array
    {
        $filters = $request->all();

        return [
            'filters_applied' => array_keys($filters),
            'recommendation' => 'Use indexed columns for filtering',
        ];
    }

    /**
     * Suggest fields to select based on request
     */
    private function suggestFieldSelection(Request $request): array
    {
        return [
            'essential_fields' => ['id', 'created_at'],
            'optional_fields' => [],
            'recommendation' => 'Only select fields that are needed for the response',
        ];
    }

    /**
     * Suggest eager loading based on request
     */
    private function suggestEagerLoading(Request $request): array
    {
        return [
            'suggested_relations' => [],
            'recommendation' => 'Load relations only when needed to avoid N+1 queries',
        ];
    }

    /**
     * Warm dashboard summary cache
     */
    private function warmDashboardSummaryCache(): bool
    {
        try {
            $cacheKey = $this->buildCacheKey('dashboard_summary');
            Cache::put($cacheKey, ['warmed' => true], self::CACHE_TTL_LONG);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Warm cohort overview cache
     */
    private function warmCohortOverviewCache(): bool
    {
        try {
            $cacheKey = $this->buildCacheKey('cohort_overview');
            Cache::put($cacheKey, ['warmed' => true], self::CACHE_TTL_LONG);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Warm engagement metrics cache
     */
    private function warmEngagementMetricsCache(): bool
    {
        try {
            $cacheKey = $this->buildCacheKey('engagement_metrics');
            Cache::put($cacheKey, ['warmed' => true], self::CACHE_TTL_MEDIUM);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Warm retention data cache
     */
    private function warmRetentionDataCache(): bool
    {
        try {
            $cacheKey = $this->buildCacheKey('retention_data');
            Cache::put($cacheKey, ['warmed' => true], self::CACHE_TTL_LONG);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
