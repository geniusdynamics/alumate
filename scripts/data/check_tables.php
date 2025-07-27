<?php

require_once '../../vendor/autoload.php';

$app = require_once '../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = \DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name;");

echo "Database tables:\n";
foreach($tables as $table) {
    echo "- " . $table->table_name . "\n";
}

// Check if roles table exists
try {
    $rolesCount = \DB::table('roles')->count();
    echo "\nRoles table exists with {$rolesCount} records\n";
} catch (Exception $e) {
    echo "\nRoles table does not exist or is not accessible\n";
    echo "Error: " . $e->getMessage() . "\n";
}