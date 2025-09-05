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
use Tests\TestCase;

class TemplateWorkflowEndToEndTest extends TestCase
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

    public function test_complete_template_creation_workflow_tenant_isolation()
    {
        // Step 1: User 1 (Tenant A) creates a landing page template
        $templateData1 = [
            'name' => 'Admission Campaign Landing Page',
            'description' => 'High-converting landing page for undergraduate admissions',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'onboarding',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Start Your Journey at State University A',
                            'subtitle' => 'Join thousands of students pursuing excellence',
                            'cta_text' => 'Apply Now',
                            'background_type' => 'video',
                        ]
                    ],
                    [
                        'type' => 'statistics',
                        'config' => [
                            'title' => 'Why Choose State University A',
                            'items' => [
                                ['label' => 'Students', 'value' => '25,000'],
                                ['label' => 'Acceptance Rate', 'value' => '78%'],
                                ['label' => 'Graduation Rate', 'value' => '92%'],
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
                            ],
                            'submit_text' => 'Start Application',
                        ]
                    ]
                ]
            ],
            'default_config' => [
                'theme' => [
                    'primary_color' => '#004080',
                    'secondary_color' => '#0066cc',
                    'accent_color' => '#00aaff',
                ],
                'typography' => [
                    'heading_font' => 'Arial, sans-serif',
                    'body_font' => 'Georgia, serif',
                    'font_size_base' => '16px',
                ]
            ],
        ];

        $response = $this->actingAs($this->user1)
            ->postJson('/api/templates', $templateData1);

        $response->assertStatus(201);
        $template1 = Template::find($response->json('data.id'));
        $this->assertEquals($this->tenant1->id, $template1->tenant_id);

        // Verify tenant 2 cannot see template 1
        $response = $this->actingAs($this->user2)
            ->getJson('/api/templates');

        $response->assertStatus(200);
        $templatesForTenant2 = $response->json('data.data');
        $this->assertEmpty($templatesForTenant2);
        $this->assertDatabaseMissing('templates', [
            'id' => $template1->id,
            'tenant_id' => $this->tenant2->id,
        ]);

        // Step 2: User 2 (Tenant B) creates their own template
        $templateData2 = [
            'name' => 'Graduate Program Campaign',
            'description' => 'Landing page for graduate admissions at State University B',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'leadership',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Advance Your Career at State University B',
                            'subtitle' => 'World-class graduate programs for tomorrow\'s leaders',
                            'cta_text' => 'Explore Programs',
                            'background_type' => 'image',
                        ]
                    ],
                    [
                        'type' => 'testimonials',
                        'config' => [
                            'title' => 'Hear from Our Graduates',
                            'testimonials' => [
                                [
                                    'content' => 'The graduate program transformed my career trajectory.',
                                    'author' => 'Sarah Chen, MBA 2020',
                                    'position' => 'VP of Operations at TechCorp',
                                ]
                            ]
                        ]
                    ],
                ]
            ],
            'default_config' => [
                'theme' => [
                    'primary_color' => '#8b0000',
                    'secondary_color' => '#b22222',
                    'accent_color' => '#dc143c',
                ],
                'typography' => [
                    'heading_font' => 'Helvetica, sans-serif',
                    'body_font' => 'Times New Roman, serif',
                ]
            ],
        ];

        $response = $this->actingAs($this->user2)
            ->postJson('/api/templates', $templateData2);

        $response->assertStatus(201);
        $template2 = Template::find($response->json('data.id'));
        $this->assertEquals($this->tenant2->id, $template2->tenant_id);

        // Step 3: User 1 creates landing pages from their template
        $landingPageData1 = [
            'template_id' => $template1->id,
            'name' => 'Fall 2024 Undergraduate Admissions',
            'campaign_type' => 'onboarding',
            'audience_type' => 'individual',
            'category' => $this->institution1->id,
            'config' => [
                'hero' => [
                    'title' => 'Fall 2024 Admissions Now Open',
                    'subtitle' => 'Applications due February 1, 2024',
                ],
                'form' => [
                    'title' => 'Begin Your Application',
                ]
            ],
            'brand_config' => [
                'logo_url' => 'https://state-university-a.edu/logo.png',
                'primary_color' => '#004080',
                'secondary_color' => '#0066cc',
                'institution_name' => 'State University A',
            ],
            'seo_title' => 'Undergraduate Admissions - Fall 2024 | State University A',
            'seo_description' => 'Apply to State University A for Fall 2024. Join our vibrant academic community.',
            'tracking_id' => 'UA-' . $this->faker->randomNumber(8),
        ];

        $response = $this->actingAs($this->user1)
            ->postJson('/api/landing-pages', $landingPageData1);

        $response->assertStatus(201);
        $landingPage1 = LandingPage::find($response->json('data.id'));
        $this->assertEquals($template1->id, $landingPage1->template_id);
        $this->assertEquals($this->tenant1->id, $landingPage1->tenant_id);

        // Step 4: User 2 creates their landing page from their template
        $landingPageData2 = [
            'template_id' => $template2->id,
            'name' => 'MBA Program Admissions 2024',
            'campaign_type' => 'leadership',
            'audience_type' => 'individual',
            'category' => $this->institution2->id,
            'config' => [
                'hero' => [
                    'title' => 'MBA Program - Shape Your Future',
                    'subtitle' => 'Applications for Fall 2024 now being accepted',
                ],
            ],
            'brand_config' => [
                'logo_url' => 'https://state-university-b.edu/logo.png',
                'primary_color' => '#8b0000',
                'secondary_color' => '#b22222',
                'institution_name' => 'State University B',
            ],
            'seo_title' => 'MBA Program Admissions | State University B',
            'seo_description' => 'Join State University B\'s prestigious MBA program.',
        ];

        $response = $this->actingAs($this->user2)
            ->postJson('/api/landing-pages', $landingPageData2);

        $response->assertStatus(201);
        $landingPage2 = LandingPage::find($response->json('data.id'));
        $this->assertEquals($template2->id, $landingPage2->template_id);
        $this->assertEquals($this->tenant2->id, $landingPage2->tenant_id);

        // Step 5: Verify tenant isolation in queries
        // User 1 can only see their own templates and landing pages
        $response = $this->actingAs($this->user1)
            ->getJson('/api/templates');

        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertCount(1, $templates);
        $this->assertEquals($template1->name, $templates[0]['name']);

        $response = $this->actingAs($this->user1)
            ->getJson('/api/landing-pages');

        $response->assertStatus(200);
        $landingPages = $response->json('data.data');
        $this->assertCount(1, $landingPages);
        $this->assertEquals($landingPage1->name, $landingPages[0]['name']);

        // User 2 can only see their own templates and landing pages
        $response = $this->actingAs($this->user2)
            ->getJson('/api/templates');

        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertCount(1, $templates);
        $this->assertEquals($template2->name, $templates[0]['name']);

        $response = $this->actingAs($this->user2)
            ->getJson('/api/landing-pages');

        $response->assertStatus(200);
        $landingPages = $response->json('data.data');
        $this->assertCount(1, $landingPages);
        $this->assertEquals($landingPage2->name, $landingPages[0]['name']);

        // Step 6: Test cross-tenant security - users cannot access other tenants' resources
        $response = $this->actingAs($this->user1)
            ->getJson("/api/templates/{$template2->id}");

        $response->assertStatus(403); // Forbidden

        $response = $this->actingAs($this->user2)
            ->getJson("/api/landing-pages/{$landingPage1->id}");

        $response->assertStatus(403); // Forbidden

        // Step 7: Test template preview (before publishing)
        $response = $this->actingAs($this->user1)
            ->getJson("/api/landing-pages/{$landingPage1->id}/preview");

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.preview_url'));
        $this->assertEquals('draft', $landingPage1->fresh()->status);

        // Step 8: Publish landing pages
        $response = $this->actingAs($this->user1)
            ->postJson("/api/landing-pages/{$landingPage1->id}/publish");

        $response->assertStatus(200);
        $this->assertEquals('published', $landingPage1->fresh()->status);

        $response = $this->actingAs($this->user2)
            ->postJson("/api/landing-pages/{$landingPage2->id}/publish");

        $response->assertStatus(200);
        $this->assertEquals('published', $landingPage2->fresh()->status);

        // Step 9: Verify public URLs are tenant-specific
        $this->assertContains($this->tenant1->domain, $landingPage1->fresh()->public_url ?: '');
        $this->assertContains($this->tenant2->domain, $landingPage2->fresh()->public_url ?: '');

        // Step 10: Test usage tracking and analytics
        // Simulate visitor interaction with landing page 1
        $response = $this->get($landingPage1->fresh()->public_url . '?utm_source=test&utm_campaign=admissions');
        $response->assertStatus(200);

        // Increment usage counter
        $landingPage1->fresh()->increment('usage_count');
        $this->assertGreaterThan(0, $landingPage1->fresh()->usage_count);

        // Step 11: Verify template usage statistics
        $stats = $template1->fresh()->getUsageStats();
        $this->assertEquals(1, $stats['landing_page_count']);
        $this->assertGreaterThan(0, $stats['usage_count']);

        // Final verification: Complete tenant isolation maintained throughout workflow
        $this->assertEquals($this->tenant1->id, $template1->fresh()->tenant_id);
        $this->assertEquals($this->tenant1->id, $landingPage1->fresh()->tenant_id);
        $this->assertEquals($this->tenant2->id, $template2->fresh()->tenant_id);
        $this->assertEquals($this->tenant2->id, $landingPage2->fresh()->tenant_id);
    }
}