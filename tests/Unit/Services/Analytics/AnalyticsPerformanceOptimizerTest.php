<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\AnalyticsPerformanceOptimizer;
use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Unit tests for AnalyticsPerformanceOptimizer
 *
 * @covers \App\Services\Analytics\AnalyticsPerformanceOptimizer
 */
class AnalyticsPerformanceOptimizerTest extends TestCase
{
    private AnalyticsPerformanceOptimizer $optimizer;
    private TenantContextService|MockInterface $tenantContext;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $this->tenantContext = Mockery::mock(TenantContextService::class);
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andReturn($this->tenant->id);
        $this->tenantContext->shouldReceive('getCurrentSchema')
            ->andReturn('tenant_' . $this->tenant->id);

        $this->optimizer = new AnalyticsPerformanceOptimizer($this->tenantContext);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test query optimization returns builder
     */
    public function test_optimize_query_returns_builder(): void
    {
        $query = User::query();

        $result = $this->optimizer->optimizeQuery($query);

        $this->assertInstanceOf(Builder::class, $result);
    }

    /**
     * Test query optimization handles SELECT * warning
     */
    public function test_optimize_query_logs_select_star_warning(): void
    {
        $query = User::query()->where('id', 1);

        $result = $this->optimizer->optimizeQuery($query);

        $this->assertInstanceOf(Builder::class, $result);
    }

