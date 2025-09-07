<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Template;
use App\Exceptions\TemplateValidationException;

/**
 * Template Import/Export Service
 *
 * Handles importing and exporting templates in various formats
 * with validation, transformation, and bulk operations support.
 */
class TemplateImportExportService
{
    private const SUPPORTED_FORMATS = ['json', 'xml', 'csv'];
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const BATCH_SIZE = 50;

    /**
     * Export templates to specified format
     *
     * @param array $templateIds
     * @param string $format
     * @param array $options
     * @return array
     */
    public function exportTemplates(array $templateIds, string $format = 'json', array $options = []): array
    {
        $this->validateExportFormat($format);

        $templates = Template::with(['landingPages', 'creator'])
            ->whereIn('id', $templateIds)
            ->get();

        if ($templates->isEmpty()) {
            throw new \InvalidArgumentException('No templates found for export');
        }

        $exportData = $this->prepareExportData($templates, $options);

        return [
            'data' => $this->formatExportData($exportData, $format),
            'format' => $format,
            'count' => $templates->count(),
            'timestamp' => now()->toISOString(),
            'version' => '1.0',
        ];
    }

    /**
     * Import templates from data
     *
     * @param string $data
     * @param string $format
     * @param array $options
     * @return array
     */
    public function importTemplates(string $data, string $format = 'json', array $options = []): array
    {
        $this->validateImportFormat($format);

        $importData = $this->parseImportData($data, $format);
        $this->validateImportData($importData);

        $results = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
            'warnings' => [],
        ];

        // Process in batches for better performance
        $batches = array_chunk($importData['templates'] ?? [], self::BATCH_SIZE);

        foreach ($batches as $batch) {
            $batchResults = $this->processImportBatch($batch, $options);
            $results['imported'] += $batchResults['imported'];
            $results['skipped'] += $batchResults['skipped'];
            $results['errors'] = array_merge($results['errors'], $batchResults['errors']);
            $results['warnings'] = array_merge($results['warnings'], $batchResults['warnings']);
        }

        Log::info('Template import completed', [
            'imported' => $results['imported'],
            'skipped' => $results['skipped'],
            'errors' => count($results['errors']),
        ]);

