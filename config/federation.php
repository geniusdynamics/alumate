<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Federation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Matrix and ActivityPub federation protocols.
    | This prepares the platform for future federation capabilities.
    |
    */

    'enabled' => env('FEDERATION_ENABLED', false),

    'protocols' => [
        // Enable specific protocols
        // 'matrix',
        // 'activitypub',
    ],

    /*
    |--------------------------------------------------------------------------
    | Matrix Protocol Configuration
    |--------------------------------------------------------------------------
    */
    'matrix' => [
        'server_name' => env('MATRIX_SERVER_NAME', env('APP_DOMAIN', 'localhost')),
        'server_url' => env('MATRIX_SERVER_URL', 'https://matrix.org'),
        'access_token' => env('MATRIX_ACCESS_TOKEN'),
        'user_id' => env('MATRIX_USER_ID'),

        // Room settings
        'default_room_version' => '10',
        'default_power_levels' => [
            'users_default' => 0,
            'events_default' => 0,
            'state_default' => 50,
            'ban' => 50,
            'kick' => 50,
            'redact' => 50,
            'invite' => 0,
        ],

        // Event mapping settings
        'event_mapping' => [
            'include_alumni_extensions' => true,
            'preserve_original_content' => true,
            'enable_rich_formatting' => true,
        ],

        // Encryption settings (for future use)
        'encryption' => [
            'enabled' => false,
            'algorithm' => 'm.megolm.v1.aes-sha2',
            'key_rotation_period' => 604800, // 1 week in seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ActivityPub Protocol Configuration
    |--------------------------------------------------------------------------
    */
    'activitypub' => [
        'server_name' => env('ACTIVITYPUB_SERVER_NAME', env('APP_DOMAIN', 'localhost')),
        'actor_base_url' => env('ACTIVITYPUB_ACTOR_BASE_URL', env('APP_URL').'/federation'),
        'public_key_algorithm' => 'RS256',
        'signature_algorithm' => 'rsa-sha256',

        // Activity settings
        'activity_mapping' => [
            'include_alumni_extensions' => true,
            'enable_custom_context' => true,
            'preserve_original_content' => true,
        ],

        // Delivery settings
        'delivery' => [
            'timeout' => 30,
            'retry_attempts' => 3,
            'retry_delay' => 300, // 5 minutes
            'batch_size' => 100,
        ],

        // Security settings
        'security' => [
            'verify_signatures' => true,
            'require_https' => true,
            'allowed_algorithms' => ['rsa-sha256'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Federation Bridge Settings
    |--------------------------------------------------------------------------
    */
    'bridge' => [
        'auto_federate' => [
            'posts' => false,
            'users' => false,
            'groups' => false,
            'circles' => false,
        ],

        'mapping_cache_ttl' => 3600, // 1 hour
        'activity_log_retention' => 30, // days

        // Rate limiting
        'rate_limits' => [
            'outgoing_activities_per_minute' => 60,
            'incoming_activities_per_minute' => 120,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Identity Mapping
    |--------------------------------------------------------------------------
    */
    'identity' => [
        'matrix_id_format' => '@{username}:{domain}',
        'activitypub_actor_format' => '{base_url}/users/{username}',

        // Future compatibility settings
        'preserve_local_ids' => true,
        'enable_cross_protocol_discovery' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Mapping
    |--------------------------------------------------------------------------
    */
    'content' => [
        'preserve_formatting' => true,
        'convert_mentions' => true,
        'convert_hashtags' => true,
        'include_media_attachments' => true,

        // Alumni-specific extensions
        'alumni_extensions' => [
            'circles' => true,
            'groups' => true,
            'career_data' => false, // Privacy consideration
            'education_data' => false, // Privacy consideration
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy and Security
    |--------------------------------------------------------------------------
    */
    'privacy' => [
        'default_visibility' => 'circles', // public, circles, private
        'allow_public_federation' => false,
        'require_explicit_consent' => true,
        'anonymize_sensitive_data' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Logging
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'log_all_activities' => true,
        'log_level' => 'info',
        'metrics_enabled' => true,
        'health_check_interval' => 300, // 5 minutes
    ],
];
