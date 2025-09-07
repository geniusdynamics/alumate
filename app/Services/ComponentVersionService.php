<?php

namespace App\Services;

use App\Models\Component;
use App\Models\ComponentVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ComponentVersionService
{
    public function __construct(
        private ComponentAnalyticsService $analyticsService
    ) {}

    /**
     * Create a new version of a component
     */
    public function createVersion(Component $component, array $changes = [], ?string $description = null): ComponentVersion
    {
        return DB::transaction(function () use ($component, $changes, $description) {
            $latestVersion = $this->getLatestVersion($component);
            $newVersionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;

            $version = ComponentVersion::create([
                'component_id' => $component->id,
                'version_number' => $newVersionNumber,
                'config' => $component->config,
                'metadata' => $component->metadata,
                'changes' => $changes,
                'description' => $description,
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            Log::info('Component version created', [
                'component_id' => $component->id,
                'version_number' => $newVersionNumber,
                'user_id' => auth()->id(),
            ]);

            return $version;
        });
    }

    /**
     * Get the latest version of a component
     */
    public function getLatestVersion(Component $component): ?ComponentVersion
    {
        return $component->versions()->latest('version_number')->first();
    }

    /**
     * Get all versions of a component
     */
    public function getVersionHistory(Component $component): Collection
    {
        return $component->versions()
            ->with('creator')
            ->orderBy('version_number', 'desc')
            ->get();
    }

    /**
     * Compare two component versions and generate diff
     */
    public function generateDiff(ComponentVersion $fromVersion, ComponentVersion $toVersion): array
    {
        $fromConfig = $fromVersion->config ?? [];
        $toConfig = $toVersion->config ?? [];

        return [
            'version_from' => $fromVersion->version_number,
            'version_to' => $toVersion->version_number,
            'config_diff' => $this->deepArrayDiff($fromConfig, $toConfig),
            'metadata_diff' => $this->deepArrayDiff(
                $fromVersion->metadata ?? [],
                $toVersion->metadata ?? []
            ),
            'changes' => $toVersion->changes ?? [],
            'created_at' => $toVersion->created_at,
        ];
    }

    /**
     * Restore a component to a specific version
     */
    public function restoreToVersion(Component $component, ComponentVersion $version): Component
    {
        return DB::transaction(function () use ($component, $version) {
            // Create a backup of current state before restoring
            $this->createVersion($component, ['action' => 'backup_before_restore'], 'Backup before restore');

            // Update component with version data
            $component->update([
                'config' => $version->config,
                'metadata' => $version->metadata,
            ]);

            // Create new version entry for the restore
            $this->createVersion($component, [
                'action' => 'restore',
                'restored_from_version' => $version->version_number,
            ], "Restored from version {$version->version_number}");

            Log::info('Component restored to version', [
                'component_id' => $component->id,
                'restored_to_version' => $version->version_number,
                'user_id' => auth()->id(),
            ]);

            return $component->fresh();
        });
    }

    /**
     * Generate GrapeJS-compatible diff visualization
     */
    public function generateGrapeJSDiff(ComponentVersion $fromVersion, ComponentVersion $toVersion): array
    {
        $diff = $this->generateDiff($fromVersion, $toVersion);

        return [
            'type' => 'component_diff',
            'from_version' => $fromVersion->version_number,
            'to_version' => $toVersion->version_number,
            'visual_changes' => $this->extractVisualChanges($diff['config_diff']),
            'structural_changes' => $this->extractStructuralChanges($diff['config_diff']),
            'style_changes' => $this->extractStyleChanges($diff['config_diff']),
            'content_changes' => $this->extractContentChanges($diff['config_diff']),
            'grapejs_blocks' => $this->generateGrapeJSBlocks($fromVersion, $toVersion),
        ];
    }

    /**
     * Deep array diff for nested configurations
     */
    private function deepArrayDiff(array $array1, array $array2): array
    {
        $diff = [];

        // Check for added or modified keys
        foreach ($array2 as $key => $value) {
            if (!array_key_exists($key, $array1)) {
                $diff['added'][$key] = $value;
            } elseif (is_array($value) && is_array($array1[$key])) {
                $nestedDiff = $this->deepArrayDiff($array1[$key], $value);
                if (!empty($nestedDiff)) {
                    $diff['modified'][$key] = $nestedDiff;
                }
            } elseif ($array1[$key] !== $value) {
                $diff['modified'][$key] = [
                    'from' => $array1[$key],
                    'to' => $value,
                ];
            }
        }

        // Check for removed keys
        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key, $array2)) {
                $diff['removed'][$key] = $value;
            }
        }

        return $diff;
    }

    /**
     * Extract visual changes for GrapeJS diff
     */
    private function extractVisualChanges(array $configDiff): array
    {
        $visualKeys = ['colors', 'fonts', 'spacing', 'layout', 'dimensions'];
        $changes = [];

        foreach ($visualKeys as $key) {
            if (isset($configDiff['modified'][$key]) || isset($configDiff['added'][$key]) || isset($configDiff['removed'][$key])) {
                $changes[$key] = $configDiff['modified'][$key] ?? $configDiff['added'][$key] ?? $configDiff['removed'][$key];
            }
        }

        return $changes;
    }

    /**
     * Extract structural changes for GrapeJS diff
     */
    private function extractStructuralChanges(array $configDiff): array
    {
        $structuralKeys = ['components', 'blocks', 'hierarchy', 'relationships'];
        $changes = [];

        foreach ($structuralKeys as $key) {
            if (isset($configDiff['modified'][$key]) || isset($configDiff['added'][$key]) || isset($configDiff['removed'][$key])) {
                $changes[$key] = $configDiff['modified'][$key] ?? $configDiff['added'][$key] ?? $configDiff['removed'][$key];
            }
        }

        return $changes;
    }

    /**
     * Extract style changes for GrapeJS diff
     */
    private function extractStyleChanges(array $configDiff): array
    {
        $styleKeys = ['css', 'classes', 'styles', 'theme'];
        $changes = [];

        foreach ($styleKeys as $key) {
            if (isset($configDiff['modified'][$key]) || isset($configDiff['added'][$key]) || isset($configDiff['removed'][$key])) {
                $changes[$key] = $configDiff['modified'][$key] ?? $configDiff['added'][$key] ?? $configDiff['removed'][$key];
            }
        }

        return $changes;
    }

    /**
     * Extract content changes for GrapeJS diff
     */
    private function extractContentChanges(array $configDiff): array
    {
        $contentKeys = ['text', 'content', 'data', 'props'];
        $changes = [];

        foreach ($contentKeys as $key) {
            if (isset($configDiff['modified'][$key]) || isset($configDiff['added'][$key]) || isset($configDiff['removed'][$key])) {
                $changes[$key] = $configDiff['modified'][$key] ?? $configDiff['added'][$key] ?? $configDiff['removed'][$key];
            }
        }

        return $changes;
    }

    /**
     * Generate GrapeJS blocks for diff visualization
     */
    private function generateGrapeJSBlocks(ComponentVersion $fromVersion, ComponentVersion $toVersion): array
    {
        return [
            'from_block' => $this->convertVersionToGrapeJSBlock($fromVersion),
            'to_block' => $this->convertVersionToGrapeJSBlock($toVersion),
        ];
    }

    /**
     * Convert component version to GrapeJS block format
     */
    private function convertVersionToGrapeJSBlock(ComponentVersion $version): array
    {
        $config = $version->config ?? [];

        return [
            'id' => "component-{$version->component_id}-v{$version->version_number}",
            'label' => $config['name'] ?? 'Component',
            'category' => $config['category'] ?? 'general',
            'content' => $config['content'] ?? '',
            'attributes' => $config['attributes'] ?? [],
            'version' => $version->version_number,
        ];
    }
}