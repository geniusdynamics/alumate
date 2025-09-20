<?php

try {
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    
    // Bootstrap the application with the necessary bootstrappers
    $app->bootstrapWith([
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
    ]);
    
    echo "Laravel application bootstrapped successfully!\n";
    echo "Environment: " . $app->environment() . "\n";
} catch (Exception $e) {
    echo "Error bootstrapping Laravel: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}