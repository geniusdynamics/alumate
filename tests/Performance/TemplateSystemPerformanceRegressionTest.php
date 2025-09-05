<?php

namespace Tests\Performance;

use App\Models\BrandConfig;
use App\Models\Institution;
use App\Models\LandingPage;
use App\Models\Tenant;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TemplateSystemPerformanceRegressionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant;
    protected Institution $institution;
    protected User $user;
    protected array $performanceBaselines = [];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        DB::flushQueryLog();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Performance Test University',
            'domain' => 'performance-test.edu',
        ]);

        $this->institution = Institution::factory()->create([
            'name' => 'Performance Test University',
            'domain' => 'performance-test.edu',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Performance Admin',
            'email' => 'admin@performance-test.edu',
            'tenant_id' => $this->tenant->id,
            'institution_id' => $this->institution->id,
        ]);

        // Set performance baselines
        $this->performanceBaselines = [
            'template_creation' => 0.5, // seconds
            'landing_page_creation' => 1.0, // seconds
            'template_rendering' => 2.0, // seconds
            'bulk_operation' => 5.0, // seconds
            'search_operation' => 0.2, // seconds
            'cache_hit_ratio' => 0.85, // 85%
            'memory_usage' => 50 * 1024 * 1024, // 50MB
            'database_queries' => 10, // max queries per operation
        ];
    }

    public function test_template_creation_performance_regression()
    {
        $templateData = [
            'name' => 'Performance Test Template',
            'description' => 'Template for performance testing',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Performance Test Title',
                            'subtitle' => 'Testing performance metrics',
                            'cta_text' => 'Get Started'
                        ]
                    ],
                    [
                        'type' => 'statistics',
                        'config' => [
                            'title' => 'Key Metrics',
                            'items' => [
                                ['label' => 'Users', 'value' => '10,000+'],
                                ['label' => 'Conversion', 'value' => '15%'],
                                ['label' => 'Satisfaction', 'value' => '98%']
                            ]
                        ]
                    ],
                    [
                        'type' => 'form',
                        'config' => [
                            'title' => 'Contact Us',
                            'fields' => [
                                ['type' => 'text', 'name' => 'first_name', 'label' => 'First Name', 'required' => true],
                                ['type' => 'text', 'name' => 'last_name', 'label' => 'Last Name', 'required' => true],
                                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
                                ['type' => 'textarea', 'name' => 'message', 'label' => 'Message']
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Measure creation performance
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)
            ->postJson('/api/templates', $templateData);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        $queries = DB::getQueryLog();

        $creationTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;

        // Performance assertions
        $this->assertLessThan(
            $this->performanceBaselines['template_creation'],
            $creationTime,
            "Template creation time regression: {$creationTime}s (baseline: {$this->performanceBaselines['template_creation']}s)"
        );

        $this->assertLessThan(
            $this->performanceBaselines['memory_usage'],
            $memoryUsed,
            "Memory usage regression: " . round($memoryUsed / 1024 / 1024, 2) . "MB (baseline: " . round($this->performanceBaselines['memory_usage'] / 1024 / 1024, 2) . "MB)"
        );

        $this->assertLessThanOrEqual(
            $this->performanceBaselines['database_queries'],
            count($queries),
            "Database query count regression: " . count($queries) . " queries (baseline: {$this->performanceBaselines['database_queries']})"
        );

        $response->assertStatus(201);

        // Verify template was created
        $template = Template::find($response->json('data.id'));
        $this->assertNotNull($template);
        $this->assertEquals($this->tenant->id, $template->tenant_id);
    }

    public function test_landing_page_creation_performance_regression()
    {
        // Create template first
        $template = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'structure' => [
                'sections' => [
                    ['type' => 'hero', 'config' => ['title' => 'Test Hero']],
                    ['type' => 'form', 'config' => ['title' => 'Test Form']]
                ]
            ]
        ]);

        $landingPageData = [
            'template_id' => $template->id,
            'name' => 'Performance Test Landing Page',
            'campaign_type' => 'marketing',
            'audience_type' => 'individual',
            'category' => $this->institution->id,
            'config' => [
                'hero' => [
                    'title' => 'Custom Performance Test Title',
                    'subtitle' => 'Testing landing page creation performance'
                ],
                'form' => [
                    'title' => 'Get Started Today',
                    'fields' => [
                        ['type' => 'text', 'name' => 'name', 'label' => 'Full Name', 'required' => true],
                        ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
                        ['type' => 'tel', 'name' => 'phone', 'label' => 'Phone']
                    ]
                ]
            ],
            'seo_title' => 'Performance Test Landing Page',
            'seo_description' => 'Testing landing page performance metrics',
            'tracking_id' => 'UA-' . $this->faker->randomNumber(8)
        ];

        // Measure creation performance
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)
            ->postJson('/api/landing-pages', $landingPageData);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        $queries = DB::getQueryLog();

        $creationTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;

        // Performance assertions
        $this->assertLessThan(
            $this->performanceBaselines['landing_page_creation'],
            $creationTime,
            "Landing page creation time regression: {$creationTime}s (baseline: {$this->performanceBaselines['landing_page_creation']}s)"
        );

        $this->assertLessThan(
            $this->performanceBaselines['memory_usage'],
            $memoryUsed,
            "Memory usage regression: " . round($memoryUsed / 1024 / 1024, 2) . "MB"
        );

        $this->assertLessThanOrEqual(
            $this->performanceBaselines['database_queries'] * 2, // Allow more queries for landing page creation
            count($queries),
            "Database query count regression: " . count($queries) . " queries"
        );

        $response->assertStatus(201);

        // Verify landing page was created
        $landingPage = LandingPage::find($response->json('data.id'));
        $this->assertNotNull($landingPage);
        $this->assertEquals($template->id, $landingPage->template_id);
    }

    public function test_template_rendering_performance_regression()
    {
        // Create complex template with brand integration
        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'institution_name' => 'Performance University',
            'primary_color' => '#1a365d',
            'logo_url' => 'https://performance.edu/logo.png'
        ]);

        $complexTemplate = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'brand_config_id' => $brandConfig->id,
            'structure' => [
                'sections' => array_fill(0, 10, [
                    'type' => 'content',
                    'config' => [
                        'title' => 'Complex Content Section with Brand Integration {{institution_name}}',
                        'body' => str_repeat('This is a complex content section with lots of text for performance testing. ', 20),
                        'media' => [
                            'images' => [
                                ['url' => 'https://example.com/image1.jpg', 'alt' => 'Test image 1'],
                                ['url' => 'https://example.com/image2.jpg', 'alt' => 'Test image 2']
                            ]
                        ],
                        'links' => [
                            ['text' => 'Learn More', 'url' => '{{website_url}}'],
                            ['text' => 'Contact Us', 'url' => 'mailto:info@{{domain}}']
                        ]
                    ]
                ])
            ]
        ]);

        // Create landing page with customizations
        $landingPage = LandingPage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $complexTemplate->id,
            'config' => [
                'hero' => ['title' => 'Custom Performance Test Title'],
                'contact' => ['email' => 'custom@performance.edu']
            ]
        ]);

        // Measure rendering performance
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $response = $this->actingAs($this->user)
            ->getJson("/api/landing-pages/{$landingPage->id}/render");

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $renderTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;

        // Performance assertions
        $this->assertLessThan(
            $this->performanceBaselines['template_rendering'],
            $renderTime,
            "Template rendering time regression: {$renderTime}s (baseline: {$this->performanceBaselines['template_rendering']}s)"
        );

        $this->assertLessThan(
            $this->performanceBaselines['memory_usage'] * 2, // Allow more memory for complex rendering
            $memoryUsed,
            "Memory usage regression: " . round($memoryUsed / 1024 / 1024, 2) . "MB"
        );

        $response->assertStatus(200);

        // Verify rendered content
        $rendered = $response->json('data');
        $this->assertArrayHasKey('html', $rendered);
        $this->assertArrayHasKey('css', $rendered);
        $this->assertStringContainsString('Performance University', $rendered['html']);
        $this->assertStringContainsString('Custom Performance Test Title', $rendered['html']);
    }

    public function test_bulk_operations_performance_regression()
    {
        // Create multiple templates for bulk operations
        $templates = Template::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id
        ]);

        $bulkUpdateData = [
            'templates' => $templates->map(function ($template) {
                return [
                    'id' => $template->id,
                    'category' => 'updated_category',
                    'audience_type' => 'updated_audience'
                ];
            })->toArray()
        ];

        // Measure bulk operation performance
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)
            ->putJson('/api/templates/bulk', $bulkUpdateData);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        $queries = DB::getQueryLog();

        $operationTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;

        // Performance assertions
        $this->assertLessThan(
            $this->performanceBaselines['bulk_operation'],
            $operationTime,
            "Bulk operation time regression: {$operationTime}s (baseline: {$this->performanceBaselines['bulk_operation']}s)"
        );

        $this->assertLessThan(
            $this->performanceBaselines['memory_usage'],
            $memoryUsed,
            "Memory usage regression: " . round($memoryUsed / 1024 / 1024, 2) . "MB"
        );

        // Bulk operations might require more queries but should be efficient
        $this->assertLessThanOrEqual(
            $this->performanceBaselines['database_queries'] * 5,
            count($queries),
            "Database query count regression: " . count($queries) . " queries"
        );

        $response->assertStatus(200);

        // Verify bulk update
        $updatedTemplates = $response->json('data');
        $this->assertCount(20, $updatedTemplates);

        foreach ($updatedTemplates as $updatedTemplate) {
            $this->assertEquals('updated_category', $updatedTemplate['category']);
            $this->assertEquals('updated_audience', $updatedTemplate['audience_type']);
        }
    }

    public function test_search_and_filter_performance_regression()
    {
        // Create diverse templates for search testing
        $templates = [];
        $categories = ['landing', 'email', 'social', 'form'];
        $audienceTypes = ['individual', 'institution', 'general'];
        $campaignTypes = ['marketing', 'onboarding', 'leadership', 'outreach'];

        for ($i = 0; $i < 100; $i++) {
            $templates[] = Template::factory()->create([
                'tenant_id' => $this->tenant->id,
                'name' => "Search Test Template {$i}",
                'category' => $categories[array_rand($categories)],
                'audience_type' => $audienceTypes[array_rand($audienceTypes)],
                'campaign_type' => $campaignTypes[array_rand($campaignTypes)],
                'is_premium' => $i % 10 === 0, // 10% premium
                'tags' => ['tag' . ($i % 5), 'category_' . $categories[$i % 4]]
            ]);
        }

        // Test search performance
        $searchQueries = [
            ['category' => 'landing'],
            ['audience_type' => 'individual', 'is_premium' => 'false'],
            ['campaign_type' => 'marketing', 'tags' => 'tag1'],
            ['name' => 'Template 5']
        ];

        foreach ($searchQueries as $query) {
            $startTime = microtime(true);
            DB::enableQueryLog();

            $response = $this->actingAs($this->user)
                ->getJson('/api/templates?' . http_build_query($query));

            $endTime = microtime(true);
            $queries = DB::getQueryLog();

            $searchTime = $endTime - $startTime;

            // Performance assertions
            $this->assertLessThan(
                $this->performanceBaselines['search_operation'],
                $searchTime,
                "Search operation time regression: {$searchTime}s for query: " . json_encode($query)
            );

            $this->assertLessThanOrEqual(
                $this->performanceBaselines['database_queries'],
                count($queries),
                "Search query count regression: " . count($queries) . " queries"
            );

            $response->assertStatus(200);
        }
    }

    public function test_caching_performance_regression()
    {
        // Create template and landing page
        $template = Template::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $landingPage = LandingPage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $template->id
        ]);

        // Clear cache and test cold cache performance
        Cache::flush();

        $responseTimes = [];
        $cacheHits = 0;
        $totalRequests = 10;

        for ($i = 0; $i < $totalRequests; $i++) {
            $startTime = microtime(true);

            $response = $this->actingAs($this->user)
                ->getJson("/api/landing-pages/{$landingPage->id}/render");

            $endTime = microtime(true);
            $responseTimes[] = $endTime - $startTime;

            $response->assertStatus(200);

            // Check if response was cached (implementation dependent)
            $responseData = $response->json();
            if (isset($responseData['meta']['cached']) && $responseData['meta']['cached']) {
                $cacheHits++;
            }
        }

        $averageResponseTime = array_sum($responseTimes) / count($responseTimes);
        $cacheHitRatio = $cacheHits / $totalRequests;

        // Performance assertions
        $this->assertLessThan(
            1.0, // 1 second average response time
            $averageResponseTime,
            "Average response time regression: {$averageResponseTime}s"
        );

        $this->assertGreaterThanOrEqual(
            $this->performanceBaselines['cache_hit_ratio'],
            $cacheHitRatio,
            "Cache hit ratio regression: " . round($cacheHitRatio * 100, 2) . "% (baseline: " . round($this->performanceBaselines['cache_hit_ratio'] * 100, 2) . "%)"
        );

        // Verify cache effectiveness (cached requests should be faster)
        $firstRequestTime = $responseTimes[0];
        $lastRequestTime = end($responseTimes);

        if ($cacheHitRatio > 0.5) { // If we have decent cache hits
            $this->assertLessThan(
                $firstRequestTime * 0.5, // At least 50% faster
                $lastRequestTime,
                "Cache effectiveness regression: cached request not significantly faster"
            );
        }
    }

    public function test_concurrent_user_performance_regression()
    {
        // Create shared template
        $template = Template::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        // Simulate concurrent users creating landing pages
        $concurrentOperations = 5;
        $responses = [];
        $startTime = microtime(true);

        // Note: In a real scenario, you'd use actual concurrent requests
        // This is a simplified simulation
        for ($i = 0; $i < $concurrentOperations; $i++) {
            $landingPageData = [
                'template_id' => $template->id,
                'name' => "Concurrent Test Landing Page {$i}",
                'campaign_type' => 'marketing',
                'audience_type' => 'individual',
                'category' => $this->institution->id
            ];

            $responses[] = $this->actingAs($this->user)
                ->postJson('/api/landing-pages', $landingPageData);
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $averageTime = $totalTime / $concurrentOperations;

        // Performance assertions for concurrent operations
        $this->assertLessThan(
            $this->performanceBaselines['landing_page_creation'] * 2, // Allow some overhead for concurrency
            $averageTime,
            "Concurrent operation time regression: {$averageTime}s per operation"
        );

        // Verify all operations succeeded
        foreach ($responses as $response) {
            $response->assertStatus(201);
        }

        // Verify no race conditions (all landing pages created)
        $landingPagesCount = LandingPage::where('template_id', $template->id)->count();
        $this->assertEquals($concurrentOperations, $landingPagesCount);
    }

    public function test_memory_leak_regression()
    {
        // Test for memory leaks during repeated operations
        $initialMemory = memory_get_usage();
        $iterations = 20;

        for ($i = 0; $i < $iterations; $i++) {
            // Create and render template
            $template = Template::factory()->create([
                'tenant_id' => $this->tenant->id,
                'structure' => [
                    'sections' => [
                        ['type' => 'hero', 'config' => ['title' => "Test {$i}"]],
                        ['type' => 'form', 'config' => ['title' => "Form {$i}"]]
                    ]
                ]
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/templates/{$template->id}/render");

            $response->assertStatus(200);

            // Clean up
            $template->delete();

            // Force garbage collection (PHP 7.3+)
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        $averageMemoryPerIteration = $memoryIncrease / $iterations;

        // Memory leak detection
        $this->assertLessThan(
            1024 * 1024, // 1MB per iteration max
            $averageMemoryPerIteration,
            "Memory leak detected: " . round($averageMemoryPerIteration / 1024, 2) . "KB per iteration"
        );

        // Overall memory increase should be reasonable
        $this->assertLessThan(
            10 * 1024 * 1024, // 10MB total max
            $memoryIncrease,
            "Excessive memory usage: " . round($memoryIncrease / 1024 / 1024, 2) . "MB total increase"
        );
    }

    public function test_database_performance_regression()
    {
        // Test database performance with large datasets
        $templateCount = 100;

        // Create large number of templates
        $startTime = microtime(true);
        Template::factory()->count($templateCount)->create([
            'tenant_id' => $this->tenant->id
        ]);
        $creationTime = microtime(true) - $startTime;

        // Test query performance
        DB::enableQueryLog();

        $startTime = microtime(true);
        $templates = Template::where('tenant_id', $this->tenant->id)
            ->where('category', 'landing')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
        $queryTime = microtime(true) - $startTime;
        $queries = DB::getQueryLog();

        // Performance assertions
        $this->assertLessThan(
            2.0, // 2 seconds for bulk creation
            $creationTime,
            "Bulk creation time regression: {$creationTime}s for {$templateCount} templates"
        );

        $this->assertLessThan(
            0.5, // 500ms for complex query
            $queryTime,
            "Query time regression: {$queryTime}s"
        );

        $this->assertLessThanOrEqual(
            5, // Max 5 queries for optimized operation
            count($queries),
            "Query count regression: " . count($queries) . " queries"
        );

        $this->assertCount(50, $templates);
    }
}