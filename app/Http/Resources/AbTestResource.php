<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A/B Test Resource
 *
 * API resource for A/B test data transformation
 */
class AbTestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'template_id' => $this->template_id,
            'name' => $this->name,
            'description' => $this->description,
            'variants' => $this->variants,
            'status' => $this->status,
            'goal_metric' => $this->goal_metric,
            'confidence_threshold' => $this->confidence_threshold,
            'sample_size_per_variant' => $this->sample_size_per_variant,
            'traffic_distribution' => $this->traffic_distribution,
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'results' => $this->results,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Computed properties
            'is_running' => $this->isRunning(),
            'has_significance' => $this->hasStatisticalSignificance(),
            'winning_variant' => $this->getWinningVariant(),
            'current_traffic_distribution' => $this->getCurrentTrafficDistribution(),

            // Relationships
            'template' => $this->whenLoaded('template', function () {
                return [
                    'id' => $this->template->id,
                    'name' => $this->template->name,
                    'category' => $this->template->category,
                    'audience_type' => $this->template->audience_type
                ];
            }),

            'events_count' => $this->whenLoaded('events', function () {
                return $this->events->count();
            }),

            'events_summary' => $this->whenLoaded('events', function () {
                $events = $this->events;
                return [
                    'total' => $events->count(),
                    'by_variant' => $events->groupBy('variant_id')->map->count(),
                    'by_type' => $events->groupBy('event_type')->map->count(),
                    'unique_sessions' => $events->pluck('session_id')->unique()->count()
                ];
            })
        ];
    }
}