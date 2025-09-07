<?php

namespace App\Services;

use App\Models\Component;
use App\Models\ComponentVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ComponentMigrationService
{
    public function __construct(
        private ComponentVersionService $versionService,
        private ComponentBackupRecoveryService $backupService
    ) {}

    /**
     * Migrate component to new GrapeJS format version
     */
    public function migrateToGrapeJSFormat(Component $component, string $targetVersion): Component
    {
        return DB::transaction(function () use ($component, $targetVersion) {
            // Create backup before migration
            $this->backupService->createAutomaticBackup($component, 'grapejs_format_migration');

            // Get current format version
            $currentVersion = $component->getConfigValue('grapejs_format_version', '1.0.0');
            
            if (version_compare($currentVersion, $targetVersion, '>=')) {
                throw new \Exception("Component is already at or above target version {$targetVersion}");
            }

            // Apply migrations step by step
            $migrationPath = $this->getMigrationPath($currentVersion, $targetVersion);
            
            foreach ($migrationPath as $step) {
                $component = $this->applyMigrationStep($component, $step);
            }

            // Update format version
            $component->setConfigValue('grapejs_format_version', $targetVersion);
            $component->save();

            // Create version entry for migration
            $this->versionService->createVersion($component, [
                'action' => 'grapejs_format_migration',
                'from_version' => $currentVersion,
                'to_version' => $targetVersion,
                'migration_path' => $migrationPath,
            ], "Migrated GrapeJS format from {$currentVersion} to {$targetVersion}");

            Log::info('Component migrated to new GrapeJS format', [
                'component_id' => $component->id,
                'from_version' => $currentVersion,
                'to_version' => $targetVersion,
            ]);

            return $component;
        });
    }

    /**
     * Migrate component configuration schema
     */
    public function migrateConfigurationSchema(Component $component, array $schemaChanges): Component
    {
        return DB::transaction(function () use ($component, $schemaChanges) {
            // Create backup before migration
            $this->backupService->createAutomaticBackup($component, 'config_schema_migration');

            $originalConfig = $component->config ?? [];
            $migratedConfig = $this->applySchemaChanges($originalConfig, $schemaChanges);

            // Validate migrated configuration
            $validationResult = $this->validateMigratedConfig($migratedConfig, $component->category);
            
            if (!$validationResult['valid']) {
                throw new \Exception('Migration resulted in invalid configuration: ' . implode(', ', $validationResult['errors']));
            }

            // Update component configuration
            $component->config = $migratedConfig;
            $component->save();

            // Create version entry for migration
            $this->versionService->createVersion($component, [
                'action' => 'config_schema_migration',
                'schema_changes' => $schemaChanges,
                'validation_warnings' => $validationResult['warnings'] ?? [],
            ], 'Configuration schema migration');

            Log::info('Component configuration schema migrated', [
                'component_id' => $component->id,
                'schema_changes' => count($schemaChanges),
            ]);

            return $component;
        });
    }

    /**
     * Migrate multiple components in batch
     */
    public function batchMigrateComponents(Collection $components, array $migrationOptions): array
    {
        $results = [
            'total_components' => $components->count(),
            'successful_migrations' => 0,
            'failed_migrations' => 0,
            'errors' => [],
            'warnings' => [],
        ];

        foreach ($components as $component) {
            try {
                $this->migrateComponent($component, $migrationOptions);
                $results['successful_migrations']++;
            } catch (\Exception $e) {
                $results['failed_migrations']++;
                $results['errors'][] = [
                    'component_id' => $component->id,
                    'component_name' => $component->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Batch component migration completed', $results);

        return $results;
    }

    /**
     * Migrate component from legacy format
     */
    public function migrateLegacyComponent(array $legacyData, int $tenantId): Component
    {
        return DB::transaction(function () use ($legacyData, $tenantId) {
            // Convert legacy data to current format
            $componentData = $this->convertLegacyData($legacyData);
            $componentData['tenant_id'] = $tenantId;

            // Create component
            $component = Component::create($componentData);

            // Create initial version
            $this->versionService->createVersion($component, [
                'action' => 'legacy_migration',
                'legacy_format' => $legacyData['format'] ?? 'unknown',
                'migration_date' => now()->toISOString(),
            ], 'Migrated from legacy format');

            Log::info('Legacy component migrated', [
                'component_id' => $component->id,
                'legacy_format' => $legacyData['format'] ?? 'unknown',
            ]);

            return $component;
        });
    }

    /**
     * Update component for new GrapeJS features
     */
    public function updateForNewGrapeJSFeatures(Component $component, array $newFeatures): Component
    {
        return DB::transaction(function () use ($component, $newFeatures) {
            // Create backup before update
            $this->backupService->createAutomaticBackup($component, 'grapejs_features_update');

            $updatedConfig = $component->config ?? [];
            $updatedMetadata = $component->metadata ?? [];

            foreach ($newFeatures as $feature => $config) {
                switch ($feature) {
                    case 'responsive_breakpoints':
                        $updatedConfig = $this->addResponsiveBreakpoints($updatedConfig, $config);
                        break;
                    
                    case 'accessibility_enhancements':
                        $updatedConfig = $this->addAccessibilityEnhancements($updatedConfig, $config);
                        break;
                    
                    case 'performance_optimizations':
                        $updatedConfig = $this->addPerformanceOptimizations($updatedConfig, $config);
                        break;
                    
                    case 'new_style_manager':
                        $updatedMetadata = $this->updateStyleManagerConfig($updatedMetadata, $config);
                        break;
                    
                    case 'enhanced_traits':
                        $updatedMetadata = $this->updateTraitManagerConfig($updatedMetadata, $config);
                        break;
                }
            }

            // Update component
            $component->update([
                'config' => $updatedConfig,
                'metadata' => $updatedMetadata,
            ]);

            // Create version entry
            $this->versionService->createVersion($component, [
                'action' => 'grapejs_features_update',
                'new_features' => array_keys($newFeatures),
                'updated_at' => now()->toISOString(),
            ], 'Updated for new GrapeJS features');

            Log::info('Component updated for new GrapeJS features', [
                'component_id' => $component->id,
                'features' => array_keys($newFeatures),
            ]);

            return $component;
        });
    }

    /**
     * Rollback migration to previous version
     */
    public function rollbackMigration(Component $component, ?int $targetVersionNumber = null): Component
    {
        return DB::transaction(function () use ($component, $targetVersionNumber) {
            // Find target version
            if ($targetVersionNumber) {
                $targetVersion = $component->versions()
                    ->where('version_number', $targetVersionNumber)
                    ->first();
            } else {
                // Get the version before the last migration
                $targetVersion = $component->versions()
                    ->where('changes->action', '!=', 'migration_rollback')
                    ->orderBy('version_number', 'desc')
                    ->skip(1)
                    ->first();
            }

            if (!$targetVersion) {
                throw new \Exception('No suitable version found for rollback');
            }

            // Create backup before rollback
            $this->backupService->createAutomaticBackup($component, 'pre_migration_rollback');

            // Restore to target version
            $component = $this->versionService->restoreToVersion($component, $targetVersion);

            // Create version entry for rollback
            $this->versionService->createVersion($component, [
                'action' => 'migration_rollback',
                'rolled_back_to_version' => $targetVersion->version_number,
                'rollback_reason' => 'Migration rollback requested',
            ], "Rolled back migration to version {$targetVersion->version_number}");

            Log::info('Migration rolled back', [
                'component_id' => $component->id,
                'target_version' => $targetVersion->version_number,
            ]);

            return $component;
        });
    }

    /**
     * Get migration path between versions
     */
    private function getMigrationPath(string $fromVersion, string $toVersion): array
    {
        $migrations = [
            '1.0.0' => [
                '1.1.0' => 'add_responsive_config',
                '2.0.0' => 'major_format_update',
            ],
            '1.1.0' => [
                '1.2.0' => 'add_accessibility_metadata',
                '2.0.0' => 'major_format_update',
            ],
            '1.2.0' => [
                '2.0.0' => 'major_format_update',
            ],
        ];

        // Simple path finding - in a real implementation, this would be more sophisticated
        $path = [];
        $currentVersion = $fromVersion;

        while (version_compare($currentVersion, $toVersion, '<')) {
            $nextStep = null;
            
            if (isset($migrations[$currentVersion])) {
                foreach ($migrations[$currentVersion] as $nextVersion => $migration) {
                    if (version_compare($nextVersion, $toVersion, '<=')) {
                        $nextStep = [
                            'from' => $currentVersion,
                            'to' => $nextVersion,
                            'migration' => $migration,
                        ];
                        $currentVersion = $nextVersion;
                        break;
                    }
                }
            }

            if (!$nextStep) {
                throw new \Exception("No migration path found from {$currentVersion} to {$toVersion}");
            }

            $path[] = $nextStep;
        }

        return $path;
    }

    /**
     * Apply a single migration step
     */
    private function applyMigrationStep(Component $component, array $step): Component
    {
        $migration = $step['migration'];
        $config = $component->config ?? [];

        switch ($migration) {
            case 'add_responsive_config':
                $config = $this->addResponsiveConfigMigration($config);
                break;
            
            case 'add_accessibility_metadata':
                $config = $this->addAccessibilityMetadataMigration($config);
                break;
            
            case 'major_format_update':
                $config = $this->majorFormatUpdateMigration($config);
                break;
            
            default:
                throw new \Exception("Unknown migration: {$migration}");
        }

        $component->config = $config;
        $component->save();

        return $component;
    }

    /**
     * Apply schema changes to configuration
     */
    private function applySchemaChanges(array $config, array $schemaChanges): array
    {
        $migratedConfig = $config;

        foreach ($schemaChanges as $change) {
            switch ($change['type']) {
                case 'rename_field':
                    $migratedConfig = $this->renameField($migratedConfig, $change['from'], $change['to']);
                    break;
                
                case 'move_field':
                    $migratedConfig = $this->moveField($migratedConfig, $change['from'], $change['to']);
                    break;
                
                case 'transform_field':
                    $migratedConfig = $this->transformField($migratedConfig, $change['field'], $change['transformer']);
                    break;
                
                case 'add_default':
                    $migratedConfig = $this->addDefaultValue($migratedConfig, $change['field'], $change['default']);
                    break;
                
                case 'remove_field':
                    $migratedConfig = $this->removeField($migratedConfig, $change['field']);
                    break;
            }
        }

        return $migratedConfig;
    }

    /**
     * Migrate a single component with options
     */
    private function migrateComponent(Component $component, array $options): Component
    {
        $migrationType = $options['type'] ?? 'grapejs_format';
        
        switch ($migrationType) {
            case 'grapejs_format':
                return $this->migrateToGrapeJSFormat($component, $options['target_version']);
            
            case 'config_schema':
                return $this->migrateConfigurationSchema($component, $options['schema_changes']);
            
            case 'feature_update':
                return $this->updateForNewGrapeJSFeatures($component, $options['new_features']);
            
            default:
                throw new \Exception("Unknown migration type: {$migrationType}");
        }
    }

    /**
     * Convert legacy data to current format
     */
    private function convertLegacyData(array $legacyData): array
    {
        $componentData = [
            'name' => $legacyData['title'] ?? $legacyData['name'] ?? 'Migrated Component',
            'slug' => Str::slug($legacyData['title'] ?? $legacyData['name'] ?? 'migrated-component'),
            'category' => $this->mapLegacyCategory($legacyData['type'] ?? 'general'),
            'type' => $legacyData['subtype'] ?? 'basic',
            'description' => $legacyData['description'] ?? 'Migrated from legacy format',
            'config' => $this->convertLegacyConfig($legacyData),
            'metadata' => [
                'migrated_from_legacy' => true,
                'legacy_format' => $legacyData['format'] ?? 'unknown',
                'migration_date' => now()->toISOString(),
            ],
            'version' => '1.0.0',
            'is_active' => $legacyData['active'] ?? true,
        ];

        return $componentData;
    }

    /**
     * Map legacy category to current category
     */
    private function mapLegacyCategory(string $legacyType): string
    {
        $mapping = [
            'header' => 'hero',
            'banner' => 'hero',
            'form' => 'forms',
            'contact' => 'forms',
            'testimonial' => 'testimonials',
            'review' => 'testimonials',
            'stats' => 'statistics',
            'metrics' => 'statistics',
            'button' => 'ctas',
            'link' => 'ctas',
            'image' => 'media',
            'video' => 'media',
            'gallery' => 'media',
        ];

        return $mapping[$legacyType] ?? 'general';
    }

    /**
     * Convert legacy configuration
     */
    private function convertLegacyConfig(array $legacyData): array
    {
        $config = [];

        // Convert common fields
        if (isset($legacyData['content'])) {
            $config['content'] = $legacyData['content'];
        }

        if (isset($legacyData['styles'])) {
            $config['styles'] = $this->convertLegacyStyles($legacyData['styles']);
        }

        if (isset($legacyData['settings'])) {
            $config = array_merge($config, $legacyData['settings']);
        }

        return $config;
    }

    /**
     * Convert legacy styles
     */
    private function convertLegacyStyles(array $legacyStyles): array
    {
        $styles = [];

        // Map legacy style properties to current format
        $styleMapping = [
            'background-color' => 'backgroundColor',
            'text-color' => 'color',
            'font-family' => 'fontFamily',
            'font-size' => 'fontSize',
        ];

        foreach ($legacyStyles as $property => $value) {
            $newProperty = $styleMapping[$property] ?? $property;
            $styles[$newProperty] = $value;
        }

        return $styles;
    }

    // Migration-specific methods

    private function addResponsiveConfigMigration(array $config): array
    {
        if (!isset($config['responsive'])) {
            $config['responsive'] = [
                'desktop' => [],
                'tablet' => [],
                'mobile' => [],
            ];
        }
        return $config;
    }

    private function addAccessibilityMetadataMigration(array $config): array
    {
        if (!isset($config['accessibility'])) {
            $config['accessibility'] = [
                'semanticTag' => 'div',
                'keyboardNavigation' => ['focusable' => false],
                'motionPreferences' => ['respectReducedMotion' => true],
            ];
        }
        return $config;
    }

    private function majorFormatUpdateMigration(array $config): array
    {
        // Implement major format changes
        $config['format_version'] = '2.0.0';
        
        // Restructure configuration for new format
        if (isset($config['old_structure'])) {
            $config['new_structure'] = $this->convertToNewStructure($config['old_structure']);
            unset($config['old_structure']);
        }

        return $config;
    }

    private function convertToNewStructure(array $oldStructure): array
    {
        // Convert old structure to new format
        return [
            'components' => $oldStructure['elements'] ?? [],
            'styles' => $oldStructure['css'] ?? [],
            'attributes' => $oldStructure['attrs'] ?? [],
        ];
    }

    // Schema change methods

    private function renameField(array $config, string $from, string $to): array
    {
        if (isset($config[$from])) {
            $config[$to] = $config[$from];
            unset($config[$from]);
        }
        return $config;
    }

    private function moveField(array $config, string $from, string $to): array
    {
        $value = data_get($config, $from);
        if ($value !== null) {
            data_set($config, $to, $value);
            data_forget($config, $from);
        }
        return $config;
    }

    private function transformField(array $config, string $field, callable $transformer): array
    {
        $value = data_get($config, $field);
        if ($value !== null) {
            data_set($config, $field, $transformer($value));
        }
        return $config;
    }

    private function addDefaultValue(array $config, string $field, mixed $default): array
    {
        if (!isset($config[$field])) {
            data_set($config, $field, $default);
        }
        return $config;
    }

    private function removeField(array $config, string $field): array
    {
        data_forget($config, $field);
        return $config;
    }

    // Feature update methods

    private function addResponsiveBreakpoints(array $config, array $breakpointConfig): array
    {
        $config['responsive']['breakpoints'] = $breakpointConfig;
        return $config;
    }

    private function addAccessibilityEnhancements(array $config, array $accessibilityConfig): array
    {
        $config['accessibility'] = array_merge(
            $config['accessibility'] ?? [],
            $accessibilityConfig
        );
        return $config;
    }

    private function addPerformanceOptimizations(array $config, array $performanceConfig): array
    {
        $config['performance'] = $performanceConfig;
        return $config;
    }

    private function updateStyleManagerConfig(array $metadata, array $styleConfig): array
    {
        $metadata['grapejs']['styleManager'] = $styleConfig;
        return $metadata;
    }

    private function updateTraitManagerConfig(array $metadata, array $traitConfig): array
    {
        $metadata['grapejs']['traitManager'] = $traitConfig;
        return $metadata;
    }

    /**
     * Validate migrated configuration
     */
    private function validateMigratedConfig(array $config, string $category): array
    {
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
        ];

        // Basic validation - in a real implementation, this would be more comprehensive
        if (empty($config)) {
            $validation['valid'] = false;
            $validation['errors'][] = 'Configuration is empty after migration';
        }

        // Category-specific validation
        switch ($category) {
            case 'hero':
                if (!isset($config['headline']) && !isset($config['title'])) {
                    $validation['warnings'][] = 'Hero component missing headline or title';
                }
                break;
            
            case 'forms':
                if (!isset($config['fields']) || empty($config['fields'])) {
                    $validation['warnings'][] = 'Form component has no fields defined';
                }
                break;
        }

        return $validation;
    }
}