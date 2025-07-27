<?php

require_once '../../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once '../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Create a default tenant
try {
    // Insert directly into database
    \DB::table('tenants')->insert([
        'id' => 'default',
        'name' => 'Default Tenant',
        'address' => null,
        'contact_information' => null,
        'plan' => null,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    // Create domains
    \DB::table('domains')->insert([
        ['domain' => '127.0.0.1', 'tenant_id' => 'default'],
        ['domain' => 'localhost', 'tenant_id' => 'default']
    ]);
    
    echo "Tenant created successfully!\n";
    echo "You can now access the application at http://127.0.0.1:8080 or http://localhost:8080\n";
} catch (Exception $e) {
    echo "Error creating tenant: " . $e->getMessage() . "\n";
}