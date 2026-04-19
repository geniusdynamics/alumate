# Performance Testing Guide for Schema-Based Tenancy

## Overview
This guide provides comprehensive performance testing procedures and optimization strategies for the schema-based tenancy architecture.

## Performance Testing Strategy

### Testing Objectives
1. **Query Performance**: Measure query execution times across different tenant schemas
2. **Schema Switching**: Evaluate overhead of tenant context switching
3. **Concurrent Access**: Test performance under multiple tenant load
4. **Resource Utilization**: Monitor CPU, memory, and I/O usage
5. **Scalability**: Assess performance as tenant count increases

### Key Performance Metrics

#### Database Metrics
- Query execution time (avg, p95, p99)
- Connection pool utilization
- Schema switching overhead
- Index effectiveness
- Lock contention

#### Application Metrics
- Response time per tenant
- Memory usage per request
- CPU utilization
- Garbage collection frequency
- Cache hit rates

## Performance Testing Scripts

### 1. Database Query Performance Test

```php
<?php
// tests/Performance/DatabasePerformanceTest.php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\Tenant;
use App\Services\TenantContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabasePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private $tenantContext;
    private $testTenants = [];
    private $performanceResults = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = app(TenantContextService::class);
        $this->createTestTenants();
    }

    private function createTestTenants(int $count = 10): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $tenant = Tenant::create([
                'name' => "Test Tenant {$i}",
                'slug' => "test-tenant-{$i}",
                'domain' => "tenant{$i}.test.com",
                'status' => 'active'
            ]);
            
            $this->testTenants[] = $tenant;
            
            // Create schema and seed data
            $this->tenantContext->setCurrentTenant($tenant);
            $this->seedTenantData($tenant, 1000); // 1000 records per tenant
        }
    }

    private function seedTenantData(Tenant $tenant, int $recordCount): void
    {
        // Create users
        for ($i = 1; $i <= $recordCount; $i++) {
            DB::table('users')->insert([
                'name' => "User {$i}",
                'email' => "user{$i}@{$tenant->slug}.com",
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create courses
        for ($i = 1; $i <= $recordCount / 10; $i++) {
            DB::table('courses')->insert([
                'title' => "Course {$i}",
                'description' => "Description for course {$i}",
                'duration_weeks' => rand(4, 52),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create graduates
        for ($i = 1; $i <= $recordCount / 5; $i++) {
            DB::table('graduates')->insert([
                'user_id' => rand(1, $recordCount),
                'course_id' => rand(1, $recordCount / 10),
                'graduation_date' => now()->subDays(rand(1, 365)),
                'grade' => ['A', 'B', 'C', 'D'][rand(0, 3)],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function test_query_performance_across_tenants(): void
    {
        $queries = [
            'simple_select' => 'SELECT COUNT(*) FROM users',
            'join_query' => 'SELECT u.name, c.title FROM users u JOIN graduates g ON u.id = g.user_id JOIN courses c ON g.course_id = c.id LIMIT 100',
            'aggregate_query' => 'SELECT course_id, COUNT(*) as graduate_count FROM graduates GROUP BY course_id',
            'complex_query' => 'SELECT u.name, COUNT(g.id) as course_count FROM users u LEFT JOIN graduates g ON u.id = g.user_id GROUP BY u.id, u.name HAVING COUNT(g.id) > 0 ORDER BY course_count DESC LIMIT 50'
        ];

        foreach ($this->testTenants as $tenant) {
            $this->tenantContext->setCurrentTenant($tenant);
            
            foreach ($queries as $queryName => $sql) {
                $startTime = microtime(true);
                
                for ($i = 0; $i < 10; $i++) {
                    DB::select($sql);
                }
                
                $endTime = microtime(true);
                $avgTime = ($endTime - $startTime) / 10;
                
                $this->performanceResults[$tenant->id][$queryName] = $avgTime;
                
                // Assert reasonable performance (adjust thresholds as needed)
                $this->assertLessThan(0.1, $avgTime, "Query {$queryName} took too long for tenant {$tenant->id}: {$avgTime}s");
            }
        }
        
        $this->outputPerformanceResults();
    }

    public function test_schema_switching_overhead(): void
    {
        $switchingTimes = [];
        
        for ($i = 0; $i < 100; $i++) {
            $tenant = $this->testTenants[array_rand($this->testTenants)];
            
            $startTime = microtime(true);
            $this->tenantContext->setCurrentTenant($tenant);
            $endTime = microtime(true);
            
            $switchingTimes[] = $endTime - $startTime;
        }
        
        $avgSwitchingTime = array_sum($switchingTimes) / count($switchingTimes);
        $maxSwitchingTime = max($switchingTimes);
        
        echo "\nSchema Switching Performance:\n";
        echo "Average switching time: " . number_format($avgSwitchingTime * 1000, 2) . "ms\n";
        echo "Maximum switching time: " . number_format($maxSwitchingTime * 1000, 2) . "ms\n";
        
        // Assert reasonable switching performance
        $this->assertLessThan(0.01, $avgSwitchingTime, "Schema switching is too slow: {$avgSwitchingTime}s");
        $this->assertLessThan(0.05, $maxSwitchingTime, "Maximum schema switching time is too slow: {$maxSwitchingTime}s");
    }

    public function test_concurrent_tenant_access(): void
    {
        $concurrentRequests = 50;
        $results = [];
        
        // Simulate concurrent requests to different tenants
        $processes = [];
        
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $tenant = $this->testTenants[array_rand($this->testTenants)];
            
            $process = proc_open(
                "php artisan tinker --execute=\"\n                    use App\\Models\\Tenant;\n                    use App\\Services\\TenantContextService;\n                    \\$tenant = Tenant::find({$tenant->id});\n                    \\$context = app(TenantContextService::class);\n                    \\$start = microtime(true);\n                    \\$context->setCurrentTenant(\\$tenant);\n                    \\$users = DB::table('users')->count();\n                    \\$end = microtime(true);\n                    echo (\\$end - \\$start) . PHP_EOL;\n                \"",
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
            
            $processes[] = [$process, $pipes];
        }
        
        // Collect results
        foreach ($processes as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]);
            $executionTime = (float) trim($output);
            $results[] = $executionTime;
            
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }
        
        $avgConcurrentTime = array_sum($results) / count($results);
        $maxConcurrentTime = max($results);
        
        echo "\nConcurrent Access Performance:\n";
        echo "Average concurrent request time: " . number_format($avgConcurrentTime * 1000, 2) . "ms\n";
        echo "Maximum concurrent request time: " . number_format($maxConcurrentTime * 1000, 2) . "ms\n";
        
        // Assert reasonable concurrent performance
        $this->assertLessThan(0.5, $avgConcurrentTime, "Concurrent access is too slow: {$avgConcurrentTime}s");
    }

    private function outputPerformanceResults(): void
    {
        echo "\n\nQuery Performance Results:\n";
        echo str_repeat('=', 80) . "\n";
        
        foreach ($this->performanceResults as $tenantId => $queries) {
            echo "Tenant {$tenantId}:\n";
            foreach ($queries as $queryName => $time) {
                echo "  {$queryName}: " . number_format($time * 1000, 2) . "ms\n";
            }
            echo "\n";
        }
    }

    protected function tearDown(): void
    {
        // Cleanup test tenants
        foreach ($this->testTenants as $tenant) {
            $schemaName = "tenant_{$tenant->id}";
            DB::statement("DROP SCHEMA IF EXISTS {$schemaName} CASCADE");
            $tenant->delete();
        }
        
        parent::tearDown();
    }
}
```

