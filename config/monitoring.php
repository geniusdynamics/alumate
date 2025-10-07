<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for system monitoring, alerting,
    | and performance thresholds for the Advanced Analytics System.
    |
    */

    'alerts' => [
        'enabled' => env('MONITORING_ALERTS_ENABLED', true),

        'channels' => [
            'email' => env('MONITORING_ALERT_EMAIL'),
            'slack' => env('MONITORING_SLACK_WEBHOOK'),
            'pagerduty' => env('PAGERDUTY_INTEGRATION_KEY'),
            'datadog' => env('DATADOG_API_KEY'),
            'newrelic' => env('NEWRELIC_API_KEY'),
        ],

        'thresholds' => [
            'queue_backlog' => [
                'warning' => 50,
                'critical' => 100,
            ],

            'error_rate' => [
                'warning' => 0.5, // percentage
                'critical' => 1.0, // percentage
            ],

            'response_time' => [
                'warning' => 1000, // milliseconds
                'critical' => 3000, // milliseconds
            ],

            'memory_usage' => [
                'warning' => 80, // percentage
                'critical' => 95, // percentage
            ],

            'cpu_usage' => [
                'warning' => 70, // percentage
                'critical' => 90, // percentage
            ],

            'disk_usage' => [
                'warning' => 80, // percentage
                'critical' => 95, // percentage
            ],

            'failed_jobs' => [
                'warning' => 10,
                'critical' => 50,
            ],
        ],

        'rate_limits' => [
            'critical' => env('HORIZON_ALERT_CRITICAL_LIMIT', 300), // seconds
            'error' => env('HORIZON_ALERT_ERROR_LIMIT', 900), // seconds
            'warning' => env('HORIZON_ALERT_WARNING_LIMIT', 1800), // seconds
            'info' => env('HORIZON_ALERT_INFO_LIMIT', 3600), // seconds
        ],
    ],

    'health_checks' => [
        'enabled' => env('HORIZON_HEALTH_CHECKS', true),
        'interval' => env('HORIZON_HEALTH_CHECK_INTERVAL', 300), // seconds
        'timeout' => env('HORIZON_HEALTH_CHECK_TIMEOUT', 10), // seconds

        'checks' => [
            'database' => true,
            'redis' => true,
            'queue' => true,
            'storage' => true,
            'external_apis' => true,
        ],
    ],

    'metrics' => [
        'retention' => [
            'performance' => env('HORIZON_PERFORMANCE_RETENTION', 30), // days
            'error' => env('HORIZON_ERROR_RETENTION', 90), // days
            'analytics' => env('HORIZON_ANALYTICS_RETENTION', 365), // days
            'alert' => env('HORIZON_ALERT_RETENTION', 180), // days
        ],
    ],

    'notifications' => [
        'templates' => [
            'queue_backlog' => 'Queue backlog has exceeded :threshold jobs (:current jobs)',
            'error_rate' => 'Error rate has exceeded :threshold% (:current%)',
            'response_time' => 'Response time has exceeded :thresholdms (:currentms)',
            'system_resource' => ':resource usage has exceeded :threshold% (:current%)',
        ],
    ],

];