<?php

namespace Tests\Feature\Api;

use App\Models\Component;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ComponentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Mockery;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Create tenant for testing
    $this->tenant = Tenant::factory()->create();

    // Create user and associate with tenant
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    // Mock ComponentService
    $this->componentService = Mockery::mock(ComponentService::class);

    // Bind mock to container
    $this->app->instance(ComponentService::class, $this->componentService);

    // Set authenticated user
    Sanctum::actingAs($this->user);
});

afterEach(function () {
    Mockery::close();
    Cache::flush(); // Clear cache after tests
});

test('index returns paginated component list', function () {
    $components = Component::factory()->count(3)->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $componentsCollection = collect($components)->paginate(15);

    $this->componentService
        ->shouldReceive('search')
        ->once()
        ->with([
            'search' => null,
            'category' => null,
            'type' => null,
            'is_active' => null,
            'theme_id' => null,
            'version' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'created_after' => null,
            'created_before' => null,
        ], $this->user->tenant_id, 15)
        ->andReturn($componentsCollection);

    $response = $this->getJson('/api/components');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'components',
            'pagination' => [
                'current_page',
                'last_page',
                'per_page',
                'total'
            ]
        ]);
});

test('index filters components by search term', function () {
    $this->componentService
        ->shouldReceive('search')
        ->once()
        ->with([
            'search' => 'hero',
            'category' => null,
            'type' => null,
            'is_active' => null,
            'theme_id' => null,
            'version' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'created_after' => null,
            'created_before' => null,
        ], $this->user->tenant_id, 15)
        ->andReturn(collect([])->paginate(15));

    $response = $this->getJson('/api/components?search=hero');

    $response->assertStatus(200);
});

test('index paginates with custom per page', function () {
    $this->componentService
        ->shouldReceive('search')
        ->once()
        ->with(Mockery::any(), $this->user->tenant_id, 5)
        ->andReturn(collect([])->paginate(5));

    $response = $this->getJson('/api/components?per_page=5');

    $response->assertStatus(200)
        ->assertJson(['pagination' => ['per_page' => 5]]);
});

test('store creates component with valid data', function () {
    $componentData = [
        'name' => 'Test Component',
        'category' => 'hero',
        'type' => 'basic-hero',
        'config' => [
            'headline' => 'Welcome Headline',
            'subheading' => 'Welcome subheading',
            'cta_text' => 'Learn More',
            'cta_url' => 'https://example.com',
            'background_type' => 'gradient',
        ]
    ];

    $createdComponent = Component::factory()->make(array_merge($componentData, [
        'tenant_id' => $this->tenant->id,
        'id' => 1
    ]));

    $this->componentService
        ->shouldReceive('create')
        ->once()
        ->with($componentData, $this->user->tenant_id)
        ->andReturn($createdComponent);

    $response = $this->postJson('/api/components', $componentData);

    $response->assertStatus(201)
        ->assertJson([
            'component' => [
                'name' => 'Test Component',
                'category' => 'hero',
                'type' => 'basic-hero',
            ],
            'message' => 'Component created successfully'
        ]);
});

test('store fails with invalid data', function () {
    $invalidData = [
        'name' => '',
        'category' => 'invalid',
        'type' => 'basic-hero',
        'config' => []
    ];

    $response = $this->postJson('/api/components', $invalidData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'category']);
});
test('duplicate creates component copy', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Original Component',
    ]);

    $duplicatedComponent = Component::factory()->make([
        'tenant_id' => $this->tenant->id,
        'name' => 'Copy of Original Component',
    ]);

    $this->componentService
        ->shouldReceive('duplicate')
        ->once()
        ->with($component, [])
        ->andReturn($duplicatedComponent);

    $response = $this->postJson("/api/components/{$component->id}/duplicate");

    $response->assertStatus(201)
        ->assertJson([
            'component' => [
                'name' => 'Copy of Original Component',
            ],
            'message' => 'Component duplicated successfully'
        ]);
});

test('activate component succeeds', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_active' => false,
    ]);

    $activatedComponent = $component;
    $activatedComponent->is_active = true;

    $this->componentService
        ->shouldReceive('activate')
        ->once()
        ->with($component)
        ->andReturn($activatedComponent);

    $response = $this->postJson("/api/components/{$component->id}/activate");

    $response->assertStatus(200)
        ->assertJson([
            'component' => ['is_active' => true],
            'message' => 'Component activated successfully'
        ]);
});

test('deactivate component succeeds', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_active' => true,
    ]);

    $deactivatedComponent = $component;
    $deactivatedComponent->is_active = false;

    $this->componentService
        ->shouldReceive('deactivate')
        ->once()
        ->with($component)
        ->andReturn($deactivatedComponent);

    $response = $this->postJson("/api/components/{$component->id}/deactivate");

    $response->assertStatus(200)
        ->assertJson([
            'component' => ['is_active' => false],
            'message' => 'Component deactivated successfully'
        ]);
});

test('preview returns component preview data', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $previewData = [
        'preview_html' => '<div>Preview Content</div>',
        'css_variables' => ['primary' => '#000'],
    ];

    $this->componentService
        ->shouldReceive('generatePreview')
        ->once()
        ->with($component, [])
        ->andReturn($previewData);

    $response = $this->getJson("/api/components/{$component->id}/preview");

    $response->assertStatus(200)
        ->assertJson($previewData);
});

test('validate config returns success for valid config', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'config' => ['headline' => 'Valid Headline'],
    ]);

    $response = $this->postJson("/api/components/{$component->id}/validate-config", [
        'config' => ['headline' => 'Valid Headline']
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'valid' => true,
            'message' => 'Configuration is valid'
        ]);
});