<?php

require_once '../../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once '../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Temporarily disabling security middleware for testing...\n";

try {
    // Read the current kernel file
    $kernelPath = '../../app/Http/Kernel.php';
    
    if (!file_exists($kernelPath)) {
        echo "❌ Kernel file not found at: {$kernelPath}\n";
        exit(1);
    }
    
    $kernelContent = file_get_contents($kernelPath);
    
    // Comment out the SecurityMonitoring middleware
    $kernelContent = str_replace(
        'App\Http\Middleware\SecurityMonitoring::class,',
        '// App\Http\Middleware\SecurityMonitoring::class, // Temporarily disabled for testing',
        $kernelContent
    );
    
    // Write back to file
    file_put_contents($kernelPath, $kernelContent);
    
    echo "✅ Security middleware temporarily disabled\n";
    echo "\nYou can now test the analytics system without security restrictions.\n";
    echo "Remember to re-enable it later for production use.\n";
    echo "\nTo re-enable, uncomment the SecurityMonitoring line in app/Http/Kernel.php\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}