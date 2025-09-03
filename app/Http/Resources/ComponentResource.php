<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComponentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'theme_id' => $this->theme_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'type' => $this->type,
            'description' => $this->description,
            'config' => $this->config,
            'metadata' => $this->metadata,
            'version' => $this->version,
            'is_active' => $this->is_active,
            'usage_count' => $this->usage_count,
            'last_used_at' => $this->last_used_at,
            
            // Computed properties
            'display_name' => $this->display_name,
            'formatted_config' => $this->formatted_config,
            
            // Preview data
            'preview_html' => $this->when(
                $request->query('include_preview'),
                fn() => $this->generatePreviewHtml()
            ),
            
            // Accessibility metadata
            'accessibility' => $this->when(
                $request->query('include_accessibility'),
                fn() => $this->getAccessibilityMetadata()
            ),
            
            // Responsive configuration
            'responsive_config' => $this->when(
                $request->query('include_responsive'),
                fn() => $this->getResponsiveConfig()
            ),
            
            // Usage statistics
            'usage_stats' => $this->when(
                $request->query('include_usage'),
                fn() => $this->getUsageStats()
            ),
            
            // Validation status
            'is_valid' => $this->validateConfig(),
            'validation_errors' => $this->when(
                !$this->validateConfig(),
                fn() => $this->getValidationErrors()
            ),
            
            // Relationships
            'theme' => new ComponentThemeResource($this->whenLoaded('theme')),
            'instances' => ComponentInstanceResource::collection(
                $this->whenLoaded('instances')
            ),
            'versions' => ComponentVersionResource::collection(
                $this->whenLoaded('versions')
            ),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Additional metadata
            'meta' => [
                'has_responsive_config' => $this->hasResponsiveConfig(),
                'has_accessibility_features' => $this->hasAccessibilityFeatures(),
                'is_mobile_optimized' => $this->isMobileOptimized(),
                'is_popular' => $this->usage_count > 10,
                'recently_used' => $this->last_used_at && $this->last_used_at->isAfter(now()->subDays(7)),
                'grapejs_compatible' => $this->isGrapeJSCompatible(),
                'category_display_name' => $this->getCategoryDisplayName(),
                'supported_features' => $this->getSupportedFeatures(),
            ]
        ];
    }

    /**
     * Get validation errors for the component
     */
    private function getValidationErrors(): array
    {
        // This would need to be implemented based on actual validation logic
        return [];
    }

    /**
     * Check if component is compatible with GrapeJS
     */
    private function isGrapeJSCompatible(): bool
    {
        $config = $this->config ?? [];
        
        // Check required properties
        if (empty($this->name)) {
            return false;
        }
        
        if (empty($this->category)) {
            return false;
        }
        
        // Check category-specific requirements
        switch ($this->category) {
            case 'hero':
                return !empty($config['headline']) && !empty($config['cta_text']);
            case 'forms':
                return !empty($config['fields']) && is_array($config['fields']);
            case 'testimonials':
                return !empty($config['testimonials']) && is_array($config['testimonials']);
            case 'statistics':
                return !empty($config['metrics']) && is_array($config['metrics']);
            case 'ctas':
                return !empty($config['buttons']) && is_array($config['buttons']);
            case 'media':
                return !empty($config['sources']) && is_array($config['sources']);
            default:
                return true;
        }
    }

    /**
     * Get category display name
     */
    private function getCategoryDisplayName(): string
    {
        $categoryNames = [
            'hero' => 'Hero Sections',
            'forms' => 'Forms',
            'testimonials' => 'Testimonials',
            'statistics' => 'Statistics',
            'ctas' => 'Call to Actions',
            'media' => 'Media'
        ];
        
        return $categoryNames[$this->category] ?? ucfirst($this->category);
    }

    /**
     * Get supported features for this component
     */
    private function getSupportedFeatures(): array
    {
        $baseFeatures = ['responsive_design', 'accessibility', 'theme_integration'];
        
        $categoryFeatures = match($this->category) {
            'hero' => ['background_media', 'cta_buttons', 'statistics_display'],
            'forms' => ['field_validation', 'crm_integration', 'conditional_logic'],
            'testimonials' => ['carousel_navigation', 'video_support', 'filtering'],
            'statistics' => ['counter_animations', 'chart_rendering', 'real_time_data'],
            'ctas' => ['conversion_tracking', 'ab_testing', 'personalization'],
            'media' => ['lazy_loading', 'lightbox', 'cdn_integration'],
            default => []
        };
        
        return array_merge($baseFeatures, $categoryFeatures);
    }

    /**
     * Generate preview HTML for the component
     */
    private function generatePreviewHtml(): string
    {
        // This would generate a preview based on the component configuration
        return "<div class='component-preview {$this->category}' data-component-id='{$this->id}'>
            <h3>{$this->name}</h3>
            <p>" . ($this->description ?: 'Component preview') . "</p>
        </div>";
    }
}