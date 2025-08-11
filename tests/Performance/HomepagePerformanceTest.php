<?php

namespace Tests\Performance;

use App\Models\Course;
use App\Models\Employer;
use App\Models\Graduate;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomepagePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable query logging for performance analysis
        DB::enableQueryLog();

        // Clear all caches
        Cache::flush();
    }

    public function test_homepage_load_performance_under_concurrent_users(): void
    {
        // Create test data for homepage
        $this->createHomepageTestData();

        $concurrentRequests = 50;
        $startTime = microtime(true);

        // Simulate concurrent homepage requests
        $promises = [];
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $promises[] = $this->get('/');
        }

        // Wait for all requests to complete
        $responses = $promises;
        $totalTime = microtime(true) - $startTime;

        // Performance assertions
        $this->assertLessThan(10, $totalTime, 'Homepage should handle 50 concurrent requests within 10 seconds');

        // Verify all responses are successful
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Check query efficiency
        $queries = DB::getQueryLog();
        $this->assertLessThan(20, count($queries), 'Homepage should not execute more than 20 queries per request');
    }

    public function test_homepage_api_response_times(): void
    {
        $this->createHomepageTestData();

        // Test statistics API endpoint
        $startTime = microtime(true);
        $response = $this->get('/api/homepage/statistics');
        $statisticsTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        $this->assertLessThan(0.5, $statisticsTime, 'Statistics API should respond within 500ms');

        // Test testimonials API endpoint
        $startTime = microtime(true);
        $response = $this->get('/api/homepage/testimonials');
        $testimonialsTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        $this->assertLessThan(0.3, $testimonialsTime, 'Testimonials API should respond within 300ms');

        // Test success stories API endpoint
        $startTime = microtime(true);
        $response = $this->get('/api/homepage/success-stories');
        $successStoriesTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        $this->assertLessThan(0.8, $successStoriesTime, 'Success stories API should respond within 800ms');
    }

    public function test_homepage_caching_performance(): void
    {
        $this->createHomepageTestData();

        // First request (no cache)
        $startTime = microtime(true);
        $response1 = $this->get('/api/homepage/statistics');
        $noCacheTime = microtime(true) - $startTime;

        $response1->assertStatus(200);

        // Second request (with cache)
        $startTime = microtime(true);
        $response2 = $this->get('/api/homepage/statistics');
        $cachedTime = microtime(true) - $startTime;

        $response2->assertStatus(200);

        // Cached response should be significantly faster
        $this->assertLessThan($noCacheTime / 2, $cachedTime, 'Cached response should be at least 50% faster');
        $this->assertLessThan(0.1, $cachedTime, 'Cached response should be under 100ms');

        // Verify cache hit
        $this->assertTrue(Cache::has('homepage_statistics'), 'Statistics should be cached');
    }

    public function test_homepage_database_query_optimization(): void
    {
        $this->createHomepageTestData();

        DB::flushQueryLog();

        // Make homepage request
        $response = $this->get('/');
        $response->assertStatus(200);

        $queries = DB::getQueryLog();

        // Analyze query performance
        $slowQueries = array_filter($queries, function ($query) {
            return $query['time'] > 100; // Queries taking more than 100ms
        });

        $this->assertEmpty($slowQueries, 'No queries should take more than 100ms');

        // Check for N+1 query problems
        $duplicateQueries = $this->findDuplicateQueries($queries);
        $this->assertLessThan(3, count($duplicateQueries), 'Should have minimal duplicate queries');

        // Verify eager loading is used
        $this->assertQueryUsesEagerLoading($queries);
    }

    public function test_homepage_memory_usage(): void
    {
        $this->createHomepageTestData();

        $initialMemory = memory_get_usage(true);

        // Make multiple homepage requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->get('/');
            $response->assertStatus(200);
        }

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;

        // Memory increase should be reasonable (less than 50MB)
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 'Memory usage should not increase significantly');
    }

    public function test_homepage_image_optimization_performance(): void
    {
        // Test image loading performance
        $imageUrls = [
            '/images/hero-background.jpg',
            '/images/testimonial-1.jpg',
            '/images/testimonial-2.jpg',
            '/images/success-story-1.jpg',
        ];

        foreach ($imageUrls as $url) {
            $startTime = microtime(true);
            $response = $this->get($url);
            $loadTime = microtime(true) - $startTime;

            // Images should load quickly
            $this->assertLessThan(1, $loadTime, "Image {$url} should load within 1 second");

            // Check for proper caching headers
            $response->assertHeader('Cache-Control');
            $response->assertHeader('ETag');
        }
    }

    public function test_homepage_javascript_bundle_performance(): void
    {
        // Test JavaScript bundle loading
        $response = $this->get('/build/assets/homepage.js');

        // Bundle should exist and be reasonably sized
        $response->assertStatus(200);

        $contentLength = $response->headers->get('Content-Length');
        if ($contentLength) {
            // Bundle should be less than 500KB
            $this->assertLessThan(500 * 1024, (int) $contentLength, 'JavaScript bundle should be under 500KB');
        }

        // Check for compression
        $response->assertHeader('Content-Encoding', 'gzip');
    }

    public function test_homepage_css_performance(): void
    {
        // Test CSS loading
        $response = $this->get('/build/assets/homepage.css');

        $response->assertStatus(200);

        $contentLength = $response->headers->get('Content-Length');
        if ($contentLength) {
            // CSS should be less than 200KB
            $this->assertLessThan(200 * 1024, (int) $contentLength, 'CSS bundle should be under 200KB');
        }

        // Check for compression
        $response->assertHeader('Content-Encoding', 'gzip');
    }

    public function test_homepage_seo_performance(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Check for essential SEO elements
        $this->assertStringContains('<title>', $content, 'Page should have a title tag');
        $this->assertStringContains('<meta name="description"', $content, 'Page should have meta description');
        $this->assertStringContains('<meta property="og:', $content, 'Page should have Open Graph tags');
        $this->assertStringContains('application/ld+json', $content, 'Page should have structured data');

        // Check for performance-related meta tags
        $this->assertStringContains('<link rel="preload"', $content, 'Page should preload critical resources');
        $this->assertStringContains('<link rel="dns-prefetch"', $content, 'Page should use DNS prefetch');
    }

    public function test_homepage_accessibility_performance(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Check for accessibility features that don't impact performance
        $this->assertStringContains('alt=', $content, 'Images should have alt attributes');
        $this->assertStringContains('aria-label', $content, 'Interactive elements should have ARIA labels');
        $this->assertStringContains('<h1>', $content, 'Page should have proper heading structure');

        // Check for skip links
        $this->assertStringContains('skip-to-content', $content, 'Page should have skip navigation links');
    }

    public function test_homepage_analytics_performance(): void
    {
        $this->createHomepageTestData();

        // Test analytics tracking performance
        $startTime = microtime(true);

        $response = $this->post('/api/homepage/track-cta-click', [
            'action' => 'register',
            'section' => 'hero',
            'audience' => 'individual',
            'additional_data' => ['test' => true],
        ]);

        $trackingTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        $this->assertLessThan(0.2, $trackingTime, 'Analytics tracking should be under 200ms');
    }

    public function test_homepage_ab_testing_performance(): void
    {
        $this->createHomepageTestData();

        // Test A/B testing performance impact
        $startTime = microtime(true);

        $response = $this->get('/', [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; test)',
            'HTTP_X_FORWARDED_FOR' => '192.168.1.1',
        ]);

        $abTestTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        $this->assertLessThan(2, $abTestTime, 'A/B testing should not significantly impact load time');
    }

    private function createHomepageTestData(): void
    {
        // Create courses
        $courses = Course::factory()->count(10)->create();

        // Create graduates with success stories
        Graduate::factory()->count(1000)->create([
            'course_id' => $courses->random()->id,
            'employment_status' => json_encode(['status' => 'employed']),
            'salary_range' => json_encode(['min' => 50000, 'max' => 80000]),
        ]);

        // Create employers and jobs
        $employers = Employer::factory()->count(50)->create();
        Job::factory()->count(200)->create([
            'employer_id' => $employers->random()->id,
            'course_id' => $courses->random()->id,
        ]);

        // Cache some data that would normally be cached
        Cache::put('homepage_statistics', [
            'total_alumni' => 1000,
            'employed_alumni' => 850,
            'average_salary_increase' => 35,
            'job_placements' => 750,
        ], 3600);

        Cache::put('homepage_testimonials', [
            [
                'name' => 'John Doe',
                'role' => 'Software Engineer',
                'company' => 'Tech Corp',
                'testimonial' => 'Great platform for networking!',
                'image' => '/images/testimonial-1.jpg',
            ],
        ], 3600);
    }

    private function findDuplicateQueries(array $queries): array
    {
        $queryStrings = array_column($queries, 'query');
        $duplicates = [];

        foreach (array_count_values($queryStrings) as $query => $count) {
            if ($count > 1) {
                $duplicates[] = $query;
            }
        }

        return $duplicates;
    }

    private function assertQueryUsesEagerLoading(array $queries): void
    {
        $hasEagerLoading = false;

        foreach ($queries as $query) {
            if (strpos($query['query'], 'left join') !== false ||
                strpos($query['query'], 'inner join') !== false) {
                $hasEagerLoading = true;
                break;
            }
        }

        $this->assertTrue($hasEagerLoading, 'Queries should use eager loading to prevent N+1 problems');
    }

    protected function tearDown(): void
    {
        // Log performance metrics
        $queries = DB::getQueryLog();
        $totalQueryTime = array_sum(array_column($queries, 'time'));

        if ($totalQueryTime > 500) {
            echo "\nWarning: Total query time exceeded 500ms: {$totalQueryTime}ms\n";
        }

        parent::tearDown();
    }
}
