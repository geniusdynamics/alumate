<?php return array (
  4 => 'concurrency',
  5 => 'cors',
  8 => 'hashing',
  14 => 'view',
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
  ),
  'app' => 
  array (
    'name' => 'Laravel',
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
    'key' => 'base64:me0nvRYENZFAnsMLlYOG7KyTRviogkCkY5lohF4pk9c=',
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
      5 => 'Illuminate\\Cookie\\CookieServiceProvider',
      6 => 'Illuminate\\Database\\DatabaseServiceProvider',
      7 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      8 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      9 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      10 => 'Illuminate\\Hashing\\HashServiceProvider',
      11 => 'Illuminate\\Mail\\MailServiceProvider',
      12 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      13 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      14 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
      15 => 'Illuminate\\Queue\\QueueServiceProvider',
      16 => 'Illuminate\\Redis\\RedisServiceProvider',
      17 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      18 => 'Illuminate\\Session\\SessionServiceProvider',
      19 => 'Illuminate\\Translation\\TranslationServiceProvider',
      20 => 'Illuminate\\Validation\\ValidationServiceProvider',
      21 => 'Illuminate\\View\\ViewServiceProvider',
      22 => 'App\\Providers\\AppServiceProvider',
      23 => 'App\\Providers\\AuthServiceProvider',
      24 => 'App\\Providers\\EventServiceProvider',
      25 => 'App\\Providers\\RouteServiceProvider',
      26 => 'App\\Providers\\TenancyServiceProvider',
      27 => 'App\\Providers\\AppServiceProvider',
      28 => 'App\\Providers\\TenancyServiceProvider',
    ),
    'aliases' => 
    array (
      'App' => 'Illuminate\\Support\\Facades\\App',
      'Arr' => 'Illuminate\\Support\\Arr',
      'Artisan' => 'Illuminate\\Support\\Facades\\Artisan',
      'Auth' => 'Illuminate\\Support\\Facades\\Auth',
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
      'URL' => 'Illuminate\\Support\\Facades\\URL',
      'Uri' => 'Illuminate\\Support\\Uri',
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
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'cluster' => NULL,
          'host' => 'api-mt1.pusherapp.com',
          'port' => 443,
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
    ),
    'prefix' => 'laravel_cache_',
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
        'database' => 'laravel',
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
        'database' => 'laravel',
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
        'database' => 'laravel',
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
        'database' => 'laravel',
        'username' => 'postgres',
        'password' => 'postgres',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
      ),
      'sqlsrv' => 
      array (
        'driver' => 'sqlsrv',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '5433',
        'database' => 'laravel',
        'username' => 'postgres',
        'password' => 'postgres',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
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
        'prefix' => 'laravel_database_',
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
    ),
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
          'warning' => 2000,
          'critical' => 5000,
        ),
        'api_response' => 
        array (
          'warning' => 1000,
          'critical' => 3000,
        ),
        'database_query' => 
        array (
          'warning' => 500,
          'critical' => 2000,
        ),
      ),
      'conversion_thresholds' => 
      array (
        'conversion_rate' => 
        array (
          'min' => 2.0,
          'max' => 15.0,
        ),
        'cta_click_rate' => 
        array (
          'min' => 5.0,
          'max' => 25.0,
        ),
        'bounce_rate' => 
        array (
          'max' => 70.0,
        ),
      ),
      'security' => 
      array (
        'rate_limits' => 
        array (
          'api_requests_per_minute' => 30,
          'page_requests_per_minute' => 60,
          'suspicious_activity_threshold' => 50,
          'ddos_threshold' => 100,
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
          'error' => 900,
          'warning' => 1800,
          'info' => 3600,
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
        'interval' => 300,
        'timeout' => 10,
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
        'performance_metrics' => 30,
        'error_logs' => 90,
        'analytics_events' => 365,
        'alert_logs' => 180,
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
          'policy' => 'default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data: https:; font-src \'self\' data:; connect-src \'self\';',
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
        'release' => NULL,
      ),
      'datadog' => 
      array (
        'enabled' => false,
        'api_key' => NULL,
        'app_key' => NULL,
      ),
      'newrelic' => 
      array (
        'enabled' => false,
        'api_key' => NULL,
        'app_id' => NULL,
      ),
      'pagerduty' => 
      array (
        'enabled' => false,
        'integration_key' => NULL,
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
      ),
    ),
    'links' => 
    array (
      'D:\\DevCenter\\abuilds\\alumate\\public\\storage' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\app/public',
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
        'formatter' => NULL,
        'with' => 
        array (
          'stream' => 'php://stderr',
        ),
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
      'homepage-errors' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/homepage-errors.log',
        'level' => 'debug',
        'days' => 30,
        'replace_placeholders' => true,
      ),
      'homepage-alerts' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/homepage-alerts.log',
        'level' => 'debug',
        'days' => 30,
        'replace_placeholders' => true,
      ),
      'homepage-performance' => 
      array (
        'driver' => 'daily',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/homepage-performance.log',
        'level' => 'debug',
        'days' => 7,
        'replace_placeholders' => true,
      ),
      'browser' => 
      array (
        'driver' => 'single',
        'path' => 'D:\\DevCenter\\abuilds\\alumate\\storage\\logs/browser.log',
        'level' => 'debug',
        'days' => 14,
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
      'name' => 'Laravel',
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
      'alert_email' => 'admin@example.com',
      'slack_webhook' => NULL,
      'pagerduty_key' => NULL,
      'datadog_api_key' => NULL,
      'newrelic_api_key' => NULL,
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
    'cookie' => 'laravel_session',
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
    'tenant_model' => 'App\\Models\\Tenant',
    'id_generator' => 'Stancl\\Tenancy\\UUIDGenerator',
    'domain_model' => 'Stancl\\Tenancy\\Database\\Models\\Domain',
    'central_domains' => 
    array (
      0 => 'localhost',
      1 => '127.0.0.1',
      2 => '127.0.0.1:8080',
      3 => 'localhost:8080',
    ),
    'bootstrappers' => 
    array (
      0 => 'Stancl\\Tenancy\\TenancyBootstrappers\\DatabaseTenancyBootstrapper',
      1 => 'Stancl\\Tenancy\\TenancyBootstrappers\\CacheTenancyBootstrapper',
      2 => 'Stancl\\Tenancy\\TenancyBootstrappers\\FilesystemTenancyBootstrapper',
      3 => 'Stancl\\Tenancy\\TenancyBootstrappers\\QueueTenancyBootstrapper',
      4 => 'Stancl\\Tenancy\\TenancyBootstrappers\\RedisTenancyBootstrapper',
    ),
    'database' => 
    array (
      'central_connection' => 'pgsql',
      'template_tenant_connection' => NULL,
      'prefix' => 'tenant',
      'suffix' => '',
      'managers' => 
      array (
        'pgsql' => 'Stancl\\Tenancy\\TenantDatabaseManagers\\PostgreSQLSchemaManager',
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
      '--path' => 
      array (
        0 => 'D:\\DevCenter\\abuilds\\alumate\\database\\migrations/tenant',
      ),
      '--realpath' => true,
    ),
    'seeder_parameters' => 
    array (
      '--class' => 'DatabaseSeeder',
      '--force' => true,
    ),
    'jobs' => 
    array (
      'create_database' => 'Stancl\\Tenancy\\Jobs\\CreateDatabase',
      'delete_database' => 'Stancl\\Tenancy\\Jobs\\DeleteDatabase',
      'migrate_database' => 'Stancl\\Tenancy\\Jobs\\MigrateDatabase',
      'seed_database' => 'Stancl\\Tenancy\\Jobs\\SeedDatabase',
    ),
  ),
  'vite' => 
  array (
    'dev_server' => 
    array (
      'enabled' => true,
      'url' => 'http://127.0.0.1:5100',
      'ping_timeout' => 1,
    ),
    'build_path' => 'build',
    'manifest' => 'build/manifest.json',
    'hot_file' => 'public/hot',
  ),
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
  'inertia' => 
  array (
    'ssr' => 
    array (
      'enabled' => true,
      'url' => 'http://127.0.0.1:13714',
    ),
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
  'boost' => 
  array (
    'browser_logs_watcher' => true,
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
