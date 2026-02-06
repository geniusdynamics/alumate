<?php

/**
 * Production Monitoring Configuration
 *
 * Environment-specific configuration for production monitoring
 * and analytics systems. This file contains all settings
 * for monitoring alerts, thresholds, and integrations.
 *
 * @package Alumate\Infrastructure\Production
 * @version 3.0.0
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring General Settings
    |--------------------------------------------------------------------------
    */

    'enabled' => env('MONITORING_ENABLED', true),

    'environment' => env('APP_ENV', 'production'),

    'instance_id' => env('INSTANCE_ID', gethostname()),

    /*
    |--------------------------------------------------------------------------
    | Alert Rules Configuration
    |--------------------------------------------------------------------------
    |
    | Comprehensive alert rules with conditions, thresholds, and actions.
    |
    */

    'alert_rules' => [
        // Critical Alerts
        'critical' => [
            [
                'name' => 'Application Down',
                'description' => 'Application is not responding to health checks',
                'condition' => [
                    'type' => 'health_check',
                    'metric' => 'application_health',
                    'operator' => 'eq',
                    'value' => 'unhealthy',
                ],
                'severity' => 'critical',
                'channels' => ['email', 'slack', 'pagerduty', 'sms', 'phone'],
                'auto_resolve' => false,
                'escalation' => true,
                'runbook' => 'https://wiki.alumate.com/runbooks/application-down',
            ],
            [
                'name' => 'Database Unavailable',
                'description' => 'Database connection failed',
                'condition' => [
                    'type' => 'database',
                    'metric' => 'connection_status',
                    'operator' => 'eq',
                    'value' => 'failed',
                ],
                'severity' => 'critical',
                'channels' => ['email', 'slack', 'pagerduty', 'phone'],
                'auto_resolve' => false,
                'escalation' => true,
                'runbook' => 'https://wiki.alumate.com/runbooks/database-unavailable',
            ],
            [
                'name' => 'High Error Rate',
                'description' => 'Application error rate exceeds critical threshold',
                'condition' => [
                    'type' => 'percentage',
                    'metric' => 'error_rate',
                    'operator' => 'gte',
                    'value' => 5.0,
                    'window' => '5m',
                ],
                'severity' => 'critical',
                'channels' => ['email', 'slack', 'pagerduty'],
                'auto_resolve' => true,
                'escalation' => true,
                'runbook' => 'https://wiki.alumate.com/runbooks/high-error-rate',
            ],
            [
                'name' => 'Memory Exhaustion',
                'description' => 'Memory usage critical - risk of OOM',
                'condition' => [
                    'type' => 'percentage',
                    'metric' => 'memory_usage',
                    'operator' => 'gte',
                    'value' => 95,
                    'window' => '2m',
                ],
                'severity' => 'critical',
                'channels' => ['email', 'slack', 'pagerduty'],
                'auto_resolve' => false,
                'escalation' => true,
                'runbook' => 'https://wiki.alumate.com/runbooks/memory-exhaustion',
            ],
            [
                'name' => 'Disk Full',
                'description' => 'Disk space critically low',
                'condition' => [
                    'type' => 'percentage',
                    'metric' => 'disk_usage',
                    'operator' => 'gte',
                    'value' => 95,
                    'window' => '1m',
                ],
                'severity' => 'critical',
                'channels' => ['email', 'slack', 'pagerduty'],
                'auto_resolve' => false,
                'escalation' => true,
                'runbook' => 'https://wiki.alumate.com/runbooks/disk-full',
            ],
            [
                'name' => 'Security Breach Detected',
                'description' => 'Potential security breach or attack detected',
                'condition' => [
                    'type' => 'security',
                    'metric' => 'threat_level',
                    'operator' => 'gte',
                    'value' => 'high',
                ],
                'severity' => 'critical',
                'channels' => ['email', 'slack', 'pagerduty', 'phone'],
                'auto_resolve' => false,
                'escalation' => true,
                'runbook' => 'https://wiki.alumate.com/runbooks/security-breach',
            ],
        ],

        // High Severity Alerts
        'high' => [
            [
                'name' => 'Slow Response Time',
                'description' => 'API response time exceeds warning threshold',
                'condition' => [
                    'type' => 'milliseconds',
                    'metric' => 'response_time_p99',
                    'operator' => 'gte',
                    'value' => 3000,
                    'window' => '5m',
                ],
                'severity' => 'high',
                'channels' => ['email', 'slack'],
                'auto_resolve' => true,
                'escalation' => false,
                'runbook' => 'https://wiki.alumate.com/runbooks/slow-response',
            ],
            [
                'name' => 'Queue Backlog Growing',
                'description' => 'Job queue backlog exceeds critical threshold',
                'condition' => [
                    'type' => 'count',
                    'metric' => 'queue_backlog',
                    'operator' => 'gte',
                    'value' => 100,
                    'window' => '10m',
                ],
                'severity' => 'high',
                'channels' => ['email', 'slack'],
                'auto_resolve' => true,
                'escalation' => true,
                'runbook' => 'https://wiki.alumate.com/runbooks/queue-backlog',
            ],
            [
                'name' => 'High CPU Usage',
                'description' => 'CPU usage consistently high',
                'condition' => [
                    'type' => 'percentage',
                    'metric' => 'cpu_usage',
                    'operator' => 'gte',
                    'value' => 90,
                    'window' => '5m',
                ],
                'severity' => 'high',
                'channels' => ['email', 'slack'],
                'auto_resolve' => true,
                'escalation' => false,
                'runbook' => 'https://wiki.alumate.com/runbooks/high-cpu',
            ],
            [
                'name' => 'Failed Jobs Accumulating',
                'description' => 'Failed jobs queue growing',
                'condition' => [
                    'type' => 'count',
                    'metric' => 'failed_jobs_count',
                    'operator' => 'gte',
                    'value' => 50,
                    'window' => '15m',
                ],
                'severity' => 'high',
                'channels' => ['email', 'slack'],
                'auto_resolve' => false,
                'escalation' => false,
                'runbook' => 'https://wiki.alumate.com/runbooks/failed-jobs',
            ],
            [
                'name' => 'SSL Certificate Expiring',
                'description' => 'SSL certificate expires soon',
                'condition' => [
                    'type' => 'days',
                    'metric' => 'ssl_certificate_expiry',
                    'operator' => 'lte',
                    'value' => 30,
                    'window' => '1d',
                ],
                'severity' => 'high',
                'channels' => ['email', 'slack'],
                'auto_resolve' => false,
                'escalation' => false,
                'runbook' => 'https://wiki.alumate.com/runbooks/ssl-renewal',
            ],
        ],

        // Medium Severity Alerts
        'medium' => [
            [
                'name' => 'Elevated Error Rate',
                'description' => 'Error rate exceeds warning threshold',
                'condition' => [
                    'type' => 'percentage',
                    'metric' => 'error_rate',
                    'operator' => 'gte',
                    'value' => 1.0,
                    'window' => '10m',
                ],
                'severity' => 'medium',
                'channels' => ['email', 'slack'],
                'auto_resolve' => true,
                'escalation' => false,
                'runbook' => 'https://wiki.alumate.com/runbooks/elevated-errors',
            ],
            [
                'name' => 'Cache Hit Rate Low',
                'description' => 'Cache efficiency dropping',
                'condition' => [
                    'type' => 'percentage',
                    'metric' => 'cache_hit_rate',
                    'operator' => 'lte',
                    'value' => 80,
                    'window' => '15m',
                ],
                'severity' => 'medium',
                'channels' => ['email', 'slack'],
                'auto_resolve' => true,
                'escalation' => false,
                'runbook' => 'https://wiki.alumate.com/runbooks/cache-performance',
            ],
            [
                'name' => 'High Memory Usage',
                'description' => 'Memory usage exceeds warning threshold',
                'condition' => [
                    'type' => 'percentage',
                    'metric' => 'memory_usage',
                    'operator' => 'gte',
                    'value' => 80,
                    'window' => '10m',
                ],
                'severity' => 'medium',
                'channels' => ['email', 'slack'],
                'auto_resolve' => true,
                'escalation' => false,
                'runbook' => 'https://wiki.alumate.com/runbooks/high-memory',
            ],
            [
                'name' => 'Disk Usage Warning',
                'description' => 'Disk usage exceeds warning threshold',
                'condition' => [
                    'type' => 'percentage',
                    'metric' => 'disk_usage',
                    'operator' => 'gte',
                    'value' => 80,
                    'window' => '1h',
                ],
                'severity' => 'medium',
                'channels' => ['email', 'slack'],
                'auto_resolve' => false,
                'escalation' => false,
                'runbook' => 'https://wiki.alumate.com/runbooks/disk-usage',
            ],
            [
                'name' => 'Database Slow Queries',
                'description' => 'Slow database queries detected',
                'condition' => [
                    'type' => 'count',
                    'metric' => 'slow_queries_per_minute',
                    'operator' => 'gte',
                    'value' => 10,
                    'window' => '5m',
                ],
                'severity' => 'medium',
                'channels' => ['email', 'slack'],
                'auto_resolve' => true,
                'escalation' => false,
                'runbook' => 'https://wiki.alumate.com/runbooks/slow-queries',
            ],
            [
                'name' => 'Redis Memory High',
                'description' => 'Redis memory usage increasing',
                'condition' => [
                    'type' => 'percentage',
                    'metric' => 'redis_memory_usage',
                    'operator' => 'gte',
                    'value' => 80,
                    'window' => '10m',
                ],
                'severity' => 'medium',
                'channels' => ['email', 'slack'],
                'auto_resolve' => true,
                'escalation' => false,
                'runbook' => 'https://wiki.alumate.com/runbooks/redis-memory',
            ],
        ],

        // Low Severity Alerts
        'low' => [
            [
                'name' => 'Backup Incomplete',
                'description' => 'Scheduled backup did not complete',
                'condition' => [
                    'type' => 'boolean',
                    'metric' => 'backup_completed',
                    'operator' => 'eq',
                    'value' => false,
                ],
                'severity' => 'low',
                'channels' => ['email'],
                'auto_resolve' => false,
                'escalation' => false,
                'runbook' => 'https://wiki.alumate.com/runbooks/backup-issues',
            ],
            [
                'name' => 'Low Disk Space Warning',
                'description' => 'Disk space below 20% threshold',
                'condition' => [
                    'type' => 'percentage',
                    'metric' => 'disk_free_percentage',
                    'operator' => 'lte',
                    'value' => 20,
                    'window' => '1d',
                ],
                'severity' => 'low',
                'channels' => ['email'],
                'auto_resolve' => false,
                'escalation' => false,
                'runbook' => 'https://wiki.alumate.com/runbooks/disk-space',
            ],
            [
                'name' => 'Deprecated API Usage',
                'description' => 'Deprecated API endpoints being accessed',
                'condition' => [
                    'type' => 'count',
                    'metric' => 'deprecated_api_calls',
                    'operator' => 'gte',
                    'value' => 100,
                    'window' => '1h',
                ],
                'severity' => 'low',
                'channels' => ['email'],
                'auto_resolve' => false,
                'escalation' => false,
                'runbook' => 'https://wiki.alumate.com/runbooks/deprecated-apis',
            ],
            [
                'name' => 'Maintenance Mode Scheduled',
                'description' => 'Upcoming maintenance window',
                'condition' => [
                    'type' => 'scheduled',
                    'metric' => 'maintenance_window',
                    'operator' => 'eq',
                    'value' => 'upcoming',
                ],
                'severity' => 'low',
                'channels' => ['email', 'slack'],
                'auto_resolve' => false,
                'escalation' => false,
                'runbook' => 'https://wiki.alumate.com/runbooks/maintenance',
            ],
        ],

        // Informational Alerts
        'info' => [
            [
                'name' => 'Deployment Successful',
                'description' => 'New deployment completed successfully',
                'condition' => [
                    'type' => 'event',
                    'metric' => 'deployment_status',
                    'operator' => 'eq',
                    'value' => 'success',
                ],
                'severity' => 'info',
                'channels' => ['slack'],
                'auto_resolve' => false,
                'escalation' => false,
            ],
            [
                'name' => 'New Tenant Created',
                'description' => 'New tenant onboarded to the platform',
                'condition' => [
                    'type' => 'event',
                    'metric' => 'tenant_created',
                    'operator' => 'eq',
                    'value' => true,
                ],
                'severity' => 'info',
                'channels' => [],
                'auto_resolve' => false,
                'escalation' => false,
            ],
            [
                'name' => 'Certificate Renewed',
                'description' => 'SSL/TLS certificate renewed successfully',
                'condition' => [
                    'type' => 'event',
                    'metric' => 'certificate_renewed',
                    'operator' => 'eq',
                    'value' => true,
                ],
                'severity' => 'info',
                'channels' => ['slack'],
                'auto_resolve' => false,
                'escalation' => false,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Channel Configuration
    |--------------------------------------------------------------------------
    */

    'alert_channels' => [
        'email' => [
            'enabled' => env('ALERT_EMAIL_ENABLED', true),
            'recipients' => array_filter(array_map('trim', explode(',', env('ALERT_EMAIL_RECIPIENTS', 'admin@example.com')))),
            'from' => [
                'address' => env('ALERT_EMAIL_FROM', 'alerts@alumate.com'),
                'name' => env('ALERT_EMAIL_FROM_NAME', 'Alumate Alerts'),
            ],
            'subject_prefix' => '[ALUMATE ALERT]',
        ],
        'slack' => [
            'enabled' => env('ALERT_SLACK_ENABLED', true),
            'webhook_url' => env('ALERT_SLACK_WEBHOOK'),
            'channel' => env('ALERT_SLACK_CHANNEL', '#alerts'),
            'username' => env('ALERT_SLACK_USERNAME', 'Alumate Monitor'),
            'icon' => env('ALERT_SLACK_ICON', ':robot_face:'),
            'mention_users' => env('ALERT_SLACK_MENTION_USERS', ''),
            'mention_roles' => ['@devops', '@platform'],
        ],
        'pagerduty' => [
            'enabled' => env('ALERT_PAGERDUTY_ENABLED', false),
            'integration_key' => env('PAGERDUTY_INTEGRATION_KEY'),
            'service_name' => env('PAGERDUTY_SERVICE_NAME', 'Alumni Platform'),
            'escalation_policy' => env('PAGERDUTY_ESCALATION_POLICY', 'default'),
        ],
        'webhook' => [
            'enabled' => env('ALERT_WEBHOOK_ENABLED', false),
            'url' => env('ALERT_WEBHOOK_URL'),
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Alumate-Alert' => 'true',
            ],
            'auth' => [
                'type' => 'bearer',
                'token' => env('ALERT_WEBHOOK_TOKEN'),
            ],
        ],
        'sms' => [
            'enabled' => env('ALERT_SMS_ENABLED', false),
            'provider' => env('SMS_PROVIDER', 'twilio'),
            'from' => env('SMS_FROM_NUMBER'),
            'recipients' => array_filter(array_map('trim', explode(',', env('ALERT_SMS_RECIPIENTS', '')))),
        ],
        'phone' => [
            'enabled' => env('ALERT_PHONE_ENABLED', false),
            'provider' => env('PHONE_PROVIDER', 'twilio'),
            'recipients' => array_filter(array_map('trim', explode(',', env('ALERT_PHONE_RECIPIENTS', '')))),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Escalation Policies
    |--------------------------------------------------------------------------
    */

    'escalation_policies' => [
        'default' => [
            'step_1' => [
                'delay_minutes' => 5,
                'channels' => ['email', 'slack'],
                'notify' => ['@devops'],
            ],
            'step_2' => [
                'delay_minutes' => 15,
                'channels' => ['email', 'slack', 'sms'],
                'notify' => ['@platform-lead'],
            ],
            'step_3' => [
                'delay_minutes' => 30,
                'channels' => ['email', 'slack', 'sms', 'phone'],
                'notify' => ['@engineering-manager'],
            ],
        ],
        'security' => [
            'step_1' => [
                'delay_minutes' => 0,
                'channels' => ['slack', 'pagerduty'],
                'notify' => ['@security-team'],
            ],
            'step_2' => [
                'delay_minutes' => 5,
                'channels' => ['email', 'sms', 'phone'],
                'notify' => ['@security-lead'],
            ],
            'step_3' => [
                'delay_minutes' => 15,
                'channels' => ['email', 'sms', 'phone'],
                'notify' => ['@cto'],
            ],
        ],
        'database' => [
            'step_1' => [
                'delay_minutes' => 2,
                'channels' => ['email', 'slack'],
                'notify' => ['@devops'],
            ],
            'step_2' => [
                'delay_minutes' => 10,
                'channels' => ['email', 'slack', 'pagerduty'],
                'notify' => ['@dba'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    */

    'dashboard' => [
        'refresh_intervals' => [
            'realtime' => 30,
            'default' => 300,
            'historical' => 3600,
        ],
        'time_ranges' => [
            'default' => '24 hours',
            'available' => ['1 hour', '6 hours', '24 hours', '7 days', '30 days', '90 days', '1 year'],
        ],
        'cache_duration' => 300,

        'widgets' => [
            'system_overview',
            'performance_metrics',
            'error_trends',
            'queue_status',
            'active_alerts',
            'business_metrics',
            'security_events',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    */

    'health_checks' => [
        'enabled' => true,
        'interval' => 30,
        'timeout' => 10,
        'retries' => 3,
        'unhealthy_threshold' => 3,

        'checks' => [
            'application' => [
                'enabled' => true,
                'critical' => true,
                'endpoints' => ['/health', '/ready'],
            ],
            'database' => [
                'enabled' => true,
                'critical' => true,
                'connections' => true,
                'replication_lag' => true,
            ],
            'redis' => [
                'enabled' => true,
                'critical' => true,
                'memory' => true,
                'connected_clients' => true,
            ],
            'queue' => [
                'enabled' => true,
                'critical' => false,
                'workers' => true,
                'backlog' => true,
            ],
            'cache' => [
                'enabled' => true,
                'critical' => false,
                'hit_rate' => true,
                'memory' => true,
            ],
            'storage' => [
                'enabled' => true,
                'critical' => true,
                'disk_usage' => true,
                'permissions' => true,
            ],
            'mail' => [
                'enabled' => true,
                'critical' => false,
                'connection' => true,
            ],
            'external_services' => [
                'enabled' => true,
                'critical' => false,
                'timeout' => 10,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Uptime Monitoring Configuration
    |--------------------------------------------------------------------------
    */

    'uptime' => [
        'enabled' => true,
        'check_interval' => 60,
        'timeout' => 30,
        'retries' => 3,

        'endpoints' => [
            'primary' => [
                'url' => env('APP_URL', 'https://alumni-platform.com'),
                'method' => 'GET',
                'expected_status' => 200,
                'verify_ssl' => true,
            ],
            'api' => [
                'url' => env('APP_URL', 'https://alumni-platform.com') . '/api/health',
                'method' => 'GET',
                'expected_status' => 200,
                'verify_ssl' => true,
            ],
        ],

        'ports' => [
            'http' => 80,
            'https' => 443,
        ],

        'history' => [
            'retention_days' => 90,
            'precision_seconds' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    */

    'performance' => [
        'enabled' => true,

        'metrics_collection' => [
            'response_times' => true,
            'memory_usage' => true,
            'database_queries' => true,
            'cache_hits' => true,
            'error_rates' => true,
            'queue_metrics' => true,
            'custom' => true,
        ],

        'budgets' => [
            'response_time' => [
                'warning' => 1000,
                'critical' => 3000,
            ],
            'memory_usage' => [
                'warning' => 256,
                'critical' => 512,
            ],
            'db_query_time' => [
                'warning' => 200,
                'critical' => 500,
            ],
            'cache_miss_rate' => [
                'warning' => 0.1,
                'critical' => 0.2,
            ],
        ],

        'profiling' => [
            'enabled' => env('PERFORMANCE_PROFILING_ENABLED', false),
            'sampling_rate' => 0.01,
            'max_stack_depth' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Monitoring
    |--------------------------------------------------------------------------
    */

    'database_monitoring' => [
        'enabled' => true,

        'postgres' => [
            'enabled' => true,
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'alumni_platform'),
            'user' => env('DB_USERNAME', 'root'),

            'metrics' => [
                'connections' => true,
                'queries' => true,
                'slow_queries' => true,
                'locks' => true,
                'cache_hit_ratio' => true,
                'index_usage' => true,
                'table_size' => true,
                'vacuum_status' => true,
                'replication_status' => true,
            ],

            'thresholds' => [
                'max_connections' => 200,
                'slow_query_threshold_ms' => 1000,
                'idle_connection_timeout' => 300,
                'lock_wait_timeout' => 10,
            ],
        ],

        'connections' => [
            'pool_size' => 20,
            'max_lifetime' => 3600,
            'idle_timeout' => 600,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Monitoring
    |--------------------------------------------------------------------------
    */

    'cache_monitoring' => [
        'enabled' => true,

        'redis' => [
            'enabled' => true,
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD'),
            'database' => env('REDIS_DB', 0),

            'metrics' => [
                'memory_usage' => true,
                'connected_clients' => true,
                'commands_processed' => true,
                'hit_rate' => true,
                'miss_rate' => true,
                'evictions' => true,
                'keys_count' => true,
                'expired_keys' => true,
                'latency' => true,
            ],

            'thresholds' => [
                'memory_usage_percent' => 80,
                'hit_rate_percent' => 80,
                'connection_pool_usage' => 90,
                'latency_threshold_ms' => 10,
                'eviction_rate' => 100,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Monitoring
    |--------------------------------------------------------------------------
    */

    'queue_monitoring' => [
        'enabled' => true,

        'redis' => [
            'enabled' => true,
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'queue_prefix' => env('REDIS_QUEUE_PREFIX', 'queues:'),

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
                'stale_job_threshold_seconds' => 600,
            ],
        ],

        'horizon' => [
            'enabled' => true,
            'balancer' => 'workload',
            'terminators' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Tracking
    |--------------------------------------------------------------------------
    */

    'error_tracking' => [
        'enabled' => true,

        'sentry' => [
            'enabled' => env('SENTRY_ENABLED', false),
            'dsn' => env('SENTRY_DSN'),
            'environment' => env('APP_ENV', 'production'),
            'release' => env('APP_VERSION'),
            'sample_rate' => 1.0,
            'max_breadcrumbs' => 100,
            'attach_stacktrace' => true,
            'before_send' => null,
        ],

        'filters' => [
            'ignore_404' => true,
            'ignore_403' => false,
            'ignore_csrf' => true,
            'ignore_exceptions' => [
                'Symfony\Component\HttpKernel\Exception\HttpException',
            ],
        ],

        'grouping' => [
            'enabled' => true,
            'similarity_threshold' => 0.9,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Metrics
    |--------------------------------------------------------------------------
    */

    'custom_metrics' => [
        'enabled' => true,

        'namespaces' => [
            'application' => 'alumate.application',
            'business' => 'alumate.business',
            'security' => 'alumate.security',
            'infrastructure' => 'alumate.infrastructure',
        ],

        'business_metrics' => [
            'active_users' => [
                'type' => 'gauge',
                'description' => 'Number of active users',
                'tags' => ['tenant'],
            ],
            'new_registrations' => [
                'type' => 'counter',
                'description' => 'Number of new user registrations',
                'tags' => ['tenant'],
            ],
            'tenant_count' => [
                'type' => 'gauge',
                'description' => 'Number of active tenants',
            ],
            'session_count' => [
                'type' => 'gauge',
                'description' => 'Number of active sessions',
            ],
            'api_requests' => [
                'type' => 'counter',
                'description' => 'API request count',
                'tags' => ['endpoint', 'method', 'status'],
            ],
            'conversion_rate' => [
                'type' => 'gauge',
                'description' => 'User conversion rate',
                'tags' => ['funnel'],
            ],
        ],

        'infrastructure_metrics' => [
            'container_cpu' => [
                'type' => 'gauge',
                'description' => 'Container CPU usage',
                'unit' => 'percent',
            ],
            'container_memory' => [
                'type' => 'gauge',
                'description' => 'Container memory usage',
                'unit' => 'bytes',
            ],
            'network_io' => [
                'type' => 'counter',
                'description' => 'Network I/O',
                'unit' => 'bytes',
                'tags' => ['direction'],
            ],
            'disk_io' => [
                'type' => 'counter',
                'description' => 'Disk I/O',
                'unit' => 'bytes',
                'tags' => ['operation'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Monitoring
    |--------------------------------------------------------------------------
    */

    'security_monitoring' => [
        'enabled' => true,

        'threat_detection' => [
            'brute_force' => [
                'enabled' => true,
                'threshold' => 10,
                'window_minutes' => 15,
                'action' => 'block_ip',
            ],
            'suspicious_activity' => [
                'enabled' => true,
                'geo_anomaly' => [
                    'enabled' => true,
                    'max_distance_km' => 1000,
                    'time_window_hours' => 1,
                ],
                'impossible_travel' => [
                    'enabled' => true,
                    'speed_threshold_kmh' => 1000,
                ],
            ],
            'rate_limiting' => [
                'enabled' => true,
                'api_threshold' => 1000,
                'auth_threshold' => 30,
            ],
        ],

        'audit' => [
            'enabled' => true,
            'retention_days' => 365,
            'events' => [
                'authentication' => [
                    'login' => true,
                    'logout' => true,
                    'failed_login' => true,
                    'password_reset' => true,
                    'password_change' => true,
                    '2fa_enable' => true,
                    '2fa_disable' => true,
                ],
                'authorization' => [
                    'permission_denied' => true,
                    'role_change' => true,
                    'resource_access' => true,
                ],
                'data' => [
                    'create' => true,
                    'update' => true,
                    'delete' => true,
                    'export' => true,
                    'import' => true,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External Integrations
    |--------------------------------------------------------------------------
    */

    'integrations' => [
        'prometheus' => [
            'enabled' => env('PROMETHEUS_ENABLED', true),
            'path' => '/metrics',
            'namespace' => 'alumate',
            'labels' => [
                'environment' => env('APP_ENV'),
                'instance' => gethostname(),
            ],
        ],
        'grafana' => [
            'enabled' => env('GRAFANA_ENABLED', false),
            'datasource_url' => env('GRAFANA_DATASOURCE_URL'),
            'api_key' => env('GRAFANA_API_KEY'),
            'dashboard_folder' => 'Alumate',
        ],
        'datadog' => [
            'enabled' => env('DATADOG_ENABLED', false),
            'api_key' => env('DATADOG_API_KEY'),
            'app_key' => env('DATADOG_APP_KEY'),
            'service' => 'alumni-platform',
            'env' => env('APP_ENV'),
            'version' => env('APP_VERSION'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting
    |--------------------------------------------------------------------------
    */

    'reporting' => [
        'enabled' => true,

        'daily' => [
            'enabled' => true,
            'time' => '06:00',
            'recipients' => ['email'],
            'includes' => [
                'system_health' => true,
                'performance_summary' => true,
                'error_summary' => true,
                'alert_summary' => true,
            ],
        ],

        'weekly' => [
            'enabled' => true,
            'day' => 'monday',
            'time' => '08:00',
            'recipients' => ['email'],
            'includes' => [
                'system_health' => true,
                'performance_summary' => true,
                'error_summary' => true,
                'alert_summary' => true,
                'business_metrics' => true,
                'security_summary' => true,
            ],
        ],

        'monthly' => [
            'enabled' => true,
            'day' => 1,
            'time' => '09:00',
            'recipients' => ['email'],
            'includes' => [
                'all' => true,
                'capacity_planning' => true,
                'trend_analysis' => true,
                'recommendations' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    */

    'data_retention' => [
        'metrics' => [
            'high_resolution' => 7,      // days
            'hourly_aggregates' => 30,   // days
            'daily_aggregates' => 365,   // days
            'monthly_aggregates' => 730, // days
        ],
        'logs' => [
            'application' => 90,
            'access' => 90,
            'security' => 365,
            'audit' => 365,
            'error' => 90,
        ],
        'alerts' => [
            'raw' => 30,
            'aggregated' => 180,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance
    |--------------------------------------------------------------------------
    */

    'maintenance' => [
        'cleanup' => [
            'scheduled' => '0 5 * * *',
            'enabled' => true,
            'tasks' => [
                'logs_rotation' => true,
                'metrics_archive' => true,
                'old_alerts_cleanup' => true,
                'temp_files_cleanup' => true,
            ],
        ],
        'scheduled_maintenance' => [
            'enabled' => true,
            'window' => 'sunday 02:00-04:00',
            'notify_before_hours' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Isolation
    |--------------------------------------------------------------------------
    */

    'tenancy' => [
        'enabled' => env('TENANCY_ENABLED', true),
        'isolate_tenant_data' => true,
        'shared_monitoring' => false,
        'tenant_alert_separation' => true,
        'cross_tenant_analytics' => false,
        'aggregate_tenant_metrics' => true,
    ],
];
