<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Models\Tenant;
use App\Services\Analytics\AnalyticsDataExportImportService;
use App\Services\TenantContextService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Unit tests for AnalyticsDataExportImportService
 *
 * @covers \App\Services\Analytics\AnalyticsDataExportImportService
 */
class AnalyticsDataExportImportServiceTest extends TestCase
{
    private AnalyticsDataExportImportService $service;
    private TenantContextService|MockInterface $tenantContext;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $this->tenantContext = Mockery::mock(TenantContextService::class);
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andReturn($this->tenant->id);
        $this->tenantContext->shouldReceive('getCurrentTenant')
            ->andReturn($this->tenant);

        // Mock Auth facade
        Auth::shouldReceive('id')
            ->andReturn(1);

        // Mock Storage facade
        Storage::shouldReceive('disk')
            ->andReturnSelf();
        Storage::shouldReceive('put')
            ->andReturn(true);
        Storage::shouldReceive('exists')
            ->andReturn(true);
        Storage::shouldReceive('size')
            ->andReturn(1000);
        Storage::shouldReceive('get')
            ->andReturn('{}');
        Storage::shouldReceive('copy')
            ->andReturn(true);
        Storage::shouldReceive('delete')
            ->andReturn(true);

        // Mock Cache facade
        Cache::shouldReceive('get')
            ->andReturn([]);
        Cache::shouldReceive('put')
            ->andReturn(true);
        Cache::shouldReceive('remember')
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->service = new AnalyticsDataExportImportService($this->tenantContext);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test exportData returns success response
     */
    public function test_export_data_returns_success_response(): void
    {
        $result = $this->service->exportData([
            'format' => 'json',
            'data_types' => ['events'],
            'timeframe' => '24h',
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('file_path', $result);
        $this->assertArrayHasKey('file_name', $result);
        $this->assertArrayHasKey('format', $result);
        $this->assertArrayHasKey('record_count', $result);
        $this->assertArrayHasKey('file_size', $result);
        $this->assertArrayHasKey('download_url', $result);
        $this->assertArrayHasKey('expires_at', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('exported_at', $result);
        $this->assertEquals('json', $result['format']);
    }

    /**
     * Test exportData with CSV format
     */
    public function test_export_data_with_csv_format(): void
    {
        $result = $this->service->exportData([
            'format' => 'csv',
            'data_types' => ['events'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringEndsWith('.csv', $result['file_name']);
    }

    /**
     * Test exportData with Excel format
     */
    public function test_export_data_with_excel_format(): void
    {
        $result = $this->service->exportData([
            'format' => 'excel',
            'data_types' => ['events'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringEndsWith('.xlsx', $result['file_name']);
    }

    /**
     * Test exportData throws exception for unsupported format
     */
    public function test_export_data_throws_exception_for_unsupported_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported export format');

        $this->service->exportData([
            'format' => 'unsupported_format',
            'data_types' => ['events'],
        ]);
    }

    /**
     * Test exportToCSV returns correct structure
     */
    public function test_export_to_csv_returns_correct_structure(): void
    {
        $data = [
            'events' => [
                [
                    'event_name' => 'page_view',
                    'event_timestamp' => '2024-01-01T00:00:00Z',
                    'event_type' => 'page_view',
                    'user_id' => 'user_123',
                ],
            ],
        ];

        $result = $this->service->exportToCSV($data);

        $this->assertArrayHasKey('file_path', $result);
        $this->assertArrayHasKey('file_name', $result);
        $this->assertArrayHasKey('record_count', $result);
        $this->assertArrayHasKey('file_size', $result);
        $this->assertEquals(1, $result['record_count']);
        $this->assertStringEndsWith('.csv', $result['file_name']);
    }

    /**
     * Test exportToJSON returns correct structure
     */
    public function test_export_to_json_returns_correct_structure(): void
    {
        $data = [
            'events' => [
                [
                    'event_name' => 'page_view',
                    'event_timestamp' => '2024-01-01T00:00:00Z',
                    'event_type' => 'page_view',
                ],
            ],
        ];

        $result = $this->service->exportToJSON($data);

        $this->assertArrayHasKey('file_path', $result);
        $this->assertArrayHasKey('file_name', $result);
        $this->assertArrayHasKey('record_count', $result);
        $this->assertArrayHasKey('file_size', $result);
        $this->assertStringEndsWith('.json', $result['file_name']);
        $this->assertEquals(1, $result['record_count']);
    }

    /**
     * Test exportToExcel returns correct structure
     */
    public function test_export_to_excel_returns_correct_structure(): void
    {
        $data = [
            'events' => [
                [
                    'event_name' => 'page_view',
                    'event_timestamp' => '2024-01-01T00:00:00Z',
                    'event_type' => 'page_view',
                ],
            ],
        ];

        $result = $this->service->exportToExcel($data);

        $this->assertArrayHasKey('file_path', $result);
        $this->assertArrayHasKey('file_name', $result);
        $this->assertArrayHasKey('record_count', $result);
        $this->assertArrayHasKey('file_size', $result);
        $this->assertStringEndsWith('.xlsx', $result['file_name']);
    }

    /**
     * Test validateImportData with valid data
     */
    public function test_validate_import_data_with_valid_data(): void
    {
        $data = [
            [
                'event_name' => 'page_view',
                'event_timestamp' => '2024-01-01T00:00:00Z',
                'event_type' => 'page_view',
            ],
        ];

        $result = $this->service->validateImportData($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertEquals(1, $result['total_records']);
        $this->assertEquals(1, $result['valid_records']);
        $this->assertEquals(0, $result['invalid_records']);
    }

    /**
     * Test validateImportData detects missing required fields
     */
    public function test_validate_import_data_detects_missing_required_fields(): void
    {
        $data = [
            [
                'event_name' => 'page_view',
                // Missing event_timestamp and event_type
            ],
        ];

        $result = $this->service->validateImportData($data);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Missing required field', $result['errors'][0]);
    }

    /**
     * Test validateImportData detects invalid timestamp format
     */
    public function test_validate_import_data_detects_invalid_timestamp(): void
    {
        $data = [
            [
                'event_name' => 'page_view',
                'event_timestamp' => 'invalid-date',
                'event_type' => 'page_view',
            ],
        ];

        $result = $this->service->validateImportData($data);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Invalid event_timestamp format', $result['errors'][0]);
    }

    /**
     * Test validateImportData detects unknown event type
     */
    public function test_validate_import_data_detects_unknown_event_type(): void
    {
        $data = [
            [
                'event_name' => 'page_view',
                'event_timestamp' => '2024-01-01T00:00:00Z',
                'event_type' => 'unknown_type',
            ],
        ];

        $result = $this->service->validateImportData($data);

        $this->assertTrue($result['valid']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('Unknown event_type', $result['warnings'][0]);
    }

    /**
     * Test getExportHistory returns correct structure
     */
    public function test_get_export_history_returns_correct_structure(): void
    {
        $result = $this->service->getExportHistory();

        $this->assertArrayHasKey('exports', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('offset', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('retrieved_at', $result);
    }

    /**
     * Test getExportHistory with pagination
     */
    public function test_get_export_history_with_pagination(): void
    {
        $result = $this->service->getExportHistory([
            'limit' => 10,
            'offset' => 5,
        ]);

        $this->assertEquals(10, $result['limit']);
        $this->assertEquals(5, $result['offset']);
    }

    /**
     * Test getExportHistory with format filter
     */
    public function test_get_export_history_with_format_filter(): void
    {
        $result = $this->service->getExportHistory([
            'format' => 'json',
        ]);

        $this->assertArrayHasKey('exports', $result);
    }

    /**
     * Test getImportHistory returns correct structure
     */
    public function test_get_import_history_returns_correct_structure(): void
    {
        $result = $this->service->getImportHistory();

        $this->assertArrayHasKey('imports', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('offset', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('retrieved_at', $result);
    }

    /**
     * Test getImportHistory with pagination
     */
    public function test_get_import_history_with_pagination(): void
    {
        $result = $this->service->getImportHistory([
            'limit' => 20,
            'offset' => 10,
        ]);

        $this->assertEquals(20, $result['limit']);
        $this->assertEquals(10, $result['offset']);
    }

    /**
     * Test previewExport returns correct structure
     */
    public function test_preview_export_returns_correct_structure(): void
    {
        $result = $this->service->previewExport([
            'data_types' => ['events', 'sessions'],
            'timeframe' => '24h',
        ]);

        $this->assertTrue($result['preview']);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('estimated_records', $result);
        $this->assertArrayHasKey('data_types', $result);
        $this->assertArrayHasKey('timeframe', $result);
        $this->assertArrayHasKey('sample_records', $result);
        $this->assertArrayHasKey('generated_at', $result);
    }

    /**
     * Test importFromCSV parses data correctly
     */
    public function test_import_from_csv_parses_data_correctly(): void
    {
        // Mock storage to return CSV content
        $csvContent = "event_name,event_timestamp,event_type\npage_view,2024-01-01T00:00:00Z,page_view";
        Storage::shouldReceive('get')
            ->with('test/path/file.csv')
            ->andReturn($csvContent);

        $result = $this->service->importFromCSV('test/path/file.csv');

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('record_count', $result);
        $this->assertEquals(1, $result['record_count']);
        $this->assertEquals('page_view', $result['data'][0]['event_name']);
    }

    /**
     * Test importFromJSON parses data correctly
     */
    public function test_import_from_json_parses_data_correctly(): void
    {
        $jsonContent = json_encode([
            'data' => [
                [
                    'event_name' => 'page_view',
                    'event_timestamp' => '2024-01-01T00:00:00Z',
                    'event_type' => 'page_view',
                ],
            ],
        ]);
        Storage::shouldReceive('get')
            ->with('test/path/file.json')
            ->andReturn($jsonContent);

        $result = $this->service->importFromJSON('test/path/file.json');

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('record_count', $result);
        $this->assertEquals(1, $result['record_count']);
        $this->assertEquals('page_view', $result['data'][0]['event_name']);
    }

    /**
     * Test importFromJSON with flat data structure
     */
    public function test_import_from_json_with_flat_data_structure(): void
    {
        $jsonContent = json_encode([
            [
                'event_name' => 'page_view',
                'event_timestamp' => '2024-01-01T00:00:00Z',
                'event_type' => 'page_view',
            ],
        ]);
        Storage::shouldReceive('get')
            ->with('test/path/file.json')
            ->andReturn($jsonContent);

        $result = $this->service->importFromJSON('test/path/file.json');

        $this->assertEquals(1, $result['record_count']);
    }

    /**
     * Test importFromJSON throws exception for invalid JSON
     */
    public function test_import_from_json_throws_exception_for_invalid_json(): void
    {
        Storage::shouldReceive('get')
            ->with('test/path/file.json')
            ->andReturn('invalid json{');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON format');

        $this->service->importFromJSON('test/path/file.json');
    }

    /**
     * Test format constants are defined correctly
     */
    public function test_format_constants_are_defined(): void
    {
        $this->assertEquals('json', AnalyticsDataExportImportService::FORMAT_JSON);
        $this->assertEquals('csv', AnalyticsDataExportImportService::FORMAT_CSV);
        $this->assertEquals('excel', AnalyticsDataExportImportService::FORMAT_EXCEL);
    }

    /**
     * Test status constants are defined correctly
     */
    public function test_status_constants_are_defined(): void
    {
        $this->assertEquals('pending', AnalyticsDataExportImportService::STATUS_PENDING);
        $this->assertEquals('processing', AnalyticsDataExportImportService::STATUS_PROCESSING);
        $this->assertEquals('completed', AnalyticsDataExportImportService::STATUS_COMPLETED);
        $this->assertEquals('failed', AnalyticsDataExportImportService::STATUS_FAILED);
    }

    /**
     * Test tenant isolation in cache keys
     */
    public function test_tenant_isolation_in_cache_operations(): void
    {
        // Create service with different tenant
        $tenant2 = Tenant::factory()->create();
        
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andReturn($tenant2->id);

        $service2 = new AnalyticsDataExportImportService($this->tenantContext);

        // Get export history for tenant 2
        $result2 = $service2->getExportHistory();
        
        // Verify tenant isolation
        $this->assertEquals($tenant2->id, $result2['tenant_id']);
    }

    /**
     * Test export handles empty data
     */
    public function test_export_handles_empty_data(): void
    {
        $data = [];
        $result = $this->service->exportToJSON($data);

        $this->assertArrayHasKey('file_path', $result);
        $this->assertArrayHasKey('file_name', $result);
        $this->assertArrayHasKey('record_count', $result);
        $this->assertEquals(0, $result['record_count']);
    }

    /**
     * Test export with multiple data types
     */
    public function test_export_with_multiple_data_types(): void
    {
        $data = [
            'events' => [
                ['event_name' => 'page_view', 'event_timestamp' => '2024-01-01T00:00:00Z', 'event_type' => 'page_view'],
            ],
            'sessions' => [
                ['session_id' => 'sess_123', 'user_id' => 'user_123'],
            ],
            'users' => [
                ['user_id' => 'user_123', 'email' => 'user@example.com'],
            ],
        ];

        $result = $this->service->exportToJSON($data);

        $this->assertEquals(3, $result['record_count']);
    }

    /**
     * Test import data with valid data returns success
     */
    public function test_import_data_with_valid_data_returns_success(): void
    {
        $jsonContent = json_encode([
            'data' => [
                [
                    'event_name' => 'page_view',
                    'event_timestamp' => '2024-01-01T00:00:00Z',
                    'event_type' => 'page_view',
                ],
            ],
        ]);
        
        Storage::shouldReceive('get')
            ->andReturn($jsonContent);

        $result = $this->service->importData('test/file.json');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('format', $result);
        $this->assertArrayHasKey('file_name', $result);
        $this->assertArrayHasKey('imported_count', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('imported_at', $result);
    }

    /**
     * Test import data throws exception for non-existent file
     */
    public function test_import_data_throws_exception_for_non_existent_file(): void
    {
        Storage::shouldReceive('exists')
            ->with('non/existent/file.json')
            ->andReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Import file not found');

        $this->service->importData('non/existent/file.json');
    }

    /**
     * Test CSV content includes BOM for Excel compatibility
     */
    public function test_csv_content_includes_bom(): void
    {
        $data = [
            'events' => [
                ['event_name' => 'page_view', 'event_timestamp' => '2024-01-01T00:00:00Z', 'event_type' => 'page_view'],
            ],
        ];

        $result = $this->service->exportToCSV($data);
        $content = Storage::get($result['file_path']);

        // Check for UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
    }

    /**
     * Test validateImportData returns warnings for unknown event types
     */
    public function test_validate_import_data_returns_warnings_for_unknown_types(): void
    {
        $data = [
            [
                'event_name' => 'custom_event',
                'event_timestamp' => '2024-01-01T00:00:00Z',
                'event_type' => 'completely_unknown_type_xyz',
            ],
        ];

        $result = $this->service->validateImportData($data);

        $this->assertTrue($result['valid']);
        $this->assertNotEmpty($result['warnings']);
    }

    /**
     * Test export generates unique filename
     */
    public function test_export_generates_unique_filename(): void
    {
        $result1 = $this->service->exportData(['format' => 'json', 'data_types' => ['events']]);
        $result2 = $this->service->exportData(['format' => 'json', 'data_types' => ['events']]);

        $this->assertNotEquals($result1['file_name'], $result2['file_name']);
    }

    /**
     * Test error handling in exportData
     */
    public function test_error_handling_in_export_data(): void
    {
        // Mock tenant context to throw exception
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andThrow(new \Exception('Test error'));

        $service = new AnalyticsDataExportImportService($this->tenantContext);
        $result = $service->exportData(['format' => 'json', 'data_types' => ['events']]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test error handling in getExportHistory
     */
    public function test_error_handling_in_get_export_history(): void
    {
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andThrow(new \Exception('Test error'));

        $service = new AnalyticsDataExportImportService($this->tenantContext);
        $result = $service->getExportHistory();

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(0, $result['total_count']);
    }

    /**
     * Test error handling in getImportHistory
     */
    public function test_error_handling_in_get_import_history(): void
    {
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andThrow(new \Exception('Test error'));

        $service = new AnalyticsDataExportImportService($this->tenantContext);
        $result = $service->getImportHistory();

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(0, $result['total_count']);
    }

    /**
     * Test invalid timeframe validation
     */
    public function test_invalid_timeframe_validation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid timeframe');

        $this->service->exportData([
            'format' => 'json',
            'data_types' => ['events'],
            'timeframe' => 'invalid_timeframe',
        ]);
    }
}
