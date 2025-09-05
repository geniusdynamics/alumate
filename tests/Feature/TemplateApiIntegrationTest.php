<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\LandingPage;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemplateApiIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $testUser;
    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(['public', 'private']);

        $this->institution = Institution::factory()->create([
            'name' => 'Tech University',
            'domain' => 'tech-university.com',
        ]);

        $this->testUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@tech-university.com',
            'tenant_id' => $this->institution->id,
        ]);
    }

    public function test_template_crud_operations()
    {
        // Test template creation
        $templateData = [
            'name' => 'Marketing Campaign Template',
            'description' => 'Versatile template for marketing campaigns',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Transform Your Business',
                            'cta_text' => 'Get Started Today',
                        ]
                    ]
                ]
            ],
            'default_config' => [
                'theme' => [
                    'primary_color' => '#007acc',
                ]
            ],
            'tags' => ['marketing', 'campaign'],
        ];

        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates', $templateData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id', 'name', 'category', 'audience_type',
                        'campaign_type', 'structure', 'default_config', 'tags'
                    ]
                ]);

        $templateId = $response->json('data.id');
        $this->assertDatabaseHas('templates', [
            'id' => $templateId,
            'name' => 'Marketing Campaign Template',
            'tenant_id' => $this->testUser->tenant_id,
        ]);

        // Test template retrieval
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$templateId}");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'name' => 'Marketing Campaign Template',
                        'category' => 'landing',
                        'audience_type' => 'individual',
                    ]
                ]);

        // Test template listing
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'data' => [
                            '*' => [
                                'id', 'name', 'category', 'audience_type',
                                'campaign_type', 'usage_count', 'is_active'
                            ]
                        ],
                        'meta' => ['pagination' => 'links']
                    ]
                ]);

        // Test template update
        $updatedData = [
            'name' => 'Enhanced Marketing Campaign Template',
            'description' => 'Enhanced version with improved features',
            'audience_type' => 'institution',
            'tags' => ['marketing', 'campaign', 'enhanced'],
        ];

        $response = $this->actingAs($this->testUser)
            ->putJson("/api/templates/{$templateId}", $updatedData);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'name' => 'Enhanced Marketing Campaign Template',
                        'audience_type' => 'institution',
                    ]
                ]);

        $this->assertDatabaseHas('templates', [
            'id' => $templateId,
            'name' => 'Enhanced Marketing Campaign Template',
            'audience_type' => 'institution',
        ]);

        // Test template deletion
        $response = $this->actingAs($this->testUser)
            ->deleteJson("/api/templates/{$templateId}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('templates', ['id' => $templateId]);
    }

    public function test_landing_page_creation_from_template()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Base Template',
            'category' => 'landing',
            'default_config' => [
                'theme' => ['primary_color' => '#004080'],
                'typography' => ['heading_font' => 'Arial'],
            ],
        ]);

        $landingPageData = [
            'template_id' => $template->id,
            'name' => 'Summer Marketing Campaign',
            'description' => 'Campaign for summer programs',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing',
            'config' => [
                'hero' => [
                    'title' => 'Summer Special Programs',
                    'subtitle' => 'Limited time offers',
                ]
            ],
            'brand_config' => [
                'primary_color' => '#00aaff',
                'secondary_color' => '#004080',
                'logo_url' => 'https://example.com/logo.png',
            ],
            'seo_title' => 'Summer Programs | Tech University',
            'seo_description' => 'Explore our summer programs and special offers',
        ];

        $response = $this->actingAs($this->testUser)
            ->postJson('/api/landing-pages', $landingPageData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id', 'name', 'status', 'public_url', 'preview_url',
                        'config', 'brand_config', 'seo_title', 'seo_description'
                    ]
                ]);

        $landingPageId = $response->json('data.id');
        $this->assertDatabaseHas('landing_pages', [
            'id' => $landingPageId,
            'template_id' => $template->id,
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Summer Marketing Campaign',
            'status' => 'draft',
        ]);

        // Verify configuration merging
        $landingPage = LandingPage::find($landingPageId);
        $this->assertEquals('Summer Special Programs', $landingPage->config['hero']['title']);
        // Should include both custom and template default configs
        $this->assertEquals('#00aaff', $landingPage->getEffectiveConfig()['brand_config']['primary_color']);
    }

    public function test_template_validation_and_security()
    {
        // Test structure validation
        $invalidTemplateData = [
            'name' => 'Invalid Template',
            'category' => 'invalid_category',
            'audience_type' => 'invalid_audience',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'invalid_section_type',
                        'config' => 'invalid_config_should_be_array',
                    ]
                ]
            ],
        ];

        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates', $invalidTemplateData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'category', 'audience_type', 'structure.sections.0.config'
                ]);

        // Test XSS prevention
        $maliciousTemplateData = [
            'name' => '<script>alert("XSS")</script>',
            'description' => '<img src="x" onerror="alert(\'XSS\')">',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => '<script>malicious()</script>',
                        ]
                    ]
                ]
            ],
        ];

        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates', $maliciousTemplateData);

        $response->assertStatus(201);

        // Verify XSS is cleaned
        $template = Template::find($response->json('data.id'));
        $this->assertNotContains('<script>', $template->name);
        $this->assertNotContains('<script>', $template->structure['sections'][0]['config']['title']);
    }

    public function test_template_preview_generation()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Preview Test',
                            'subtitle' => 'Testing preview generation',
                        ]
                    ]
                ]
            ],
        ]);

        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/preview");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'preview_html', 'preview_url', 'render_time', 'sections'
                    ]
                ]);

        // Verify preview content
        $previewData = $response->json('data');
        $this->assertContains('Preview Test', $previewData['preview_html']);
        $this->assertArrayHasKey('render_time', $previewData);
        $this->assertGreaterThan(0, $previewData['render_time']);
    }

    public function test_template_analytics_and_metrics()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'usage_count' => 5,
            'performance_metrics' => [
                'avg_load_time' => 2.3,
                'conversion_rate' => 0.15,
                'bounce_rate' => 0.2,
            ],
        ]);

        // Test usage statistics
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/analytics");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'usage_count' => 5,
                        'conversion_rate' => 0.15,
                        'avg_load_time' => 2.3,
                    ]
                ]);

        // Test bulk analytics
        $template2 = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'usage_count' => 10,
        ]);

        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates/analytics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id', 'name', 'usage_count', 'conversion_rate', 'performance_metrics'
                        ]
                    ]
                ]);

        $analyticsData = $response->json('data');
        $this->assertCount(2, $analyticsData);
    }

    public function test_template_export_import_functionality()
    {
        // Create template for export
        $originalTemplate = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Export Test Template',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => ['title' => 'Export Test'],
                    ]
                ]
            ],
            'default_config' => ['theme' => ['primary_color' => '#ff0000']],
        ]);

        // Test export
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$originalTemplate->id}/export");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'template', 'version', 'exported_at', 'exported_by'
                    ]
                ]);

        $exportData = $response->json('data');
        $this->assertEquals($originalTemplate->id, $exportData['template']['id']);
        $this->assertEquals('Export Test Template', $exportData['template']['name']);

        // Test import
        $importData = [
            'template_data' => $exportData['template'],
            'options' => [
                'reset_usage_count' => true,
                'generate_new_name' => true,
            ],
        ];

        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates/import', $importData);

        $response->assertStatus(201);
        $importedTemplateId = $response->json('data.id');

        $this->assertDatabaseHas('templates', [
            'id' => $importedTemplateId,
            'tenant_id' => $this->testUser->tenant_id,
            'usage_count' => 0, // Should be reset
        ]);

        $importedTemplate = Template::find($importedTemplateId);
        $this->assertEquals($originalTemplate->structure, $importedTemplate->structure);
    }

    public function test_template_versioning_and_cloning()
    {
        $originalTemplate = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Version Test Template',
            'version' => 1,
        ]);

        // Test template cloning
        $cloneData = [
            'name' => 'Version Test Template - Clone',
            'template_id' => $originalTemplate->id,
        ];

        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates/clone', $cloneData);

        $response->assertStatus(201);
        $clonedTemplateId = $response->json('data.id');

        $clonedTemplate = Template::find($clonedTemplateId);
        $this->assertEquals($this->testUser->tenant_id, $clonedTemplate->tenant_id);
        $this->assertEquals('Version Test Template - Clone', $clonedTemplate->name);

        // Test version creation
        $versionData = [
            'version_name' => 'Version 2.0',
            'changes' => 'Enhanced features and improvements',
            'template_id' => $originalTemplate->id,
        ];

        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates/versions', $versionData);

        $response->assertStatus(201);

        // Verify version history
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$originalTemplate->id}/versions");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id', 'version_name', 'changes', 'created_at'
                        ]
                    ]
                ]);
    }

    public function test_template_search_and_filtering()
    {
        // Create test templates
        Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Marketing Landing Page',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing',
            'tags' => ['marketing', 'landing'],
        ]);

        Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Admin Dashboard',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'onboarding',
            'tags' => ['dashboard', 'admin'],
        ]);

        // Test search by name
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?search=Marketing');

        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertCount(1, $templates);
        $this->assertEquals('Marketing Landing Page', $templates[0]['name']);

        // Test filter by category
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?category=landing');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.data'));

        // Test filter by audience
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?audience_type=individual');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.data'));

        // Test filter by campaign type
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?campaign_type=onboarding');

        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertCount(1, $templates);
        $this->assertEquals('Admin Dashboard', $templates[0]['name']);

        // Test filter by tags
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?tags[]=marketing');

        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertCount(1, $templates);
        $this->assertEquals('Marketing Landing Page', $templates[0]['name']);
    }

    public function test_template_cache_performance()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Cache Test Template',
        ]);

        // First request - cache miss
        $startTime = microtime(true);
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/preview");
        $firstRequestTime = microtime(true) - $startTime;

        $response->assertStatus(200);

        // Second request - cache hit (should be faster)
        $startTime = microtime(true);
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/preview");
        $secondRequestTime = microtime(true) - $startTime;

        $response->assertStatus(200);

        // Cache hit should be significantly faster
        // Note: Actual performance improvement depends on cache configuration
        $this->assertLessThan($firstRequestTime, $secondRequestTime * 0.8);
    }
}