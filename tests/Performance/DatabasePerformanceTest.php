<?php

namespace Tests\Performance;

use App\Models\Course;
use App\Models\Employer;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabasePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable query logging for performance analysis
        DB::enableQueryLog();
    }

    public function test_graduate_search_performance_with_large_dataset(): void
    {
        // Create large dataset
        $courses = Course::factory()->count(50)->create();

        $startTime = microtime(true);

        // Create 10,000 graduates
        Graduate::factory()->count(10000)->create([
            'course_id' => $courses->random()->id,
        ]);

        $creationTime = microtime(true) - $startTime;
        $this->assertLessThan(30, $creationTime, 'Graduate creation took too long');

        // Test search performance
        $searchStartTime = microtime(true);

        $results = Graduate::where('employment_status->status', 'unemployed')
            ->where('job_search_active', true)
            ->whereJsonContains('skills', 'PHP')
            ->with(['course'])
            ->paginate(50);

        $searchTime = microtime(true) - $searchStartTime;
        $this->assertLessThan(2, $searchTime, 'Graduate search took too long');

        // Verify query efficiency
        $queries = DB::getQueryLog();
        $searchQueries = array_filter($queries, function ($query) {
            return strpos($query['query'], 'graduates') !== false;
        });

        $this->assertLessThan(5, count($searchQueries), 'Too many queries for graduate search');
    }

    public function test_job_matching_performance(): void
    {
        // Create test data
        $course = Course::factory()->create();
        $employer = Employer::factory()->create();

        // Create 5,000 graduates with various skills
        $skills = ['PHP', 'JavaScript', 'Python', 'Java', 'C#', 'Laravel', 'React', 'Vue.js'];
        Graduate::factory()->count(5000)->create([
            'course_id' => $course->id,
            'skills' => function () use ($skills) {
                return collect($skills)->random(rand(2, 5))->toArray();
            },
            'job_search_active' => true,
            'allow_employer_contact' => true,
        ]);

        // Create job with specific requirements
        $job = Job::factory()->create([
            'employer_id' => $employer->id,
            'course_id' => $course->id,
            'required_skills' => ['PHP', 'Laravel', 'MySQL'],
        ]);

        $startTime = microtime(true);

        // Test job matching performance
        $matches = $job->getMatchingGraduates();

        $matchingTime = microtime(true) - $startTime;
        $this->assertLessThan(3, $matchingTime, 'Job matching took too long');

        // Verify results quality
        $this->assertGreaterThan(0, $matches->count());

        foreach ($matches->take(10) as $graduate) {
            $this->assertContains('PHP', $graduate->skills);
            $this->assertTrue($graduate->job_search_active);
        }
    }

    public function test_analytics_calculation_performance(): void
    {
        // Create large dataset for analytics
        $courses = Course::factory()->count(20)->create();
        $employers = Employer::factory()->count(100)->create();

        // Create 15,000 graduates
        Graduate::factory()->count(15000)->create([
            'course_id' => function () use ($courses) {
                return $courses->random()->id;
            },
        ]);

        // Create 2,000 jobs
        Job::factory()->count(2000)->create([
            'employer_id' => function () use ($employers) {
                return $employers->random()->id;
            },
            'course_id' => function () use ($courses) {
                return $courses->random()->id;
            },
        ]);

        $startTime = microtime(true);

        // Test employment rate calculation
        $employmentRate = Graduate::where('employment_status->status', 'employed')->count() /
                         Graduate::count() * 100;

        $calculationTime = microtime(true) - $startTime;
        $this->assertLessThan(1, $calculationTime, 'Employment rate calculation took too long');

        $startTime = microtime(true);

        // Test course-wise analytics
        $courseAnalytics = Course::withCount([
            'graduates',
            'graduates as employed_count' => function ($query) {
                $query->where('employment_status->status', 'employed');
            },
        ])->get();

        $analyticsTime = microtime(true) - $startTime;
        $this->assertLessThan(2, $analyticsTime, 'Course analytics calculation took too long');

        $this->assertEquals(20, $courseAnalytics->count());
    }

    public function test_bulk_import_performance(): void
    {
        $course = Course::factory()->create();

        // Prepare bulk data
        $graduateData = [];
        for ($i = 0; $i < 5000; $i++) {
            $graduateData[] = [
                'course_id' => $course->id,
                'name' => "Graduate {$i}",
                'email' => "graduate{$i}@example.com",
                'student_id' => "STU{$i}",
                'graduation_year' => rand(2020, 2024),
                'gpa' => rand(250, 400) / 100,
                'skills' => json_encode(['PHP', 'JavaScript']),
                'employment_status' => json_encode(['status' => 'unemployed']),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $startTime = microtime(true);

        // Test bulk insert performance
        Graduate::insert($graduateData);

        $insertTime = microtime(true) - $startTime;
        $this->assertLessThan(10, $insertTime, 'Bulk insert took too long');

        // Verify data integrity
        $this->assertEquals(5000, Graduate::count());
    }

    public function test_concurrent_job_application_performance(): void
    {
        // Create test data
        $course = Course::factory()->create();
        $employer = Employer::factory()->create();
        $job = Job::factory()->active()->create([
            'employer_id' => $employer->id,
            'course_id' => $course->id,
        ]);

        $graduates = Graduate::factory()->count(1000)->create([
            'course_id' => $course->id,
        ]);

        $startTime = microtime(true);

        // Simulate concurrent applications
        $applicationData = [];
        foreach ($graduates as $graduate) {
            $applicationData[] = [
                'job_id' => $job->id,
                'graduate_id' => $graduate->id,
                'status' => 'pending',
                'applied_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        JobApplication::insert($applicationData);

        $applicationTime = microtime(true) - $startTime;
        $this->assertLessThan(5, $applicationTime, 'Bulk job applications took too long');

        // Verify application counts
        $this->assertEquals(1000, $job->applications()->count());
    }

    public function test_search_index_performance(): void
    {
        // Create searchable data
        Graduate::factory()->count(10000)->create();
        Job::factory()->count(2000)->create();

        $startTime = microtime(true);

        // Test full-text search performance
        $graduateResults = Graduate::where('name', 'LIKE', '%John%')
            ->orWhereJsonContains('skills', 'PHP')
            ->limit(100)
            ->get();

        $searchTime = microtime(true) - $startTime;
        $this->assertLessThan(1, $searchTime, 'Graduate search took too long');

        $startTime = microtime(true);

        // Test job search performance
        $jobResults = Job::where('title', 'LIKE', '%Developer%')
            ->orWhereJsonContains('required_skills', 'PHP')
            ->with(['employer', 'course'])
            ->limit(100)
            ->get();

        $jobSearchTime = microtime(true) - $startTime;
        $this->assertLessThan(1, $jobSearchTime, 'Job search took too long');
    }

    public function test_caching_performance(): void
    {
        // Create test data
        Course::factory()->count(50)->create();
        Graduate::factory()->count(5000)->create();

        // Test without cache
        $startTime = microtime(true);

        $stats = [
            'total_graduates' => Graduate::count(),
            'employed_graduates' => Graduate::where('employment_status->status', 'employed')->count(),
            'courses_count' => Course::count(),
        ];

        $noCacheTime = microtime(true) - $startTime;

        // Test with cache
        Cache::flush();

        $startTime = microtime(true);

        $cachedStats = Cache::remember('dashboard_stats', 3600, function () {
            return [
                'total_graduates' => Graduate::count(),
                'employed_graduates' => Graduate::where('employment_status->status', 'employed')->count(),
                'courses_count' => Course::count(),
            ];
        });

        $cacheTime = microtime(true) - $startTime;

        // Second call should be much faster
        $startTime = microtime(true);
        $cachedStats2 = Cache::get('dashboard_stats');
        $cachedRetrievalTime = microtime(true) - $startTime;

        $this->assertLessThan($noCacheTime / 2, $cacheTime, 'Cached query should be faster');
        $this->assertLessThan(0.01, $cachedRetrievalTime, 'Cache retrieval should be very fast');
        $this->assertEquals($stats, $cachedStats);
    }

    public function test_pagination_performance(): void
    {
        // Create large dataset
        Graduate::factory()->count(50000)->create();

        // Test first page performance
        $startTime = microtime(true);
        $firstPage = Graduate::with('course')->paginate(50);
        $firstPageTime = microtime(true) - $startTime;

        // Test middle page performance
        $startTime = microtime(true);
        $middlePage = Graduate::with('course')->paginate(50, ['*'], 'page', 500);
        $middlePageTime = microtime(true) - $startTime;

        // Test last page performance
        $startTime = microtime(true);
        $lastPage = Graduate::with('course')->paginate(50, ['*'], 'page', 1000);
        $lastPageTime = microtime(true) - $startTime;

        // All pagination queries should be reasonably fast
        $this->assertLessThan(1, $firstPageTime, 'First page pagination too slow');
        $this->assertLessThan(2, $middlePageTime, 'Middle page pagination too slow');
        $this->assertLessThan(2, $lastPageTime, 'Last page pagination too slow');

        // Verify pagination results
        $this->assertEquals(50, $firstPage->count());
        $this->assertEquals(50, $middlePage->count());
        $this->assertEquals(50, $lastPage->count());
    }

    public function test_database_connection_pool_performance(): void
    {
        $startTime = microtime(true);

        // Simulate multiple concurrent database operations
        $operations = [];
        for ($i = 0; $i < 100; $i++) {
            $operations[] = function () {
                return Graduate::inRandomOrder()->first();
            };
        }

        // Execute operations
        $results = [];
        foreach ($operations as $operation) {
            $results[] = $operation();
        }

        $totalTime = microtime(true) - $startTime;

        // Should handle 100 operations efficiently
        $this->assertLessThan(5, $totalTime, 'Database connection pool performance issue');
        $this->assertCount(100, $results);
    }

    protected function tearDown(): void
    {
        // Log query performance for analysis
        $queries = DB::getQueryLog();
        $slowQueries = array_filter($queries, function ($query) {
            return $query['time'] > 100; // Queries taking more than 100ms
        });

        if (! empty($slowQueries)) {
            echo "\nSlow queries detected:\n";
            foreach ($slowQueries as $query) {
                echo "Time: {$query['time']}ms - Query: {$query['query']}\n";
            }
        }

        parent::tearDown();
    }
}
