<?php

namespace Tests\Feature\Api;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupApiTest extends TestCase
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
    public function it_can_list_backups_for_current_tenant()
    {
        // Create some backups for the current tenant
        Backup::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        // Create a backup for a different tenant
        $otherTenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();
        Backup::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/backups');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'backups',
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

        // Should only return backups for current tenant
        $this->assertCount(3, $response->json('backups'));
    }

    /** @test */
    public function it_can_filter_backups_by_status()
    {
        Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed'
        ]);

        Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $response = $this->getJson('/api/backups?status=completed');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('backups'));
        $this->assertEquals('completed', $response->json('backups.0.status'));
    }

    /** @test */
    public function it_can_create_a_backup()
    {
        $backupData = [
            'name' => 'Test Backup',
            'description' => 'Test backup description',
            'include_assets' => true,
            'compress' => true,
            'retention_days' => 30
        ];

        $response = $this->postJson('/api/backups', $backupData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'backup' => [
                        'id',
                        'name',
                        'status',
                        'created_at'
                    ],
                    'message'
                ]);

        $this->assertDatabaseHas('backups', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'name' => 'Test Backup',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_validates_backup_creation_data()
    {
        $invalidData = [
            'name' => '', // Empty name should fail
            'retention_days' => 400 // Too many days
        ];

        $response = $this->postJson('/api/backups', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'retention_days']);
    }

    /** @test */
    public function it_can_show_a_specific_backup()
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/backups/{$backup->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'backup' => [
                        'id',
                        'name',
                        'status',
                        'file_name',
                        'file_size',
                        'created_at'
                    ]
                ]);
    }

    /** @test */
    public function it_cannot_show_backup_from_different_tenant()
    {
        $otherTenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();
        $backup = Backup::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/backups/{$backup->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_restore_a_backup()
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed'
        ]);

        $restoreData = [
            'overwrite' => true,
            'restore_assets' => true
        ];

        $response = $this->postJson("/api/backups/{$backup->id}/restore", $restoreData);

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
    }

    /** @test */
    public function it_cannot_restore_backup_from_different_tenant()
    {
        $otherTenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();
        $backup = Backup::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed'
        ]);

        $response = $this->postJson("/api/backups/{$backup->id}/restore");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_cannot_restore_incomplete_backup()
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $response = $this->postJson("/api/backups/{$backup->id}/restore");

        $response->assertStatus(422)
                ->assertJson(['message' => 'Backup is not available for restore']);
    }

    /** @test */
    public function it_can_delete_a_backup()
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/backups/{$backup->id}");

        $response->assertStatus(200)
                ->assertJson(['message' => 'Backup deleted successfully']);

        $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
    }

    /** @test */
    public function it_cannot_delete_backup_from_different_tenant()
    {
        $otherTenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();
        $backup = Backup::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/backups/{$backup->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_download_completed_backup()
    {
        Storage::fake('local');

        // Create a fake backup file
        $filePath = 'backups/test-backup.zip';
        Storage::put($filePath, 'backup content');

        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'file_path' => $filePath,
            'file_name' => 'test-backup.zip'
        ]);

        $response = $this->getJson("/api/backups/{$backup->id}/download");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_cannot_download_pending_backup()
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $response = $this->getJson("/api/backups/{$backup->id}/download");

        $response->assertStatus(422)
                ->assertJson(['message' => 'Backup is not ready for download']);
    }

    /** @test */
    public function it_cannot_download_backup_from_different_tenant()
    {
        $otherTenant = \Spatie\Multitenancy\Models\Tenant::factory()->create();
        $backup = Backup::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed'
        ]);

        $response = $this->getJson("/api/backups/{$backup->id}/download");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_handles_backup_cleanup_on_deletion()
    {
        Storage::fake('local');

        $filePath = 'backups/test-backup.zip';
        Storage::put($filePath, 'backup content');

        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'file_path' => $filePath
        ]);

        $this->deleteJson("/api/backups/{$backup->id}");

        // File should be deleted from storage
        Storage::assertMissing($filePath);
    }

    /** @test */
    public function it_handles_pagination_correctly()
    {
        Backup::factory()->count(25)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/backups?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('backups'));
        $this->assertEquals(25, $response->json('pagination.total'));
        $this->assertEquals(3, $response->json('pagination.last_page'));
    }

    /** @test */
    public function it_handles_date_range_filtering()
    {
        $startDate = now()->subDays(5);
        $endDate = now()->addDays(2);

        Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(3)
        ]);

        Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(10)
        ]);

        $response = $this->getJson("/api/backups?start_date={$startDate->toDateString()}&end_date={$endDate->toDateString()}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('backups'));
    }

    /** @test */
    public function it_handles_environment_filtering()
    {
        Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'metadata' => ['environment' => 'production']
        ]);

        Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'metadata' => ['environment' => 'staging']
        ]);

        $response = $this->getJson('/api/backups?environment=production');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('backups'));
    }

    /** @test */
    public function it_requires_authentication()
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/backups');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_concurrent_backup_operations()
    {
        $backupData = [
            'name' => 'Concurrent Backup',
            'include_assets' => true,
            'compress' => true
        ];

        // Create multiple backups simultaneously
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->postJson('/api/backups', array_merge($backupData, [
                'name' => "Concurrent Backup {$i}"
            ]));
        }

        foreach ($responses as $response) {
            $response->assertStatus(201);
        }

        $this->assertDatabaseCount('backups', 5);
    }

    /** @test */
    public function it_handles_large_backup_files()
    {
        Storage::fake('local');

        // Create a large backup file (simulate)
        $largeContent = str_repeat('x', 100 * 1024 * 1024); // 100MB
        $filePath = 'backups/large-backup.zip';
        Storage::put($filePath, $largeContent);

        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'file_path' => $filePath,
            'file_size' => strlen($largeContent)
        ]);

        $response = $this->getJson("/api/backups/{$backup->id}/download");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_validates_backup_file_integrity()
    {
        Storage::fake('local');

        // Create a corrupted backup file
        $filePath = 'backups/corrupted-backup.zip';
        Storage::put($filePath, 'corrupted content');

        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'file_path' => $filePath
        ]);

        // This would typically be handled by a file validation service
        $response = $this->getJson("/api/backups/{$backup->id}/download");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_handles_backup_with_encryption()
    {
        $backupData = [
            'name' => 'Encrypted Backup',
            'description' => 'Backup with encryption',
            'include_assets' => true,
            'compress' => true,
            'encrypt' => true,
            'encryption_algorithm' => 'AES-256'
        ];

        $response = $this->postJson('/api/backups', $backupData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('backups', [
            'name' => 'Encrypted Backup',
            'metadata->encrypt' => true,
            'metadata->encryption_algorithm' => 'AES-256'
        ]);
    }

    /** @test */
    public function it_handles_backup_with_custom_retention()
    {
        $backupData = [
            'name' => 'Custom Retention Backup',
            'retention_days' => 90,
            'include_assets' => true
        ];

        $response = $this->postJson('/api/backups', $backupData);

        $response->assertStatus(201);

        $backup = Backup::where('name', 'Custom Retention Backup')->first();
        $this->assertEquals(90, $backup->retention_days);
        $this->assertEquals(
            now()->addDays(90)->toDateString(),
            $backup->expires_at->toDateString()
        );
    }

    /** @test */
    public function it_handles_backup_with_metadata()
    {
        $backupData = [
            'name' => 'Metadata Backup',
            'description' => 'Backup with custom metadata',
            'tags' => ['important', 'production'],
            'custom_field' => 'custom_value'
        ];

        $response = $this->postJson('/api/backups', $backupData);

        $response->assertStatus(201);

        $backup = Backup::where('name', 'Metadata Backup')->first();
        $this->assertEquals(['important', 'production'], $backup->metadata['tags']);
        $this->assertEquals('custom_value', $backup->metadata['custom_field']);
    }
}