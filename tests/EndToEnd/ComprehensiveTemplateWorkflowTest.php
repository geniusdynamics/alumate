<?php

namespace Tests\EndToEnd;

use App\Models\BrandConfig;
use App\Models\Institution;
use App\Models\LandingPage;
use App\Models\Tenant;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ComprehensiveTemplateWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected Institution $institution1;
    protected Institution $institution2;
    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(['public', 'private']);
        Cache::flush();

        // Set up first tenant (University A)
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'State University A',
            'domain' => 'state-university-a.edu',
            'database' => 'tenant_a',
        ]);

        $this->institution1 = Institution::factory()->create([
            'name' => 'State University A',
            'domain' => 'state-university-a.edu',
        ]);

        $this->user1 = User::factory()->create([
            'name' => 'Admin Johnson',
            'email' => 'admin@state-university-a.edu',
            'tenant_id' => $this->tenant1->id,
            'institution_id' => $this->institution1->id,
        ]);

        // Set up second tenant (University B)
        $this->tenant2 = Tenant::factory()->create([
            'name' => 'State University B',
            'domain' => 'state-university-b.edu',
            'database' => 'tenant_b',
        ]);

        $this->institution2 = Institution::factory()->create([
            'name' => 'State University B',
            'domain' => 'state-university-b.edu',
        ]);

        $this->user2 = User::factory()->create([
            'name' => 'Admin Smith',
            'email' => 'admin@state-university-b.edu',
            'tenant_id' => $this->tenant2->id,
            'institution_id' => $this->institution2->id,
        ]);
    }

    public function test_complete_template_lifecycle_with_brand_customization()
    {
        // Step 1: Create brand configuration
        $brandConfigData = [
            'institution_name' => 'State University A',
            'logo_url' => 'https://state-university-a.edu/logo.png',
            'primary_color' => '#004080',
            'secondary_color' => '#0066cc',
            'accent_color' => '#00aaff',
            'font_family' => 'Arial, sans-serif',
            'website_url' => 'https://state-university-a.edu',
            'social_links' => [
                'facebook' => 'https://facebook.com/stateuniva',
                'twitter' => 'https://twitter.com/stateuniva',
                'linkedin' => 'https://linkedin.com/school/stateuniva'
            ]
        ];

        $response = $this->actingAs($this->user1)
            ->postJson('/api/brand-config', $brandConfigData);

        $response->assertStatus(201);
        $brandConfig = BrandConfig::find($response->json('data.id'));
        $this->assertEquals($this->tenant1->id, $brandConfig->tenant_id);

        // Step 2: Create template with brand integration
        $templateData = [
            'name' => 'Branded Admission Campaign Template',
            'description' => 'High-converting landing page with full brand integration',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'onboarding',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => '{{institution_name}} - Start Your Journey',
                            'subtitle' => 'Join thousands of students pursuing excellence',
                            'cta_text' => 'Apply Now',
                            'background_type' => 'video',
                            'brand_logo' => '{{logo_url}}'
                        ]
                    ],
                    [
                        'type' => 'statistics',
                        'config' => [
                            'title' => 'Why Choose {{institution_name}}',
                            'items' => [
                                ['label' => 'Students', 'value' => '25,000+'],
                                ['label' => 'Acceptance Rate', 'value' => '78%'],
                                ['label' => 'Graduation Rate', 'value' => '92%'],
                            ]
                        ]
                    ],
                    [
                        'type' => 'social_proof',
                        'config' => [
                            'title' => 'Trusted by Industry Leaders',
                            'testimonials' => [
                                [
                                    'content' => 'The program transformed my career trajectory.',
                                    'author' => 'Sarah Chen, MBA 2020',
                                    'position' => 'VP of Operations at TechCorp',
                                    'company_logo' => 'https://example.com/techcorp-logo.png'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'form',
                        'config' => [
                            'title' => 'Ready to Join Our Community?',
                            'fields' => [
                                ['type' => 'text', 'name' => 'first_name', 'label' => 'First Name', 'required' => true],
                                ['type' => 'text', 'name' => 'last_name', 'label' => 'Last Name', 'required' => true],
                                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
                                ['type' => 'tel', 'name' => 'phone', 'label' => 'Phone'],
                                ['type' => 'select', 'name' => 'program_interest', 'label' => 'Program of Interest', 'options' => ['Undergraduate', 'Graduate', 'Certificate']],
                            ],
                            'submit_text' => 'Start Application',
                            'success_message' => 'Thank you for your interest in {{institution_name}}!'
                        ]
                    ]
                ]
            ],
            'default_config' => [
                'theme' => [
                    'primary_color' => '{{primary_color}}',
                    'secondary_color' => '{{secondary_color}}',
                    'accent_color' => '{{accent_color}}',
                ],
                'typography' => [
                    'heading_font' => '{{font_family}}',
                    'body_font' => 'Georgia, serif',
                    'font_size_base' => '16px',
                ],
                'brand_integration' => [
                    'logo_url' => '{{logo_url}}',
                    'website_url' => '{{website_url}}',
                    'social_links' => '{{social_links}}'
                ]
            ],
            'brand_config_id' => $brandConfig->id,
            'is_premium' => true,
            'tags' => ['admissions', 'undergraduate', 'branded']
        ];

        $response = $this->actingAs($this->user1)
            ->postJson('/api/templates', $templateData);

        $response->assertStatus(201);
        $template = Template::find($response->json('data.id'));
        $this->assertEquals($this->tenant1->id, $template->tenant_id);
        $this->assertEquals($brandConfig->id, $template->brand_config_id);

        // Step 3: Test template customization interface
        $response = $this->actingAs($this->user1)
            ->getJson("/api/templates/{$template->id}/customization-options");

        $response->assertStatus(200);
        $customizationOptions = $response->json('data');
        $this->assertArrayHasKey('sections', $customizationOptions);
        $this->assertArrayHasKey('theme', $customizationOptions);
        $this->assertArrayHasKey('brand_variables', $customizationOptions);

        // Step 4: Create landing page with customizations
        $landingPageData = [
            'template_id' => $template->id,
            'name' => 'Fall 2024 Undergraduate Admissions',
            'campaign_type' => 'onboarding',
            'audience_type' => 'individual',
            'category' => $this->institution1->id,
            'config' => [
                'hero' => [
                    'title' => 'Fall 2024 Admissions Now Open',
                    'subtitle' => 'Applications due February 1, 2024',
                    'background_video_url' => 'https://example.com/campus-tour.mp4'
                ],
                'statistics' => [
                    'items' => [
                        ['label' => 'Students', 'value' => '25,000+'],
                        ['label' => 'Acceptance Rate', 'value' => '78%'],
                        ['label' => 'Graduation Rate', 'value' => '92%'],
                        ['label' => 'Average Starting Salary', 'value' => '$65,000'],
                    ]
                ],
                'form' => [
                    'title' => 'Begin Your Application Today',
                    'additional_fields' => [
                        ['type' => 'date', 'name' => 'graduation_date', 'label' => 'Expected Graduation Date'],
                        ['type' => 'textarea', 'name' => 'additional_info', 'label' => 'Tell us about yourself'],
                    ]
                ]
            ],
            'brand_config' => [
                'logo_url' => 'https://state-university-a.edu/logo.png',
                'primary_color' => '#004080',
                'secondary_color' => '#0066cc',
                'institution_name' => 'State University A',
                'social_links' => [
                    'facebook' => 'https://facebook.com/stateuniva',
                    'instagram' => 'https://instagram.com/stateuniva'
                ]
            ],
            'seo_title' => 'Undergraduate Admissions - Fall 2024 | State University A',
            'seo_description' => 'Apply to State University A for Fall 2024. Join our vibrant academic community of 25,000+ students.',
            'tracking_id' => 'UA-' . $this->faker->randomNumber(8),
            'ab_testing_enabled' => true,
            'mobile_optimized' => true,
        ];

        $response = $this->actingAs($this->user1)
            ->postJson('/api/landing-pages', $landingPageData);

        $response->assertStatus(201);
        $landingPage = LandingPage::find($response->json('data.id'));
        $this->assertEquals($template->id, $landingPage->template_id);
        $this->assertEquals($this->tenant1->id, $landingPage->tenant_id);
        $this->assertTrue($landingPage->ab_testing_enabled);
        $this->assertTrue($landingPage->mobile_optimized);

        // Step 5: Test A/B testing setup
        $abTestData = [
            'variations' => [
                [
                    'name' => 'Control',
                    'weight' => 50,
                    'config' => [
                        'hero' => [
                            'cta_text' => 'Apply Now'
                        ]
                    ]
                ],
                [
                    'name' => 'Variation A',
                    'weight' => 30,
                    'config' => [
                        'hero' => [
                            'cta_text' => 'Start Your Application'
                        ]
                    ]
                ],
                [
                    'name' => 'Variation B',
                    'weight' => 20,
                    'config' => [
                        'hero' => [
                            'cta_text' => 'Join Our Community'
                        ]
                    ]
                ]
            ],
            'metrics' => ['conversion_rate', 'form_submissions', 'time_on_page'],
            'duration_days' => 14
        ];

        $response = $this->actingAs($this->user1)
            ->postJson("/api/landing-pages/{$landingPage->id}/ab-test", $abTestData);

        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data.variations')));

        // Step 6: Test preview functionality
        $response = $this->actingAs($this->user1)
            ->getJson("/api/landing-pages/{$landingPage->id}/preview");

        $response->assertStatus(200);
        $previewData = $response->json('data');
        $this->assertArrayHasKey('rendered_html', $previewData);
        $this->assertArrayHasKey('mobile_preview_url', $previewData);
        $this->assertArrayHasKey('seo_score', $previewData);

        // Step 7: Test mobile rendering
        $response = $this->actingAs($this->user1)
            ->getJson("/api/landing-pages/{$landingPage->id}/preview/mobile");

        $response->assertStatus(200);
        $mobilePreview = $response->json('data');
        $this->assertArrayHasKey('mobile_html', $mobilePreview);
        $this->assertArrayHasKey('responsive_breakpoints', $mobilePreview);

        // Step 8: Publish landing page
        $response = $this->actingAs($this->user1)
            ->postJson("/api/landing-pages/{$landingPage->id}/publish");

        $response->assertStatus(200);
        $this->assertEquals('published', $landingPage->fresh()->status);
        $this->assertNotNull($landingPage->fresh()->public_url);

        // Step 9: Test public access and analytics tracking
        $publicUrl = $landingPage->fresh()->public_url;
        $response = $this->get($publicUrl . '?utm_source=test&utm_campaign=admissions&utm_medium=email');
        $response->assertStatus(200);

        // Verify analytics tracking
        $response = $this->actingAs($this->user1)
            ->getJson("/api/landing-pages/{$landingPage->id}/analytics");

        $response->assertStatus(200);
        $analytics = $response->json('data');
        $this->assertGreaterThan(0, $analytics['page_views']);
        $this->assertArrayHasKey('utm_tracking', $analytics);

        // Step 10: Test form submission and lead generation
        $formData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
            'program_interest' => 'Undergraduate',
            'graduation_date' => '2028-05-15',
            'additional_info' => 'I am very interested in computer science and research opportunities.'
        ];

        $response = $this->postJson("/api/landing-pages/{$landingPage->slug}/submit", $formData);
        $response->assertStatus(200);

        // Verify lead creation and CRM integration
        $this->assertDatabaseHas('leads', [
            'email' => 'john.doe@example.com',
            'source' => 'landing_page',
            'landing_page_id' => $landingPage->id,
        ]);

        // Step 11: Test performance monitoring
        $response = $this->actingAs($this->user1)
            ->getJson("/api/landing-pages/{$landingPage->id}/performance");

        $response->assertStatus(200);
        $performance = $response->json('data');
        $this->assertArrayHasKey('load_time', $performance);
        $this->assertArrayHasKey('mobile_score', $performance);
        $this->assertArrayHasKey('seo_score', $performance);

        // Step 12: Test export functionality
        $response = $this->actingAs($this->user1)
            ->postJson("/api/landing-pages/{$landingPage->id}/export", [
                'format' => 'json',
                'include_analytics' => true,
                'include_submissions' => true
            ]);

        $response->assertStatus(200);
        $exportData = $response->json('data');
        $this->assertArrayHasKey('landing_page', $exportData);
        $this->assertArrayHasKey('analytics', $exportData);
        $this->assertArrayHasKey('submissions', $exportData);

        // Step 13: Test template usage statistics
        $stats = $template->fresh()->getUsageStats();
        $this->assertEquals(1, $stats['landing_page_count']);
        $this->assertGreaterThan(0, $stats['usage_count']);
        $this->assertGreaterThan(0, $stats['conversion_count']);

        // Step 14: Verify tenant isolation throughout workflow
        $this->assertEquals($this->tenant1->id, $template->fresh()->tenant_id);
        $this->assertEquals($this->tenant1->id, $landingPage->fresh()->tenant_id);
        $this->assertEquals($this->tenant1->id, $brandConfig->fresh()->tenant_id);

        // Cross-tenant access should be forbidden
        $response = $this->actingAs($this->user2)
            ->getJson("/api/templates/{$template->id}");
        $response->assertStatus(403);

        $response = $this->actingAs($this->user2)
            ->getJson("/api/landing-pages/{$landingPage->id}");
        $response->assertStatus(403);

        // Final verification: Complete workflow integrity
        $this->assertEquals('published', $landingPage->fresh()->status);
        $this->assertTrue($landingPage->fresh()->ab_testing_enabled);
        $this->assertNotNull($landingPage->fresh()->public_url);
    }

    public function test_template_versioning_and_rollback()
    {
        // Create initial template
        $templateData = [
            'name' => 'Versioned Template',
            'description' => 'Template with versioning support',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Version 1 Title',
                            'subtitle' => 'Version 1 Subtitle'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->user1)
            ->postJson('/api/templates', $templateData);

        $response->assertStatus(201);
        $template = Template::find($response->json('data.id'));

        // Create version 2
        $version2Data = [
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Version 2 Title',
                            'subtitle' => 'Version 2 Subtitle'
                        ]
                    ],
                    [
                        'type' => 'testimonials',
                        'config' => [
                            'title' => 'What People Say'
                        ]
                    ]
                ]
            ],
            'version_notes' => 'Added testimonials section'
        ];

        $response = $this->actingAs($this->user1)
            ->postJson("/api/templates/{$template->id}/versions", $version2Data);

        $response->assertStatus(201);
        $version2 = $response->json('data');

        // Create landing page with version 2
        $landingPageData = [
            'template_id' => $template->id,
            'name' => 'Versioned Landing Page',
            'campaign_type' => 'marketing',
            'audience_type' => 'individual',
            'category' => $this->institution1->id,
            'template_version_id' => $version2['id']
        ];

        $response = $this->actingAs($this->user1)
            ->postJson('/api/landing-pages', $landingPageData);

        $response->assertStatus(201);
        $landingPage = LandingPage::find($response->json('data.id'));

        // Test rollback to version 1
        $response = $this->actingAs($this->user1)
            ->postJson("/api/landing-pages/{$landingPage->id}/rollback", [
                'template_version_id' => $template->versions()->first()->id
            ]);

        $response->assertStatus(200);

        // Verify rollback
        $landingPage->refresh();
        $this->assertEquals($template->versions()->first()->id, $landingPage->template_version_id);

        // Test version listing
        $response = $this->actingAs($this->user1)
            ->getJson("/api/templates/{$template->id}/versions");

        $response->assertStatus(200);
        $versions = $response->json('data');
        $this->assertCount(2, $versions);
    }

    public function test_error_handling_and_edge_cases()
    {
        // Test invalid template structure
        $invalidTemplateData = [
            'name' => 'Invalid Template',
            'structure' => 'invalid_json_string'
        ];

        $response = $this->actingAs($this->user1)
            ->postJson('/api/templates', $invalidTemplateData);

        $response->assertStatus(422);

        // Test template with missing required fields
        $incompleteTemplateData = [
            'name' => '',
            'category' => 'invalid_category'
        ];

        $response = $this->actingAs($this->user1)
            ->postJson('/api/templates', $incompleteTemplateData);

        $response->assertStatus(422);

        // Test cross-tenant access attempts
        $template = Template::factory()->create(['tenant_id' => $this->tenant1->id]);

        $response = $this->actingAs($this->user2)
            ->getJson("/api/templates/{$template->id}");

        $response->assertStatus(403);

        // Test publishing without proper permissions
        $landingPage = LandingPage::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($this->user2)
            ->postJson("/api/landing-pages/{$landingPage->id}/publish");

        $response->assertStatus(403);

        // Test invalid brand configuration
        $invalidBrandData = [
            'primary_color' => 'invalid_color',
            'logo_url' => 'not_a_url'
        ];

        $response = $this->actingAs($this->user1)
            ->postJson('/api/brand-config', $invalidBrandData);

        $response->assertStatus(422);
    }

    public function test_bulk_operations_and_batch_processing()
    {
        // Create multiple templates
        $templates = [];
        for ($i = 0; $i < 5; $i++) {
            $templateData = [
                'name' => "Bulk Template {$i}",
                'description' => "Template for bulk operations test {$i}",
                'category' => 'landing',
                'audience_type' => 'individual',
                'campaign_type' => 'marketing',
                'structure' => [
                    'sections' => [
                        [
                            'type' => 'hero',
                            'config' => [
                                'title' => "Template {$i} Title"
                            ]
                        ]
                    ]
                ]
            ];

            $response = $this->actingAs($this->user1)
                ->postJson('/api/templates', $templateData);

            $response->assertStatus(201);
            $templates[] = Template::find($response->json('data.id'));
        }

        // Test bulk template operations
        $templateIds = collect($templates)->pluck('id')->toArray();

        $response = $this->actingAs($this->user1)
            ->postJson('/api/templates/bulk', [
                'operation' => 'duplicate',
                'template_ids' => $templateIds
            ]);

        $response->assertStatus(200);
        $duplicatedTemplates = $response->json('data');
        $this->assertCount(5, $duplicatedTemplates);

        // Test bulk landing page creation
        $bulkLandingPageData = [
            'template_id' => $templates[0]->id,
            'landing_pages' => [
                [
                    'name' => 'Bulk LP 1',
                    'campaign_type' => 'marketing'
                ],
                [
                    'name' => 'Bulk LP 2',
                    'campaign_type' => 'onboarding'
                ],
                [
                    'name' => 'Bulk LP 3',
                    'campaign_type' => 'leadership'
                ]
            ]
        ];

        $response = $this->actingAs($this->user1)
            ->postJson('/api/landing-pages/bulk', $bulkLandingPageData);

        $response->assertStatus(200);
        $createdPages = $response->json('data');
        $this->assertCount(3, $createdPages);
    }
}