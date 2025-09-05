<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
            'audience_type' => $this->audience_type,
            'campaign_type' => $this->campaign_type,
            'structure' => $this->structure,
            'default_config' => $this->default_config,
            'preview_image' => $this->preview_image,
            'preview_url' => $this->preview_url,
            'version' => $this->version,
            'is_active' => $this->is_active,
            'is_premium' => $this->is_premium,
            'usage_count' => $this->usage_count,
            'last_used_at' => $this->last_used_at,
            'tags' => $this->tags ?? [],
            'performance_rating' => $this->getPerformanceRating(),
            'effective_structure' => $this->getEffectiveStructure(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Conditionally include creator and updater information
        if ($this->whenLoaded('creator')) {
            $data['creator'] = [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ];
        }

        if ($this->whenLoaded('updater')) {
            $data['updater'] = [
                'id' => $this->updater->id,
                'name' => $this->updater->name,
                'email' => $this->updater->email,
            ];
        }

        // Include usage statistics
        if ($request->has('with_stats')) {
            $data['usage_stats'] = $this->getUsageStats();
            $data['performance_stats'] = $this->getPerformanceStats();
            $data['landing_page_count'] = $this->whenLoaded('landingPages', function () {
                return $this->landingPages->count();
            }, 0);
        }

        return $data;
    }

    /**
     * Get performance rating based on metrics.
     *
     * @return string
     */
    protected function getPerformanceRating(): string
    {
        $metrics = $this->performance_metrics ?? [];

        $conversionRate = $metrics['conversion_rate'] ?? 0;
        $loadTime = $metrics['avg_load_time'] ?? 0;
        $usageCount = $this->usage_count ?? 0;

        // Rating algorithm
        $score = 0;

        // Conversion rate (0-50%)
        if ($conversionRate >= 5) $score += 30;
        elseif ($conversionRate >= 2) $score += 20;
        elseif ($conversionRate >= 1) $score += 10;

        // Load time (0-30%)
        if ($loadTime <= 1.5) $score += 25;
        elseif ($loadTime <= 2.5) $score += 15;
        elseif ($loadTime <= 4) $score += 5;

        // Usage popularity (0-20%)
        if ($usageCount >= 1000) $score += 20;
        elseif ($usageCount >= 500) $score += 15;
        elseif ($usageCount >= 100) $score += 10;
        elseif ($usageCount >= 25) $score += 5;

        if ($score >= 45) return 'excellent';
        if ($score >= 30) return 'good';
        if ($score >= 15) return 'average';
        return 'needs_improvement';
    }
}