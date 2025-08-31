<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\ComponentVersion;
use App\Services\ComponentVersionService;
use App\Services\ComponentExportImportService;
use App\Services\ComponentPerformanceAnalysisService;
use App\Services\ComponentBackupRecoveryService;
use App\Services\ComponentMigrationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ComponentVersionController extends Controller
{
    public function __construct(
        private ComponentVersionService $versionService,
        private ComponentExportImportService $exportImportService,
        private ComponentPerformanceAnalysisService $performanceService,
        private ComponentBackupRecoveryService $backupService,
        private ComponentMigrationService $migrationService
    ) {}

    /**
     * Get version history for a component
     */
    public function index(Component $component): JsonResponse
    {
        $versions = $this->versionService->getVersionHistory($component);

        return response()->json([
            'success' => true,
            'data' => [
                'component_id' => $component->id,
                'versions' => $versions->map(function (ComponentVersion $version) {
                    return [
                        'id' => $version->id,
                        'version_number' => $version->version_number,
                        'description' => $version->description,
                        'changes' => $version->changes,
                        'created_by' => $version->creator?->name,
                        'created_at' => $version->created_at,
                        'is_latest' => $version->is_latest,
                    ];
                }),
                'total_versions' => $versions->count(),
            ],
        ]);
    }

    /**
     * Create a new version of a component
     */
    public function store(Request $request, Component $component): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string|max:500',
            'changes' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $version = $this->versionService->createVersion(
                $component,
                $request->input('changes', []),
                $request->input('description')
            );

            return response()->json([
                'success' => true,
                'message' => 'Version created successfully',
                'data' => [
                    'version' => [
                        'id' => $version->id,
                        'version_number' => $version->version_number,
                        'description' => $version->description,
                        'created_at' => $version->created_at,
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create version: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get details of a specific version
     */
    public function show(Component $component, ComponentVersion $version): JsonResponse
    {
        if ($version->component_id !== $component->id) {
            return response()->json([
                'success' => false,
                'message' => 'Version does not belong to this component',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'version' => [
                    'id' => $version->id,
                    'version_number' => $version->version_number,
                    'config' => $version->config,
                    'metadata' => $version->metadata,
                    'changes' => $version->changes,
                    'description' => $version->description,
                    'created_by' => $version->creator?->name,
                    'created_at' => $version->created_at,
                    'is_latest' => $version->is_latest,
                ],
            ],
        ]);
    }

    /**
     * Restore component to a specific version
     */
    public function restore(Component $component, ComponentVersion $version): JsonResponse
    {
        if ($version->component_id !== $component->id) {
            return response()->json([
                'success' => false,
                'message' => 'Version does not belong to this component',
            ], 404);
        }

        try {
            $restoredComponent = $this->versionService->restoreToVersion($component, $version);

            return response()->json([
                'success' => true,
                'message' => "Component restored to version {$version->version_number}",
                'data' => [
                    'component' => [
                        'id' => $restoredComponent->id,
                        'name' => $restoredComponent->name,
                        'version' => $restoredComponent->version,
                        'updated_at' => $restoredComponent->updated_at,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore version: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Compare two versions and generate diff
     */
    public function compare(Component $component, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_version' => 'required|integer|exists:component_versions,version_number',
            'to_version' => 'required|integer|exists:component_versions,version_number',
            'format' => 'nullable|string|in:standard,grapejs',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $fromVersion = $component->versions()
                ->where('version_number', $request->input('from_version'))
                ->firstOrFail();
            
            $toVersion = $component->versions()
                ->where('version_number', $request->input('to_version'))
                ->firstOrFail();

            $format = $request->input('format', 'standard');
            
            if ($format === 'grapejs') {
                $diff = $this->versionService->generateGrapeJSDiff($fromVersion, $toVersion);
            } else {
                $diff = $this->versionService->generateDiff($fromVersion, $toVersion);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'diff' => $diff,
                    'format' => $format,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate diff: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export component with version history
     */
    public function export(Component $component, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'format' => 'nullable|string|in:json,grapejs',
            'include_versions' => 'nullable|boolean',
            'include_analytics' => 'nullable|boolean',
            'file_format' => 'nullable|string|in:json,zip',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $options = [
                'format' => $request->input('format', 'grapejs'),
                'include_versions' => $request->input('include_versions', true),
                'include_analytics' => $request->input('include_analytics', false),
            ];

            $fileFormat = $request->input('file_format', 'json');
            
            if ($fileFormat === 'json') {
                $exportData = $this->exportImportService->exportComponent($component, $options);
                
                return response()->json([
                    'success' => true,
                    'data' => $exportData,
                ]);
            } else {
                $filePath = $this->exportImportService->exportToFile($component, $fileFormat);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Component exported to file',
                    'data' => [
                        'file_path' => $filePath,
                        'download_url' => url("api/components/{$component->id}/download/{$filePath}"),
                    ],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export component: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import component from export data
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'export_data' => 'required|array',
            'overwrite_existing' => 'nullable|boolean',
            'preserve_ids' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $options = [
                'overwrite_existing' => $request->input('overwrite_existing', false),
                'preserve_ids' => $request->input('preserve_ids', false),
                'tenant_id' => auth()->user()->tenant_id,
            ];

            $component = $this->exportImportService->importComponent(
                $request->input('export_data'),
                $options
            );

            return response()->json([
                'success' => true,
                'message' => 'Component imported successfully',
                'data' => [
                    'component' => [
                        'id' => $component->id,
                        'name' => $component->name,
                        'slug' => $component->slug,
                        'category' => $component->category,
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import component: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create component template from GrapeJS configuration
     */
    public function createTemplate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'grapejs_data' => 'required|array',
            'template_info' => 'required|array',
            'template_info.name' => 'required|string|max:255',
            'template_info.category' => 'required|string|in:hero,forms,testimonials,statistics,ctas,media',
            'template_info.type' => 'nullable|string|max:100',
            'template_info.description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $component = $this->exportImportService->createTemplateFromGrapeJS(
                $request->input('grapejs_data'),
                $request->input('template_info')
            );

            return response()->json([
                'success' => true,
                'message' => 'Template created successfully from GrapeJS configuration',
                'data' => [
                    'component' => [
                        'id' => $component->id,
                        'name' => $component->name,
                        'slug' => $component->slug,
                        'category' => $component->category,
                        'type' => $component->type,
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create template: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze component performance
     */
    public function analyzePerformance(Component $component): JsonResponse
    {
        try {
            $analysis = $this->performanceService->analyzeComponentPerformance($component);

            return response()->json([
                'success' => true,
                'data' => $analysis,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze performance: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get performance trends
     */
    public function performanceTrends(Component $component, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $days = $request->input('days', 30);
            $trends = $this->performanceService->getPerformanceTrends($component, $days);

            return response()->json([
                'success' => true,
                'data' => [
                    'trends' => $trends,
                    'period_days' => $days,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get performance trends: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Compare performance between versions
     */
    public function comparePerformance(Component $component, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'version1' => 'required|integer|exists:component_versions,version_number',
            'version2' => 'required|integer|exists:component_versions,version_number',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $version1 = $component->versions()
                ->where('version_number', $request->input('version1'))
                ->firstOrFail();
            
            $version2 = $component->versions()
                ->where('version_number', $request->input('version2'))
                ->firstOrFail();

            $comparison = $this->performanceService->compareVersionPerformance($version1, $version2);

            return response()->json([
                'success' => true,
                'data' => $comparison,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to compare performance: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create backup
     */
    public function createBackup(Component $component, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|string|in:full,config_only,versions_only',
            'include_analytics' => 'nullable|boolean',
            'storage' => 'nullable|string|in:local,s3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $options = [
                'type' => $request->input('type', 'full'),
                'include_analytics' => $request->input('include_analytics', true),
                'storage' => $request->input('storage', 'local'),
            ];

            $backup = $this->backupService->createBackup($component, $options);

            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'data' => [
                    'backup_id' => $backup['backup_info']['id'],
                    'type' => $backup['backup_info']['type'],
                    'size' => $backup['metadata']['backup_size'],
                    'created_at' => $backup['backup_info']['created_at'],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List backups for component
     */
    public function listBackups(Component $component): JsonResponse
    {
        try {
            $backups = $this->backupService->listBackups($component);

            return response()->json([
                'success' => true,
                'data' => [
                    'backups' => $backups->values(),
                    'total_backups' => $backups->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list backups: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore from backup
     */
    public function restoreBackup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'backup_id' => 'required|string',
            'overwrite_existing' => 'nullable|boolean',
            'restore_versions' => 'nullable|boolean',
            'restore_analytics' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $options = [
                'overwrite_existing' => $request->input('overwrite_existing', false),
                'restore_versions' => $request->input('restore_versions', true),
                'restore_analytics' => $request->input('restore_analytics', false),
            ];

            $component = $this->backupService->restoreFromBackup(
                $request->input('backup_id'),
                $options
            );

            return response()->json([
                'success' => true,
                'message' => 'Component restored from backup successfully',
                'data' => [
                    'component' => [
                        'id' => $component->id,
                        'name' => $component->name,
                        'slug' => $component->slug,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore from backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Migrate component to new GrapeJS format
     */
    public function migrate(Component $component, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'target_version' => 'required|string',
            'migration_type' => 'nullable|string|in:grapejs_format,config_schema,feature_update',
            'schema_changes' => 'nullable|array',
            'new_features' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $migrationType = $request->input('migration_type', 'grapejs_format');
            
            switch ($migrationType) {
                case 'grapejs_format':
                    $migratedComponent = $this->migrationService->migrateToGrapeJSFormat(
                        $component,
                        $request->input('target_version')
                    );
                    break;
                
                case 'config_schema':
                    $migratedComponent = $this->migrationService->migrateConfigurationSchema(
                        $component,
                        $request->input('schema_changes', [])
                    );
                    break;
                
                case 'feature_update':
                    $migratedComponent = $this->migrationService->updateForNewGrapeJSFeatures(
                        $component,
                        $request->input('new_features', [])
                    );
                    break;
                
                default:
                    throw new \Exception("Unknown migration type: {$migrationType}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Component migrated successfully',
                'data' => [
                    'component' => [
                        'id' => $migratedComponent->id,
                        'name' => $migratedComponent->name,
                        'version' => $migratedComponent->version,
                        'updated_at' => $migratedComponent->updated_at,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to migrate component: ' . $e->getMessage(),
            ], 500);
        }
    }
}