### 2. Load Testing Script

```bash
#!/bin/bash
# scripts/load_test.sh

echo "Starting Load Testing for Schema-Based Tenancy"
echo "============================================="

# Configuration
BASE_URL="http://localhost:8000"
TEST_DURATION=300  # 5 minutes
CONCURRENT_USERS=50
RAMP_UP_TIME=60    # 1 minute

# Create test plan
cat > load_test_plan.jmx << 'EOF'
<?xml version="1.0" encoding="UTF-8"?>
<jmeterTestPlan version="1.2" properties="5.0" jmeter="5.4.1">
  <hashTree>
    <TestPlan guiclass="TestPlanGui" testclass="TestPlan" testname="Schema Tenancy Load Test">
      <stringProp name="TestPlan.comments">Load test for schema-based tenancy</stringProp>
      <boolProp name="TestPlan.functional_mode">false</boolProp>
      <boolProp name="TestPlan.tearDown_on_shutdown">true</boolProp>
      <boolProp name="TestPlan.serialize_threadgroups">false</boolProp>
      <elementProp name="TestPlan.arguments" elementType="Arguments" guiclass="ArgumentsPanel" testclass="Arguments" testname="User Defined Variables">
        <collectionProp name="Arguments.arguments"/>
      </elementProp>
      <stringProp name="TestPlan.user_define_classpath"></stringProp>
    </TestPlan>
    <hashTree>
      <ThreadGroup guiclass="ThreadGroupGui" testclass="ThreadGroup" testname="Tenant Users">
        <stringProp name="ThreadGroup.on_sample_error">continue</stringProp>
        <elementProp name="ThreadGroup.main_controller" elementType="LoopController" guiclass="LoopControllerGui" testclass="LoopController" testname="Loop Controller">
          <boolProp name="LoopController.continue_forever">false</boolProp>
          <intProp name="LoopController.loops">-1</intProp>
        </elementProp>
        <stringProp name="ThreadGroup.num_threads">${CONCURRENT_USERS}</stringProp>
        <stringProp name="ThreadGroup.ramp_time">${RAMP_UP_TIME}</stringProp>
        <boolProp name="ThreadGroup.scheduler">true</boolProp>
        <stringProp name="ThreadGroup.duration">${TEST_DURATION}</stringProp>
        <stringProp name="ThreadGroup.delay"></stringProp>
      </ThreadGroup>
      <hashTree>
        <!-- Add HTTP requests for different tenant endpoints -->
        <HTTPSamplerProxy guiclass="HttpTestSampleGui" testclass="HTTPSamplerProxy" testname="Tenant Dashboard">
          <elementProp name="HTTPsampler.Arguments" elementType="Arguments" guiclass="HTTPArgumentsPanel" testclass="Arguments" testname="User Defined Variables">
            <collectionProp name="Arguments.arguments"/>
          </elementProp>
          <stringProp name="HTTPSampler.domain">${BASE_URL}</stringProp>
          <stringProp name="HTTPSampler.port"></stringProp>
          <stringProp name="HTTPSampler.protocol">http</stringProp>
          <stringProp name="HTTPSampler.contentEncoding"></stringProp>
          <stringProp name="HTTPSampler.path">/dashboard</stringProp>
          <stringProp name="HTTPSampler.method">GET</stringProp>
          <boolProp name="HTTPSampler.follow_redirects">true</boolProp>
          <boolProp name="HTTPSampler.auto_redirects">false</boolProp>
          <boolProp name="HTTPSampler.use_keepalive">true</boolProp>
          <boolProp name="HTTPSampler.DO_MULTIPART_POST">false</boolProp>
          <stringProp name="HTTPSampler.embedded_url_re"></stringProp>
          <stringProp name="HTTPSampler.connect_timeout"></stringProp>
          <stringProp name="HTTPSampler.response_timeout"></stringProp>
        </HTTPSamplerProxy>
      </hashTree>
    </hashTree>
  </hashTree>
</jmeterTestPlan>
EOF

# Run JMeter load test
if command -v jmeter &> /dev/null; then
    echo "Running JMeter load test..."
    jmeter -n -t load_test_plan.jmx -l load_test_results.jtl -e -o load_test_report/
    echo "Load test completed. Results saved to load_test_results.jtl"
else
    echo "JMeter not found. Installing..."
    # Install JMeter (adjust for your OS)
    wget https://downloads.apache.org//jmeter/binaries/apache-jmeter-5.4.1.zip
    unzip apache-jmeter-5.4.1.zip
    export PATH=$PATH:$(pwd)/apache-jmeter-5.4.1/bin
    jmeter -n -t load_test_plan.jmx -l load_test_results.jtl
fi

# Analyze results
echo "\nAnalyzing load test results..."
php -r "
\$results = file('load_test_results.jtl');
\$totalRequests = count(\$results) - 1; // Exclude header
\$responseTimes = [];
\$errors = 0;

foreach (array_slice(\$results, 1) as \$line) {
    \$data = str_getcsv(\$line);
    \$responseTime = (int)\$data[1];
    \$success = \$data[7] === 'true';
    
    \$responseTimes[] = \$responseTime;
    if (!\$success) \$errors++;
}

sort(\$responseTimes);
\$avgResponseTime = array_sum(\$responseTimes) / count(\$responseTimes);
\$p95ResponseTime = \$responseTimes[floor(count(\$responseTimes) * 0.95)];
\$p99ResponseTime = \$responseTimes[floor(count(\$responseTimes) * 0.99)];
\$errorRate = (\$errors / \$totalRequests) * 100;

echo \"Load Test Results:\n\"; 
echo \"Total Requests: \$totalRequests\n\";
echo \"Average Response Time: \" . number_format(\$avgResponseTime, 2) . \"ms\n\";
echo \"95th Percentile: \" . number_format(\$p95ResponseTime, 2) . \"ms\n\";
echo \"99th Percentile: \" . number_format(\$p99ResponseTime, 2) . \"ms\n\";
echo \"Error Rate: \" . number_format(\$errorRate, 2) . \"%\n\";
"

# Cleanup
rm -f load_test_plan.jmx
echo "\nLoad testing completed!"
```

