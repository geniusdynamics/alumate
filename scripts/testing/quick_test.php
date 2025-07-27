<?php

require_once '../../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once '../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🚀 Quick Analytics System Test\n";
echo "==============================\n\n";

try {
    // Test 1: Check if SecurityService methods exist
    echo "1. Testing SecurityService...\n";
    $securityService = app(\App\Services\SecurityService::class);
    
    $methods = ['detectMaliciousRequest', 'detectRateLimitViolation', 'logDataAccess'];
    foreach ($methods as $method) {
        if (method_exists($securityService, $method)) {
            echo "   ✅ {$method}() - EXISTS\n";
        } else {
            echo "   ❌ {$method}() - MISSING\n";
        }
    }
    
    // Test 2: Check Analytics Service
    echo "\n2. Testing AnalyticsService...\n";
    $analyticsService = app(\App\Services\AnalyticsService::class);
    
    if (method_exists($analyticsService, 'getAnalyticsDashboard')) {
        echo "   ✅ AnalyticsService - OK\n";
    } else {
        echo "   ❌ AnalyticsService - MISSING METHODS\n";
    }
    
    // Test 3: Check Models
    echo "\n3. Testing Models...\n";
    $models = [
        'App\Models\Graduate',
        'App\Models\Course', 
        'App\Models\Job',
        'App\Models\KpiDefinition',
        'App\Models\AnalyticsSnapshot'
    ];
    
    foreach ($models as $model) {
        if (class_exists($model)) {
            echo "   ✅ {$model} - EXISTS\n";
        } else {
            echo "   ❌ {$model} - MISSING\n";
        }
    }
    
    // Test 4: Check Routes
    echo "\n4. Testing Routes...\n";
    $router = app('router');
    $routes = $router->getRoutes();
    
    $analyticsRoutes = 0;
    foreach ($routes as $route) {
        if (strpos($route->uri(), 'analytics') !== false) {
            $analyticsRoutes++;
        }
    }
    
    if ($analyticsRoutes > 0) {
        echo "   ✅ Found {$analyticsRoutes} analytics routes\n";
    } else {
        echo "   ❌ No analytics routes found\n";
    }
    
    // Test 5: Database Connection
    echo "\n5. Testing Database...\n";
    try {
        $graduateCount = \App\Models\Graduate::count();
        echo "   ✅ Database connected - {$graduateCount} graduates found\n";
    } catch (Exception $e) {
        echo "   ❌ Database error: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎯 Test Summary:\n";
    echo "================\n";
    echo "If all tests show ✅, the analytics system should work!\n";
    echo "\nNext steps:\n";
    echo "1. Visit: http://localhost:8080/analytics/dashboard\n";
    echo "2. Login with: admin@test.com / password\n";
    echo "3. If you get security errors, run: php scripts/debugging/disable_security_middleware.php\n";

} catch (Exception $e) {
    echo "❌ Critical Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}