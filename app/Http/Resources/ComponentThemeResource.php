<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComponentThemeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'is_default' => $this->is_default,
            'config' => $this->config,
            'tenant_id' => $this->tenant_id,
            
            // Computed properties
            'css_variables' => $this->generateCssVariables(),
            'accessibility_issues' => $this->checkAccessibility(),
            'preview_html' => $this->when(
                $request->query('include_preview'),
                fn() => $this->generatePreviewHtml()
            ),
            
            // Usage statistics
            'usage' => $this->when(
                $request->query('include_usage'),
                fn() => [
                    'component_count' => $this->components()->count(),
                    'page_count' => $this->getPageCount(),
                ]
            ),
            
            // Relationships
            'components' => ComponentResource::collection(
                $this->whenLoaded('components')
            ),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Additional metadata
            'meta' => [
                'color_count' => count($this->config['colors'] ?? []),
                'has_custom_fonts' => !empty($this->config['typography']['heading_font']),
                'animation_enabled' => !empty($this->config['animations']),
                'responsive_spacing' => $this->hasResponsiveSpacing(),
                'grapejs_compatible' => $this->isGrapeJSCompatible(),
            ]
        ];
    }

    /**
     * Get the count of pages using this theme
     */
    private function getPageCount(): int
    {
        return $this->components()
            ->with('instances')
            ->get()
            ->pluck('instances')
            ->flatten()
            ->pluck('page_id')
            ->unique()
            ->count();
    }

    /**
     * Check if theme has responsive spacing configuration
     */
    private function hasResponsiveSpacing(): bool
    {
        $spacing = $this->config['spacing'] ?? [];
        return count($spacing) >= 3; // small, base, large
    }

    /**
     * Check if theme is compatible with GrapeJS
     */
    private function isGrapeJSCompatible(): bool
    {
        $config = $this->config ?? [];
        
        // Check required colors
        $requiredColors = ['primary', 'background', 'text'];
        foreach ($requiredColors as $color) {
            if (!isset($config['colors'][$color])) {
                return false;
            }
        }
        
        // Check typography
        if (!isset($config['typography']['font_family'])) {
            return false;
        }
        
        // Check spacing
        if (!isset($config['spacing']['base'])) {
            return false;
        }
        
        return true;
    }
}