### 3. Memory Usage Monitoring

```php
<?php
// scripts/memory_monitor.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Services\TenantContextService;
use Illuminate\Support\Facades\DB;

class MemoryMonitor
{
    private $tenantContext;
    private $baselineMemory;
    private $results = [];

    public function __construct()
    {
        $this->tenantContext = app(TenantContextService::class);
        $this->baselineMemory = memory_get_usage(true);
    }

    public function monitorTenantSwitching(): void
    {
        echo "Monitoring memory usage during tenant switching...\n";
        
        $tenants = Tenant::take(20)->get();
        $memoryUsages = [];
        
        foreach ($tenants as $tenant) {
            $memoryBefore = memory_get_usage(true);
            
            // Switch tenant context
            $this->tenantContext->setCurrentTenant($tenant);
            
            // Perform some operations
            $userCount = DB::table('users')->count();
            $courseCount = DB::table('courses')->count();
            
            $memoryAfter = memory_get_usage(true);
            $memoryDiff = $memoryAfter - $memoryBefore;
            
            $memoryUsages[] = [
                'tenant_id' => $tenant->id,
                'memory_before' => $memoryBefore,
                'memory_after' => $memoryAfter,
                'memory_diff' => $memoryDiff,
                'user_count' => $userCount,
                'course_count' => $courseCount
            ];
            
            echo "Tenant {$tenant->id}: " . $this->formatBytes($memoryDiff) . " (Users: {$userCount}, Courses: {$courseCount})\n";
        }
        
        $this->analyzeMemoryUsage($memoryUsages);
    }

    public function monitorLongRunningOperations(): void
    {
        echo "\nMonitoring memory usage during long-running operations...\n";
        
        $tenant = Tenant::first();
        $this->tenantContext->setCurrentTenant($tenant);
        
        $operations = [
            'bulk_user_creation' => function() {
                for ($i = 0; $i < 1000; $i++) {
                    DB::table('users')->insert([
                        'name' => "Bulk User {$i}",
                        'email' => "bulk{$i}@test.com",
                        'password' => bcrypt('password'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            },
            'large_query_result' => function() {
                return DB::table('users')->get();
            },
            'complex_aggregation' => function() {
                return DB::table('users')
                    ->join('graduates', 'users.id', '=', 'graduates.user_id')
                    ->join('courses', 'graduates.course_id', '=', 'courses.id')
                    ->select('courses.title', DB::raw('COUNT(*) as graduate_count'))
                    ->groupBy('courses.id', 'courses.title')
                    ->orderBy('graduate_count', 'desc')
                    ->get();
            }
        ];
        
        foreach ($operations as $operationName => $operation) {
            $memoryBefore = memory_get_usage(true);
            $peakBefore = memory_get_peak_usage(true);
            
            $result = $operation();
            
            $memoryAfter = memory_get_usage(true);
            $peakAfter = memory_get_peak_usage(true);
            
            echo "{$operationName}:\n";
            echo "  Memory used: " . $this->formatBytes($memoryAfter - $memoryBefore) . "\n";
            echo "  Peak memory: " . $this->formatBytes($peakAfter - $peakBefore) . "\n";
            
            // Force garbage collection
            unset($result);
            gc_collect_cycles();
            
            $memoryAfterGC = memory_get_usage(true);
            echo "  After GC: " . $this->formatBytes($memoryAfterGC - $memoryBefore) . "\n\n";
        }
    }

    private function analyzeMemoryUsage(array $memoryUsages): void
    {
        $totalMemoryDiff = array_sum(array_column($memoryUsages, 'memory_diff'));
        $avgMemoryDiff = $totalMemoryDiff / count($memoryUsages);
        $maxMemoryDiff = max(array_column($memoryUsages, 'memory_diff'));
        $minMemoryDiff = min(array_column($memoryUsages, 'memory_diff'));
        
        echo "\nMemory Usage Analysis:\n";
        echo "Total memory increase: " . $this->formatBytes($totalMemoryDiff) . "\n";
        echo "Average per tenant: " . $this->formatBytes($avgMemoryDiff) . "\n";
        echo "Maximum increase: " . $this->formatBytes($maxMemoryDiff) . "\n";
        echo "Minimum increase: " . $this->formatBytes($minMemoryDiff) . "\n";
        
        // Check for memory leaks
        if ($avgMemoryDiff > 1024 * 1024) { // 1MB
            echo "WARNING: High memory usage per tenant switch detected!\n";
        }
        
        if ($maxMemoryDiff > 5 * 1024 * 1024) { // 5MB
            echo "CRITICAL: Excessive memory usage detected for some tenants!\n";
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return number_format($bytes, 2) . ' ' . $units[$unitIndex];
    }
}

// Run monitoring
$monitor = new MemoryMonitor();
$monitor->monitorTenantSwitching();
$monitor->monitorLongRunningOperations();

echo "\nMemory monitoring completed.\n";
echo "Peak memory usage: " . $monitor->formatBytes(memory_get_peak_usage(true)) . "\n";
echo "Current memory usage: " . $monitor->formatBytes(memory_get_usage(true)) . "\n";
```

