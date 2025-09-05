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
use Tests\TestCase;

class WorkflowValidationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant;
    protected Institution $institution;
    protected User $user;
    protected BrandConfig $brandConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Workflow Validation University',
            'domain' => 'workflow-validation.edu',
        ]);

        $this->institution = Institution::factory()->create([
            'name' => 'Workflow Validation University',
            'domain' => 'workflow-validation.edu',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Workflow Admin',
            'email' => 'admin@workflow-validation.edu',
            'tenant_id' => $this->tenant->id,
            'institution_id' => $this->institution->id,
        ]);

        $this->brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'institution_name' => 'Workflow Validation University',
            'primary_color' => '#1a365d',
            'secondary_color' => '#2d3748',
        ]);
    }

    public function test_template_creation_business_rules()
    {
        // Test required fields validation
        $response = $this->actingAs($this->user)
            ->postJson('/api/templates', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'category', 'audience_type', 'campaign_type']);

        // Test category validation
        $invalidCategoryData = [
            'name' => 'Test Template',
            'category' => 'invalid_category',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/templates', $invalidCategoryData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category']);

        // Test audience type validation
        $invalidAudienceData = [
            'name' => 'Test Template',
            'category' => 'landing',
            'audience_type' => 'invalid_audience',
            'campaign_type' => 'marketing'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/templates', $invalidAudienceData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['audience_type']);

        // Test campaign type validation
        $invalidCampaignData = [
            'name' => 'Test Template',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'invalid_campaign'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/templates', $invalidCampaignData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['campaign_type']);

        // Test valid template creation
        $validTemplateData = [
            'name' => 'Valid Test Template',
            'description' => 'A valid template for testing',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Test Title',
                            'subtitle' => 'Test Subtitle'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/templates', $validTemplateData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('templates', [
            'name' => 'Valid Test Template',
            'tenant_id' => $this->tenant->id
        ]);
    }

    public function test_landing_page_creation_business_rules()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing'
        ]);

        // Test required fields validation
        $response = $this->actingAs($this->user)
            ->postJson('/api/landing-pages', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['template_id', 'name', 'campaign_type', 'audience_type']);

        // Test template existence validation
        $invalidTemplateData = [
            'template_id' => 99999, // Non-existent template
            'name' => 'Test Landing Page',
            'campaign_type' => 'marketing',
            'audience_type' => 'individual'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/landing-pages', $invalidTemplateData);

        $response->assertStatus(422);

        // Test campaign type compatibility with template
        $incompatibleCampaignData = [
            'template_id' => $template->id,
            'name' => 'Test Landing Page',
            'campaign_type' => 'onboarding', // Different from template's marketing
            'audience_type' => 'individual'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/landing-pages', $incompatibleCampaignData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['campaign_type']);

        // Test audience type compatibility
        $incompatibleAudienceData = [
            'template_id' => $template->id,
            'name' => 'Test Landing Page',
            'campaign_type' => 'marketing',
            'audience_type' => 'institution' // Different from template's individual
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/landing-pages', $incompatibleAudienceData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['audience_type']);

        // Test valid landing page creation
        $validLandingPageData = [
            'template_id' => $template->id,
            'name' => 'Valid Test Landing Page',
            'campaign_type' => 'marketing',
            'audience_type' => 'individual',
            'category' => $this->institution->id,
            'config' => [
                'hero' => [
                    'title' => 'Custom Title'
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/landing-pages', $validLandingPageData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('landing_pages', [
            'name' => 'Valid Test Landing Page',
            'template_id' => $template->id,
            'tenant_id' => $this->tenant->id
        ]);
    }

    public function test_brand_config_business_rules()
    {
        // Test required fields validation
        $response = $this->actingAs($this->user)
            ->postJson('/api/brand-config', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['institution_name', 'primary_color']);

        // Test color format validation
        $invalidColorData = [
            'institution_name' => 'Test University',
            'primary_color' => 'invalid-color',
            'secondary_color' => '#gggggg'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/brand-config', $invalidColorData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['primary_color', 'secondary_color']);

        // Test URL format validation
        $invalidUrlData = [
            'institution_name' => 'Test University',
            'primary_color' => '#000000',
            'logo_url' => 'not-a-valid-url',
            'website_url' => 'also-not-valid'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/brand-config', $invalidUrlData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['logo_url', 'website_url']);

        // Test valid brand config creation
        $validBrandData = [
            'institution_name' => 'Valid Test University',
            'primary_color' => '#1a365d',
            'secondary_color' => '#2d3748',
            'accent_color' => '#38b2ac',
            'logo_url' => 'https://valid-test.edu/logo.png',
            'website_url' => 'https://valid-test.edu',
            'font_family' => 'Arial, sans-serif',
            'social_links' => [
                'facebook' => 'https://facebook.com/validtest',
                'twitter' => 'https://twitter.com/validtest'
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/brand-config', $validBrandData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('brand_configs', [
            'institution_name' => 'Valid Test University',
            'tenant_id' => $this->tenant->id
        ]);
    }

    public function test_workflow_state_transitions()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft'
        ]);

        $landingPage = LandingPage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $template->id,
            'status' => 'draft'
        ]);

        // Test invalid state transitions
        // Cannot publish landing page with draft template
        $response = $this->actingAs($this->user)
            ->postJson("/api/landing-pages/{$landingPage->id}/publish");

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Cannot publish landing page with draft template']);

        // Publish template first
        $response = $this->actingAs($this->user)
            ->postJson("/api/templates/{$template->id}/publish");

        $response->assertStatus(200);
        $this->assertEquals('published', $template->fresh()->status);

        // Now can publish landing page
        $response = $this->actingAs($this->user)
            ->postJson("/api/landing-pages/{$landingPage->id}/publish");

        $response->assertStatus(200);
        $this->assertEquals('published', $landingPage->fresh()->status);

        // Test unpublish workflow
        $response = $this->actingAs($this->user)
            ->postJson("/api/landing-pages/{$landingPage->id}/unpublish");

        $response->assertStatus(200);
        $this->assertEquals('draft', $landingPage->fresh()->status);

        // Test archive workflow
        $response = $this->actingAs($this->user)
            ->postJson("/api/landing-pages/{$landingPage->id}/archive");

        $response->assertStatus(200);
        $this->assertEquals('archived', $landingPage->fresh()->status);

        // Cannot publish archived landing page
        $response = $this->actingAs($this->user)
            ->postJson("/api/landing-pages/{$landingPage->id}/publish");

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Cannot publish archived landing page']);
    }

    public function test_business_logic_constraints()
    {
        // Test template usage limits
        $template = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'usage_limit' => 2
        ]);

        // Create first landing page
        $landingPage1 = LandingPage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $template->id,
            'status' => 'published'
        ]);

        // Create second landing page
        $landingPage2 = LandingPage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $template->id,
            'status' => 'published'
        ]);

        // Attempt to create third landing page (should fail due to limit)
        $landingPageData = [
            'template_id' => $template->id,
            'name' => 'Third Landing Page',
            'campaign_type' => 'marketing',
            'audience_type' => 'individual',
            'category' => $this->institution->id
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/landing-pages', $landingPageData);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Template usage limit exceeded']);

        // Test premium template restrictions
        $premiumTemplate = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_premium' => true
        ]);

        $premiumLandingPageData = [
            'template_id' => $premiumTemplate->id,
            'name' => 'Premium Landing Page',
            'campaign_type' => 'marketing',
            'audience_type' => 'individual',
            'category' => $this->institution->id
        ];

        // Should fail without premium subscription
        $response = $this->actingAs($this->user)
            ->postJson('/api/landing-pages', $premiumLandingPageData);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Premium template requires premium subscription']);
    }

    public function test_data_integrity_constraints()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $landingPage = LandingPage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $template->id
        ]);

        // Test foreign key constraints
        // Cannot delete template with associated landing pages
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/templates/{$template->id}");

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Cannot delete template with associated landing pages']);

        // Delete landing page first
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/landing-pages/{$landingPage->id}");

        $response->assertStatus(204);

        // Now can delete template
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/templates/{$template->id}");

        $response->assertStatus(204);

        // Test tenant isolation constraints
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

        $otherTemplate = Template::factory()->create([
            'tenant_id' => $otherTenant->id
        ]);

        // User from different tenant cannot access template
        $response = $this->actingAs($this->user)
            ->getJson("/api/templates/{$otherTemplate->id}");

        $response->assertStatus(403);

        // User from different tenant cannot create landing page with template from another tenant
        $crossTenantData = [
            'template_id' => $otherTemplate->id,
            'name' => 'Cross Tenant Landing Page',
            'campaign_type' => 'marketing',
            'audience_type' => 'individual'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/landing-pages', $crossTenantData);

        $response->assertStatus(403);
    }

    public function test_validation_rule_combinations()
    {
        // Test complex validation scenarios
        $template = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing',
            'required_fields' => ['phone', 'company']
        ]);

        // Test missing required fields
        $landingPageData = [
            'template_id' => $template->id,
            'name' => 'Test Landing Page',
            'campaign_type' => 'marketing',
            'audience_type' => 'individual',
            'category' => $this->institution->id,
            'config' => [
                'form' => [
                    'fields' => [
                        ['type' => 'email', 'name' => 'email', 'required' => true]
                        // Missing phone and company fields
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/landing-pages', $landingPageData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['config.form.fields']);

        // Test valid configuration with all required fields
        $validLandingPageData = [
            'template_id' => $template->id,
            'name' => 'Valid Test Landing Page',
            'campaign_type' => 'marketing',
            'audience_type' => 'individual',
            'category' => $this->institution->id,
            'config' => [
                'form' => [
                    'fields' => [
                        ['type' => 'email', 'name' => 'email', 'required' => true],
                        ['type' => 'tel', 'name' => 'phone', 'required' => true],
                        ['type' => 'text', 'name' => 'company', 'required' => true]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/landing-pages', $validLandingPageData);

        $response->assertStatus(201);
    }

    public function test_rate_limiting_and_throttling()
    {
        // Test API rate limiting for template creation
        for ($i = 0; $i < 10; $i++) {
            $templateData = [
                'name' => "Rate Limit Test Template {$i}",
                'category' => 'landing',
                'audience_type' => 'individual',
                'campaign_type' => 'marketing'
            ];

            $response = $this->actingAs($this->user)
                ->postJson('/api/templates', $templateData);

            if ($i < 5) { // Assuming rate limit allows 5 per minute
                $response->assertStatus(201);
            } else {
                $response->assertStatus(429); // Too Many Requests
            }
        }

        // Test bulk operation rate limiting
        $bulkData = [
            'templates' => []
        ];

        for ($i = 0; $i < 20; $i++) {
            $bulkData['templates'][] = [
                'name' => "Bulk Template {$i}",
                'category' => 'landing',
                'audience_type' => 'individual',
                'campaign_type' => 'marketing'
            ];
        }

        $response = $this->actingAs($this->user)
            ->postJson('/api/templates/bulk', $bulkData);

        // Should be rate limited for large bulk operations
        $response->assertStatus(429);
    }
}