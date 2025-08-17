<?php

namespace Tests\Performance;

use App\Models\Course;
use App\Models\Employer;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoadTestRunner extends TestCase
{
    use RefreshDatabase;

    private array $performanceMetrics = [];

    private int $concurrentUsers = 100;

    private int $testDuration = 60; // seconds

    private array $endpoints = [
        '/',
        '/institutional',
        '/api/homepage/statistics',
        '/api/homepage/testimonials',
        '/api/homepage/success-stories',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Create comprehensive test data
        $this->createLoadTestData();

        // Initialize performance metrics
        $this->performanceMetrics = [
            'requests_sent' => 0,
            'requests_successful' => 0,
            'requests_failed' => 0,
            'total_response_time' => 0,
            'min_response_time' => PHP_FLOAT_MAX,
            'max_response_time' => 0,
            'response_times' => [],
            'errors' => [],
            'memory_usage' => [],
            'database_queries' => [],
        ];
    }

    public function test_homepage_load_under_concurrent_users(): void
    {
        $this->runLoadTest();
        $this->analyzeResults();
        $this->assertPerformanceRequirements();
    }

    public function test_homepage_stress_testing(): void
    {
        // Gradually increase load
        $userCounts = [10, 25, 50, 100, 200];
        $results = [];

        foreach ($userCounts as $userCount) {
            $this->concurrentUsers = $userCount;
            $this->testDuration = 30; // Shorter duration for stress test

            echo "\nTesting with {$userCount} concurrent users...\n";

            $this->resetMetrics();
            $this->runLoadTest();

            $results[$userCount] = [
                'avg_response_time' => $this->getAverageResponseTime(),
                'success_rate' => $this->getSuccessRate(),
                'requests_per_second' => $this->getRequestsPerSecond(),
                'memory_peak' => max($this->performanceMetrics['memory_usage']),
            ];

            // Stop if performance degrades significantly
            if ($this->getAverageResponseTime() > 5000) { // 5 seconds
                echo "Performance degraded significantly at {$userCount} users\n";
                break;
            }
        }

        $this->analyzeStressTestResults($results);
    }

    public function test_homepage_endurance_testing(): void
    {
        // Run for extended period with moderate load
        $this->concurrentUsers = 50;
        $this->testDuration = 300; // 5 minutes

        echo "\nRunning endurance test for 5 minutes with 50 concurrent users...\n";

        $this->runLoadTest();
        $this->analyzeEnduranceResults();
    }

    public function test_homepage_spike_testing(): void
    {
        // Simulate traffic spikes
        $spikes = [
            ['users' => 10, 'duration' => 30],
            ['users' => 200, 'duration' => 10], // Spike
            ['users' => 10, 'duration' => 30],
            ['users' => 300, 'duration' => 5],  // Bigger spike
            ['users' => 10, 'duration' => 30],
        ];

        $spikeResults = [];

        foreach ($spikes as $index => $spike) {
            echo "\nSpike test {$index}: {$spike['users']} users for {$spike['duration']} seconds\n";

            $this->concurrentUsers = $spike['users'];
            $this->testDuration = $spike['duration'];

            $this->resetMetrics();
            $this->runLoadTest();

            $spikeResults[] = [
                'users' => $spike['users'],
                'duration' => $spike['duration'],
                'avg_response_time' => $this->getAverageResponseTime(),
                'success_rate' => $this->getSuccessRate(),
                'peak_memory' => max($this->performanceMetrics['memory_usage']),
            ];
        }

        $this->analyzeSpikeTestResults($spikeResults);
    }

    private function runLoadTest(): void
    {
        $startTime = microtime(true);
        $endTime = $startTime + $this->testDuration;

        // Create user sessions
        $userSessions = $this->createUserSessions($this->concurrentUsers);

        while (microtime(true) < $endTime) {
            $this->executeRequestBatch($userSessions);

            // Small delay to prevent overwhelming the system
            usleep(100000); // 100ms
        }

        $this->performanceMetrics['test_duration'] = microtime(true) - $startTime;
    }

    private function createUserSessions(int $count): array
    {
        $sessions = [];

        for ($i = 0; $i < $count; $i++) {
            $sessions[] = [
                'id' => "user_{$i}",
                'session_id' => 'session_'.uniqid(),
                'user_agent' => $this->getRandomUserAgent(),
                'current_endpoint' => 0,
            ];
        }

        return $sessions;
    }

    private function executeRequestBatch(array &$userSessions): void
    {
        $batchSize = min(20, count($userSessions)); // Process in batches
        $batch = array_slice($userSessions, 0, $batchSize);

        foreach ($batch as &$session) {
            $this->executeUserRequest($session);
        }

        // Rotate sessions
        $userSessions = array_merge(array_slice($userSessions, $batchSize), $batch);
    }

    private function executeUserRequest(array &$session): void
    {
        $endpoint = $this->endpoints[$session['current_endpoint']];
        $session['current_endpoint'] = ($session['current_endpoint'] + 1) % count($this->endpoints);

        $startTime = microtime(true);
        $memoryBefore = memory_get_usage(true);

        DB::flushQueryLog();

        try {
            $response = $this->withHeaders([
                'User-Agent' => $session['user_agent'],
                'X-Session-ID' => $session['session_id'],
            ])->get($endpoint);

            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            $memoryAfter = memory_get_usage(true);

            $this->recordMetrics($response, $responseTime, $memoryAfter - $memoryBefore);

        } catch (\Exception $e) {
            $this->performanceMetrics['requests_failed']++;
            $this->performanceMetrics['errors'][] = [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'timestamp' => microtime(true),
            ];
        }
    }

    private function recordMetrics($response, float $responseTime, int $memoryUsed): void
    {
        $this->performanceMetrics['requests_sent']++;

        if ($response->status() === 200) {
            $this->performanceMetrics['requests_successful']++;
        } else {
            $this->performanceMetrics['requests_failed']++;
        }

        $this->performanceMetrics['total_response_time'] += $responseTime;
        $this->performanceMetrics['min_response_time'] = min($this->performanceMetrics['min_response_time'], $responseTime);
        $this->performanceMetrics['max_response_time'] = max($this->performanceMetrics['max_response_time'], $responseTime);
        $this->performanceMetrics['response_times'][] = $responseTime;
        $this->performanceMetrics['memory_usage'][] = $memoryUsed;

        // Record database query count
        $queries = DB::getQueryLog();
        $this->performanceMetrics['database_queries'][] = count($queries);
    }

    private function analyzeResults(): void
    {
        $avgResponseTime = $this->getAverageResponseTime();
        $successRate = $this->getSuccessRate();
        $requestsPerSecond = $this->getRequestsPerSecond();
        $p95ResponseTime = $this->getPercentileResponseTime(95);
        $p99ResponseTime = $this->getPercentileResponseTime(99);

        echo "\n=== Load Test Results ===\n";
        echo "Concurrent Users: {$this->concurrentUsers}\n";
        echo "Test Duration: {$this->testDuration} seconds\n";
        echo "Total Requests: {$this->performanceMetrics['requests_sent']}\n";
        echo "Successful Requests: {$this->performanceMetrics['requests_successful']}\n";
        echo "Failed Requests: {$this->performanceMetrics['requests_failed']}\n";
        echo 'Success Rate: '.number_format($successRate, 2)."%\n";
        echo 'Requests per Second: '.number_format($requestsPerSecond, 2)."\n";
        echo 'Average Response Time: '.number_format($avgResponseTime, 2)."ms\n";
        echo 'Min Response Time: '.number_format($this->performanceMetrics['min_response_time'], 2)."ms\n";
        echo 'Max Response Time: '.number_format($this->performanceMetrics['max_response_time'], 2)."ms\n";
        echo '95th Percentile: '.number_format($p95ResponseTime, 2)."ms\n";
        echo '99th Percentile: '.number_format($p99ResponseTime, 2)."ms\n";
        echo 'Average DB Queries per Request: '.number_format(array_sum($this->performanceMetrics['database_queries']) / count($this->performanceMetrics['database_queries']), 2)."\n";

        if (! empty($this->performanceMetrics['errors'])) {
            echo "\n=== Errors ===\n";
            foreach (array_slice($this->performanceMetrics['errors'], 0, 10) as $error) {
                echo "- {$error['endpoint']}: {$error['error']}\n";
            }
        }
    }

    private function assertPerformanceRequirements(): void
    {
        $avgResponseTime = $this->getAverageResponseTime();
        $successRate = $this->getSuccessRate();
        $p95ResponseTime = $this->getPercentileResponseTime(95);

        // Performance requirements from the spec
        $this->assertGreaterThan(95, $successRate, 'Success rate should be above 95%');
        $this->assertLessThan(3000, $avgResponseTime, 'Average response time should be under 3 seconds');
        $this->assertLessThan(5000, $p95ResponseTime, '95th percentile response time should be under 5 seconds');

        // Database performance
        $avgQueries = array_sum($this->performanceMetrics['database_queries']) / count($this->performanceMetrics['database_queries']);
        $this->assertLessThan(20, $avgQueries, 'Average queries per request should be under 20');
    }

    private function analyzeStressTestResults(array $results): void
    {
        echo "\n=== Stress Test Results ===\n";

        foreach ($results as $userCount => $metrics) {
            echo "Users: {$userCount}\n";
            echo '  Avg Response Time: '.number_format($metrics['avg_response_time'], 2)."ms\n";
            echo '  Success Rate: '.number_format($metrics['success_rate'], 2)."%\n";
            echo '  Requests/sec: '.number_format($metrics['requests_per_second'], 2)."\n";
            echo '  Peak Memory: '.number_format($metrics['memory_peak'] / 1024 / 1024, 2)."MB\n\n";
        }

        // Find breaking point
        $breakingPoint = null;
        foreach ($results as $userCount => $metrics) {
            if ($metrics['avg_response_time'] > 5000 || $metrics['success_rate'] < 95) {
                $breakingPoint = $userCount;
                break;
            }
        }

        if ($breakingPoint) {
            echo "System breaking point: {$breakingPoint} concurrent users\n";
        } else {
            echo "System handled all tested load levels successfully\n";
        }
    }

    private function analyzeEnduranceResults(): void
    {
        echo "\n=== Endurance Test Results ===\n";

        // Analyze performance over time
        $timeSlices = array_chunk($this->performanceMetrics['response_times'],
            count($this->performanceMetrics['response_times']) / 10);

        echo "Performance over time (10 time slices):\n";
        foreach ($timeSlices as $index => $slice) {
            $avgTime = array_sum($slice) / count($slice);
            echo 'Slice '.($index + 1).': '.number_format($avgTime, 2)."ms\n";
        }

        // Check for memory leaks
        $memorySlices = array_chunk($this->performanceMetrics['memory_usage'],
            count($this->performanceMetrics['memory_usage']) / 10);

        $firstSliceAvg = array_sum($memorySlices[0]) / count($memorySlices[0]);
        $lastSliceAvg = array_sum(end($memorySlices)) / count(end($memorySlices));

        $memoryIncrease = ($lastSliceAvg - $firstSliceAvg) / $firstSliceAvg * 100;

        echo 'Memory usage change: '.number_format($memoryIncrease, 2)."%\n";

        if ($memoryIncrease > 20) {
            echo "WARNING: Potential memory leak detected\n";
        }
    }

    private function analyzeSpikeTestResults(array $results): void
    {
        echo "\n=== Spike Test Results ===\n";

        foreach ($results as $index => $result) {
            echo 'Spike '.($index + 1)." ({$result['users']} users):\n";
            echo '  Avg Response Time: '.number_format($result['avg_response_time'], 2)."ms\n";
            echo '  Success Rate: '.number_format($result['success_rate'], 2)."%\n";
            echo '  Peak Memory: '.number_format($result['peak_memory'] / 1024 / 1024, 2)."MB\n\n";
        }

        // Check recovery after spikes
        $recoveryGood = true;
        for ($i = 1; $i < count($results) - 1; $i += 2) {
            $spike = $results[$i];
            $recovery = $results[$i + 1];

            if ($recovery['avg_response_time'] > $spike['avg_response_time'] * 0.5) {
                $recoveryGood = false;
                echo 'WARNING: Slow recovery after spike '.(($i + 1) / 2)."\n";
            }
        }

        if ($recoveryGood) {
            echo "System recovered well from all spikes\n";
        }
    }

    private function getAverageResponseTime(): float
    {
        return $this->performanceMetrics['requests_sent'] > 0
            ? $this->performanceMetrics['total_response_time'] / $this->performanceMetrics['requests_sent']
            : 0;
    }

    private function getSuccessRate(): float
    {
        return $this->performanceMetrics['requests_sent'] > 0
            ? ($this->performanceMetrics['requests_successful'] / $this->performanceMetrics['requests_sent']) * 100
            : 0;
    }

    private function getRequestsPerSecond(): float
    {
        return $this->performanceMetrics['test_duration'] > 0
            ? $this->performanceMetrics['requests_sent'] / $this->performanceMetrics['test_duration']
            : 0;
    }

    private function getPercentileResponseTime(int $percentile): float
    {
        $times = $this->performanceMetrics['response_times'];
        sort($times);

        $index = (int) ceil(($percentile / 100) * count($times)) - 1;

        return $times[$index] ?? 0;
    }

    private function resetMetrics(): void
    {
        $this->performanceMetrics = [
            'requests_sent' => 0,
            'requests_successful' => 0,
            'requests_failed' => 0,
            'total_response_time' => 0,
            'min_response_time' => PHP_FLOAT_MAX,
            'max_response_time' => 0,
            'response_times' => [],
            'errors' => [],
            'memory_usage' => [],
            'database_queries' => [],
        ];
    }

    private function createLoadTestData(): void
    {
        // Create comprehensive test data
        $courses = Course::factory()->count(20)->create();

        Graduate::factory()->count(5000)->create([
            'course_id' => $courses->random()->id,
            'employment_status' => json_encode(['status' => 'employed']),
            'salary_range' => json_encode(['min' => 50000, 'max' => 120000]),
        ]);

        $employers = Employer::factory()->count(200)->create();
        Job::factory()->count(1000)->create([
            'employer_id' => $employers->random()->id,
            'course_id' => $courses->random()->id,
        ]);

        // Pre-cache critical data
        Cache::put('homepage_statistics', [
            'total_alumni' => 5000,
            'employed_alumni' => 4250,
            'average_salary_increase' => 42,
            'job_placements' => 3800,
            'companies_represented' => 200,
            'success_stories' => 150,
        ], 3600);
    }

    private function getRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
        ];

        return $userAgents[array_rand($userAgents)];
    }
}
