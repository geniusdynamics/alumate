<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Analytics Routes...\n\n";

try {
    // Get all registered routes
    $router = app('router');
    $routes = $router->getRoutes();
    
    $analyticsRoutes = [];
    
    foreach ($routes as $route) {
        $uri = $route->uri();
        if (strpos($uri, 'analytics') !== false) {
            $analyticsRoutes[] = [
                'method' => implode('|', $route->methods()),
                'uri' => $uri,
                'name' => $route->getName(),
                'action' => $route->getActionName(),
            ];
        }
    }
    
    echo "📍 Found " . count($analyticsRoutes) . " analytics routes:\n\n";
    
    foreach ($analyticsRoutes as $route) {
        echo "- {$route['method']} /{$route['uri']} -> {$route['action']}\n";
        if ($route['name']) {
            echo "  Name: {$route['name']}\n";
        }
        echo "\n";
    }
    
    // Test if controllers exist
    echo "🔍 Checking Controllers:\n";
    
    $controllers = [
        'App\Http\Controllers\AnalyticsController',
    ];
    
    foreach ($controllers as $controller) {
        if (class_exists($controller)) {
            echo "✅ {$controller} - EXISTS\n";
            
            // Check methods
            $reflection = new ReflectionClass($controller);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            $publicMethods = array_filter($methods, function($method) {
                return !$method->isConstructor() && !$method->isDestructor() && $method->class === $method->getDeclaringClass()->getName();
            });
            
            echo "   Methods: " . implode(', ', array_map(function($m) { return $m->getName(); }, $publicMethods)) . "\n";
        } else {
            echo "❌ {$controller} - NOT FOUND\n";
        }
    }
    
    echo "\n🔍 Checking Services:\n";
    
    $services = [
        'App\Services\AnalyticsService',
        'App\Services\ReportBuilderService',
    ];
    
    foreach ($services as $service) {
        if (class_exists($service)) {
            echo "✅ {$service} - EXISTS\n";
        } else {
            echo "❌ {$service} - NOT FOUND\n";
        }
    }
    
    echo "\n🔍 Checking Models:\n";
    
    $models = [
        'App\Models\AnalyticsSnapshot',
        'App\Models\CustomReport',
        'App\Models\KpiDefinition',
        'App\Models\PredictionModel',
    ];
    
    foreach ($models as $model) {
        if (class_exists($model)) {
            echo "✅ {$model} - EXISTS\n";
        } else {
            echo "❌ {$model} - NOT FOUND\n";
        }
    }
    
    echo "\n✅ Route testing completed!\n";
    echo "\nYou can now visit these URLs:\n";
    echo "- http://localhost:8000/analytics/dashboard\n";
    echo "- http://localhost:8000/analytics/kpis\n";
    echo "- http://localhost:8000/analytics/reports\n";
    echo "- http://localhost:8000/analytics/predictions\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}