<?php

require_once '../../vendor/autoload.php';

use App\Models\Graduate;
use App\Models\Course;
use App\Models\KpiDefinition;

// Bootstrap Laravel
$app = require_once '../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Analytics System...\n\n";

try {
    $analyticsService = app(App\Services\AnalyticsService::class);

    // Test 1: Check data counts
    echo "📊 Data Overview:\n";
    echo "- Graduates: " . Graduate::count() . "\n";
    echo "- Courses: " . Course::count() . "\n";
    echo "- KPI Definitions: " . KpiDefinition::count() . "\n";
    echo "\n";

    // Test 2: Generate analytics dashboard
    echo "🎯 Testing Analytics Dashboard...\n";
    $dashboard = $analyticsService->getAnalyticsDashboard();
    echo "- Overview metrics: " . count($dashboard['overview'] ?? []) . " items\n";
    echo "- KPIs: " . count($dashboard['kpis'] ?? []) . " items\n";
    echo "- Charts: " . count($dashboard['charts'] ?? []) . " items\n";
    echo "\n";

    // Test 3: Calculate KPIs
    echo "📈 Testing KPI Calculations...\n";
    $kpiResults = $analyticsService->calculateKpiValues();
    foreach ($kpiResults as $key => $value) {
        echo "- {$key}: {$value}\n";
    }
    echo "\n";

    // Test 4: Employment Analytics
    echo "👥 Testing Employment Analytics...\n";
    $employmentAnalytics = $analyticsService->getEmploymentAnalytics();
    $summary = $employmentAnalytics['summary'] ?? [];
    echo "- Total Graduates: " . ($summary['total_graduates'] ?? 0) . "\n";
    echo "- Employed Count: " . ($summary['employed_count'] ?? 0) . "\n";
    echo "- Employment Rate: " . number_format($summary['employment_rate'] ?? 0, 1) . "%\n";
    echo "\n";

    // Test 5: Generate Daily Snapshot
    echo "📸 Testing Snapshot Generation...\n";
    $snapshot = $analyticsService->generateDailySnapshot();
    echo "- Snapshot ID: " . $snapshot->id . "\n";
    echo "- Snapshot Type: " . $snapshot->type . "\n";
    echo "- Data Keys: " . implode(', ', array_keys($snapshot->data)) . "\n";
    echo "\n";

    echo "✅ All tests completed successfully!\n";
    echo "\nThe analytics system is ready to use.\n";
    echo "Visit: http://localhost:8080/analytics/dashboard\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}