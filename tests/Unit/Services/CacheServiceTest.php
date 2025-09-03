<?php

namespace Tests\Unit\Services;

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

/**
 * Unit tests for CacheService
 */
class CacheServiceTest extends TestCase
{
    private CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the cache facade to ensure isolation
        Cache::shouldReceive('remember')->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });

        Cache::shouldReceive('rememberForever')->andReturnUsing(function ($key, $callback) {
            return $callback();
        });

        Cache::shouldReceive('forget')->andReturn(true);
        Cache::shouldReceive('flush')->andReturn(true);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('has')->andReturn(false);

        $this->cacheService = new CacheService();
    }

    /**
     * Test cache service instantiation
     */
    public function test_cache_service_can_be_instantiated()
    {
        $this->assertInstanceOf(CacheService::class, $this->cacheService);
    }

    /**
     * Test getting cache key for user data
     */
    public function test_get_user_cache_key()
    {
        $userId = 123;
        $key = $this->cacheService->getUserCacheKey($userId, 'profile');

        $this->assertEquals("user_123_profile", $key);
    }

    /**
     * Test getting cache key for tenant data
     */
    public function test_get_tenant_cache_key()
    {
        $tenantId = 45;
        $key = $this->cacheService->getTenantCacheKey($tenantId, 'settings');

        $this->assertEquals("tenant_45_settings", $key);
    }

    /**
     * Test caching user data
     */
    public function test_cache_user_data()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('user_123_data', 3600, Mockery::type('callable'))
            ->andReturn(['name' => 'John']);

        $data = $this->cacheService->cacheUserData(123, 'data', function() {
            return ['name' => 'John'];
        });

        $this->assertEquals(['name' => 'John'], $data);
    }

    /**
     * Test caching tenant data
     */
    public function test_cache_tenant_data()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('tenant_45_config', 7200, Mockery::type('callable'))
            ->andReturn(['theme' => 'dark']);

        $data = $this->cacheService->cacheTenantData(45, 'config', function() {
            return ['theme' => 'dark'];
        }, 7200);

        $this->assertEquals(['theme' => 'dark'], $data);
    }

    /**
     * Test caching with default TTL
     */
    public function test_cache_data_with_default_ttl()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('test_key', 3600, Mockery::type('callable'))
            ->andReturn('test_value');

        $data = $this->cacheService->cache('test_key', function() {
            return 'test_value';
        });

        $this->assertEquals('test_value', $data);
    }

    /**
     * Test caching with custom TTL
     */
    public function test_cache_data_with_custom_ttl()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('test_key', 1800, Mockery::type('callable'))
            ->andReturn('test_value');

        $data = $this->cacheService->cache('test_key', function() {
            return 'test_value';
        }, 1800);

        $this->assertEquals('test_value', $data);
    }

    /**
     * Test permanent caching
     */
    public function test_cache_permanently()
    {
        Cache::shouldReceive('rememberForever')
            ->once()
            ->with('permanent_key', Mockery::type('callable'))
            ->andReturn('permanent_value');

        $data = $this->cacheService->cachePermanently('permanent_key', function() {
            return 'permanent_value';
        });

        $this->assertEquals('permanent_value', $data);
    }

    /**
     * Test cache invalidation for user
     */
    public function test_invalidate_user_cache()
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('user_123_profile')
            ->andReturn(true);

        $this->cacheService->invalidateUserCache(123, 'profile');
    }

    /**
     * Test cache invalidation for tenant
     */
    public function test_invalidate_tenant_cache()
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('tenant_456_settings')
            ->andReturn(true);

        $this->cacheService->invalidateTenantCache(456, 'settings');
    }

    /**
     * Test cache invalidation with pattern
     */
    public function test_invalidate_cache_pattern()
    {
        // Mock the cache tags or multiple keys scenario
        Cache::shouldReceive('forget')
            ->times(3)
            ->andReturn(true);

        // This would typically work with cache tags or batch operations
        $result = $this->cacheService->invalidatePattern('user_*');
        $this->assertTrue($result);
    }

    /**
     * Test clearing all cache
     */
    public function test_clear_all_cache()
    {
        Cache::shouldReceive('flush')
            ->once()
            ->andReturn(true);

        $result = $this->cacheService->clearAll();
        $this->assertTrue($result);
    }

    /**
     * Test getting cached data
     */
    public function test_get_cached_data()
    {
        Cache::shouldReceive('get')
            ->once()
            ->with('test_key')
            ->andReturn('cached_value');

        $data = $this->cacheService->get('test_key');
        $this->assertEquals('cached_value', $data);
    }

    /**
     * Test getting non-existent cache data
     */
    public function test_get_non_existent_cache_data()
    {
        Cache::shouldReceive('get')
            ->once()
            ->with('non_existent_key')
            ->andReturn(null);

        $data = $this->cacheService->get('non_existent_key');
        $this->assertNull($data);
    }

    /**
     * Test setting cache data
     */
    public function test_set_cache_data()
    {
        Cache::shouldReceive('put')
            ->once()
            ->with('test_key', 'test_value', 3600)
            ->andReturn(true);

        $result = $this->cacheService->set('test_key', 'test_value', 3600);
        $this->assertTrue($result);
    }

    /**
     * Test checking if key exists in cache
     */
    public function test_has_cache_key()
    {
        Cache::shouldReceive('has')
            ->once()
            ->with('existing_key')
            ->andReturn(true);

        Cache::shouldReceive('has')
            ->once()
            ->with('non_existing_key')
            ->andReturn(false);

        $this->assertTrue($this->cacheService->has('existing_key'));
        $this->assertFalse($this->cacheService->has('non_existing_key'));
    }

    /**
     * Test cache hit metrics
     */
    public function test_get_cache_metrics()
    {
        // Mock cache operations to simulate usage
        Cache::shouldReceive('remember')
            ->times(2)
            ->andReturn('data');

        Cache::shouldReceive('has')
            ->times(1)
            ->andReturn(true);

        $this->cacheService->cache('key1', fn() => 'data1');
        $this->cacheService->cache('key2', fn() => 'data2');
        $this->cacheService->has('test');

        $metrics = $this->cacheService->getMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_requests', $metrics);
        $this->assertEquals(3, $metrics['total_requests']);
    }

    /**
     * Test cache warmup functionality
     */
    public function test_warm_up_cache()
    {
        $keys = ['user_1_profile', 'user_2_profile', 'tenant_1_settings'];

        Cache::shouldReceive('remember')
            ->times(count($keys))
            ->andReturn('warmed_data');

        $result = $this->cacheService->warmUpCache($keys, function($key) {
            return "warmed_data_for_{$key}";
        });

        $this->assertTrue($result);
    }

    /**
     * Test building cache key with prefixes
     */
    public function test_build_cache_key()
    {
        $key = $this->cacheService->buildKey(['users', 123, 'profile']);

        $this->assertEquals('users:123:profile', $key);
    }

    /**
     * Test building cache key with single parameter
     */
    public function test_build_cache_key_single()
    {
        $key = $this->cacheService->buildKey('simple_key');

        $this->assertEquals('simple_key', $key);
    }

    /**
     * Test handling cache exceptions
     */
    public function test_cache_exception_handling()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new \Exception('Cache unavailable'));

        // Should return null when cache fails
        $result = $this->cacheService->cache('failing_key', function() {
            return 'fallback_data';
        });

        $this->assertNull($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