## Performance Optimization Strategies

### 1. Database Optimizations

#### Index Strategy
```sql
-- Create indexes for frequently queried columns in each tenant schema
CREATE INDEX CONCURRENTLY idx_users_email ON tenant_1.users(email);
CREATE INDEX CONCURRENTLY idx_users_created_at ON tenant_1.users(created_at);
CREATE INDEX CONCURRENTLY idx_graduates_user_id ON tenant_1.graduates(user_id);
CREATE INDEX CONCURRENTLY idx_graduates_course_id ON tenant_1.graduates(course_id);
CREATE INDEX CONCURRENTLY idx_graduates_graduation_date ON tenant_1.graduates(graduation_date);

-- Composite indexes for common query patterns
CREATE INDEX CONCURRENTLY idx_graduates_user_course ON tenant_1.graduates(user_id, course_id);
CREATE INDEX CONCURRENTLY idx_jobs_location_type ON tenant_1.jobs(location, job_type);

-- Partial indexes for active records
CREATE INDEX CONCURRENTLY idx_users_active ON tenant_1.users(id) WHERE deleted_at IS NULL;
CREATE INDEX CONCURRENTLY idx_courses_published ON tenant_1.courses(id) WHERE status = 'published';
```

#### Connection Pooling Configuration
```php
// config/database.php
'connections' => [
    'pgsql' => [
        'driver' => 'pgsql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '5432'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'schema' => 'public',
        'sslmode' => 'prefer',
        'options' => [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ],
        // Connection pooling settings
        'pool' => [
            'min_connections' => 5,
            'max_connections' => 20,
            'acquire_timeout' => 60000,
            'timeout' => 60000,
            'idle_timeout' => 600000,
            'max_lifetime' => 1800000,
        ],
    ],
],
```

