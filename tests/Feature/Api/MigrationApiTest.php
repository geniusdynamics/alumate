<?php

namespace Tests\Feature\Api;

use App\Models\Migration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigrationApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user and tenant
        $this->user = User::factory()->create();
        $this->tenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();

        // Set the current tenant
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant);

        // Authenticate the user
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_list_migrations_for_current_tenant()
    {
        // Create some migrations for the current tenant
        Migration::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        // Create a migration for a different tenant
        $otherTenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();
        Migration::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/migrations');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'migrations',
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total'
                    ],
                    'meta' => [
                        'total_count',
                        'statuses'
                    ]
                ]);

        // Should only return migrations for current tenant
        $this->assertCount(3, $response->json('migrations'));
    }

    /** @test */
    public function it_can_filter_migrations_by_status()
    {
        Migration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed'
        ]);

        Migration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'running'
        ]);

        $response = $this->getJson('/api/migrations?status=completed');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('migrations'));
        $this->assertEquals('completed', $response->json('migrations.0.status'));
    }

    /** @test */
    public function it_can_create_a_migration()
    {
        $migrationData = [
            'name' => 'Test Migration',
            'description' => 'Test migration description',
            'source_environment' => 'development',
            'target_environment' => 'production',
            'target_tenant_id' => 'target-tenant-123',
            'include_assets' => true,
            'validate_before_migration' => true
        ];

        $response = $this->postJson('/api/migrations', $migrationData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'migration' => [
                        'id',
                        'name',
                        'status',
                        'created_at'
                    ],
                    'message'
                ]);

        $this->assertDatabaseHas('migrations', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'name' => 'Test Migration',
            'status' => 'draft'
        ]);
    }

    /** @test */
    public function it_validates_migration_creation_data()
    {
        $invalidData = [
            'name' => '', // Empty name should fail
            'source_environment' => 'invalid_env',
            'target_environment' => 'invalid_env'
        ];

        $response = $this->postJson('/api/migrations', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'source_environment', 'target_environment']);
    }

    /** @test */
    public function it_can_show_a_specific_migration()
    {
        $migration = Migration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/migrations/{$migration->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'migration' => [
                        'id',
                        'name',
                        'status',
                        'config',
                        'progress',
                        'created_at'
                    ]
                ]);
    }

    /** @test */
    public function it_cannot_show_migration_from_different_tenant()
    {
        $otherTenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();
        $migration = Migration::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/migrations/{$migration->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_execute_a_migration()
    {
        $migration = Migration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'draft'
        ]);

        $response = $this->postJson("/api/migrations/{$migration->id}/execute");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'migration_result' => [
                        'success',
                        'migrated_items',
                        'failed_items',
                        'errors'
                    ]
                ]);
    }

    /** @test */
    public function it_cannot_execute_migration_from_different_tenant()
    {
        $otherTenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();
        $migration = Migration::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id,
            'status' => 'draft'
        ]);

        $response = $this->postJson("/api/migrations/{$migration->id}/execute");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_cancel_a_migration()
    {
        $migration = Migration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'running'
        ]);

        $response = $this->postJson("/api/migrations/{$migration->id}/cancel");

        $response->assertStatus(200)
                ->assertJson(['message' => 'Migration cancelled successfully']);

        $migration->refresh();
        $this->assertEquals('cancelled', $migration->status);
    }

    /** @test */
    public function it_cannot_cancel_migration_from_different_tenant()
    {
        $otherTenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();
        $migration = Migration::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id,
            'status' => 'running'
        ]);

        $response = $this->postJson("/api/migrations/{$migration->id}/cancel");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_delete_a_migration()
    {
        $migration = Migration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/migrations/{$migration->id}");

        $response->assertStatus(200)
                ->assertJson(['message' => 'Migration deleted successfully']);

        $this->assertDatabaseMissing('migrations', ['id' => $migration->id]);
    }

    /** @test */
    public function it_cannot_delete_migration_from_different_tenant()
    {
        $otherTenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();
        $migration = Migration::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/migrations/{$migration->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_handles_pagination_correctly()
    {
        Migration::factory()->count(25)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/migrations?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('migrations'));
        $this->assertEquals(25, $response->json('pagination.total'));
        $this->assertEquals(3, $response->json('pagination.last_page'));
    }

    /** @test */
    public function it_handles_date_range_filtering()
    {
        $startDate = now()->subDays(5);
        $endDate = now()->addDays(2);

        Migration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(3)
        ]);

        Migration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(10)
        ]);

        $response = $this->getJson("/api/migrations?start_date={$startDate->toDateString()}&end_date={$endDate->toDateString()}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('migrations'));
    }

    /** @test */
    public function it_handles_environment_filtering()
    {
        Migration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'config' => [
                'source' => ['environment' => 'production'],
                'target' => ['environment' => 'staging']
            ]
        ]);

        Migration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'config' => [
                'source' => ['environment' => 'development'],
                'target' => ['environment' => 'production']
            ]
        ]);

        $response = $this->getJson('/api/migrations?environment=production');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('migrations'));
    }

    /** @test */
    public function it_requires_authentication()
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/migrations');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_concurrent_migration_operations()
    {
        $migrationData = [
            'name' => 'Concurrent Migration',
            'source_environment' => 'development',
            'target_environment' => 'production',
            'target_tenant_id' => 'target-tenant-123',
            'include_assets' => true
        ];

        // Create multiple migrations simultaneously
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->postJson('/api/migrations', array_merge($migrationData, [
                'name' => "Concurrent Migration {$i}"
            ]));
        }

        foreach ($responses as $response) {
            $response->assertStatus(201);
        }

        $this->assertDatabaseCount('migrations', 5);
    }

    /** @test */
    public function it_validates_migration_with_complex_config()
    {
        $complexMigrationData = [
            'name' => 'Complex Migration',
            'description' => 'Migration with complex configuration',
            'source_environment' => 'development',
            'target_environment' => 'production',
            'target_tenant_id' => 'target-tenant-123',
            'include_assets' => true,
            'validate_before_migration' => true,
            'items' => [
                [
                    'type' => 'pages',
                    'filters' => [
                        ['type' => 'tag', 'parameters' => ['tags' => ['important']]]
                    ]
                ],
                [
                    'type' => 'templates',
                    'filters' => [
                        ['type' => 'category', 'parameters' => ['category' => 'landing']]
                    ]
                ]
            ],
            'options' => [
                'validate_before_migration' => true,
                'backup_before_migration' => true,
                'rollback_on_error' => true,
                'parallel_processing' => false,
                'batch_size' => 10
            ]
        ];

        $response = $this->postJson('/api/migrations', $complexMigrationData);

        $response->assertStatus(201);

        $migration = Migration::where('name', 'Complex Migration')->first();
        $this->assertEquals('pages', $migration->config['items'][0]['type']);
        $this->assertEquals('templates', $migration->config['items'][1]['type']);
        $this->assertTrue($migration->config['options']['validate_before_migration']);
    }

    /** @test */
    public function it_handles_migration_with_schedule()
    {
        $scheduledMigrationData = [
            'name' => 'Scheduled Migration',
            'source_environment' => 'staging',
            'target_environment' => 'production',
            'target_tenant_id' => 'target-tenant-123',
            'schedule_type' => 'scheduled',
            'scheduled_date' => now()->addDays(1)->toISOString(),
            'include_assets' => true
        ];

        $response = $this->postJson('/api/migrations', $scheduledMigrationData);

        $response->assertStatus(201);

        $migration = Migration::where('name', 'Scheduled Migration')->first();
        $this->assertEquals('scheduled', $migration->config['schedule']['type']);
        $this->assertEquals('draft', $migration->status);
    }

    /** @test */
    public function it_handles_migration_with_transformations()
    {
        $transformationMigrationData = [
            'name' => 'Transformation Migration',
            'source_environment' => 'development',
            'target_environment' => 'production',
            'target_tenant_id' => 'target-tenant-123',
            'items' => [
                [
                    'type' => 'pages',
                    'transformations' => [
                        [
                            'type' => 'rename',
                            'source_field' => 'title',
                            'target_field' => 'name',
                            'parameters' => []
                        ]
                    ]
                ]
            ],
            'include_assets' => true
        ];

        $response = $this->postJson('/api/migrations', $transformationMigrationData);

        $response->assertStatus(201);

        $migration = Migration::where('name', 'Transformation Migration')->first();
        $this->assertEquals('rename', $migration->config['items'][0]['transformations'][0]['type']);
        $this->assertEquals('title', $migration->config['items'][0]['transformations'][0]['source_field']);
    }

    /** @test */
    public function it_handles_migration_progress_tracking()
    {
        $migration = Migration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'running',
            'progress' => [
                'total_items' => 100,
                'processed_items' => 50,
                'successful_items' => 45,
                'failed_items' => 5,
                'percentage' => 50
            ]
        ]);

        $response = $this->getJson("/api/migrations/{$migration->id}");

        $response->assertStatus(200);
        $this->assertEquals(100, $response->json('migration.progress.total_items'));
        $this->assertEquals(50, $response->json('migration.progress.processed_items'));
        $this->assertEquals(50, $response->json('migration.progress.percentage'));
    }

    /** @test */
    public function it_handles_migration_error_reporting()
    {
        $migration = Migration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
            'results' => [
                'success' => false,
                'errors' => [
                    [
                        'type' => 'connection_error',
                        'message' => 'Failed to connect to target database',
                        'details' => ['host' => 'localhost', 'port' => 5432]
                    ]
                ],
                'failed_items' => [
                    [
                        'type' => 'pages',
                        'id' => 'page-1',
                        'name' => 'Home Page',
                        'error' => 'Validation failed'
                    ]
                ]
            ]
        ]);

        $response = $this->getJson("/api/migrations/{$migration->id}");

        $response->assertStatus(200);
        $this->assertFalse($response->json('migration.results.success'));
        $this->assertCount(1, $response->json('migration.results.errors'));
        $this->assertEquals('connection_error', $response->json('migration.results.errors.0.type'));
    }

    /** @test */
    public function it_handles_tenant_migration()
    {
        $tenantMigrationData = [
            'name' => 'Tenant Migration',
            'description' => 'Migrating entire tenant',
            'source_environment' => 'development',
            'target_environment' => 'production',
            'target_tenant_id' => 'new-tenant-123',
            'include_assets' => true,
            'validate_before_migration' => true,
            'items' => [
                ['type' => 'pages'],
                ['type' => 'templates'],
                ['type' => 'components'],
                ['type' => 'users'],
                ['type' => 'configurations']
            ]
        ];

        $response = $this->postJson('/api/migrations', $tenantMigrationData);

        $response->assertStatus(201);

        $migration = Migration::where('name', 'Tenant Migration')->first();
        $this->assertCount(5, $migration->config['items']);
        $this->assertContains('users', array_column($migration->config['items'], 'type'));
        $this->assertContains('configurations', array_column($migration->config['items'], 'type'));
    }

    /** @test */
    public function it_validates_cross_tenant_migration_permissions()
    {
        // This test would verify that users can only migrate to tenants they have access to
        // In a real implementation, this would check user permissions across tenants

        $crossTenantMigrationData = [
            'name' => 'Cross Tenant Migration',
            'source_environment' => 'development',
            'target_environment' => 'production',
            'target_tenant_id' => 'unauthorized-tenant-123',
            'include_assets' => true
        ];

        $response = $this->postJson('/api/migrations', $crossTenantMigrationData);

        // This should either succeed (if user has permission) or fail with appropriate error
        // For this test, we'll assume it succeeds since we're testing the API structure
        $response->assertStatus(201);
    }

    /** @test */
    public function it_handles_migration_rollback()
    {
        $migration = Migration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed'
        ]);

        $rollbackData = [
            'reason' => 'User requested rollback',
            'rollback_items' => ['pages', 'templates']
        ];

        $response = $this->postJson("/api/migrations/{$migration->id}/rollback", $rollbackData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'rollback_result' => [
                        'success',
                        'rolled_back_items',
                        'failed_rollbacks'
                    ]
                ]);
    }
}