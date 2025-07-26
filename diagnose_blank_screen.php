<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Diagnosing Blank Screen Issue\n";
echo "=================================\n\n";

// Check 1: Frontend Assets
echo "1. Frontend Assets Check:\n";
echo "-------------------------\n";

$checks = [
    'package.json' => file_exists('package.json'),
    'node_modules' => is_dir('node_modules'),
    'vite.config.ts' => file_exists('vite.config.ts'),
    'resources/js/app.ts' => file_exists('resources/js/app.ts'),
    'resources/css/app.css' => file_exists('resources/css/app.css'),
    'public/build' => is_dir('public/build'),
    'public/build/manifest.json' => file_exists('public/build/manifest.json'),
];

foreach ($checks as $item => $exists) {
    echo ($exists ? "✅" : "❌") . " {$item}\n";
}

// Check 2: Laravel Configuration
echo "\n2. Laravel Configuration:\n";
echo "-------------------------\n";

try {
    echo "✅ Laravel app boots successfully\n";
    
    // Check if routes are working
    $router = app('router');
    $routes = $router->getRoutes();
    echo "✅ Routes loaded: " . count($routes) . " routes\n";
    
    // Check database connection
    try {
        \DB::connection()->getPdo();
        echo "✅ Database connection working\n";
    } catch (Exception $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    }
    
    // Check if Inertia is configured
    if (class_exists('Inertia\Inertia')) {
        echo "✅ Inertia.js is available\n";
    } else {
        echo "❌ Inertia.js not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Laravel configuration error: " . $e->getMessage() . "\n";
}

// Check 3: Environment
echo "\n3. Environment Check:\n";
echo "---------------------\n";

$envChecks = [
    'APP_ENV' => env('APP_ENV', 'not set'),
    'APP_DEBUG' => env('APP_DEBUG', 'not set') ? 'true' : 'false',
    'APP_URL' => env('APP_URL', 'not set'),
    'DB_CONNECTION' => env('DB_CONNECTION', 'not set'),
];

foreach ($envChecks as $key => $value) {
    echo "- {$key}: {$value}\n";
}

// Check 4: Common Issues
echo "\n4. Common Issues Check:\n";
echo "-----------------------\n";

// Check if .env exists
if (file_exists('.env')) {
    echo "✅ .env file exists\n";
} else {
    echo "❌ .env file missing - copy .env.example to .env\n";
}

// Check if APP_KEY is set
if (env('APP_KEY')) {
    echo "✅ APP_KEY is set\n";
} else {
    echo "❌ APP_KEY not set - run: php artisan key:generate\n";
}

// Check storage permissions (Windows doesn't have the same permission issues)
if (is_writable('storage')) {
    echo "✅ Storage directory is writable\n";
} else {
    echo "❌ Storage directory not writable\n";
}

// Check 5: Recommendations
echo "\n🚀 Recommendations:\n";
echo "===================\n";

if (!is_dir('node_modules')) {
    echo "1. Install Node.js dependencies: npm install\n";
}

if (!file_exists('public/build/manifest.json')) {
    echo "2. Build frontend assets: npm run build\n";
}

if (!env('APP_KEY')) {
    echo "3. Generate application key: php artisan key:generate\n";
}

echo "4. Clear all caches: php artisan optimize:clear\n";
echo "5. Try development mode: npm run dev (in separate terminal)\n";

echo "\n💡 Quick Fix Command:\n";
echo "=====================\n";
echo "npm install && npm run build && php artisan optimize:clear\n";

echo "\n🌐 Test URLs:\n";
echo "=============\n";
echo "- http://localhost:8000/ (should show welcome page)\n";
echo "- http://localhost:8000/login (should show login form)\n";
echo "- http://localhost:8000/register (should show registration form)\n";

echo "\n📝 If still blank:\n";
echo "==================\n";
echo "1. Open browser developer tools (F12)\n";
echo "2. Check Console tab for JavaScript errors\n";
echo "3. Check Network tab to see if assets are loading\n";
echo "4. Try running 'npm run dev' for development mode\n";