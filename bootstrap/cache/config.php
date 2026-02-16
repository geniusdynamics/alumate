<?php return array (
  'concurrency' => 
  array (
    'default' => 'process',
  ),
  'cors' => 
  array (
    'paths' => 
    array (
      0 => 'api/*',
      1 => 'sanctum/csrf-cookie',
    ),
    'allowed_methods' => 
    array (
      0 => '*',
    ),
    'allowed_origins' => 
    array (
      0 => '*',
    ),
    'allowed_origins_patterns' => 
    array (
    ),
    'allowed_headers' => 
    array (
      0 => '*',
    ),
    'exposed_headers' => 
    array (
    ),
    'max_age' => 0,
    'supports_credentials' => false,
  ),
  'hashing' => 
  array (
    'driver' => 'bcrypt',
    'bcrypt' => 
    array (
      'rounds' => '12',
      'verify' => true,
      'limit' => NULL,
    ),
    'argon' => 
    array (
      'memory' => 65536,
      'threads' => 1,
      'time' => 4,
      'verify' => true,
    ),
    'rehash_on_login' => true,
  ),
  'view' => 
  array (
    'paths' => 
    array (
      0 => 'D:\\DevCenter\\abuilds\\alumate\\resources\\views',
    ),
    'compiled' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\framework\\views',
  ),
  'analytics' => 
  array (
    'cache' => 
    array (
      'enabled' => true,
      'ttl' => 300,
      'prefix' => 'analytics:',
    ),
    'snapshots' => 
    array (
      'enabled' => true,
      'retention_days' => 365,
      'auto_generate' => 
      array (
        'daily' => true,
        'weekly' => true,
        'monthly' => true,
      ),
    ),
    'kpis' => 
    array (
      'auto_calculate' => true,
      'calculation_schedule' => 'daily',
      'alert_thresholds' => 
      array (
        'employment_rate' => 
        array (
          'warning' => 70.0,
          'critical' => 60.0,
        ),
        'job_placement_rate' => 
        array (
          'warning' => 15.0,
          'critical' => 10.0,
        ),
        'avg_time_to_employment' => 
        array (
          'warning' => 120.0,
          'critical' => 180.0,
        ),
      ),
    ),
    'predictions' => 
    array (
      'enabled' => true,
      'auto_retrain' => true,
      'retrain_schedule' => 'weekly',
      'min_training_data' => 100,
      'prediction_horizon_days' => 90,
    ),
    'reports' => 
    array (
      'max_records' => 10000,
      'timeout_seconds' => 300,
      'expiration_days' => 30,
      'storage_disk' => 'local',
      'allowed_formats' => 
      array (
        0 => 'csv',
        1 => 'excel',
        2 => 'pdf',
        3 => 'json',
      ),
      'scheduled_processing' => 
      array (
        'enabled' => true,
        'max_concurrent' => 3,
      ),
    ),
    'charts' => 
    array (
      'default_colors' => 
      array (
        0 => '#3B82F6',
        1 => '#10B981',
        2 => '#F59E0B',
        3 => '#EF4444',
        4 => '#8B5CF6',
        5 => '#F97316',
        6 => '#06B6D4',
        7 => '#84CC16',
      ),
      'max_data_points' => 100,
      'animation_duration' => 750,
    ),
    'exports' => 
    array (
      'max_file_size' => 52428800,
      'cleanup_after_days' => 7,
      'batch_size' => 1000,
    ),
    'performance' => 
    array (
      'query_timeout' => 60,
      'memory_limit' => '512M',
      'chunk_size' => 1000,
      'parallel_processing' => false,
    ),
    'security' => 
    array (
      'data_anonymization' => false,
      'audit_access' => true,
      'rate_limiting' => 
      array (
        'enabled' => true,
        'max_requests_per_minute' => 60,
      ),
    ),
    'integrations' => 
    array (
      'slack' => 
      array (
        'enabled' => false,
        'webhook_url' => NULL,
        'channel' => '#analytics',
      ),
      'email' => 
      array (
        'enabled' => true,
        'from_address' => 'hello@example.com',
        'from_name' => 'Analytics System',
      ),
      'webhooks' => 
      array (
        'enabled' => false,
        'timeout' => 30,
        'retry_attempts' => 3,
      ),
    ),
    'dashboard' => 
    array (
      'default_timeframe' => '30_days',
      'refresh_interval' => 300,
      'widgets' => 
      array (
        'overview_metrics' => 
        array (
          'enabled' => true,
          'order' => 1,
        ),
        'kpi_summary' => 
        array (
          'enabled' => true,
          'order' => 2,
        ),
        'employment_trend' => 
        array (
          'enabled' => true,
          'order' => 3,
        ),
        'course_performance' => 
        array (
          'enabled' => true,
          'order' => 4,
        ),
        'job_market_activity' => 
        array (
          'enabled' => true,
          'order' => 5,
        ),
        'recent_predictions' => 
        array (
          'enabled' => true,
          'order' => 6,
        ),
        'system_alerts' => 
        array (
          'enabled' => true,
          'order' => 7,
        ),
      ),
    ),
    'logging' => 
    array (
      'enabled' => true,
      'level' => 'info',
      'channel' => 'daily',
      'log_queries' => false,
      'log_performance' => true,
    ),
    'archiving' => 
    array (
      'enabled' => true,
      'compression' => 'gzip',
      'encryption' => false,
      'storage_disk' => 'local',
      'archive_path' => 'archives/analytics',
      'max_archive_size' => 5368709120,
      'default_retention_days' => 365,
      'auto_archive' => true,
      'archive_schedule' => 'daily',
    ),
  ),
  'app' => 
  array (
    'name' => 'Alumate',
    'env' => 'local',
    'debug' => true,
    'url' => 'http://127.0.0.1:8080',
    'frontend_url' => 'http://localhost:3000',
    'asset_url' => NULL,
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'cipher' => 'AES-256-CBC',
    'key' => 'base64:KI26BZM/wbAVl9GrLRW+QD7cMCNh9C1cqIBM/Ms40SQ=',
    'previous_keys' => 
    array (
    ),
    'maintenance' => 
    array (
      'driver' => 'file',
      'store' => 'database',
    ),
    'providers' => 
    array (
      0 => 'Illuminate\\Auth\\AuthServiceProvider',
      1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
      2 => 'Illuminate\\Bus\\BusServiceProvider',
      3 => 'Illuminate\\Cache\\CacheServiceProvider',
      4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
      5 => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider',
      6 => 'Illuminate\\Cookie\\CookieServiceProvider',
      7 => 'Illuminate\\Database\\DatabaseServiceProvider',
      8 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      9 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      10 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      11 => 'Illuminate\\Hashing\\HashServiceProvider',
      12 => 'Illuminate\\Mail\\MailServiceProvider',
      13 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      14 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      15 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      16 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
      17 => 'Illuminate\\Queue\\QueueServiceProvider',
      18 => 'Illuminate\\Redis\\RedisServiceProvider',
      19 => 'Illuminate\\Session\\SessionServiceProvider',
      20 => 'Illuminate\\Translation\\TranslationServiceProvider',
      21 => 'Illuminate\\Validation\\ValidationServiceProvider',
      22 => 'Illuminate\\View\\ViewServiceProvider',
      23 => 'App\\Providers\\AppServiceProvider',
      24 => 'App\\Providers\\AuthServiceProvider',
      25 => 'App\\Providers\\EventServiceProvider',
      26 => 'App\\Providers\\RouteServiceProvider',
      27 => 'App\\Providers\\TenancyServiceProvider',
      28 => 'App\\Providers\\AppServiceProvider',
      29 => 'App\\Providers\\TenancyServiceProvider',
    ),
    'aliases' => 
    array (
      'App' => 'Illuminate\\Support\\Facades\\App',
      'Arr' => 'Illuminate\\Support\\Arr',
      'Artisan' => 'Illuminate\\Support\\Facades\\Artisan',
      'Auth' => 'Illuminate\\Support\\Facades\\Auth',
      'Benchmark' => 'Illuminate\\Support\\Benchmark',
      'Blade' => 'Illuminate\\Support\\Facades\\Blade',
      'Broadcast' => 'Illuminate\\Support\\Facades\\Broadcast',
      'Bus' => 'Illuminate\\Support\\Facades\\Bus',
      'Cache' => 'Illuminate\\Support\\Facades\\Cache',
      'Concurrency' => 'Illuminate\\Support\\Facades\\Concurrency',
      'Config' => 'Illuminate\\Support\\Facades\\Config',
      'Context' => 'Illuminate\\Support\\Facades\\Context',
      'Cookie' => 'Illuminate\\Support\\Facades\\Cookie',
      'Crypt' => 'Illuminate\\Support\\Facades\\Crypt',
      'Date' => 'Illuminate\\Support\\Facades\\Date',
      'DB' => 'Illuminate\\Support\\Facades\\DB',
      'Eloquent' => 'Illuminate\\Database\\Eloquent\\Model',
      'Event' => 'Illuminate\\Support\\Facades\\Event',
      'File' => 'Illuminate\\Support\\Facades\\File',
      'Gate' => 'Illuminate\\Support\\Facades\\Gate',
      'Hash' => 'Illuminate\\Support\\Facades\\Hash',
      'Http' => 'Illuminate\\Support\\Facades\\Http',
      'Js' => 'Illuminate\\Support\\Js',
      'Lang' => 'Illuminate\\Support\\Facades\\Lang',
      'Log' => 'Illuminate\\Support\\Facades\\Log',
      'Mail' => 'Illuminate\\Support\\Facades\\Mail',
      'Notification' => 'Illuminate\\Support\\Facades\\Notification',
      'Number' => 'Illuminate\\Support\\Number',
      'Password' => 'Illuminate\\Support\\Facades\\Password',
      'Process' => 'Illuminate\\Support\\Facades\\Process',
      'Queue' => 'Illuminate\\Support\\Facades\\Queue',
      'RateLimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
      'Redirect' => 'Illuminate\\Support\\Facades\\Redirect',
      'Request' => 'Illuminate\\Support\\Facades\\Request',
      'Response' => 'Illuminate\\Support\\Facades\\Response',
      'Route' => 'Illuminate\\Support\\Facades\\Route',
      'Schedule' => 'Illuminate\\Support\\Facades\\Schedule',
      'Schema' => 'Illuminate\\Support\\Facades\\Schema',
      'Session' => 'Illuminate\\Support\\Facades\\Session',
      'Storage' => 'Illuminate\\Support\\Facades\\Storage',
      'Str' => 'Illuminate\\Support\\Str',
      'Uri' => 'Illuminate\\Support\\Uri',
      'URL' => 'Illuminate\\Support\\Facades\\URL',
      'Validator' => 'Illuminate\\Support\\Facades\\Validator',
      'View' => 'Illuminate\\Support\\Facades\\View',
      'Vite' => 'Illuminate\\Support\\Facades\\Vite',
    ),
  ),
  'auth' => 
  array (
    'defaults' => 
    array (
      'guard' => 'web',
      'passwords' => 'users',
    ),
    'guards' => 
    array (
      'web' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
      'sanctum' => 
      array (
        'driver' => 'sanctum',
        'provider' => 'users',
      ),
    ),
    'providers' => 
    array (
      'users' => 
      array (
        'driver' => 'eloquent',
        'model' => 'App\\Models\\User',
      ),
    ),
    'passwords' => 
    array (
      'users' => 
      array (
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
      ),
    ),
    'password_timeout' => 10800,
  ),
  'boost' => 
  array (
    'enabled' => true,
    'browser_logs_watcher' => false,
  ),
  'broadcasting' => 
  array (
    'default' => 'null',
    'connections' => 
    array (
      'reverb' => 
      array (
        'driver' => 'reverb',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'host' => NULL,
          'port' => 443,
          'scheme' => 'https',
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'pusher' => 
      array (
        'driver' => 'pusher',
        'key' => '',
        'secret' => '',
        'app_id' => '',
        'options' => 
        array (
          'cluster' => 'mt1',
          'host' => 'api-mt1.pusherapp.com',
          'port' => '443',
          'scheme' => 'https',
          'encrypted' => true,
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'ably' => 
      array (
        'driver' => 'ably',
        'key' => NULL,
      ),
      'log' => 
      array (
        'driver' => 'log',
      ),
      'null' => 
      array (
        'driver' => 'null',
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
      ),
    ),
  ),
  'cache' => 
  array (
    'default' => 'database',
    'stores' => 
    array (
      'array' => 
      array (
        'driver' => 'array',
        'serialize' => false,
      ),
      'session' => 
      array (
        'driver' => 'session',
        'key' => '_cache',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'cache',
        'lock_connection' => NULL,
        'lock_table' => NULL,
      ),
      'file' => 
      array (
        'driver' => 'file',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\framework/cache/data',
        'lock_path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\framework/cache/data',
      ),
      'memcached' => 
      array (
        'driver' => 'memcached',
        'persistent_id' => NULL,
        'sasl' => 
        array (
          0 => NULL,
          1 => NULL,
        ),
        'options' => 
        array (
        ),
        'servers' => 
        array (
          0 => 
          array (
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 100,
          ),
        ),
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
      ),
      'dynamodb' => 
      array (
        'driver' => 'dynamodb',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'table' => 'cache',
        'endpoint' => NULL,
      ),
      'octane' => 
      array (
        'driver' => 'octane',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'stores' => 
        array (
          0 => 'database',
          1 => 'array',
        ),
      ),
      'template_l1' => 
      array (
        'driver' => 'array',
        'serialize' => true,
      ),
      'template_l2' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
        'ttl' => 3600,
      ),
      'template_metadata' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
        'ttl' => 300,
      ),
      'template_metrics' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'cache',
        'ttl' => 86400,
      ),
      'template_optimization' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
        'ttl' => 1800,
      ),
      'template_popular' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
        'ttl' => 3600,
        'compression' => true,
      ),
      'template_archive' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'cache',
        'ttl' => 604800,
        'compression' => true,
      ),
      'analytics' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
        'ttl' => 300,
      ),
      'analytics_metrics' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
        'ttl' => 600,
      ),
      'redis_cluster' => 
      array (
        'driver' => 'redis',
        'connection' => 'cluster',
        'lock_connection' => 'cluster',
        'options' => 
        array (
          'cluster' => 'redis',
          'parameters' => 
          array (
            'password' => NULL,
          ),
        ),
      ),
    ),
    'prefix' => 'alumate_cache_',
    'template_policies' => 
    array (
      'layers' => 
      array (
        'l1' => 
        array (
          'store' => 'template_l1',
          'ttl' => 60,
          'enable_tagging' => false,
          'compression' => false,
          'size_limit' => 100,
        ),
        'l2' => 
        array (
          'store' => 'template_l2',
          'ttl' => 3600,
          'enable_tagging' => true,
          'compression' => true,
          'size_limit' => 1000,
        ),
        'l3' => 
        array (
          'store' => 'template_archive',
          'ttl' => 86400,
          'enable_tagging' => true,
          'compression' => true,
          'size_limit' => 10000,
        ),
      ),
      'tenant_isolation' => 
      array (
        'enabled' => true,
        'key_prefix_template' => 'tenant_{tenant_id}:',
        'cache_tags_enabled' => true,
        'cross_tenant_access_blocked' => true,
      ),
      'invalidation' => 
      array (
        'template_update' => 
        array (
          'invalidate_pattern' => 'template_{id}*',
          'also_invalidate_tags' => 
          array (
            0 => 'template_category_{category}',
            1 => 'tenant_{tenant_id}_templates',
          ),
          'cascade_invalidation' => true,
        ),
        'tenant_cleanup' => 
        array (
          'invalidate_pattern' => 'tenant_{tenant_id}:*',
          'cleanup_priority' => 'high',
        ),
        'performance_threshold' => 
        array (
          'slow_query_threshold' => 1000,
          'cache_bust_on_threshold' => true,
        ),
      ),
      'compression' => 
      array (
        'enabled' => true,
        'algorithm' => 'gzip',
        'level' => 6,
        'threshold' => 1024,
        'exclude_ranges' => 
        array (
          0 => '0-512',
        ),
      ),
    ),
    'performance_monitoring' => 
    array (
      'enable_metrics' => true,
      'metrics_store' => 'template_metrics',
      'alerts_enabled' => true,
      'alert_thresholds' => 
      array (
        'slow_cache_hit' => 100,
        'cache_miss_rate' => 30,
        'memory_usage' => 80,
      ),
      'reporting_interval' => 300,
    ),
    'key_templates' => 
    array (
      'template_render' => 'template_render:{tenant_id}:{template_id}',
      'template_metadata' => 'template_meta:{tenant_id}:{template_id}',
      'template_optimization' => 'template_opt:{tenant_id}:{template_id}',
      'template_popular' => 'template_popular:{tenant_id}',
      'template_by_category' => 'templates:{tenant_id}:category_{category}',
      'template_search' => 'template_search:{tenant_id}:hash_{hash}',
      'template_performance_metrics' => 'template_perf_metrics:{tenant_id}:{template_id}',
      'template_recommendations' => 'template_recs:{tenant_id}',
    ),
  ),
  'database' => 
  array (
    'default' => 'pgsql',
    'connections' => 
    array (
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'url' => NULL,
        'database' => 'alumate',
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => NULL,
        'journal_mode' => NULL,
        'synchronous' => NULL,
      ),
      'mysql' => 
      array (
        'driver' => 'mysql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '5433',
        'database' => 'alumate',
        'username' => 'postgres',
        'password' => 'postgres',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'mariadb' => 
      array (
        'driver' => 'mariadb',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '5433',
        'database' => 'alumate',
        'username' => 'postgres',
        'password' => 'postgres',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'pgsql' => 
      array (
        'driver' => 'pgsql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '5433',
        'database' => 'alumate',
        'username' => 'postgres',
        'password' => 'postgres',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'disable',
        'options' => 
        array (
        ),
      ),
      'sqlsrv' => 
      array (
        'driver' => 'sqlsrv',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '5433',
        'database' => 'alumate',
        'username' => 'postgres',
        'password' => 'postgres',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
      ),
      'null' => 
      array (
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => false,
      ),
      'central' => 
      array (
        'driver' => 'pgsql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '5433',
        'database' => 'alumate',
        'username' => 'postgres',
        'password' => 'postgres',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
      ),
      'tenant' => 
      array (
        'driver' => 'pgsql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '5433',
        'database' => 'alumate',
        'username' => 'postgres',
        'password' => 'postgres',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
      ),
    ),
    'migrations' => 
    array (
      'table' => 'migrations',
      'update_date_on_publish' => true,
    ),
    'redis' => 
    array (
      'client' => 'phpredis',
      'options' => 
      array (
        'cluster' => 'redis',
        'prefix' => 'alumate_database_',
        'persistent' => false,
      ),
      'default' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '0',
      ),
      'cache' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '1',
      ),
      'session' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '2',
      ),
      'queue' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '3',
      ),
    ),
    'read_write' => 
    array (
      'read' => 
      array (
        'host' => NULL,
      ),
      'write' => 
      array (
        'host' => NULL,
      ),
      'sticky' => true,
      'read_write_separation' => true,
    ),
  ),
  'debugbar' => 
  array (
    'enabled' => false,
    'hide_empty_tabs' => true,
    'except' => 
    array (
      0 => 'telescope*',
      1 => 'horizon*',
    ),
    'storage' => 
    array (
      'enabled' => false,
      'open' => false,
      'driver' => 'file',
      'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\debugbar',
      'connection' => NULL,
      'provider' => '',
      'hostname' => '127.0.0.1',
      'port' => 2304,
    ),
    'editor' => 'phpstorm',
    'remote_sites_path' => NULL,
    'local_sites_path' => NULL,
    'include_vendors' => true,
    'capture_ajax' => true,
    'add_ajax_timing' => false,
    'ajax_handler_auto_show' => true,
    'ajax_handler_enable_tab' => true,
    'defer_datasets' => false,
    'error_handler' => false,
    'error_level' => 32767,
    'clockwork' => false,
    'collectors' => 
    array (
      'phpinfo' => false,
      'messages' => true,
      'time' => true,
      'memory' => true,
      'exceptions' => true,
      'log' => true,
      'db' => true,
      'views' => true,
      'route' => false,
      'auth' => false,
      'gate' => true,
      'session' => false,
      'symfony_request' => true,
      'mail' => true,
      'laravel' => true,
      'events' => false,
      'default_request' => false,
      'logs' => false,
      'files' => false,
      'config' => false,
      'cache' => false,
      'models' => true,
      'livewire' => true,
      'jobs' => false,
      'pennant' => false,
    ),
    'options' => 
    array (
      'time' => 
      array (
        'memory_usage' => false,
      ),
      'messages' => 
      array (
        'trace' => true,
        'capture_dumps' => false,
      ),
      'memory' => 
      array (
        'reset_peak' => false,
        'with_baseline' => false,
        'precision' => 0,
      ),
      'auth' => 
      array (
        'show_name' => true,
        'show_guards' => true,
      ),
      'gate' => 
      array (
        'trace' => false,
      ),
      'db' => 
      array (
        'with_params' => true,
        'exclude_paths' => 
        array (
        ),
        'backtrace' => true,
        'backtrace_exclude_paths' => 
        array (
        ),
        'timeline' => false,
        'duration_background' => true,
        'explain' => 
        array (
          'enabled' => false,
        ),
        'hints' => true,
        'show_copy' => true,
        'slow_threshold' => false,
        'memory_usage' => false,
        'soft_limit' => 100,
        'hard_limit' => 500,
      ),
      'mail' => 
      array (
        'timeline' => true,
        'show_body' => true,
      ),
      'views' => 
      array (
        'timeline' => true,
        'data' => false,
        'group' => 50,
        'inertia_pages' => 'js/Pages',
        'exclude_paths' => 
        array (
          0 => 'vendor/filament',
        ),
      ),
      'route' => 
      array (
        'label' => true,
      ),
      'session' => 
      array (
        'hiddens' => 
        array (
        ),
      ),
      'symfony_request' => 
      array (
        'label' => true,
        'hiddens' => 
        array (
        ),
      ),
      'events' => 
      array (
        'data' => false,
        'excluded' => 
        array (
        ),
      ),
      'logs' => 
      array (
        'file' => NULL,
      ),
      'cache' => 
      array (
        'values' => true,
      ),
    ),
    'inject' => true,
    'route_prefix' => '_debugbar',
    'route_middleware' => 
    array (
    ),
    'route_domain' => NULL,
    'theme' => 'auto',
    'debug_backtrace_limit' => 50,
  ),
  'deployment' => 
  array (
    'homepage' => 
    array (
      'monitoring' => 
      array (
        'enabled' => true,
        'performance_monitoring' => true,
        'error_tracking' => true,
        'uptime_monitoring' => true,
        'security_monitoring' => true,
        'conversion_monitoring' => true,
      ),
      'performance_thresholds' => 
      array (
        'page_load' => 
        array (
          'warning' => '2000',
          'critical' => '5000',
        ),
        'api_response' => 
        array (
          'warning' => '1000',
          'critical' => '3000',
        ),
        'database_query' => 
        array (
          'warning' => '500',
          'critical' => '2000',
        ),
      ),
      'conversion_thresholds' => 
      array (
        'conversion_rate' => 
        array (
          'min' => '2.0',
          'max' => '15.0',
        ),
        'cta_click_rate' => 
        array (
          'min' => '5.0',
          'max' => '25.0',
        ),
        'bounce_rate' => 
        array (
          'max' => '70.0',
        ),
      ),
      'security' => 
      array (
        'rate_limits' => 
        array (
          'api_requests_per_minute' => '30',
          'page_requests_per_minute' => '60',
          'suspicious_activity_threshold' => '50',
          'ddos_threshold' => '100',
        ),
        'blocked_patterns' => 
        array (
          0 => 'sql_injection',
          1 => 'xss_attempt',
          2 => 'path_traversal',
          3 => 'command_injection',
        ),
        'suspicious_user_agents' => 
        array (
          0 => 'sqlmap',
          1 => 'nikto',
          2 => 'nmap',
          3 => 'masscan',
          4 => 'zap',
        ),
      ),
      'alerts' => 
      array (
        'rate_limits' => 
        array (
          'critical' => '300',
          'error' => '900',
          'warning' => '1800',
          'info' => '3600',
        ),
        'escalation' => 
        array (
          'enabled' => false,
          'escalation_time' => 1800,
          'escalation_email' => NULL,
        ),
      ),
      'health_checks' => 
      array (
        'enabled' => true,
        'interval' => '300',
        'timeout' => '10',
        'endpoints' => 
        array (
          'homepage' => '/',
          'health_check' => '/health-check/homepage',
          'api_statistics' => '/api/homepage/statistics',
          'api_testimonials' => '/api/homepage/testimonials',
        ),
      ),
      'data_retention' => 
      array (
        'performance_metrics' => '30',
        'error_logs' => '90',
        'analytics_events' => '365',
        'alert_logs' => '180',
      ),
      'security_headers' => 
      array (
        'hsts' => 
        array (
          'enabled' => true,
          'max_age' => 31536000,
          'include_subdomains' => true,
          'preload' => false,
        ),
        'csp' => 
        array (
          'enabled' => true,
          'policy' => 'default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\' http://127.0.0.1:5173; style-src \'self\' \'unsafe-inline\' http://127.0.0.1:5173 https://fonts.googleapis.com; img-src \'self\' data: https:; font-src \'self\' data: https://fonts.gstatic.com; connect-src \'self\' http://127.0.0.1:5173 ws://127.0.0.1:5173;',
        ),
        'x_frame_options' => 'SAMEORIGIN',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
      ),
    ),
    'external_services' => 
    array (
      'sentry' => 
      array (
        'enabled' => false,
        'dsn' => 'https://5b2c3cb3a5eb423893d58842bbe71483@app1.genius2.mrmarkuz.ddnss.eu/1',
        'environment' => 'local',
        'release' => '${APP_VERSION}',
      ),
      'datadog' => 
      array (
        'enabled' => false,
        'api_key' => '',
        'app_key' => NULL,
      ),
      'newrelic' => 
      array (
        'enabled' => false,
        'api_key' => '',
        'app_id' => NULL,
      ),
      'pagerduty' => 
      array (
        'enabled' => false,
        'integration_key' => '',
      ),
    ),
  ),
  'elasticsearch' => 
  array (
    'host' => 'localhost:9200',
    'index_prefix' => 'alumni_platform',
    'settings' => 
    array (
      'number_of_shards' => 1,
      'number_of_replicas' => 0,
    ),
    'search' => 
    array (
      'default_size' => 20,
      'max_size' => 100,
      'highlight_fragment_size' => 150,
      'suggestion_size' => 5,
    ),
    'indexing' => 
    array (
      'batch_size' => 100,
      'queue_connection' => 'database',
    ),
  ),
  'federation' => 
  array (
    'enabled' => false,
    'protocols' => 
    array (
    ),
    'matrix' => 
    array (
      'server_name' => 'localhost',
      'server_url' => 'https://matrix.org',
      'access_token' => NULL,
      'user_id' => NULL,
      'default_room_version' => '10',
      'default_power_levels' => 
      array (
        'users_default' => 0,
        'events_default' => 0,
        'state_default' => 50,
        'ban' => 50,
        'kick' => 50,
        'redact' => 50,
        'invite' => 0,
      ),
      'event_mapping' => 
      array (
        'include_alumni_extensions' => true,
        'preserve_original_content' => true,
        'enable_rich_formatting' => true,
      ),
      'encryption' => 
      array (
        'enabled' => false,
        'algorithm' => 'm.megolm.v1.aes-sha2',
        'key_rotation_period' => 604800,
      ),
    ),
    'activitypub' => 
    array (
      'server_name' => 'localhost',
      'actor_base_url' => 'http://127.0.0.1:8080/federation',
      'public_key_algorithm' => 'RS256',
      'signature_algorithm' => 'rsa-sha256',
      'activity_mapping' => 
      array (
        'include_alumni_extensions' => true,
        'enable_custom_context' => true,
        'preserve_original_content' => true,
      ),
      'delivery' => 
      array (
        'timeout' => 30,
        'retry_attempts' => 3,
        'retry_delay' => 300,
        'batch_size' => 100,
      ),
      'security' => 
      array (
        'verify_signatures' => true,
        'require_https' => true,
        'allowed_algorithms' => 
        array (
          0 => 'rsa-sha256',
        ),
      ),
    ),
    'bridge' => 
    array (
      'auto_federate' => 
      array (
        'posts' => false,
        'users' => false,
        'groups' => false,
        'circles' => false,
      ),
      'mapping_cache_ttl' => 3600,
      'activity_log_retention' => 30,
      'rate_limits' => 
      array (
        'outgoing_activities_per_minute' => 60,
        'incoming_activities_per_minute' => 120,
      ),
    ),
    'identity' => 
    array (
      'matrix_id_format' => '@{username}:{domain}',
      'activitypub_actor_format' => '{base_url}/users/{username}',
      'preserve_local_ids' => true,
      'enable_cross_protocol_discovery' => false,
    ),
    'content' => 
    array (
      'preserve_formatting' => true,
      'convert_mentions' => true,
      'convert_hashtags' => true,
      'include_media_attachments' => true,
      'alumni_extensions' => 
      array (
        'circles' => true,
        'groups' => true,
        'career_data' => false,
        'education_data' => false,
      ),
    ),
    'privacy' => 
    array (
      'default_visibility' => 'circles',
      'allow_public_federation' => false,
      'require_explicit_consent' => true,
      'anonymize_sensitive_data' => true,
    ),
    'monitoring' => 
    array (
      'log_all_activities' => true,
      'log_level' => 'info',
      'metrics_enabled' => true,
      'health_check_interval' => 300,
    ),
  ),
  'filesystems' => 
  array (
    'default' => 'local',
    'disks' => 
    array (
      'local' => 
      array (
        'driver' => 'local',
        'root' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\app/private',
        'serve' => true,
        'throw' => false,
        'report' => false,
      ),
      'public' => 
      array (
        'driver' => 'local',
        'root' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\app/public',
        'url' => 'http://127.0.0.1:8080/storage',
        'visibility' => 'public',
        'throw' => false,
        'report' => false,
      ),
      's3' => 
      array (
        'driver' => 's3',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'bucket' => '',
        'url' => NULL,
        'endpoint' => NULL,
        'use_path_style_endpoint' => false,
        'throw' => false,
        'report' => false,
        'cdn_url' => NULL,
        'cdn_enabled' => false,
      ),
      'spaces' => 
      array (
        'driver' => 's3',
        'key' => NULL,
        'secret' => NULL,
        'region' => 'nyc3',
        'bucket' => NULL,
        'endpoint' => 'https://nyc3.digitaloceanspaces.com',
        'url' => NULL,
        'use_path_style_endpoint' => false,
        'throw' => false,
        'report' => false,
        'cdn_url' => NULL,
        'cdn_enabled' => false,
      ),
      'b2' => 
      array (
        'driver' => 's3',
        'key' => NULL,
        'secret' => NULL,
        'region' => 'us-west-002',
        'bucket' => NULL,
        'endpoint' => NULL,
        'url' => NULL,
        'use_path_style_endpoint' => false,
        'throw' => false,
        'report' => false,
        'cdn_url' => NULL,
        'cdn_enabled' => false,
      ),
    ),
    'links' => 
    array (
      'D:\\DevCenter\\abuilds\\alumate\\public\\storage' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\app/public',
    ),
    'default_storage_disk' => 's3',
    'upload_max_size' => 104857600,
    'default_quota_bytes' => 5368709120,
    'virus_scanning' => 
    array (
      'enabled' => true,
      'driver' => 'clamav',
      'auto_delete_infected' => false,
      'clamav_socket' => '/var/run/clamav/clamd.ctl',
      'clamav_host' => 'localhost',
      'clamav_port' => 3310,
    ),
    'signed_url_expiration' => 15,
    'max_signed_url_expiration' => 60,
    'image_processing' => 
    array (
      'max_width' => 2048,
      'max_height' => 2048,
      'quality' => 85,
      'auto_optimize' => true,
      'generate_webp' => true,
      'thumbnail_sizes' => 
      array (
        'thumbnail' => 
        array (
          'width' => 150,
          'height' => 150,
        ),
        'small' => 
        array (
          'width' => 400,
          'height' => 300,
        ),
        'medium' => 
        array (
          'width' => 800,
          'height' => 600,
        ),
        'large' => 
        array (
          'width' => 1600,
          'height' => 1200,
        ),
      ),
    ),
    'cdn' => 
    array (
      'enabled' => false,
      'url' => NULL,
      'cache_duration' => 86400,
      'image_optimization' => true,
      'video_streaming' => false,
    ),
  ),
  'horizon' => 
  array (
    'domain' => NULL,
    'path' => 'horizon',
    'use' => 'redis',
    'prefix' => 'horizon:',
    'middleware' => 
    array (
      0 => 'web',
    ),
    'waits' => 
    array (
      'redis:analytics' => 60,
      'redis:default' => 60,
    ),
    'trim' => 
    array (
      'recent' => 60,
      'pending' => 60,
      'completed' => 60,
      'recent_failed' => 10080,
      'failed' => 10080,
      'monitored' => 10080,
    ),
    'metrics' => 
    array (
      'trim_snapshots' => 
      array (
        'job' => 24,
        'queue' => 24,
      ),
    ),
    'fast_termination' => false,
    'memory' => 128,
    'environments' => 
    array (
      'production' => 
      array (
        'supervisor-1' => 
        array (
          'connection' => 'redis',
          'queue' => 
          array (
            0 => 'analytics',
            1 => 'default',
          ),
          'balance' => 'auto',
          'processes' => 10,
          'tries' => 3,
          'timeout' => 90,
          'sleep' => 3,
          'max_jobs' => 1000,
          'max_processes' => 1,
          'min_processes' => 1,
          'force' => false,
        ),
      ),
      'local' => 
      array (
        'supervisor-1' => 
        array (
          'connection' => 'redis',
          'queue' => 
          array (
            0 => 'default',
          ),
          'balance' => 'simple',
          'processes' => 3,
          'tries' => 3,
          'timeout' => 90,
          'sleep' => 3,
          'max_jobs' => 1000,
          'max_processes' => 1,
          'min_processes' => 1,
          'force' => false,
        ),
      ),
    ),
  ),
  'logging' => 
  array (
    'default' => 'stack',
    'deprecations' => 
    array (
      'channel' => NULL,
      'trace' => false,
    ),
    'channels' => 
    array (
      'stack' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'single',
        ),
        'ignore_exceptions' => false,
      ),
      'single' => 
      array (
        'driver' => 'single',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/laravel.log',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'daily' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/laravel.log',
        'level' => 'debug',
        'days' => 14,
        'replace_placeholders' => true,
      ),
      'slack' => 
      array (
        'driver' => 'slack',
        'url' => NULL,
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'papertrail' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\SyslogUdpHandler',
        'handler_with' => 
        array (
          'host' => NULL,
          'port' => NULL,
          'connectionString' => 'tls://:',
        ),
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'stderr' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\StreamHandler',
        'handler_with' => 
        array (
          'stream' => 'php://stderr',
        ),
        'formatter' => NULL,
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'syslog' => 
      array (
        'driver' => 'syslog',
        'level' => 'debug',
        'facility' => 8,
        'replace_placeholders' => true,
      ),
      'errorlog' => 
      array (
        'driver' => 'errorlog',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'null' => 
      array (
        'driver' => 'monolog',
        'handler' => 'Monolog\\Handler\\NullHandler',
      ),
      'emergency' => 
      array (
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/laravel.log',
      ),
      'security' => 
      array (
        'driver' => 'single',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/security.log',
        'level' => 'info',
        'days' => 365,
      ),
      'audit' => 
      array (
        'driver' => 'single',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/audit.log',
        'level' => 'info',
        'days' => 365,
      ),
      'performance' => 
      array (
        'driver' => 'single',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/performance.log',
        'level' => 'info',
        'days' => 90,
      ),
    ),
    'retention' => 
    array (
      'low_severity_days' => 30,
      'medium_severity_days' => 90,
      'high_severity_days' => 180,
      'critical_severity_days' => 'indefinite',
      'security_category_days' => 365,
      'max_logs_per_category' => 100000,
    ),
    'thresholds' => 
    array (
      'production' => 'error',
      'staging' => 'warning',
      'development' => 'debug',
    ),
    'sanitization' => 
    array (
      'enabled' => true,
      'patterns' => 
      array (
        0 => '/\\b(?:\\d{4}[-\\s]?){3}\\d{4}\\b/',
        1 => '/\\b\\d{3}-\\d{2}-\\d{4}\\b/',
        2 => '/[\\w\\.-]+@[\\w\\.-]+\\.\\w+/',
        3 => '/\\b\\d{3}[-.]?\\d{3}[-.]?\\d{4}\\b/',
        4 => '/\\b\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\b/',
      ),
      'replacement' => '[REDACTED]',
    ),
  ),
  'logging-prod' => 
  array (
    'default' => 'stack',
    'levels' => 
    array (
      'debug' => 100,
      'info' => 200,
      'notice' => 250,
      'warning' => 300,
      'error' => 400,
      'critical' => 500,
      'alert' => 550,
      'emergency' => 600,
    ),
    'channels' => 
    array (
      'stack' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'daily',
        ),
        'ignore_exceptions' => false,
      ),
      'daily' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/laravel.log',
        'level' => 'debug',
        'days' => 30,
      ),
      'error' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/error.log',
        'level' => 'error',
        'days' => 30,
      ),
      'access' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/access.log',
        'level' => 'info',
        'days' => 90,
      ),
      'queries' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/queries.log',
        'level' => 'debug',
        'days' => 7,
      ),
      'security' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/security.log',
        'level' => 'info',
        'days' => 365,
      ),
      'auth' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/auth.log',
        'level' => 'info',
        'days' => 90,
      ),
      'performance' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/performance.log',
        'level' => 'warning',
        'days' => 30,
      ),
      'tenant' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/tenant.log',
        'level' => 'debug',
        'days' => 90,
      ),
      'api' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/api.log',
        'level' => 'info',
        'days' => 30,
      ),
      'jobs' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/jobs.log',
        'level' => 'info',
        'days' => 14,
      ),
      'mail' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/mail.log',
        'level' => 'info',
        'days' => 30,
      ),
      'monitoring' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/monitoring.log',
        'level' => 'info',
        'days' => 90,
      ),
      'single' => 
      array (
        'driver' => 'single',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/laravel.log',
        'level' => 'debug',
      ),
      'slack' => 
      array (
        'driver' => 'slack',
        'url' => NULL,
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'critical',
        'replace_placeholders' => true,
      ),
      'papertrail' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\SyslogUdpHandler',
        'handler_with' => 
        array (
          'host' => NULL,
          'port' => NULL,
          'facility' => 8,
        ),
      ),
      'stackdriver' => 
      array (
        'driver' => 'stackdriver',
        'level' => 'debug',
      ),
      'null' => 
      array (
        'driver' => 'monolog',
        'handler' => 'Monolog\\Handler\\NullHandler',
      ),
      'emergency' => 
      array (
        'driver' => 'single',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/emergency.log',
        'level' => 'emergency',
      ),
    ),
    'formatters' => 
    array (
      'Monolog\\Formatter\\LineFormatter' => 
      array (
        'format' => '[%datetime%] %channel%.%level_name%: %message% %context% %extra%
',
        'dateFormat' => 'Y-m-d H:i:s.u',
        'allowInlineLineBreaks' => true,
        'ignoreEmptyContextAndExtra' => true,
      ),
      'Monolog\\Formatter\\JsonFormatter' => 
      array (
        'format' => 'json',
        'batchMode' => 2,
        'ignoreEmptyContextAndExtra' => true,
      ),
      'Monolog\\Formatter\\HtmlFormatter' => 
      array (
        'format' => 'html',
      ),
    ),
    'processors' => 
    array (
      0 => 'Monolog\\Processor\\UidProcessor',
      1 => 'Monolog\\Processor\\HostnameProcessor',
    ),
    'tenant' => 
    array (
      'enabled' => true,
      'separate_channels' => false,
      'tenant_id_context' => true,
    ),
    'masking' => 
    array (
      'enabled' => true,
      'patterns' => 
      array (
        '/"password"\\s*:\\s*"[^"]*"/i' => '"password":"[MASKED]"',
        '/password\\s*=\\s*[^\\s]+/i' => 'password=[MASKED]',
        '/api[_-]?key["\']?\\s*[:=]\\s*["\']?[a-zA-Z0-9-_]{16,}["\']?/i' => 'api_key=[MASKED]',
        '/(Bearer|token|bearer|token)\\s+[a-zA-Z0-9\\-\\._~\\+\\/]+=*/i' => '$1 [MASKED]',
        '/\\b(?:\\d[ -]*?){13,16}\\b/' => '[CREDIT_CARD_MASKED]',
        '/\\b\\d{3}-\\d{2}-\\d{4}\\b/' => '[SSN_MASKED]',
        '/email["\']?\\s*[:=]\\s*["\']?[^"\']+["\']?/i' => 'email=[MASKED]',
      ),
    ),
    'audit' => 
    array (
      'enabled' => true,
      'events' => 
      array (
        0 => 'created',
        1 => 'updated',
        2 => 'deleted',
        3 => 'restored',
        4 => 'force_deleted',
        5 => 'login',
        6 => 'logout',
        7 => 'password_reset',
      ),
      'ignore_fields' => 
      array (
        0 => 'id',
        1 => 'created_at',
        2 => 'updated_at',
      ),
    ),
  ),
  'mail' => 
  array (
    'default' => 'log',
    'mailers' => 
    array (
      'smtp' => 
      array (
        'transport' => 'smtp',
        'scheme' => NULL,
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '2525',
        'username' => NULL,
        'password' => NULL,
        'timeout' => NULL,
        'local_domain' => '127.0.0.1',
      ),
      'ses' => 
      array (
        'transport' => 'ses',
      ),
      'postmark' => 
      array (
        'transport' => 'postmark',
      ),
      'resend' => 
      array (
        'transport' => 'resend',
      ),
      'sendmail' => 
      array (
        'transport' => 'sendmail',
        'path' => '/usr/sbin/sendmail -bs -i',
      ),
      'log' => 
      array (
        'transport' => 'log',
        'channel' => NULL,
      ),
      'array' => 
      array (
        'transport' => 'array',
      ),
      'failover' => 
      array (
        'transport' => 'failover',
        'mailers' => 
        array (
          0 => 'smtp',
          1 => 'log',
        ),
      ),
      'roundrobin' => 
      array (
        'transport' => 'roundrobin',
        'mailers' => 
        array (
          0 => 'ses',
          1 => 'postmark',
        ),
      ),
    ),
    'from' => 
    array (
      'address' => 'hello@example.com',
      'name' => 'Alumate',
    ),
    'markdown' => 
    array (
      'theme' => 'default',
      'paths' => 
      array (
        0 => 'D:\\DevCenter\\abuilds\\alumate\\resources\\views/vendor/mail',
      ),
    ),
  ),
  'monitoring' => 
  array (
    'enabled' => true,
    'environment' => 'local',
    'version' => '3.0.0',
    'alerts' => 
    array (
      'enabled' => true,
      'channels' => 
      array (
        'email' => 
        array (
          'enabled' => true,
          'recipients' => 
          array (
            0 => 'admin@example.com',
          ),
        ),
        'slack' => 
        array (
          'enabled' => true,
          'webhook_url' => '',
          'channel' => '#alerts',
          'username' => 'Monitoring Bot',
          'icon' => ':robot_face:',
        ),
        'pagerduty' => 
        array (
          'enabled' => false,
          'integration_key' => '',
          'service_name' => 'Alumni Platform',
        ),
        'datadog' => 
        array (
          'enabled' => false,
          'api_key' => '',
          'app_key' => NULL,
        ),
        'webhook' => 
        array (
          'enabled' => false,
          'url' => NULL,
          'method' => 'POST',
          'headers' => 
          array (
          ),
        ),
      ),
      'thresholds' => 
      array (
        'queue_backlog' => 
        array (
          'warning' => 50,
          'critical' => 100,
          'unit' => 'jobs',
        ),
        'error_rate' => 
        array (
          'warning' => 0.5,
          'critical' => 1.0,
          'unit' => 'percentage',
        ),
        'response_time' => 
        array (
          'warning' => 1000,
          'critical' => 3000,
          'unit' => 'milliseconds',
        ),
        'memory_usage' => 
        array (
          'warning' => 80,
          'critical' => 95,
          'unit' => 'percentage',
        ),
        'cpu_usage' => 
        array (
          'warning' => 70,
          'critical' => 90,
          'unit' => 'percentage',
        ),
        'disk_usage' => 
        array (
          'warning' => 80,
          'critical' => 95,
          'unit' => 'percentage',
        ),
        'failed_jobs' => 
        array (
          'warning' => 10,
          'critical' => 50,
          'unit' => 'jobs',
        ),
        'db_connections' => 
        array (
          'warning' => 80,
          'critical' => 95,
          'unit' => 'percentage',
        ),
        'cache_hit_rate' => 
        array (
          'warning' => 80,
          'critical' => 60,
          'unit' => 'percentage',
        ),
        'session_usage' => 
        array (
          'warning' => 85,
          'critical' => 95,
          'unit' => 'percentage',
        ),
      ),
      'rate_limits' => 
      array (
        'critical' => 300,
        'error' => 900,
        'warning' => 1800,
        'info' => 3600,
      ),
      'cooldown_periods' => 
      array (
        'low' => 300,
        'medium' => 900,
        'high' => 1800,
        'critical' => 3600,
      ),
    ),
    'health_checks' => 
    array (
      'enabled' => true,
      'interval' => 60,
      'timeout' => 10,
      'retries' => 3,
      'checks' => 
      array (
        'database' => 
        array (
          'enabled' => true,
          'timeout' => 5,
          'critical' => true,
        ),
        'redis' => 
        array (
          'enabled' => true,
          'timeout' => 3,
          'critical' => true,
        ),
        'queue' => 
        array (
          'enabled' => true,
          'timeout' => 5,
          'critical' => false,
        ),
        'storage' => 
        array (
          'enabled' => true,
          'timeout' => 5,
          'critical' => true,
        ),
        'cache' => 
        array (
          'enabled' => true,
          'timeout' => 3,
          'critical' => false,
        ),
        'mail' => 
        array (
          'enabled' => true,
          'timeout' => 5,
          'critical' => false,
        ),
        'external_apis' => 
        array (
          'enabled' => true,
          'timeout' => 10,
          'critical' => false,
        ),
        'filesystem' => 
        array (
          'enabled' => true,
          'timeout' => 5,
          'critical' => true,
        ),
        'php_extensions' => 
        array (
          'enabled' => true,
          'timeout' => 2,
          'critical' => true,
        ),
      ),
      'endpoints' => 
      array (
        'basic' => '/health',
        'detailed' => '/health/detailed',
        'ready' => '/ready',
        'live' => '/live',
      ),
    ),
    'metrics' => 
    array (
      'enabled' => true,
      'retention' => 
      array (
        'performance' => 30,
        'error' => 90,
        'analytics' => 365,
        'alert' => 180,
        'business' => 730,
      ),
      'collection' => 
      array (
        'response_times' => true,
        'memory_usage' => true,
        'database_queries' => true,
        'cache_hits' => true,
        'error_rates' => true,
        'queue_metrics' => true,
        'custom_metrics' => true,
      ),
      'aggregation' => 
      array (
        'interval' => 60,
        'precision' => 2,
      ),
    ),
    'performance' => 
    array (
      'enabled' => true,
      'budgets' => 
      array (
        'response_time' => 
        array (
          'warning' => 1000,
          'critical' => 3000,
          'unit' => 'ms',
        ),
        'memory_usage' => 
        array (
          'warning' => 256,
          'critical' => 512,
          'unit' => 'MB',
        ),
        'db_query_time' => 
        array (
          'warning' => 200,
          'critical' => 500,
          'unit' => 'ms',
        ),
        'cache_miss_rate' => 
        array (
          'warning' => 0.1,
          'critical' => 0.2,
          'unit' => 'percentage',
        ),
      ),
      'profiling' => 
      array (
        'enabled' => false,
        'sampling_rate' => 0.01,
        'max_stack_depth' => 50,
        'exclude_patterns' => 
        array (
          0 => '/vendor/',
          1 => '/bootstrap/',
        ),
      ),
      'database_queries' => 
      array (
        'slow_query_threshold' => 1000,
        'very_slow_query_threshold' => 5000,
        'log_queries' => true,
        'log_binding_values' => false,
      ),
    ),
    'apm' => 
    array (
      'enabled' => false,
      'sample_rate' => 0.1,
      'services' => 
      array (
        'sentry' => 
        array (
          'enabled' => false,
          'dsn' => NULL,
          'environment' => 'local',
          'traces_sample_rate' => 0.1,
          'profiles_sample_rate' => 0.1,
        ),
        'newrelic' => 
        array (
          'enabled' => false,
          'license_key' => NULL,
          'app_name' => 'Alumni Platform',
          'distributed_tracing' => true,
        ),
        'datadog' => 
        array (
          'enabled' => false,
          'service' => 'alumni-platform',
          'env' => 'local',
          'version' => NULL,
        ),
      ),
    ),
    'database_monitoring' => 
    array (
      'enabled' => true,
      'connections' => 
      array (
        'pgsql' => 
        array (
          'enabled' => true,
          'metrics' => 
          array (
            'connections' => true,
            'queries' => true,
            'slow_queries' => true,
            'locks' => true,
            'cache_hit_ratio' => true,
            'index_usage' => true,
            'table_size' => true,
            'vacuum_status' => true,
          ),
          'thresholds' => 
          array (
            'max_connections' => 200,
            'slow_query_threshold_ms' => 1000,
            'idle_connection_timeout' => 300,
          ),
        ),
      ),
      'replication' => 
      array (
        'enabled' => false,
        'check_interval' => 30,
        'lag_threshold' => 5,
      ),
    ),
    'cache_monitoring' => 
    array (
      'enabled' => true,
      'metrics' => 
      array (
        'hit_rate' => true,
        'miss_rate' => true,
        'memory_usage' => true,
        'evictions' => true,
        'connections' => true,
        'latency' => true,
        'keys_count' => true,
        'expired_keys' => true,
        'evicted_keys' => true,
      ),
      'thresholds' => 
      array (
        'memory_usage_percent' => 80,
        'hit_rate_percent' => 80,
        'connection_pool_usage' => 90,
        'latency_threshold_ms' => 10,
      ),
    ),
    'queue_monitoring' => 
    array (
      'enabled' => true,
      'metrics' => 
      array (
        'jobs_waiting' => true,
        'jobs_processing' => true,
        'jobs_failed' => true,
        'jobs_retry' => true,
        'job_duration' => true,
        'job_throughput' => true,
        'worker_status' => true,
      ),
      'thresholds' => 
      array (
        'max_jobs_waiting' => 100,
        'max_jobs_failed' => 10,
        'max_job_duration_seconds' => 300,
        'max_retry_count' => 3,
      ),
      'horizon' => 
      array (
        'enabled' => true,
        'balancer' => 'workload',
      ),
    ),
    'error_tracking' => 
    array (
      'enabled' => true,
      'channels' => 
      array (
        'sentry' => 
        array (
          'enabled' => false,
          'dsn' => NULL,
          'environment' => 'local',
          'release' => NULL,
          'sample_rate' => 1.0,
          'max_breadcrumbs' => 100,
        ),
        'rollbar' => 
        array (
          'enabled' => false,
          'access_token' => NULL,
          'environment' => 'local',
        ),
      ),
      'filters' => 
      array (
        'ignore_404' => true,
        'ignore_403' => false,
        'ignore_csrf' => true,
        'ignore_exceptions' => 
        array (
        ),
      ),
      'grouping' => 
      array (
        'enabled' => true,
        'similarity_threshold' => 0.9,
      ),
    ),
    'uptime_monitoring' => 
    array (
      'enabled' => true,
      'checks' => 
      array (
        'http' => 
        array (
          'enabled' => true,
          'endpoint' => '/health',
          'method' => 'GET',
          'expected_status' => 200,
        ),
        'https' => 
        array (
          'enabled' => true,
          'endpoint' => '/health',
          'verify_ssl' => true,
        ),
        'ports' => 
        array (
          'enabled' => true,
          'ports' => 
          array (
            0 => 80,
            1 => 443,
          ),
        ),
      ),
      'schedule' => 
      array (
        'interval' => 60,
        'timeout' => 30,
      ),
      'history' => 
      array (
        'retention_days' => 90,
        'precision_seconds' => 60,
      ),
    ),
    'custom_metrics' => 
    array (
      'enabled' => true,
      'business' => 
      array (
        'active_users' => 
        array (
          'enabled' => true,
          'description' => 'Number of active users',
          'type' => 'gauge',
        ),
        'new_registrations' => 
        array (
          'enabled' => true,
          'description' => 'Number of new user registrations',
          'type' => 'counter',
        ),
        'tenant_count' => 
        array (
          'enabled' => true,
          'description' => 'Number of active tenants',
          'type' => 'gauge',
        ),
        'session_count' => 
        array (
          'enabled' => true,
          'description' => 'Number of active sessions',
          'type' => 'gauge',
        ),
        'api_requests' => 
        array (
          'enabled' => true,
          'description' => 'API request count',
          'type' => 'counter',
        ),
        'conversion_rate' => 
        array (
          'enabled' => true,
          'description' => 'User conversion rate',
          'type' => 'gauge',
        ),
      ),
      'application' => 
      array (
        'login_attempts' => 
        array (
          'enabled' => true,
          'description' => 'Login attempt count',
          'type' => 'counter',
        ),
        'failed_logins' => 
        array (
          'enabled' => true,
          'description' => 'Failed login count',
          'type' => 'counter',
        ),
        'password_resets' => 
        array (
          'enabled' => true,
          'description' => 'Password reset requests',
          'type' => 'counter',
        ),
        'email_sent' => 
        array (
          'enabled' => true,
          'description' => 'Emails sent',
          'type' => 'counter',
        ),
      ),
    ),
    'notifications' => 
    array (
      'templates' => 
      array (
        'queue_backlog' => 'Queue backlog has exceeded :threshold jobs (:current jobs)',
        'error_rate' => 'Error rate has exceeded :threshold% (:current%)',
        'response_time' => 'Response time has exceeded :thresholdms (:currentms)',
        'memory_usage' => 'Memory usage has exceeded :threshold% (:current%)',
        'cpu_usage' => 'CPU usage has exceeded :threshold% (:current%)',
        'disk_usage' => 'Disk usage has exceeded :threshold% (:current%)',
        'failed_jobs' => 'Failed jobs count has exceeded :threshold (:current)',
        'database_connection' => 'Database connection pool usage at :threshold% (:current%)',
        'cache_hit_rate' => 'Cache hit rate has fallen below :threshold% (:current%)',
        'security_alert' => 'Security alert: :type detected',
        'system_down' => 'System is down or unresponsive',
        'deployment_complete' => 'Deployment completed successfully',
        'backup_complete' => 'Backup completed successfully',
      ),
      'formats' => 
      array (
        'email' => 
        array (
          'subject_prefix' => '[ALUMATE]',
          'severity_emoji' => true,
          'include_stack_trace' => true,
        ),
        'slack' => 
        array (
          'use_blocks' => true,
          'severity_emoji' => true,
        ),
      ),
    ),
    'reporting' => 
    array (
      'enabled' => true,
      'schedules' => 
      array (
        'daily' => 
        array (
          'enabled' => true,
          'time' => '06:00',
          'recipients' => 
          array (
            0 => 'email',
          ),
        ),
        'weekly' => 
        array (
          'enabled' => true,
          'day' => 'monday',
          'time' => '08:00',
          'recipients' => 
          array (
            0 => 'email',
          ),
        ),
        'monthly' => 
        array (
          'enabled' => true,
          'day' => 1,
          'time' => '09:00',
          'recipients' => 
          array (
            0 => 'email',
          ),
        ),
      ),
      'contents' => 
      array (
        'system_health' => true,
        'performance_metrics' => true,
        'error_summary' => true,
        'alerts_summary' => true,
        'business_metrics' => true,
        'security_events' => true,
      ),
    ),
    'tenancy' => 
    array (
      'isolate_tenant_data' => true,
      'shared_monitoring' => false,
      'tenant_alert_separation' => true,
      'cross_tenant_analytics' => false,
      'aggregate_tenant_metrics' => true,
    ),
    'security_monitoring' => 
    array (
      'enabled' => true,
      'threat_detection' => 
      array (
        'brute_force' => 
        array (
          'enabled' => true,
          'threshold' => 10,
          'window_minutes' => 15,
        ),
        'suspicious_activity' => 
        array (
          'enabled' => true,
          'geo_anomaly' => 
          array (
            'enabled' => true,
            'max_distance_km' => 1000,
          ),
        ),
      ),
      'audit' => 
      array (
        'enabled' => true,
        'events' => 
        array (
          'login' => true,
          'logout' => true,
          'password_change' => true,
          'email_change' => true,
          'role_change' => true,
          'permission_change' => true,
          'data_export' => true,
          'data_delete' => true,
        ),
      ),
    ),
    'integrations' => 
    array (
      'prometheus' => 
      array (
        'enabled' => true,
        'path' => '/metrics',
        'namespace' => 'alumate',
      ),
      'grafana' => 
      array (
        'enabled' => false,
        'datasource_url' => NULL,
      ),
      'newrelic' => 
      array (
        'enabled' => false,
        'app_name' => NULL,
      ),
    ),
  ),
  'permission' => 
  array (
    'models' => 
    array (
      'permission' => 'Spatie\\Permission\\Models\\Permission',
      'role' => 'Spatie\\Permission\\Models\\Role',
    ),
    'table_names' => 
    array (
      'roles' => 'roles',
      'permissions' => 'permissions',
      'model_has_permissions' => 'model_has_permissions',
      'model_has_roles' => 'model_has_roles',
      'role_has_permissions' => 'role_has_permissions',
    ),
    'column_names' => 
    array (
      'role_pivot_key' => NULL,
      'permission_pivot_key' => NULL,
      'model_morph_key' => 'model_id',
      'team_foreign_key' => 'team_id',
    ),
    'register_permission_check_method' => true,
    'register_octane_reset_listener' => false,
    'events_enabled' => false,
    'teams' => false,
    'team_resolver' => 'Spatie\\Permission\\DefaultTeamResolver',
    'use_passport_client_credentials' => false,
    'display_permission_in_exception' => false,
    'display_role_in_exception' => false,
    'enable_wildcard_permission' => false,
    'cache' => 
    array (
      'expiration_time' => 
      \DateInterval::__set_state(array(
         'from_string' => true,
         'date_string' => '24 hours',
      )),
      'key' => 'spatie.permission.cache',
      'store' => 'default',
    ),
  ),
  'production' => 
  array (
    'app' => 
    array (
      'env' => 'local',
      'debug' => true,
      'debug_bar' => false,
      'telescope' => false,
      'name' => 'Alumate',
      'url' => 'http://127.0.0.1:8080',
      'timezone' => 'UTC',
      'locale' => 'en',
      'fallback_locale' => 'en',
      'key' => 'base64:KI26BZM/wbAVl9GrLRW+QD7cMCNh9C1cqIBM/Ms40SQ=',
      'cipher' => 'AES-256-CBC',
    ),
    'logging' => 
    array (
      'channel' => 'stack',
      'level' => 'debug',
      'max_files' => 30,
      'deprecations_channel' => NULL,
      'slack_webhook_url' => NULL,
    ),
    'database' => 
    array (
      'default' => 'pgsql',
      'connections' => 
      array (
        'pgsql' => 
        array (
          'driver' => 'pgsql',
          'host' => '127.0.0.1',
          'port' => '5433',
          'database' => 'alumate',
          'username' => 'postgres',
          'password' => 'postgres',
          'charset' => 'utf8',
          'prefix' => '',
          'prefix_indexes' => true,
          'search_path' => 'public',
          'sslmode' => 'disable',
          'options' => 
          array (
            20 => true,
            12 => true,
            3 => 2,
            19 => 2,
          ),
        ),
      ),
      'redis' => 
      array (
        'client' => 'phpredis',
        'prefix' => 'alumate_',
      ),
    ),
    'cache' => 
    array (
      'driver' => 'redis',
      'store' => 'database',
      'prefix' => 'alumate_cache_',
      'stores' => 
      array (
        'redis' => 
        array (
          'driver' => 'redis',
          'connection' => 'cache',
          'lock_connection' => 'default',
        ),
      ),
    ),
    'session' => 
    array (
      'driver' => 'database',
      'lifetime' => 120,
      'expire_on_close' => false,
      'encrypt' => false,
      'files' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\framework/sessions',
      'connection' => 'session',
      'table' => 'sessions',
      'store' => NULL,
      'lottery' => 
      array (
        0 => 2,
        1 => 100,
      ),
      'cookie' => 'alumate_session',
      'path' => '/',
      'domain' => NULL,
      'secure' => true,
      'http_only' => true,
      'same_site' => 'lax',
      'partitioned' => false,
    ),
    'queue' => 
    array (
      'default' => 'database',
      'failed' => 
      array (
        'driver' => 'database-uuids',
        'database' => 'pgsql',
        'table' => 'failed_jobs',
      ),
    ),
    'redis' => 
    array (
      'client' => 'phpredis',
      'options' => 
      array (
        'cluster' => false,
        'prefix' => 'alumate_',
        'persistent' => true,
      ),
      'default' => 
      array (
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '0',
      ),
      'cache' => 
      array (
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '1',
      ),
      'session' => 
      array (
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '2',
      ),
      'queue' => 
      array (
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '3',
      ),
    ),
    'mail' => 
    array (
      'default' => 'log',
      'mailers' => 
      array (
        'smtp' => 
        array (
          'transport' => 'smtp',
          'host' => '127.0.0.1',
          'port' => '2525',
          'username' => NULL,
          'password' => NULL,
          'encryption' => 'tls',
          'timeout' => NULL,
          'local_domain' => NULL,
        ),
        'failover' => 
        array (
          'transport' => 'failover',
          'mailers' => 
          array (
            0 => 'smtp',
            1 => 'log',
          ),
        ),
      ),
      'from' => 
      array (
        'address' => 'hello@example.com',
        'name' => 'Alumate',
      ),
    ),
    'filesystems' => 
    array (
      'default' => 'local',
      'disks' => 
      array (
        'local' => 
        array (
          'driver' => 'local',
          'root' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\app',
          'url' => 'http://127.0.0.1:8080/storage',
          'visibility' => 'private',
        ),
        's3' => 
        array (
          'driver' => 's3',
          'key' => '',
          'secret' => '',
          'region' => 'us-east-1',
          'bucket' => '',
          'url' => NULL,
          'endpoint' => NULL,
          'use_path_style_endpoint' => false,
          'visibility' => 'public-read',
        ),
      ),
    ),
    'tenancy' => 
    array (
      'enabled' => true,
      'database_auto_delete_enabled' => false,
      'database_auto_cleanup' => false,
      'isolate_data' => true,
      'default_domain' => 'alumni-platform.com',
    ),
    'security' => 
    array (
      'max_login_attempts' => 5,
      'lockout_duration' => 30,
      'rate_limit_authenticated' => 100,
      'rate_limit_unauthenticated' => 30,
      'session_timeout' => 120,
      'two_factor_enabled' => true,
      'two_factor_force' => false,
      'password' => 
      array (
        'hash' => 'bcrypt',
        'rounds' => 12,
      ),
    ),
    'monitoring' => 
    array (
      'enabled' => true,
      'sentry' => 
      array (
        'enabled' => false,
        'dsn' => 'https://5b2c3cb3a5eb423893d58842bbe71483@app1.genius2.mrmarkuz.ddnss.eu/1',
        'environment' => 'local',
        'traces_sample_rate' => 0.1,
        'profiles_sample_rate' => 0.1,
      ),
      'new_relic' => 
      array (
        'enabled' => false,
        'app_name' => 'Alumate',
        'license_key' => NULL,
        'distributed_tracing' => true,
      ),
      'datadog' => 
      array (
        'enabled' => false,
        'service' => 'Alumate',
        'environment' => 'local',
        'agent_host' => 'localhost',
        'trace_agent_port' => 8126,
      ),
    ),
    'alerts' => 
    array (
      'enabled' => true,
      'channels' => 
      array (
        'email' => 
        array (
          'enabled' => true,
          'recipients' => 
          array (
            0 => 'admin@alumni-platform.com',
          ),
        ),
        'slack' => 
        array (
          'enabled' => false,
          'webhook_url' => NULL,
        ),
        'pagerduty' => 
        array (
          'enabled' => false,
          'service_key' => NULL,
        ),
      ),
      'thresholds' => 
      array (
        'memory_warning' => 128,
        'memory_critical' => 256,
        'response_warning' => 500,
        'response_critical' => 1000,
        'error_warning' => 1.0,
        'error_critical' => 5.0,
      ),
      'cooldowns' => 
      array (
        'low' => 300,
        'medium' => 1800,
        'high' => 3600,
        'critical' => 7200,
      ),
    ),
    'performance' => 
    array (
      'php_fpm' => 
      array (
        'pm' => 'dynamic',
        'pm_max_children' => 10,
        'pm_start_servers' => 2,
        'pm_min_spare_servers' => 1,
        'pm_max_spare_servers' => 5,
        'pm_max_requests' => 500,
      ),
      'redis' => 
      array (
        'pool_size' => 10,
        'pool_timeout' => 10,
      ),
      'queue' => 
      array (
        'worker_sleep' => 3,
        'worker_max_tries' => 3,
        'worker_timeout' => 90,
      ),
    ),
    'analytics' => 
    array (
      'enabled' => true,
      'cache' => 
      array (
        'enabled' => true,
        'ttl' => 300,
        'prefix' => 'analytics:',
      ),
      'snapshots' => 
      array (
        'enabled' => true,
        'retention_days' => 365,
      ),
      'performance' => 
      array (
        'query_timeout' => 60,
        'memory_limit' => '512M',
        'chunk_size' => 1000,
      ),
      'security' => 
      array (
        'data_anonymization' => false,
        'rate_limiting' => true,
        'max_requests_per_minute' => 60,
      ),
    ),
    'features' => 
    array (
      'analytics' => true,
      'notifications' => true,
      'search' => true,
      'webhooks' => true,
      'batch_operations' => true,
      'import_export' => true,
    ),
    'data_retention' => 
    array (
      'general' => 365,
      'analytics' => 90,
      'logs' => 90,
      'gdpr_mode' => true,
      'anonymize_after_days' => 90,
    ),
    'backup' => 
    array (
      'enabled' => true,
      'provider' => 'aws_s3',
      'storage_disk' => 'local',
      'schedule' => '0 1 * * *',
      'compression' => 'lz4',
      'encryption' => true,
      'retention' => 
      array (
        'database' => 30,
        'files' => 90,
        'config' => 365,
      ),
      'retention_policy' => 
      array (
        'max_age_days' => 90,
        'max_count' => 100,
        'keep_critical' => true,
      ),
      'verification' => 
      array (
        'enabled' => true,
        'checksum' => 'sha256',
        'min_size_bytes' => 1024,
      ),
      'notifications' => 
      array (
        'enabled' => true,
        'on_success' => true,
        'on_failure' => true,
        'channels' => 
        array (
          0 => 'mail',
        ),
      ),
      'aws' => 
      array (
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'bucket' => NULL,
        'storage_class' => 'STANDARD_IA',
      ),
      'gcp' => 
      array (
        'project_id' => NULL,
        'bucket' => NULL,
      ),
      'azure' => 
      array (
        'container' => 'backups',
        'account' => NULL,
        'key' => NULL,
      ),
    ),
    'external_services' => 
    array (
      'google' => 
      array (
        'client_id' => NULL,
        'client_secret' => NULL,
        'redirect_uri' => NULL,
      ),
      'facebook' => 
      array (
        'client_id' => NULL,
        'client_secret' => NULL,
        'redirect_uri' => NULL,
      ),
      'linkedin' => 
      array (
        'client_id' => NULL,
        'client_secret' => NULL,
        'redirect_uri' => NULL,
      ),
      'matomo' => 
      array (
        'url' => NULL,
        'site_id' => NULL,
        'token' => NULL,
      ),
    ),
    'health_checks' => 
    array (
      'enabled' => true,
      'interval' => 30,
      'timeout' => 10,
      'retries' => 3,
    ),
    'infrastructure' => 
    array (
      'container' => 
      array (
        'memory_limit' => '512M',
        'cpu_limit' => '1.0',
      ),
    ),
  ),
  'queue' => 
  array (
    'default' => 'database',
    'connections' => 
    array (
      'sync' => 
      array (
        'driver' => 'sync',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
      ),
      'beanstalkd' => 
      array (
        'driver' => 'beanstalkd',
        'host' => 'localhost',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
        'after_commit' => false,
      ),
      'sqs' => 
      array (
        'driver' => 'sqs',
        'key' => '',
        'secret' => '',
        'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
        'queue' => 'default',
        'suffix' => NULL,
        'region' => 'us-east-1',
        'after_commit' => false,
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => NULL,
        'after_commit' => false,
      ),
      'deferred' => 
      array (
        'driver' => 'deferred',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'connections' => 
        array (
          0 => 'database',
          1 => 'deferred',
        ),
      ),
    ),
    'batching' => 
    array (
      'database' => 'pgsql',
      'table' => 'job_batches',
    ),
    'failed' => 
    array (
      'driver' => 'database-uuids',
      'database' => 'pgsql',
      'table' => 'failed_jobs',
    ),
  ),
  'security' => 
  array (
    'max_login_attempts' => 5,
    'lockout_duration' => 30,
    'rate_limit_authenticated' => 100,
    'rate_limit_unauthenticated' => 30,
    'session_timeout' => 120,
    'track_suspicious_sessions' => true,
    'two_factor_required_roles' => 
    array (
      0 => 'super-admin',
      1 => 'institution-admin',
    ),
    'two_factor_recovery_codes_count' => 8,
    'backup' => 
    array (
      'retention_days' => 30,
      'compression_enabled' => true,
      'storage_disk' => 'local',
    ),
    'monitoring' => 
    array (
      'log_data_access' => true,
      'detect_malicious_requests' => true,
      'alert_critical_events' => true,
    ),
    'health_check' => 
    array (
      'database_timeout' => 5,
      'cache_timeout' => 2,
      'storage_timeout' => 3,
      'memory_warning_threshold' => 80,
      'memory_critical_threshold' => 90,
      'disk_warning_threshold' => 80,
      'disk_critical_threshold' => 90,
    ),
  ),
  'services' => 
  array (
    'postmark' => 
    array (
      'token' => NULL,
    ),
    'resend' => 
    array (
      'key' => NULL,
    ),
    'ses' => 
    array (
      'key' => '',
      'secret' => '',
      'region' => 'us-east-1',
      'configuration_set' => NULL,
      'from_arn' => NULL,
      'feedback_forwarding' => true,
      'rate_limits' => 
      array (
        'per_second' => 14,
        'per_day' => 50000,
      ),
    ),
    'slack' => 
    array (
      'notifications' => 
      array (
        'bot_user_oauth_token' => NULL,
        'channel' => NULL,
      ),
    ),
    'sentry' => 
    array (
      'dsn' => 'https://5b2c3cb3a5eb423893d58842bbe71483@app1.genius2.mrmarkuz.ddnss.eu/1',
      'environment' => 'local',
      'release' => '1.0.0',
      'traces_sample_rate' => 0.1,
      'profiles_sample_rate' => 0.1,
    ),
    'monitoring' => 
    array (
      'alert_email' => '',
      'slack_webhook' => '',
      'pagerduty_key' => '',
      'datadog_api_key' => '',
      'newrelic_api_key' => '',
    ),
    'vapid' => 
    array (
      'public_key' => 'demo-vapid-public-key-for-development',
      'private_key' => 'demo-vapid-private-key-for-development',
      'subject' => 'mailto:admin@alumate.com',
    ),
    'google_analytics' => 
    array (
      'measurement_id' => NULL,
      'property_id' => NULL,
      'api_secret' => NULL,
      'service_account_json' => NULL,
      'service_account_key_file' => NULL,
      'timeout' => 10,
      'connect_timeout' => 5,
    ),
    'matomo' => 
    array (
      'url' => NULL,
      'site_id' => NULL,
      'token_auth' => NULL,
      'timeout' => 10,
      'connect_timeout' => 5,
    ),
    'stripe' => 
    array (
      'key' => NULL,
      'secret' => NULL,
      'webhook_secret' => NULL,
      'webhook_tolerance' => 300,
      'currency' => 'usd',
      'model' => 'App\\Models\\Tenant',
      'prorate' => true,
      'tax_rates' => 
      array (
      ),
    ),
    'sendgrid' => 
    array (
      'api_key' => NULL,
      'endpoint' => 'https://api.sendgrid.com/v3',
      'webhook_secret' => NULL,
      'templates' => 
      array (
        'enabled' => false,
        'default_version' => '1',
      ),
      'rate_limits' => 
      array (
        'per_minute' => 100,
        'per_hour' => 6000,
      ),
    ),
    'mailgun' => 
    array (
      'domain' => NULL,
      'secret' => NULL,
      'endpoint' => 'https://api.mailgun.net/v3',
      'webhook_secret' => NULL,
      'route_domain' => NULL,
      'rate_limits' => 
      array (
        'per_minute' => 300,
        'per_hour' => 5000,
      ),
    ),
    'email' => 
    array (
      'default_provider' => 'internal',
      'unsubscribe_on_hard_bounce' => false,
      'max_retries' => 5,
      'retry_backoff_minutes' => 5,
      'tracking' => 
      array (
        'opens' => true,
        'clicks' => true,
        'pixel_path' => '/email/track/open',
        'click_path' => '/email/track/click',
      ),
      'analytics' => 
      array (
        'cache_duration' => 1800,
        'realtime_window' => 5,
      ),
    ),
  ),
  'session' => 
  array (
    'driver' => 'database',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\framework/sessions',
    'connection' => NULL,
    'table' => 'sessions',
    'store' => NULL,
    'lottery' => 
    array (
      0 => 2,
      1 => 100,
    ),
    'cookie' => 'alumate_session',
    'path' => '/',
    'domain' => NULL,
    'secure' => NULL,
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
  ),
  'sso' => 
  array (
    'defaults' => 
    array (
      'auto_provision' => false,
      'auto_update' => false,
      'session_timeout' => 3600,
      'remember_me' => true,
    ),
    'saml' => 
    array (
      'sp' => 
      array (
        'entityId' => 'http://127.0.0.1:8080',
        'assertionConsumerService' => 
        array (
          'url' => NULL,
          'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        ),
        'singleLogoutService' => 
        array (
          'url' => NULL,
          'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ),
        'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
        'x509cert' => NULL,
        'privateKey' => NULL,
      ),
      'security' => 
      array (
        'nameIdEncrypted' => false,
        'authnRequestsSigned' => false,
        'logoutRequestSigned' => false,
        'logoutResponseSigned' => false,
        'signMetadata' => false,
        'wantAssertionsSigned' => false,
        'wantNameId' => true,
        'wantAssertionsEncrypted' => false,
        'wantNameIdEncrypted' => false,
        'requestedAuthnContext' => true,
        'requestedAuthnContextComparison' => 'exact',
        'wantXMLValidation' => true,
        'relaxDestinationValidation' => false,
        'destinationStrictlyMatches' => false,
        'allowRepeatAttributeName' => false,
        'rejectUnsolicitedResponsesWithInResponseTo' => false,
        'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
        'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',
      ),
    ),
    'oauth' => 
    array (
      'default_scopes' => 
      array (
        0 => 'openid',
        1 => 'profile',
        2 => 'email',
      ),
      'state_lifetime' => 300,
      'pkce' => true,
      'response_type' => 'code',
      'response_mode' => 'query',
    ),
    'attribute_mapping' => 
    array (
      'saml' => 
      array (
        'name' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name',
        'email' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress',
        'first_name' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname',
        'last_name' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname',
        'phone' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/mobilephone',
        'department' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/department',
        'title' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/title',
        'groups' => 'http://schemas.xmlsoap.org/claims/Group',
      ),
      'oidc' => 
      array (
        'name' => 'name',
        'email' => 'email',
        'first_name' => 'given_name',
        'last_name' => 'family_name',
        'phone' => 'phone_number',
        'picture' => 'picture',
        'locale' => 'locale',
        'groups' => 'groups',
      ),
      'oauth2' => 
      array (
        'name' => 'name',
        'email' => 'email',
        'first_name' => 'given_name',
        'last_name' => 'family_name',
        'avatar' => 'avatar_url',
      ),
    ),
    'role_mapping' => 
    array (
      'default_role' => 'Graduate',
      'admin_roles' => 
      array (
        0 => 'admin',
        1 => 'administrator',
        2 => 'super_admin',
        3 => 'system_admin',
      ),
      'institution_admin_roles' => 
      array (
        0 => 'institution_admin',
        1 => 'school_admin',
        2 => 'university_admin',
      ),
      'student_roles' => 
      array (
        0 => 'student',
        1 => 'current_student',
      ),
      'alumni_roles' => 
      array (
        0 => 'alumni',
        1 => 'graduate',
        2 => 'alumnus',
      ),
      'employer_roles' => 
      array (
        0 => 'employer',
        1 => 'recruiter',
        2 => 'hr',
      ),
    ),
    'provisioning' => 
    array (
      'create_missing_users' => false,
      'update_existing_users' => false,
      'sync_roles' => true,
      'sync_attributes' => true,
      'required_attributes' => 
      array (
        0 => 'email',
        1 => 'name',
      ),
      'default_status' => 'active',
      'email_verification' => false,
    ),
    'session' => 
    array (
      'sso_session_key' => 'sso_session_id',
      'provider_session_key' => 'sso_provider',
      'logout_redirect' => '/',
      'login_redirect' => '/dashboard',
      'single_logout' => true,
    ),
    'security' => 
    array (
      'validate_issuer' => true,
      'validate_audience' => true,
      'validate_signature' => true,
      'validate_timestamps' => true,
      'clock_skew' => 300,
      'max_auth_age' => 3600,
      'require_encrypted_assertions' => false,
      'require_signed_assertions' => true,
    ),
    'logging' => 
    array (
      'enabled' => true,
      'level' => 'info',
      'channel' => 'single',
      'log_requests' => false,
      'log_responses' => false,
    ),
    'error_handling' => 
    array (
      'show_detailed_errors' => false,
      'fallback_to_local_auth' => true,
      'error_redirect' => '/login',
      'max_retry_attempts' => 3,
    ),
  ),
  'tenancy' => 
  array (
    'tenant_model' => 'Stancl\\Tenancy\\Database\\Models\\Tenant',
    'id_generator' => 'Stancl\\Tenancy\\UUIDGenerator',
    'domain_model' => 'Stancl\\Tenancy\\Database\\Models\\Domain',
    'central_domains' => 
    array (
      0 => '127.0.0.1',
      1 => 'localhost',
    ),
    'bootstrappers' => 
    array (
      0 => 'Stancl\\Tenancy\\Bootstrappers\\DatabaseTenancyBootstrapper',
      1 => 'Stancl\\Tenancy\\Bootstrappers\\CacheTenancyBootstrapper',
      2 => 'Stancl\\Tenancy\\Bootstrappers\\FilesystemTenancyBootstrapper',
      3 => 'Stancl\\Tenancy\\Bootstrappers\\QueueTenancyBootstrapper',
    ),
    'database' => 
    array (
      'central_connection' => 'pgsql',
      'template_tenant_connection' => NULL,
      'prefix' => 'tenant',
      'suffix' => '',
      'managers' => 
      array (
        'sqlite' => 'Stancl\\Tenancy\\TenantDatabaseManagers\\SQLiteDatabaseManager',
        'mysql' => 'Stancl\\Tenancy\\TenantDatabaseManagers\\MySQLDatabaseManager',
        'pgsql' => 'Stancl\\Tenancy\\TenantDatabaseManagers\\PostgreSQLDatabaseManager',
      ),
    ),
    'cache' => 
    array (
      'tag_base' => 'tenant',
    ),
    'filesystem' => 
    array (
      'suffix_base' => 'tenant',
      'disks' => 
      array (
        0 => 'local',
        1 => 'public',
      ),
      'root_override' => 
      array (
        'local' => '%storage_path%/app/',
        'public' => '%storage_path%/app/public/',
      ),
      'suffix_storage_path' => true,
      'asset_helper_tenancy' => true,
    ),
    'redis' => 
    array (
      'prefix_base' => 'tenant',
      'prefixed_connections' => 
      array (
      ),
    ),
    'features' => 
    array (
    ),
    'routes' => true,
    'migration_parameters' => 
    array (
      '--force' => true,
      '--path' => 
      array (
        0 => 'D:\\DevCenter\\abuilds\\alumate\\database\\migrations/tenant',
      ),
      '--realpath' => true,
    ),
    'seeder_parameters' => 
    array (
      '--class' => 'DatabaseSeeder',
    ),
    'production' => 
    array (
      'database_partitioning' => 
      array (
        'enabled' => true,
        'auto_create_databases' => true,
        'database_prefix' => 'tenant_',
        'max_connections_per_tenant' => 10,
        'connection_pooling' => true,
      ),
      'performance' => 
      array (
        'cache_tenant_configs' => true,
        'preload_tenant_data' => false,
        'optimize_queries' => true,
        'connection_timeout' => 30,
      ),
      'monitoring' => 
      array (
        'log_tenant_queries' => false,
        'track_performance_metrics' => true,
        'alert_on_failures' => true,
        'health_check_interval' => 60,
      ),
      'security' => 
      array (
        'strict_domain_isolation' => true,
        'encrypt_tenant_data' => false,
        'audit_tenant_actions' => true,
        'rate_limit_tenant_requests' => true,
      ),
    ),
  ),
  'vite' => 
  array (
    'dev_server' => 
    array (
      'enabled' => true,
      'url' => 'http://127.0.0.1:5173',
      'ping_timeout' => 1,
    ),
    'build_path' => 'build',
    'manifest' => 'build/manifest.json',
    'hot_file' => 'public/hot',
  ),
  'inertia' => 
  array (
    'ssr' => 
    array (
      'enabled' => true,
      'url' => 'http://127.0.0.1:13714',
      'ensure_bundle_exists' => true,
    ),
    'ensure_pages_exist' => false,
    'page_paths' => 
    array (
      0 => 'D:\\DevCenter\\abuilds\\alumate\\resources\\js/Pages',
    ),
    'page_extensions' => 
    array (
      0 => 'js',
      1 => 'jsx',
      2 => 'svelte',
      3 => 'ts',
      4 => 'tsx',
      5 => 'vue',
    ),
    'use_script_element_for_initial_page' => false,
    'testing' => 
    array (
      'ensure_pages_exist' => true,
      'page_paths' => 
      array (
        0 => 'D:\\DevCenter\\abuilds\\alumate\\resources\\js/Pages',
      ),
      'page_extensions' => 
      array (
        0 => 'js',
        1 => 'jsx',
        2 => 'svelte',
        3 => 'ts',
        4 => 'tsx',
        5 => 'vue',
      ),
    ),
    'history' => 
    array (
      'encrypt' => false,
    ),
  ),
  'cashier' => 
  array (
    'key' => NULL,
    'secret' => NULL,
    'path' => 'stripe',
    'webhook' => 
    array (
      'secret' => NULL,
      'tolerance' => 300,
      'events' => 
      array (
        0 => 'customer.subscription.created',
        1 => 'customer.subscription.updated',
        2 => 'customer.subscription.deleted',
        3 => 'customer.updated',
        4 => 'customer.deleted',
        5 => 'payment_method.automatically_updated',
        6 => 'invoice.payment_action_required',
        7 => 'invoice.payment_succeeded',
      ),
    ),
    'currency' => 'usd',
    'currency_locale' => 'en',
    'payment_notification' => NULL,
    'invoices' => 
    array (
      'renderer' => 'Laravel\\Cashier\\Invoices\\DompdfInvoiceRenderer',
      'options' => 
      array (
        'paper' => 'letter',
        'remote_enabled' => false,
      ),
    ),
    'logger' => NULL,
  ),
  'mcp' => 
  array (
    'redirect_domains' => 
    array (
      0 => '*',
    ),
  ),
  'octane' => 
  array (
    'server' => 'roadrunner',
    'https' => false,
    'listeners' => 
    array (
      'Laravel\\Octane\\Events\\WorkerStarting' => 
      array (
        0 => 'Laravel\\Octane\\Listeners\\EnsureUploadedFilesAreValid',
        1 => 'Laravel\\Octane\\Listeners\\EnsureUploadedFilesCanBeMoved',
      ),
      'Laravel\\Octane\\Events\\RequestReceived' => 
      array (
        0 => 'Laravel\\Octane\\Listeners\\CreateConfigurationSandbox',
        1 => 'Laravel\\Octane\\Listeners\\CreateUrlGeneratorSandbox',
        2 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToAuthorizationGate',
        3 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToBroadcastManager',
        4 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToDatabaseManager',
        5 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToDatabaseSessionHandler',
        6 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToFilesystemManager',
        7 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToHttpKernel',
        8 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToLogManager',
        9 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToMailManager',
        10 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToNotificationChannelManager',
        11 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToPipelineHub',
        12 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToCacheManager',
        13 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToSessionManager',
        14 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToQueueManager',
        15 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToRouter',
        16 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToValidationFactory',
        17 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToViewFactory',
        18 => 'Laravel\\Octane\\Listeners\\FlushDatabaseRecordModificationState',
        19 => 'Laravel\\Octane\\Listeners\\FlushDatabaseQueryLog',
        20 => 'Laravel\\Octane\\Listeners\\RefreshQueryDurationHandling',
        21 => 'Laravel\\Octane\\Listeners\\FlushArrayCache',
        22 => 'Laravel\\Octane\\Listeners\\FlushLogContext',
        23 => 'Laravel\\Octane\\Listeners\\FlushMonologState',
        24 => 'Laravel\\Octane\\Listeners\\FlushStrCache',
        25 => 'Laravel\\Octane\\Listeners\\FlushTranslatorCache',
        26 => 'Laravel\\Octane\\Listeners\\FlushVite',
        27 => 'Laravel\\Octane\\Listeners\\PrepareInertiaForNextOperation',
        28 => 'Laravel\\Octane\\Listeners\\PrepareLivewireForNextOperation',
        29 => 'Laravel\\Octane\\Listeners\\PrepareScoutForNextOperation',
        30 => 'Laravel\\Octane\\Listeners\\PrepareSocialiteForNextOperation',
        31 => 'Laravel\\Octane\\Listeners\\FlushLocaleState',
        32 => 'Laravel\\Octane\\Listeners\\FlushQueuedCookies',
        33 => 'Laravel\\Octane\\Listeners\\FlushSessionState',
        34 => 'Laravel\\Octane\\Listeners\\FlushAuthenticationState',
        35 => 'Laravel\\Octane\\Listeners\\EnforceRequestScheme',
        36 => 'Laravel\\Octane\\Listeners\\EnsureRequestServerPortMatchesScheme',
        37 => 'Laravel\\Octane\\Listeners\\GiveNewRequestInstanceToApplication',
        38 => 'Laravel\\Octane\\Listeners\\GiveNewRequestInstanceToPaginator',
      ),
      'Laravel\\Octane\\Events\\RequestHandled' => 
      array (
      ),
      'Laravel\\Octane\\Events\\RequestTerminated' => 
      array (
      ),
      'Laravel\\Octane\\Events\\TaskReceived' => 
      array (
        0 => 'Laravel\\Octane\\Listeners\\CreateConfigurationSandbox',
        1 => 'Laravel\\Octane\\Listeners\\CreateUrlGeneratorSandbox',
        2 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToAuthorizationGate',
        3 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToBroadcastManager',
        4 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToDatabaseManager',
        5 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToDatabaseSessionHandler',
        6 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToFilesystemManager',
        7 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToHttpKernel',
        8 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToLogManager',
        9 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToMailManager',
        10 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToNotificationChannelManager',
        11 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToPipelineHub',
        12 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToCacheManager',
        13 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToSessionManager',
        14 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToQueueManager',
        15 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToRouter',
        16 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToValidationFactory',
        17 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToViewFactory',
        18 => 'Laravel\\Octane\\Listeners\\FlushDatabaseRecordModificationState',
        19 => 'Laravel\\Octane\\Listeners\\FlushDatabaseQueryLog',
        20 => 'Laravel\\Octane\\Listeners\\RefreshQueryDurationHandling',
        21 => 'Laravel\\Octane\\Listeners\\FlushArrayCache',
        22 => 'Laravel\\Octane\\Listeners\\FlushLogContext',
        23 => 'Laravel\\Octane\\Listeners\\FlushMonologState',
        24 => 'Laravel\\Octane\\Listeners\\FlushStrCache',
        25 => 'Laravel\\Octane\\Listeners\\FlushTranslatorCache',
        26 => 'Laravel\\Octane\\Listeners\\FlushVite',
        27 => 'Laravel\\Octane\\Listeners\\PrepareInertiaForNextOperation',
        28 => 'Laravel\\Octane\\Listeners\\PrepareLivewireForNextOperation',
        29 => 'Laravel\\Octane\\Listeners\\PrepareScoutForNextOperation',
        30 => 'Laravel\\Octane\\Listeners\\PrepareSocialiteForNextOperation',
      ),
      'Laravel\\Octane\\Events\\TaskTerminated' => 
      array (
      ),
      'Laravel\\Octane\\Events\\TickReceived' => 
      array (
        0 => 'Laravel\\Octane\\Listeners\\CreateConfigurationSandbox',
        1 => 'Laravel\\Octane\\Listeners\\CreateUrlGeneratorSandbox',
        2 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToAuthorizationGate',
        3 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToBroadcastManager',
        4 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToDatabaseManager',
        5 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToDatabaseSessionHandler',
        6 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToFilesystemManager',
        7 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToHttpKernel',
        8 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToLogManager',
        9 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToMailManager',
        10 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToNotificationChannelManager',
        11 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToPipelineHub',
        12 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToCacheManager',
        13 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToSessionManager',
        14 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToQueueManager',
        15 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToRouter',
        16 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToValidationFactory',
        17 => 'Laravel\\Octane\\Listeners\\GiveNewApplicationInstanceToViewFactory',
        18 => 'Laravel\\Octane\\Listeners\\FlushDatabaseRecordModificationState',
        19 => 'Laravel\\Octane\\Listeners\\FlushDatabaseQueryLog',
        20 => 'Laravel\\Octane\\Listeners\\RefreshQueryDurationHandling',
        21 => 'Laravel\\Octane\\Listeners\\FlushArrayCache',
        22 => 'Laravel\\Octane\\Listeners\\FlushLogContext',
        23 => 'Laravel\\Octane\\Listeners\\FlushMonologState',
        24 => 'Laravel\\Octane\\Listeners\\FlushStrCache',
        25 => 'Laravel\\Octane\\Listeners\\FlushTranslatorCache',
        26 => 'Laravel\\Octane\\Listeners\\FlushVite',
        27 => 'Laravel\\Octane\\Listeners\\PrepareInertiaForNextOperation',
        28 => 'Laravel\\Octane\\Listeners\\PrepareLivewireForNextOperation',
        29 => 'Laravel\\Octane\\Listeners\\PrepareScoutForNextOperation',
        30 => 'Laravel\\Octane\\Listeners\\PrepareSocialiteForNextOperation',
      ),
      'Laravel\\Octane\\Events\\TickTerminated' => 
      array (
      ),
      'Laravel\\Octane\\Contracts\\OperationTerminated' => 
      array (
        0 => 'Laravel\\Octane\\Listeners\\FlushOnce',
        1 => 'Laravel\\Octane\\Listeners\\FlushTemporaryContainerInstances',
      ),
      'Laravel\\Octane\\Events\\WorkerErrorOccurred' => 
      array (
        0 => 'Laravel\\Octane\\Listeners\\ReportException',
        1 => 'Laravel\\Octane\\Listeners\\StopWorkerIfNecessary',
      ),
      'Laravel\\Octane\\Events\\WorkerStopping' => 
      array (
        0 => 'Laravel\\Octane\\Listeners\\CloseMonologHandlers',
      ),
    ),
    'warm' => 
    array (
      0 => 'auth',
      1 => 'cache',
      2 => 'cache.store',
      3 => 'config',
      4 => 'cookie',
      5 => 'db',
      6 => 'db.factory',
      7 => 'db.transactions',
      8 => 'encrypter',
      9 => 'files',
      10 => 'hash',
      11 => 'log',
      12 => 'router',
      13 => 'routes',
      14 => 'session',
      15 => 'session.store',
      16 => 'translator',
      17 => 'url',
      18 => 'view',
    ),
    'flush' => 
    array (
    ),
    'tables' => 
    array (
      'example:1000' => 
      array (
        'name' => 'string:1000',
        'votes' => 'int',
      ),
    ),
    'cache' => 
    array (
      'rows' => 1000,
      'bytes' => 10000,
    ),
    'watch' => 
    array (
      0 => 'app',
      1 => 'bootstrap',
      2 => 'config/**/*.php',
      3 => 'database/**/*.php',
      4 => 'public/**/*.php',
      5 => 'resources/**/*.php',
      6 => 'routes',
      7 => 'composer.lock',
      8 => '.env',
    ),
    'garbage' => 50,
    'max_execution_time' => 30,
  ),
  'sanctum' => 
  array (
    'stateful' => 
    array (
      0 => 'localhost',
      1 => 'localhost:3000',
      2 => '127.0.0.1',
      3 => '127.0.0.1:8000',
      4 => '::1',
      5 => '127.0.0.1:8080',
    ),
    'guard' => 
    array (
      0 => 'web',
    ),
    'expiration' => NULL,
    'token_prefix' => '',
    'middleware' => 
    array (
      'authenticate_session' => 'Laravel\\Sanctum\\Http\\Middleware\\AuthenticateSession',
      'encrypt_cookies' => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
      'validate_csrf_token' => 'Illuminate\\Foundation\\Http\\Middleware\\ValidateCsrfToken',
    ),
  ),
  'excel' => 
  array (
    'exports' => 
    array (
      'chunk_size' => 1000,
      'pre_calculate_formulas' => false,
      'strict_null_comparison' => false,
      'csv' => 
      array (
        'delimiter' => ',',
        'enclosure' => '"',
        'line_ending' => '
',
        'use_bom' => false,
        'include_separator_line' => false,
        'excel_compatibility' => false,
        'output_encoding' => '',
        'test_auto_detect' => true,
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
    ),
    'imports' => 
    array (
      'read_only' => true,
      'ignore_empty' => false,
      'heading_row' => 
      array (
        'formatter' => 'slug',
      ),
      'csv' => 
      array (
        'delimiter' => NULL,
        'enclosure' => '"',
        'escape_character' => '\\',
        'contiguous' => false,
        'input_encoding' => 'guess',
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
      'cells' => 
      array (
        'middleware' => 
        array (
        ),
      ),
    ),
    'extension_detector' => 
    array (
      'xlsx' => 'Xlsx',
      'xlsm' => 'Xlsx',
      'xltx' => 'Xlsx',
      'xltm' => 'Xlsx',
      'xls' => 'Xls',
      'xlt' => 'Xls',
      'ods' => 'Ods',
      'ots' => 'Ods',
      'slk' => 'Slk',
      'xml' => 'Xml',
      'gnumeric' => 'Gnumeric',
      'htm' => 'Html',
      'html' => 'Html',
      'csv' => 'Csv',
      'tsv' => 'Csv',
      'pdf' => 'Dompdf',
    ),
    'value_binder' => 
    array (
      'default' => 'Maatwebsite\\Excel\\DefaultValueBinder',
    ),
    'cache' => 
    array (
      'driver' => 'memory',
      'batch' => 
      array (
        'memory_limit' => 60000,
      ),
      'illuminate' => 
      array (
        'store' => NULL,
      ),
      'default_ttl' => 10800,
    ),
    'transactions' => 
    array (
      'handler' => 'db',
      'db' => 
      array (
        'connection' => NULL,
      ),
    ),
    'temporary_files' => 
    array (
      'local_path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\framework/cache/laravel-excel',
      'local_permissions' => 
      array (
      ),
      'remote_disk' => NULL,
      'remote_prefix' => NULL,
      'force_resync_remote' => NULL,
    ),
  ),
  'tinker' => 
  array (
    'commands' => 
    array (
    ),
    'alias' => 
    array (
    ),
    'dont_alias' => 
    array (
      0 => 'App\\Nova',
    ),
  ),
);
