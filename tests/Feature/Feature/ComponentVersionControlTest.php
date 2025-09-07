<?php

use App\Models\Component;
use App\Models\ComponentVersion;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test tenant and user
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    
    // Create test component
    $this->component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Test Component',
        'category' => 'hero',
        'config' => [
            'headline' => 'Original Headline',
            'subheading' => 'Original Subheading',
        ],
    ]);

    $this->actingAs($this->user);
    Storage::fake('local');
});

test('can create component version', function () {
    $response = $this->postJson("/api/components/{$this->component->id}/versions", [
        'description' => 'Initial version',
        'changes' => ['action' => 'created'],
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Version created successfully',
        ]);

    expect('component_versions')->toBeInDatabase([
        'component_id' => $this->component->id,
        'version_number' => 1,
        'description' => 'Initial version',
    ]);
});

test('can get version history', function () {
    // Create multiple versions
    ComponentVersion::factory()->count(3)->create([
        'component_id' => $this->component->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->getJson("/api/components/{$this->component->id}/versions");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'component_id' => $this->component->id,
                'total_versions' => 3,
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'versions' => [
                    '*' => [
                        'id',
                        'version_number',
                        'description',
                        'changes',
                        'created_by',
                        'created_at',
                        'is_latest',
                    ],
                ],
            ],
        ]);
});

test('can get specific version details', function () {
    $version = ComponentVersion::factory()->create([
        'component_id' => $this->component->id,
        'version_number' => 1,
        'config' => ['test' => 'data'],
        'created_by' => $this->user->id,
    ]);

    $response = $this->getJson("/api/components/{$this->component->id}/versions/{$version->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'version' => [
                    'id' => $version->id,
                    'version_number' => 1,
                    'config' => ['test' => 'data'],
                ],
            ],
        ]);
});

test('can restore component to version', function () {
    // Create a version with different config
    $version = ComponentVersion::factory()->create([
        'component_id' => $this->component->id,
        'version_number' => 1,
        'config' => [
            'headline' => 'Restored Headline',
            'subheading' => 'Restored Subheading',
        ],
        'created_by' => $this->user->id,
    ]);

    $response = $this->postJson("/api/components/{$this->component->id}/versions/{$version->id}/restore");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Component restored to version {$version->version_number}",
        ]);

    // Verify component was updated
    $this->component->refresh();
    expect($this->component->config['headline'])->toBe('Restored Headline');
});

test('can compare versions', function () {
    $version1 = ComponentVersion::factory()->create([
        'component_id' => $this->component->id,
        'version_number' => 1,
        'config' => ['headline' => 'Version 1'],
        'created_by' => $this->user->id,
    ]);

    $version2 = ComponentVersion::factory()->create([
        'component_id' => $this->component->id,
        'version_number' => 2,
        'config' => ['headline' => 'Version 2'],
        'created_by' => $this->user->id,
    ]);

    $response = $this->postJson("/api/components/{$this->component->id}/versions/compare", [
        'from_version' => 1,
        'to_version' => 2,
        'format' => 'standard',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'data' => [
                'diff' => [
                    'version_from',
                    'version_to',
                    'config_diff',
                ],
            ],
        ]);
});

test('can export component', function () {
    $response = $this->postJson("/api/components/{$this->component->id}/export", [
        'format' => 'grapejs',
        'include_versions' => true,
        'include_analytics' => false,
        'file_format' => 'json',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'data' => [
                'export_info',
                'component',
            ],
        ]);
});

test('can import component', function () {
    $exportData = [
        'export_info' => [
            'version' => '1.0.0',
            'format' => 'grapejs',
            'exported_at' => now()->toISOString(),
            'component_id' => 999,
        ],
        'component' => [
            'name' => 'Imported Component',
            'slug' => 'imported-component',
            'category' => 'hero',
            'type' => 'basic',
            'config' => ['test' => 'imported'],
            'metadata' => [],
            'version' => '1.0.0',
            'is_active' => true,
        ],
    ];

    $response = $this->postJson('/api/components/import', [
        'export_data' => $exportData,
        'overwrite_existing' => false,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Component imported successfully',
        ]);

    expect('components')->toBeInDatabase([
        'name' => 'Imported Component',
        'slug' => 'imported-component',
        'tenant_id' => $this->tenant->id,
    ]);
});

