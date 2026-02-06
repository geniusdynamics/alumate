<?php

/**
 * Production Logging Configuration
 * 
 * Comprehensive logging setup for production environment
 * with rotation, multiple channels, and structured logging.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Levels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log levels for your application. Out of the
    | box, Laravel uses the Monolog PHP logging library, which provides
    | a variety of powerful log handlers / formatters to use.
    |
    | Available Levels: debug, info, notice, warning, error, critical, alert, emergency
    |
    */

    'levels' => [
        'debug'     => 100,
        'info'      => 200,
        'notice'    => 250,
        'warning'   => 300,
        'error'     => 400,
        'critical'  => 500,
        'alert'     => 550,
        'emergency' => 600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | comes with Monolog logging, which supports various log handlers and
    | formatters. Feel free to add additional channels as needed.
    |
    */

    'channels' => [
        // Main application log with daily rotation
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
            'ignore_exceptions' => false,
        ],

        // Daily rotating log for production
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'warning'),
            'days' => env('LOG_MAX_FILES', 30),
        ],

        // Separate error log for quick access
        'error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/error.log'),
            'level' => 'error',
            'days' => 30,
        ],

        // Combined access log
        'access' => [
            'driver' => 'daily',
            'path' => storage_path('logs/access.log'),
            'level' => 'info',
            'days' => 90,
        ],

        // Database query log (for debugging)
        'queries' => [
            'driver' => 'daily',
            'path' => storage_path('logs/queries.log'),
            'level' => 'debug',
            'days' => 7,
        ],

        // Security events log
        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => 'info',
            'days' => 365,
        ],

        // Authentication events log
        'auth' => [
            'driver' => 'daily',
            'path' => storage_path('logs/auth.log'),
            'level' => 'info',
            'days' => 90,
        ],

        // Performance monitoring log
        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => 'warning',
            'days' => 30,
        ],

        // Tenant-specific logging
        'tenant' => [
            'driver' => 'daily',
            'path' => storage_path('logs/tenant.log'),
            'level' => env('LOG_LEVEL', 'warning'),
            'days' => 90,
        ],

        // API request/response logging
        'api' => [
            'driver' => 'daily',
            'path' => storage_path('logs/api.log'),
            'level' => 'info',
            'days' => 30,
        ],

        // Job/queue execution log
        'jobs' => [
            'driver' => 'daily',
            'path' => storage_path('logs/jobs.log'),
            'level' => 'info',
            'days' => 14,
        ],

        // Email sending log
        'mail' => [
            'driver' => 'daily',
            'path' => storage_path('logs/mail.log'),
            'level' => 'info',
            'days' => 30,
        ],

        // Monitoring and alerts log
        'monitoring' => [
            'driver' => 'daily',
            'path' => storage_path('logs/monitoring.log'),
            'level' => 'info',
            'days' => 90,
        ],

        // Single file log for development
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
        ],

        // Slack integration for alerts
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_CHANNEL', 'Laravel Log'),
            'emoji' => ':boom:',
            'level' => 'critical',
            'replace_placeholders' => true,
        ],

        // Papertrail integration
        'papertrail' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => \Monolog\Handler\SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'facility' => \LOG_USER,
            ],
        ],

        // Stackdriver (GCP) integration
        'stackdriver' => [
            'driver' => 'stackdriver',
            'level' => 'debug',
        ],

        // Null channel for disabling logs
        'null' => [
            'driver' => 'monolog',
            'handler' => \Monolog\Handler\NullHandler::class,
        ],

        // Emergency log - always writes, never fails
        'emergency' => [
            'driver' => 'single',
            'path' => storage_path('logs/emergency.log'),
            'level' => 'emergency',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Formatters
    |--------------------------------------------------------------------------
    |
    | Here you can customize the log formatters for different channels.
    | Each formatter can be configured to format logs appropriately.
    |
    */

    'formatters' => [
        \Monolog\Formatter\LineFormatter::class => [
            'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'dateFormat' => 'Y-m-d H:i:s.u',
            'allowInlineLineBreaks' => true,
            'ignoreEmptyContextAndExtra' => true,
        ],

        \Monolog\Formatter\JsonFormatter::class => [
            'format' => 'json',
            'batchMode' => \Monolog\Formatter\JsonFormatter::BATCH_MODE_NEWLINES,
            'ignoreEmptyContextAndExtra' => true,
        ],

        \Monolog\Formatter\HtmlFormatter::class => [
            'format' => 'html',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Processors
    |--------------------------------------------------------------------------
    |
    | Here you can add processors to modify log records before they are written.
    | Processors are useful for adding contextual information like request IDs,
    | user information, and other custom data.
    |
    */

    'processors' => [
        \Monolog\Processor\UidProcessor::class,
        \Monolog\Processor\HostnameProcessor::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant-Aware Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant-specific logging features.
    |
    */

    'tenant' => [
        'enabled' => env('TENANCY_LOG_ENABLED', true),
        'separate_channels' => env('TENANCY_SEPARATE_LOG_CHANNELS', false),
        'tenant_id_context' => env('TENANCY_LOG_TENANT_ID', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Data Masking
    |--------------------------------------------------------------------------
    |
    | Configure patterns for masking sensitive data in logs.
    |
    */

    'masking' => [
        'enabled' => env('LOG_MASKING_ENABLED', true),
        'patterns' => [
            // Passwords
            '/"password"\s*:\s*"[^"]*"/i' => '"password":"[MASKED]"',
            '/password\s*=\s*[^\s]+/i' => 'password=[MASKED]',
            
            // API Keys
            '/api[_-]?key["\']?\s*[:=]\s*["\']?[a-zA-Z0-9-_]{16,}["\']?/i' => 'api_key=[MASKED]',
            
            // Tokens
            '/(Bearer|token|bearer|token)\s+[a-zA-Z0-9\-\._~\+\/]+=*/i' => '$1 [MASKED]',
            
            // Credit Cards (basic pattern)
            '/\b(?:\d[ -]*?){13,16}\b/' => '[CREDIT_CARD_MASKED]',
            
            // Social Security Numbers
            '/\b\d{3}-\d{2}-\d{4}\b/' => '[SSN_MASKED]',
            
            // Email addresses in sensitive contexts
            '/email["\']?\s*[:=]\s*["\']?[^"\']+["\']?/i' => 'email=[MASKED]',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for audit logging - tracking important changes and actions.
    |
    */

    'audit' => [
        'enabled' => env('AUDIT_LOG_ENABLED', true),
        'events' => [
            'created',
            'updated',
            'deleted',
            'restored',
            'force_deleted',
            'login',
            'logout',
            'password_reset',
        ],
        'ignore_fields' => [
            'id',
            'created_at',
            'updated_at',
        ],
    ],
];
