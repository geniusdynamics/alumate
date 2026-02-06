<?php

namespace Tests\Feature\Security;

use App\Models\Backup;
use App\Models\Export;
use App\Models\Migration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected $user1;
    protected $user2;
    protected $tenant1;
    protected $tenant2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();

        // Create test tenants
        $this->tenant1 = \Spatie\Multitenancy\Models\Tenant::factory()->create();
        $this->tenant2 = \Spatie\Multitenancy\Models\Tenant::factory()->create();
    }

    /** @test */
    public function user_cannot_access_exports_from_different_tenant()
    {
        // Set tenant 1 context and authenticate user 1
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        // Create export in tenant 1
        $export1 = Export::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        // Switch to tenant 2 context and authenticate user 2
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Try to access export from tenant 1
        $response = $this->getJson("/api/exports/{$export1->id}");
        $response->assertStatus(403);

        // Try to list exports (should not include tenant 1 exports)
        $response = $this->getJson('/api/exports');
        $response->assertStatus(200);

        $exportIds = array_column($response->json('exports'), 'id');
        $this->assertNotContains($export1->id, $exportIds);
    }

    /** @test */
    public function user_cannot_modify_exports_from_different_tenant()
    {
        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        $export1 = Export::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        // Switch to tenant 2 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Try to delete export from tenant 1
        $response = $this->deleteJson("/api/exports/{$export1->id}");
        $response->assertStatus(403);

        // Verify export still exists
        $this->assertDatabaseHas('exports', ['id' => $export1->id]);
    }

    /** @test */
    public function user_cannot_download_exports_from_different_tenant()
    {
        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        $export1 = Export::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'status' => 'completed',
            'file_path' => 'exports/test.json'
        ]);

        // Switch to tenant 2 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Try to download export from tenant 1
        $response = $this->getJson("/api/exports/{$export1->id}/download");
        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_access_backups_from_different_tenant()
    {
        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        $backup1 = Backup::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        // Switch to tenant 2 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Try to access backup from tenant 1
        $response = $this->getJson("/api/backups/{$backup1->id}");
        $response->assertStatus(403);

        // Try to restore backup from tenant 1
        $response = $this->postJson("/api/backups/{$backup1->id}/restore");
        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_modify_backups_from_different_tenant()
    {
        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        $backup1 = Backup::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        // Switch to tenant 2 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Try to delete backup from tenant 1
        $response = $this->deleteJson("/api/backups/{$backup1->id}");
        $response->assertStatus(403);

        // Verify backup still exists
        $this->assertDatabaseHas('backups', ['id' => $backup1->id]);
    }

    /** @test */
    public function user_cannot_download_backups_from_different_tenant()
    {
        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        $backup1 = Backup::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'status' => 'completed',
            'file_path' => 'backups/test.zip'
        ]);

        // Switch to tenant 2 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Try to download backup from tenant 1
        $response = $this->getJson("/api/backups/{$backup1->id}/download");
        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_access_migrations_from_different_tenant()
    {
        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        $migration1 = Migration::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        // Switch to tenant 2 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Try to access migration from tenant 1
        $response = $this->getJson("/api/migrations/{$migration1->id}");
        $response->assertStatus(403);

        // Try to execute migration from tenant 1
        $response = $this->postJson("/api/migrations/{$migration1->id}/execute");
        $response->assertStatus(403);

        // Try to cancel migration from tenant 1
        $response = $this->postJson("/api/migrations/{$migration1->id}/cancel");
        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_modify_migrations_from_different_tenant()
    {
        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        $migration1 = Migration::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        // Switch to tenant 2 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Try to delete migration from tenant 1
        $response = $this->deleteJson("/api/migrations/{$migration1->id}");
        $response->assertStatus(403);

        // Verify migration still exists
        $this->assertDatabaseHas('migrations', ['id' => $migration1->id]);
    }

    /** @test */
    public function tenant_isolation_in_list_operations()
    {
        // Create data in both tenants
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        $export1 = Export::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        $backup1 = Backup::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        $migration1 = Migration::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        $export2 = Export::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->user2->id
        ]);

        $backup2 = Backup::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->user2->id
        ]);

        $migration2 = Migration::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->user2->id
        ]);

        // Test exports list isolation
        $response = $this->getJson('/api/exports');
        $response->assertStatus(200);

        $exportIds = array_column($response->json('exports'), 'id');
        $this->assertContains($export2->id, $exportIds);
        $this->assertNotContains($export1->id, $exportIds);

        // Test backups list isolation
        $response = $this->getJson('/api/backups');
        $response->assertStatus(200);

        $backupIds = array_column($response->json('backups'), 'id');
        $this->assertContains($backup2->id, $backupIds);
        $this->assertNotContains($backup1->id, $backupIds);

        // Test migrations list isolation
        $response = $this->getJson('/api/migrations');
        $response->assertStatus(200);

        $migrationIds = array_column($response->json('migrations'), 'id');
        $this->assertContains($migration2->id, $migrationIds);
        $this->assertNotContains($migration1->id, $migrationIds);
    }

    /** @test */
    public function tenant_isolation_with_user_permissions()
    {
        // Create a user that belongs to both tenants (simulating admin scenario)
        $adminUser = User::factory()->create();

        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($adminUser);

        $export1 = Export::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $adminUser->id
        ]);

        // Switch to tenant 2 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($adminUser);

        // Even with the same user, tenant isolation should prevent access
        $response = $this->getJson("/api/exports/{$export1->id}");
        $response->assertStatus(403);

        // Create export in tenant 2
        $export2 = Export::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $adminUser->id
        ]);

        // Should be able to access export in current tenant
        $response = $this->getJson("/api/exports/{$export2->id}");
        $response->assertStatus(200);
    }

    /** @test */
    public function tenant_isolation_in_bulk_operations()
    {
        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        // Create multiple exports in tenant 1
        $exports1 = Export::factory()->count(5)->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        // Switch to tenant 2 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Create multiple exports in tenant 2
        $exports2 = Export::factory()->count(3)->create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->user2->id
        ]);

        // List should only show tenant 2 exports
        $response = $this->getJson('/api/exports');
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('exports'));

        $exportIds = array_column($response->json('exports'), 'id');
        foreach ($exports2 as $export) {
            $this->assertContains($export->id, $exportIds);
        }

        foreach ($exports1 as $export) {
            $this->assertNotContains($export->id, $exportIds);
        }
    }

    /** @test */
    public function tenant_isolation_in_filtering_operations()
    {
        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        // Create exports with different statuses in tenant 1
        Export::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'status' => 'completed'
        ]);

        Export::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'status' => 'pending'
        ]);

        // Switch to tenant 2 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Create export with same status in tenant 2
        Export::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->user2->id,
            'status' => 'completed'
        ]);

        // Filter by status should only return tenant 2 results
        $response = $this->getJson('/api/exports?status=completed');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('exports'));
    }

    /** @test */
    public function tenant_isolation_in_pagination()
    {
        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        // Create many exports in tenant 1
        Export::factory()->count(50)->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        // Switch to tenant 2 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Create fewer exports in tenant 2
        Export::factory()->count(5)->create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->user2->id
        ]);

        // Pagination should only consider tenant 2 data
        $response = $this->getJson('/api/exports?per_page=10');
        $response->assertStatus(200);
        $this->assertCount(5, $response->json('exports'));
        $this->assertEquals(5, $response->json('pagination.total'));
        $this->assertEquals(1, $response->json('pagination.last_page'));
    }

    /** @test */
    public function tenant_isolation_in_search_operations()
    {
        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        // Create export with specific name in tenant 1
        Export::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'name' => 'Unique Tenant 1 Export'
        ]);

        // Switch to tenant 2 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Create export with same name in tenant 2
        Export::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->user2->id,
            'name' => 'Unique Tenant 1 Export' // Same name, different tenant
        ]);

        // Search should only find tenant 2 results
        $response = $this->getJson('/api/exports?search=Unique');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('exports'));
        $this->assertEquals($this->tenant2->id, $response->json('exports.0.tenant_id'));
    }

    /** @test */
    public function tenant_isolation_in_statistics_and_analytics()
    {
        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        // Create various records in tenant 1
        Export::factory()->count(10)->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        Backup::factory()->count(8)->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        Migration::factory()->count(5)->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        // Switch to tenant 2 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Create different numbers in tenant 2
        Export::factory()->count(3)->create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->user2->id
        ]);

        Backup::factory()->count(2)->create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->user2->id
        ]);

        Migration::factory()->count(1)->create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->user2->id
        ]);

        // Statistics should only reflect tenant 2 data
        $response = $this->getJson('/api/exports');
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('exports'));
        $this->assertEquals(3, $response->json('meta.total_count'));

        $response = $this->getJson('/api/backups');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('backups'));

        $response = $this->getJson('/api/migrations');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('migrations'));
    }

    /** @test */
    public function tenant_isolation_with_invalid_tenant_ids()
    {
        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        // Try to access resources with invalid tenant IDs
        $response = $this->getJson('/api/exports?tenant_id=invalid-tenant-id');
        $response->assertStatus(200); // Should work but return no results

        $response = $this->getJson('/api/backups?tenant_id=non-existent-tenant');
        $response->assertStatus(200); // Should work but return no results

        $response = $this->getJson('/api/migrations?tenant_id=invalid-tenant-id');
        $response->assertStatus(200); // Should work but return no results
    }

    /** @test */
    public function tenant_isolation_in_concurrent_requests()
    {
        // This test simulates concurrent requests from different tenants
        // In a real scenario, this would use actual concurrent requests

        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        $export1 = Export::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        // Simulate concurrent request from tenant 2
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Should not be able to access tenant 1 export
        $response = $this->getJson("/api/exports/{$export1->id}");
        $response->assertStatus(403);

        // Create export in tenant 2
        $export2 = Export::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->user2->id
        ]);

        // Should be able to access tenant 2 export
        $response = $this->getJson("/api/exports/{$export2->id}");
        $response->assertStatus(200);
    }

    /** @test */
    public function tenant_isolation_with_deleted_tenants()
    {
        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        $export1 = Export::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        // Simulate tenant deletion by changing tenant context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Should not be able to access export from "deleted" tenant
        $response = $this->getJson("/api/exports/{$export1->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function tenant_isolation_in_error_responses()
    {
        // Set tenant 1 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant1);
        $this->actingAs($this->user1);

        $export1 = Export::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id
        ]);

        // Switch to tenant 2 context
        \Spatie\Multitenancy\Models\Tenant::current()::associate($this->tenant2);
        $this->actingAs($this->user2);

        // Try various operations that should fail due to tenant isolation
        $operations = [
            ['method' => 'GET', 'url' => "/api/exports/{$export1->id}"],
            ['method' => 'DELETE', 'url' => "/api/exports/{$export1->id}"],
            ['method' => 'GET', 'url' => "/api/exports/{$export1->id}/download"],
        ];

        foreach ($operations as $operation) {
            if ($operation['method'] === 'GET') {
                $response = $this->getJson($operation['url']);
            } elseif ($operation['method'] === 'DELETE') {
                $response = $this->deleteJson($operation['url']);
            }

            $response->assertStatus(403);
        }
    }
}