<?php

/**
 * Monitoring Configuration
 *
 * This file contains comprehensive configuration for system monitoring, alerting,
 * health checks, and performance thresholds for the Advanced Analytics System.
 *
 * @package Alumate\Config
 * @version 3.0.0
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Monitoring General Settings
    |--------------------------------------------------------------------------
    |
    | Core monitoring settings for the application.
    |
    */

    'enabled' => env('MONITORING_ENABLED', true),

    'environment' => env('APP_ENV', 'production'),

    'version' => '3.0.0',

    /*
    |--------------------------------------------------------------------------
    | Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Alert system configuration including channels, thresholds, and escalation.
    |
    */

    'alerts' => [
        'enabled' => env('MONITORING_ALERTS_ENABLED', true),

        'channels' => [
            'email' => [
                'enabled' => env('MONITORING_ALERT_EMAIL_ENABLED', true),
                'recipients' => array_filter(array_map('trim', explode(',', env('MONITORING_ALERT_EMAIL_RECIPIENTS', 'admin@example.com')))),
            ],
            'slack' => [
                'enabled' => env('MONITORING_SLACK_ENABLED', true),
                'webhook_url' => env('MONITORING_SLACK_WEBHOOK'),
                'channel' => env('MONITORING_SLACK_CHANNEL', '#alerts'),
                'username' => env('MONITORING_SLACK_USERNAME', 'Monitoring Bot'),
                'icon' => env('MONITORING_SLACK_ICON', ':robot_face:'),
            ],
            'pagerduty' => [
                'enabled' => env('PAGERDUTY_ENABLED', false),
                'integration_key' => env('PAGERDUTY_INTEGRATION_KEY'),
                'service_name' => env('PAGERDUTY_SERVICE_NAME', 'Alumni Platform'),
            ],
            'datadog' => [
                'enabled' => env('DATADOG_ENABLED', false),
                'api_key' => env('DATADOG_API_KEY'),
                'app_key' => env('DATADOG_APP_KEY'),
            ],
            'webhook' => [
                'enabled' => env('ALERT_WEBHOOK_ENABLED', false),
                'url' => env('ALERT_WEBHOOK_URL'),
                'method' => 'POST',
                'headers' => [],
            ],
        ],

        'thresholds' => [
            // Queue monitoring
            'queue_backlog' => [
                'warning' => 50,
                'critical' => 100,
                'unit' => 'jobs',
            ],

            // Error rate monitoring
            'error_rate' => [
                'warning' => 0.5,
                'critical' => 1.0,
                'unit' => 'percentage',
            ],

            // Response time monitoring
            'response_time' => [
                'warning' => 1000,
                'critical' => 3000,
                'unit' => 'milliseconds',
            ],

            // Memory usage monitoring
            'memory_usage' => [
                'warning' => 80,
                'critical' => 95,
                'unit' => 'percentage',
            ],

            // CPU usage monitoring
            'cpu_usage' => [
                'warning' => 70,
                'critical' => 90,
                'unit' => 'percentage',
            ],

            // Disk usage monitoring
            'disk_usage' => [
                'warning' => 80,
                'critical' => 95,
                'unit' => 'percentage',
            ],

            // Failed jobs monitoring
            'failed_jobs' => [
                'warning' => 10,
                'critical' => 50,
                'unit' => 'jobs',
            ],

            // Database connection pool
            'db_connections' => [
                'warning' => 80,
                'critical' => 95,
                'unit' => 'percentage',
            ],

            // Cache hit rate
            'cache_hit_rate' => [
                'warning' => 80,
                'critical' => 60,
                'unit' => 'percentage',
            ],

            // Session usage
            'session_usage' => [
                'warning' => 85,
                'critical' => 95,
                'unit' => 'percentage',
            ],
        ],

        'rate_limits' => [
            'critical' => env('HORIZON_ALERT_CRITICAL_LIMIT', 300),
            'error' => env('HORIZON_ALERT_ERROR_LIMIT', 900),
            'warning' => env('HORIZON_ALERT_WARNING_LIMIT', 1800),
            'info' => env('HORIZON_ALERT_INFO_LIMIT', 3600),
        ],

        'cooldown_periods' => [
            'low' => 300,      // 5 minutes
            'medium' => 900,   // 15 minutes
            'high' => 1800,    // 30 minutes
            'critical' => 3600, // 1 hour
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Checks Configuration
    |--------------------------------------------------------------------------
    |
    | Health check settings for monitoring system components.
    |
    */

    'health_checks' => [
        'enabled' => env('HEALTH_CHECKS_ENABLED', true),
        'interval' => env('HEALTH_CHECK_INTERVAL', 60),
        'timeout' => env('HEALTH_CHECK_TIMEOUT', 10),
        'retries' => env('HEALTH_CHECK_RETRIES', 3),

        'checks' => [
            'database' => [
                'enabled' => true,
                'timeout' => 5,
                'critical' => true,
            ],
            'redis' => [
                'enabled' => true,
                'timeout' => 3,
                'critical' => true,
            ],
            'queue' => [
                'enabled' => true,
                'timeout' => 5,
                'critical' => false,
            ],
            'storage' => [
                'enabled' => true,
                'timeout' => 5,
                'critical' => true,
            ],
            'cache' => [
                'enabled' => true,
                'timeout' => 3,
                'critical' => false,
            ],
            'mail' => [
                'enabled' => true,
                'timeout' => 5,
                'critical' => false,
            ],
            'external_apis' => [
                'enabled' => true,
                'timeout' => 10,
                'critical' => false,
            ],
            'filesystem' => [
                'enabled' => true,
                'timeout' => 5,
                'critical' => true,
            ],
            'php_extensions' => [
                'enabled' => true,
                'timeout' => 2,
                'critical' => true,
            ],
        ],

        'endpoints' => [
            'basic' => '/health',
            'detailed' => '/health/detailed',
            'ready' => '/ready',
            'live' => '/live',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Configuration
    |--------------------------------------------------------------------------
    |
    | Metrics collection and retention settings.
    |
    */

    'metrics' => [
        'enabled' => env('METRICS_ENABLED', true),

        'retention' => [
            'performance' => env('METRICS_PERFORMANCE_RETENTION', 30),
            'error' => env('METRICS_ERROR_RETENTION', 90),
            'analytics' => env('METRICS_ANALYTICS_RETENTION', 365),
            'alert' => env('METRICS_ALERT_RETENTION', 180),
            'business' => env('METRICS_BUSINESS_RETENTION', 730),
        ],

        'collection' => [
            'response_times' => true,
            'memory_usage' => true,
            'database_queries' => true,
            'cache_hits' => true,
            'error_rates' => true,
            'queue_metrics' => true,
            'custom_metrics' => true,
        ],

        'aggregation' => [
            'interval' => 60,
            'precision' => 2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Application and system performance monitoring settings.
    |
    */

    'performance' => [
        'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),

        'budgets' => [
            'response_time' => [
                'warning' => 1000,
                'critical' => 3000,
                'unit' => 'ms',
            ],
            'memory_usage' => [
                'warning' => 256,
                'critical' => 512,
                'unit' => 'MB',
            ],
            'db_query_time' => [
                'warning' => 200,
                'critical' => 500,
                'unit' => 'ms',
            ],
            'cache_miss_rate' => [
                'warning' => 0.1,
                'critical' => 0.2,
                'unit' => 'percentage',
            ],
        ],

        'profiling' => [
            'enabled' => env('PERFORMANCE_PROFILING_ENABLED', false),
            'sampling_rate' => 0.01,
            'max_stack_depth' => 50,
            'exclude_patterns' => [
                '/vendor/',
                '/bootstrap/',
            ],
        ],

        'database_queries' => [
            'slow_query_threshold' => 1000,
            'very_slow_query_threshold' => 5000,
            'log_queries' => true,
            'log_binding_values' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Performance Monitoring (APM)
    |--------------------------------------------------------------------------
    |
    | APM configuration for distributed tracing and performance insights.
    |
    */

    'apm' => [
        'enabled' => env('APM_ENABLED', false),

        'sample_rate' => env('APM_SAMPLE_RATE', 0.1),

        'services' => [
            'sentry' => [
                'enabled' => env('SENTRY_ENABLED', false),
                'dsn' => env('SENTRY_DSN'),
                'environment' => env('APP_ENV', 'production'),
                'traces_sample_rate' => 0.1,
                'profiles_sample_rate' => 0.1,
            ],
            'newrelic' => [
                'enabled' => env('NEWRELIC_ENABLED', false),
                'license_key' => env('NEWRELIC_LICENSE_KEY'),
                'app_name' => env('NEWRELIC_APP_NAME', 'Alumni Platform'),
                'distributed_tracing' => true,
            ],
            'datadog' => [
                'enabled' => env('DD_TRACE_ENABLED', false),
                'service' => env('DD_SERVICE', 'alumni-platform'),
                'env' => env('APP_ENV'),
                'version' => env('APP_VERSION'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Database performance and health monitoring settings.
    |
    */

    'database_monitoring' => [
        'enabled' => true,

        'connections' => [
            'pgsql' => [
                'enabled' => true,
                'metrics' => [
                    'connections' => true,
                    'queries' => true,
                    'slow_queries' => true,
                    'locks' => true,
                    'cache_hit_ratio' => true,
                    'index_usage' => true,
                    'table_size' => true,
                    'vacuum_status' => true,
                ],
                'thresholds' => [
                    'max_connections' => 200,
                    'slow_query_threshold_ms' => 1000,
                    'idle_connection_timeout' => 300,
                ],
            ],
        ],

        'replication' => [
            'enabled' => false,
            'check_interval' => 30,
            'lag_threshold' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Redis and cache system monitoring settings.
    |
    */

    'cache_monitoring' => [
        'enabled' => true,

        'metrics' => [
            'hit_rate' => true,
            'miss_rate' => true,
            'memory_usage' => true,
            'evictions' => true,
            'connections' => true,
            'latency' => true,
            'keys_count' => true,
            'expired_keys' => true,
            'evicted_keys' => true,
        ],

        'thresholds' => [
            'memory_usage_percent' => 80,
            'hit_rate_percent' => 80,
            'connection_pool_usage' => 90,
            'latency_threshold_ms' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Background job queue monitoring settings.
    |
    */

    'queue_monitoring' => [
        'enabled' => true,

        'metrics' => [
            'jobs_waiting' => true,
            'jobs_processing' => true,
            'jobs_failed' => true,
            'jobs_retry' => true,
            'job_duration' => true,
            'job_throughput' => true,
            'worker_status' => true,
        ],

        'thresholds' => [
            'max_jobs_waiting' => 100,
            'max_jobs_failed' => 10,
            'max_job_duration_seconds' => 300,
            'max_retry_count' => 3,
        ],

        'horizon' => [
            'enabled' => true,
            'balancer' => 'workload',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Tracking Configuration
    |--------------------------------------------------------------------------
    |
    | Error collection and tracking settings.
    |
    */

    'error_tracking' => [
        'enabled' => env('ERROR_TRACKING_ENABLED', true),

        'channels' => [
            'sentry' => [
                'enabled' => env('SENTRY_ENABLED', false),
                'dsn' => env('SENTRY_DSN'),
                'environment' => env('APP_ENV', 'production'),
                'release' => env('APP_VERSION'),
                'sample_rate' => 1.0,
                'max_breadcrumbs' => 100,
            ],
            'rollbar' => [
                'enabled' => env('ROLLBAR_ENABLED', false),
                'access_token' => env('ROLLBAR_ACCESS_TOKEN'),
                'environment' => env('APP_ENV'),
            ],
        ],

        'filters' => [
            'ignore_404' => true,
            'ignore_403' => false,
            'ignore_csrf' => true,
            'ignore_exceptions' => [],
        ],

        'grouping' => [
            'enabled' => true,
            'similarity_threshold' => 0.9,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Uptime Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | System uptime and availability monitoring.
    |
    */

    'uptime_monitoring' => [
        'enabled' => env('UPTIME_MONITORING_ENABLED', true),

        'checks' => [
            'http' => [
                'enabled' => true,
                'endpoint' => '/health',
                'method' => 'GET',
                'expected_status' => 200,
            ],
            'https' => [
                'enabled' => true,
                'endpoint' => '/health',
                'verify_ssl' => true,
            ],
            'ports' => [
                'enabled' => true,
                'ports' => [80, 443],
            ],
        ],

        'schedule' => [
            'interval' => 60,
            'timeout' => 30,
        ],

        'history' => [
            'retention_days' => 90,
            'precision_seconds' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Metrics Configuration
    |--------------------------------------------------------------------------
    |
    | Custom business and application metrics.
    |
    */

    'custom_metrics' => [
        'enabled' => true,

        'business' => [
            'active_users' => [
                'enabled' => true,
                'description' => 'Number of active users',
                'type' => 'gauge',
            ],
            'new_registrations' => [
                'enabled' => true,
                'description' => 'Number of new user registrations',
                'type' => 'counter',
            ],
            'tenant_count' => [
                'enabled' => true,
                'description' => 'Number of active tenants',
                'type' => 'gauge',
            ],
            'session_count' => [
                'enabled' => true,
                'description' => 'Number of active sessions',
                'type' => 'gauge',
            ],
            'api_requests' => [
                'enabled' => true,
                'description' => 'API request count',
                'type' => 'counter',
            ],
            'conversion_rate' => [
                'enabled' => true,
                'description' => 'User conversion rate',
                'type' => 'gauge',
            ],
        ],

        'application' => [
            'login_attempts' => [
                'enabled' => true,
                'description' => 'Login attempt count',
                'type' => 'counter',
            ],
            'failed_logins' => [
                'enabled' => true,
                'description' => 'Failed login count',
                'type' => 'counter',
            ],
            'password_resets' => [
                'enabled' => true,
                'description' => 'Password reset requests',
                'type' => 'counter',
            ],
            'email_sent' => [
                'enabled' => true,
                'description' => 'Emails sent',
                'type' => 'counter',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Templates
    |--------------------------------------------------------------------------
    |
    | Alert notification message templates.
    |
    */

    'notifications' => [
        'templates' => [
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
        ],

        'formats' => [
            'email' => [
                'subject_prefix' => '[ALUMATE]',
                'severity_emoji' => true,
                'include_stack_trace' => true,
            ],
            'slack' => [
                'use_blocks' => true,
                'severity_emoji' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting Configuration
    |--------------------------------------------------------------------------
    |
    | Automated report generation settings.
    |
    */

    'reporting' => [
        'enabled' => true,

        'schedules' => [
            'daily' => [
                'enabled' => true,
                'time' => '06:00',
                'recipients' => ['email'],
            ],
            'weekly' => [
                'enabled' => true,
                'day' => 'monday',
                'time' => '08:00',
                'recipients' => ['email'],
            ],
            'monthly' => [
                'enabled' => true,
                'day' => 1,
                'time' => '09:00',
                'recipients' => ['email'],
            ],
        ],

        'contents' => [
            'system_health' => true,
            'performance_metrics' => true,
            'error_summary' => true,
            'alerts_summary' => true,
            'business_metrics' => true,
            'security_events' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenancy Considerations
    |--------------------------------------------------------------------------
    |
    | Multi-tenant monitoring isolation settings.
    |
    */

    'tenancy' => [
        'isolate_tenant_data' => env('TENANCY_ISOLATE_DATA', true),
        'shared_monitoring' => false,
        'tenant_alert_separation' => true,
        'cross_tenant_analytics' => false,
        'aggregate_tenant_metrics' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Monitoring
    |--------------------------------------------------------------------------
    |
    | Security-related monitoring and threat detection.
    |
    */

    'security_monitoring' => [
        'enabled' => true,

        'threat_detection' => [
            'brute_force' => [
                'enabled' => true,
                'threshold' => 10,
                'window_minutes' => 15,
            ],
            'suspicious_activity' => [
                'enabled' => true,
                'geo_anomaly' => [
                    'enabled' => true,
                    'max_distance_km' => 1000,
                ],
            ],
        ],

        'audit' => [
            'enabled' => true,
            'events' => [
                'login' => true,
                'logout' => true,
                'password_change' => true,
                'email_change' => true,
                'role_change' => true,
                'permission_change' => true,
                'data_export' => true,
                'data_delete' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Third-party monitoring service integrations.
    |
    */

    'integrations' => [
        'prometheus' => [
            'enabled' => env('PROMETHEUS_ENABLED', true),
            'path' => '/metrics',
            'namespace' => 'alumate',
        ],
        'grafana' => [
            'enabled' => false,
            'datasource_url' => env('GRAFANA_DATASOURCE_URL'),
        ],
        'newrelic' => [
            'enabled' => false,
            'app_name' => env('NEWRELIC_APP_NAME'),
        ],
    ],

];
