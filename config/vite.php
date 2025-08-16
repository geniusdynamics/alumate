<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vite Development Server
    |--------------------------------------------------------------------------
    |
    | The development server URL for Vite. This should match the URL that
    | your Vite development server is running on.
    |
    */
    'dev_server' => [
        'enabled' => env('APP_ENV') === 'local',
        'url' => env('VITE_DEV_SERVER_URL', 'http://localhost:5100'),
        'ping_timeout' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Vite Build Directory
    |--------------------------------------------------------------------------
    |
    | The directory where Vite will output the built assets. This should
    | match the build.outDir configuration in your vite.config.js file.
    |
    */
    'build_path' => 'build',

    /*
    |--------------------------------------------------------------------------
    | Vite Manifest Path
    |--------------------------------------------------------------------------
    |
    | The path to the Vite manifest file. This file contains information
    | about the built assets and is used by Laravel to serve the correct
    | files in production.
    |
    */
    'manifest' => 'build/manifest.json',

    /*
    |--------------------------------------------------------------------------
    | Hot File Path
    |--------------------------------------------------------------------------
    |
    | The path to the Vite hot file. This file is created when the Vite
    | development server is running and is used by Laravel to determine
    | whether to serve assets from the development server or from the
    | built files.
    |
    */
    'hot_file' => 'public/hot',
];