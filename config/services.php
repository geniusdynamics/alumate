<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sentry' => [
        'dsn' => env('SENTRY_LARAVEL_DSN', 'https://5b2c3cb3a5eb423893d58842bbe71483@app1.genius2.mrmarkuz.ddnss.eu/1'),
        'environment' => env('APP_ENV', 'production'),
        'release' => env('APP_VERSION', '1.0.0'),
        'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
        'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),
    ],

    'monitoring' => [
        'alert_email' => env('MONITORING_ALERT_EMAIL'),
        'slack_webhook' => env('MONITORING_SLACK_WEBHOOK'),
        'pagerduty_key' => env('PAGERDUTY_INTEGRATION_KEY'),
        'datadog_api_key' => env('DATADOG_API_KEY'),
        'newrelic_api_key' => env('NEWRELIC_API_KEY'),
    ],

    'vapid' => [
        'public_key' => env('VAPID_PUBLIC_KEY', 'demo-vapid-public-key-for-development'),
        'private_key' => env('VAPID_PRIVATE_KEY', 'demo-vapid-private-key-for-development'),
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@alumate.com'),
    ],

    'google_analytics' => [
        'measurement_id' => env('GA_MEASUREMENT_ID'),
        'property_id' => env('GA_PROPERTY_ID'),
        'api_secret' => env('GA_API_SECRET'),
        'service_account_json' => env('GA_SERVICE_ACCOUNT_JSON'),
        'service_account_key_file' => env('GA_SERVICE_ACCOUNT_KEY_FILE'),
        'timeout' => env('GA_TIMEOUT', 10),
        'connect_timeout' => env('GA_CONNECT_TIMEOUT', 5),
    ],

    'matomo' => [
        'url' => env('MATOMO_URL'),
        'site_id' => env('MATOMO_SITE_ID'),
        'token_auth' => env('MATOMO_TOKEN_AUTH'),
        'timeout' => env('MATOMO_TIMEOUT', 10),
        'connect_timeout' => env('MATOMO_CONNECT_TIMEOUT', 5),
    ],

];
