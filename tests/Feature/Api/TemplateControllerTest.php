<?php

namespace Tests\Feature\Api;

use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_list_templates()
    {
        Template::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson('/api/templates');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'templates' => [
                        '*' => [
                            'id', 'name', 'slug', 'category', 'is_active'
                        ]
                    ],
                    'pagination' => [
                        'current_page', 'last_page', 'total', 'per_page'
                    ],
                    'meta' => [
                        'total_count', 'categories', 'audience_types', 'campaign_types'
                    ]
                ]);
    }

    /** @test */
    public function it_can_create_a_template()
    {
        $templateData = [
            'name' => 'Test Landing Page Template',
            'description' => 'A test template',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'onboarding',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Welcome'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/templates', $templateData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'template' => [
                        'id', 'name', 'slug', 'category', 'is_active'
                    ],
                    'message'
                ]);

        $this->assertDatabaseHas('templates', [
            'name' => $templateData['name'],
            'category' => $templateData['category'],
            'tenant_id' => $this->tenant->id
        ]);
    }

    /** @test */
    public function it_can_show_a_template()
    {
        $template = Template::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson("/api/templates/{$template->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'template' => [
                        'id', 'name', 'slug', 'category', 'structure'
                    ],
                    'stats' => [
                        'usage_count', 'conversion_rate_avg'
                    ]
                ]);
    }

    /** @test */
    public function it_can_update_a_template()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Old Name'
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description'
        ];

        $response = $this->putJson("/api/templates/{$template->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'template' => ['id', 'name', 'description'],
                    'message'
                ]);

        $this->assertDatabaseHas('templates', [
            'id' => $template->id,
            'name' => 'Updated Name',
            'description' => 'Updated description'
        ]);
    }

    /** @test */
    public function it_can_delete_a_template()
    {
        $template = Template::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->deleteJson("/api/templates/{$template->id}");

        $response->assertStatus(200)
                ->assertJson(['message' => 'Template deleted successfully']);

        $this->assertSoftDeleted($template);
    }

    /** @test */
    public function it_can_search_templates()
    {
        Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Landing Page Template'
        ]);
        Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Email Template'
        ]);

        $response = $this->getJson('/api/templates/search?q=Landing');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'query',
                    'results' => [
                        '*' => ['id', 'name', 'slug']
                    ],
                    'count'
                ])
                ->assertJsonCount(1, 'results');
    }

    /** @test */
    public function it_can_get_template_categories()
    {
        $response = $this->getJson('/api/templates/categories');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'categories',
                    'templates_by_category'
                ]);
    }

    /** @test */
    public function it_can_preview_a_template()
    {
        $template = Template::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson("/api/templates/{$template->id}/preview");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'template_id',
                    'structure',
                    'config'
                ]);
    }

    /** @test */
    public function it_can_duplicate_a_template()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Template'
        ]);

        $response = $this->postJson("/api/templates/{$template->id}/duplicate", [
            'name' => 'Duplicated Template'
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'template' => ['id', 'name', 'slug'],
                    'message'
                ]);

        $this->assertDatabaseHas('templates', [
            'name' => 'Duplicated Template',
            'tenant_id' => $this->tenant->id
        ]);
    }

    /** @test */
    public function it_can_activate_a_template()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => false
        ]);

        $response = $this->postJson("/api/templates/{$template->id}/activate");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'template' => ['id', 'is_active'],
                    'message'
                ]);

        $this->assertDatabaseHas('templates', [
            'id' => $template->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_template()
    {
        $response = $this->getJson('/api/templates/9999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Remove authentication
        $this->withoutMiddleware();

        $response = $this->getJson('/api/templates');

        $response->assertStatus(401);
    }
}