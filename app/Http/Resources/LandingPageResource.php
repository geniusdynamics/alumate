<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LandingPageResource extends JsonResource
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
            'config' => $this->config,
            'brand_config' => $this->brand_config,
            'audience_type' => $this->audience_type,
            'campaign_type' => $this->campaign_type,
            'category' => $this->category,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'version' => $this->version,
            'usage_count' => $this->usage_count,
            'conversion_count' => $this->conversion_count,
            'preview_url' => $this->preview_url,
            'public_url' => $this->public_url,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'seo_keywords' => $this->seo_keywords ?? [],
            'social_image' => $this->social_image,
            'tracking_id' => $this->tracking_id,
            'favicon_url' => $this->favicon_url,
            'custom_css' => $this->custom_css,
            'custom_js' => $this->custom_js,
            'is_published' => $this->isPublished(),
            'is_public' => $this->isPublic(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Conditionally include template and user information
        if ($this->whenLoaded('template')) {
            $data['template'] = [
                'id' => $this->template->id,
                'name' => $this->template->name,
                'category' => $this->template->category,
                'audience_type' => $this->template->audience_type,
                'campaign_type' => $this->template->campaign_type,
                'is_premium' => $this->template->is_premium,
                'previewImage' => $this->template->preview_image,
                'usage_count' => $this->template->usage_count,
            ];
        }

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

        // Include performance stats when requested
        if ($request->has('with_stats')) {
            $data['performance_stats'] = $this->getPerformanceStats();
            $data['usage_stats'] = $this->getUsageStats();
            $data['submission_count'] = $this->whenLoaded('submissions', function () {
                return $this->submissions->count();
            }, 0);
        }

        // Include SEO metadata
        if ($request->has('with_seo')) {
            $data['seo_metadata'] = $this->getSEOMetadata();
        }

        // Include public URLs
        $data['urls'] = [
            'preview' => $this->getFullPreviewUrl(),
            'public' => $this->getFullPublicUrl(),
        ];

        return $data;
    }

    /**
     * Get usage statistics with derived metrics
     *
     * @return array
     */
    protected function getUsageStats(): array
    {
        $stats = [
            'views' => $this->usage_count,
            'conversions' => $this->conversion_count,
            'conversion_rate' => $this->usage_count > 0
                ? round(($this->conversion_count / $this->usage_count) * 100, 2)
                : 0,
        ];

        // Calculate performance rating
        $stats['performance_rating'] = $this->calculatePerformanceRating($stats['conversion_rate']);

        return $stats;
    }

    /**
     * Calculate performance rating based on conversion rate
     *
     * @param float $conversionRate
     * @return string
     */
    protected function calculatePerformanceRating(float $conversionRate): string
    {
        return match (true) {
            $conversionRate >= 5 => 'excellent',
            $conversionRate >= 2 => 'good',
            $conversionRate >= 1 => 'average',
            default => 'needs_improvement',
        };
    }
}