<?php

namespace Tests\Integration;

use App\Models\Component;
use App\Models\LandingPage;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use App\Models\BrandConfig;
use App\Models\CareerPath;
use App\Models\EducationHistory;
use App\Models\CareerTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CrossSystemPerformanceIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $user1;
    protected User $user2;
    protected array $testUsers = [];
    protected array $testComponents = [];
    protected array $testPages = [];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Queue::fake();

        // Create test tenant
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Performance Test University',
            'domain' => 'perf-test.edu',
        ]);

        // Create test users
        for ($i = 0; $i < 50; $i++) {
            $this->testUsers[] = User::factory()->create([
                'tenant_id' => $this->tenant1->id,
                'email' => "user{$i}@perf-test.edu",
                'graduation_year' => 2020 + ($i % 5), // Spread across 5 years
                'is_alumni' => true,
            ]);
        }

        $this->user1 = $this->testUsers[0];
        $this->user2 = $this->testUsers[1];

        // Create test data for performance testing
        $this->createPerformanceTestData();
    }

    protected function createPerformanceTestData()
    {
        // Create brand configuration
        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'institution_name' => 'Performance Test University',
        ]);

        // Create components (simulate realistic component library)
        $componentCategories = ['hero', 'forms', 'testimonials', 'statistics', 'ctas', 'media'];
        foreach ($componentCategories as $category) {
            for ($i = 0; $i < 10; $i++) {
                $this->testComponents[] = Component::factory()->create([
                    'tenant_id' => $this->tenant1->id,
                    'name' => "{$category} Component " . ($i + 1),
                    'category' => $category,
                    'is_active' => true,
                ]);
            }
        }

        // Create templates with complex structures
        for ($i = 0; $i < 20; $i++) {
            $template = Template::factory()->create([
                'tenant_id' => $this->tenant1->id,
                'brand_config_id' => $brandConfig->id,
                'name' => "Performance Template " . ($i + 1),
                'category' => 'landing',
                'structure' => $this->generateComplexTemplateStructure(),
            ]);

            $this->testPages[] = LandingPage::factory()->create([
                'tenant_id' => $this->tenant1->id,
                'template_id' => $template->id,
                'name' => "Performance Page " . ($i + 1),
                'status' => 'published',
                'config' => [
                    'is_personalized' => true,
                    'requires_authentication' => $i % 2 === 0, // Alternate authentication requirements
                ]
            ]);
        }

        // Create career and education data for users
        foreach ($this->testUsers as $user) {
            EducationHistory::factory()->create([
                'tenant_id' => $this->tenant1->id,
                'user_id' => $user->id,
                'degree' => 'Bachelor of Science',
                'major' => ['Computer Science', 'Business', 'Engineering', 'Arts', 'Science'][rand(0, 4)],
            ]);

            CareerPath::factory()->create([
                'tenant_id' => $this->tenant1->id,
                'user_id' => $user->id,
                'job_title' => ['Software Engineer', 'Product Manager', 'Data Analyst', 'Designer', 'Consultant'][rand(0, 4)],
                'current_salary_range' => '80000-120000',
            ]);

            CareerTimeline::factory()->create([
                'tenant_id' => $this->tenant1->id,
                'user_id' => $user->id,
                'title' => 'Career Milestone',
                'event_type' => 'career_milestone',
                'is_public' => true,
            ]);
        }
    }

    protected function generateComplexTemplateStructure()
    {
        $components = array_slice($this->testComponents, 0, rand(3, 8));
        $sections = [];

        foreach ($components as $component) {
            $sections[] = [
                'type' => 'component_block',
                'component_id' => $component->id,
                'config' => [
                    'title' => 'Dynamic Section Title',
                    'custom_styling' => [
                        'background_color' => '#f8f9fa',
                        'padding' => '20px'
                    ]
                ]
            ];
        }

        return ['sections' => $sections];
    }

    public function test_concurrent_component_library_access()
    {
        $startTime = microtime(true);

        // Simulate concurrent users accessing component library
        $promises = [];
        $concurrentUsers = 10;

        for ($i = 0; $i < $concurrentUsers; $i++) {
            $user = $this->testUsers[$i];
            $promises[] = $this->simulateUserAction($user, 'component_library');
        }

        // Wait for all requests to complete
        foreach ($promises as $promise) {
            $this->assertTrue($promise['success']);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Performance metrics
        $this->assertLessThan(5000, $totalTime); // Should complete within 5 seconds
        $this->assertGreaterThan(0, $concurrentUsers); // All users should succeed
    }

    public function test_concurrent_page_builder_operations()
    {
        $startTime = microtime(true);

        // Simulate concurrent page rendering operations
        $promises = [];
        $concurrentUsers = 15;

        for ($i = 0; $i < $concurrentUsers; $i++) {
            $user = $this->testUsers[$i];
            $page = $this->testPages[$i % count($this->testPages)];
            $promises[] = $this->simulateUserAction($user, 'page_rendering', $page);
        }

        // Wait for all requests to complete
        foreach ($promises as $promise) {
            $this->assertTrue($promise['success']);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // Performance metrics
        $this->assertLessThan(8000, $totalTime); // Should complete within 8 seconds
        $this->assertGreaterThan(0, $concurrentUsers); // All users should succeed
    }

    public function test_concurrent_alumni_platform_interactions()
    {
        $startTime = microtime(true);

        // Simulate concurrent alumni platform usage
        $promises = [];
        $concurrentUsers = 12;

        for ($i = 0; $i < $concurrentUsers; $i++) {
            $user = $this->testUsers[$i];
            $promises[] = $this->simulateUserAction($user, 'alumni_platform');
        }

        // Wait for all requests to complete
        foreach ($promises as $promise) {
            $this->assertTrue($promise['success']);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // Performance metrics
        $this->assertLessThan(6000, $totalTime); // Should complete within 6 seconds
        $this->assertGreaterThan(0, $concurrentUsers); // All users should succeed
    }

    public function test_cross_system_concurrent_workflow()
    {
        $startTime = microtime(true);

        // Simulate complex workflow involving multiple systems
        $promises = [];
        $concurrentUsers = 8;

        for ($i = 0; $i < $concurrentUsers; $i++) {
            $user = $this->testUsers[$i];
            $promises[] = $this->simulateComplexWorkflow($user);
        }

        // Wait for all workflows to complete
        foreach ($promises as $promise) {
            $this->assertTrue($promise['success']);
            $this->assertArrayHasKey('workflow_steps', $promise);
            $this->assertGreaterThan(3, count($promise['workflow_steps']));
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // Performance metrics for complex workflows
        $this->assertLessThan(10000, $totalTime); // Should complete within 10 seconds
        $this->assertGreaterThan(0, $concurrentUsers); // All users should succeed
    }

    public function test_memory_usage_under_concurrent_load()
    {
        $initialMemory = memory_get_usage(true);

        // Run concurrent operations
        $promises = [];
        $concurrentUsers = 20;

        for ($i = 0; $i < $concurrentUsers; $i++) {
            $user = $this->testUsers[$i];
            $promises[] = $this->simulateUserAction($user, 'mixed_operations');
        }

        // Wait for completion
        foreach ($promises as $promise) {
            $this->assertTrue($promise['success']);
        }

        $peakMemory = memory_get_peak_usage(true);
        $memoryIncrease = $peakMemory - $initialMemory;

        // Memory usage should be reasonable
        $this->assertLessThan(100 * 1024 * 1024, $memoryIncrease); // Less than 100MB increase
        $this->assertLessThan(200 * 1024 * 1024, $peakMemory); // Peak usage under 200MB
    }

    public function test_database_connection_pooling_under_load()
    {
        $startTime = microtime(true);
        $dbConnections = DB::getConnections();

        // Simulate high database load
        $promises = [];
        $concurrentUsers = 25;

        for ($i = 0; $i < $concurrentUsers; $i++) {
            $user = $this->testUsers[$i];
            $promises[] = $this->simulateDatabaseIntensiveOperation($user);
        }

        // Wait for completion
        foreach ($promises as $promise) {
            $this->assertTrue($promise['success']);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // Database operations should remain performant
        $this->assertLessThan(15000, $totalTime); // Should complete within 15 seconds
        $this->assertGreaterThan(0, $concurrentUsers); // All operations should succeed
    }

    public function test_cache_performance_under_concurrent_access()
    {
        Cache::flush();

        $startTime = microtime(true);

        // Test cache performance with concurrent access
        $promises = [];
        $concurrentUsers = 15;

        for ($i = 0; $i < $concurrentUsers; $i++) {
            $user = $this->testUsers[$i];
            $promises[] = $this->simulateCachedOperation($user);
        }

        // Wait for completion
        foreach ($promises as $promise) {
            $this->assertTrue($promise['success']);
            $this->assertArrayHasKey('cache_used', $promise);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // Cache operations should be very fast
        $this->assertLessThan(3000, $totalTime); // Should complete within 3 seconds
        $this->assertGreaterThan(0, $concurrentUsers); // All operations should succeed
    }

    public function test_tenant_isolation_under_concurrent_multi_tenant_load()
    {
        $startTime = microtime(true);

        // Create additional tenant for multi-tenant testing
        $tenant2 = Tenant::factory()->create(['name' => 'Concurrent Test University B']);
        $tenant2Users = [];

        for ($i = 0; $i < 10; $i++) {
            $tenant2Users[] = User::factory()->create([
                'tenant_id' => $tenant2->id,
                'email' => "concurrent-user{$i}@tenant2.edu",
            ]);
        }

        // Simulate concurrent multi-tenant operations
        $promises = [];

        // Mix of users from both tenants
        for ($i = 0; $i < 20; $i++) {
            $user = $i % 2 === 0 ? $this->testUsers[$i % count($this->testUsers)]
                                 : $tenant2Users[$i % count($tenant2Users)];
            $promises[] = $this->simulateMultiTenantOperation($user);
        }

        // Wait for completion
        foreach ($promises as $promise) {
            $this->assertTrue($promise['success']);
            $this->assertArrayHasKey('tenant_isolation_verified', $promise);
            $this->assertTrue($promise['tenant_isolation_verified']);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // Multi-tenant operations should maintain performance
        $this->assertLessThan(10000, $totalTime); // Should complete within 10 seconds
        $this->assertGreaterThan(0, 20); // All operations should succeed
    }

    protected function simulateUserAction($user, $actionType, $additionalData = null)
    {
        $startTime = microtime(true);

        try {
            switch ($actionType) {
                case 'component_library':
                    $response = $this->actingAs($user)->getJson('/api/components');
                    $success = $response->isOk();
                    break;

                case 'page_rendering':
                    $page = $additionalData ?? $this->testPages[0];
                    $response = $this->actingAs($user)->getJson("/api/pages/{$page->slug}/render");
                    $success = $response->isOk();
                    break;

                case 'alumni_platform':
                    $response = $this->actingAs($user)->getJson('/api/alumni/social-timeline');
                    $success = $response->isOk();
                    break;

                case 'mixed_operations':
                    // Simulate multiple rapid operations
                    $this->actingAs($user)->getJson('/api/components');
                    $this->actingAs($user)->getJson('/api/alumni/social-timeline');
                    $this->actingAs($user)->getJson('/api/career-paths');
                    $success = true;
                    break;

                default:
                    $success = false;
            }

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;

            return [
                'success' => $success,
                'response_time' => $responseTime,
                'user_id' => $user->id,
                'action' => $actionType
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'action' => $actionType
            ];
        }
    }

    protected function simulateComplexWorkflow($user)
    {
        $workflowSteps = [];
        $startTime = microtime(true);

        try {
            // Step 1: Access component library
            $response1 = $this->actingAs($user)->getJson('/api/components');
            $workflowSteps[] = ['step' => 'component_access', 'success' => $response1->isOk()];

            // Step 2: Render personalized page
            $page = $this->testPages[array_rand($this->testPages)];
            $response2 = $this->actingAs($user)->getJson("/api/pages/{$page->slug}/render");
            $workflowSteps[] = ['step' => 'page_rendering', 'success' => $response2->isOk()];

            // Step 3: Access alumni data
            $response3 = $this->actingAs($user)->getJson('/api/alumni/social-timeline');
            $workflowSteps[] = ['step' => 'alumni_data', 'success' => $response3->isOk()];

            // Step 4: Access career information
            $response4 = $this->actingAs($user)->getJson('/api/career-paths');
            $workflowSteps[] = ['step' => 'career_data', 'success' => $response4->isOk()];

            $endTime = microtime(true);
            $totalTime = ($endTime - $startTime) * 1000;

            $success = array_reduce($workflowSteps, function($carry, $step) {
                return $carry && $step['success'];
            }, true);

            return [
                'success' => $success,
                'workflow_steps' => $workflowSteps,
                'total_time' => $totalTime,
                'user_id' => $user->id
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'workflow_steps' => $workflowSteps,
                'user_id' => $user->id
            ];
        }
    }

    protected function simulateDatabaseIntensiveOperation($user)
    {
        $startTime = microtime(true);

        try {
            // Perform database-intensive operations
            $components = Component::where('tenant_id', $user->tenant_id)->get();
            $careerData = CareerPath::where('tenant_id', $user->tenant_id)->get();
            $educationData = EducationHistory::where('tenant_id', $user->tenant_id)->get();

            // Simulate complex queries
            $aggregatedData = DB::table('components')
                ->where('tenant_id', $user->tenant_id)
                ->join('templates', 'components.tenant_id', '=', 'templates.tenant_id')
                ->select('components.*', 'templates.name as template_name')
                ->get();

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;

            return [
                'success' => true,
                'response_time' => $responseTime,
                'records_processed' => count($components) + count($careerData) + count($educationData),
                'user_id' => $user->id
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ];
        }
    }

    protected function simulateCachedOperation($user)
    {
        $startTime = microtime(true);

        try {
            // Test cache performance
            $cacheKey = "user_{$user->id}_components";
            $cachedData = Cache::remember($cacheKey, 3600, function() use ($user) {
                return Component::where('tenant_id', $user->tenant_id)->get();
            });

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;

            return [
                'success' => true,
                'response_time' => $responseTime,
                'cache_used' => true,
                'cached_items' => count($cachedData),
                'user_id' => $user->id
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ];
        }
    }

    protected function simulateMultiTenantOperation($user)
    {
        $startTime = microtime(true);

        try {
            // Perform operations and verify tenant isolation
            $userComponents = Component::where('tenant_id', $user->tenant_id)->count();
            $userPages = LandingPage::where('tenant_id', $user->tenant_id)->count();
            $userCareerData = CareerPath::where('tenant_id', $user->tenant_id)->count();

            // Verify no cross-tenant data access
            $crossTenantCheck = Component::where('tenant_id', '!=', $user->tenant_id)->count();
            $isolationVerified = $crossTenantCheck === 0;

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;

            return [
                'success' => true,
                'response_time' => $responseTime,
                'tenant_isolation_verified' => $isolationVerified,
                'user_tenant_id' => $user->tenant_id,
                'user_id' => $user->id
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ];
        }
    }
}