### 2. Application-Level Optimizations

#### Tenant Context Caching
```php
<?php
// app/Services/OptimizedTenantContextService.php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OptimizedTenantContextService extends TenantContextService
{
    private static $schemaCache = [];
    private static $connectionCache = [];

    public function setCurrentTenant(?Tenant $tenant): void
    {
        if ($tenant === null) {
            $this->currentTenant = null;
            return;
        }

        // Check if tenant is already set
        if ($this->currentTenant && $this->currentTenant->id === $tenant->id) {
            return;
        }

        $this->currentTenant = $tenant;
        $schemaName = $this->getSchemaName($tenant);

        // Use cached connection if available
        if (!isset(self::$connectionCache[$schemaName])) {
            $this->switchToTenantSchema($schemaName);
            self::$connectionCache[$schemaName] = true;
        } else {
            // Just set the search path
            DB::statement("SET search_path TO {$schemaName}, public");
        }
    }

    public function getTenantConfig(string $key, $default = null)
    {
        if (!$this->currentTenant) {
            return $default;
        }

        $cacheKey = "tenant_config_{$this->currentTenant->id}_{$key}";
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            return $this->currentTenant->getConfig($key, $default);
        });
    }

    public function clearTenantCache(Tenant $tenant): void
    {
        $pattern = "tenant_config_{$tenant->id}_*";
        Cache::flush(); // In production, use more specific cache clearing
        
        $schemaName = $this->getSchemaName($tenant);
        unset(self::$connectionCache[$schemaName]);
        unset(self::$schemaCache[$schemaName]);
    }
}
```

