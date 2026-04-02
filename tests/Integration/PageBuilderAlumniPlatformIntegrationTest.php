<?php

namespace Tests\Integration;

use App\Models\LandingPage;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use App\Models\BrandConfig;
use App\Models\Institution;
use App\Models\CareerPath;
use App\Models\EducationHistory;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageBuilderAlumniPlatformIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $alumni1;
    protected User $alumni2;
    protected User $admin1;
    protected Institution $institution1;
    protected BrandConfig $brandConfig1;
    protected Template $dashboardTemplate;
    protected LandingPage $alumniDashboard;
    protected LandingPage $careerPortal;
    protected Company $company1;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('public');

        // Create two tenants for cross-tenant testing
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Page Builder University A',
            'domain' => 'pb-a.edu',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Page Builder University B',
            'domain' => 'pb-b.edu',
        ]);

        $this->institution1 = Institution::factory()->create([
            'name' => 'Page Builder University A',
            'domain' => 'pb-a.edu',
        ]);

        // Create brand configuration
        $this->brandConfig1 = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'institution_name' => 'Page Builder University A',
            'primary_color' => '#1a365d',
            'secondary_color' => '#2d3748',
            'logo_url' => 'https://pb-a.edu/logo.png',
        ]);

        // Create users for tenant 1
        $this->alumni1 = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'institution_id' => $this->institution1->id,
            'email' => 'alumni1@pb-a.edu',
            'graduation_year' => 2020,
            'is_alumni' => true,
        ]);

        $this->alumni2 = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'institution_id' => $this->institution1->id,
            'email' => 'alumni2@pb-a.edu',
            'graduation_year' => 2021,
            'is_alumni' => true,
        ]);

        $this->admin1 = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'institution_id' => $this->institution1->id,
            'email' => 'admin@pb-a.edu',
            'is_admin' => true,
        ]);

        // Create company for career data
        $this->company1 = Company::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Alumni Tech Corp',
            'industry' => 'Technology',
        ]);

        // Create education and career data for alumni1
        EducationHistory::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->alumni1->id,
            'degree' => 'Bachelor of Science',
            'major' => 'Computer Science',
            'graduation_year' => 2020,
        ]);

        CareerPath::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->alumni1->id,
            'current_company' => $this->company1->id,
            'job_title' => 'Senior Software Engineer',
            'start_date' => '2020-06-01',
            'current_salary_range' => '90000-110000',
        ]);

        // Create dashboard template with dynamic components
        $this->dashboardTemplate = Template::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'brand_config_id' => $this->brandConfig1->id,
            'name' => 'Alumni Dashboard Template',
            'category' => 'dashboard',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'dynamic_hero',
                        'config' => [
                            'title' => 'Welcome back, {{user_name}}!',
                            'subtitle' => 'Class of {{graduation_year}} • {{degree}}',
                            'show_career_status' => true,
                            'show_achievements' => true
                        ]
                    ],
                    [
                        'type' => 'career_timeline',
                        'config' => [
                            'show_company_logos' => true,
                            'timeline_items' => 'user_career_data',
                            'highlight_promotions' => true
                        ]
                    ],
                    [
                        'type' => 'alumni_network',
                        'config' => [
                            'show_connection_count' => true,
                            'show_recent_activity' => true,
                            'filter_by_graduation_year' => true
                        ]
                    ],
                    [
                        'type' => 'personalized_content',
                        'config' => [
                            'content_type' => 'job_opportunities',
                            'filter_by_industry' => 'user_industry',
                            'max_items' => 5
                        ]
                    ]
                ]
            ]
        ]);

        // Create alumni dashboard page
        $this->alumniDashboard = LandingPage::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'template_id' => $this->dashboardTemplate->id,
            'name' => 'Alumni Dashboard',
            'slug' => 'alumni-dashboard',
            'status' => 'published',
            'page_type' => 'dashboard',
            'config' => [
                'is_personalized' => true,
                'requires_authentication' => true,
                'allowed_user_types' => ['alumni', 'admin'],
                'dynamic_content_sections' => [
                    'career_timeline',
                    'personalized_content',
                    'alumni_network'
                ]
            ]
        ]);

        // Create career portal page
        $this->careerPortal = LandingPage::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'template_id' => $this->dashboardTemplate->id,
            'name' => 'Career Portal',
            'slug' => 'career-portal',
            'status' => 'published',
            'page_type' => 'portal',
            'config' => [
                'is_personalized' => true,
                'requires_authentication' => false,
                'show_job_listings' => true,
                'show_company_profiles' => true,
                'allow_job_applications' => true
            ]
        ]);
    }

    public function test_personalized_dashboard_rendering_for_authenticated_alumni()
    {
        // Test personalized dashboard rendering
        $response = $this->actingAs($this->alumni1)
            ->getJson("/api/pages/{$this->alumniDashboard->slug}/render");

        $response->assertStatus(200);
        $renderedData = $response->json('data');

        $this->assertArrayHasKey('html', $renderedData);
        $this->assertArrayHasKey('personalized_data', $renderedData);
        $this->assertArrayHasKey('user_context', $renderedData);

        // Verify personalization
        $this->assertStringContainsString($this->alumni1->name, $renderedData['html']);
        $this->assertStringContainsString('2020', $renderedData['html']);
        $this->assertStringContainsString('Computer Science', $renderedData['html']);
        $this->assertStringContainsString('Senior Software Engineer', $renderedData['html']);
    }

    public function test_dynamic_content_integration_with_user_data()
    {
        // Test dynamic content sections based on user data
        $response = $this->actingAs($this->alumni1)
            ->getJson("/api/pages/{$this->alumniDashboard->slug}/dynamic-content");

        $response->assertStatus(200);
        $dynamicContent = $response->json('data');

        $this->assertArrayHasKey('career_timeline', $dynamicContent);
        $this->assertArrayHasKey('personalized_content', $dynamicContent);
        $this->assertArrayHasKey('alumni_network', $dynamicContent);

        // Verify career timeline data
        $this->assertNotEmpty($dynamicContent['career_timeline']['items']);
        $this->assertEquals('Senior Software Engineer', $dynamicContent['career_timeline']['items'][0]['job_title']);
        $this->assertEquals('Alumni Tech Corp', $dynamicContent['career_timeline']['items'][0]['company_name']);

        // Verify personalized content based on user's industry
        $this->assertNotEmpty($dynamicContent['personalized_content']['items']);
        $this->assertEquals('Technology', $dynamicContent['personalized_content']['industry_filter']);
    }

    public function test_career_portal_job_matching_integration()
    {
        // Test career portal with job matching
        $response = $this->actingAs($this->alumni1)
            ->getJson("/api/pages/{$this->careerPortal->slug}/jobs");

        $response->assertStatus(200);
        $jobData = $response->json('data');

        $this->assertArrayHasKey('matched_jobs', $jobData);
        $this->assertArrayHasKey('company_profiles', $jobData);
        $this->assertArrayHasKey('application_stats', $jobData);

        // Verify job matching based on user profile
        $this->assertNotEmpty($jobData['matched_jobs']);
        $this->assertGreaterThan(0, $jobData['application_stats']['total_applications']);
    }

    public function test_alumni_network_section_integration()
    {
        // Create network connections and activity
        $connection1 = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'institution_id' => $this->institution1->id,
            'graduation_year' => 2020,
            'is_alumni' => true,
        ]);

        $connection2 = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'institution_id' => $this->institution1->id,
            'graduation_year' => 2021,
            'is_alumni' => true,
        ]);

        // Test alumni network section
        $response = $this->actingAs($this->alumni1)
            ->getJson("/api/pages/{$this->alumniDashboard->slug}/network");

        $response->assertStatus(200);
        $networkData = $response->json('data');

        $this->assertArrayHasKey('connections', $networkData);
        $this->assertArrayHasKey('recent_activity', $networkData);
        $this->assertArrayHasKey('connection_count', $networkData);
        $this->assertGreaterThan(0, $networkData['connection_count']);
    }

    public function test_dashboard_analytics_and_metrics_integration()
    {
        // Test dashboard analytics integration
        $response = $this->actingAs($this->alumni1)
            ->getJson("/api/pages/{$this->alumniDashboard->slug}/analytics");

        $response->assertStatus(200);
        $analyticsData = $response->json('data');

        $this->assertArrayHasKey('user_engagement', $analyticsData);
        $this->assertArrayHasKey('career_progress', $analyticsData);
        $this->assertArrayHasKey('network_activity', $analyticsData);
        $this->assertArrayHasKey('content_personalization', $analyticsData);

        // Verify metrics are user-specific
        $this->assertEquals($this->alumni1->id, $analyticsData['user_context']['user_id']);
        $this->assertGreaterThan(0, $analyticsData['career_progress']['years_experience']);
    }

    public function test_cross_tenant_page_isolation()
    {
        // Create page in tenant 2
        $tenant2Page = LandingPage::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Tenant 2 Dashboard',
            'slug' => 'tenant2-dashboard',
            'status' => 'published',
        ]);

        // Test tenant 1 cannot access tenant 2 pages
        $response = $this->actingAs($this->alumni1)
            ->getJson("/api/pages/{$tenant2Page->slug}/render");

        $response->assertStatus(403);

        // Test tenant 2 user cannot access tenant 1 pages
        $tenant2User = User::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'is_alumni' => true,
        ]);

        $response = $this->actingAs($tenant2User)
            ->getJson("/api/pages/{$this->alumniDashboard->slug}/render");

        $response->assertStatus(403);

        // Verify each tenant only sees their own pages
        $response = $this->actingAs($this->alumni1)
            ->getJson('/api/pages');

        $response->assertStatus(200);
        $tenant1Pages = $response->json('data');

        $response = $this->actingAs($tenant2User)
            ->getJson('/api/pages');

        $response->assertStatus(200);
        $tenant2Pages = $response->json('data');

        // Each tenant should only see their own pages
        $this->assertGreaterThan(0, count($tenant1Pages));
        $this->assertGreaterThan(0, count($tenant2Pages));

        // Verify no cross-contamination
        $tenant1Slugs = array_column($tenant1Pages, 'slug');
        $tenant2Slugs = array_column($tenant2Pages, 'slug');

        $this->assertNotContains($tenant2Page->slug, $tenant1Slugs);
        $this->assertNotContains($this->alumniDashboard->slug, $tenant2Slugs);
    }

    public function test_page_builder_template_variable_substitution()
    {
        // Test template variable substitution with user data
        $templateWithVariables = Template::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Variable Test Template',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Hello {{user_name}} from {{institution_name}}!',
                            'subtitle' => 'Graduate of {{graduation_year}} with {{degree}}',
                            'welcome_message' => 'Welcome back to your alumni dashboard'
                        ]
                    ]
                ]
            ]
        ]);

        $testPage = LandingPage::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'template_id' => $templateWithVariables->id,
            'name' => 'Variable Test Page',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->alumni1)
            ->getJson("/api/pages/{$testPage->slug}/render");

        $response->assertStatus(200);
        $renderedContent = $response->json('data');

        // Verify variable substitution
        $this->assertStringContainsString($this->alumni1->name, $renderedContent['html']);
        $this->assertStringContainsString($this->brandConfig1->institution_name, $renderedContent['html']);
        $this->assertStringContainsString('2020', $renderedContent['html']);
        $this->assertStringContainsString('Computer Science', $renderedContent['html']);
    }

    public function test_page_performance_with_dynamic_content()
    {
        // Test page rendering performance with dynamic content
        $startTime = microtime(true);

        $response = $this->actingAs($this->alumni1)
            ->getJson("/api/pages/{$this->alumniDashboard->slug}/render");

        $endTime = microtime(true);
        $renderTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Performance should be reasonable (< 2 seconds for personalized dashboard)
        $this->assertLessThan(2000, $renderTime);

        // Test memory usage
        $memoryUsage = memory_get_usage(true);
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsage); // Less than 50MB
    }

    public function test_responsive_dashboard_rendering()
    {
        // Test responsive rendering for different devices
        $devices = ['desktop', 'tablet', 'mobile'];

        foreach ($devices as $device) {
            $response = $this->actingAs($this->alumni1)
                ->getJson("/api/pages/{$this->alumniDashboard->slug}/render?device={$device}");

            $response->assertStatus(200);
            $deviceContent = $response->json('data');

            $this->assertArrayHasKey('html', $deviceContent);
            $this->assertArrayHasKey('css', $deviceContent);
            $this->assertArrayHasKey('responsive_meta', $deviceContent);

            // Verify device-specific content
            $this->assertStringContainsString($device, $deviceContent['responsive_meta']['target_device']);
            $this->assertStringContainsString($this->alumni1->name, $deviceContent['html']);
        }
    }

    public function test_page_caching_with_user_context()
    {
        // Test page caching with user-specific content
        Cache::flush();

        // First request
        $response1 = $this->actingAs($this->alumni1)
            ->getJson("/api/pages/{$this->alumniDashboard->slug}/render");

        $response1->assertStatus(200);
        $content1 = $response->json('data');

        // Second request (should be cached but still personalized)
        $response2 = $this->actingAs($this->alumni1)
            ->getJson("/api/pages/{$this->alumniDashboard->slug}/render");

        $response2->assertStatus(200);
        $content2 = $response->json('data');

        // Content should be consistent for same user
        $this->assertEquals($content1['html'], $content2['html']);
        $this->assertStringContainsString($this->alumni1->name, $content2['html']);

        // Different user should get different content
        $response3 = $this->actingAs($this->alumni2)
            ->getJson("/api/pages/{$this->alumniDashboard->slug}/render");

        $response3->assertStatus(200);
        $content3 = $response->json('data');

        $this->assertStringContainsString($this->alumni2->name, $content3['html']);
        $this->assertNotEquals($content1['html'], $content3['html']);
    }

    public function test_error_handling_for_missing_user_data()
    {
        // Create user without career data
        $userWithoutData = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'institution_id' => $this->institution1->id,
            'is_alumni' => true,
        ]);

        // Test graceful handling of missing data
        $response = $this->actingAs($userWithoutData)
            ->getJson("/api/pages/{$this->alumniDashboard->slug}/render");

        $response->assertStatus(200);
        $content = $response->json('data');

        $this->assertArrayHasKey('html', $content);
        $this->assertArrayHasKey('fallback_content', $content);

        // Should show fallback content for missing data
        $this->assertStringContainsString('Welcome', $content['html']);
        $this->assertNotEmpty($content['fallback_content']);
    }

    public function test_admin_preview_functionality()
    {
        // Test admin preview of personalized pages
        $response = $this->actingAs($this->admin1)
            ->getJson("/api/pages/{$this->alumniDashboard->slug}/preview?user_id={$this->alumni1->id}");

        $response->assertStatus(200);
        $previewData = $response->json('data');

        $this->assertArrayHasKey('preview_mode', $previewData);
        $this->assertArrayHasKey('admin_controls', $previewData);
        $this->assertTrue($previewData['preview_mode']);
        $this->assertStringContainsString($this->alumni1->name, $previewData['html']);
    }
}