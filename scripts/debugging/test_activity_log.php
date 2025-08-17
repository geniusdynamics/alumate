<?php

require_once '../../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once '../../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "🔍 Testing Activity Log Creation\n";
echo "===============================\n\n";

try {
    // Test creating an activity log entry
    $activityLog = \App\Models\ActivityLog::create([
        'user_id' => 1, // Assuming super admin user ID is 1
        'activity' => 'Test Activity',
        'description' => 'Testing activity log creation',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test User Agent',
    ]);

    echo "✅ Activity log created successfully!\n";
    echo 'ID: '.$activityLog->id."\n";
    echo 'User ID: '.$activityLog->user_id."\n";
    echo 'Activity: '.$activityLog->activity."\n";
    echo 'Description: '.$activityLog->description."\n";
    echo 'Created at: '.$activityLog->created_at."\n";

    // Clean up the test record
    $activityLog->delete();
    echo "\n✅ Test record cleaned up\n";

} catch (Exception $e) {
    echo '❌ Error creating activity log: '.$e->getMessage()."\n";
    echo 'File: '.$e->getFile().':'.$e->getLine()."\n";
}

echo "\n🔧 Activity Log Model Fillable Fields:\n";
$model = new \App\Models\ActivityLog;
$fillable = $model->getFillable();
echo 'Fillable: '.implode(', ', $fillable)."\n";

echo "\n📊 Database Schema Check:\n";
try {
    $columns = \DB::select("SELECT column_name, is_nullable, data_type FROM information_schema.columns WHERE table_name = 'activity_logs' ORDER BY ordinal_position");

    foreach ($columns as $column) {
        $nullable = $column->is_nullable === 'YES' ? '(nullable)' : '(required)';
        echo "- {$column->column_name}: {$column->data_type} {$nullable}\n";
    }
} catch (Exception $e) {
    echo '❌ Error checking schema: '.$e->getMessage()."\n";
}

echo "\n💡 Login Test Recommendation:\n";
echo "The activity logging should now work correctly.\n";
echo "Try logging in with: admin@system.com / password\n";
echo "URL: http://127.0.0.1:8080/login\n";
