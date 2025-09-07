<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache store that will be used by the
    | framework. This connection is utilized if another isn't explicitly
    | specified when running a cache operation inside the application.
    |
    */

    'default' => env('CACHE_STORE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "array", "database", "file", "memcached",
    |                    "redis", "dynamodb", "octane", "null"
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Template Performance Cache Stores
        |--------------------------------------------------------------------------
        |
        | Dedicated cache stores for template performance optimization with
        | multi-layer caching strategy supporting tenant isolation.
        |
        */

        // L1 Cache: Fast memory cache for frequently accessed templates
        'template_l1' => [
            'driver' => 'array',
            'serialize' => true,
        ],

        // L2 Cache: Redis/Memcached for medium-term template storage
        'template_l2' => [
            'driver' => env('TEMPLATE_CACHE_STORE', 'redis'),
            'connection' => env('TEMPLATE_REDIS_CONNECTION', 'cache'),
            'lock_connection' => env('TEMPLATE_CACHE_LOCK_CONNECTION', 'default'),
            'ttl' => env('TEMPLATE_L2_TTL', 3600), // 1 hour
        ],

        // Template metadata cache for structural information
        'template_metadata' => [
            'driver' => env('METADATA_CACHE_STORE', 'redis'),
            'connection' => env('METADATA_REDIS_CONNECTION', 'cache'),
            'lock_connection' => env('METADATA_CACHE_LOCK_CONNECTION', 'default'),
            'ttl' => env('TEMPLATE_METADATA_TTL', 300), // 5 minutes
        ],

        // Performance metrics cache
        'template_metrics' => [
            'driver' => env('METRICS_CACHE_STORE', 'database'),
            'connection' => env('DB_METRICS_CONNECTION'),
            'table' => env('METRICS_CACHE_TABLE', 'cache'),
            'ttl' => env('TEMPLATE_METRICS_TTL', 86400), // 24 hours
        ],

        // Template optimization cache
        'template_optimization' => [
            'driver' => env('OPTIMIZATION_CACHE_STORE', 'redis'),
            'connection' => env('OPTIMIZATION_REDIS_CONNECTION', 'cache'),
            'lock_connection' => env('OPTIMIZATION_CACHE_LOCK_CONNECTION', 'default'),
            'ttl' => env('TEMPLATE_OPTIMIZATION_TTL', 1800), // 30 minutes
        ],

        // High-performance cache for popular templates
        'template_popular' => [
            'driver' => env('POPULAR_TEMPLATE_CACHE_STORE', 'redis'),
            'connection' => env('POPULAR_TEMPLATE_REDIS_CONNECTION', 'cache'),
            'lock_connection' => env('POPULAR_TEMPLATE_CACHE_LOCK_CONNECTION', 'default'),
            'ttl' => env('TEMPLATE_POPULAR_TTL', 3600), // 1 hour
            'compression' => env('TEMPLATE_CACHE_COMPRESSION', true),
        ],

        // Archive cache for rarely accessed templates
        'template_archive' => [
            'driver' => env('TEMPLATE_ARCHIVE_STORE', 'database'),
            'connection' => env('DB_ARCHIVE_CONNECTION'),
            'table' => env('ARCHIVE_CACHE_TABLE', 'cache'),
            'ttl' => env('TEMPLATE_ARCHIVE_TTL', 604800), // 7 days
            'compression' => env('TEMPLATE_ARCHIVE_COMPRESSION', true),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, and DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),

    /*
    |--------------------------------------------------------------------------
    | Template Cache Policies
    |--------------------------------------------------------------------------
    |
    | Specific caching policies for template performance optimization,
    | including TTL settings, invalidation strategies, and tenant isolation.
    |
    */

    'template_policies' => [
        /*
        |--------------------------------------------------------------------------
        | Cache Layer Policies
        |--------------------------------------------------------------------------
        |
        | Configuration for multi-layer caching strategy with L1 (fast memory),
        | L2 (persistent), and L3 (long-term) cache layers.
        |
        */

        'layers' => [
            'l1' => [
                'store' => 'template_l1',
                'ttl' => 60, // 1 minute - fast memory cache
                'enable_tagging' => false,
                'compression' => false,
                'size_limit' => 100, // Maximum entries in L1
            ],
            'l2' => [
                'store' => 'template_l2',
                'ttl' => env('TEMPLATE_L2_TTL', 3600), // 1 hour
                'enable_tagging' => true,
                'compression' => env('TEMPLATE_COMPRESSION', true),
                'size_limit' => 1000,
            ],
            'l3' => [
                'store' => 'template_archive',
                'ttl' => env('TEMPLATE_L3_TTL', 86400), // 24 hours
                'enable_tagging' => true,
                'compression' => true,
                'size_limit' => 10000,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Tenant Isolation Configuration
        |--------------------------------------------------------------------------
        |
        | Settings for tenant-aware caching to ensure data isolation
        | between different tenants in multi-tenant applications.
        |
        */

        'tenant_isolation' => [
            'enabled' => env('TENANT_CACHE_ISOLATION', true),
            'key_prefix_template' => 'tenant_{tenant_id}:',
            'cache_tags_enabled' => true,
            'cross_tenant_access_blocked' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Invalidation Policies
        |--------------------------------------------------------------------------
        |
        | Cache invalidation strategies for different types of updates
        | to ensure cache consistency and performance.
        |
        */

        'invalidation' => [
            'template_update' => [
                'invalidate_pattern' => 'template_{id}*',
                'also_invalidate_tags' => [
                    'template_category_{category}',
                    'tenant_{tenant_id}_templates',
                ],
                'cascade_invalidation' => true,
            ],
            'tenant_cleanup' => [
                'invalidate_pattern' => 'tenant_{tenant_id}:*',
                'cleanup_priority' => 'high',
            ],
            'performance_threshold' => [
                'slow_query_threshold' => 1000, // ms
                'cache_bust_on_threshold' => true,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Compression Settings
        |--------------------------------------------------------------------------
        |
        | Compression configuration for cache data to reduce memory
        | usage and improve performance.
        |
        */

        'compression' => [
            'enabled' => env('TEMPLATE_CACHE_COMPRESSION', true),
            'algorithm' => env('CACHE_COMPRESSION_ALGORITHM', 'gzip'),
            'level' => env('CACHE_COMPRESSION_LEVEL', 6),
            'threshold' => env('CACHE_COMPRESSION_THRESHOLD', 1024), // Compress items larger than 1KB
            'exclude_ranges' => ['0-512'], // Don't compress small items
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for monitoring cache performance and generating
    | optimization recommendations.
    |
    */

    'performance_monitoring' => [
        'enable_metrics' => env('CACHE_METRICS_ENABLED', true),
        'metrics_store' => 'template_metrics',
        'alerts_enabled' => env('CACHE_ALERTS_ENABLED', true),
        'alert_thresholds' => [
            'slow_cache_hit' => 100, // ms - Alert if cache hit takes longer than this
            'cache_miss_rate' => 30, // percent - Alert if miss rate above this
            'memory_usage' => 80, // percent - Alert if cache memory usage above this
        ],
        'reporting_interval' => env('CACHE_REPORTING_INTERVAL', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Templates
    |--------------------------------------------------------------------------
    |
    | Standardized cache key patterns for consistent cache management
    | and easier debugging.
    |
    */

    'key_templates' => [
        'template_render' => 'template_render:{tenant_id}:{template_id}',
        'template_metadata' => 'template_meta:{tenant_id}:{template_id}',
        'template_optimization' => 'template_opt:{tenant_id}:{template_id}',
        'template_popular' => 'template_popular:{tenant_id}',
        'template_by_category' => 'templates:{tenant_id}:category_{category}',
        'template_search' => 'template_search:{tenant_id}:hash_{hash}',
        'template_performance_metrics' => 'template_perf_metrics:{tenant_id}:{template_id}',
        'template_recommendations' => 'template_recs:{tenant_id}',
    ],

];
