<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

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
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        // Homepage-specific log channels
        'homepage-errors' => [
            'driver' => 'daily',
            'path' => storage_path('logs/homepage-errors.log'),
            'level' => env('LOG_LEVEL', 'error'),
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'homepage-alerts' => [
            'driver' => 'daily',
            'path' => storage_path('logs/homepage-alerts.log'),
            'level' => env('LOG_LEVEL', 'alert'),
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'homepage-performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/homepage-performance.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 7,
            'replace_placeholders' => true,
        ],

        // Template-specific log channels (new)
        'template-errors' => [
            'driver' => 'daily',
            'path' => storage_path('logs/template-errors.log'),
            'level' => env('LOG_LEVEL', 'error'),
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'template-warnings' => [
            'driver' => 'daily',
            'path' => storage_path('logs/template-warnings.log'),
            'level' => env('LOG_LEVEL', 'warning'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'template-info' => [
            'driver' => 'daily',
            'path' => storage_path('logs/template-info.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 7,
            'replace_placeholders' => true,
        ],

        'template-alerts' => [
            'driver' => 'daily',
            'path' => storage_path('logs/template-alerts.log'),
            'level' => env('LOG_LEVEL', 'alert'),
            'days' => 90,
            'replace_placeholders' => true,
        ],

        'template-performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/template-performance.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'template-security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/template-security.log'),
            'level' => env('LOG_LEVEL', 'warning'),
            'days' => 90,
            'replace_placeholders' => true,
        ],
    ],

];
