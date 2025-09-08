<?php
// ABOUTME: Configuration file for hybrid tenancy system with schema-based and global data management
// ABOUTME: Defines settings for tenant resolution, schema management, caching, security, and performance optimization

return [

    /*
    |--------------------------------------------------------------------------
    | Tenancy Mode
    |--------------------------------------------------------------------------
    |
    | Defines the tenancy architecture mode:
    | - 'hybrid': Mixed global and schema-based tenancy (recommended)
    | - 'schema': Pure schema-based tenancy
    | - 'global': Global tenancy with tenant_id columns
    |
    */
    'mode' => env('TENANCY_MODE', 'hybrid'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Resolution
    |--------------------------------------------------------------------------
    |
    | Configuration for how tenants are identified and resolved from requests.
    |
    */
    'resolution' => [
        
        // Order of resolution methods (first match wins)
        'methods' => [
            'subdomain',
            'header',
            'parameter',
            'session',
            'path',
        ],
        
        // Subdomain resolution settings
        'subdomain' => [
            'enabled' => env('TENANCY_SUBDOMAIN_ENABLED', true),
            'excluded' => ['www', 'api', 'admin', 'app', 'mail', 'ftp'],
            'domain' => env('APP_DOMAIN', 'localhost'),
        ],
        
        // Header resolution settings
        'header' => [
            'enabled' => env('TENANCY_HEADER_ENABLED', true),
            'name' => 'X-Tenant-ID',
        ],
        
        // Parameter resolution settings
        'parameter' => [
            'enabled' => env('TENANCY_PARAMETER_ENABLED', true),
            'name' => 'tenant_id',
            'route_parameter' => 'tenant',
        ],
        
        // Session resolution settings
        'session' => [
            'enabled' => env('TENANCY_SESSION_ENABLED', true),
            'key' => 'current_tenant_context',
        ],
        
        // Path resolution settings
        'path' => [
            'enabled' => env('TENANCY_PATH_ENABLED', false),
            'prefix' => 'tenant',
        ],
        
        // Fallback tenant (for development/testing)
        'fallback' => [
            'enabled' => env('TENANCY_FALLBACK_ENABLED', false),
            'tenant_id' => env('TENANCY_FALLBACK_TENANT_ID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema Management
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant schema creation, management, and operations.
    |
    */
    'schema' => [
        
        // Schema naming
        'prefix' => env('TENANCY_SCHEMA_PREFIX', 'tenant_'),
        'max_length' => 63, // PostgreSQL limit
        
        // Schema creation settings
        'auto_create' => env('TENANCY_SCHEMA_AUTO_CREATE', false),
        'auto_migrate' => env('TENANCY_SCHEMA_AUTO_MIGRATE', false),
        
        // Required tables in each tenant schema
        'required_tables' => [
            'users',
            'courses',
            'enrollments',
            'assignments',
            'submissions',
            'grades',
            'announcements',
            'discussions',
            'files',
            'settings',
        ],
        
        // Schema validation settings
        'validation' => [
            'enabled' => env('TENANCY_SCHEMA_VALIDATION', true),
            'check_tables' => true,
            'check_indexes' => true,
            'check_constraints' => true,
        ],
        
        // Schema backup settings
        'backup' => [
            'enabled' => env('TENANCY_SCHEMA_BACKUP', true),
            'before_migration' => true,
            'before_drop' => true,
            'retention_days' => 30,
        ],
        
        // Connection pooling
        'connection_pooling' => [
            'enabled' => env('TENANCY_CONNECTION_POOLING', true),
            'max_connections' => 10,
            'idle_timeout' => 300, // seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Data Management
    |--------------------------------------------------------------------------
    |
    | Configuration for global tables and cross-tenant data management.
    |
    */
    'global' => [
        
        // Global tables (always in public schema)
        'tables' => [
            'tenants',
            'global_users',
            'user_tenant_memberships',
            'global_courses',
            'tenant_course_offerings',
            'super_admin_analytics',
            'data_sync_logs',
            'audit_trail',
            'migrations',
            'failed_jobs',
        ],
        
        // Cross-tenant synchronization
        'sync' => [
            'enabled' => env('TENANCY_GLOBAL_SYNC', true),
            'batch_size' => 100,
            'retry_attempts' => 3,
            'retry_delay' => 60, // seconds
            'conflict_resolution' => 'latest_wins', // latest_wins, manual, skip
        ],
        
        // Global user management
        'users' => [
            'auto_create_global' => true,
            'sync_profile_changes' => true,
            'allow_cross_tenant_login' => true,
        ],
        
        // Global course catalog
        'courses' => [
            'auto_sync_offerings' => true,
            'allow_tenant_customization' => true,
            'require_approval' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for caching tenant context, schema information, and data.
    |
    */
    'cache' => [
        
        // Cache driver (uses default if not specified)
        'driver' => env('TENANCY_CACHE_DRIVER'),
        
        // Cache TTL settings (in seconds)
        'ttl' => [
            'tenant_context' => 1800, // 30 minutes
            'schema_info' => 3600,     // 1 hour
            'user_access' => 1800,     // 30 minutes
            'global_data' => 3600,     // 1 hour
        ],
        
        // Cache key prefixes
        'prefixes' => [
            'tenant' => 'tenant:',
            'schema' => 'schema:',
            'user' => 'user:',
            'global' => 'global:',
        ],
        
        // Cache invalidation
        'invalidation' => [
            'auto_invalidate' => true,
            'cascade_invalidate' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for tenant isolation and access control.
    |
    */
    'security' => [
        
        // Tenant isolation
        'strict_isolation' => env('TENANCY_STRICT_ISOLATION', true),
        'prevent_cross_tenant_access' => true,
        'validate_user_access' => true,
        
        // Schema security
        'schema_permissions' => [
            'restrict_public_access' => true,
            'require_explicit_grants' => true,
            'audit_schema_access' => true,
        ],
        
        // Data encryption
        'encryption' => [
            'encrypt_sensitive_fields' => env('TENANCY_ENCRYPT_SENSITIVE', true),
            'sensitive_fields' => [
                'email',
                'phone',
                'ssn',
                'payment_info',
            ],
        ],
        
        // Rate limiting
        'rate_limiting' => [
            'enabled' => env('TENANCY_RATE_LIMITING', true),
            'tenant_switching' => [
                'max_attempts' => 10,
                'decay_minutes' => 60,
            ],
            'schema_operations' => [
                'max_attempts' => 5,
                'decay_minutes' => 60,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for optimizing tenant performance and resource usage.
    |
    */
    'performance' => [
        
        // Query optimization
        'query_optimization' => [
            'use_prepared_statements' => true,
            'enable_query_cache' => true,
            'optimize_cross_tenant_queries' => true,
        ],
        
        // Connection management
        'connections' => [
            'max_concurrent_operations' => 5,
            'operation_timeout' => 300, // seconds
            'connection_retry_attempts' => 3,
        ],
        
        // Memory management
        'memory' => [
            'max_context_cache_size' => 100, // number of contexts
            'cleanup_interval' => 3600,      // seconds
            'gc_probability' => 1,           // 1 in 100 requests
        ],
        
        // Monitoring
        'monitoring' => [
            'enabled' => env('TENANCY_MONITORING', true),
            'log_slow_operations' => true,
            'slow_operation_threshold' => 1000, // milliseconds
            'track_memory_usage' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for migrating between tenancy models and schema management.
    |
    */
    'migration' => [
        
        // Migration settings
        'batch_size' => env('TENANCY_MIGRATION_BATCH_SIZE', 100),
        'timeout' => env('TENANCY_MIGRATION_TIMEOUT', 3600), // seconds
        'memory_limit' => env('TENANCY_MIGRATION_MEMORY_LIMIT', '512M'),
        
        // Backup settings
        'create_backup' => true,
        'backup_compression' => true,
        'backup_retention' => 30, // days
        
        // Rollback settings
        'enable_rollback' => true,
        'auto_rollback_on_error' => false,
        'rollback_timeout' => 1800, // seconds
        
        // Validation settings
        'validate_before_migration' => true,
        'validate_after_migration' => true,
        'strict_validation' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit and Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for audit trails and logging tenant operations.
    |
    */
    'audit' => [
        
        // Audit settings
        'enabled' => env('TENANCY_AUDIT_ENABLED', true),
        'log_all_operations' => false,
        'log_sensitive_operations' => true,
        
        // Logged operations
        'operations' => [
            'tenant_creation' => true,
            'tenant_deletion' => true,
            'schema_operations' => true,
            'context_switching' => true,
            'user_access_changes' => true,
            'data_synchronization' => true,
        ],
        
        // Audit data retention
        'retention' => [
            'days' => 365,
            'auto_cleanup' => true,
            'compress_old_logs' => true,
        ],
        
        // Audit data export
        'export' => [
            'enabled' => true,
            'formats' => ['json', 'csv'],
            'encryption' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development and Testing
    |--------------------------------------------------------------------------
    |
    | Settings specific to development and testing environments.
    |
    */
    'development' => [
        
        // Debug settings
        'debug_mode' => env('TENANCY_DEBUG', false),
        'log_all_queries' => env('TENANCY_LOG_QUERIES', false),
        'show_tenant_info' => env('TENANCY_SHOW_INFO', false),
        
        // Testing settings
        'testing' => [
            'use_test_tenants' => env('TENANCY_USE_TEST_TENANTS', false),
            'auto_cleanup_test_data' => true,
            'mock_external_services' => true,
        ],
        
        // Seeding settings
        'seeding' => [
            'create_demo_tenants' => env('TENANCY_CREATE_DEMO', false),
            'demo_tenant_count' => 3,
            'seed_demo_data' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for integrating with external services and APIs.
    |
    */
    'integrations' => [
        
        // Queue settings
        'queue' => [
            'enabled' => env('TENANCY_QUEUE_ENABLED', true),
            'connection' => env('TENANCY_QUEUE_CONNECTION'),
            'tenant_aware_jobs' => true,
        ],
        
        // Broadcasting settings
        'broadcasting' => [
            'enabled' => env('TENANCY_BROADCASTING_ENABLED', false),
            'tenant_channels' => true,
            'cross_tenant_broadcasting' => false,
        ],
        
        // Storage settings
        'storage' => [
            'tenant_specific_disks' => env('TENANCY_TENANT_STORAGE', false),
            'shared_storage' => true,
            'storage_isolation' => 'path', // path, disk, bucket
        ],
        
        // Mail settings
        'mail' => [
            'tenant_specific_config' => env('TENANCY_TENANT_MAIL', false),
            'from_address_per_tenant' => false,
            'template_per_tenant' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Feature toggles for experimental or optional functionality.
    |
    */
    'features' => [
        
        // Experimental features
        'experimental' => [
            'auto_schema_optimization' => env('TENANCY_AUTO_OPTIMIZE', false),
            'predictive_caching' => env('TENANCY_PREDICTIVE_CACHE', false),
            'ai_tenant_insights' => env('TENANCY_AI_INSIGHTS', false),
        ],
        
        // Optional features
        'optional' => [
            'tenant_analytics' => env('TENANCY_ANALYTICS', true),
            'cross_tenant_search' => env('TENANCY_CROSS_SEARCH', false),
            'tenant_marketplace' => env('TENANCY_MARKETPLACE', false),
        ],
        
        // Beta features
        'beta' => [
            'real_time_sync' => env('TENANCY_REALTIME_SYNC', false),
            'distributed_tenancy' => env('TENANCY_DISTRIBUTED', false),
            'tenant_federation' => env('TENANCY_FEDERATION', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configuration for handling tenant-related errors and exceptions.
    |
    */
    'error_handling' => [
        
        // Error reporting
        'report_errors' => env('TENANCY_REPORT_ERRORS', true),
        'error_notification' => env('TENANCY_ERROR_NOTIFICATION', false),
        
        // Fallback behavior
        'fallback_to_global' => env('TENANCY_FALLBACK_GLOBAL', false),
        'graceful_degradation' => true,
        
        // Recovery settings
        'auto_recovery' => [
            'enabled' => env('TENANCY_AUTO_RECOVERY', true),
            'max_attempts' => 3,
            'recovery_delay' => 5, // seconds
        ],
        
        // Error pages
        'custom_error_pages' => [
            'tenant_not_found' => 'errors.tenant-not-found',
            'access_denied' => 'errors.tenant-access-denied',
            'schema_error' => 'errors.schema-error',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode
    |--------------------------------------------------------------------------
    |
    | Settings for tenant-specific maintenance and system operations.
    |
    */
    'maintenance' => [
        
        // Maintenance settings
        'allow_tenant_maintenance' => env('TENANCY_TENANT_MAINTENANCE', true),
        'maintenance_bypass_roles' => ['super_admin', 'system_admin'],
        
        // Scheduled maintenance
        'scheduled_maintenance' => [
            'enabled' => env('TENANCY_SCHEDULED_MAINTENANCE', false),
            'notification_hours' => 24,
            'max_duration_hours' => 4,
        ],
        
        // Emergency maintenance
        'emergency_maintenance' => [
            'enabled' => true,
            'auto_enable_on_errors' => false,
            'error_threshold' => 10, // errors per minute
        ],
    ],

];