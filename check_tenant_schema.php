<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TenantContextService;
use Illuminate\Support\Facades\DB;

try {
    $service = app(TenantContextService::class);
    $service->setTenant('tech-institute');
    
    echo "Current schema: " . $service->getCurrentSchema() . PHP_EOL;
    
    $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = ?', [$service->getCurrentSchema()]);
    
    echo "Tables in schema:" . PHP_EOL;
    foreach ($tables as $table) {
        echo "- " . $table->table_name . PHP_EOL;
    }
    
    // Check if courses table exists and has title column
    $columns = DB::select('SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ?', [$service->getCurrentSchema(), 'courses']);
    
    echo "\nCourses table columns:" . PHP_EOL;
    foreach ($columns as $column) {
        echo "- " . $column->column_name . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}