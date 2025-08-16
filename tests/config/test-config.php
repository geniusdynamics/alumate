<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Test Environment Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file defines settings for different test environments
    | and test execution parameters.
    |
    */

    'environments' => [
        'local' => [
            'database' => [
                'connection' => 'sqlite',
                'database' => ':memory:',
            ],
            'cache' => 'array',
            'session' => 'array',
            'queue' => 'sync',
            'mail' => 'array',
        ],

        'ci' => [
            'database' => [
                'connection' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'database' => env('DB_DATABASE', 'graduate_tracking_test'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
            ],
            'cache' => 'redis',
            'session' => 'redis',
            'queue' => 'redis',
            'mail' => 'array',
        ],

        'staging' => [
            'database' => [
                'connection' => 'pgsql',
                'host' => env('DB_HOST'),
                'database' => env('DB_DATABASE').'_test',
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
            ],
            'cache' => 'redis',
            'session' => 'redis',
            'queue' => 'redis',
            'mail' => 'log',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Suite Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for different test suites and their execution parameters.
    |
    */

    'suites' => [
        'unit' => [
            'directory' => 'tests/Unit',
            'parallel' => true,
            'memory_limit' => '512M',
            'time_limit' => 300, // 5 minutes
            'coverage_required' => 90,
        ],

        'integration' => [
            'directory' => 'tests/Integration',
            'parallel' => false, // Database transactions may conflict
            'memory_limit' => '1G',
            'time_limit' => 600, // 10 minutes
            'coverage_required' => 80,
        ],

        'feature' => [
            'directory' => 'tests/Feature',
            'parallel' => true,
            'memory_limit' => '1G',
            'time_limit' => 900, // 15 minutes
            'coverage_required' => 85,
        ],

        'end_to_end' => [
            'directory' => 'tests/EndToEnd',
            'parallel' => false, // Sequential execution for user journeys
            'memory_limit' => '2G',
            'time_limit' => 1800, // 30 minutes
            'coverage_required' => 70,
        ],

        'performance' => [
            'directory' => 'tests/Performance',
            'parallel' => false,
            'memory_limit' => '4G',
            'time_limit' => 3600, // 1 hour
            'coverage_required' => 60,
        ],

        'security' => [
            'directory' => 'tests/Security',
            'parallel' => true,
            'memory_limit' => '1G',
            'time_limit' => 1200, // 20 minutes
            'coverage_required' => 95,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Coverage Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for code coverage analysis and reporting.
    |
    */

    'coverage' => [
        'enabled' => env('TEST_COVERAGE', true),
        'driver' => 'xdebug', // xdebug, pcov, phpdbg
        'output_formats' => ['html', 'xml', 'clover', 'text'],
        'output_directory' => 'tests/reports/coverage',

        'thresholds' => [
            'high' => 90,
            'medium' => 70,
            'low' => 50,
        ],

        'exclude_directories' => [
            'vendor',
            'node_modules',
            'storage',
            'bootstrap/cache',
            'tests',
        ],

        'exclude_files' => [
            'server.php',
            'artisan',
            '*.blade.php',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for performance and load testing.
    |
    */

    'performance' => [
        'benchmarks' => [
            'database_query_time' => 100, // milliseconds
            'api_response_time' => 200, // milliseconds
            'page_load_time' => 500, // milliseconds
            'memory_usage' => '256M',
        ],

        'load_testing' => [
            'concurrent_users' => 100,
            'test_duration' => 300, // seconds
            'ramp_up_time' => 60, // seconds
        ],

        'stress_testing' => [
            'max_concurrent_users' => 1000,
            'test_duration' => 600, // seconds
            'acceptable_failure_rate' => 5, // percent
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for security vulnerability testing.
    |
    */

    'security' => [
        'vulnerability_tests' => [
            'sql_injection' => true,
            'xss' => true,
            'csrf' => true,
            'authentication_bypass' => true,
            'authorization_bypass' => true,
            'session_fixation' => true,
            'brute_force' => true,
            'directory_traversal' => true,
            'file_upload' => true,
            'information_disclosure' => true,
        ],

        'security_headers' => [
            'x-frame-options',
            'x-content-type-options',
            'x-xss-protection',
            'strict-transport-security',
            'content-security-policy',
        ],

        'password_policies' => [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for test reporting and notifications.
    |
    */

    'reporting' => [
        'formats' => ['json', 'html', 'xml', 'junit'],
        'output_directory' => 'tests/reports',

        'notifications' => [
            'enabled' => env('TEST_NOTIFICATIONS', false),
            'channels' => ['slack', 'email'],
            'on_failure' => true,
            'on_success' => false,
            'on_coverage_drop' => true,
        ],

        'metrics' => [
            'track_execution_time' => true,
            'track_memory_usage' => true,
            'track_database_queries' => true,
            'track_api_calls' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Management Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for test data management and factories.
    |
    */

    'data' => [
        'factories' => [
            'default_count' => 10,
            'large_dataset_count' => 1000,
            'performance_dataset_count' => 10000,
        ],

        'seeders' => [
            'run_before_tests' => true,
            'cleanup_after_tests' => true,
        ],

        'fixtures' => [
            'directory' => 'tests/fixtures',
            'auto_load' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Parallel Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for parallel test execution.
    |
    */

    'parallel' => [
        'enabled' => env('TEST_PARALLEL', false),
        'processes' => env('TEST_PARALLEL_PROCESSES', 4),
        'database_template' => env('DB_DATABASE', 'graduate_tracking').'_test_template',
    ],

    /*
    |--------------------------------------------------------------------------
    | Continuous Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Settings specific to CI/CD environments.
    |
    */

    'ci' => [
        'timeout' => 3600, // 1 hour
        'retry_failed_tests' => 3,
        'fail_fast' => false,
        'generate_artifacts' => true,

        'quality_gates' => [
            'min_coverage' => 80,
            'max_failure_rate' => 5,
            'max_execution_time' => 1800, // 30 minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mock and Stub Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for external service mocking and stubbing.
    |
    */

    'mocking' => [
        'external_apis' => [
            'payment_gateway' => true,
            'email_service' => true,
            'sms_service' => true,
            'file_storage' => true,
        ],

        'database_mocking' => [
            'enabled' => false,
            'mock_slow_queries' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Debugging Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for test debugging and troubleshooting.
    |
    */

    'debugging' => [
        'enabled' => env('TEST_DEBUG', false),
        'log_level' => 'debug',
        'log_queries' => true,
        'log_requests' => true,
        'dump_on_failure' => true,

        'profiling' => [
            'enabled' => false,
            'memory_profiling' => true,
            'time_profiling' => true,
        ],
    ],
];
