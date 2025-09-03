<?php

/**
 * Production Monitoring Configuration
 *
 * Environment-specific configuration for production monitoring
 * and analytics systems. This file contains all settings
 * for monitoring alerts, thresholds, and integrations.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring General Settings
    |--------------------------------------------------------------------------
    */

    'enabled' => env('MONITORING_ENABLED', true),

    'environment' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Alert Configuration
    |--------------------------------------------------------------------------
    */

    'alerts' => [
        'enabled' => env('ALERTS_ENABLED', true),
        'channels' => [
            'email' => env('ALERT_EMAIL_ENABLED', true),
            'slack' => env('ALERT_SLACK_ENABLED', true),
            'sms' => env('ALERT_SMS_ENABLED', false),
            'webhook' => env('ALERT_WEBHOOK_ENABLED', false),
        ],
        'recipients' => [
            'email' => env('ALERT_EMAIL_RECIPIENTS', 'admin@example.com,dev@example.com'),
            'slack' => env('ALERT_SLACK_WEBHOOK', ''),
            'sms' => env('ALERT_SMS_RECIPIENTS', ''),
        ],
        'thresholds' => [
            'memory_usage' => [
                'warning' => env('ALERT_MEMORY_WARNING', 128), // MB
                'critical' => env('ALERT_MEMORY_CRITICAL', 256), // MB
            ],
            'response_time' => [
                'warning' => env('ALERT_RESPONSE_WARNING', 500), // ms
                'critical' => env('ALERT_RESPONSE_CRITICAL', 1000), // ms
            ],
            'error_rate' => [
                'warning' => env('ALERT_ERROR_WARNING', 1.0), // %
                'critical' => env('ALERT_ERROR_CRITICAL', 5.0), // %
            ],
        ],
        'cooldown_periods' => [
            'low' => env('ALERT_COOLDOWN_LOW', 300), // 5 minutes
            'medium' => env('ALERT_COOLDOWN_MEDIUM', 1800), // 30 minutes
            'high' => env('ALERT_COOLDOWN_HIGH', 3600), // 1 hour
            'critical' => env('ALERT_COOLDOWN_CRITICAL', 7200), // 2 hours
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    */

    'dashboard' => [
        'refresh_intervals' => [
            'realtime' => env('DASHBOARD_REFRESH_REALTIME', 120), // seconds
            'default' => env('DASHBOARD_REFRESH_DEFAULT', 300), // seconds
        ],
        'time_ranges' => [
            'default' => env('DASHBOARD_DEFAULT_TIME_RANGE', '24 hours'),
            'available' => ['1 hour', '6 hours', '24 hours', '7 days', '30 days'],
        ],
        'cache_duration' => env('DASHBOARD_CACHE_DURATION', 300), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    */

    'performance' => [
        'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),
        'metrics_collection' => [
            'response_times' => env('COLLECT_RESPONSE_TIMES', true),
            'memory_usage' => env('COLLECT_MEMORY_USAGE', true),
            'database_queries' => env('COLLECT_DB_QUERIES', true),
            'cache_hits' => env('COLLECT_CACHE_HITS', true),
            'error_rates' => env('COLLECT_ERROR_RATES', true),
        ],
        'budgets' => [
            'response_time' => env('PERFORMANCE_RESPONSE_TIME_BUDGET', 1000), // ms
            'memory_usage' => env('PERFORMANCE_MEMORY_BUDGET', 256), // MB
            'db_query_time' => env('PERFORMANCE_DB_QUERY_BUDGET', 200), // ms
            'cache_miss_rate' => env('PERFORMANCE_CACHE_MISS_BUDGET', 0.1), // 10%
        ],
        'profiling' => [
            'enabled' => env('PERFORMANCE_PROFILING_ENABLED', true),
            'sampling_rate' => env('PERFORMANCE_SAMPLING_RATE', 0.01), // 1%
            'max_stack_depth' => env('PERFORMANCE_MAX_STACK_DEPTH', 50),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Monitoring
    |--------------------------------------------------------------------------
    */

    'security' => [
        'enabled' => env('SECURITY_MONITORING_ENABLED', true),
        'scan_intervals' => [
            'vulnerability_scan' => env('SECURITY_VULNERABILITY_SCAN_HOURS', 24),
            'access_audit' => env('SECURITY_ACCESS_AUDIT_HOURS', 1),
            'threat_detection' => env('SECURITY_THREAT_DETECTION_MINUTES', 5),
        ],
        'threat_detection' => [
            'brute_force_threshold' => env('SECURITY_BRUTE_FORCE_THRESHOLD', 10),
            'suspicious_ip_window' => env('SECURITY_SUSPICIOUS_IP_WINDOW', 3600), // 1 hour
            'geo_anomaly_threshold' => env('SECURITY_GEO_ANOMALY_THRESHOLD', 1000), // km
        ],
        'data_protection' => [
            'data_retention_days' => env('DATA_RETENTION_DAYS', 365),
            'anonymize_after_days' => env('DATA_ANONYMIZE_AFTER_DAYS', 90),
            'gdpr_compliance_mode' => env('GDPR_COMPLIANCE_MODE', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics and Reporting
    |--------------------------------------------------------------------------
    */

    'analytics' => [
        'enabled' => env('ANALYTICS_ENABLED', true),
        'collection_rate' => env('ANALYTICS_COLLECTION_RATE', 1.0), // 100%
        'privacy_compliance' => [
            'ip_anonymization' => env('ANALYTICS_IP_ANONYMIZATION', true),
            'user_agent_cleanup' => env('ANALYTICS_UA_CLEANUP', true),
            'personal_data_aggregation' => env('ANALYTICS_PD_AGGREGATION', false),
        ],
        'reports' => [
            'auto_generate' => [
                'daily' => env('AUTO_DAILY_REPORTS', true),
                'weekly' => env('AUTO_WEEKLY_REPORTS', true),
                'monthly' => env('AUTO_MONTHLY_REPORTS', true),
                'quarterly' => env('AUTO_QUARTERLY_REPORTS', true),
            ],
            'distribution' => [
                'email' => env('REPORT_EMAIL_DISTRIBUTION', true),
                'slack' => env('REPORT_SLACK_DISTRIBUTION', false),
                'dashboard' => env('REPORT_DASHBOARD_PUBLISH', true),
            ],
        ],
        'components' => [
            'track_user_interactions' => env('TRACK_COMPONENT_INTERACTIONS', true),
            'track_performance' => env('TRACK_COMPONENT_PERFORMANCE', true),
            'a_b_testing' => env('ENABLE_A_B_TESTING', true),
            'max_tracking_age' => env('COMPONENT_TRACKING_MAX_AGE', 90), // days
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External Integrations
    |--------------------------------------------------------------------------
    */

    'integrations' => [
        'error_tracking' => [
            'sentry' => [
                'enabled' => env('SENTRY_ENABLED', false),
                'dsn' => env('SENTRY_DSN'),
                'environment' => env('SENTRY_ENVIRONMENT', 'production'),
            ],
            'rollbar' => [
                'enabled' => env('ROLLBAR_ENABLED', false),
                'access_token' => env('ROLLBAR_ACCESS_TOKEN'),
                'environment' => env('ROLLBAR_ENVIRONMENT'),
            ],
        ],
        'metrics_backend' => [
            'datadog' => [
                'enabled' => env('DATADOG_ENABLED', false),
                'api_key' => env('DATADOG_API_KEY'),
                'app_key' => env('DATADOG_APP_KEY'),
            ],
            'new_relic' => [
                'enabled' => env('NEW_RELIC_ENABLED', false),
                'license_key' => env('NEW_RELIC_LICENSE_KEY'),
                'app_name' => env('NEW_RELIC_APP_NAME'),
            ],
        ],
        'notifications' => [
            'slack' => [
                'webhook_url' => env('SLACK_WEBHOOK_URL'),
                'channel' => env('SLACK_CHANNEL', '#alerts'),
                'username' => env('SLACK_USERNAME', 'Monitoring Bot'),
                'icon_emoji' => env('SLACK_ICON_EMOJI', ':robot_face:'),
            ],
            'discord' => [
                'webhook_url' => env('DISCORD_WEBHOOK_URL'),
                'username' => env('DISCORD_USERNAME', 'Monitoring Bot'),
                'avatar_url' => env('DISCORD_AVATAR_URL'),
            ],
            'pagerduty' => [
                'enabled' => env('PAGERDUTY_ENABLED', false),
                'api_token' => env('PAGERDUTY_API_TOKEN'),
                'service_key' => env('PAGERDUTY_SERVICE_KEY'),
            ],
        ],
        'storage' => [
            'backup_provider' => env('BACKUP_PROVIDER', 'aws_s3'),
            'retry_config' => [
                'max_attempts' => env('BACKUP_RETRY_MAX_ATTEMPTS', 3),
                'backoff_multiplier' => env('BACKUP_RETRY_BACKOFF', 2),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automated Testing
    |--------------------------------------------------------------------------
    */

    'testing' => [
        'enabled' => env('MONITORING_TESTS_ENABLED', true),
        'schedules' => [
            'unit_tests' => env('UNIT_TESTS_SCHEDULE', '0 */4 * * *'), // Every 4 hours
            'integration_tests' => env('INTEGRATION_TESTS_SCHEDULE', '0 2 * * *'), // Daily at 2 AM
            'performance_tests' => env('PERFORMANCE_TESTS_SCHEDULE', '0 3 * * 1'), // Weekly on Monday
            'security_tests' => env('SECURITY_TESTS_SCHEDULE', '0 4 1 * *'), // Monthly 1st
        ],
        'thresholds' => [
            'unit_test_coverage' => env('UNIT_TEST_COVERAGE_MIN', 80), // %
            'integration_test_success' => env('INTEGRATION_TEST_SUCCESS_MIN', 95), // %
            'performance_regression' => env('PERFORMANCE_REGRESSION_MAX', 10), // %
        ],
        'slack_notifications' => [
            'on_failure' => env('TEST_FAILURE_NOTIFICATIONS', true),
            'on_regression' => env('TEST_REGRESSION_NOTIFICATIONS', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance and Operations
    |--------------------------------------------------------------------------
    */

    'maintenance' => [
        'cleanup_intervals' => [
            'old_logs' => env('CLEANUP_OLD_LOGS_DAYS', 90),
            'old_metrics' => env('CLEANUP_OLD_METRICS_DAYS', 365),
            'temp_files' => env('CLEANUP_TEMP_FILES_HOURS', 24),
        ],
        'backup_schedules' => [
            'database' => env('DB_BACKUP_SCHEDULE', '0 1 * * *'), // Daily at 1 AM
            'files' => env('FILES_BACKUP_SCHEDULE', '0 2 * * *'), // Daily at 2 AM
            'configuration' => env('CONFIG_BACKUP_SCHEDULE', '0 3 * * 1'), // Weekly Monday
        ],
        'health_checks' => [
            'frequency' => env('HEALTH_CHECK_FREQUENCY', 60), // seconds
            'timeout' => env('HEALTH_CHECK_TIMEOUT', 30), // seconds
            'unhealthy_threshold' => env('HEALTH_CHECK_UNHEALTHY_THRESHOLD', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Business KPI Tracking
    |--------------------------------------------------------------------------
    */

    'kpis' => [
        'track_business_metrics' => env('TRACK_BUSINESS_KPIS', true),
        'definitions' => [
            'user_engagement' => [
                'name' => 'User Engagement Rate',
                'type' => 'percentage',
                'formula' => '(active_users / total_users) * 100',
                'target' => env('KPI_USER_ENGAGEMENT_TARGET', 75),
            ],
            'conversion_rate' => [
                'name' => 'Conversion Rate',
                'type' => 'percentage',
                'formula' => '(conversions / visits) * 100',
                'target' => env('KPI_CONVERSION_TARGET', 5),
            ],
            'revenue_per_user' => [
                'name' => 'Revenue Per User',
                'type' => 'currency',
                'formula' => 'total_revenue / active_users',
                'target' => env('KPI_REVENUE_TARGET', 100),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenancy and Multi-tenant Considerations
    |--------------------------------------------------------------------------
    */

    'tenancy' => [
        'isolate_tenant_data' => env('TENANCY_ISOLATE_DATA', true),
        'shared_monitoring' => env('TENANCY_SHARED_MONITORING', false),
        'tenant_alert_separation' => env('TENANCY_ALERT_SEPARATION', true),
        'cross_tenant_analytics' => env('TENANCY_CROSS_ANALYTICS', false),
    ],
];