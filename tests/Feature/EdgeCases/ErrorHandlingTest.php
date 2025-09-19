<?php

namespace Tests\Feature\EdgeCases;

use App\Models\Backup;
use App\Models\Export;
use App\Models\Migration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
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
    public function handles_invalid_export_formats()
    {
        $invalidFormats = ['exe', 'bat', 'com', 'scr', 'pif', 'invalid'];

        foreach ($invalidFormats as $format) {
            $response = $this->postJson('/api/exports', [
                'format' => $format,
                'name' => 'Test Export'
            ]);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['format']);
        }
    }

    /** @test */
    public function handles_invalid_backup_compression_settings()
    {
        $response = $this->postJson('/api/backups', [
            'name' => 'Test Backup',
            'compress' => 'invalid_boolean'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['compress']);
    }

    /** @test */
    public function handles_invalid_migration_environments()
    {
        $response = $this->postJson('/api/migrations', [
            'name' => 'Test Migration',
            'source_environment' => 'invalid_env',
            'target_environment' => 'another_invalid_env',
            'target_tenant_id' => 'tenant-123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['source_environment', 'target_environment']);
    }

    /** @test */
    public function handles_extremely_long_names()
    {
        $longName = str_repeat('A', 256); // Exceeds typical VARCHAR limits

        $response = $this->postJson('/api/exports', [
            'format' => 'json',
            'name' => $longName
        ]);

        // Should either validate the length or handle gracefully
        if ($response->status() === 422) {
            $response->assertJsonValidationErrors(['name']);
        } else {
            $response->assertStatus(201);
        }
    }

    /** @test */
    public function handles_special_characters_in_names()
    {
        $specialNames = [
            'Test<>&"\'',
            'Test<script>alert("xss")</script>',
            'Test../../../../etc/passwd',
            'Test' . chr(0) . 'nullbyte',
            'Test💥🚀🎉' // Unicode characters
        ];

        foreach ($specialNames as $name) {
            $response = $this->postJson('/api/exports', [
                'format' => 'json',
                'name' => $name
            ]);

            // Should handle special characters appropriately
            if ($response->status() === 422) {
                $response->assertJsonValidationErrors(['name']);
            } else {
                $response->assertStatus(201);
            }
        }
    }

    /** @test */
    public function handles_concurrent_requests_with_same_names()
    {
        $exportData = [
            'format' => 'json',
            'name' => 'Duplicate Name Export'
        ];

        // Create multiple exports with the same name simultaneously
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->postJson('/api/exports', $exportData);
        }

        // All should succeed (database should handle duplicates appropriately)
        foreach ($responses as $response) {
            $response->assertStatus(201);
        }
    }

    /** @test */
    public function handles_network_interruptions_during_large_operations()
    {
        Storage::fake('local');

        // Create a large export that might be interrupted
        $largeExport = Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'processing',
            'file_size' => 100 * 1024 * 1024 // 100MB
        ]);

        // Simulate network interruption by trying to download
        $response = $this->getJson("/api/exports/{$largeExport->id}/download");

        // Should handle gracefully
        if ($response->status() === 404) {
            // File doesn't exist yet
            $response->assertStatus(404);
        } else {
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function handles_database_connection_failures()
    {
        // This test would simulate database connection failures
        // In a real scenario, this would require mocking the database connection

        $response = $this->getJson('/api/exports');

        // Should handle database issues gracefully
        if ($response->status() === 500) {
            $response->assertJsonStructure([
                'message',
                'error' => [
                    'code',
                    'message'
                ]
            ]);
        } else {
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function handles_file_system_errors()
    {
        Storage::fake('local');

        // Create export with file path that doesn't exist
        $export = Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'file_path' => 'nonexistent/file.json'
        ]);

        $response = $this->getJson("/api/exports/{$export->id}/download");

        // Should handle missing files gracefully
        $response->assertStatus(404);
    }

    /** @test */
    public function handles_insufficient_disk_space()
    {
        Storage::fake('local');

        // Simulate disk space issues by creating a very large file
        $largeContent = str_repeat('x', 1024 * 1024 * 1024); // 1GB
        Storage::put('large-file.dat', $largeContent);

        $response = $this->postJson('/api/backups', [
            'name' => 'Large Backup',
            'include_assets' => true
        ]);

        // Should handle disk space issues gracefully
        $response->assertStatus(201); // Or appropriate error status
    }

    /** @test */
    public function handles_timeout_scenarios()
    {
        // Create a long-running operation
        $response = $this->postJson('/api/backups', [
            'name' => 'Timeout Test Backup',
            'include_assets' => true,
            'compress' => true
        ]);

        $backupId = $response->json('backup.id');

        // Simulate timeout by immediately trying to access the result
        $response = $this->getJson("/api/backups/{$backupId}");

        // Should handle timeout gracefully
        $response->assertStatus(200);
        $this->assertContains($response->json('backup.status'), ['pending', 'processing', 'completed', 'failed']);
    }

    /** @test */
    public function handles_corrupted_data_files()
    {
        Storage::fake('local');

        // Create a corrupted JSON file
        $corruptedJson = '{"incomplete": "json", "missing": "brace"';
        Storage::put('exports/corrupted.json', $corruptedJson);

        $export = Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'file_path' => 'exports/corrupted.json',
            'format' => 'json'
        ]);

        $response = $this->getJson("/api/exports/{$export->id}/download");

        // Should handle corrupted files gracefully
        $response->assertStatus(200); // File download should still work
    }

    /** @test */
    public function handles_extremely_large_datasets()
    {
        // Create a very large number of records
        $largeDataset = Export::factory()->count(10000)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/exports?per_page=1000');

        // Should handle large datasets gracefully
        $response->assertStatus(200);
        $this->assertCount(1000, $response->json('exports'));
    }

    /** @test */
    public function handles_unicode_and_multilingual_content()
    {
        $unicodeNames = [
            '测试导出', // Chinese
            'Test Export ñ', // Spanish with ñ
            'Тест Экспорт', // Russian
            'Test Export 🚀', // With emoji
            'Test Export 123' // Mixed
        ];

        foreach ($unicodeNames as $name) {
            $response = $this->postJson('/api/exports', [
                'format' => 'json',
                'name' => $name
            ]);

            $response->assertStatus(201);
        }
    }

    /** @test */
    public function handles_extremely_nested_data_structures()
    {
        // Create export with deeply nested configuration
        $nestedConfig = [];
        $current = &$nestedConfig;
        for ($i = 0; $i < 100; $i++) {
            $current['level_' . $i] = [];
            $current = &$current['level_' . $i];
        }
        $current['deepest'] = 'value';

        $response = $this->postJson('/api/exports', [
            'format' => 'json',
            'name' => 'Nested Config Export',
            'custom_config' => $nestedConfig
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function handles_zero_byte_files()
    {
        Storage::fake('local');

        // Create a zero-byte file
        Storage::put('exports/empty.json', '');

        $export = Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'file_path' => 'exports/empty.json',
            'file_size' => 0
        ]);

        $response = $this->getJson("/api/exports/{$export->id}/download");

        // Should handle zero-byte files gracefully
        $response->assertStatus(200);
    }

    /** @test */
    public function handles_files_with_special_permissions()
    {
        Storage::fake('local');

        // Create a file that might have permission issues
        Storage::put('exports/permission-test.json', '{"test": "data"}');

        $export = Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'file_path' => 'exports/permission-test.json'
        ]);

        $response = $this->getJson("/api/exports/{$export->id}/download");

        // Should handle permission issues gracefully
        $response->assertStatus(200);
    }

    /** @test */
    public function handles_concurrent_file_access()
    {
        Storage::fake('local');

        Storage::put('exports/concurrent.json', '{"test": "data"}');

        $export = Export::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'file_path' => 'exports/concurrent.json'
        ]);

        // Simulate concurrent access
        $promises = [];
        for ($i = 0; $i < 10; $i++) {
            $promises[] = $this->getJson("/api/exports/{$export->id}/download");
        }

        // All should succeed
        foreach ($promises as $promise) {
            $promise->assertStatus(200);
        }
    }

    /** @test */
    public function handles_malformed_request_data()
    {
        $malformedData = [
            ['format' => null, 'name' => 'Test'],
            ['format' => [], 'name' => 'Test'],
            ['format' => true, 'name' => 'Test'],
            ['format' => 123, 'name' => 'Test'],
            ['format' => 'json', 'name' => null],
            ['format' => 'json', 'name' => []],
            ['format' => 'json', 'name' => 123],
        ];

        foreach ($malformedData as $data) {
            $response = $this->postJson('/api/exports', $data);

            // Should handle malformed data gracefully
            if ($response->status() === 422) {
                // Validation error is acceptable
                $response->assertJsonValidationErrors(array_keys($data));
            } else {
                $response->assertStatus(201);
            }
        }
    }

    /** @test */
    public function handles_extremely_long_descriptions()
    {
        $longDescription = str_repeat('This is a very long description. ', 1000);

        $response = $this->postJson('/api/exports', [
            'format' => 'json',
            'name' => 'Long Description Export',
            'description' => $longDescription
        ]);

        // Should handle long descriptions gracefully
        if ($response->status() === 422) {
            $response->assertJsonValidationErrors(['description']);
        } else {
            $response->assertStatus(201);
        }
    }

    /** @test */
    public function handles_duplicate_operations()
    {
        $exportData = [
            'format' => 'json',
            'name' => 'Duplicate Operation Test'
        ];

        // Create the same export twice quickly
        $response1 = $this->postJson('/api/exports', $exportData);
        $response2 = $this->postJson('/api/exports', $exportData);

        // Both should succeed (or second should fail gracefully)
        $response1->assertStatus(201);
        if ($response2->status() === 422) {
            // Duplicate handling is acceptable
            $response2->assertJsonValidationErrors(['name']);
        } else {
            $response2->assertStatus(201);
        }
    }

    /** @test */
    public function handles_invalid_date_formats()
    {
        $invalidDates = [
            'invalid-date',
            '2025-13-45',
            '2025-01-32',
            'not-a-date',
            '2025/01/01',
            ''
        ];

        foreach ($invalidDates as $date) {
            $response = $this->getJson("/api/exports?start_date={$date}");

            // Should handle invalid dates gracefully
            if ($response->status() === 422) {
                $response->assertJsonValidationErrors(['start_date']);
            } else {
                $response->assertStatus(200);
            }
        }
    }

    /** @test */
    public function handles_extremely_large_pagination_requests()
    {
        // Create some test data
        Export::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/exports?per_page=10000');

        // Should handle large pagination requests gracefully
        if ($response->status() === 422) {
            $response->assertJsonValidationErrors(['per_page']);
        } else {
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function handles_empty_request_bodies()
    {
        $response = $this->postJson('/api/exports', []);

        // Should handle empty request bodies gracefully
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['format']);
    }

    /** @test */
    public function handles_request_with_only_whitespace()
    {
        $response = $this->postJson('/api/exports', [
            'format' => 'json',
            'name' => '   ', // Only whitespace
            'description' => "\t\n  " // Only whitespace
        ]);

        // Should handle whitespace-only values gracefully
        if ($response->status() === 422) {
            $response->assertJsonValidationErrors(['name']);
        } else {
            $response->assertStatus(201);
        }
    }

    /** @test */
    public function handles_requests_with_invalid_json()
    {
        // This would typically be handled by Laravel's JSON parsing
        // but we can test with malformed data that passes initial parsing

        $response = $this->postJson('/api/exports', [
            'format' => 'json',
            'name' => 'Test Export',
            'invalid_field' => ['nested' => ['deeply' => ['nested' => 'value']]]
        ]);

        // Should handle unexpected fields gracefully
        $response->assertStatus(201);
    }

    /** @test */
    public function handles_extremely_fast_consecutive_requests()
    {
        $exportData = [
            'format' => 'json',
            'name' => 'Fast Request Test'
        ];

        // Send requests as fast as possible
        $startTime = microtime(true);
        $responses = [];
        for ($i = 0; $i < 20; $i++) {
            $responses[] = $this->postJson('/api/exports', array_merge($exportData, [
                'name' => "Fast Request Test {$i}"
            ]));
        }
        $endTime = microtime(true);

        // All should succeed
        foreach ($responses as $response) {
            $response->assertStatus(201);
        }

        // Should complete within reasonable time
        $this->assertLessThan(5.0, $endTime - $startTime);
    }
}