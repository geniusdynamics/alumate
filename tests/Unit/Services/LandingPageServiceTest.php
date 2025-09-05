<?php

namespace Tests\Unit\Services;

use App\Models\LandingPage;
use App\Models\LandingPageSubmission;
use App\Models\LandingPageAnalytics;
use App\Models\Template;
use App\Models\Lead;
use App\Services\LandingPageService;
use App\Services\LeadManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

class LandingPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private LandingPageService $landingPageService;
    private LeadManagementService $leadService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock services and facades for isolation
        Cache::shouldReceive('remember')->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });

        // Create mock for LeadManagementService
        $this->leadService = $this->mock(LeadManagementService::class);
        $this->landingPageService = new LandingPageService($this->leadService);
    }

    /**
     * Test landing page service instantiation
     */
    public function test_landing_page_service_can_be_instantiated()
    {
        $this->assertInstanceOf(LandingPageService::class, $this->landingPageService);
    }

    /**
     * Test creating basic landing page
     */
    public function test_create_landing_page()
    {
        $data = [
            'name' => 'Test Landing Page',
            'title' => 'Welcome to Our Platform',
            'description' => 'A test landing page',
            'target_audience' => 'individual',
            'campaign_type' => 'onboarding',
        ];

        $landingPage = $this->landingPageService->createLandingPage($data);

        $this->assertInstanceOf(LandingPage::class, $landingPage);
        $this->assertEquals('Test Landing Page', $landingPage->name);
        $this->assertEquals('draft', $landingPage->status);
    }

    /**
     * Test creating landing page from template
     */
    public function test_create_from_template()
    {
        $template = Template::factory()->create([
            'name' => 'Hero Template',
            'structure' => ['sections' => [['type' => 'hero', 'config' => []]]],
            'audience_type' => 'individual',
            'campaign_type' => 'onboarding',
        ]);

        $customizations = [
            'name' => 'Custom Landing Page',
            'audience_type' => 'individual',
        ];

        $landingPage = $this->landingPageService->createFromTemplate($template->id, $customizations);

        $this->assertInstanceOf(LandingPage::class, $landingPage);
        $this->assertEquals('Custom Landing Page', $landingPage->name);
        $this->assertEquals($template->id, $landingPage->template_id);
        $this->assertEquals('draft', $landingPage->status);
    }

    /**
     * Test creating from non-existent template throws exception
     */
    public function test_create_from_template_throws_exception_for_invalid_template()
    {
        $this->expectException(\App\Exceptions\TemplateNotFoundException::class);

        $this->landingPageService->createFromTemplate(999, []);
    }

    /**
     * Test applying branding to landing page
     */
    public function test_apply_branding()
    {
        $landingPage = LandingPage::factory()->create(['config' => ['sections' => [['type' => 'hero']]]]);
        $brandConfig = [
            'colors' => ['primary' => '#007bff', 'secondary' => '#6c757d'],
            'fonts' => ['body' => 'Arial', 'heading' => 'Arial Black'],
        ];

        $brandedConfig = $this->landingPageService->applyBranding($landingPage->id, $brandConfig, false);

        $this->assertIsArray($brandedConfig);
        $this->assertArrayHasKey('brand', $brandedConfig);
        $this->assertEquals($brandConfig, $brandedConfig['brand']);
    }

    /**
     * Test customizing landing page content
     */
    public function test_customize_content()
    {
        $landingPage = LandingPage::factory()->create(['config' => ['sections' => [['type' => 'hero']]]]);
        $customizations = [
            'seo_title' => 'Custom SEO Title',
            'seo_description' => 'Custom SEO description',
            'sections' => [['type' => 'hero'], ['type' => 'form']],
        ];

        $customizedConfig = $this->landingPageService->customizeContent($landingPage->id, $customizations, false);

        $this->assertIsArray($customizedConfig);
        $this->assertEquals($customizations['sections'], $customizedConfig['sections']);
    }

    /**
     * Test publishing landing page
     */
    public function test_publish_page()
    {
        $landingPage = LandingPage::factory()->create([
            'status' => 'draft',
            'name' => 'Test Page',
            'config' => ['sections' => [['type' => 'hero']]],
        ]);

        $result = $this->landingPageService->publishPage($landingPage->id);

        $this->assertTrue($result);

        $updatedPage = $landingPage->fresh();
        $this->assertEquals('published', $updatedPage->status);
        $this->assertNotNull($updatedPage->published_at);
        $this->assertEquals(2, $updatedPage->version);
    }

    /**
     * Test publishing page with schedule
     */
    public function test_publish_page_with_schedule()
    {
        $landingPage = LandingPage::factory()->create(['status' => 'draft']);
        $publishAt = now()->addDays(1);

        $result = $this->landingPageService->publishPage($landingPage->id, [
            'publish_at' => $publishAt,
        ]);

        $this->assertTrue($result);
        $this->assertEquals($publishAt->toISOString(), $landingPage->fresh()->published_at->toISOString());
    }

    /**
     * Test publishing page with invalid data
     */
    public function test_publish_page_with_invalid_data_throws_exception()
    {
        $landingPage = LandingPage::factory()->create([
            'status' => 'draft',
            'name' => '', // Invalid - empty name
            'config' => [],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Landing page must have a name before publishing');

        $this->landingPageService->publishPage($landingPage->id);
    }

    /**
     * Test publishing already published page
     */
    public function test_publish_already_published_page_throws_exception()
    {
        $landingPage = LandingPage::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Landing page is already published');

        $this->landingPageService->publishPage($landingPage->id);
    }

    /**
     * Test getting performance metrics for landing page
     */
    public function test_get_performance_metrics()
    {
        $landingPage = LandingPage::factory()->create();

        // Create analytics data
        LandingPageAnalytics::factory()->count(5)->create([
            'landing_page_id' => $landingPage->id,
            'event_type' => 'page_view',
            'session_id' => 'session_1',
            'created_at' => now()->subDays(1),
        ]);

        LandingPageAnalytics::factory()->count(3)->create([
            'landing_page_id' => $landingPage->id,
            'event_type' => 'page_view',
            'session_id' => 'session_2',
            'created_at' => now()->subDays(1),
        ]);

        // Create submission data
        LandingPageSubmission::factory()->count(3)->create([
            'landing_page_id' => $landingPage->id,
            'created_at' => now()->subDays(1),
        ]);

        $metrics = $this->landingPageService->getPerformanceMetrics($landingPage->id);

        $this->assertIsArray($metrics);
        $this->assertEquals(8, $metrics['page_views']);
        $this->assertEquals(2, $metrics['unique_visitors']);
        $this->assertEquals(3, $metrics['conversion_count']);
        $this->assertGreaterThan(0, $metrics['conversion_rate']);
        $this->assertArrayHasKey('bounce_rate', $metrics);
        $this->assertArrayHasKey('device_breakdown', $metrics);
    }

    /**
     * Test duplicating landing page
     */
    public function test_duplicate_landing_page()
    {
        $originalPage = LandingPage::factory()->create([
            'name' => 'Original Page',
            'description' => 'Original description',
            'config' => ['sections' => [['type' => 'hero']]],
        ]);

        $overrides = [
            'name' => 'Duplicated Page',
        ];

        $duplicate = $this->landingPageService->duplicate($originalPage->id, $overrides);

        $this->assertInstanceOf(LandingPage::class, $duplicate);
        $this->assertEquals('Duplicated Page', $duplicate->name);
        $this->assertEquals('draft', $duplicate->status);
        $this->assertEquals($originalPage->template_id, $duplicate->template_id);
        $this->assertNotEquals($originalPage->id, $duplicate->id);
    }

    /**
     * Test archiving landing page
     */
    public function test_archive_landing_page()
    {
        $landingPage = LandingPage::factory()->create(['status' => 'published']);

        $result = $this->landingPageService->archive($landingPage->id);

        $this->assertTrue($result);
        $this->assertEquals('archived', $landingPage->fresh()->status);
    }

    /**
     * Test handling form submission with lead creation
     */
    public function test_handle_form_submission_creates_lead()
    {
        $landingPage = LandingPage::factory()->create([
            'target_audience' => 'individual',
            'campaign_type' => 'onboarding',
        ]);

        $formData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '123-456-7890',
            'form_name' => 'contact_form',
        ];

        $request = Request::create('/submit', 'POST', $formData);

        // Mock lead creation
        $expectedLead = Lead::factory()->make(['id' => 123]);
        Mockery::shouldReceive('shouldReceive')
            ->once()
            ->with('createLead')
            ->andReturn($expectedLead);

        $submission = $this->landingPageService->handleFormSubmission($landingPage, $formData, $request);

        $this->assertInstanceOf(LandingPageSubmission::class, $submission);
        $this->assertEquals($formData['email'], $submission->form_data['email']);
        $this->assertEquals($expectedLead->id, $submission->lead_id);
        $this->assertEquals('processed', $submission->status);
    }

    /**
     * Test handling form submission when lead creation fails
     */
    public function test_handle_form_submission_handles_lead_creation_failure()
    {
        $landingPage = LandingPage::factory()->create();
        $formData = [
            'first_name' => 'John',
            'email' => 'john@example.com',
            'form_name' => 'contact_form',
        ];

        $request = Request::create('/submit', 'POST', $formData);

        // Mock lead creation failure
        $this->leadService->shouldReceive('createLead')
            ->once()
            ->andThrow(new \Exception('Lead creation failed'));

        $submission = $this->landingPageService->handleFormSubmission($landingPage, $formData, $request);

        $this->assertInstanceOf(LandingPageSubmission::class, $submission);
        $this->assertNull($submission->lead_id);
        $this->assertNotEquals('processed', $submission->status);
    }

    /**
     * Test validating brand configuration
     */
    public function test_validate_brand_config()
    {
        $reflection = new \ReflectionClass($this->landingPageService);
        $method = $reflection->getMethod('validateBrandConfig');
        $method->setAccessible(true);

        // Valid brand config should not throw exception
        $method->invokeArgs($this->landingPageService, [[
            'colors' => ['primary' => '#007bff'],
            'fonts' => ['body' => 'Arial'],
        ]]);
    }

    /**
     * Test invalid brand configuration throws exception
     */
    public function test_invalid_brand_config_throws_exception()
    {
        $reflection = new \ReflectionClass($this->landingPageService);
        $method = $reflection->getMethod('validateBrandConfig');
        $method->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);

        $method->invokeArgs($this->landingPageService, [[
            'invalid_key' => 'value',
        ]]);
    }

    /**
     * Test applying customizations merges with existing config
     */
    public function test_apply_customizations_merges_with_existing()
    {
        $reflection = new \ReflectionClass($this->landingPageService);
        $method = $reflection->getMethod('applyCustomizations');
        $method->setAccessible(true);

        $baseConfig = [
            'sections' => [['type' => 'hero', 'title' => 'Original Title']],
            'theme' => 'default',
        ];

        $customizations = [
            'sections' => [['type' => 'hero', 'title' => 'Custom Title']],
            'customSetting' => 'value',
        ];

        $result = $method->invokeArgs($this->landingPageService, [$baseConfig, $customizations]);

        $this->assertEquals('Custom Title', $result['sections'][0]['title']);
        $this->assertEquals('value', $result['customSetting']);
        $this->assertEquals('default', $result['theme']); // Preserved from base
    }

    /**
     * Test extracting UTM data from request
     */
    public function test_extract_utm_data()
    {
        $reflection = new \ReflectionClass($this->landingPageService);
        $method = $reflection->getMethod('extractUtmData');
        $method->setAccessible(true);

        $request = Request::create('/', 'GET', [
            'utm_source' => 'google',
            'utm_medium' => 'organic',
            'utm_campaign' => 'summer_sale',
            'utm_term' => 'landing page',
            'utm_content' => 'banner_ad',
        ]);

        $utmData = $method->invokeArgs($this->landingPageService, [$request]);

        $this->assertEquals('google', $utmData['utm_source']);
        $this->assertEquals('organic', $utmData['utm_medium']);
        $this->assertEquals('summer_sale', $utmData['utm_campaign']);
        $this->assertEquals('landing page', $utmData['utm_term']);
        $this->assertEquals('banner_ad', $utmData['utm_content']);
    }

    /**
     * Test mapping audience to lead type
     */
    public function test_map_audience_to_lead_type()
    {
        $reflection = new \ReflectionClass($this->landingPageService);
        $method = $reflection->getMethod('mapAudienceToLeadType');
        $method->setAccessible(true);

        $this->assertEquals('institutional', $method->invokeArgs($this->landingPageService, ['institution']));
        $this->assertEquals('enterprise', $method->invokeArgs($this->landingPageService, ['employer']));
        $this->assertEquals('individual', $method->invokeArgs($this->landingPageService, ['individual']));
        $this->assertEquals('individual', $method->invokeArgs($this->landingPageService, ['unknown']));
    }

    /**
     * Test error handling when getting performance metrics for non-existent page
     */
    public function test_get_performance_metrics_for_nonexistent_page_returns_empty_array()
    {
        $metrics = $this->landingPageService->getPerformanceMetrics(999);

        $this->assertEquals([], $metrics);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}