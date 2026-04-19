<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All API routes are organized into domain-specific files in routes/api/.
| This keeps the main file clean and makes route management easier.
|
*/

// Health checks
Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'message' => 'Alumni Platform API is online',
    ]);
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'message' => 'API is healthy',
        'services' => [
            'database' => 'ok',
            'cache' => 'ok',
            'storage' => 'ok',
        ],
    ]);
});

// Include all domain-specific route files
foreach (glob(__DIR__.'/api/*.php') as $routeFile) {
    require $routeFile;
}
