<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Template Preview API Resource
 *
 * Formats template preview data for consistent API responses.
 * Includes conditional data based on request parameters.
 */
class TemplatePreviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'template_id' => $this->resource['template_id'] ?? null,
            'name' => $this->resource['template_name'] ?? 'Unknown Template',
            'device_mode' => $this->resource['device_mode'] ?? 'desktop',
            'viewport' => $this->resource['viewport'] ?? [],
            'cache_hash' => $this->resource['cache_hash'] ?? null,
            'generated_at' => $this->resource['generated_at'] ?? now()->toISOString(),
        ];

        // Include compiled content based on request parameters
        if ($request->boolean('include_html', true)) {
            $data['compiled_html'] = $this->resource['compiled_html'] ?? '';
        }

        if ($request->boolean('include_css', true)) {
            $data['compiled_css'] = $this->resource['responsive_styles'] ?? '';
        }

        if ($request->boolean('include_js', false)) {
            $data['compiled_js'] = $this->resource['compiled_js'] ?? '';
        }

        if ($request->boolean('include_config', false)) {
            $data['compiled_config'] = $this->resource['config'] ?? [];
        }

        // Include preview URL when requested
        if ($request->boolean('include_url', true)) {
            $data['preview_url'] = $this->resource['preview_url'] ?? '';
        }

        // Include performance metrics when requested
        if ($request->boolean('include_performance', false)) {
            $data['performance_metrics'] = $this->resource['performance_metrics'] ?? [];
        }

        // Include metadata
        if ($request->boolean('include_metadata', true)) {
            $data['metadata'] = [
                'tenant_id' => $this->resource['metadata']['tenant_id'] ?? null,
                'category' => $this->resource['metadata']['category'] ?? null,
                'audience_type' => $this->resource['metadata']['audience_type'] ?? null,
                'is_active' => $this->resource['metadata']['is_active'] ?? false,
                'cache_used' => $request->boolean('cache_used', true),
                'is_responsive' => $this->isResponsive(),
                'has_custom_css' => !empty($this->resource['responsive_styles']),
                'has_custom_js' => !empty($this->resource['compiled_js']),
            ];
        }

        // Include breakpoint information for responsive previews
        if ($this->resource['device_mode'] !== 'desktop' && $request->boolean('include_breakpoints')) {
            $data['breakpoints'] = $this->getBreakpoints();
        }

        return $data;
    }

    /**
     * Check if the preview supports responsive design
     */
    protected function isResponsive(): bool
    {
        $css = $this->resource['responsive_styles'] ?? '';
        return str_contains($css, '@media') || str_contains($css, 'flex') || str_contains($css, 'grid');
    }

    /**
     * Get responsive breakpoints information
     */
    protected function getBreakpoints(): array
    {
        return [
            'mobile' => [
                'max_width' => 576,
                'description' => 'Mobile devices',
                'active' => $this->resource['device_mode'] === 'mobile'
            ],
            'tablet' => [
                'max_width' => 768,
                'description' => 'Tablet devices',
                'active' => $this->resource['device_mode'] === 'tablet'
            ],
            'desktop' => [
                'min_width' => 992,
                'description' => 'Desktop devices',
                'active' => $this->resource['device_mode'] === 'desktop'
            ],
            'large' => [
                'min_width' => 1200,
                'description' => 'Large desktop screens',
                'active' => false
            ],
        ];
    }
}