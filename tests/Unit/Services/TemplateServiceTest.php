<?php

namespace Tests\Unit\Services;

use App\Models\Template;
use App\Models\LandingPage;
use App\Models\User;
use App\Services\TemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

class TemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    private TemplateService $templateService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock cache facade for isolation
        Cache::shouldReceive('remember')->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });

        Cache::shouldReceive('rememberForever')->andReturnUsing(function ($key, $callback) {
            return $callback();
        });

        Cache::shouldReceive('forget')->andReturn(true);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        $this->templateService = new TemplateService();
    }

    /**
     * Test template service instantiation
     */
    public function test_template_service_can_be_instantiated()
    {
        $this->assertInstanceOf(TemplateService::class, $this->templateService);
    }

    /**
     * Test getting all templates
     */
    public function test_get_all_templates()
    {
        // Create test templates
        Template::factory()->count(3)->create(['is_active' => true]);
        Template::factory()->count(2)->create(['is_active' => false]);

        $templates = $this->templateService->getAllTemplates();

        $this->assertCount(3, $templates);
        $templates->each(function ($template) {
            $this->assertTrue($template->is_active);
        });
    }

    /**
     * Test getting all templates with pagination
     */
    public function test_get_all_templates_with_pagination()
    {
        Template::factory()->count(10)->create(['is_active' => true]);

        $result = $this->templateService->getAllTemplates([], ['paginate' => true, 'per_page' => 5]);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->perPage());
        $this->assertEquals(10, $result->total());
    }

    /**
     * Test getting template by ID
     */
    public function test_get_template_by_id()
    {
        $template = Template::factory()->create(['is_active' => true]);

        $result = $this->templateService->getTemplateById($template->id);

        $this->assertInstanceOf(Template::class, $result);
        $this->assertEquals($template->id, $result->id);
    }

    /**
     * Test getting non-existent template throws exception
     */
    public function test_get_template_by_id_throws_exception_for_nonexistent_template()
    {
        $this->expectException(\App\Exceptions\TemplateNotFoundException::class);

        $this->templateService->getTemplateById(999);
    }

    /**
     * Test searching templates
     */
    public function test_search_templates()
    {
        Template::factory()->create(['name' => 'Hero Template', 'description' => 'Great for hero sections']);
        Template::factory()->create(['name' => 'Contact Form Template', 'description' => 'Perfect for contact forms']);
        Template::factory()->create(['name' => 'Landing Page Template', 'description' => 'Complete landing page solution']);

        $results = $this->templateService->searchTemplates('hero');

        $this->assertCount(1, $results);
        $this->assertEquals('Hero Template', $results->first()->name);
    }

    /**
     * Test searching templates with filters
     */
    public function test_search_templates_with_filters()
    {
        Template::factory()->create([
            'name' => 'Individual Template',
            'audience_type' => 'individual',
            'category' => 'landing'
        ]);
        Template::factory()->create([
            'name' => 'Individual Email Template',
            'audience_type' => 'individual',
            'category' => 'email'
        ]);

        $results = $this->templateService->searchTemplates('individual', [
            'category' => 'landing'
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals('landing', $results->first()->category);
    }

    /**
     * Test getting templates by category
     */
    public function test_get_templates_by_category()
    {
        Template::factory()->count(3)->create(['category' => 'landing', 'is_active' => true]);
        Template::factory()->count(2)->create(['category' => 'email', 'is_active' => true]);

        $landingTemplates = $this->templateService->getTemplatesByCategory('landing');
        $emailTemplates = $this->templateService->getTemplatesByCategory('email');

        $this->assertCount(3, $landingTemplates);
        $this->assertCount(2, $emailTemplates);
    }

    /**
     * Test getting templates by audience type
     */
    public function test_get_templates_by_audience()
    {
        Template::factory()->count(2)->create(['audience_type' => 'individual', 'is_active' => true]);
        Template::factory()->count(3)->create(['audience_type' => 'institution', 'is_active' => true]);

        $individualTemplates = $this->templateService->getTemplatesByAudience('individual');
        $institutionTemplates = $this->templateService->getTemplatesByAudience('institution');

        $this->assertCount(2, $individualTemplates);
        $this->assertCount(3, $institutionTemplates);
    }

    /**
     * Test getting premium templates
     */
    public function test_get_premium_templates()
    {
        Template::factory()->count(3)->create(['is_premium' => true, 'is_active' => true]);
        Template::factory()->count(5)->create(['is_premium' => false, 'is_active' => true]);

        $premiumTemplates = $this->templateService->getPremiumTemplates();

        $this->assertCount(3, $premiumTemplates);
        $premiumTemplates->each(function ($template) {
            $this->assertTrue($template->is_premium);
        });
    }

    /**
     * Test getting popular templates
     */
    public function test_get_popular_templates()
    {
        Template::factory()->create(['usage_count' => 100, 'is_active' => true]);
        Template::factory()->create(['usage_count' => 200, 'is_active' => true]);
        Template::factory()->create(['usage_count' => 50, 'is_active' => true]);

        $popularTemplates = $this->templateService->getPopularTemplates(2);

        $this->assertCount(2, $popularTemplates);
        $this->assertEquals(200, $popularTemplates->first()->usage_count);
        $this->assertEquals(100, $popularTemplates->last()->usage_count);
    }

    /**
     * Test getting recently used templates
     */
    public function test_get_recently_used_templates()
    {
        $oldTemplate = Template::factory()->create(['last_used_at' => now()->subDays(10), 'is_active' => true]);
        $recentTemplate = Template::factory()->create(['last_used_at' => now()->subHours(1), 'is_active' => true]);
        $neverUsedTemplate = Template::factory()->create(['last_used_at' => null, 'is_active' => true]);

        $recentTemplates = $this->templateService->getRecentlyUsedTemplates();

        $this->assertCount(2, $recentTemplates);
        $this->assertEquals($recentTemplate->id, $recentTemplates->first()->id);
        $this->assertFalse($recentTemplates->contains($neverUsedTemplate));
    }

    /**
     * Test validating template structure
     */
    public function test_validate_template_structure()
    {
        $validStructure = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => ['title' => 'Welcome', 'subtitle' => 'Hello world'],
                ],
                [
                    'type' => 'form',
                    'config' => ['fields' => []],
                ],
            ],
        ];

        $result = $this->templateService->validateTemplateStructure($validStructure);

        $this->assertTrue($result);
    }

    /**
     * Test validating invalid template structure throws exception
     */
    public function test_validate_invalid_template_structure_throws_exception()
    {
        $invalidStructure = [
            'invalid_key' => []
        ];

        $this->expectException(\App\Exceptions\TemplateValidationException::class);

        $this->templateService->validateTemplateStructure($invalidStructure);
    }

    /**
     * Test validating template structure containing security issues
     */
    public function test_validate_template_structure_with_security_issues_throws_exception()
    {
        $maliciousStructure = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => '<script>alert("xss")</script>Welcome',
                    ],
                ],
            ],
        ];

        $this->expectException(\App\Exceptions\TemplateSecurityException::class);

        $this->templateService->validateTemplateStructure($maliciousStructure);
    }

    /**
     * Test incrementing usage count
     */
    public function test_increment_usage()
    {
        $template = Template::factory()->create(['usage_count' => 10]);

        $result = $this->templateService->incrementUsage($template->id);

        $this->assertTrue($result);
        $this->assertEquals(11, $template->fresh()->usage_count);
        $this->assertNotNull($template->fresh()->last_used_at);
    }

    /**
     * Test updating performance metrics
     */
    public function test_update_performance_metrics()
    {
        $template = Template::factory()->create(['performance_metrics' => ['old_metric' => 100]]);
        $newMetrics = ['conversion_rate' => 15.5, 'load_time' => 2.3];

        $result = $this->templateService->updatePerformanceMetrics($template->id, $newMetrics);

        $this->assertTrue($result);

        $updatedTemplate = $template->fresh();
        $this->assertArrayHasKey('conversion_rate', $updatedTemplate->performance_metrics);
        $this->assertEquals(15.5, $updatedTemplate->performance_metrics['conversion_rate']);
        // Should preserve old metrics
        $this->assertArrayHasKey('old_metric', $updatedTemplate->performance_metrics);
    }

    /**
     * Test getting template statistics
     */
    public function test_get_template_stats()
    {
        $template = Template::factory()->create(['usage_count' => 50]);
        $landingPages = LandingPage::factory()->count(10)->create(['template_id' => $template->id]);

        $stats = $this->templateService->getTemplateStats($template->id);

        $this->assertIsArray($stats);
        $this->assertEquals(50, $stats['usage_stats']['usage_count']);
        $this->assertEquals(10, $stats['landing_page_count']);
    }

    /**
     * Test template filtering by multiple criteria
     */
    public function test_complex_filtering()
    {
        Template::factory()->create([
            'audience_type' => 'individual',
            'campaign_type' => 'onboarding',
            'is_active' => true,
            'is_premium' => false
        ]);

        Template::factory()->create([
            'audience_type' => 'institution',
            'campaign_type' => 'onboarding',
            'is_active' => true,
            'is_premium' => true
        ]);

        Template::factory()->create([
            'audience_type' => 'individual',
            'campaign_type' => 'event_promotion',
            'is_active' => true,
            'is_premium' => false
        ]);

        $results = $this->templateService->getAllTemplates([
            'audience_type' => 'individual',
            'campaign_type' => 'onboarding'
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals('individual', $results->first()->audience_type);
        $this->assertEquals('onboarding', $results->first()->campaign_type);
    }

    /**
     * Test template sorting functionality
     */
    public function test_template_sorting()
    {
        // Create templates with different names for testing alphabetical sort
        Template::factory()->create(['name' => 'Zulu Template', 'is_active' => true]);
        Template::factory()->create(['name' => 'Alpha Template', 'is_active' => true]);
        Template::factory()->create(['name' => 'Bravo Template', 'is_active' => true]);

        $results = $this->templateService->getAllTemplates([], ['sort' => 'name:asc']);

        $this->assertEquals('Alpha Template', $results->first()->name);
        $this->assertEquals('Bravo Template', $results->get(1)->name);
        $this->assertEquals('Zulu Template', $results->last()->name);
    }

    /**
     * Test template sorting by usage count descending
     */
    public function test_template_sorting_by_usage_descending()
    {
        Template::factory()->create(['usage_count' => 10, 'is_active' => true]);
        Template::factory()->create(['usage_count' => 100, 'is_active' => true]);
        Template::factory()->create(['usage_count' => 50, 'is_active' => true]);

        $results = $this->templateService->getAllTemplates([], ['sort' => 'usage_count:desc']);

        $this->assertEquals(100, $results->first()->usage_count);
        $this->assertEquals(50, $results->get(1)->usage_count);
        $this->assertEquals(10, $results->last()->usage_count);
    }

    /**
     * Test error handling when incrementing usage for non-existent template
     */
    public function test_increment_usage_handles_errors_gracefully()
    {
        // Using a non-existent template ID
        $result = $this->templateService->incrementUsage(99999);

        $this->assertFalse($result);
    }

    /**
     * Test error handling when updating metrics for non-existent template
     */
    public function test_update_performance_metrics_handles_errors_gracefully()
    {
        $result = $this->templateService->updatePerformanceMetrics(99999, ['conversion_rate' => 10]);

        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}