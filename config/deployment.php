<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Homepage Deployment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for homepage deployment monitoring and alerting.
    |
    */

    'homepage' => [

        /*
        |--------------------------------------------------------------------------
        | Monitoring Configuration
        |--------------------------------------------------------------------------
        |
        | Enable/disable various monitoring features for the homepage.
        |
        */

        'monitoring' => [
            'enabled' => env('HOMEPAGE_MONITORING_ENABLED', true),
            'performance_monitoring' => env('HOMEPAGE_PERFORMANCE_MONITORING', true),
            'error_tracking' => env('HOMEPAGE_ERROR_TRACKING', true),
            'uptime_monitoring' => env('HOMEPAGE_UPTIME_MONITORING', true),
            'security_monitoring' => env('HOMEPAGE_SECURITY_MONITORING', true),
            'conversion_monitoring' => env('HOMEPAGE_CONVERSION_MONITORING', true),
        ],

        /*
        |--------------------------------------------------------------------------
        | Performance Thresholds
        |--------------------------------------------------------------------------
        |
        | Define performance thresholds for alerting.
        |
        */

        'performance_thresholds' => [
            'page_load' => [
                'warning' => env('HOMEPAGE_PAGE_LOAD_WARNING', 2000), // milliseconds
                'critical' => env('HOMEPAGE_PAGE_LOAD_CRITICAL', 5000),
            ],
            'api_response' => [
                'warning' => env('HOMEPAGE_API_WARNING', 1000),
                'critical' => env('HOMEPAGE_API_CRITICAL', 3000),
            ],
            'database_query' => [
                'warning' => env('HOMEPAGE_DB_WARNING', 500),
                'critical' => env('HOMEPAGE_DB_CRITICAL', 2000),
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Conversion Thresholds
        |--------------------------------------------------------------------------
        |
        | Define conversion rate thresholds for alerting.
        |
        */

        'conversion_thresholds' => [
            'conversion_rate' => [
                'min' => env('HOMEPAGE_CONVERSION_MIN', 2.0), // percentage
                'max' => env('HOMEPAGE_CONVERSION_MAX', 15.0),
            ],
            'cta_click_rate' => [
                'min' => env('HOMEPAGE_CTA_CLICK_MIN', 5.0),
                'max' => env('HOMEPAGE_CTA_CLICK_MAX', 25.0),
            ],
            'bounce_rate' => [
                'max' => env('HOMEPAGE_BOUNCE_RATE_MAX', 70.0),
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Security Configuration
        |--------------------------------------------------------------------------
        |
        | Security monitoring and threat detection settings.
        |
        */

        'security' => [
            'rate_limits' => [
                'api_requests_per_minute' => env('HOMEPAGE_API_RATE_LIMIT', 30),
                'page_requests_per_minute' => env('HOMEPAGE_PAGE_RATE_LIMIT', 60),
                'suspicious_activity_threshold' => env('HOMEPAGE_SUSPICIOUS_THRESHOLD', 50),
                'ddos_threshold' => env('HOMEPAGE_DDOS_THRESHOLD', 100),
            ],
            'blocked_patterns' => [
                'sql_injection',
                'xss_attempt',
                'path_traversal',
                'command_injection',
            ],
            'suspicious_user_agents' => [
                'sqlmap',
                'nikto',
                'nmap',
                'masscan',
                'zap',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Alert Configuration
        |--------------------------------------------------------------------------
        |
        | Configure alert rate limiting and escalation.
        |
        */

        'alerts' => [
            'rate_limits' => [
                'critical' => env('HOMEPAGE_ALERT_CRITICAL_LIMIT', 300), // seconds
                'error' => env('HOMEPAGE_ALERT_ERROR_LIMIT', 900),
                'warning' => env('HOMEPAGE_ALERT_WARNING_LIMIT', 1800),
                'info' => env('HOMEPAGE_ALERT_INFO_LIMIT', 3600),
            ],
            'escalation' => [
                'enabled' => env('HOMEPAGE_ALERT_ESCALATION', false),
                'escalation_time' => env('HOMEPAGE_ALERT_ESCALATION_TIME', 1800), // 30 minutes
                'escalation_email' => env('HOMEPAGE_ALERT_ESCALATION_EMAIL'),
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Health Check Configuration
        |--------------------------------------------------------------------------
        |
        | Configure health check endpoints and intervals.
        |
        */

        'health_checks' => [
            'enabled' => env('HOMEPAGE_HEALTH_CHECKS', true),
            'interval' => env('HOMEPAGE_HEALTH_CHECK_INTERVAL', 300), // seconds
            'timeout' => env('HOMEPAGE_HEALTH_CHECK_TIMEOUT', 10),
            'endpoints' => [
                'homepage' => '/',
                'health_check' => '/health-check/homepage',
                'api_statistics' => '/api/homepage/statistics',
                'api_testimonials' => '/api/homepage/testimonials',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Data Retention
        |--------------------------------------------------------------------------
        |
        | Configure how long to keep monitoring data.
        |
        */

        'data_retention' => [
            'performance_metrics' => env('HOMEPAGE_PERFORMANCE_RETENTION', 30), // days
            'error_logs' => env('HOMEPAGE_ERROR_RETENTION', 90),
            'analytics_events' => env('HOMEPAGE_ANALYTICS_RETENTION', 365),
            'alert_logs' => env('HOMEPAGE_ALERT_RETENTION', 180),
        ],

        /*
        |--------------------------------------------------------------------------
        | Security Headers Configuration
        |--------------------------------------------------------------------------
        |
        | Configure HTTP security headers for the homepage.
        |
        */

        'security_headers' => [
            'hsts' => [
                'enabled' => env('HOMEPAGE_HSTS_ENABLED', true),
                'max_age' => env('HOMEPAGE_HSTS_MAX_AGE', 31536000), // 1 year
                'include_subdomains' => env('HOMEPAGE_HSTS_SUBDOMAINS', true),
                'preload' => env('HOMEPAGE_HSTS_PRELOAD', false),
            ],
            'csp' => [
                'enabled' => env('HOMEPAGE_CSP_ENABLED', true),
                'policy' => env('HOMEPAGE_CSP_POLICY', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self';"),
            ],
            'x_frame_options' => env('HOMEPAGE_X_FRAME_OPTIONS', 'SAMEORIGIN'),
            'x_content_type_options' => env('HOMEPAGE_X_CONTENT_TYPE_OPTIONS', 'nosniff'),
            'x_xss_protection' => env('HOMEPAGE_X_XSS_PROTECTION', '1; mode=block'),
            'referrer_policy' => env('HOMEPAGE_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External Services
    |--------------------------------------------------------------------------
    |
    | Configuration for external monitoring and alerting services.
    |
    */

    'external_services' => [
        'sentry' => [
            'enabled' => env('SENTRY_ENABLED', false),
            'dsn' => env('SENTRY_LARAVEL_DSN'),
            'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),
            'release' => env('SENTRY_RELEASE', env('APP_VERSION')),
        ],
        'datadog' => [
            'enabled' => env('DATADOG_ENABLED', false),
            'api_key' => env('DATADOG_API_KEY'),
            'app_key' => env('DATADOG_APP_KEY'),
        ],
        'newrelic' => [
            'enabled' => env('NEWRELIC_ENABLED', false),
            'api_key' => env('NEWRELIC_API_KEY'),
            'app_id' => env('NEWRELIC_APP_ID'),
        ],
        'pagerduty' => [
            'enabled' => env('PAGERDUTY_ENABLED', false),
            'integration_key' => env('PAGERDUTY_INTEGRATION_KEY'),
        ],
    ],
];
