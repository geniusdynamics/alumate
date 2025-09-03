<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComponentVersionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'component_id' => $this->component_id,
            'version_number' => $this->version_number,
            'config' => $this->config,
            'metadata' => $this->metadata,
            'changes' => $this->changes,
            'description' => $this->description,
            'created_by' => $this->created_by,

            // Computed properties
            'display_name' => $this->display_name,
            'is_latest' => $this->is_latest,

            // Change summary
            'change_summary' => $this->when(
                $request->query('include_change_summary'),
                fn() => $this->getChangeSummary()
            ),

            // Configuration diff
            'config_diff' => $this->when(
                $request->query('include_config_diff'),
                fn() => $this->getConfigDiff()
            ),

            // Relationships
            'component' => new ComponentResource($this->whenLoaded('component')),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Additional metadata
            'meta' => [
                'has_changes' => !empty($this->changes),
                'has_description' => !empty($this->description),
                'config_size' => strlen(json_encode($this->config ?? [])),
                'metadata_keys' => array_keys($this->metadata ?? []),
                'version_format' => $this->getVersionFormat(),
                'is_semantic_version' => $this->isSemanticVersion(),
            ]
        ];
    }

    /**
     * Get a summary of changes
     */
    private function getChangeSummary(): array
    {
        if (empty($this->changes)) {
            return [];
        }

        $summary = [];
        foreach ($this->changes as $change) {
            $summary[] = [
                'type' => $change['type'] ?? 'unknown',
                'field' => $change['field'] ?? 'unknown',
                'description' => $change['description'] ?? 'No description',
                'impact' => $change['impact'] ?? 'minor',
            ];
        }

        return $summary;
    }

    /**
     * Get configuration differences (simplified)
     */
    private function getConfigDiff(): array
    {
        // This would typically compare with the previous version
        // For now, we'll return a placeholder structure
        return [
            'added' => [],
            'removed' => [],
            'modified' => [],
            'unchanged' => count($this->config ?? []),
        ];
    }

    /**
     * Get version format type
     */
    private function getVersionFormat(): string
    {
        $version = $this->version_number;

        if (preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            return 'semantic';
        } elseif (preg_match('/^\d+\.\d+$/', $version)) {
            return 'simple';
        } elseif (preg_match('/^v\d+/', $version)) {
            return 'prefixed';
        } else {
            return 'custom';
        }
    }

    /**
     * Check if version follows semantic versioning
     */
    private function isSemanticVersion(): bool
    {
        return preg_match('/^\d+\.\d+\.\d+$/', $this->version_number);
    }
}
