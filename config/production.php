<?php

/**
 * Production Environment Configuration
 * 
 * This file contains production-specific configuration settings that override
 * or supplement the default configuration for production deployments.
 * 
 * @package Alumate\Config
 * @version 2.0.0
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | Application Settings
    |--------------------------------------------------------------------------
    |
    | Production-specific application configuration including debug mode,
    | logging levels, and environment detection.
    |
    */

    'app' => [
        'env' => env('APP_ENV', 'production'),
        'debug' => env('APP_DEBUG', false),
        'debug_bar' => false,
        'telescope' => false,
        'name' => env('APP_NAME', 'Alumni Platform'),
        'url' => env('APP_URL', 'https://alumni-platform.com'),
        'timezone' => env('APP_TIMEZONE', 'UTC'),
        'locale' => env('APP_LOCALE', 'en'),
        'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
        'key' => env('APP_KEY'),
        'cipher' => 'AES-256-CBC',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Production logging settings with optimized performance and retention.
    |
    */

    'logging' => [
        'channel' => env('LOG_CHANNEL', 'stack'),
        'level' => env('LOG_LEVEL', 'warning'),
        'max_files' => env('LOG_MAX_FILES', 30),
        'deprecations_channel' => null,
        'slack_webhook_url' => env('LOG_SLACK_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration (PostgreSQL)
    |--------------------------------------------------------------------------
    |
    | Production database settings optimized for performance and security.
    |
    */

    'database' => [
        'default' => env('DB_CONNECTION', 'pgsql'),
        
        'connections' => [
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '5432'),
                'database' => env('DB_DATABASE', 'alumni_platform'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => env('DB_SSLMODE', 'require'),
                'options' => [
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::PGSQL_ATTR_DISABLE_PREPARES => false,
                ],
            ],
        ],
        
        'redis' => [
            'client' => env('REDIS_CLIENT', 'phpredis'),
            'prefix' => env('REDIS_PREFIX', 'alumate_'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Production cache settings with Redis optimization for high performance.
    |
    */

    'cache' => [
        'driver' => env('CACHE_DRIVER', 'redis'),
        'store' => env('CACHE_STORE', 'redis'),
        'prefix' => env('CACHE_PREFIX', 'alumate_cache_'),
        
        'stores' => [
            'redis' => [
                'driver' => 'redis',
                'connection' => 'cache',
                'lock_connection' => 'default',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    |
    | Production session settings with Redis storage for performance.
    |
    */

    'session' => [
        'driver' => env('SESSION_DRIVER', 'redis'),
        'lifetime' => (int) env('SESSION_LIFETIME', 120),
        'expire_on_close' => false,
        'encrypt' => env('SESSION_ENCRYPT', true),
        'files' => storage_path('framework/sessions'),
        'connection' => 'session',
        'table' => 'sessions',
        'store' => env('SESSION_STORE'),
        'lottery' => [2, 100],
        'cookie' => 'alumate_session',
        'path' => '/',
        'domain' => env('SESSION_DOMAIN'),
        'secure' => true,
        'http_only' => true,
        'same_site' => 'lax',
        'partitioned' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Production queue settings with Redis for reliable job processing.
    |
    */

    'queue' => [
        'default' => env('QUEUE_CONNECTION', 'redis'),
        'failed' => [
            'driver' => 'database-uuids',
            'database' => env('DB_CONNECTION', 'pgsql'),
            'table' => 'failed_jobs',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Configuration
    |--------------------------------------------------------------------------
    |
    | Production Redis settings optimized for high availability.
    |
    */

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        
        'options' => [
            'cluster' => env('REDIS_CLUSTER', false),
            'prefix' => env('REDIS_PREFIX', 'alumate_'),
            'persistent' => true,
        ],
        
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        
        'cache' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
        
        'session' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_SESSION_DB', '2'),
        ],
        
        'queue' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_QUEUE_DB', '3'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail Configuration
    |--------------------------------------------------------------------------
    |
    | Production mail settings with SMTP optimization.
    |
    */

    'mail' => [
        'default' => env('MAIL_MAILER', 'smtp'),
        
        'mailers' => [
            'smtp' => [
                'transport' => 'smtp',
                'host' => env('MAIL_HOST', 'smtp.example.com'),
                'port' => env('MAIL_PORT', 587),
                'username' => env('MAIL_USERNAME'),
                'password' => env('MAIL_PASSWORD'),
                'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'timeout' => null,
                'local_domain' => env('MAIL_EHLO_DOMAIN'),
            ],
            
            'failover' => [
                'transport' => 'failover',
                'mailers' => [
                    'smtp',
                    'log',
                ],
            ],
        ],
        
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@alumni-platform.com'),
            'name' => env('MAIL_FROM_NAME', 'Alumni Platform'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Production file storage settings with S3 support.
    |
    */

    'filesystems' => [
        'default' => env('FILESYSTEM_DISK', 's3'),
        
        'disks' => [
            'local' => [
                'driver' => 'local',
                'root' => storage_path('app'),
                'url' => env('APP_URL').'/storage',
                'visibility' => 'private',
            ],
            
            's3' => [
                'driver' => 's3',
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
                'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'bucket' => env('AWS_BUCKET'),
                'url' => env('AWS_URL'),
                'endpoint' => env('AWS_ENDPOINT'),
                'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
                'visibility' => 'public-read',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Configuration
    |--------------------------------------------------------------------------
    |
    | Production multi-tenancy settings for tenant isolation.
    |
    */

    'tenancy' => [
        'enabled' => env('TENANCY_ENABLED', true),
        'database_auto_delete_enabled' => false,
        'database_auto_cleanup' => false,
        'isolate_data' => true,
        'default_domain' => 'alumni-platform.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Production security settings including rate limiting and 2FA.
    |
    */

    'security' => [
        'max_login_attempts' => 5,
        'lockout_duration' => 30,
        'rate_limit_authenticated' => 100,
        'rate_limit_unauthenticated' => 30,
        'session_timeout' => 120,
        'two_factor_enabled' => env('2FA_ENABLED', true),
        'two_factor_force' => env('2FA_FORCE', false),
        
        'password' => [
            'hash' => 'bcrypt',
            'rounds' => (int) env('PASSWORD_ROUNDS', 12),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Observability Configuration
    |--------------------------------------------------------------------------
    |
    | Production monitoring settings for performance tracking.
    |
    */

    'monitoring' => [
        'enabled' => true,
        
        'sentry' => [
            'enabled' => env('SENTRY_ENABLED', false),
            'dsn' => env('SENTRY_LARAVEL_DSN'),
            'environment' => env('APP_ENV'),
            'traces_sample_rate' => 0.1,
            'profiles_sample_rate' => 0.1,
        ],
        
        'new_relic' => [
            'enabled' => env('NEW_RELIC_ENABLED', false),
            'app_name' => env('APP_NAME'),
            'license_key' => env('NEW_RELIC_LICENSE_KEY'),
            'distributed_tracing' => true,
        ],
        
        'datadog' => [
            'enabled' => env('DATADOG_ENABLED', false),
            'service' => env('APP_NAME'),
            'environment' => env('APP_ENV'),
            'agent_host' => env('DD_AGENT_HOST', 'localhost'),
            'trace_agent_port' => 8126,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerting Configuration
    |--------------------------------------------------------------------------
    |
    | Production alerting thresholds and notification channels.
    |
    */

    'alerts' => [
        'enabled' => true,
        
        'channels' => [
            'email' => [
                'enabled' => true,
                'recipients' => explode(',', env('ALERT_EMAIL_RECIPIENTS', 'admin@alumni-platform.com')),
            ],
            'slack' => [
                'enabled' => env('ALERT_SLACK_ENABLED', false),
                'webhook_url' => env('ALERT_SLACK_WEBHOOK_URL'),
            ],
            'pagerduty' => [
                'enabled' => env('ALERT_PAGERDUTY_ENABLED', false),
                'service_key' => env('ALERT_PAGERDUTY_SERVICE_KEY'),
            ],
        ],
        
        'thresholds' => [
            'memory_warning' => 128, // MB
            'memory_critical' => 256, // MB
            'response_warning' => 500, // ms
            'response_critical' => 1000, // ms
            'error_warning' => 1.0, // percentage
            'error_critical' => 5.0, // percentage
        ],
        
        'cooldowns' => [
            'low' => 300,
            'medium' => 1800,
            'high' => 3600,
            'critical' => 7200,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Production performance tuning settings.
    |
    */

    'performance' => [
        'php_fpm' => [
            'pm' => 'dynamic',
            'pm_max_children' => 10,
            'pm_start_servers' => 2,
            'pm_min_spare_servers' => 1,
            'pm_max_spare_servers' => 5,
            'pm_max_requests' => 500,
        ],
        
        'redis' => [
            'pool_size' => 10,
            'pool_timeout' => 10,
        ],
        
        'queue' => [
            'worker_sleep' => 3,
            'worker_max_tries' => 3,
            'worker_timeout' => 90,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | Production analytics settings for the Advanced Analytics System.
    |
    */

    'analytics' => [
        'enabled' => env('FEATURE_ANALYTICS', true),
        
        'cache' => [
            'enabled' => true,
            'ttl' => 300,
            'prefix' => 'analytics:',
        ],
        
        'snapshots' => [
            'enabled' => true,
            'retention_days' => 365,
        ],
        
        'performance' => [
            'query_timeout' => 60,
            'memory_limit' => '512M',
            'chunk_size' => 1000,
        ],
        
        'security' => [
            'data_anonymization' => env('ANALYTICS_ANONYMIZE_DATA', false),
            'rate_limiting' => true,
            'max_requests_per_minute' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Feature toggles for gradual rollout in production.
    |
    */

    'features' => [
        'analytics' => env('FEATURE_ANALYTICS', true),
        'notifications' => env('FEATURE_NOTIFICATIONS', true),
        'search' => env('FEATURE_SEARCH', true),
        'webhooks' => env('FEATURE_WEBHOOKS', true),
        'batch_operations' => env('FEATURE_BATCH_OPERATIONS', true),
        'import_export' => env('FEATURE_IMPORT_EXPORT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention & GDPR Configuration
    |--------------------------------------------------------------------------
    |
    | Production data retention and GDPR compliance settings.
    |
    */

    'data_retention' => [
        'general' => (int) env('DATA_RETENTION_DAYS', 365),
        'analytics' => (int) env('ANALYTICS_DATA_RETENTION_DAYS', 90),
        'logs' => (int) env('LOGS_RETENTION_DAYS', 90),
        'gdpr_mode' => env('GDPR_COMPLIANCE_MODE', true),
        'anonymize_after_days' => (int) env('DATA_ANONYMIZE_AFTER_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Production backup settings for disaster recovery.
    |
    */

    'backup' => [
        'enabled' => env('BACKUP_ENABLED', true),
        'provider' => env('BACKUP_PROVIDER', 'aws_s3'),
        'storage_disk' => env('BACKUP_STORAGE_DISK', 'local'),
        'schedule' => env('BACKUP_SCHEDULE', '0 1 * * *'),
        'compression' => env('BACKUP_COMPRESSION', 'lz4'),
        'encryption' => env('BACKUP_ENCRYPTION', true),
        
        'retention' => [
            'database' => (int) env('BACKUP_RETENTION_DATABASE', 30),
            'files' => (int) env('BACKUP_RETENTION_FILES', 90),
            'config' => (int) env('BACKUP_RETENTION_CONFIG', 365),
        ],
        
        'retention_policy' => [
            'max_age_days' => (int) env('BACKUP_MAX_AGE_DAYS', 90),
            'max_count' => (int) env('BACKUP_MAX_COUNT', 100),
            'keep_critical' => env('BACKUP_KEEP_CRITICAL', true),
        ],
        
        'verification' => [
            'enabled' => env('BACKUP_VERIFICATION_ENABLED', true),
            'checksum' => 'sha256',
            'min_size_bytes' => 1024,
        ],
        
        'notifications' => [
            'enabled' => env('BACKUP_NOTIFICATIONS_ENABLED', true),
            'on_success' => env('BACKUP_NOTIFY_ON_SUCCESS', true),
            'on_failure' => env('BACKUP_NOTIFY_ON_FAILURE', true),
            'channels' => explode(',', env('BACKUP_NOTIFICATION_CHANNELS', 'mail')),
        ],
        
        'aws' => [
            'key' => env('BACKUP_AWS_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
            'secret' => env('BACKUP_AWS_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
            'region' => env('BACKUP_AWS_DEFAULT_REGION', env('AWS_DEFAULT_REGION')),
            'bucket' => env('BACKUP_AWS_BUCKET'),
            'storage_class' => env('BACKUP_AWS_STORAGE_CLASS', 'STANDARD_IA'),
        ],
        
        'gcp' => [
            'project_id' => env('BACKUP_GCP_PROJECT_ID'),
            'bucket' => env('BACKUP_GCS_BUCKET'),
        ],
        
        'azure' => [
            'container' => env('BACKUP_AZURE_CONTAINER', 'backups'),
            'account' => env('BACKUP_AZURE_ACCOUNT'),
            'key' => env('BACKUP_AZURE_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External Services Configuration
    |--------------------------------------------------------------------------
    |
    | Production settings for third-party integrations.
    |
    */

    'external_services' => [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        ],
        
        'facebook' => [
            'client_id' => env('FACEBOOK_CLIENT_ID'),
            'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
            'redirect_uri' => env('FACEBOOK_REDIRECT_URI'),
        ],
        
        'linkedin' => [
            'client_id' => env('LINKEDIN_CLIENT_ID'),
            'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
            'redirect_uri' => env('LINKEDIN_REDIRECT_URI'),
        ],
        
        'matomo' => [
            'url' => env('MATOMO_URL'),
            'site_id' => env('MATOMO_SITE_ID'),
            'token' => env('MATOMO_TOKEN'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    |
    | Production health check and readiness probe settings.
    |
    */

    'health_checks' => [
        'enabled' => true,
        'interval' => (int) env('HEALTH_CHECK_INTERVAL', 30),
        'timeout' => (int) env('HEALTH_CHECK_TIMEOUT', 10),
        'retries' => (int) env('HEALTH_CHECK_RETRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Infrastructure Configuration
    |--------------------------------------------------------------------------
    |
    | Container and infrastructure-specific settings.
    |
    */

    'infrastructure' => [
        'container' => [
            'memory_limit' => env('CONTAINER_MEMORY_LIMIT', '512M'),
            'cpu_limit' => env('CONTAINER_CPU_LIMIT', '1.0'),
        ],
    ],

];
