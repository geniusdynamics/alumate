<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the analytics and reporting
    | system of the graduate tracking application.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for analytics data to improve performance.
    |
    */
    'cache' => [
        'enabled' => env('ANALYTICS_CACHE_ENABLED', true),
        'ttl' => env('ANALYTICS_CACHE_TTL', 300), // 5 minutes
        'prefix' => 'analytics:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Snapshot Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for analytics snapshots that capture historical data.
    |
    */
    'snapshots' => [
        'enabled' => env('ANALYTICS_SNAPSHOTS_ENABLED', true),
        'retention_days' => env('ANALYTICS_SNAPSHOTS_RETENTION', 365),
        'auto_generate' => [
            'daily' => env('ANALYTICS_AUTO_DAILY_SNAPSHOTS', true),
            'weekly' => env('ANALYTICS_AUTO_WEEKLY_SNAPSHOTS', true),
            'monthly' => env('ANALYTICS_AUTO_MONTHLY_SNAPSHOTS', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | KPI Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for Key Performance Indicators tracking.
    |
    */
    'kpis' => [
        'auto_calculate' => env('ANALYTICS_AUTO_CALCULATE_KPIS', true),
        'calculation_schedule' => env('ANALYTICS_KPI_SCHEDULE', 'daily'),
        'alert_thresholds' => [
            'employment_rate' => [
                'warning' => 70.0,
                'critical' => 60.0,
            ],
            'job_placement_rate' => [
                'warning' => 15.0,
                'critical' => 10.0,
            ],
            'avg_time_to_employment' => [
                'warning' => 120.0,
                'critical' => 180.0,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Predictive Analytics Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for machine learning and predictive analytics features.
    |
    */
    'predictions' => [
        'enabled' => env('ANALYTICS_PREDICTIONS_ENABLED', true),
        'auto_retrain' => env('ANALYTICS_AUTO_RETRAIN_MODELS', true),
        'retrain_schedule' => env('ANALYTICS_RETRAIN_SCHEDULE', 'weekly'),
        'min_training_data' => env('ANALYTICS_MIN_TRAINING_DATA', 100),
        'prediction_horizon_days' => env('ANALYTICS_PREDICTION_HORIZON', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for custom reports and scheduled reporting.
    |
    */
    'reports' => [
        'max_records' => env('ANALYTICS_MAX_REPORT_RECORDS', 10000),
        'timeout_seconds' => env('ANALYTICS_REPORT_TIMEOUT', 300),
        'expiration_days' => env('ANALYTICS_REPORT_EXPIRATION', 30),
        'storage_disk' => env('ANALYTICS_REPORT_DISK', 'local'),
        'allowed_formats' => ['csv', 'excel', 'pdf', 'json'],
        'scheduled_processing' => [
            'enabled' => env('ANALYTICS_SCHEDULED_REPORTS_ENABLED', true),
            'max_concurrent' => env('ANALYTICS_MAX_CONCURRENT_REPORTS', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Visualization Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for charts and data visualization components.
    |
    */
    'charts' => [
        'default_colors' => [
            '#3B82F6', // Blue
            '#10B981', // Green
            '#F59E0B', // Yellow
            '#EF4444', // Red
            '#8B5CF6', // Purple
            '#F97316', // Orange
            '#06B6D4', // Cyan
            '#84CC16', // Lime
        ],
        'max_data_points' => env('ANALYTICS_MAX_CHART_POINTS', 100),
        'animation_duration' => env('ANALYTICS_CHART_ANIMATION', 750),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for data export functionality.
    |
    */
    'exports' => [
        'max_file_size' => env('ANALYTICS_MAX_EXPORT_SIZE', 50 * 1024 * 1024), // 50MB
        'cleanup_after_days' => env('ANALYTICS_EXPORT_CLEANUP_DAYS', 7),
        'batch_size' => env('ANALYTICS_EXPORT_BATCH_SIZE', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for performance optimization.
    |
    */
    'performance' => [
        'query_timeout' => env('ANALYTICS_QUERY_TIMEOUT', 60),
        'memory_limit' => env('ANALYTICS_MEMORY_LIMIT', '512M'),
        'chunk_size' => env('ANALYTICS_CHUNK_SIZE', 1000),
        'parallel_processing' => env('ANALYTICS_PARALLEL_PROCESSING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for analytics security and access control.
    |
    */
    'security' => [
        'data_anonymization' => env('ANALYTICS_ANONYMIZE_DATA', false),
        'audit_access' => env('ANALYTICS_AUDIT_ACCESS', true),
        'rate_limiting' => [
            'enabled' => env('ANALYTICS_RATE_LIMITING', true),
            'max_requests_per_minute' => env('ANALYTICS_MAX_REQUESTS_PER_MINUTE', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for external integrations and webhooks.
    |
    */
    'integrations' => [
        'slack' => [
            'enabled' => env('ANALYTICS_SLACK_ENABLED', false),
            'webhook_url' => env('ANALYTICS_SLACK_WEBHOOK'),
            'channel' => env('ANALYTICS_SLACK_CHANNEL', '#analytics'),
        ],
        'email' => [
            'enabled' => env('ANALYTICS_EMAIL_ENABLED', true),
            'from_address' => env('ANALYTICS_EMAIL_FROM', env('MAIL_FROM_ADDRESS')),
            'from_name' => env('ANALYTICS_EMAIL_FROM_NAME', 'Analytics System'),
        ],
        'webhooks' => [
            'enabled' => env('ANALYTICS_WEBHOOKS_ENABLED', false),
            'timeout' => env('ANALYTICS_WEBHOOK_TIMEOUT', 30),
            'retry_attempts' => env('ANALYTICS_WEBHOOK_RETRIES', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Default widgets and layout for the analytics dashboard.
    |
    */
    'dashboard' => [
        'default_timeframe' => env('ANALYTICS_DEFAULT_TIMEFRAME', '30_days'),
        'refresh_interval' => env('ANALYTICS_REFRESH_INTERVAL', 300), // 5 minutes
        'widgets' => [
            'overview_metrics' => ['enabled' => true, 'order' => 1],
            'kpi_summary' => ['enabled' => true, 'order' => 2],
            'employment_trend' => ['enabled' => true, 'order' => 3],
            'course_performance' => ['enabled' => true, 'order' => 4],
            'job_market_activity' => ['enabled' => true, 'order' => 5],
            'recent_predictions' => ['enabled' => true, 'order' => 6],
            'system_alerts' => ['enabled' => true, 'order' => 7],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for analytics-specific logging.
    |
    */
    'logging' => [
        'enabled' => env('ANALYTICS_LOGGING_ENABLED', true),
        'level' => env('ANALYTICS_LOG_LEVEL', 'info'),
        'channel' => env('ANALYTICS_LOG_CHANNEL', 'daily'),
        'log_queries' => env('ANALYTICS_LOG_QUERIES', false),
        'log_performance' => env('ANALYTICS_LOG_PERFORMANCE', true),
    ],
];