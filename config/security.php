<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Login Security Settings
    |--------------------------------------------------------------------------
    */
    'max_login_attempts' => env('SECURITY_MAX_LOGIN_ATTEMPTS', 5),
    'lockout_duration' => env('SECURITY_LOCKOUT_DURATION', 30), // minutes
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit_authenticated' => env('SECURITY_RATE_LIMIT_AUTH', 100), // per minute
    'rate_limit_unauthenticated' => env('SECURITY_RATE_LIMIT_UNAUTH', 30), // per minute
    
    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    */
    'session_timeout' => env('SECURITY_SESSION_TIMEOUT', 120), // minutes
    'track_suspicious_sessions' => env('SECURITY_TRACK_SUSPICIOUS', true),
    
    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    */
    'two_factor_required_roles' => [
        'super-admin',
        'institution-admin',
    ],
    'two_factor_recovery_codes_count' => 8,
    
    /*
    |--------------------------------------------------------------------------
    | Backup Settings
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        'compression_enabled' => env('BACKUP_COMPRESSION', true),
        'storage_disk' => env('BACKUP_STORAGE_DISK', 'local'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security Monitoring
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'log_data_access' => env('SECURITY_LOG_DATA_ACCESS', true),
        'detect_malicious_requests' => env('SECURITY_DETECT_MALICIOUS', true),
        'alert_critical_events' => env('SECURITY_ALERT_CRITICAL', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | System Health Monitoring
    |--------------------------------------------------------------------------
    */
    'health_check' => [
        'database_timeout' => 5, // seconds
        'cache_timeout' => 2, // seconds
        'storage_timeout' => 3, // seconds
        'memory_warning_threshold' => 80, // percentage
        'memory_critical_threshold' => 90, // percentage
        'disk_warning_threshold' => 80, // percentage
        'disk_critical_threshold' => 90, // percentage
    ],
];