<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class PerformanceOptimizationService
{
    private const SOCIAL_GRAPH_CACHE_PREFIX = 'social_graph:';

    private const QUERY_CACHE_PREFIX = 'query_cache:';

    private const PERFORMANCE_METRICS_KEY = 'performance_metrics';

    private const SLOW_QUERY_THRESHOLD = 1000; // milliseconds

    /**
     * Implement advanced caching strategies for social graph queries.
     */
    public function optimizeSocialGraphCaching(): void
    {
        // Cache user connections with hierarchical structure
        $this->cacheUserConnections();

        // Cache circle memberships for faster timeline generation
        $this->cacheCircleMemberships();

        // Cache group memberships
        $this->cacheGroupMemberships();

        // Pre-compute social graph metrics
        $this->preComputeSocialGraphMetrics();
    }

    /**
     * Cache user connections in Redis for O(1) lookups.
     */
    private function cacheUserConnections(): void
    {
        $connections = DB::table('connections')
            ->where('status', 'accepted')
            ->select('user_id', 'connected_user_id')
            ->get();

        $connectionMap = [];

        foreach ($connections as $connection) {
            // Bidirectional connections
            $connectionMap[$connection->user_id][] = $connection->connected_user_id;
            $connectionMap[$connection->connected_user_id][] = $connection->user_id;
        }

        foreach ($connectionMap as $userId => $connections) {
            $cacheKey = self::SOCIAL_GRAPH_CACHE_PREFIX."connections:{$userId}";
            Cache::put($cacheKey, array_unique($connections), now()->addHours(6));
        }

        Log::info('Social graph connections cached', [
            'users_processed' => count($connectionMap),
        ]);
    }

    /**
     * Cache circle memberships for faster timeline queries.
     */
    private function cacheCircleMemberships(): void
    {
        $memberships = DB::table('circle_memberships')
            ->where('status', 'active')
            ->select('user_id', 'circle_id')
            ->get()
            ->groupBy('user_id');

        foreach ($memberships as $userId => $userMemberships) {
            $circleIds = $userMemberships->pluck('circle_id')->toArray();
            $cacheKey = self::SOCIAL_GRAPH_CACHE_PREFIX."circles:{$userId}";
            Cache::put($cacheKey, $circleIds, now()->addHours(4));
        }

        // Also cache reverse mapping (circle -> users)
        $reverseMap = DB::table('circle_memberships')
            ->where('status', 'active')
            ->select('circle_id', 'user_id')
            ->get()
            ->groupBy('circle_id');

        foreach ($reverseMap as $circleId => $members) {
            $userIds = $members->pluck('user_id')->toArray();
            $cacheKey = self::SOCIAL_GRAPH_CACHE_PREFIX."circle_members:{$circleId}";
            Cache::put($cacheKey, $userIds, now()->addHours(4));
        }
    }

    /**
     * Cache group memberships for faster access.
     */
    private function cacheGroupMemberships(): void
    {
        $memberships = DB::table('group_memberships')
            ->where('status', 'active')
            ->select('user_id', 'group_id')
            ->get()
            ->groupBy('user_id');

        foreach ($memberships as $userId => $userMemberships) {
            $groupIds = $userMemberships->pluck('group_id')->toArray();
            $cacheKey = self::SOCIAL_GRAPH_CACHE_PREFIX."groups:{$userId}";
            Cache::put($cacheKey, $groupIds, now()->addHours(4));
        }
    }

    /**
     * Pre-compute social graph metrics for analytics.
     */
    private function preComputeSocialGraphMetrics(): void
    {
        $metrics = [
            'total_connections' => DB::table('connections')->where('status', 'accepted')->count(),
            'total_circles' => DB::table('circles')->count(),
            'total_groups' => DB::table('groups')->count(),
            'avg_connections_per_user' => $this->calculateAverageConnectionsPerUser(),
            'most_connected_users' => $this->getMostConnectedUsers(),
            'largest_circles' => $this->getLargestCircles(),
        ];

        Cache::put(self::SOCIAL_GRAPH_CACHE_PREFIX.'metrics', $metrics, now()->addHour());
    }

    /**
     * Get cached user connections.
     */
    public function getCachedUserConnections(int $userId): array
    {
        $cacheKey = self::SOCIAL_GRAPH_CACHE_PREFIX."connections:{$userId}";

        return Cache::get($cacheKey, []);
    }

    /**
     * Get cached user circles.
     */
    public function getCachedUserCircles(int $userId): array
    {
        $cacheKey = self::SOCIAL_GRAPH_CACHE_PREFIX."circles:{$userId}";

        return Cache::get($cacheKey, []);
    }

    /**
     * Get cached user groups.
     */
    public function getCachedUserGroups(int $userId): array
    {
        $cacheKey = self::SOCIAL_GRAPH_CACHE_PREFIX."groups:{$userId}";

        return Cache::get($cacheKey, []);
    }

    /**
     * Optimize database queries for timeline generation.
     */
    public function optimizeTimelineQueries(): void
    {
        // Create optimized indexes if they don't exist
        $this->createOptimizedIndexes();

        // Analyze and optimize slow queries
        $this->analyzeSlowQueries();

        // Pre-compute timeline segments for active users
        $this->preComputeTimelineSegments();
    }

    /**
     * Create optimized database indexes for timeline queries.
     */
    private function createOptimizedIndexes(): void
    {
        $indexes = [
            'posts_timeline_idx' => 'CREATE INDEX IF NOT EXISTS posts_timeline_idx ON posts (created_at DESC, visibility, user_id)',
            'posts_circles_gin_idx' => 'CREATE INDEX IF NOT EXISTS posts_circles_gin_idx ON posts USING GIN (circle_ids)',
            'posts_groups_gin_idx' => 'CREATE INDEX IF NOT EXISTS posts_groups_gin_idx ON posts USING GIN (group_ids)',
            'post_engagements_user_post_idx' => 'CREATE INDEX IF NOT EXISTS post_engagements_user_post_idx ON post_engagements (user_id, post_id, created_at)',
            'connections_composite_idx' => 'CREATE INDEX IF NOT EXISTS connections_composite_idx ON connections (user_id, connected_user_id, status)',
            'circle_memberships_composite_idx' => 'CREATE INDEX IF NOT EXISTS circle_memberships_composite_idx ON circle_memberships (user_id, circle_id, status)',
            'group_memberships_composite_idx' => 'CREATE INDEX IF NOT EXISTS group_memberships_composite_idx ON group_memberships (user_id, group_id, status)',
        ];

        foreach ($indexes as $name => $sql) {
            try {
                DB::statement($sql);
                Log::info("Created index: {$name}");
            } catch (\Exception $e) {
                Log::warning("Failed to create index {$name}: ".$e->getMessage());
            }
        }
    }

    /**
     * Analyze slow queries and log performance issues.
     */
    private function analyzeSlowQueries(): void
    {
        // Enable query logging temporarily
        DB::enableQueryLog();

        // Run sample timeline generation to capture queries
        $sampleUser = User::inRandomOrder()->first();
        if ($sampleUser) {
            $timelineService = app(TimelineService::class);
            $start = microtime(true);
            $timelineService->generateTimelineForUser($sampleUser, 20);
            $duration = (microtime(true) - $start) * 1000;

            $queries = DB::getQueryLog();
            $slowQueries = array_filter($queries, function ($query) {
                return $query['time'] > self::SLOW_QUERY_THRESHOLD;
            });

            if (! empty($slowQueries)) {
                Log::warning('Slow timeline queries detected', [
                    'total_duration' => $duration,
                    'slow_queries' => count($slowQueries),
                    'queries' => $slowQueries,
                ]);
            }
        }

        DB::disableQueryLog();
    }

    /**
     * Pre-compute timeline segments for active users.
     */
    private function preComputeTimelineSegments(): void
    {
        $activeUsers = User::where('last_activity_at', '>=', now()->subHours(24))
            ->limit(100)
            ->get();

        $timelineService = app(TimelineService::class);

        foreach ($activeUsers as $user) {
            try {
                // Pre-generate timeline for active users
                $timeline = $timelineService->generateTimelineForUser($user, 50);

                // Cache timeline segments
                $cacheKey = self::QUERY_CACHE_PREFIX."timeline_segments:{$user->id}";
                Cache::put($cacheKey, $timeline, now()->addMinutes(30));

            } catch (\Exception $e) {
                Log::error("Failed to pre-compute timeline for user {$user->id}: ".$e->getMessage());
            }
        }
    }

    /**
     * Monitor performance metrics and create alerts.
     */
    public function monitorPerformanceMetrics(): array
    {
        $metrics = [
            'cache_hit_rate' => $this->calculateCacheHitRate(),
            'average_query_time' => $this->calculateAverageQueryTime(),
            'active_connections' => $this->getActiveConnectionsCount(),
            'memory_usage' => memory_get_usage(true),
            'redis_memory_usage' => $this->getRedisMemoryUsage(),
            'slow_queries_count' => $this->getSlowQueriesCount(),
            'timeline_generation_time' => $this->measureTimelineGenerationTime(),
        ];

        // Store metrics for historical tracking
        $this->storePerformanceMetrics($metrics);

        // Check for performance alerts
        $this->checkPerformanceAlerts($metrics);

        return $metrics;
    }

    /**
     * Calculate cache hit rate.
     */
    private function calculateCacheHitRate(): float
    {
        try {
            if (! extension_loaded('redis')) {
                return 0;
            }

            $redis = Redis::connection();
            $info = $redis->info('stats');

            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            $total = $hits + $misses;

            return $total > 0 ? ($hits / $total) * 100 : 0;
        } catch (\Exception $e) {
            Log::warning('Failed to calculate cache hit rate', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Calculate average query time from recent metrics.
     */
    private function calculateAverageQueryTime(): float
    {
        $recentMetrics = Cache::get(self::PERFORMANCE_METRICS_KEY.':query_times', []);

        if (empty($recentMetrics)) {
            return 0;
        }

        return array_sum($recentMetrics) / count($recentMetrics);
    }

    /**
     * Get active database connections count.
     */
    private function getActiveConnectionsCount(): int
    {
        try {
            $result = DB::select("SELECT count(*) as count FROM pg_stat_activity WHERE state = 'active'");

            return $result[0]->count ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get Redis memory usage.
     */
    private function getRedisMemoryUsage(): array
    {
        try {
            if (! extension_loaded('redis')) {
                return [
                    'used_memory' => 0,
                    'used_memory_human' => 'Redis not available',
                    'used_memory_peak' => 0,
                    'used_memory_peak_human' => 'Redis not available',
                ];
            }

            $redis = Redis::connection();
            $info = $redis->info('memory');

            return [
                'used_memory' => $info['used_memory'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? '0B',
                'used_memory_peak' => $info['used_memory_peak'] ?? 0,
                'used_memory_peak_human' => $info['used_memory_peak_human'] ?? '0B',
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get Redis memory usage', ['error' => $e->getMessage()]);

            return [
                'used_memory' => 0,
                'used_memory_human' => 'Redis unavailable',
                'used_memory_peak' => 0,
                'used_memory_peak_human' => 'Redis unavailable',
            ];
        }
    }

    /**
     * Get count of slow queries in the last hour.
     */
    private function getSlowQueriesCount(): int
    {
        return Cache::get(self::PERFORMANCE_METRICS_KEY.':slow_queries_count', 0);
    }

    /**
     * Measure timeline generation time for sample user.
     */
    private function measureTimelineGenerationTime(): float
    {
        $sampleUser = User::inRandomOrder()->first();
        if (! $sampleUser) {
            return 0;
        }

        $timelineService = app(TimelineService::class);
        $start = microtime(true);
        $timelineService->generateTimelineForUser($sampleUser, 20);

        return (microtime(true) - $start) * 1000; // Convert to milliseconds
    }

    /**
     * Store performance metrics for historical tracking.
     */
    private function storePerformanceMetrics(array $metrics): void
    {
        $timestamp = now()->toISOString();
        $metricsWithTimestamp = array_merge($metrics, ['timestamp' => $timestamp]);

        // Store in Redis with TTL
        $key = self::PERFORMANCE_METRICS_KEY.':'.date('Y-m-d-H');
        $existingMetrics = Cache::get($key, []);
        $existingMetrics[] = $metricsWithTimestamp;

        // Keep only last 24 hours of metrics
        $existingMetrics = array_slice($existingMetrics, -24);

        Cache::put($key, $existingMetrics, now()->addHours(25));
    }

    /**
     * Check performance metrics against thresholds and create alerts.
     */
    private function checkPerformanceAlerts(array $metrics): void
    {
        $alerts = [];

        // Cache hit rate alert
        if ($metrics['cache_hit_rate'] < 80) {
            $alerts[] = [
                'type' => 'low_cache_hit_rate',
                'message' => "Cache hit rate is low: {$metrics['cache_hit_rate']}%",
                'severity' => 'warning',
            ];
        }

        // Query time alert
        if ($metrics['average_query_time'] > 500) {
            $alerts[] = [
                'type' => 'slow_queries',
                'message' => "Average query time is high: {$metrics['average_query_time']}ms",
                'severity' => 'warning',
            ];
        }

        // Memory usage alert
        $memoryUsageMB = $metrics['memory_usage'] / 1024 / 1024;
        if ($memoryUsageMB > 512) {
            $alerts[] = [
                'type' => 'high_memory_usage',
                'message' => "High memory usage: {$memoryUsageMB}MB",
                'severity' => 'critical',
            ];
        }

        // Timeline generation time alert
        if ($metrics['timeline_generation_time'] > 2000) {
            $alerts[] = [
                'type' => 'slow_timeline_generation',
                'message' => "Timeline generation is slow: {$metrics['timeline_generation_time']}ms",
                'severity' => 'warning',
            ];
        }

        // Log alerts
        foreach ($alerts as $alert) {
            Log::channel('performance')->warning('Performance Alert', $alert);
        }

        // Store alerts for dashboard
        if (! empty($alerts)) {
            Cache::put(self::PERFORMANCE_METRICS_KEY.':alerts', $alerts, now()->addHour());
        }
    }

    /**
     * Get performance budget status.
     */
    public function getPerformanceBudgetStatus(): array
    {
        $budgets = [
            'timeline_generation' => ['budget' => 1000, 'current' => $this->measureTimelineGenerationTime()],
            'cache_hit_rate' => ['budget' => 85, 'current' => $this->calculateCacheHitRate()],
            'memory_usage_mb' => ['budget' => 256, 'current' => memory_get_usage(true) / 1024 / 1024],
            'active_connections' => ['budget' => 50, 'current' => $this->getActiveConnectionsCount()],
        ];

        foreach ($budgets as $metric => &$budget) {
            $budget['status'] = $this->getBudgetStatus($budget['current'], $budget['budget'], $metric);
            $budget['percentage'] = $this->calculateBudgetPercentage($budget['current'], $budget['budget'], $metric);
        }

        return $budgets;
    }

    /**
     * Get budget status (within_budget, approaching_limit, over_budget).
     */
    private function getBudgetStatus(float $current, float $budget, string $metric): string
    {
        $isInverse = in_array($metric, ['cache_hit_rate']); // Higher is better for these metrics

        if ($isInverse) {
            if ($current >= $budget) {
                return 'within_budget';
            }
            if ($current >= $budget * 0.8) {
                return 'approaching_limit';
            }

            return 'over_budget';
        } else {
            if ($current <= $budget) {
                return 'within_budget';
            }
            if ($current <= $budget * 1.2) {
                return 'approaching_limit';
            }

            return 'over_budget';
        }
    }

    /**
     * Calculate budget percentage.
     */
    private function calculateBudgetPercentage(float $current, float $budget, string $metric): float
    {
        $isInverse = in_array($metric, ['cache_hit_rate']);

        if ($isInverse) {
            return min(100, ($current / $budget) * 100);
        } else {
            return min(100, ($current / $budget) * 100);
        }
    }

    /**
     * Clear all performance caches.
     */
    public function clearPerformanceCaches(): void
    {
        $patterns = [
            self::SOCIAL_GRAPH_CACHE_PREFIX.'*',
            self::QUERY_CACHE_PREFIX.'*',
            'timeline:user:*',
        ];

        foreach ($patterns as $pattern) {
            $keys = Cache::getRedis()->keys($pattern);
            if (! empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }

        Log::info('Performance caches cleared');
    }

    /**
     * Implement CDN integration for media assets.
     */
    public function optimizeCdnIntegration(): array
    {
        $cdnConfig = config('filesystems.disks.cdn', []);
        $results = [];

        if (empty($cdnConfig)) {
            Log::warning('CDN configuration not found');

            return ['status' => 'error', 'message' => 'CDN not configured'];
        }

        try {
            // Analyze media assets that should be served via CDN
            $mediaAssets = $this->analyzeMediaAssets();

            // Configure CDN cache headers
            $this->configureCdnCacheHeaders();

            // Implement image optimization
            $this->optimizeImageDelivery();

            // Set up CDN purging capabilities
            $this->setupCdnPurging();

            $results = [
                'status' => 'success',
                'assets_analyzed' => count($mediaAssets),
                'cdn_endpoints' => $this->getCdnEndpoints(),
                'cache_headers_configured' => true,
                'image_optimization_enabled' => true,
                'purging_configured' => true,
                'recommendations' => $this->generateCdnRecommendations($mediaAssets),
            ];

            Log::info('CDN integration optimized', $results);

        } catch (\Exception $e) {
            Log::error('CDN optimization failed', ['error' => $e->getMessage()]);
            $results = ['status' => 'error', 'message' => $e->getMessage()];
        }

        return $results;
    }

    /**
     * Analyze media assets for CDN optimization.
     */
    private function analyzeMediaAssets(): array
    {
        $assets = [];

        // Analyze profile images
        $profileImages = DB::table('users')
            ->whereNotNull('avatar_url')
            ->select('avatar_url', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('avatar_url')
            ->orderBy('usage_count', 'desc')
            ->limit(100)
            ->get();

        foreach ($profileImages as $image) {
            $assets[] = [
                'type' => 'profile_image',
                'url' => $image->avatar_url,
                'usage_count' => $image->usage_count,
                'priority' => 'high',
            ];
        }

        // Analyze post media
        $postMedia = DB::table('posts')
            ->whereNotNull('media_urls')
            ->select('media_urls', DB::raw('COUNT(*) as post_count'))
            ->groupBy('media_urls')
            ->orderBy('post_count', 'desc')
            ->limit(200)
            ->get();

        foreach ($postMedia as $media) {
            $mediaUrls = json_decode($media->media_urls, true) ?? [];
            foreach ($mediaUrls as $url) {
                $assets[] = [
                    'type' => 'post_media',
                    'url' => $url,
                    'usage_count' => $media->post_count,
                    'priority' => 'medium',
                ];
            }
        }

        // Analyze static assets
        $staticAssets = [
            '/build/assets/app.css' => ['type' => 'css', 'priority' => 'critical'],
            '/build/assets/app.js' => ['type' => 'js', 'priority' => 'critical'],
            '/images/logo.png' => ['type' => 'logo', 'priority' => 'high'],
            '/images/favicon.ico' => ['type' => 'favicon', 'priority' => 'high'],
        ];

        foreach ($staticAssets as $url => $info) {
            $assets[] = array_merge($info, ['url' => $url, 'usage_count' => 'all_pages']);
        }

        return $assets;
    }

    /**
     * Configure CDN cache headers.
     */
    private function configureCdnCacheHeaders(): void
    {
        $cacheRules = [
            'images' => [
                'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
                'max_age' => 31536000, // 1 year
                'immutable' => true,
            ],
            'css_js' => [
                'extensions' => ['css', 'js'],
                'max_age' => 31536000, // 1 year
                'immutable' => true,
            ],
            'fonts' => [
                'extensions' => ['woff', 'woff2', 'ttf', 'eot'],
                'max_age' => 31536000, // 1 year
                'immutable' => true,
            ],
            'documents' => [
                'extensions' => ['pdf', 'doc', 'docx'],
                'max_age' => 86400, // 1 day
                'immutable' => false,
            ],
        ];

        Cache::put('cdn_cache_rules', $cacheRules, now()->addHours(24));
    }

    /**
     * Optimize image delivery through CDN.
     */
    private function optimizeImageDelivery(): void
    {
        $optimizations = [
            'webp_conversion' => true,
            'responsive_images' => true,
            'lazy_loading' => true,
            'compression_levels' => [
                'thumbnail' => 80,
                'medium' => 85,
                'large' => 90,
            ],
            'sizes' => [
                'thumbnail' => ['width' => 150, 'height' => 150],
                'small' => ['width' => 300, 'height' => 300],
                'medium' => ['width' => 600, 'height' => 600],
                'large' => ['width' => 1200, 'height' => 1200],
            ],
        ];

        Cache::put('image_optimization_config', $optimizations, now()->addHours(24));
    }

    /**
     * Set up CDN purging capabilities.
     */
    private function setupCdnPurging(): void
    {
        $purgingConfig = [
            'auto_purge_on_update' => true,
            'batch_purging' => true,
            'purge_patterns' => [
                'user_avatar_update' => '/images/avatars/{user_id}/*',
                'post_media_update' => '/images/posts/{post_id}/*',
                'static_asset_update' => '/build/assets/*',
            ],
            'purge_delay' => 300, // 5 minutes delay for batch purging
        ];

        Cache::put('cdn_purging_config', $purgingConfig, now()->addHours(24));
    }

    /**
     * Get CDN endpoints configuration.
     */
    private function getCdnEndpoints(): array
    {
        return [
            'primary' => config('app.cdn_url', config('app.url')),
            'images' => config('app.cdn_images_url', config('app.cdn_url', config('app.url'))),
            'assets' => config('app.cdn_assets_url', config('app.cdn_url', config('app.url'))),
            'media' => config('app.cdn_media_url', config('app.cdn_url', config('app.url'))),
        ];
    }

    /**
     * Generate CDN optimization recommendations.
     */
    private function generateCdnRecommendations(array $assets): array
    {
        $recommendations = [];

        // Analyze asset sizes and usage
        $highUsageAssets = array_filter($assets, function ($asset) {
            return is_numeric($asset['usage_count']) && $asset['usage_count'] > 100;
        });

        if (count($highUsageAssets) > 50) {
            $recommendations[] = [
                'type' => 'high_usage_assets',
                'message' => 'Consider implementing automatic CDN distribution for high-usage assets',
                'priority' => 'high',
                'assets_count' => count($highUsageAssets),
            ];
        }

        // Check for unoptimized images
        $imageAssets = array_filter($assets, function ($asset) {
            return in_array($asset['type'], ['profile_image', 'post_media']);
        });

        if (count($imageAssets) > 0) {
            $recommendations[] = [
                'type' => 'image_optimization',
                'message' => 'Enable WebP conversion and responsive images for better performance',
                'priority' => 'medium',
                'potential_savings' => '30-50% bandwidth reduction',
            ];
        }

        // Check for critical assets
        $criticalAssets = array_filter($assets, function ($asset) {
            return $asset['priority'] === 'critical';
        });

        if (count($criticalAssets) > 0) {
            $recommendations[] = [
                'type' => 'critical_assets',
                'message' => 'Ensure critical assets are preloaded and have optimal cache headers',
                'priority' => 'high',
                'assets' => array_column($criticalAssets, 'url'),
            ];
        }

        return $recommendations;
    }

    /**
     * Create automated performance optimization alerts.
     */
    public function setupAutomatedAlerts(): array
    {
        $alertRules = [
            'cache_hit_rate_low' => [
                'condition' => 'cache_hit_rate < 80',
                'severity' => 'warning',
                'frequency' => 'every_5_minutes',
                'action' => 'optimize_caching',
            ],
            'query_time_high' => [
                'condition' => 'average_query_time > 500',
                'severity' => 'critical',
                'frequency' => 'every_minute',
                'action' => 'optimize_queries',
            ],
            'memory_usage_high' => [
                'condition' => 'memory_usage > 512MB',
                'severity' => 'warning',
                'frequency' => 'every_10_minutes',
                'action' => 'clear_caches',
            ],
            'timeline_generation_slow' => [
                'condition' => 'timeline_generation_time > 2000',
                'severity' => 'critical',
                'frequency' => 'every_minute',
                'action' => 'optimize_social_graph',
            ],
            'redis_memory_high' => [
                'condition' => 'redis_memory_usage > 1GB',
                'severity' => 'warning',
                'frequency' => 'every_15_minutes',
                'action' => 'optimize_redis',
            ],
        ];

        // Store alert rules in cache
        Cache::put('performance_alert_rules', $alertRules, now()->addDays(7));

        // Set up monitoring schedule
        $this->schedulePerformanceMonitoring();

        return [
            'status' => 'success',
            'rules_configured' => count($alertRules),
            'monitoring_enabled' => true,
            'alert_channels' => ['log', 'cache', 'webhook'],
        ];
    }

    /**
     * Schedule automated performance monitoring.
     */
    private function schedulePerformanceMonitoring(): void
    {
        $schedule = [
            'every_minute' => ['query_time_high', 'timeline_generation_slow'],
            'every_5_minutes' => ['cache_hit_rate_low'],
            'every_10_minutes' => ['memory_usage_high'],
            'every_15_minutes' => ['redis_memory_high'],
            'hourly' => ['full_performance_audit'],
            'daily' => ['performance_report_generation'],
        ];

        Cache::put('performance_monitoring_schedule', $schedule, now()->addDays(7));
    }

    /**
     * Execute automated performance optimization.
     */
    public function executeAutomatedOptimization(): array
    {
        $results = [];
        $currentMetrics = $this->monitorPerformanceMetrics();
        $alertRules = Cache::get('performance_alert_rules', []);

        foreach ($alertRules as $ruleName => $rule) {
            if ($this->evaluateAlertCondition($rule['condition'], $currentMetrics)) {
                $actionResult = $this->executeOptimizationAction($rule['action']);
                $results[$ruleName] = [
                    'triggered' => true,
                    'action' => $rule['action'],
                    'result' => $actionResult,
                    'severity' => $rule['severity'],
                ];

                Log::info("Automated optimization triggered: {$ruleName}", $results[$ruleName]);
            }
        }

        return [
            'timestamp' => now()->toISOString(),
            'rules_evaluated' => count($alertRules),
            'actions_triggered' => count($results),
            'results' => $results,
        ];
    }

    /**
     * Evaluate alert condition against current metrics.
     */
    private function evaluateAlertCondition(string $condition, array $metrics): bool
    {
        // Simple condition evaluation - in production, use a proper expression evaluator
        if (str_contains($condition, 'cache_hit_rate < 80')) {
            return ($metrics['cache_hit_rate'] ?? 100) < 80;
        }

        if (str_contains($condition, 'average_query_time > 500')) {
            return ($metrics['average_query_time'] ?? 0) > 500;
        }

        if (str_contains($condition, 'memory_usage > 512MB')) {
            return ($metrics['memory_usage'] ?? 0) > (512 * 1024 * 1024);
        }

        if (str_contains($condition, 'timeline_generation_time > 2000')) {
            return ($metrics['timeline_generation_time'] ?? 0) > 2000;
        }

        if (str_contains($condition, 'redis_memory_usage > 1GB')) {
            $redisMemory = $metrics['redis_memory_usage']['used_memory'] ?? 0;

            return $redisMemory > (1024 * 1024 * 1024);
        }

        return false;
    }

    /**
     * Execute optimization action based on alert.
     */
    private function executeOptimizationAction(string $action): array
    {
        try {
            switch ($action) {
                case 'optimize_caching':
                    $this->optimizeSocialGraphCaching();

                    return ['status' => 'success', 'message' => 'Social graph caching optimized'];

                case 'optimize_queries':
                    $this->optimizeTimelineQueries();

                    return ['status' => 'success', 'message' => 'Timeline queries optimized'];

                case 'clear_caches':
                    $this->clearPerformanceCaches();

                    return ['status' => 'success', 'message' => 'Performance caches cleared'];

                case 'optimize_social_graph':
                    $this->optimizeSocialGraphCaching();
                    $this->preComputeTimelineSegments();

                    return ['status' => 'success', 'message' => 'Social graph and timeline optimized'];

                case 'optimize_redis':
                    $this->optimizeRedisConfiguration();

                    return ['status' => 'success', 'message' => 'Redis configuration optimized'];

                default:
                    return ['status' => 'error', 'message' => "Unknown action: {$action}"];
            }
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Optimize Redis configuration for better performance.
     */
    private function optimizeRedisConfiguration(): void
    {
        try {
            if (! extension_loaded('redis')) {
                Log::info('Redis extension not available, skipping Redis optimization');

                return;
            }

            $redis = Redis::connection();

            // Set optimal Redis configurations
            $optimizations = [
                'maxmemory-policy' => 'allkeys-lru',
                'timeout' => '300',
                'tcp-keepalive' => '60',
                'save' => '900 1 300 10 60 10000', // Optimize persistence
                'stop-writes-on-bgsave-error' => 'no',
                'rdbcompression' => 'yes',
                'rdbchecksum' => 'yes',
            ];

            foreach ($optimizations as $key => $value) {
                $redis->config('SET', $key, $value);
            }

            Log::info('Redis configuration optimized', $optimizations);

        } catch (\Exception $e) {
            Log::warning('Failed to optimize Redis configuration', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Helper methods for metrics calculation.
     */
    private function calculateAverageConnectionsPerUser(): float
    {
        $totalConnections = DB::table('connections')->where('status', 'accepted')->count();
        $totalUsers = User::count();

        return $totalUsers > 0 ? $totalConnections / $totalUsers : 0;
    }

    private function getMostConnectedUsers(): array
    {
        return DB::table('connections')
            ->select('user_id', DB::raw('COUNT(*) as connection_count'))
            ->where('status', 'accepted')
            ->groupBy('user_id')
            ->orderBy('connection_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getLargestCircles(): array
    {
        return DB::table('circle_memberships')
            ->select('circle_id', DB::raw('COUNT(*) as member_count'))
            ->where('status', 'active')
            ->groupBy('circle_id')
            ->orderBy('member_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
