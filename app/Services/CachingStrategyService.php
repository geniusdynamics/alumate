<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CachingStrategyService
{
    private array $cacheHitRates = [];

    private array $cacheKeys = [];

    // Cache TTL constants (in seconds)
    const HOMEPAGE_STATISTICS_TTL = 3600; // 1 hour

    const TESTIMONIALS_TTL = 1800; // 30 minutes

    const SUCCESS_STORIES_TTL = 1800; // 30 minutes

    const JOB_MATCHES_TTL = 900; // 15 minutes

    const USER_PREFERENCES_TTL = 7200; // 2 hours

    const STATIC_CONTENT_TTL = 86400; // 24 hours

    const AB_TEST_CONFIG_TTL = 3600; // 1 hour

    public function __construct()
    {
        $this->initializeCacheMetrics();
    }

    /**
     * Initialize cache metrics tracking
     */
    private function initializeCacheMetrics(): void
    {
        $this->cacheKeys = [
            'homepage_statistics',
            'homepage_testimonials',
            'homepage_success_stories',
            'homepage_job_matches',
            'homepage_ab_tests',
            'homepage_static_content',
        ];

        foreach ($this->cacheKeys as $key) {
            $this->cacheHitRates[$key] = ['hits' => 0, 'misses' => 0];
        }
    }

    /**
     * Get homepage statistics with multi-layer caching
     */
    public function getHomepageStatistics(): array
    {
        $cacheKey = 'homepage_statistics';

        // Try L1 cache (in-memory)
        $data = $this->getFromL1Cache($cacheKey);
        if ($data !== null) {
            $this->recordCacheHit($cacheKey);

            return $data;
        }

        // Try L2 cache (Redis)
        $data = $this->getFromL2Cache($cacheKey);
        if ($data !== null) {
            $this->setL1Cache($cacheKey, $data, 300); // 5 minutes in L1
            $this->recordCacheHit($cacheKey);

            return $data;
        }

        // Cache miss - fetch from database
        $this->recordCacheMiss($cacheKey);
        $data = $this->fetchHomepageStatisticsFromDB();

        // Store in both cache layers
        $this->setL2Cache($cacheKey, $data, self::HOMEPAGE_STATISTICS_TTL);
        $this->setL1Cache($cacheKey, $data, 300);

        return $data;
    }

    /**
     * Get testimonials with intelligent caching
     */
    public function getTestimonials(int $limit = 6, array $filters = []): array
    {
        $cacheKey = 'homepage_testimonials_'.md5(serialize(['limit' => $limit, 'filters' => $filters]));

        return Cache::remember($cacheKey, self::TESTIMONIALS_TTL, function () use ($limit, $filters) {
            return $this->fetchTestimonialsFromDB($limit, $filters);
        });
    }

    /**
     * Get success stories with pagination caching
     */
    public function getSuccessStories(int $page = 1, int $limit = 12, array $filters = []): array
    {
        $cacheKey = 'homepage_success_stories_'.md5(serialize([
            'page' => $page,
            'limit' => $limit,
            'filters' => $filters,
        ]));

        return Cache::remember($cacheKey, self::SUCCESS_STORIES_TTL, function () use ($page, $limit, $filters) {
            return $this->fetchSuccessStoriesFromDB($page, $limit, $filters);
        });
    }

    /**
     * Get job matches with user-specific caching
     */
    public function getJobMatches(int $graduateId, int $limit = 10): array
    {
        $cacheKey = "job_matches_{$graduateId}_{$limit}";

        return Cache::remember($cacheKey, self::JOB_MATCHES_TTL, function () use ($graduateId, $limit) {
            return $this->fetchJobMatchesFromDB($graduateId, $limit);
        });
    }

    /**
     * Cache A/B test configurations
     */
    public function getABTestConfig(string $testId): ?array
    {
        $cacheKey = "ab_test_config_{$testId}";

        return Cache::remember($cacheKey, self::AB_TEST_CONFIG_TTL, function () use ($testId) {
            return $this->fetchABTestConfigFromDB($testId);
        });
    }

    /**
     * Implement cache warming strategy
     */
    public function warmCache(): void
    {
        Log::info('Starting cache warming process');

        $startTime = microtime(true);

        // Warm homepage statistics
        $this->getHomepageStatistics();

        // Warm testimonials
        $this->getTestimonials(6);
        $this->getTestimonials(12);

        // Warm success stories (first few pages)
        for ($page = 1; $page <= 3; $page++) {
            $this->getSuccessStories($page, 12);
        }

        // Warm popular job matches (for active job seekers)
        $activeJobSeekers = DB::table('graduates')
            ->where('job_search_active', true)
            ->limit(100)
            ->pluck('id');

        foreach ($activeJobSeekers as $graduateId) {
            $this->getJobMatches($graduateId, 10);
        }

        // Warm static content
        $this->warmStaticContent();

        $duration = microtime(true) - $startTime;
        Log::info('Cache warming completed', ['duration' => $duration]);
    }

    /**
     * Implement intelligent cache invalidation
     */
    public function invalidateRelatedCaches(string $entity, int $entityId): void
    {
        switch ($entity) {
            case 'graduate':
                $this->invalidateGraduateRelatedCaches($entityId);
                break;
            case 'job':
                $this->invalidateJobRelatedCaches($entityId);
                break;
            case 'testimonial':
                $this->invalidateTestimonialRelatedCaches();
                break;
            case 'success_story':
                $this->invalidateSuccessStoryRelatedCaches();
                break;
            case 'employer':
                $this->invalidateEmployerRelatedCaches($entityId);
                break;
        }

        // Always invalidate homepage statistics when any entity changes
        $this->invalidateCache('homepage_statistics');
    }

    /**
     * Get cache performance metrics
     */
    public function getCacheMetrics(): array
    {
        $metrics = [];

        foreach ($this->cacheKeys as $key) {
            $hits = $this->cacheHitRates[$key]['hits'];
            $misses = $this->cacheHitRates[$key]['misses'];
            $total = $hits + $misses;

            $metrics[$key] = [
                'hits' => $hits,
                'misses' => $misses,
                'hit_rate' => $total > 0 ? ($hits / $total) * 100 : 0,
                'total_requests' => $total,
            ];
        }

        // Get Redis metrics if available
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $metrics['redis'] = $this->getRedisMetrics();
        }

        return $metrics;
    }

    /**
     * Optimize cache configuration
     */
    public function optimizeCacheConfiguration(): void
    {
        // Set optimal Redis configuration
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $this->optimizeRedisConfiguration();
        }

        // Configure cache serialization
        $this->configureCacheSerialization();

        // Set up cache compression
        $this->configureCacheCompression();
    }

    /**
     * Implement cache preloading for critical paths
     */
    public function preloadCriticalData(): void
    {
        $criticalData = [
            'homepage_statistics' => fn () => $this->getHomepageStatistics(),
            'featured_testimonials' => fn () => $this->getTestimonials(6, ['featured' => true]),
            'recent_success_stories' => fn () => $this->getSuccessStories(1, 6),
            'active_job_count' => fn () => $this->getActiveJobCount(),
            'platform_metrics' => fn () => $this->getPlatformMetrics(),
        ];

        foreach ($criticalData as $key => $loader) {
            if (! Cache::has($key)) {
                Cache::put($key, $loader(), self::STATIC_CONTENT_TTL);
            }
        }
    }

    /**
     * L1 Cache operations (in-memory)
     */
    private function getFromL1Cache(string $key)
    {
        static $l1Cache = [];

        if (isset($l1Cache[$key]) && $l1Cache[$key]['expires'] > time()) {
            return $l1Cache[$key]['data'];
        }

        return null;
    }

    private function setL1Cache(string $key, $data, int $ttl): void
    {
        static $l1Cache = [];

        $l1Cache[$key] = [
            'data' => $data,
            'expires' => time() + $ttl,
        ];
    }

    /**
     * L2 Cache operations (Redis)
     */
    private function getFromL2Cache(string $key)
    {
        return Cache::get($key);
    }

    private function setL2Cache(string $key, $data, int $ttl): void
    {
        Cache::put($key, $data, $ttl);
    }

    /**
     * Database fetch operations
     */
    private function fetchHomepageStatisticsFromDB(): array
    {
        return DB::select("
            SELECT 
                (SELECT COUNT(*) FROM graduates) as total_alumni,
                (SELECT COUNT(*) FROM graduates WHERE JSON_EXTRACT(employment_status, '$.status') = 'employed') as employed_alumni,
                (SELECT COUNT(*) FROM jobs WHERE status = 'active') as active_jobs,
                (SELECT COUNT(DISTINCT employer_id) FROM jobs) as companies_represented,
                (SELECT COUNT(*) FROM job_applications WHERE status = 'hired') as successful_placements,
                (SELECT AVG(CAST(JSON_EXTRACT(salary_range, '$.max') AS UNSIGNED)) FROM graduates WHERE JSON_EXTRACT(employment_status, '$.status') = 'employed') as average_salary
        ")[0];
    }

    private function fetchTestimonialsFromDB(int $limit, array $filters): array
    {
        $query = DB::table('testimonials as t')
            ->join('graduates as g', 't.graduate_id', '=', 'g.id')
            ->join('courses as c', 'g.course_id', '=', 'c.id')
            ->select([
                'g.name',
                'g.profile_image',
                'c.name as course_name',
                't.testimonial',
                't.rating',
                'g.graduation_year',
            ])
            ->where('t.approved', true);

        if (isset($filters['featured']) && $filters['featured']) {
            $query->where('t.featured', true);
        }

        return $query->orderBy('t.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function fetchSuccessStoriesFromDB(int $page, int $limit, array $filters): array
    {
        $offset = ($page - 1) * $limit;

        $query = DB::table('success_stories as ss')
            ->join('graduates as g', 'ss.graduate_id', '=', 'g.id')
            ->join('courses as c', 'g.course_id', '=', 'c.id')
            ->select([
                'g.id',
                'g.name',
                'g.profile_image',
                'g.graduation_year',
                'c.name as course_name',
                'ss.story_title',
                'ss.story_summary',
                'ss.career_progression',
                'ss.key_achievements',
            ])
            ->where('ss.approved', true);

        if (isset($filters['featured']) && $filters['featured']) {
            $query->where('ss.featured', true);
        }

        return $query->orderBy('ss.created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function fetchJobMatchesFromDB(int $graduateId, int $limit): array
    {
        // Implementation would depend on your job matching algorithm
        return DB::table('jobs as j')
            ->join('employers as e', 'j.employer_id', '=', 'e.id')
            ->select(['j.*', 'e.name as employer_name'])
            ->where('j.status', 'active')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function fetchABTestConfigFromDB(string $testId): ?array
    {
        return DB::table('ab_tests')
            ->where('id', $testId)
            ->where('active', true)
            ->first();
    }

    /**
     * Cache invalidation methods
     */
    private function invalidateGraduateRelatedCaches(int $graduateId): void
    {
        Cache::forget("job_matches_{$graduateId}_10");
        Cache::forget("job_matches_{$graduateId}_20");
        $this->invalidateCache('homepage_statistics');
        $this->invalidatePatternCache('homepage_testimonials_*');
        $this->invalidatePatternCache('homepage_success_stories_*');
    }

    private function invalidateJobRelatedCaches(int $jobId): void
    {
        $this->invalidateCache('homepage_statistics');
        $this->invalidatePatternCache('job_matches_*');
    }

    private function invalidateTestimonialRelatedCaches(): void
    {
        $this->invalidatePatternCache('homepage_testimonials_*');
    }

    private function invalidateSuccessStoryRelatedCaches(): void
    {
        $this->invalidatePatternCache('homepage_success_stories_*');
    }

    private function invalidateEmployerRelatedCaches(int $employerId): void
    {
        $this->invalidateCache('homepage_statistics');
        $this->invalidatePatternCache('job_matches_*');
    }

    private function invalidateCache(string $key): void
    {
        Cache::forget($key);
    }

    private function invalidatePatternCache(string $pattern): void
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $keys = Redis::keys($pattern);
            if (! empty($keys)) {
                Redis::del($keys);
            }
        }
    }

    /**
     * Cache metrics and monitoring
     */
    private function recordCacheHit(string $key): void
    {
        if (isset($this->cacheHitRates[$key])) {
            $this->cacheHitRates[$key]['hits']++;
        }
    }

    private function recordCacheMiss(string $key): void
    {
        if (isset($this->cacheHitRates[$key])) {
            $this->cacheHitRates[$key]['misses']++;
        }
    }

    private function getRedisMetrics(): array
    {
        try {
            $info = Redis::info();

            return [
                'used_memory' => $info['used_memory'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? '0B',
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get Redis metrics', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function optimizeRedisConfiguration(): void
    {
        try {
            // Set optimal Redis configurations
            Redis::config('SET', 'maxmemory-policy', 'allkeys-lru');
            Redis::config('SET', 'timeout', '300');
            Redis::config('SET', 'tcp-keepalive', '60');
        } catch (\Exception $e) {
            Log::warning('Failed to optimize Redis configuration', ['error' => $e->getMessage()]);
        }
    }

    private function configureCacheSerialization(): void
    {
        // Configure optimal serialization method
        if (extension_loaded('igbinary')) {
            ini_set('session.serialize_handler', 'igbinary');
        }
    }

    private function configureCacheCompression(): void
    {
        // Enable compression for large cache values
        if (extension_loaded('zlib')) {
            ini_set('zlib.output_compression', '1');
        }
    }

    private function warmStaticContent(): void
    {
        $staticContent = [
            'platform_features',
            'pricing_tiers',
            'company_logos',
            'trust_badges',
            'faq_content',
        ];

        foreach ($staticContent as $content) {
            Cache::remember("static_{$content}", self::STATIC_CONTENT_TTL, function () use ($content) {
                return $this->fetchStaticContent($content);
            });
        }
    }

    private function fetchStaticContent(string $type): array
    {
        // Fetch static content from database or configuration
        return DB::table('static_content')
            ->where('type', $type)
            ->where('active', true)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    private function getActiveJobCount(): int
    {
        return DB::table('jobs')->where('status', 'active')->count();
    }

    private function getPlatformMetrics(): array
    {
        return [
            'total_users' => DB::table('users')->count(),
            'active_employers' => DB::table('employers')->where('verified', true)->count(),
            'total_courses' => DB::table('courses')->where('active', true)->count(),
            'last_updated' => now()->toISOString(),
        ];
    }
}
