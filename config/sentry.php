<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    // The release version of your application
    'release' => env('SENTRY_RELEASE'),

    // When left empty or `null` the Laravel environment will be used
    'environment' => env('SENTRY_ENVIRONMENT'),

    // @see: https://docs.sentry.io/platforms/php/guides/laravel/configuration/options/#sample-rate
    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.0),

];