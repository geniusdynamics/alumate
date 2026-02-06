<?php

/**
 * Health Checks Configuration
 *
 * Comprehensive health check definitions for the Alumate application.
 * These checks are used by the monitoring system to verify system health.
 *
 * @package Alumate\Infrastructure\Production
 * @version 2.0.0
 */

return [
    /*
    |--------------------------------------------------------------------------
    | General Settings
    |--------------------------------------------------------------------------
    */

    'enabled' => env('HEALTH_CHECKS_ENABLED', true),

    'run_on_startup' => true,

    'cache_results' => true,

    'cache_ttl' => 30,

    /*
    |--------------------------------------------------------------------------
    | Health Check Groups
    |--------------------------------------------------------------------------
    |
    | Health checks are organized into groups for better management.
    |
    */

    'groups' => [
        'critical' => [
            'name' => 'Critical Checks',
            'description' => 'Essential services that must be operational',
            'run_order' => 1,
            'fail_fast' => true,
        ],
        'important' => [
            'name' => 'Important Checks',
            'description' => 'Services that should be operational',
            'run_order' => 2,
            'fail_fast' => false,
        ],
        'optional' => [
            'name' => 'Optional Checks',
            'description' => 'Additional services for monitoring',
            'run_order' => 3,
            'fail_fast' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Definitions
    |--------------------------------------------------------------------------
    |
    | Individual health check configurations.
    |
    */

    'checks' => [
        /*
        |--------------------------------------------------------------------------
        | Application Health
        |--------------------------------------------------------------------------
        */

        'application' => [
            'group' => 'critical',
            'enabled' => true,
            'timeout' => 10,
            'critical' => true,

            'checks' => [
                'php_version' => [
                    'enabled' => true,
                    'required_version' => '8.3.0',
                    'description' => 'PHP version requirement',
                ],
                'php_extensions' => [
                    'enabled' => true,
                    'required_extensions' => [
                        'pdo',
                        'pdo_pgsql',
                        'redis',
                        'openssl',
                        'mbstring',
                        ' tokenizer',
                        'xml',
                        'ctype',
                        'json',
                        'bcmath',
                        'fileinfo',
                        'gd',
                    ],
                    'description' => 'Required PHP extensions',
                ],
                'composer_autoload' => [
                    'enabled' => true,
                    'description' => 'Composer autoloader exists',
                ],
                'bootstrap_cache' => [
                    'enabled' => true,
                    'description' => 'Bootstrap cache is writable',
                ],
                'environment' => [
                    'enabled' => true,
                    'required_env' => 'production',
                    'description' => 'Environment is set correctly',
                ],
                'app_key' => [
                    'enabled' => true,
                    'description' => 'Application key is set',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Database Health
        |--------------------------------------------------------------------------
        */

        'database' => [
            'group' => 'critical',
            'enabled' => true,
            'timeout' => 10,
            'critical' => true,

            'connections' => [
                'default' => [
                    'enabled' => true,
                    'driver' => 'pgsql',
                    'required' => true,
                    'checks' => [
                        'connection' => [
                            'enabled' => true,
                            'description' => 'Database connection successful',
                        ],
                        'migrations' => [
                            'enabled' => true,
                            'description' => 'All migrations have run',
                        ],
                        'connections_count' => [
                            'enabled' => true,
                            'max_connections' => 200,
                            'description' => 'Connection pool not exhausted',
                        ],
                        'query_performance' => [
                            'enabled' => true,
                            'max_query_time_ms' => 5000,
                            'description' => 'Database queries perform within threshold',
                        ],
                        'locks' => [
                            'enabled' => true,
                            'description' => 'No long-running locks detected',
                        ],
                        'table_exists' => [
                            'enabled' => true,
                            'required_tables' => [
                                'users',
                                'tenants',
                                'components',
                                'migrations',
                            ],
                            'description' => 'Required tables exist',
                        ],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Redis Health
        |--------------------------------------------------------------------------
        */

        'redis' => [
            'group' => 'critical',
            'enabled' => true,
            'timeout' => 5,
            'critical' => true,

            'connections' => [
                'default' => [
                    'enabled' => true,
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'port' => env('REDIS_PORT', 6379),
                    'password' => env('REDIS_PASSWORD'),
                    'database' => env('REDIS_DB', 0),
                    'checks' => [
                        'connection' => [
                            'enabled' => true,
                            'description' => 'Redis connection successful',
                        ],
                        'ping' => [
                            'enabled' => true,
                            'description' => 'Redis PING successful',
                        ],
                        'memory_usage' => [
                            'enabled' => true,
                            'max_memory_percent' => 80,
                            'description' => 'Memory usage within threshold',
                        ],
                        'connected_clients' => [
                            'enabled' => true,
                            'max_clients' => 100,
                            'description' => 'Connection count within limits',
                        ],
                        'hit_rate' => [
                            'enabled' => true,
                            'min_hit_rate' => 80,
                            'description' => 'Cache hit rate acceptable',
                        ],
                        'evictions' => [
                            'enabled' => true,
                            'max_evictions' => 0,
                            'description' => 'No recent key evictions',
                        ],
                    ],
                ],
                'cache' => [
                    'enabled' => env('REDIS_CACHE_DB', 1) !== (int) env('REDIS_DB', 0),
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'port' => (int) env('REDIS_PORT', 6379),
                    'password' => env('REDIS_PASSWORD'),
                    'database' => (int) env('REDIS_CACHE_DB', 1),
                    'checks' => [
                        'connection' => [
                            'enabled' => true,
                            'description' => 'Cache Redis connection successful',
                        ],
                    ],
                ],
                'session' => [
                    'enabled' => (int) env('REDIS_SESSION_DB', 2) !== (int) env('REDIS_DB', 0),
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'port' => (int) env('REDIS_PORT', 6379),
                    'password' => env('REDIS_PASSWORD'),
                    'database' => (int) env('REDIS_SESSION_DB', 2),
                    'checks' => [
                        'connection' => [
                            'enabled' => true,
                            'description' => 'Session Redis connection successful',
                        ],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Queue Health
        |--------------------------------------------------------------------------
        */

        'queue' => [
            'group' => 'important',
            'enabled' => true,
            'timeout' => 10,
            'critical' => false,

            'connections' => [
                'redis' => [
                    'enabled' => true,
                    'queue_prefix' => env('REDIS_QUEUE_PREFIX', 'queues:'),
                    'checks' => [
                        'connection' => [
                            'enabled' => true,
                            'description' => 'Queue connection successful',
                        ],
                        'workers_running' => [
                            'enabled' => true,
                            'min_workers' => 1,
                            'description' => 'Queue workers are running',
                        ],
                        'backlog' => [
                            'enabled' => true,
                            'max_backlog' => 100,
                            'description' => 'Queue backlog within threshold',
                        ],
                        'failed_jobs' => [
                            'enabled' => true,
                            'max_failed' => 10,
                            'description' => 'Failed jobs count within threshold',
                        ],
                        'stale_jobs' => [
                            'enabled' => true,
                            'max_stale_seconds' => 600,
                            'description' => 'No stale jobs in queue',
                        ],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Cache Health
        |--------------------------------------------------------------------------
        */

        'cache' => [
            'group' => 'important',
            'enabled' => true,
            'timeout' => 5,
            'critical' => false,

            'driver' => env('CACHE_DRIVER', 'redis'),

            'checks' => [
                'connection' => [
                    'enabled' => true,
                    'description' => 'Cache connection successful',
                ],
                'write_test' => [
                    'enabled' => true,
                    'description' => 'Cache write operation successful',
                ],
                'read_test' => [
                    'enabled' => true,
                    'description' => 'Cache read operation successful',
                ],
                'hit_rate' => [
                    'enabled' => true,
                    'min_hit_rate' => 80,
                    'description' => 'Cache hit rate acceptable',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Storage Health
        |--------------------------------------------------------------------------
        */

        'storage' => [
            'group' => 'critical',
            'enabled' => true,
            'timeout' => 5,
            'critical' => true,

            'paths' => [
                'storage' => [
                    'path' => storage_path(),
                    'required' => true,
                    'checks' => [
                        'exists' => [
                            'enabled' => true,
                            'description' => 'Storage directory exists',
                        ],
                        'writable' => [
                            'enabled' => true,
                            'description' => 'Storage directory is writable',
                        ],
                        'disk_usage' => [
                            'enabled' => true,
                            'max_usage_percent' => 90,
                            'description' => 'Disk usage within threshold',
                        ],
                    ],
                ],
                'bootstrap_cache' => [
                    'path' => base_path('bootstrap/cache'),
                    'required' => true,
                    'checks' => [
                        'exists' => [
                            'enabled' => true,
                            'description' => 'Bootstrap cache directory exists',
                        ],
                        'writable' => [
                            'enabled' => true,
                            'description' => 'Bootstrap cache is writable',
                        ],
                    ],
                ],
                'logs' => [
                    'path' => storage_path('logs'),
                    'required' => true,
                    'checks' => [
                        'exists' => [
                            'enabled' => true,
                            'description' => 'Logs directory exists',
                        ],
                        'writable' => [
                            'enabled' => true,
                            'description' => 'Logs directory is writable',
                        ],
                    ],
                ],
            ],

            'disk' => [
                'enabled' => true,
                'check_all_disks' => false,
                'min_free_space_mb' => 1000,
                'description' => 'Sufficient disk space available',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Session Health
        |--------------------------------------------------------------------------
        */

        'session' => [
            'group' => 'important',
            'enabled' => true,
            'timeout' => 5,
            'critical' => false,

            'driver' => env('SESSION_DRIVER', 'redis'),

            'checks' => [
                'connection' => [
                    'enabled' => true,
                    'description' => 'Session connection successful',
                ],
                'write_test' => [
                    'enabled' => true,
                    'description' => 'Session write operation successful',
                ],
                'gc_test' => [
                    'enabled' => true,
                    'description' => 'Session garbage collection works',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Mail Health
        |--------------------------------------------------------------------------
        */

        'mail' => [
            'group' => 'optional',
            'enabled' => env('MAIL_ENABLED', true),
            'timeout' => 10,
            'critical' => false,

            'driver' => env('MAIL_MAILER', 'smtp'),

            'checks' => [
                'connection' => [
                    'enabled' => true,
                    'description' => 'Mail server connection successful',
                ],
                'smtp_auth' => [
                    'enabled' => true,
                    'description' => 'SMTP authentication successful',
                ],
                'test_email' => [
                    'enabled' => false,
                    'recipient' => env('HEALTH_CHECK_EMAIL_RECIPIENT'),
                    'description' => 'Test email sent successfully',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | External Services Health
        |--------------------------------------------------------------------------
        */

        'external_services' => [
            'group' => 'optional',
            'enabled' => true,
            'timeout' => 30,
            'critical' => false,

            'services' => [
                'sentry' => [
                    'enabled' => env('SENTRY_ENABLED', false),
                    'url' => env('SENTRY_DSN'),
                    'checks' => [
                        'connection' => [
                            'enabled' => true,
                            'description' => 'Sentry connection successful',
                        ],
                    ],
                ],
                'datadog' => [
                    'enabled' => env('DATADOG_ENABLED', false),
                    'agent_host' => env('DD_AGENT_HOST', 'localhost'),
                    'checks' => [
                        'agent_connection' => [
                            'enabled' => true,
                            'description' => 'Datadog agent connection successful',
                        ],
                    ],
                ],
                'newrelic' => [
                    'enabled' => env('NEWRELIC_ENABLED', false),
                    'checks' => [
                        'agent' => [
                            'enabled' => true,
                            'description' => 'New Relic agent loaded',
                        ],
                    ],
                ],
                'aws' => [
                    'enabled' => false,
                    'region' => env('AWS_DEFAULT_REGION'),
                    'checks' => [
                        'credentials' => [
                            'enabled' => true,
                            'description' => 'AWS credentials valid',
                        ],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Security Health
        |--------------------------------------------------------------------------
        */

        'security' => [
            'group' => 'important',
            'enabled' => true,
            'timeout' => 10,
            'critical' => false,

            'checks' => [
                'ssl_certificate' => [
                    'enabled' => true,
                    'check_expiry' => true,
                    'days_before_expiry_warning' => 30,
                    'description' => 'SSL certificate valid and not expiring soon',
                ],
                'file_permissions' => [
                    'enabled' => true,
                    'description' => 'File permissions are secure',
                ],
                'debug_mode' => [
                    'enabled' => true,
                    'description' => 'Debug mode is disabled',
                ],
                'https_enforced' => [
                    'enabled' => true,
                    'description' => 'HTTPS is enforced in production',
                ],
                'cors_configured' => [
                    'enabled' => true,
                    'description' => 'CORS is properly configured',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Queue Workers Health
        |--------------------------------------------------------------------------
        */

        'queue_workers' => [
            'group' => 'important',
            'enabled' => true,
            'timeout' => 5,
            'critical' => false,

            'checks' => [
                'horizon_running' => [
                    'enabled' => true,
                    'description' => 'Laravel Horizon is running',
                ],
                'supervisor_processes' => [
                    'enabled' => true,
                    'min_processes' => 1,
                    'description' => 'Supervisor processes are running',
                ],
                'last_job_at' => [
                    'enabled' => true,
                    'max_minutes_since_last_job' => 5,
                    'description' => 'Recent jobs processed',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Tenant Health
        |--------------------------------------------------------------------------
        */

        'tenancy' => [
            'group' => 'important',
            'enabled' => env('TENANCY_ENABLED', true),
            'timeout' => 10,
            'critical' => false,

            'checks' => [
                'tenant_identifiable' => [
                    'enabled' => true,
                    'description' => 'Tenant can be identified from request',
                ],
                'database_connection' => [
                    'enabled' => true,
                    'description' => 'Tenant database connection works',
                ],
                'cache_isolation' => [
                    'enabled' => true,
                    'description' => 'Tenant cache isolation works',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Endpoints
    |--------------------------------------------------------------------------
    |
    | HTTP endpoints for health check access.
    |
    */

    'endpoints' => [
        'basic' => [
            'path' => '/health',
            'description' => 'Basic health check (critical only)',
            'authenticated' => false,
            'cache_enabled' => true,
            'cache_ttl' => 30,
        ],
        'detailed' => [
            'path' => '/health/detailed',
            'description' => 'Detailed health check (all checks)',
            'authenticated' => false,
            'cache_enabled' => true,
            'cache_ttl' => 60,
        ],
        'ready' => [
            'path' => '/ready',
            'description' => 'Readiness probe (for Kubernetes)',
            'authenticated' => false,
            'checks' => ['application', 'database', 'redis', 'storage'],
        ],
        'live' => [
            'path' => '/live',
            'description' => 'Liveness probe (for Kubernetes)',
            'authenticated' => false,
            'checks' => ['application'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Configuration
    |--------------------------------------------------------------------------
    */

    'response' => [
        'include_details' => true,
        'include_metrics' => true,
        'include_checks' => true,
        'format' => 'json',
        'pretty_print' => false,
        'version_info' => true,
        'timestamp' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled Health Checks
    |--------------------------------------------------------------------------
    */

    'scheduled' => [
        'enabled' => true,
        'interval' => 60,
        'channel' => 'log',
        'alert_on_failure' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Health Check Classes
    |--------------------------------------------------------------------------
    |
    | Register custom health check classes here.
    |
    */

    'custom' => [
        // Example:
        // \App\HealthChecks\CustomCheck::class,
    ],
];
