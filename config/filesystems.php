<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk for Production
    |--------------------------------------------------------------------------
    |
    | The default disk used for file uploads in production environment.
    | Supports: s3, spaces, local
    |
    */

    'default_storage_disk' => env('FILESYSTEM_STORAGE_DISK', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
            // CDN Configuration
            'cdn_url' => env('AWS_CDN_URL'), // CloudFront or custom CDN URL
            'cdn_enabled' => env('AWS_CDN_ENABLED', false),
        ],

        // DigitalOcean Spaces Configuration
        'spaces' => [
            'driver' => 's3',
            'key' => env('DO_SPACES_KEY'),
            'secret' => env('DO_SPACES_SECRET'),
            'region' => env('DO_SPACES_REGION', 'nyc3'),
            'bucket' => env('DO_SPACES_BUCKET'),
            'endpoint' => env('DO_SPACES_ENDPOINT', 'https://nyc3.digitaloceanspaces.com'),
            'url' => env('DO_SPACES_URL'), // Custom domain for Spaces
            'use_path_style_endpoint' => false,
            'throw' => false,
            'report' => false,
            // CDN Configuration
            'cdn_url' => env('DO_SPACES_CDN_URL'), // Spaces CDN endpoint
            'cdn_enabled' => env('DO_SPACES_CDN_ENABLED', false),
        ],

        // Backblaze B2 Configuration (alternative S3-compatible)
        'b2' => [
            'driver' => 's3',
            'key' => env('B2_KEY_ID'),
            'secret' => env('B2_APPLICATION_KEY'),
            'region' => env('B2_REGION', 'us-west-002'),
            'bucket' => env('B2_BUCKET'),
            'endpoint' => env('B2_ENDPOINT'),
            'url' => env('B2_URL'),
            'use_path_style_endpoint' => false,
            'throw' => false,
            'report' => false,
            'cdn_url' => env('B2_CDN_URL'),
            'cdn_enabled' => env('B2_CDN_ENABLED', false),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configure maximum file upload sizes and allowed file types.
    |
    */

    'upload_max_size' => env('FILESYSTEM_UPLOAD_MAX_SIZE', 100 * 1024 * 1024), // 100MB

    'default_quota_bytes' => env('FILESYSTEM_DEFAULT_QUOTA', 5 * 1024 * 1024 * 1024), // 5GB

    /*
    |--------------------------------------------------------------------------
    | Virus Scanning Configuration
    |--------------------------------------------------------------------------
    |
    | Configure virus scanning for uploaded files.
    |
    */

    'virus_scanning' => [
        'enabled' => env('VIRUS_SCANNING_ENABLED', true),
        'driver' => env('VIRUS_SCANNING_DRIVER', 'clamav'), // clamav, mock
        'auto_delete_infected' => env('VIRUS_SCANNING_AUTO_DELETE', false),
        'clamav_socket' => env('CLAMAV_SOCKET', '/var/run/clamav/clamd.ctl'),
        'clamav_host' => env('CLAMAV_HOST', 'localhost'),
        'clamav_port' => env('CLAMAV_PORT', 3310),
    ],

    /*
    |--------------------------------------------------------------------------
    | Signed URL Configuration
    |--------------------------------------------------------------------------
    |
    | Configure signed URL expiration for private files.
    |
    */

    'signed_url_expiration' => env('FILESYSTEM_SIGNED_URL_EXPIRATION', 15), // minutes
    'max_signed_url_expiration' => env('FILESYSTEM_MAX_SIGNED_URL_EXPIRATION', 60), // minutes

    /*
    |--------------------------------------------------------------------------
    | Image Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default image processing settings.
    |
    */

    'image_processing' => [
        'max_width' => env('IMAGE_MAX_WIDTH', 2048),
        'max_height' => env('IMAGE_MAX_HEIGHT', 2048),
        'quality' => env('IMAGE_QUALITY', 85),
        'auto_optimize' => env('IMAGE_AUTO_OPTIMIZE', true),
        'generate_webp' => env('IMAGE_GENERATE_WEBP', true),
        'thumbnail_sizes' => [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 400, 'height' => 300],
            'medium' => ['width' => 800, 'height' => 600],
            'large' => ['width' => 1600, 'height' => 1200],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Global CDN settings for public file serving.
    |
    */

    'cdn' => [
        'enabled' => env('CDN_ENABLED', false),
        'url' => env('CDN_URL'),
        'cache_duration' => env('CDN_CACHE_DURATION', 86400), // 24 hours
        'image_optimization' => env('CDN_IMAGE_OPTIMIZATION', true),
        'video_streaming' => env('CDN_VIDEO_STREAMING', false),
    ],

];