#### Query Optimization Service
```php
<?php
// app/Services/QueryOptimizationService.php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryOptimizationService
{
    public function analyzeSlowQueries(): array
    {
        // Enable query logging
        DB::enableQueryLog();
        
        // Get slow queries from PostgreSQL
        $slowQueries = DB::select("
            SELECT 
                query,
                calls,
                total_time,
                mean_time,
                rows
            FROM pg_stat_statements 
            WHERE mean_time > 100 -- queries taking more than 100ms
            ORDER BY mean_time DESC
            LIMIT 20
        ");
        
        return $slowQueries;
    }

    public function optimizeQuery(string $query): array
    {
        $suggestions = [];
        
        // Analyze query plan
        $plan = DB::select("EXPLAIN (ANALYZE, BUFFERS, FORMAT JSON) {$query}");
        $planData = json_decode($plan[0]->{'QUERY PLAN'}, true);
        
        // Check for sequential scans
        if ($this->hasSequentialScan($planData)) {
            $suggestions[] = 'Consider adding indexes to avoid sequential scans';
        }
        
        // Check for high cost operations
        if ($this->hasHighCostOperations($planData)) {
            $suggestions[] = 'Query has high-cost operations, consider optimization';
        }
        
        // Check for large result sets
        if ($this->hasLargeResultSet($planData)) {
            $suggestions[] = 'Consider adding LIMIT clause or pagination';
        }
        
        return [
            'plan' => $planData,
            'suggestions' => $suggestions
        ];
    }

    private function hasSequentialScan(array $plan): bool
    {
        return $this->searchPlan($plan, 'Seq Scan');
    }

    private function hasHighCostOperations(array $plan): bool
    {
        $totalCost = $plan[0]['Plan']['Total Cost'] ?? 0;
        return $totalCost > 1000; // Adjust threshold as needed
    }

    private function hasLargeResultSet(array $plan): bool
    {
        $actualRows = $plan[0]['Plan']['Actual Rows'] ?? 0;
        return $actualRows > 10000; // Adjust threshold as needed
    }

    private function searchPlan(array $plan, string $nodeType): bool
    {
        if (isset($plan[0]['Plan']['Node Type']) && $plan[0]['Plan']['Node Type'] === $nodeType) {
            return true;
        }
        
        if (isset($plan[0]['Plan']['Plans'])) {
            foreach ($plan[0]['Plan']['Plans'] as $subPlan) {
                if ($this->searchPlan([['Plan' => $subPlan]], $nodeType)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    public function createOptimalIndexes(string $tableName, array $columns): void
    {
        $schemaName = DB::getTablePrefix() ?: 'public';
        
        foreach ($columns as $column) {
            $indexName = "idx_{$tableName}_{$column}";
            
            try {
                DB::statement("
                    CREATE INDEX CONCURRENTLY IF NOT EXISTS {$indexName} 
                    ON {$schemaName}.{$tableName}({$column})
                ");
                
                Log::info("Created index {$indexName} on {$schemaName}.{$tableName}");
            } catch (\Exception $e) {
                Log::error("Failed to create index {$indexName}: " . $e->getMessage());
            }
        }
    }
}
```

