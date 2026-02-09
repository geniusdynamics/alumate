<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines the retention policies and settings for
    | application logging to prevent database bloat while maintaining
    | necessary audit trails.
    |
    */

    'retention' => [
        // Number of days to retain logs by severity level
        'low_severity_days' => env('LOG_RETENTION_LOW_SEVERITY', 30),
        'medium_severity_days' => env('LOG_RETENTION_MEDIUM_SEVERITY', 90),
        'high_severity_days' => env('LOG_RETENTION_HIGH_SEVERITY', 180),
        'critical_severity_days' => 'indefinite', // Critical logs kept indefinitely

        // Special retention for security-related logs
        'security_category_days' => env('LOG_RETENTION_SECURITY_CATEGORY', 365),

        // Maximum number of logs to keep per category (alternative to time-based retention)
        'max_logs_per_category' => env('LOG_MAX_PER_CATEGORY', 100000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Level Thresholds
    |--------------------------------------------------------------------------
    |
    | Define minimum log levels for different contexts to optimize performance
    | and reduce noise in production environments.
    |
    */

    'thresholds' => [
        'production' => env('LOG_THRESHOLD_PRODUCTION', 'error'),
        'staging' => env('LOG_THRESHOLD_STAGING', 'warning'),
        'development' => env('LOG_THRESHOLD_DEVELOPMENT', 'debug'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channel Configuration
    |--------------------------------------------------------------------------
    |
    | Configure different log channels for various types of logs to ensure
    | proper segregation and handling.
    |
    */

    'channels' => [
        'security' => [
            'driver' => 'single',
            'path' => storage_path('logs/security.log'),
            'level' => 'info',
            'days' => 365, // Keep security logs longer
        ],

        'audit' => [
            'driver' => 'single',
            'path' => storage_path('logs/audit.log'),
            'level' => 'info',
            'days' => 365, // Keep audit logs longer
        ],

        'performance' => [
            'driver' => 'single',
            'path' => storage_path('logs/performance.log'),
            'level' => 'info',
            'days' => 90,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Sanitization
    |--------------------------------------------------------------------------
    |
    | Define patterns to sanitize sensitive data from logs to prevent
    | accidental exposure of confidential information.
    |
    */

    'sanitization' => [
        'enabled' => env('LOG_SANITIZE_ENABLED', true),

        'patterns' => [
            // Credit card numbers
            '/\b(?:\d{4}[-\s]?){3}\d{4}\b/',
            // Social Security Numbers (US)
            '/\b\d{3}-\d{2}-\d{4}\b/',
            // Email addresses
            '/[\w\.-]+@[\w\.-]+\.\w+/',
            // Phone numbers
            '/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/',
            // IP addresses
            '/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/',
        ],

        'replacement' => '[REDACTED]',
    ],
];