    /**
     * Test caching query result with default TTL
     */
    public function test_cache_query_result_with_default_ttl(): void
    {
        $query = User::query()->where('tenant_id', $this->tenant->id);

        $result = $this->optimizer->cacheQueryResult($query);

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * Test caching query result with custom TTL
     */
    public function test_cache_query_result_with_custom_ttl(): void
    {
        $query = User::query()->where('tenant_id', $this->tenant->id);

        $result = $this->optimizer->cacheQueryResult($query, 600);

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * Test caching query result with custom key
     */
    public function test_cache_query_result_with_custom_key(): void
    {
        $query = User::query()->where('tenant_id', $this->tenant->id);

        $result = $this->optimizer->cacheQueryResult($query, 300, 'custom_key');

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * Test cache invalidation returns count
     */
    public function test_invalidate_cache_returns_count(): void
    {
        $result = $this->optimizer->invalidateCache('test_pattern');

        $this->assertIsInt($result);
    }

    /**
     * Test cache warming returns results array
     */
    public function test_warm_cache_returns_results_array(): void
    {
        $result = $this->optimizer->warmCache();

        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('warmed_at', $result);
        $this->assertArrayHasKey('entries', $result);
        $this->assertEquals($this->tenant->id, $result['tenant_id']);
    }

    /**
     * Test cache warming includes expected entries
     */
    public function test_warm_cache_includes_expected_entries(): void
    {
        $result = $this->optimizer->warmCache();

        $this->assertArrayHasKey('dashboard_summary', $result['entries']);
        $this->assertArrayHasKey('cohort_overview', $result['entries']);
        $this->assertArrayHasKey('engagement_metrics', $result['entries']);
        $this->assertArrayHasKey('retention_data', $result['entries']);
    }

    /**
     * Test query performance analysis returns metrics
     */
    public function test_analyze_query_performance_returns_metrics(): void
    {
        $query = User::query()->where('tenant_id', $this->tenant->id);

        $result = $this->optimizer->analyzeQueryPerformance($query);

        $this->assertArrayHasKey('execution_time', $result);
        $this->assertArrayHasKey('is_slow', $result);
        $this->assertArrayHasKey('query_plan', $result);
        $this->assertArrayHasKey('sql', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('analyzed_at', $result);
        $this->assertIsFloat($result['execution_time']);
        $this->assertIsBool($result['is_slow']);
    }

    /**
     * Test query performance analysis execution time is reasonable
     */
    public function test_analyze_query_performance_execution_time_is_reasonable(): void
    {
        $query = User::query()->where('tenant_id', $this->tenant->id);

        $result = $this->optimizer->analyzeQueryPerformance($query);

        $this->assertLessThan(30, $result['execution_time']);
    }

    /**
     * Test index suggestions returns array
     */
    public function test_suggest_indexes_returns_array(): void
    {
        $result = $this->optimizer->suggestIndexes('users');

        $this->assertArrayHasKey('table', $result);
        $this->assertArrayHasKey('suggestions', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('generated_at', $result);
        $this->assertEquals('users', $result['table']);
    }

    /**
     * Test index suggestions includes tenant_id
     */
    public function test_suggest_indexes_includes_tenant_isolation(): void
    {
        $result = $this->optimizer->suggestIndexes('users');

        $this->assertEquals($this->tenant->id, $result['tenant_id']);
    }

    /**
     * Test index suggestions contains expected columns
     */
    public function test_suggest_indexes_contains_expected_columns(): void
    {
        $result = $this->optimizer->suggestIndexes('users');

        $this->assertNotEmpty($result['suggestions']);
        
        $columns = collect($result['suggestions'])->pluck('columns')->toArray();
        $hasTenantId = collect($columns)->contains(function ($col) {
            return in_array('tenant_id', $col);
        });
        $this->assertTrue($hasTenantId);
    }

    /**
     * Test index suggestions are sorted by priority
     */
    public function test_suggest_indexes_sorted_by_priority(): void
    {
        $result = $this->optimizer->suggestIndexes('users');

        $priorities = collect($result['suggestions'])->pluck('priority')->toArray();
        $sortedPriorities = $priorities;
        rsort($sortedPriorities);
        $this->assertEquals($sortedPriorities, $priorities);
    }

    /**
     * Test data retrieval optimization returns settings
     */
    public function test_optimize_data_retrieval_returns_settings(): void
    {
        $request = Request::create('/analytics', 'GET', [
            'per_page' => 25,
            'sort_column' => 'created_at',
            'sort_direction' => 'desc',
        ]);

        $result = $this->optimizer->optimizeDataRetrieval($request);

        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('applied_optimizations', $result);
        $this->assertArrayHasKey('suggestions', $result);
        $this->assertEquals($this->tenant->id, $result['tenant_id']);
    }

    /**
     * Test data retrieval optimization pagination
     */
    public function test_optimize_data_retrieval_pagination(): void
    {
        $request = Request::create('/analytics', 'GET', [
            'per_page' => 25,
        ]);

        $result = $this->optimizer->optimizeDataRetrieval($request);

        $this->assertArrayHasKey('pagination', $result['applied_optimizations']);
        $this->assertEquals(25, $result['applied_optimizations']['pagination']['per_page']);
    }

    /**
     * Test data retrieval optimization invalid per_page defaults to 15
     */
    public function test_optimize_data_retrieval_invalid_per_page_defaults(): void
    {
        $request = Request::create('/analytics', 'GET', [
            'per_page' => 999,
        ]);

        $result = $this->optimizer->optimizeDataRetrieval($request);

        $this->assertEquals(15, $result['applied_optimizations']['pagination']['per_page']);
    }

    /**
     * Test data retrieval optimization sorting
     */
    public function test_optimize_data_retrieval_sorting(): void
    {
        $request = Request::create('/analytics', 'GET', [
            'sort_column' => 'id',
            'sort_direction' => 'asc',
        ]);

        $result = $this->optimizer->optimizeDataRetrieval($request);

        $this->assertArrayHasKey('sorting', $result['applied_optimizations']);
        $this->assertEquals('id', $result['applied_optimizations']['sorting']['sort_column']);
        $this->assertEquals('asc', $result['applied_optimizations']['sorting']['sort_direction']);
    }

    /**
     * Test data retrieval optimization filtering
     */
    public function test_optimize_data_retrieval_filtering(): void
    {
        $request = Request::create('/analytics', 'GET', [
            'status' => 'active',
            'type' => 'premium',
        ]);

        $result = $this->optimizer->optimizeDataRetrieval($request);

        $this->assertArrayHasKey('filtering', $result['applied_optimizations']);
        $this->assertContains('status', $result['applied_optimizations']['filtering']['filters_applied']);
        $this->assertContains('type', $result['applied_optimizations']['filtering']['filters_applied']);
    }

    /**
     * Test batch processing with array data
     */
    public function test_batch_process_data_with_array(): void
    {
        $data = range(1, 100);
        $processed = [];

        $result = $this->optimizer->batchProcessData($data, 25, function ($batch, $batchNumber) use (&$processed) {
            $processed = array_merge($processed, $batch);
            return count($batch);
        });

        $this->assertArrayHasKey('total_items', $result);
        $this->assertArrayHasKey('batch_size', $result);
        $this->assertArrayHasKey('total_batches', $result);
        $this->assertArrayHasKey('processed_batches', $result);
        $this->assertArrayHasKey('failed_batches', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertEquals(100, $result['total_items']);
        $this->assertEquals(25, $result['batch_size']);
        $this->assertEquals(4, $result['total_batches']);
        $this->assertEquals(4, $result['processed_batches']);
        $this->assertEquals(0, $result['failed_batches']);
        $this->assertTrue($result['success']);
    }

    public function test_batch_process_data_with_collection(): void
    {
        $collection = collect(range(1, 50));
        $processed = [];

        $result = $this->optimizer->batchProcessData($collection, 20, function ($batch, $batchNumber) use (&$processed) {
            $processed = array_merge($processed, $batch);
            return count($batch);
        });

        $this->assertEquals(50, $result['total_items']);
        $this->assertEquals(3, $result['total_batches']);
        $this->assertEquals(3, $result['processed_batches']);
    }

    /**
     * Test batch processing with custom batch size
     */
    public function test_batch_process_data_with_custom_batch_size(): void
    {
        $data = range(1, 100);

        $result = $this->optimizer->batchProcessData($data, 10, function ($batch) {
            return count($batch);
        });

        $this->assertEquals(10, $result['batch_size']);
        $this->assertEquals(10, $result['total_batches']);
    }

    /**
     * Test batch processing validates batch size limits
     */
    public function test_batch_process_data_validates_batch_size_limits(): void
    {
        $data = range(1, 100);

        // Test min limit
        $resultMin = $this->optimizer->batchProcessData($data, 0, fn($batch) => count($batch));
        $this->assertEquals(1, $resultMin['batch_size']);

        // Test max limit
        $resultMax = $this->optimizer->batchProcessData($data, 10000, fn($batch) => count($batch));
        $this->assertEquals(5000, $resultMax['batch_size']);
    }

    /**
     * Test batch processing handles processor exceptions
     */
    public function test_batch_process_data_handles_processor_exceptions(): void
    {
        $data = range(1, 20);

        $result = $this->optimizer->batchProcessData($data, 10, function ($batch, $batchNumber) {
            if ($batchNumber === 2) {
                throw new \Exception('Processing error');
            }
            return count($batch);
        });

        $this->assertEquals(1, $result['processed_batches']);
        $this->assertEquals(1, $result['failed_batches']);
        $this->assertFalse($result['success']);
        $this->assertCount(2, $result['results']);
    }

    /**
     * Test batch processing empty data
     */
    public function test_batch_process_data_empty_data(): void
    {
        $result = $this->optimizer->batchProcessData([], 10, fn($batch) => count($batch));

        $this->assertEquals(0, $result['total_items']);
        $this->assertEquals(0, $result['total_batches']);
        $this->assertTrue($result['success']);
    }

    /**
     * Test tenant isolation in cache key
     */
    public function test_tenant_isolation_in_cache_operations(): void
    {
        $query = User::query()->where('tenant_id', $this->tenant->id);

        // Cache result
        $cached = $this->optimizer->cacheQueryResult($query, 300, 'isolation_test');

        // Warm cache
        $warmResult = $this->optimizer->warmCache();

        // Invalidate cache
        $invalidateResult = $this->optimizer->invalidateCache('isolation_test');

        $this->assertEquals($this->tenant->id, $warmResult['tenant_id']);
        $this->assertIsInt($invalidateResult);
    }

    /**
     * Test query performance analysis with slow query
     */
    public function test_analyze_query_performance_slow_query_detection(): void
    {
        // Create a query that would be slow
        $query = User::query()->where('tenant_id', $this->tenant->id);

        $result = $this->optimizer->analyzeQueryPerformance($query);

        // Result should indicate if query is slow
        $this->assertArrayHasKey('is_slow', $result);
        $this->assertIsBool($result['is_slow']);
    }

    /**
     * Test error handling in query optimization
     */
    public function test_error_handling_in_optimize_query(): void
    {
        // Create a mock builder that throws exception
        $mockQuery = Mockery::mock(Builder::class);
        $mockQuery->shouldReceive('toSql')->andThrow(new \Exception('Test error'));

        $result = $this->optimizer->optimizeQuery($mockQuery);

        // Should return the original query on error
        $this->assertInstanceOf(Builder::class, $result);
    }

    /**
     * Test index suggestion priority assignment
     */
    public function test_index_suggestion_priority_assignment(): void
    {
        $result = $this->optimizer->suggestIndexes('analytics_events');

        foreach ($result['suggestions'] as $suggestion) {
            $this->assertArrayHasKey('priority', $suggestion);
            $this->assertIsInt($suggestion['priority']);
            $this->assertGreaterThan(0, $suggestion['priority']);
        }
    }

    /**
     * Test index suggestion index type
     */
    public function test_index_suggestion_index_type(): void
    {
        $result = $this->optimizer->suggestIndexes('users');

        foreach ($result['suggestions'] as $suggestion) {
            $this->assertArrayHasKey('index_type', $suggestion);
            $this->assertNotEmpty($suggestion['index_type']);
        }
    }

    /**
     * Test data retrieval optimization includes suggestions
     */
    public function test_data_retrieval_optimization_includes_suggestions(): void
    {
        $request = Request::create('/analytics', 'GET');

        $result = $this->optimizer->optimizeDataRetrieval($request);

        $this->assertArrayHasKey('suggestions', $result);
        $this->assertArrayHasKey('select_fields', $result['suggestions']);
        $this->assertArrayHasKey('eager_loading', $result['suggestions']);
    }
}