### 3. Monitoring and Alerting

#### Performance Monitoring Middleware
```php
<?php
// app/Http/Middleware/PerformanceMonitoringMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\TenantContextService;

class PerformanceMonitoringMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $tenantContext = app(TenantContextService::class);
        $currentTenant = $tenantContext->getCurrentTenant();
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $startMemory;
        
        // Log performance metrics
        $metrics = [
            'tenant_id' => $currentTenant?->id,
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'url' => $request->url(),
            'execution_time_ms' => round($executionTime, 2),
            'memory_usage_bytes' => $memoryUsage,
            'response_status' => $response->getStatusCode(),
            'timestamp' => now()->toISOString(),
        ];
        
        // Log slow requests
        if ($executionTime > 1000) { // Requests taking more than 1 second
            Log::warning('Slow request detected', $metrics);
        }
        
        // Log high memory usage
        if ($memoryUsage > 10 * 1024 * 1024) { // More than 10MB
            Log::warning('High memory usage detected', $metrics);
        }
        
        // Add performance headers for debugging
        $response->headers->set('X-Execution-Time', $executionTime);
        $response->headers->set('X-Memory-Usage', $memoryUsage);
        $response->headers->set('X-Tenant-ID', $currentTenant?->id);
        
        return $response;
    }
}
```

## Performance Benchmarks

### Target Performance Metrics

| Metric | Target | Warning Threshold | Critical Threshold |
|--------|--------|-------------------|--------------------|
| Query Response Time (avg) | < 50ms | > 100ms | > 500ms |
| Schema Switch Time | < 5ms | > 10ms | > 50ms |
| Memory per Request | < 5MB | > 10MB | > 25MB |
| Concurrent Users | 100+ | N/A | < 50 |
| Database Connections | < 80% pool | > 90% pool | Pool exhausted |
| Error Rate | < 0.1% | > 1% | > 5% |

### Continuous Performance Testing

