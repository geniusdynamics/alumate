<?php

namespace Tests\Integration;

use App\Models\BrandConfig;
use App\Models\Institution;
use App\Models\LandingPage;
use App\Models\Tenant;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SystemComponentsIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant;
    protected Institution $institution;
    protected User $user;
    protected BrandConfig $brandConfig;
    protected Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Queue::fake();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Integration Test University',
            'domain' => 'integration-test.edu',
        ]);

        $this->institution = Institution::factory()->create([
            'name' => 'Integration Test University',
            'domain' => 'integration-test.edu',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Integration Admin',
            'email' => 'admin@integration-test.edu',
            'tenant_id' => $this->tenant->id,
            'institution_id' => $this->institution->id,
        ]);

        // Create brand configuration
        $this->brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'institution_name' => 'Integration Test University',
            'primary_color' => '#1a365d',
            'secondary_color' => '#2d3748',
            'logo_url' => 'https://integration-test.edu/logo.png',
        ]);

        // Create template
        $this->template = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'brand_config_id' => $this->brandConfig->id,
            'name' => 'Integration Test Template',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'onboarding',
        ]);
    }

    public function test_template_brand_config_integration()
    {
        // Test that template correctly integrates with brand configuration
        $response = $this->actingAs($this->user)
            ->getJson("/api/templates/{$this->template->id}");

        $response->assertStatus(200);
        $templateData = $response->json('data');

        $this->assertEquals($this->brandConfig->id, $templateData['brand_config_id']);
        $this->assertArrayHasKey('brand_config', $templateData);
        $this->assertEquals($this->brandConfig->primary_color, $templateData['brand_config']['primary_color']);

        // Test brand variable replacement in template structure
        $templateWithVariables = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'brand_config_id' => $this->brandConfig->id,
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => '{{institution_name}} - Welcome',
                            'subtitle' => 'Experience excellence at {{institution_name}}',
                            'cta_text' => 'Apply to {{institution_name}}'
                        ]
                    ]
                ]
            ]
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/templates/{$templateWithVariables->id}/rendered");

        $response->assertStatus(200);
        $rendered = $response->json('data');

        $this->assertStringContainsString('Integration Test University - Welcome', $rendered['html']);
        $this->assertStringContainsString('Experience excellence at Integration Test University', $rendered['html']);
        $this->assertStringContainsString('Apply to Integration Test University', $rendered['html']);
    }

    public function test_landing_page_template_brand_integration()
    {
        // Create landing page with template and brand integration
        $landingPageData = [
            'template_id' => $this->template->id,
            'name' => 'Brand Integrated Landing Page',
            'campaign_type' => 'onboarding',
            'audience_type' => 'individual',
            'category' => $this->institution->id,
            'brand_config' => [
                'logo_url' => 'https://integration-test.edu/custom-logo.png',
                'primary_color' => '#2b6cb0',
                'secondary_color' => '#3182ce',
                'institution_name' => 'Custom Integration University',
            ],
            'config' => [
                'hero' => [
                    'title' => 'Welcome to {{institution_name}}',
                    'use_brand_logo' => true,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/landing-pages', $landingPageData);

        $response->assertStatus(201);
        $landingPage = LandingPage::find($response->json('data.id'));

        // Test that landing page inherits template's brand config
        $this->assertEquals($this->template->brand_config_id, $landingPage->brand_config_id);

        // Test preview with brand integration
        $response = $this->actingAs($this->user)
            ->getJson("/api/landing-pages/{$landingPage->id}/preview");

        $response->assertStatus(200);
        $preview = $response->json('data');

        $this->assertStringContainsString('Custom Integration University', $preview['rendered_html']);
        $this->assertStringContainsString('https://integration-test.edu/custom-logo.png', $preview['rendered_html']);
    }

    public function test_analytics_tracking_integration()
    {
        // Create and publish landing page
        $landingPage = LandingPage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $this->template->id,
            'status' => 'published',
            'public_url' => 'https://integration-test.edu/lp/123',
        ]);

        // Simulate visitor access
        $response = $this->get($landingPage->public_url . '?utm_source=google&utm_campaign=fall2024&utm_medium=cpc');
        $response->assertStatus(200);

        // Test analytics data collection
        $response = $this->actingAs($this->user)
            ->getJson("/api/landing-pages/{$landingPage->id}/analytics");

        $response->assertStatus(200);
        $analytics = $response->json('data');

        $this->assertArrayHasKey('page_views', $analytics);
        $this->assertArrayHasKey('unique_visitors', $analytics);
        $this->assertArrayHasKey('utm_tracking', $analytics);
        $this->assertArrayHasKey('google', $analytics['utm_tracking']['sources'] ?? []);

        // Test conversion tracking
        $formData = [
            'first_name' => 'Analytics',
            'last_name' => 'Test',
            'email' => 'analytics@test.com',
        ];

        $response = $this->postJson("/api/landing-pages/{$landingPage->slug}/submit", $formData);
        $response->assertStatus(200);

        // Verify conversion analytics
        $response = $this->actingAs($this->user)
            ->getJson("/api/landing-pages/{$landingPage->id}/analytics");

        $response->assertStatus(200);
        $updatedAnalytics = $response->json('data');

        $this->assertGreaterThan(0, $updatedAnalytics['form_submissions']);
        $this->assertGreaterThan(0, $updatedAnalytics['conversion_rate']);
    }

    public function test_ab_testing_integration()
    {
        // Create landing page with A/B testing enabled
        $landingPage = LandingPage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $this->template->id,
            'ab_testing_enabled' => true,
        ]);

        // Set up A/B test variations
        $abTestData = [
            'variations' => [
                [
                    'name' => 'Control',
                    'weight' => 50,
                    'config' => [
                        'hero' => [
                            'title' => 'Control Title',
                            'cta_text' => 'Apply Now'
                        ]
                    ]
                ],
                [
                    'name' => 'Variation A',
                    'weight' => 30,
                    'config' => [
                        'hero' => [
                            'title' => 'Variation A Title',
                            'cta_text' => 'Start Application'
                        ]
                    ]
                ],
                [
                    'name' => 'Variation B',
                    'weight' => 20,
                    'config' => [
                        'hero' => [
                            'title' => 'Variation B Title',
                            'cta_text' => 'Join Us'
                        ]
                    ]
                ]
            ],
            'metrics' => ['conversion_rate', 'click_through_rate', 'time_on_page'],
            'duration_days' => 7
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/landing-pages/{$landingPage->id}/ab-test", $abTestData);

        $response->assertStatus(200);
        $abTest = $response->json('data');

        $this->assertCount(3, $abTest['variations']);
        $this->assertEquals(100, array_sum(array_column($abTest['variations'], 'weight')));

        // Test A/B test analytics integration
        $response = $this->actingAs($this->user)
            ->getJson("/api/landing-pages/{$landingPage->id}/ab-test/results");

        $response->assertStatus(200);
        $results = $response->json('data');

        $this->assertArrayHasKey('variations', $results);
        $this->assertArrayHasKey('winner', $results);
        $this->assertArrayHasKey('confidence_level', $results);

        // Test variation serving
        for ($i = 0; $i < 10; $i++) {
            $response = $this->get($landingPage->public_url);
            $response->assertStatus(200);

            $content = $response->getContent();
            $this->assertTrue(
                str_contains($content, 'Control Title') ||
                str_contains($content, 'Variation A Title') ||
                str_contains($content, 'Variation B Title')
            );
        }
    }

    public function test_crm_integration()
    {
        // Create landing page with CRM integration
        $landingPage = LandingPage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $this->template->id,
            'crm_integration_enabled' => true,
            'crm_config' => [
                'provider' => 'salesforce',
                'api_key' => 'test_api_key',
                'webhook_url' => 'https://crm.integration-test.edu/webhook',
                'field_mapping' => [
                    'first_name' => 'FirstName',
                    'last_name' => 'LastName',
                    'email' => 'Email',
                    'phone' => 'Phone',
                    'program_interest' => 'Program_Interest__c'
                ]
            ]
        ]);

        // Test form submission with CRM integration
        $formData = [
            'first_name' => 'CRM',
            'last_name' => 'Integration',
            'email' => 'crm@integration-test.com',
            'phone' => '+1234567890',
            'program_interest' => 'Computer Science'
        ];

        $response = $this->postJson("/api/landing-pages/{$landingPage->slug}/submit", $formData);
        $response->assertStatus(200);

        // Verify CRM webhook was triggered (mocked)
        // In real implementation, this would verify the webhook call
        $this->assertDatabaseHas('landing_page_submissions', [
            'landing_page_id' => $landingPage->id,
            'crm_sync_status' => 'pending'
        ]);

        // Test CRM sync status update
        $response = $this->actingAs($this->user)
            ->postJson("/api/landing-pages/{$landingPage->id}/crm/sync");

        $response->assertStatus(200);

        // Verify sync completion
        $this->assertDatabaseHas('landing_page_submissions', [
            'landing_page_id' => $landingPage->id,
            'crm_sync_status' => 'completed'
        ]);
    }

    public function test_performance_monitoring_integration()
    {
        // Create landing page
        $landingPage = LandingPage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $this->template->id,
            'status' => 'published',
        ]);

        // Simulate page loads to generate performance data
        for ($i = 0; $i < 5; $i++) {
            $startTime = microtime(true);
            $response = $this->get($landingPage->public_url);
            $loadTime = microtime(true) - $startTime;

            $response->assertStatus(200);

            // Record performance metric (simulated)
            $landingPage->performance_metrics()->create([
                'metric_type' => 'page_load_time',
                'value' => $loadTime,
                'metadata' => ['user_agent' => 'Test Browser']
            ]);
        }

        // Test performance analytics
        $response = $this->actingAs($this->user)
            ->getJson("/api/landing-pages/{$landingPage->id}/performance");

        $response->assertStatus(200);
        $performance = $response->json('data');

        $this->assertArrayHasKey('average_load_time', $performance);
        $this->assertArrayHasKey('performance_score', $performance);
        $this->assertArrayHasKey('mobile_performance_score', $performance);
        $this->assertGreaterThan(0, $performance['average_load_time']);

        // Test performance alerts
        $response = $this->actingAs($this->user)
            ->getJson("/api/landing-pages/{$landingPage->id}/performance/alerts");

        $response->assertStatus(200);
        $alerts = $response->json('data');

        $this->assertIsArray($alerts);
    }

    public function test_security_validation_integration()
    {
        // Test XSS prevention in template content
        $maliciousTemplate = [
            'name' => 'Security Test Template',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => '<script>alert("XSS")</script>Safe Title',
                            'subtitle' => '<img src=x onerror=alert("XSS")>Safe Subtitle'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/templates', $maliciousTemplate);

        $response->assertStatus(201);
        $template = Template::find($response->json('data.id'));

        // Test that malicious content is sanitized
        $response = $this->actingAs($this->user)
            ->getJson("/api/templates/{$template->id}/preview");

        $response->assertStatus(200);
        $preview = $response->json('data');

        $this->assertStringNotContainsString('<script>', $preview['rendered_html']);
        $this->assertStringNotContainsString('onerror=', $preview['rendered_html']);
        $this->assertStringContainsString('Safe Title', $preview['rendered_html']);
        $this->assertStringContainsString('Safe Subtitle', $preview['rendered_html']);
    }

    public function test_mobile_optimization_integration()
    {
        // Create mobile-optimized landing page
        $landingPage = LandingPage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $this->template->id,
            'mobile_optimized' => true,
            'responsive_breakpoints' => [
                'mobile' => 768,
                'tablet' => 1024,
                'desktop' => 1920
            ]
        ]);

        // Test mobile preview
        $response = $this->actingAs($this->user)
            ->getJson("/api/landing-pages/{$landingPage->id}/preview/mobile");

        $response->assertStatus(200);
        $mobilePreview = $response->json('data');

        $this->assertArrayHasKey('mobile_html', $mobilePreview);
        $this->assertArrayHasKey('responsive_css', $mobilePreview);
        $this->assertArrayHasKey('mobile_score', $mobilePreview);

        // Test mobile-specific analytics
        $response = $this->actingAs($this->user)
            ->getJson("/api/landing-pages/{$landingPage->id}/analytics/mobile");

        $response->assertStatus(200);
        $mobileAnalytics = $response->json('data');

        $this->assertArrayHasKey('mobile_visitors', $mobileAnalytics);
        $this->assertArrayHasKey('mobile_conversion_rate', $mobileAnalytics);
        $this->assertArrayHasKey('device_breakdown', $mobileAnalytics);
    }

    public function test_import_export_integration()
    {
        // Create template with complex structure
        $complexTemplate = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Export Test Template',
            'structure' => [
                'sections' => [
                    ['type' => 'hero', 'config' => ['title' => 'Hero Section']],
                    ['type' => 'form', 'config' => ['title' => 'Contact Form']],
                    ['type' => 'testimonials', 'config' => ['title' => 'Testimonials']]
                ]
            ]
        ]);

        // Test template export
        $response = $this->actingAs($this->user)
            ->postJson("/api/templates/{$complexTemplate->id}/export", [
                'format' => 'json',
                'include_brand_config' => true,
                'include_analytics' => true
            ]);

        $response->assertStatus(200);
        $exportData = $response->json('data');

        $this->assertArrayHasKey('template', $exportData);
        $this->assertArrayHasKey('brand_config', $exportData);
        $this->assertArrayHasKey('analytics', $exportData);
        $this->assertEquals($complexTemplate->name, $exportData['template']['name']);

        // Test template import
        $importData = [
            'template' => [
                'name' => 'Imported Template',
                'category' => 'landing',
                'structure' => [
                    'sections' => [
                        ['type' => 'hero', 'config' => ['title' => 'Imported Hero']]
                    ]
                ]
            ],
            'brand_config' => [
                'primary_color' => '#ff0000',
                'institution_name' => 'Imported University'
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/templates/import', $importData);

        $response->assertStatus(201);
        $importedTemplate = Template::find($response->json('data.id'));

        $this->assertEquals('Imported Template', $importedTemplate->name);
        $this->assertEquals($this->tenant->id, $importedTemplate->tenant_id);
    }

    public function test_workflow_automation_integration()
    {
        // Create landing page with workflow automation
        $landingPage = LandingPage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $this->template->id,
            'workflow_automation_enabled' => true,
            'automations' => [
                [
                    'trigger' => 'form_submission',
                    'conditions' => [
                        ['field' => 'program_interest', 'operator' => 'equals', 'value' => 'Computer Science']
                    ],
                    'actions' => [
                        [
                            'type' => 'send_email',
                            'template' => 'cs_program_followup',
                            'recipient_field' => 'email'
                        ],
                        [
                            'type' => 'add_tag',
                            'tag' => 'cs_interested'
                        ]
                    ]
                ]
            ]
        ]);

        // Test automation trigger
        $formData = [
            'first_name' => 'Automation',
            'last_name' => 'Test',
            'email' => 'automation@test.com',
            'program_interest' => 'Computer Science'
        ];

        $response = $this->postJson("/api/landing-pages/{$landingPage->slug}/submit", $formData);
        $response->assertStatus(200);

        // Verify automation execution
        $this->assertDatabaseHas('automation_logs', [
            'landing_page_id' => $landingPage->id,
            'trigger_type' => 'form_submission',
            'status' => 'completed'
        ]);

        // Verify lead tagging
        $this->assertDatabaseHas('leads', [
            'email' => 'automation@test.com',
            'tags' => json_encode(['cs_interested'])
        ]);
    }
}