test('can create template from grapejs', function () {
    $grapeJSData = [
        'components' => [
            ['type' => 'text', 'content' => 'Hello World'],
        ],
        'style' => [
            '.my-class' => ['color' => 'red'],
        ],
    ];

    $templateInfo = [
        'name' => 'GrapeJS Template',
        'category' => 'hero',
        'type' => 'template',
        'description' => 'Created from GrapeJS',
    ];

    $response = $this->postJson('/api/components/create-template', [
        'grapejs_data' => $grapeJSData,
        'template_info' => $templateInfo,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Template created successfully from GrapeJS configuration',
        ]);

    expect('components')->toBeInDatabase([
        'name' => 'GrapeJS Template',
        'category' => 'hero',
        'type' => 'template',
    ]);
});

test('can analyze component performance', function () {
    $response = $this->getJson("/api/components/{$this->component->id}/performance/analyze");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'data' => [
                'component_id',
                'analyzed_at',
                'performance_score',
                'metrics',
                'recommendations',
                'grapejs_optimizations',
            ],
        ]);
});

test('can get performance trends', function () {
    $response = $this->getJson("/api/components/{$this->component->id}/performance/trends?days=7");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'data' => [
                'trends',
                'period_days',
            ],
        ]);
});

test('can create backup', function () {
    $response = $this->postJson("/api/components/{$this->component->id}/backup", [
        'type' => 'full',
        'include_analytics' => true,
        'storage' => 'local',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Backup created successfully',
        ])
        ->assertJsonStructure([
            'data' => [
                'backup_id',
                'type',
                'size',
                'created_at',
            ],
        ]);
});

test('can list backups', function () {
    // Create a backup first
    $this->postJson("/api/components/{$this->component->id}/backup", [
        'type' => 'full',
    ]);

    $response = $this->getJson("/api/components/{$this->component->id}/backups");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'data' => [
                'backups',
                'total_backups',
            ],
        ]);
});

test('can migrate component', function () {
    $response = $this->postJson("/api/components/{$this->component->id}/migrate", [
        'target_version' => '2.0.0',
        'migration_type' => 'grapejs_format',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Component migrated successfully',
        ]);
});

test('version validation fails with invalid data', function () {
    $response = $this->postJson("/api/components/{$this->component->id}/versions", [
        'description' => str_repeat('a', 501), // Too long
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Validation failed',
        ]);
});

test('cannot access version from different component', function () {
    $otherComponent = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $version = ComponentVersion::factory()->create([
        'component_id' => $otherComponent->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->getJson("/api/components/{$this->component->id}/versions/{$version->id}");

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Version does not belong to this component',
        ]);
});

test('export with file format creates file', function () {
    $response = $this->postJson("/api/components/{$this->component->id}/export", [
        'file_format' => 'json',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Component exported to file',
        ])
        ->assertJsonStructure([
            'data' => [
                'file_path',
                'download_url',
            ],
        ]);

    // Verify file was created
    $filePath = $response->json('data.file_path');
    Storage::disk('local')->assertExists($filePath);
});

test('performance comparison between versions', function () {
    $version1 = ComponentVersion::factory()->create([
        'component_id' => $this->component->id,
        'version_number' => 1,
        'created_by' => $this->user->id,
    ]);

    $version2 = ComponentVersion::factory()->create([
        'component_id' => $this->component->id,
        'version_number' => 2,
        'created_by' => $this->user->id,
    ]);

    $response = $this->postJson("/api/components/{$this->component->id}/performance/compare", [
        'version1' => 1,
        'version2' => 2,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'data' => [
                'version_1',
                'version_2',
                'comparison',
            ],
        ]);
});
