<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Landing Page Preview API Resource
 *
 * Formats landing page preview data for consistent API responses.
 * Includes conditional data based on request parameters.
 */
class LandingPagePreviewResource extends JsonResource
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
            'landing_page_id' => $this->resource['landing_page_id'] ?? null,
            'landing_page_name' => $this->resource['landing_page_name'] ?? 'Unknown Landing Page',
            'template_id' => $this->resource['template_id'] ?? null,
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
            $data['effective_config'] = $this->resource['config'] ?? [];
        }

        // Include custom overrides
        if ($request->boolean('include_custom', false)) {
            $data['custom_css'] = $this->resource['custom_css'] ?? '';
            $data['custom_js'] = $this->resource['custom_js'] ?? '';
        }

        // Include SEO data when requested
        if ($request->boolean('include_seo', false)) {
            $data['seo_metadata'] = $this->resource['seo_metadata'] ?? [];
        }

        // Include URLs when requested
        if ($request->boolean('include_urls', true)) {
            $data['public_url'] = $this->resource['public_url'] ?? '';
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
                'status' => $this->resource['metadata']['status'] ?? 'draft',
                'published_at' => $this->resource['metadata']['published_at'] ?? null,
                'version' => $this->resource['metadata']['version'] ?? 1,
                'cache_used' => $this->resource['cache_used'] ?? true,
                'is_responsive' => $this->isResponsive(),
                'has_custom_css' => !empty($this->resource['custom_css']),
                'has_custom_js' => !empty($this->resource['custom_js']),
                'is_published' => ($this->resource['metadata']['status'] ?? 'draft') === 'published',
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
        $customCss = $this->resource['custom_css'] ?? '';

        return str_contains($css, '@media') ||
               str_contains($customCss, '@media') ||
               str_contains($css, 'flex') ||
               str_contains($customCss, 'flex') ||
               str_contains($css, 'grid') ||
               str_contains($customCss, 'grid');
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