```bash
#!/bin/bash
# scripts/continuous_performance_test.sh

echo "Starting Continuous Performance Testing"
echo "======================================"

# Run performance tests every hour
while true; do
    echo "Running performance tests at $(date)"
    
    # Database performance test
    php artisan test tests/Performance/DatabasePerformanceTest.php --verbose
    
    # Memory monitoring
    php scripts/memory_monitor.php
    
    # Query analysis
    php artisan performance:analyze-queries
    
    # Wait for 1 hour
    sleep 3600
done
```

## Troubleshooting Performance Issues

### Common Performance Problems

1. **Slow Schema Switching**
   - Check connection pool configuration
   - Verify search_path setting efficiency
   - Monitor connection creation overhead

2. **High Memory Usage**
   - Check for memory leaks in tenant context
   - Verify proper cleanup of tenant-specific data
   - Monitor garbage collection frequency

3. **Slow Queries**
   - Analyze query execution plans
   - Check index usage
   - Verify schema-specific optimizations

4. **Connection Pool Exhaustion**
   - Monitor connection usage patterns
   - Adjust pool size configuration
   - Check for connection leaks

### Performance Debugging Tools

```php
<?php
// app/Console/Commands/PerformanceDebugCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QueryOptimizationService;
use App\Services\TenantContextService;
use App\Models\Tenant;

class PerformanceDebugCommand extends Command
{
    protected $signature = 'performance:debug {--tenant=} {--query=}';
    protected $description = 'Debug performance issues';

    public function handle()
    {
        $tenantId = $this->option('tenant');
        $query = $this->option('query');
        
        if ($tenantId) {
            $this->debugTenantPerformance($tenantId);
        }
        
        if ($query) {
            $this->debugQuery($query);
        }
        
        if (!$tenantId && !$query) {
            $this->debugOverallPerformance();
        }
    }

    private function debugTenantPerformance(int $tenantId): void
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant {$tenantId} not found");
            return;
        }
        
        $tenantContext = app(TenantContextService::class);
        
        $this->info("Debugging performance for tenant {$tenantId}");
        
        // Measure schema switching time
        $start = microtime(true);
        $tenantContext->setCurrentTenant($tenant);
        $switchTime = (microtime(true) - $start) * 1000;
        
        $this->line("Schema switch time: {$switchTime}ms");
        
        // Check table sizes
        $tables = ['users', 'courses', 'graduates', 'jobs', 'leads'];
        foreach ($tables as $table) {
            $count = DB::table($table)->count();
            $this->line("{$table}: {$count} records");
        }
    }

    private function debugQuery(string $query): void
    {
        $optimizer = app(QueryOptimizationService::class);
        $analysis = $optimizer->optimizeQuery($query);
        
        $this->info("Query Analysis:");
        $this->line(json_encode($analysis['plan'], JSON_PRETTY_PRINT));
        
        if (!empty($analysis['suggestions'])) {
            $this->warn("Suggestions:");
            foreach ($analysis['suggestions'] as $suggestion) {
                $this->line("- {$suggestion}");
            }
        }
    }

    private function debugOverallPerformance(): void
    {
        $this->info("Overall Performance Debug");
        
        // Check database connections
        $connections = DB::select("SELECT count(*) as active_connections FROM pg_stat_activity WHERE state = 'active'");
        $this->line("Active database connections: {$connections[0]->active_connections}");
        
        // Check slow queries
        $optimizer = app(QueryOptimizationService::class);
        $slowQueries = $optimizer->analyzeSlowQueries();
        
        if (!empty($slowQueries)) {
            $this->warn("Slow queries detected:");
            foreach (array_slice($slowQueries, 0, 5) as $query) {
                $this->line("- {$query->mean_time}ms: " . substr($query->query, 0, 100) . "...");
            }
        }
    }
}
```

---

**Performance Testing Schedule:**
- Daily: Automated performance regression tests
- Weekly: Load testing with realistic data volumes
- Monthly: Comprehensive performance review and optimization
- Quarterly: Capacity planning and scaling assessment

**Last Updated:** [Date]
**Next Review:** [Date]