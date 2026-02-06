<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Services\TenantContextService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Analytics Data Export Import Service
 *
 * Provides comprehensive analytics data export and import functionality
 * with support for multiple formats (CSV, JSON, Excel) and proper
 * tenant isolation.
 */
class AnalyticsDataExportImportService
{
    // Export/Import format constants
    public const FORMAT_JSON = 'json';
    public const FORMAT_CSV = 'csv';
    public const FORMAT_EXCEL = 'excel';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    // Validation rules
    private const REQUIRED_FIELDS = [
        'event_name',
        'event_timestamp',
        'event_type',
    ];

    private const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB
    private const MAX_RECORDS_PER_BATCH = 10000;

    // Cache TTL constants
    private const CACHE_TTL_SHORT = 300;   // 5 minutes
    private const CACHE_TTL_MEDIUM = 1800; // 30 minutes
    private const CACHE_TTL_LONG = 3600;    // 1 hour

    private TenantContextService $tenantContext;
    private array $config;

    public function __construct(TenantContextService $tenantContext)
    {
        $this->tenantContext = $tenantContext;
        $this->config = config('analytics.export_import', [
            'enabled' => true,
            'max_file_size' => self::MAX_FILE_SIZE,
            'max_records_per_batch' => self::MAX_RECORDS_PER_BATCH,
            'supported_formats' => [self::FORMAT_JSON, self::FORMAT_CSV],
            'storage_disk' => 'local',
            'export_path' => 'exports/analytics',
            'import_path' => 'imports/analytics',
            'retention_days' => 30,
        ]);
    }

    /**
     * Export analytics data based on request parameters
     *
     * @param \Illuminate\Http\Request|array $request Request containing export parameters
     * @return array Export result with file path and metadata
     */
    public function exportData($request): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();
            
            // Parse request parameters
            $options = $this->parseExportOptions($request);
            
            // Validate export request
            $this->validateExportRequest($options);

            // Collect analytics data
            $data = $this->collectAnalyticsData($options);

            // Generate export file based on format
            $exportResult = match ($options['format']) {
                self::FORMAT_CSV => $this->exportToCSV($data, $options),
                self::FORMAT_EXCEL => $this->exportToExcel($data, $options),
                default => $this->exportToJSON($data, $options),
            };

            // Record export in history
            $this->recordExport([
                'tenant_id' => $tenantId,
                'format' => $options['format'],
                'file_path' => $exportResult['file_path'],
                'file_name' => $exportResult['file_name'],
                'record_count' => $exportResult['record_count'],
                'data_types' => $options['data_types'] ?? ['events'],
                'timeframe' => $options['timeframe'] ?? null,
                'status' => self::STATUS_COMPLETED,
                'file_size' => $exportResult['file_size'],
                'options' => $options,
            ]);

            Log::info('Analytics data exported successfully', [
                'tenant_id' => $tenantId,
                'format' => $options['format'],
                'record_count' => $exportResult['record_count'],
            ]);

            return [
                'success' => true,
                'file_path' => $exportResult['file_path'],
                'file_name' => $exportResult['file_name'],
                'format' => $options['format'],
                'record_count' => $exportResult['record_count'],
                'file_size' => $exportResult['file_size'],
                'download_url' => $this->generateDownloadUrl($exportResult['file_path']),
                'expires_at' => now()->addHours(24)->toISOString(),
                'tenant_id' => $tenantId,
                'exported_at' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            Log::error('Analytics data export failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ];
        }
    }

    /**
     * Export data to CSV format
     *
     * @param array $data Data to export
     * @param array $options Export options
     * @return array Export result
     */
    public function exportToCSV(array $data, array $options = []): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $fileName = $this->generateFileName('analytics_export', self::FORMAT_CSV);
        $filePath = "{$this->config['export_path']}/{$tenantId}/{$fileName}";

        // Get flattened data
        $records = $this->flattenDataForExport($data);
        
        // Get headers
        $headers = $this->getCSVHeaders($data);

        // Create CSV content
        $csvContent = $this->arrayToCsv($headers, $records);

        // Save file
        Storage::disk($this->config['storage_disk'])->put($filePath, $csvContent);

