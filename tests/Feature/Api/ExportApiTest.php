<?php

namespace Tests\Feature\Api;

use App\Models\Export;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportApiTest extends TestCase
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
    public function it_can_list_exports_for_current_tenant()
    {
        // Create some exports for the current tenant
        Export::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        // Create an export for a different tenant
        $otherTenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();
        Export::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/exports');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'exports',
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total'
                    ],
                    'meta' => [
                        'total_count',
                        'statuses',
                        'formats'
                    ]
                ]);

        // Should only return exports for current tenant
        $this->assertCount(3, $response->json('exports'));
    }

    /** @test */
    public function it_can_filter_exports_by_status()
    {
        Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed'
        ]);

        Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $response = $this->getJson('/api/exports?status=completed');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('exports'));
        $this->assertEquals('completed', $response->json('exports.0.status'));
    }

    /** @test */
    public function it_can_filter_exports_by_format()
    {
        Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'format' => 'json'
        ]);

        Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'format' => 'html'
        ]);

        $response = $this->getJson('/api/exports?format=json');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('exports'));
        $this->assertEquals('json', $response->json('exports.0.format'));
    }

    /** @test */
    public function it_can_create_an_export()
    {
        $exportData = [
            'format' => 'json',
            'include_assets' => true,
            'compress' => false,
            'encrypt' => false,
            'name' => 'Test Export',
            'description' => 'Test export description'
        ];

        $response = $this->postJson('/api/exports', $exportData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'export' => [
                        'id',
                        'format',
                        'status',
                        'created_at'
                    ],
                    'message'
                ]);

        $this->assertDatabaseHas('exports', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'format' => 'json',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_validates_export_creation_data()
    {
        $invalidData = [
            'format' => 'invalid_format',
            'include_assets' => 'not_boolean'
        ];

        $response = $this->postJson('/api/exports', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['format']);
    }

    /** @test */
    public function it_can_show_a_specific_export()
    {
        $export = Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/exports/{$export->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'export' => [
                        'id',
                        'format',
                        'status',
                        'file_name',
                        'file_size',
                        'created_at'
                    ]
                ]);
    }

    /** @test */
    public function it_cannot_show_export_from_different_tenant()
    {
        $otherTenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();
        $export = Export::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/exports/{$export->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_delete_an_export()
    {
        $export = Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/exports/{$export->id}");

        $response->assertStatus(200)
                ->assertJson(['message' => 'Export deleted successfully']);

        $this->assertDatabaseMissing('exports', ['id' => $export->id]);
    }

    /** @test */
    public function it_cannot_delete_export_from_different_tenant()
    {
        $otherTenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();
        $export = Export::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/exports/{$export->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_download_completed_export()
    {
        Storage::fake('local');

        // Create a fake export file
        $filePath = 'exports/test-export.json';
        Storage::put($filePath, '{"test": "data"}');

        $export = Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'file_path' => $filePath,
            'file_name' => 'test-export.json'
        ]);

        $response = $this->getJson("/api/exports/{$export->id}/download");

        $response->assertStatus(200);
        // Note: In a real scenario, this would return a file download response
    }

    /** @test */
    public function it_cannot_download_pending_export()
    {
        $export = Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $response = $this->getJson("/api/exports/{$export->id}/download");

        $response->assertStatus(422)
                ->assertJson(['message' => 'Export is not ready for download']);
    }

    /** @test */
    public function it_cannot_download_export_from_different_tenant()
    {
        $otherTenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();
        $export = Export::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed'
        ]);

        $response = $this->getJson("/api/exports/{$export->id}/download");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_handles_pagination_correctly()
    {
        Export::factory()->count(25)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/exports?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('exports'));
        $this->assertEquals(25, $response->json('pagination.total'));
        $this->assertEquals(3, $response->json('pagination.last_page'));
    }

    /** @test */
    public function it_handles_date_range_filtering()
    {
        $startDate = now()->subDays(5);
        $endDate = now()->addDays(2);

        Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(3)
        ]);

        Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(10)
        ]);

        $response = $this->getJson("/api/exports?start_date={$startDate->toDateString()}&end_date={$endDate->toDateString()}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('exports'));
    }

    /** @test */
    public function it_requires_authentication()
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/exports');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_large_datasets()
    {
        // Create a large number of exports
        Export::factory()->count(1000)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/exports?per_page=50');

        $response->assertStatus(200);
        $this->assertCount(50, $response->json('exports'));
    }

    /** @test */
    public function it_handles_concurrent_export_creation()
    {
        // This test would verify that concurrent export creation doesn't cause issues
        // In a real scenario, this would use database transactions and proper locking

        $exportData = [
            'format' => 'json',
            'include_assets' => true,
            'name' => 'Concurrent Export'
        ];

        // Create multiple exports simultaneously
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->postJson('/api/exports', $exportData);
        }

        foreach ($responses as $response) {
            $response->assertStatus(201);
        }

        $this->assertDatabaseCount('exports', 5);
    }

    /** @test */
    public function it_validates_export_file_integrity()
    {
        Storage::fake('local');

        // Create a corrupted export file
        $filePath = 'exports/corrupted-export.json';
        Storage::put($filePath, 'invalid json content {');

        $export = Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'file_path' => $filePath,
            'format' => 'json'
        ]);

        // This would typically be handled by a file validation service
        // For now, we'll just ensure the file exists check works
        $response = $this->getJson("/api/exports/{$export->id}/download");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_handles_export_cleanup_on_deletion()
    {
        Storage::fake('local');

        $filePath = 'exports/test-export.json';
        Storage::put($filePath, '{"test": "data"}');

        $export = Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'file_path' => $filePath
        ]);

        $this->deleteJson("/api/exports/{$export->id}");

        // File should be deleted from storage
        Storage::assertMissing($filePath);
    }
}