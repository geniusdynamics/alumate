<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Stancl\Tenancy\Database\Models\Tenant;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Creating test tenant...\n";

try {
    // Create a test tenant
    $tenant = Tenant::create([
        'id' => 'test-university',
        'data' => [
            'name' => 'Test University',
            'domain' => 'test-university.localhost',
        ]
    ]);

    // Create the domain
    $tenant->domains()->create([
        'domain' => 'test-university.localhost'
    ]);

    echo "✓ Created tenant: test-university\n";
    echo "✓ Domain: test-university.localhost\n";
    echo "\nYou can now run the sample data script within this tenant context.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}