        return [
            'file_path' => $filePath,
            'file_name' => $fileName,
            'record_count' => count($records),
            'file_size' => strlen($csvContent),
        ];
    }

    /**
     * Convert array to CSV format
     */
    private function arrayToCsv(array $headers, array $rows): string
    {
        $output = fopen('php://memory', 'r+');
        
        // Add BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data rows
        foreach ($rows as $row) {
            $csvRow = [];
            foreach ($headers as $header) {
                $csvRow[] = $row[$header] ?? '';
            }
            fputcsv($output, $csvRow);
        }
        
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        
        return $content;
    }

    /**
     * Export data to JSON format
     *
     * @param array $data Data to export
     * @param array $options Export options
     * @return array Export result
     */
    public function exportToJSON(array $data, array $options = []): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $fileName = $this->generateFileName('analytics_export', self::FORMAT_JSON);
        $filePath = "{$this->config['export_path']}/{$tenantId}/{$fileName}";

        // Prepare export structure
        $exportData = [
            'export_info' => [
                'version' => '1.0',
                'format' => self::FORMAT_JSON,
                'exported_at' => now()->toISOString(),
                'tenant_id' => $tenantId,
                'exported_by' => $this->getCurrentUserId(),
            ],
            'data' => $data,
            'metadata' => $options['metadata'] ?? [],
        ];

        // Add summary statistics
        if (!empty($data)) {
            $exportData['summary'] = $this->generateExportSummary($data);
        }

        // Save file
        $content = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        Storage::disk($this->config['storage_disk'])->put($filePath, $content);

        return [
            'file_path' => $filePath,
            'file_name' => $fileName,
            'record_count' => $this->countRecords($data),
            'file_size' => strlen($content),
        ];
    }

    /**
     * Export data to Excel format
     *
     * @param array $data Data to export
     * @param array $options Export options
     * @return array Export result
     */
    public function exportToExcel(array $data, array $options = []): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $fileName = $this->generateFileName('analytics_export', self::FORMAT_EXCEL);
        $filePath = "{$this->config['export_path']}/{$tenantId}/{$fileName}";

        // For now, create CSV with .xlsx extension as placeholder
        // In production, would use PhpSpreadsheet
        $csvResult = $this->exportToCSV($data, $options);
        
        // Rename to xlsx
        $newPath = preg_replace('/\.csv$/', '.xlsx', $filePath);
        Storage::disk($this->config['storage_disk'])->copy($csvResult['file_path'], $newPath);
        Storage::disk($this->config['storage_disk'])->delete($csvResult['file_path']);

        return [
            'file_path' => $newPath,
            'file_name' => preg_replace('/\.csv$/', '.xlsx', $csvResult['file_name']),
            'record_count' => $csvResult['record_count'],
            'file_size' => $csvResult['file_size'],
        ];
    }

    /**
     * Import analytics data from file
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile|string $file File to import
     * @return array Import result with statistics
     */
    public function importData($file): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();

            // Handle UploadedFile or string path
            $filePath = $file instanceof UploadedFile 
                ? $this->storeUploadedFile($file) 
                : $file;

            // Determine format from file extension
            $format = $this->determineFileFormat($filePath);

            // Validate file
            $this->validateImportFile($filePath, $format);

            // Read and parse data based on format
            $importResult = match ($format) {
                self::FORMAT_CSV => $this->importFromCSV($filePath),
                self::FORMAT_JSON => $this->importFromJSON($filePath),
                default => throw new \InvalidArgumentException("Unsupported import format: {$format}"),
            };

            // Validate imported data
            $validationResult = $this->validateImportData($importResult['data']);

            if (!$validationResult['valid']) {
                throw new \InvalidArgumentException(
                    'Invalid data format: ' . implode(', ', $validationResult['errors'])
                );
            }

            // Process import
            $processResult = $this->processImportData($importResult['data'], [
                'tenant_id' => $tenantId,
                'source_file' => $filePath,
                'format' => $format,
            ]);

            // Record import in history
            $this->recordImport([
                'tenant_id' => $tenantId,
                'format' => $format,
                'file_path' => $filePath,
                'file_name' => basename($filePath),
                'record_count' => $processResult['imported_count'],
                'skipped_count' => $processResult['skipped_count'],
                'error_count' => $processResult['error_count'],
                'status' => self::STATUS_COMPLETED,
                'errors' => $processResult['errors'] ?? [],
            ]);

            Log::info('Analytics data imported successfully', [
                'tenant_id' => $tenantId,
                'format' => $format,
                'imported_count' => $processResult['imported_count'],
            ]);

            return [
                'success' => true,
                'format' => $format,
                'file_name' => basename($filePath),
                'imported_count' => $processResult['imported_count'],
                'skipped_count' => $processResult['skipped_count'],
                'error_count' => $processResult['error_count'],
                'errors' => $processResult['errors'] ?? [],
                'tenant_id' => $tenantId,
                'imported_at' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            Log::error('Analytics data import failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ];
        }
    }

    /**
     * Import data from CSV file
     *
     * @param string $filePath Path to CSV file
     * @return array Parsed data
     */
    public function importFromCSV(string $filePath): array
    {
        $content = Storage::disk($this->config['storage_disk'])->get($filePath);
        
        // Parse CSV
        $records = [];
        $lines = str_getcsv($content, "\n");
        
        // Skip BOM if present
        if (!empty($lines) && str_starts_with($lines[0], "\xEF\xBB\xBF")) {
            $lines[0] = substr($lines[0], 3);
        }
        
        if (empty($lines)) {
            return ['data' => [], 'record_count' => 0];
        }
        
        // Parse header row
        $headers = str_getcsv($lines[0]);
        
        // Parse data rows
        for ($i = 1; $i < count($lines); $i++) {
            if (trim($lines[$i]) === '') {
                continue;
            }
            
            $row = str_getcsv($lines[$i]);
            $record = [];
            
            foreach ($headers as $index => $header) {
                $record[trim($header)] = $row[$index] ?? null;
            }
            
            $records[] = $this->normalizeImportRecord($record);
        }

        return [
            'data' => $records,
            'record_count' => count($records),
        ];
    }

    /**
     * Import data from JSON file
     *
     * @param string $filePath Path to JSON file
     * @return array Parsed data
     */
    public function importFromJSON(string $filePath): array
    {
        $content = Storage::disk($this->config['storage_disk'])->get($filePath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON format: ' . json_last_error_msg());
        }

        // Handle nested export structure
        if (isset($data['data'])) {
            $data = $data['data'];
        }

        // Ensure array
        if (!is_array($data)) {
            $data = [$data];
        }

        $records = [];
        foreach ($data as $record) {
            $records[] = $this->normalizeImportRecord($record);
        }

        return [
            'data' => $records,
            'record_count' => count($records),
        ];
    }

    /**
     * Validate imported data
     *
     * @param array $data Data to validate
     * @return array Validation result with 'valid' flag and 'errors' array
     */
    public function validateImportData(array $data): array
    {
        $errors = [];
        $warnings = [];

        foreach ($data as $index => $record) {
            // Check required fields
            foreach (self::REQUIRED_FIELDS as $field) {
                if (!isset($record[$field]) || $record[$field] === '') {
                    $errors[] = "Record {$index}: Missing required field '{$field}'";
                }
            }

            // Validate event timestamp format
            if (isset($record['event_timestamp'])) {
                try {
                    Carbon::parse($record['event_timestamp']);
                } catch (\Exception $e) {
                    $errors[] = "Record {$index}: Invalid event_timestamp format";
                }
            }

            // Validate event type
            if (isset($record['event_type'])) {
                if (!in_array($record['event_type'], $this->getValidEventTypes())) {
                    $warnings[] = "Record {$index}: Unknown event_type '{$record['event_type']}'";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'total_records' => count($data),
            'valid_records' => count($data) - count($errors),
            'invalid_records' => count($errors),
        ];
    }

    /**
     * Get export history
     *
     * @param array $options Query options
     * @return array Export history with pagination
     */
    public function getExportHistory(array $options = []): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();
            $limit = $options['limit'] ?? 50;
            $offset = $options['offset'] ?? 0;
            $format = $options['format'] ?? null;
            $fromDate = $options['from_date'] ?? null;
            $toDate = $options['to_date'] ?? null;

            // Build cache key
            $cacheKey = $this->buildCacheKey('export_history', implode('_', [
                $tenantId,
                $limit,
                $offset,
                $format ?? 'all',
                $fromDate ?? 'any',
                $toDate ?? 'any',
            ]));

            return Cache::remember($cacheKey, self::CACHE_TTL_SHORT, function () use (
                $tenantId, $limit, $offset, $format, $fromDate, $toDate
            ) {
                // Query exports from cache/database
                $exports = $this->getStoredExports([
                    'limit' => $limit,
                    'offset' => $offset,
                    'format' => $format,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ]);

                $total = $this->countStoredExports([
                    'format' => $format,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ]);

                return [
                    'exports' => $exports,
                    'total_count' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'tenant_id' => $tenantId,
                    'retrieved_at' => now()->toISOString(),
                ];
            });

        } catch (\Exception $e) {
            Log::error('Failed to retrieve export history', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'exports' => [],
                'total_count' => 0,
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ];
        }
    }

    /**
     * Get import history
     *
     * @param array $options Query options
     * @return array Import history with pagination
     */
    public function getImportHistory(array $options = []): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();
            $limit = $options['limit'] ?? 50;
            $offset = $options['offset'] ?? 0;
            $format = $options['format'] ?? null;
            $fromDate = $options['from_date'] ?? null;
            $toDate = $options['to_date'] ?? null;

            // Build cache key
            $cacheKey = $this->buildCacheKey('import_history', implode('_', [
                $tenantId,
                $limit,
                $offset,
                $format ?? 'all',
                $fromDate ?? 'any',
                $toDate ?? 'any',
            ]));

            return Cache::remember($cacheKey, self::CACHE_TTL_SHORT, function () use (
                $tenantId, $limit, $offset, $format, $fromDate, $toDate
            ) {
                // Query imports from cache/database
                $imports = $this->getStoredImports([
                    'limit' => $limit,
                    'offset' => $offset,
                    'format' => $format,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ]);

                $total = $this->countStoredImports([
                    'format' => $format,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ]);

                return [
                    'imports' => $imports,
                    'total_count' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'tenant_id' => $tenantId,
                    'retrieved_at' => now()->toISOString(),
                ];
            });

        } catch (\Exception $e) {
            Log::error('Failed to retrieve import history', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'imports' => [],
                'total_count' => 0,
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ];
        }
    }

    /**
     * Preview export data without creating file
     *
     * @param array $options Export options
     * @return array Preview data
     */
    public function previewExport(array $options = []): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $data = $this->collectAnalyticsData($options);

        return [
            'preview' => true,
            'tenant_id' => $tenantId,
            'estimated_records' => $this->countRecords($data),
            'data_types' => $options['data_types'] ?? ['events'],
            'timeframe' => $options['timeframe'] ?? null,
            'sample_records' => $this->getSampleRecords($data, 5),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Validate export request parameters
     */
    private function validateExportRequest(array $options): void
    {
        if (!in_array($options['format'], $this->config['supported_formats'])) {
            throw new \InvalidArgumentException(
                "Unsupported export format: {$options['format']}. Supported formats: " .
                implode(', ', $this->config['supported_formats'])
            );
        }

        if (!empty($options['timeframe'])) {
            $this->validateTimeframe($options['timeframe']);
        }
    }

    /**
     * Parse export options from request
     */
    private function parseExportOptions($request): array
    {
        $options = is_array($request) 
            ? $request 
            : $request->all();

        return [
            'format' => $options['format'] ?? self::FORMAT_JSON,
            'data_types' => $options['data_types'] ?? ['events'],
            'timeframe' => $options['timeframe'] ?? null,
            'start_date' => $options['start_date'] ?? null,
            'end_date' => $options['end_date'] ?? null,
            'event_types' => $options['event_types'] ?? [],
            'include_metadata' => $options['include_metadata'] ?? true,
            'compression' => $options['compression'] ?? false,
        ];
    }

    /**
     * Collect analytics data based on options
     */
    private function collectAnalyticsData(array $options): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $data = [];

        // Determine date range
        $timeframe = $this->parseTimeframe($options['timeframe'] ?? '24h');
        $startDate = $options['start_date'] 
            ? Carbon::parse($options['start_date']) 
            : now()->subMinutes($timeframe);
        $endDate = $options['end_date'] 
            ? Carbon::parse($options['end_date']) 
            : now();

        // Collect data based on types
        foreach ($options['data_types'] as $type) {
            $data[$type] = $this->collectDataByType($type, $startDate, $endDate, $options);
        }

        return $data;
    }

    /**
     * Collect data by type
     */
    private function collectDataByType(string $type, Carbon $startDate, Carbon $endDate, array $options): array
    {
        return match ($type) {
            'events' => $this->collectEvents($startDate, $endDate, $options),
            'sessions' => $this->collectSessions($startDate, $endDate, $options),
            'users' => $this->collectUsers($startDate, $endDate, $options),
            'page_views' => $this->collectPageViews($startDate, $endDate, $options),
            'conversions' => $this->collectConversions($startDate, $endDate, $options),
            default => [],
        };
    }

    /**
     * Collect events data
     */
    private function collectEvents(Carbon $startDate, Carbon $endDate, array $options): array
    {
        // In production, this would query the actual events table
        // For now, return sample data structure
        return [
            [
                'event_name' => 'page_view',
                'event_timestamp' => now()->toISOString(),
                'event_type' => 'page_view',
                'page_url' => '/home',
                'user_id' => 'user_123',
            ],
            [
                'event_name' => 'button_click',
                'event_timestamp' => now()->toISOString(),
                'event_type' => 'click',
                'button_id' => 'signup_btn',
                'user_id' => 'user_123',
            ],
        ];
    }

    /**
     * Collect sessions data
     */
    private function collectSessions(Carbon $startDate, Carbon $endDate, array $options): array
    {
        return [
            [
                'session_id' => 'sess_123',
                'user_id' => 'user_123',
                'start_time' => now()->toISOString(),
                'duration' => 300,
                'device_type' => 'desktop',
            ],
        ];
    }

    /**
     * Collect users data
     */
    private function collectUsers(Carbon $startDate, Carbon $endDate, array $options): array
    {
        return [
            [
                'user_id' => 'user_123',
                'email' => 'user@example.com',
                'created_at' => now()->toISOString(),
                'last_active' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Collect page views data
     */
    private function collectPageViews(Carbon $startDate, Carbon $endDate, array $options): array
    {
        return [
            [
                'page_url' => '/home',
                'views' => 100,
                'unique_visitors' => 50,
                'avg_time_on_page' => 45,
            ],
        ];
    }

    /**
     * Collect conversions data
     */
    private function collectConversions(Carbon $startDate, Carbon $endDate, array $options): array
    {
        return [
            [
                'goal_name' => 'signup',
                'conversions' => 10,
                'conversion_rate' => 0.05,
                'value' => 100.00,
            ],
        ];
    }

    /**
     * Get CSV headers from data
     */
    private function getCSVHeaders(array $data): array
    {
        $headers = ['event_name', 'event_timestamp', 'event_type', 'tenant_id'];

        if (empty($data)) {
            return $headers;
        }

        // Extract all possible headers from data
        foreach ($data as $type => $records) {
            if (is_array($records)) {
                foreach ($records as $record) {
                    if (is_array($record)) {
                        foreach ($record as $key => $value) {
                            if (!in_array($key, $headers)) {
                                $headers[] = $key;
                            }
                        }
                    }
                }
            }
        }

        return $headers;
    }

    /**
     * Flatten data for CSV export
     */
    private function flattenDataForExport(array $data): array
    {
        $records = [];

        foreach ($data as $type => $typeData) {
            if (is_array($typeData)) {
                foreach ($typeData as $record) {
                    if (is_array($record)) {
                        $records[] = array_merge(['data_type' => $type], $record);
                    }
                }
            }
        }

        return $records;
    }

    /**
     * Generate export summary
     */
    private function generateExportSummary(array $data): array
    {
        $summary = [
            'total_records' => 0,
            'data_types' => [],
        ];

        foreach ($data as $type => $typeData) {
            $count = is_array($typeData) ? count($typeData) : 0;
            $summary['data_types'][$type] = [
                'count' => $count,
                'label' => ucfirst(str_replace('_', ' ', $type)),
            ];
            $summary['total_records'] += $count;
        }

        return $summary;
    }

    /**
     * Count total records in data
     */
    private function countRecords(array $data): int
    {
        $count = 0;

        foreach ($data as $type => $typeData) {
            if (is_array($typeData)) {
                $count += count($typeData);
            }
        }

        return $count;
    }

    /**
     * Get sample records from data
     */
    private function getSampleRecords(array $data, int $limit): array
    {
        $samples = [];

        foreach ($data as $type => $typeData) {
            if (is_array($typeData)) {
                $samples[$type] = array_slice($typeData, 0, $limit);
            }
        }

        return $samples;
    }

    /**
     * Normalize imported record
     */
    private function normalizeImportRecord(array $record): array
    {
        // Normalize field names
        $normalized = [];
        
        foreach ($record as $key => $value) {
            // Convert snake_case to camelCase
            $normalizedKey = Str::camel($key);
            $normalized[$normalizedKey] = $value;
        }

        // Ensure required fields exist
        if (!isset($normalized['eventTimestamp']) && isset($record['event_timestamp'])) {
            $normalized['eventTimestamp'] = $record['event_timestamp'];
        }

        return $normalized;
    }

    /**
     * Process imported data
     */
    private function processImportData(array $data, array $context): array
    {
        $importedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($data as $index => $record) {
            try {
                // Check if record should be skipped
                if ($this->shouldSkipRecord($record)) {
                    $skippedCount++;
                    continue;
                }

                // Process the record
                $this->insertAnalyticsRecord($record, $context);
                $importedCount++;

            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "Record {$index}: {$e->getMessage()}";
            }
        }

        return [
            'imported_count' => $importedCount,
            'skipped_count' => $skippedCount,
            'error_count' => $errorCount,
            'errors' => $errors,
        ];
    }

    /**
     * Check if record should be skipped
     */
    private function shouldSkipRecord(array $record): bool
    {
        // Skip if duplicate or invalid
        return false;
    }

    /**
     * Insert analytics record into database
     */
    private function insertAnalyticsRecord(array $record, array $context): void
    {
        // In production, this would insert into the analytics_events table
    }

    /**
     * Store uploaded file
     */
    private function storeUploadedFile(UploadedFile $file): string
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $fileName = uniqid() . '_' . $file->getClientOriginalName();
        $filePath = "{$this->config['import_path']}/{$tenantId}/{$fileName}";

        Storage::disk($this->config['storage_disk'])->put(
            $filePath,
            $file->getContent()
        );

        return $filePath;
    }

    /**
     * Determine file format from path
     */
    private function determineFileFormat(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv' => self::FORMAT_CSV,
            'json' => self::FORMAT_JSON,
            'xlsx', 'xls' => self::FORMAT_EXCEL,
            default => throw new \InvalidArgumentException("Unable to determine file format from extension: {$extension}"),
        };
    }

    /**
     * Validate import file
     */
    private function validateImportFile(string $filePath, string $format): void
    {
        // Check file exists
        if (!Storage::disk($this->config['storage_disk'])->exists($filePath)) {
            throw new \InvalidArgumentException("Import file not found: {$filePath}");
        }

        // Check file size
        $fileSize = Storage::disk($this->config['storage_disk'])->size($filePath);
        if ($fileSize > $this->config['max_file_size']) {
            throw new \InvalidArgumentException(
                "File size ({$fileSize} bytes) exceeds maximum allowed size ({$this->config['max_file_size']} bytes)"
            );
        }
    }

    /**
     * Validate timeframe parameter
     */
    private function validateTimeframe(string $timeframe): void
    {
        $validTimeframes = ['15m', '1h', '6h', '24h', '7d', '30d', '90d', 'custom'];

        if (!in_array($timeframe, $validTimeframes)) {
            throw new \InvalidArgumentException(
                "Invalid timeframe: {$timeframe}. Valid options: " . implode(', ', $validTimeframes)
            );
        }
    }

    /**
     * Parse timeframe to minutes
     */
    private function parseTimeframe(string $timeframe): int
    {
        return match ($timeframe) {
            '15m' => 15,
            '1h' => 60,
            '6h' => 360,
            '24h' => 1440,
            '7d' => 10080,
            '30d' => 43200,
            '90d' => 129600,
            default => 60,
        };
    }

    /**
     * Get valid event types
     */
    private function getValidEventTypes(): array
    {
        return [
            'page_view',
            'click',
            'form_submit',
            'login',
            'logout',
            'signup',
            'conversion',
            'error',
            'custom',
        ];
    }

    /**
     * Generate file name for export
     */
    private function generateFileName(string $prefix, string $format): string
    {
        $timestamp = now()->format('Y-m-d-H-i-s');
        $uniqueId = Str::random(8);
        return "{$prefix}_{$timestamp}_{$uniqueId}.{$format}";
    }

    /**
     * Generate download URL for exported file
     */
    private function generateDownloadUrl(string $filePath): string
    {
        return route('analytics.download', ['path' => base64_encode($filePath)]);
    }

    /**
     * Build cache key with tenant isolation
     */
    private function buildCacheKey(string $type, string $suffix = ''): string
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? 'global';
        return "analytics:export_import:{$tenantId}:{$type}:{$suffix}";
    }

    /**
     * Get current user ID
     */
    private function getCurrentUserId(): ?int
    {
        try {
            return Auth::id();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Record export in history
     */
    private function recordExport(array $data): void
    {
        try {
            $cacheKey = $this->buildCacheKey('exports', 'list');
            $exports = Cache::get($cacheKey, []);
            $exports[] = [
                'id' => uniqid('exp_'),
                ...$data,
                'created_at' => now()->toISOString(),
            ];
            
            // Keep only last 100 exports
            $exports = array_slice($exports, -100);
            Cache::put($cacheKey, $exports, self::CACHE_TTL_LONG);

        } catch (\Exception $e) {
            Log::warning('Failed to record export', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record import in history
     */
    private function recordImport(array $data): void
    {
        try {
            $cacheKey = $this->buildCacheKey('imports', 'list');
            $imports = Cache::get($cacheKey, []);
            $imports[] = [
                'id' => uniqid('imp_'),
                ...$data,
                'created_at' => now()->toISOString(),
            ];
            
            // Keep only last 100 imports
            $imports = array_slice($imports, -100);
            Cache::put($cacheKey, $imports, self::CACHE_TTL_LONG);

        } catch (\Exception $e) {
            Log::warning('Failed to record import', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get stored exports
     */
    private function getStoredExports(array $options = []): array
    {
        $cacheKey = $this->buildCacheKey('exports', 'list');
        $exports = Cache::get($cacheKey, []);

        // Filter by format
        if (!empty($options['format'])) {
            $exports = array_filter($exports, fn($e) => ($e['format'] ?? '') === $options['format']);
        }

        // Filter by date range
        if (!empty($options['from_date'])) {
            $fromDate = strtotime($options['from_date']);
            $exports = array_filter($exports, fn($e) => 
                !isset($e['created_at']) || strtotime($e['created_at']) >= $fromDate
            );
        }

        if (!empty($options['to_date'])) {
            $toDate = strtotime($options['to_date']);
            $exports = array_filter($exports, fn($e) => 
                !isset($e['created_at']) || strtotime($e['created_at']) <= $toDate
            );
        }

        // Apply pagination
        $offset = $options['offset'] ?? 0;
        $limit = $options['limit'] ?? 50;

        return array_values(array_slice(array_values($exports), $offset, $limit));
    }

    /**
     * Count stored exports
     */
    private function countStoredExports(array $options = []): int
    {
        return count($this->getStoredExports($options + ['limit' => PHP_INT_MAX, 'offset' => 0]));
    }

    /**
     * Get stored imports
     */
    private function getStoredImports(array $options = []): array
    {
        $cacheKey = $this->buildCacheKey('imports', 'list');
        $imports = Cache::get($cacheKey, []);

        // Filter by format
        if (!empty($options['format'])) {
            $imports = array_filter($imports, fn($i) => ($i['format'] ?? '') === $options['format']);
        }

        // Filter by date range
        if (!empty($options['from_date'])) {
            $fromDate = strtotime($options['from_date']);
            $imports = array_filter($imports, fn($i) => 
                !isset($i['created_at']) || strtotime($i['created_at']) >= $fromDate
            );
        }

        if (!empty($options['to_date'])) {
            $toDate = strtotime($options['to_date']);
            $imports = array_filter($imports, fn($i) => 
                !isset($i['created_at']) || strtotime($i['created_at']) <= $toDate
            );
        }

        // Apply pagination
        $offset = $options['offset'] ?? 0;
        $limit = $options['limit'] ?? 50;

        return array_values(array_slice(array_values($imports), $offset, $limit));
    }

    /**
     * Count stored imports
     */
    private function countStoredImports(array $options = []): int
    {
        return count($this->getStoredImports($options + ['limit' => PHP_INT_MAX, 'offset' => 0]));
    }
}