        return $results;
    }

    /**
     * Export templates to file
     *
     * @param array $templateIds
     * @param string $format
     * @param string $filename
     * @param array $options
     * @return string
     */
    public function exportToFile(array $templateIds, string $format = 'json', string $filename = null, array $options = []): string
    {
        $export = $this->exportTemplates($templateIds, $format, $options);

        $filename = $filename ?? 'templates_export_' . now()->format('Y-m-d_H-i-s') . '.' . $format;
        $path = 'exports/' . $filename;

        Storage::put($path, $export['data']);

        Log::info('Templates exported to file', [
            'filename' => $filename,
            'format' => $format,
            'count' => $export['count'],
        ]);

        return $path;
    }

    /**
     * Import templates from file
     *
     * @param string $filePath
     * @param array $options
     * @return array
     */
    public function importFromFile(string $filePath, array $options = []): array
    {
        if (!Storage::exists($filePath)) {
            throw new \InvalidArgumentException('Import file not found: ' . $filePath);
        }

        $fileSize = Storage::size($filePath);
        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('File size exceeds maximum limit: ' . self::MAX_FILE_SIZE . ' bytes');
        }

        $data = Storage::get($filePath);
        $format = $this->detectFileFormat($filePath);

        return $this->importTemplates($data, $format, $options);
    }

    /**
     * Get export template with available options
     *
     * @return array
     */
    public function getExportTemplate(): array
    {
        return [
            'formats' => self::SUPPORTED_FORMATS,
            'options' => [
                'include_related' => true,
                'include_metadata' => true,
                'compress' => false,
                'anonymize_data' => false,
            ],
            'max_templates' => 1000,
            'batch_size' => self::BATCH_SIZE,
        ];
    }

    /**
     * Validate export format
     *
     * @param string $format
     * @throws \InvalidArgumentException
     */
    private function validateExportFormat(string $format): void
    {
        if (!in_array($format, self::SUPPORTED_FORMATS)) {
            throw new \InvalidArgumentException('Unsupported export format: ' . $format);
        }
    }

    /**
     * Validate import format
     *
     * @param string $format
     * @throws \InvalidArgumentException
     */
    private function validateImportFormat(string $format): void
    {
        if (!in_array($format, self::SUPPORTED_FORMATS)) {
            throw new \InvalidArgumentException('Unsupported import format: ' . $format);
        }
    }

    /**
     * Prepare data for export
     *
     * @param Collection $templates
     * @param array $options
     * @return array
     */
    private function prepareExportData(Collection $templates, array $options): array
    {
        $exportData = [
            'metadata' => [
                'exported_at' => now()->toISOString(),
                'version' => '1.0',
                'total_templates' => $templates->count(),
            ],
            'templates' => [],
        ];

        foreach ($templates as $template) {
            $templateData = [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'category' => $template->category,
                'audience_type' => $template->audience_type,
                'campaign_type' => $template->campaign_type,
                'structure' => $template->structure,
                'is_active' => $template->is_active,
                'is_premium' => $template->is_premium,
                'tags' => $template->tags,
                'performance_metrics' => $template->performance_metrics,
                'usage_count' => $template->usage_count,
                'created_at' => $template->created_at?->toISOString(),
                'updated_at' => $template->updated_at?->toISOString(),
            ];

            // Include related data if requested
            if ($options['include_related'] ?? true) {
                $templateData['landing_pages'] = $template->landingPages->map(function ($lp) {
                    return [
                        'id' => $lp->id,
                        'name' => $lp->name,
                        'status' => $lp->status,
                        'usage_count' => $lp->usage_count,
                    ];
                })->toArray();
            }

            // Include creator info if requested
            if (($options['include_metadata'] ?? true) && $template->creator) {
                $templateData['creator'] = [
                    'id' => $template->creator->id,
                    'name' => $template->creator->name,
                    'email' => $options['anonymize_data'] ?? false ? null : $template->creator->email,
                ];
            }

            $exportData['templates'][] = $templateData;
        }

        return $exportData;
    }

    /**
     * Format export data according to specified format
     *
     * @param array $data
     * @param string $format
     * @return string
     */
    private function formatExportData(array $data, string $format): string
    {
        switch ($format) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            case 'xml':
                return $this->arrayToXml($data);

            case 'csv':
                return $this->arrayToCsv($data['templates']);

            default:
                throw new \InvalidArgumentException('Unsupported format: ' . $format);
        }
    }

    /**
     * Parse import data from specified format
     *
     * @param string $data
     * @param string $format
     * @return array
     */
    private function parseImportData(string $data, string $format): array
    {
        switch ($format) {
            case 'json':
                return json_decode($data, true);

            case 'xml':
                return $this->xmlToArray($data);

            case 'csv':
                return ['templates' => $this->csvToArray($data)];

            default:
                throw new \InvalidArgumentException('Unsupported format: ' . $format);
        }
    }

    /**
     * Validate import data structure
     *
     * @param array $data
     * @throws TemplateValidationException
     */
    private function validateImportData(array $data): void
    {
        $rules = [
            'templates' => 'required|array',
            'templates.*.name' => 'required|string|max:255',
            'templates.*.category' => 'required|string|max:100',
            'templates.*.structure' => 'required|array',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new TemplateValidationException(
                'Import data validation failed: ' . json_encode($validator->errors()->toArray())
            );
        }
    }

    /**
     * Process a batch of templates for import
     *
     * @param array $batch
     * @param array $options
     * @return array
     */
    private function processImportBatch(array $batch, array $options): array
    {
        $results = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
            'warnings' => [],
        ];

        foreach ($batch as $templateData) {
            try {
                $result = $this->importSingleTemplate($templateData, $options);

                if ($result['imported']) {
                    $results['imported']++;
                } else {
                    $results['skipped']++;
                    if ($result['warning']) {
                        $results['warnings'][] = $result['warning'];
                    }
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'template' => $templateData['name'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
                Log::error('Template import error', [
                    'template' => $templateData['name'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Import a single template
     *
     * @param array $templateData
     * @param array $options
     * @return array
     */
    private function importSingleTemplate(array $templateData, array $options): array
    {
        // Check if template already exists
        $existingTemplate = Template::where('name', $templateData['name'])->first();

        if ($existingTemplate && !($options['overwrite_existing'] ?? false)) {
            return [
                'imported' => false,
                'warning' => "Template '{$templateData['name']}' already exists, skipping",
            ];
        }

        $template = $existingTemplate ?: new Template();

        // Map import data to template attributes
        $template->fill([
            'name' => $templateData['name'],
            'description' => $templateData['description'] ?? '',
            'category' => $templateData['category'],
            'audience_type' => $templateData['audience_type'] ?? 'general',
            'campaign_type' => $templateData['campaign_type'] ?? null,
            'structure' => $templateData['structure'],
            'is_active' => $templateData['is_active'] ?? true,
            'is_premium' => $templateData['is_premium'] ?? false,
            'tags' => $templateData['tags'] ?? [],
            'performance_metrics' => $templateData['performance_metrics'] ?? [],
            'usage_count' => $templateData['usage_count'] ?? 0,
        ]);

        $template->save();

        return ['imported' => true];
    }

    /**
     * Convert array to XML
     *
     * @param array $data
     * @return string
     */
    private function arrayToXml(array $data): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><templates/>');
        $this->arrayToXmlRecursive($data, $xml);
        return $xml->asXML();
    }

    /**
     * Recursively convert array to XML
     *
     * @param array $data
     * @param \SimpleXMLElement $xml
     */
    private function arrayToXmlRecursive(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $xml->addChild($key);
                $this->arrayToXmlRecursive($value, $child);
            } else {
                $xml->addChild($key, htmlspecialchars((string) $value));
            }
        }
    }

    /**
     * Convert XML to array
     *
     * @param string $xml
     * @return array
     */
    private function xmlToArray(string $xml): array
    {
        $xmlObject = simplexml_load_string($xml);
        return json_decode(json_encode($xmlObject), true);
    }

    /**
     * Convert array to CSV
     *
     * @param array $data
     * @return string
     */
    private function arrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $headers = array_keys($data[0]);
        $csv = implode(',', array_map('addslashes', $headers)) . "\n";

        foreach ($data as $row) {
            $values = array_map(function ($value) {
                return is_array($value) ? json_encode($value) : (string) $value;
            }, $row);
            $csv .= implode(',', array_map('addslashes', $values)) . "\n";
        }

        return $csv;
    }

    /**
     * Convert CSV to array
     *
     * @param string $csv
     * @return array
     */
    private function csvToArray(string $csv): array
    {
        $lines = explode("\n", trim($csv));
        if (empty($lines)) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        $data = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $values = str_getcsv($line);
            $row = [];

            foreach ($headers as $index => $header) {
                $value = $values[$index] ?? '';

                // Try to decode JSON values
                $decoded = json_decode($value, true);
                $row[$header] = $decoded !== null ? $decoded : $value;
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     * Detect file format from filename
     *
     * @param string $filename
     * @return string
     */
    private function detectFileFormat(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($extension, self::SUPPORTED_FORMATS)) {
            return $extension;
        }

        throw new \InvalidArgumentException('Could not detect file format from filename: ' . $filename);
    }
}