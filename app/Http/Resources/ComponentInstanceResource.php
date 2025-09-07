<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComponentInstanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'component_id' => $this->component_id,
            'page_type' => $this->page_type,
            'page_id' => $this->page_id,
            'position' => $this->position,
            'custom_config' => $this->custom_config,

            // Computed properties
            'merged_config' => $this->when(
                $request->query('include_merged_config'),
                fn() => $this->getMergedConfig()
            ),

            // Preview data
            'preview_data' => $this->when(
                $request->query('include_preview'),
                fn() => $this->generatePreview()
            ),

            // Render data
            'render_data' => $this->when(
                $request->query('include_render'),
                fn() => $this->render()
            ),

            // Validation status
            'is_valid' => $this->validateCustomConfig(),
            'validation_errors' => $this->when(
                !$this->validateCustomConfig(),
                fn() => ['Custom configuration validation failed']
            ),

            // Relationships
            'component' => new ComponentResource($this->whenLoaded('component')),
            'page' => $this->whenLoaded('page'),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Additional metadata
            'meta' => [
                'has_custom_config' => !empty($this->custom_config),
                'config_keys' => array_keys($this->custom_config ?? []),
                'page_context' => "{$this->page_type}:{$this->page_id}",
                'can_move_up' => $this->position > 0,
                'can_move_down' => $this->canMoveDown(),
                'is_first' => $this->position === 0,
                'is_last' => $this->isLastPosition(),
            ]
        ];
    }

    /**
     * Check if instance can move down
     */
    private function canMoveDown(): bool
    {
        $maxPosition = static::where('page_type', $this->page_type)
            ->where('page_id', $this->page_id)
            ->max('position');

        return $this->position < $maxPosition;
    }

    /**
     * Check if this is the last position
     */
    private function isLastPosition(): bool
    {
        $maxPosition = static::where('page_type', $this->page_type)
            ->where('page_id', $this->page_id)
            ->max('position');

        return $this->position === $maxPosition;
    }
}
