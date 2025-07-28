<?php

require_once '../../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once '../../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "🔍 Testing Route Resolution\n";
echo "==========================\n\n";

// Test route resolution
try {
    $request = \Illuminate\Http\Request::create('/', 'GET');
    $request->headers->set('Host', '127.0.0.1:8080');
    
    echo "1. Testing home route resolution:\n";
    echo "Request URI: " . $request->getRequestUri() . "\n";
    echo "Request Host: " . $request->getHost() . "\n";
    echo "Request Port: " . $request->getPort() . "\n";
    
    // Check if tenancy middleware is interfering
    $router = app('router');
    $route = $router->getRoutes()->match($request);
    
    if ($route) {
        echo "✅ Route found: " . $route->uri() . "\n";
        echo "Route name: " . ($route->getName() ?: 'unnamed') . "\n";
        echo "Route action: " . $route->getActionName() . "\n";
        echo "Route middleware: " . implode(', ', $route->middleware()) . "\n";
    } else {
        echo "❌ No route found for /\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Test tenancy resolution
echo "\n2. Testing tenancy resolution:\n";
try {
    $centralDomains = config('tenancy.central_domains');
    echo "Central domains: " . implode(', ', $centralDomains) . "\n";
    
    $host = '127.0.0.1:8080';
    if (in_array($host, $centralDomains)) {
        echo "✅ Host $host is recognized as central domain\n";
    } else {
        echo "❌ Host $host is NOT in central domains\n";
    }
    
} catch (Exception $e) {
    echo "❌ Tenancy error: " . $e->getMessage() . "\n";
}

// Test middleware stack
echo "\n3. Testing middleware resolution:\n";
try {
    $middlewareGroups = config('app.middleware_groups', []);
    echo "Web middleware group: " . implode(', ', $middlewareGroups['web'] ?? []) . "\n";
    
} catch (Exception $e) {
    echo "❌ Middleware error: " . $e->getMessage() . "\n";
}

echo "\n4. Testing direct route access:\n";
try {
    // Try to access the route directly
    $routes = app('router')->getRoutes();
    $homeRoute = null;
    
    foreach ($routes as $route) {
        if ($route->uri() === '/') {
            $homeRoute = $route;
            break;
        }
    }
    
    if ($homeRoute) {
        echo "✅ Home route exists\n";
        echo "Trying to call route action...\n";
        
        // Create a proper request
        $request = \Illuminate\Http\Request::create('/', 'GET');
        $request->headers->set('Host', '127.0.0.1:8080');
        
        // Set up the application context
        app()->instance('request', $request);
        
        echo "Route callable: " . (is_callable($homeRoute->getAction('uses')) ? 'Yes' : 'No') . "\n";
        
    } else {
        echo "❌ Home route not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Route access error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}