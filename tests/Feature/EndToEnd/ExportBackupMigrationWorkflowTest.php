<?php

namespace Tests\Feature\EndToEnd;

use App\Models\Backup;
use App\Models\Export;
use App\Models\Migration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportBackupMigrationWorkflowTest extends TestCase
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
    public function complete_export_workflow_from_ui_to_database()
    {
        // Step 1: Create export via API
        $exportData = [
            'format' => 'json',
            'include_assets' => true,
            'compress' => false,
            'encrypt' => false,
            'name' => 'E2E Test Export',
            'description' => 'End-to-end export test'
        ];

        $response = $this->postJson('/api/exports', $exportData);
        $response->assertStatus(201);

        $exportId = $response->json('export.id');

        // Step 2: Verify export was created in database
        $this->assertDatabaseHas('exports', [
            'id' => $exportId,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'format' => 'json',
            'status' => 'pending'
        ]);

        // Step 3: Retrieve the export
        $response = $this->getJson("/api/exports/{$exportId}");
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'export' => [
                        'id',
                        'format',
                        'status',
                        'file_name',
                        'created_at'
                    ]
                ]);

        // Step 4: List exports to verify it's included
        $response = $this->getJson('/api/exports');
        $response->assertStatus(200);

        $exportIds = array_column($response->json('exports'), 'id');
        $this->assertContains($exportId, $exportIds);

        // Step 5: Simulate export completion (in real scenario, this would be done by a job)
        $export = Export::find($exportId);
        $export->update([
            'status' => 'completed',
            'file_size' => 1024,
            'file_path' => 'exports/test-export.json'
        ]);

        // Step 6: Verify completed export can be downloaded
        $response = $this->getJson("/api/exports/{$exportId}/download");
        $response->assertStatus(200);

        // Step 7: Delete the export
        $response = $this->deleteJson("/api/exports/{$exportId}");
        $response->assertStatus(200);

        // Step 8: Verify export was deleted
        $this->assertDatabaseMissing('exports', ['id' => $exportId]);
    }

    /** @test */
    public function complete_backup_workflow_from_ui_to_database()
    {
        Storage::fake('local');

        // Step 1: Create backup via API
        $backupData = [
            'name' => 'E2E Test Backup',
            'description' => 'End-to-end backup test',
            'include_assets' => true,
            'compress' => true,
            'retention_days' => 30
        ];

        $response = $this->postJson('/api/backups', $backupData);
        $response->assertStatus(201);

        $backupId = $response->json('backup.id');

        // Step 2: Verify backup was created in database
        $this->assertDatabaseHas('backups', [
            'id' => $backupId,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'name' => 'E2E Test Backup',
            'status' => 'pending'
        ]);

        // Step 3: Retrieve the backup
        $response = $this->getJson("/api/backups/{$backupId}");
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'backup' => [
                        'id',
                        'name',
                        'status',
                        'file_name',
                        'created_at'
                    ]
                ]);

        // Step 4: List backups to verify it's included
        $response = $this->getJson('/api/backups');
        $response->assertStatus(200);

        $backupIds = array_column($response->json('backups'), 'id');
        $this->assertContains($backupId, $backupIds);

        // Step 5: Simulate backup completion
        $filePath = 'backups/test-backup.zip';
        Storage::put($filePath, 'backup content');

        $backup = Backup::find($backupId);
        $backup->update([
            'status' => 'completed',
            'file_size' => 1024,
            'file_path' => $filePath,
            'file_name' => 'test-backup.zip'
        ]);

        // Step 6: Verify completed backup can be downloaded
        $response = $this->getJson("/api/backups/{$backupId}/download");
        $response->assertStatus(200);

        // Step 7: Test backup restoration
        $restoreData = [
            'overwrite' => true,
            'restore_assets' => true
        ];

        $response = $this->postJson("/api/backups/{$backupId}/restore", $restoreData);
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'restore_result' => [
                        'success',
                        'restored_items',
                        'skipped_items',
                        'errors'
                    ]
                ]);

        // Step 8: Delete the backup
        $response = $this->deleteJson("/api/backups/{$backupId}");
        $response->assertStatus(200);

        // Step 9: Verify backup was deleted and file was cleaned up
        $this->assertDatabaseMissing('backups', ['id' => $backupId]);
        Storage::assertMissing($filePath);
    }

    /** @test */
    public function complete_migration_workflow_from_ui_to_database()
    {
        // Step 1: Create migration via API
        $migrationData = [
            'name' => 'E2E Test Migration',
            'description' => 'End-to-end migration test',
            'source_environment' => 'development',
            'target_environment' => 'production',
            'target_tenant_id' => 'target-tenant-123',
            'include_assets' => true,
            'validate_before_migration' => true
        ];

        $response = $this->postJson('/api/migrations', $migrationData);
        $response->assertStatus(201);

        $migrationId = $response->json('migration.id');

        // Step 2: Verify migration was created in database
        $this->assertDatabaseHas('migrations', [
            'id' => $migrationId,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'draft'
        ]);

        // Step 3: Retrieve the migration
        $response = $this->getJson("/api/migrations/{$migrationId}");
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

        // Step 4: List migrations to verify it's included
        $response = $this->getJson('/api/migrations');
        $response->assertStatus(200);

        $migrationIds = array_column($response->json('migrations'), 'id');
        $this->assertContains($migrationId, $migrationIds);

        // Step 5: Execute the migration
        $response = $this->postJson("/api/migrations/{$migrationId}/execute");
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

        // Step 6: Verify migration status was updated
        $migration = Migration::find($migrationId);
        $this->assertNotEquals('draft', $migration->status);

        // Step 7: Cancel the migration (if still running)
        if ($migration->status === 'running') {
            $response = $this->postJson("/api/migrations/{$migrationId}/cancel");
            $response->assertStatus(200);
        }

        // Step 8: Delete the migration
        $response = $this->deleteJson("/api/migrations/{$migrationId}");
        $response->assertStatus(200);

        // Step 9: Verify migration was deleted
        $this->assertDatabaseMissing('migrations', ['id' => $migrationId]);
    }

    /** @test */
    public function cross_tenant_access_prevention_workflow()
    {
        // Create another tenant
        $otherTenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();

        // Create export in other tenant
        $otherTenantExport = Export::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id
        ]);

        // Try to access export from different tenant
        $response = $this->getJson("/api/exports/{$otherTenantExport->id}");
        $response->assertStatus(403);

        // Try to delete export from different tenant
        $response = $this->deleteJson("/api/exports/{$otherTenantExport->id}");
        $response->assertStatus(403);

        // Try to download export from different tenant
        $response = $this->getJson("/api/exports/{$otherTenantExport->id}/download");
        $response->assertStatus(403);

        // Similar tests for backups
        $otherTenantBackup = Backup::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/backups/{$otherTenantBackup->id}");
        $response->assertStatus(403);

        $response = $this->postJson("/api/backups/{$otherTenantBackup->id}/restore");
        $response->assertStatus(403);

        // Similar tests for migrations
        $otherTenantMigration = Migration::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/migrations/{$otherTenantMigration->id}");
        $response->assertStatus(403);

        $response = $this->postJson("/api/migrations/{$otherTenantMigration->id}/execute");
        $response->assertStatus(403);
    }

    /** @test */
    public function concurrent_operations_workflow()
    {
        // Create multiple exports simultaneously
        $exportPromises = [];
        for ($i = 0; $i < 5; $i++) {
            $exportPromises[] = $this->postJson('/api/exports', [
                'format' => 'json',
                'name' => "Concurrent Export {$i}",
                'include_assets' => true
            ]);
        }

        // Wait for all exports to complete
        $responses = [];
        foreach ($exportPromises as $promise) {
            $responses[] = $promise;
        }

        // Verify all exports were created successfully
        foreach ($responses as $response) {
            $response->assertStatus(201);
        }

        // Verify all exports exist in database
        $this->assertDatabaseCount('exports', 5);

        // Clean up
        $exports = Export::where('tenant_id', $this->tenant->id)->get();
        foreach ($exports as $export) {
            $this->deleteJson("/api/exports/{$export->id}");
        }
    }

    /** @test */
    public function error_handling_and_recovery_workflow()
    {
        // Test invalid export creation
        $response = $this->postJson('/api/exports', [
            'format' => 'invalid_format',
            'name' => ''
        ]);
        $response->assertStatus(422);

        // Test invalid backup creation
        $response = $this->postJson('/api/backups', [
            'name' => '',
            'retention_days' => 400 // Too many days
        ]);
        $response->assertStatus(422);

        // Test invalid migration creation
        $response = $this->postJson('/api/migrations', [
            'name' => '',
            'source_environment' => 'invalid_env'
        ]);
        $response->assertStatus(422);

        // Test accessing non-existent resources
        $response = $this->getJson('/api/exports/non-existent-id');
        $response->assertStatus(404);

        $response = $this->getJson('/api/backups/non-existent-id');
        $response->assertStatus(404);

        $response = $this->getJson('/api/migrations/non-existent-id');
        $response->assertStatus(404);
    }

    /** @test */
    public function large_dataset_handling_workflow()
    {
        Storage::fake('local');

        // Create a large number of exports
        $exports = Export::factory()->count(100)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        // Test pagination
        $response = $this->getJson('/api/exports?per_page=20');
        $response->assertStatus(200);
        $this->assertCount(20, $response->json('exports'));
        $this->assertEquals(100, $response->json('pagination.total'));

        // Test large file handling
        $largeFilePath = 'exports/large-export.zip';
        Storage::put($largeFilePath, str_repeat('x', 10 * 1024 * 1024)); // 10MB file

        $largeExport = Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'file_path' => $largeFilePath,
            'file_size' => 10 * 1024 * 1024
        ]);

        $response = $this->getJson("/api/exports/{$largeExport->id}/download");
        $response->assertStatus(200);

        // Clean up
        foreach ($exports as $export) {
            $this->deleteJson("/api/exports/{$export->id}");
        }
        $this->deleteJson("/api/exports/{$largeExport->id}");
    }

    /** @test */
    public function filtering_and_search_workflow()
    {
        // Create exports with different statuses and formats
        Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'format' => 'json'
        ]);

        Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'format' => 'html'
        ]);

        Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
            'format' => 'pdf'
        ]);

        // Test status filtering
        $response = $this->getJson('/api/exports?status=completed');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('exports'));

        // Test format filtering
        $response = $this->getJson('/api/exports?format=html');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('exports'));

        // Test date range filtering
        $startDate = now()->subDays(1)->toDateString();
        $endDate = now()->addDays(1)->toDateString();

        $response = $this->getJson("/api/exports?start_date={$startDate}&end_date={$endDate}");
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('exports'));
    }

    /** @test */
    public function authentication_and_authorization_workflow()
    {
        // Test unauthenticated access
        $this->withoutMiddleware();

        $response = $this->getJson('/api/exports');
        $response->assertStatus(401);

        $response = $this->postJson('/api/exports', []);
        $response->assertStatus(401);

        $response = $this->getJson('/api/backups');
        $response->assertStatus(401);

        $response = $this->getJson('/api/migrations');
        $response->assertStatus(401);
    }

    /** @test */
    public function data_integrity_and_validation_workflow()
    {
        // Test export data integrity
        $exportData = [
            'format' => 'json',
            'include_assets' => true,
            'name' => 'Integrity Test Export'
        ];

        $response = $this->postJson('/api/exports', $exportData);
        $response->assertStatus(201);

        $exportId = $response->json('export.id');
        $export = Export::find($exportId);

        $this->assertEquals($this->tenant->id, $export->tenant_id);
        $this->assertEquals($this->user->id, $export->user_id);
        $this->assertEquals('json', $export->format);
        $this->assertEquals('pending', $export->status);

        // Test backup data integrity
        $backupData = [
            'name' => 'Integrity Test Backup',
            'include_assets' => true,
            'compress' => true
        ];

        $response = $this->postJson('/api/backups', $backupData);
        $response->assertStatus(201);

        $backupId = $response->json('backup.id');
        $backup = Backup::find($backupId);

        $this->assertEquals($this->tenant->id, $backup->tenant_id);
        $this->assertEquals($this->user->id, $backup->user_id);
        $this->assertEquals('Integrity Test Backup', $backup->name);

        // Test migration data integrity
        $migrationData = [
            'name' => 'Integrity Test Migration',
            'source_environment' => 'development',
            'target_environment' => 'production',
            'target_tenant_id' => 'target-tenant-123'
        ];

        $response = $this->postJson('/api/migrations', $migrationData);
        $response->assertStatus(201);

        $migrationId = $response->json('migration.id');
        $migration = Migration::find($migrationId);

        $this->assertEquals($this->tenant->id, $migration->tenant_id);
        $this->assertEquals($this->user->id, $migration->user_id);
        $this->assertEquals('Integrity Test Migration', $migration->config['name']);
        $this->assertEquals('draft', $migration->status);

        // Clean up
        $this->deleteJson("/api/exports/{$exportId}");
        $this->deleteJson("/api/backups/{$backupId}");
        $this->deleteJson("/api/migrations/{$migrationId}");
    }
}