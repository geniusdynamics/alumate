<?php

namespace App\Services;

use App\Models\Template;
use App\Models\LandingPage;
use App\Models\BrandConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Template Preview and Rendering Service
 *
 * Handles real-time template compilation, preview generation, and mobile-responsive
 * preview modes for the Template Creation System.
 */
class TemplatePreviewService
{
    const CACHE_PREFIX = 'template_preview_';
    const CACHE_DURATION = 1800; // 30 minutes

    const DEVICE_MODES = [
        'desktop' => ['width' => 1920, 'height' => 1080],
        'tablet' => ['width' => 768, 'height' => 1024],
        'mobile' => ['width' => 375, 'height' => 667],
    ];

    const BREAKPOINTS = [
        'mobile' => 576,
        'tablet' => 768,
        'desktop' => 992,
        'large' => 1200,
    ];

    public function __construct()
    {
        // Constructor for future dependency injection if needed
    }

    /**
     * Generate real-time preview for a template
     */
    public function generateTemplatePreview(int $templateId, array $config = [], array $options = []): array
    {
        $cacheKey = $this->getCacheKey('template', $templateId, $config, $options);
        $deviceMode = $options['device_mode'] ?? 'desktop';

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($templateId, $config, $options, $deviceMode) {
            $startTime = microtime(true);

            try {
                Log::info("Generating template preview", [
                    'template_id' => $templateId,
                    'device_mode' => $deviceMode,
                    'tenant_id' => tenant()?->id
                ]);

                $template = $this->findTemplate($templateId);
                $compiledTemplate = $this->compileTemplate($template, $config);

                $preview = [
                    'template_id' => $template->id,
                    'template_name' => $template->name,
                    'device_mode' => $deviceMode,
                    'viewport' => self::DEVICE_MODES[$deviceMode] ?? self::DEVICE_MODES['desktop'],
                    'compiled_html' => $compiledTemplate['html'],
                    'compiled_css' => $compiledTemplate['css'],
                    'responsive_styles' => $this->generateResponsiveStyles($compiledTemplate, $deviceMode),
                    'preview_url' => $this->generatePreviewUrl($templateId, $config, $options),
                    'cache_hash' => md5(serialize([$templateId, $config, $options, $template->updated_at])),
                    'generated_at' => Carbon::now()->toISOString(),
                    'performance_metrics' => $this->calculatePreviewMetrics($startTime),
                    'metadata' => [
                        'tenant_id' => $template->tenant_id,
                        'category' => $template->category,
                        'audience_type' => $template->audience_type,
                        'is_active' => $template->is_active,
                    ]
                ];

                Log::info("Template preview generated successfully", [
                    'template_id' => $templateId,
                    'duration' => $preview['performance_metrics']['generation_time_ms'],
                    'cache_hash' => $preview['cache_hash']
                ]);

                return $preview;
            } catch (\Exception $e) {
                Log::error("Template preview generation failed", [
                    'template_id' => $templateId,
                    'device_mode' => $deviceMode,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Generate preview for a landing page
     */
    public function generateLandingPagePreview(int $landingPageId, array $options = []): array
    {
        $landingPage = $this->findLandingPage($landingPageId);

        $config = $landingPage->getEffectiveConfig();
        $deviceMode = $options['device_mode'] ?? 'desktop';

        $cacheKey = $this->getCacheKey('landing_page', $landingPageId, $config, $options);

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($landingPage, $config, $options, $deviceMode) {
            $startTime = microtime(true);

            try {
                Log::info("Generating landing page preview", [
                    'landing_page_id' => $landingPage->id,
                    'template_id' => $landingPage->template_id,
                    'device_mode' => $deviceMode
                ]);

                $templatePreview = $this->generateTemplatePreview($landingPage->template_id, $config, $options);

                $preview = array_merge($templatePreview, [
                    'landing_page_id' => $landingPage->id,
                    'landing_page_name' => $landingPage->name,
                    'status' => $landingPage->status,
                    'custom_css' => $landingPage->custom_css,
                    'custom_js' => $landingPage->custom_js,
                    'seo_metadata' => $landingPage->getSEOMetadata(),
                    'public_url' => $landingPage->getFullPublicUrl(),
                    'preview_url' => $landingPage->getFullPreviewUrl(),
                    'metadata' => array_merge($templatePreview['metadata'], [
                        'status' => $landingPage->status,
                        'published_at' => $landingPage->published_at?->toISOString(),
                        'version' => $landingPage->version,
                    ])
                ]);

                return $preview;
            } catch (\Exception $e) {
                Log::error("Landing page preview generation failed", [
                    'landing_page_id' => $landingPage->id,
                    'error' => $e->getMessage()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Generate multi-device preview for responsive testing
     */
    public function generateMultiDevicePreview(int $templateId, array $config = []): array
    {
        $cacheKey = $this->getCacheKey('multi_device', $templateId, $config);

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($templateId, $config) {
            $startTime = microtime(true);

            try {
                Log::info("Generating multi-device preview", ['template_id' => $templateId]);

                $multiDevicePreview = [
                    'template_id' => $templateId,
                    'generated_at' => Carbon::now()->toISOString(),
                    'devices' => []
                ];

                foreach (self::DEVICE_MODES as $device => $dimensions) {
                    $devicePreview = $this->generateTemplatePreview($templateId, $config, [
                        'device_mode' => $device,
                        'include_responsive' => true
                    ]);

                    $multiDevicePreview['devices'][$device] = [
                        'device' => $device,
                        'dimensions' => $dimensions,
                        'preview' => $devicePreview,
                        'breakpoints' => $this->getResponsiveBreakpoints($device),
                        'media_queries' => $this->generateMediaQueries($device)
                    ];
                }

                $multiDevicePreview['performance_metrics'] = $this->calculatePreviewMetrics($startTime);
                $multiDevicePreview['cache_hash'] = md5(serialize([$templateId, $config, microtime()]));

                Log::info("Multi-device preview generated", [
                    'template_id' => $templateId,
                    'cache_hash' => $multiDevicePreview['cache_hash']
                ]);

                return $multiDevicePreview;
            } catch (\Exception $e) {
                Log::error("Multi-device preview generation failed", [
                    'template_id' => $templateId,
                    'error' => $e->getMessage()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Get preview configuration options
     */
    public function getPreviewOptions(): array
    {
        return [
            'device_modes' => array_keys(self::DEVICE_MODES),
            'breakpoints' => self::BREAKPOINTS,
            'responsive_modes' => [
                'auto' => 'Automatic responsive behavior',
                'fixed' => 'Fixed width responsive',
                'fluid' => '100% fluid layout',
                'adaptive' => 'Adaptive breakpoints'
            ],
            'output_formats' => [
                'html' => 'Complete HTML document',
                'fragment' => 'HTML fragment only',
                'json' => 'Structured data only'
            ],
            'cache_options' => [
                'use_cache' => true,
                'cache_duration' => self::CACHE_DURATION,
                'force_refresh' => false
            ]
        ];
    }

    /**
     * Clear preview cache for a specific template
     */
    public function clearTemplateCache(int $templateId): bool
    {
        try {
            $pattern = self::CACHE_PREFIX . "template_{$templateId}_*";
            $this->clearCacheByPattern($pattern);

            Log::info("Template preview cache cleared", ['template_id' => $templateId]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to clear template cache", [
                'template_id' => $templateId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Clear all preview caches for a tenant
     */
    public function clearTenantCache(int $tenantId): bool
    {
        try {
            $pattern = self::CACHE_PREFIX . "*_{$tenantId}_*";
            $this->clearCacheByPattern($pattern);

            Log::info("Tenant preview cache cleared", ['tenant_id' => $tenantId]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to clear tenant cache", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Compile template with configuration
     */
    protected function compileTemplate(Template $template, array $config): array
    {
        try {
            $effectiveConfig = $this->mergeTemplateConfig($template, $config);

            return [
                'html' => $this->compileHtml($template, $effectiveConfig),
                'css' => $this->compileCss($template, $effectiveConfig),
                'js' => $this->compileJs($template, $effectiveConfig),
                'config' => $effectiveConfig,
            ];
        } catch (\Exception $e) {
            Log::error("Template compilation failed", [
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Merge template configuration with custom config
     */
    protected function mergeTemplateConfig(Template $template, array $customConfig): array
    {
        $defaultConfig = $template->default_config ?? [];
        $brandConfig = $customConfig['brand_config'] ?? [];

        return array_merge($defaultConfig, $brandConfig, $customConfig);
    }

    /**
     * Generate responsive styles for specific device mode
     */
    protected function generateResponsiveStyles(array $compiledTemplate, string $deviceMode): string
    {
        $baseCss = $compiledTemplate['css'] ?? '';

        return match ($deviceMode) {
            'mobile' => $this->generateMobileResponsiveCss($baseCss),
            'tablet' => $this->generateTabletResponsiveCss($baseCss),
            'desktop' => $this->generateDesktopResponsiveCss($baseCss),
            default => $baseCss
        };
    }

    /**
     * Generate mobile-responsive CSS
     */
    protected function generateMobileResponsiveCss(string $baseCss): string
    {
        $mobileOverrides = "
            @media (max-width: " . self::BREAKPOINTS['mobile'] . "px) {
                .container { max-width: 100%; padding: 0 15px; }
                .row { margin: 0 -15px; }
                .col { flex: 1 1 100%; padding: 0 15px; margin-bottom: 1rem; }
                .hero-section { min-height: 50vh; padding: 2rem 0; }
                .hero-title { font-size: 1.5rem; line-height: 1.3; }
                .hero-subtitle { font-size: 1rem; line-height: 1.4; }
                .cta-button { width: 100%; margin-bottom: 1rem; }
            }
        ";

        return $baseCss . "\n\n" . $mobileOverrides;
    }

    /**
     * Generate tablet-responsive CSS
     */
    protected function generateTabletResponsiveCss(string $baseCss): string
    {
        $tabletOverrides = "
            @media (max-width: " . self::BREAKPOINTS['tablet'] . "px) {
                .container { max-width: 720px; }
                .col-2 { flex: 1 1 50%; }
                .col-3 { flex: 1 1 50%; }
                .hero-section { min-height: 60vh; }
                .hero-title { font-size: 2rem; }
                .hero-subtitle { font-size: 1.25rem; }
            }
        ";

        return $baseCss . "\n\n" . $tabletOverrides;
    }

    /**
     * Generate desktop-responsive CSS
     */
    protected function generateDesktopResponsiveCss(string $baseCss): string
    {
        $desktopOverrides = "
            @media (min-width: " . self::BREAKPOINTS['desktop'] . "px) {
                .container { max-width: 1140px; }
                .hero-section { min-height: 80vh; }
                .hero-title { font-size: 3rem; }
                .hero-subtitle { font-size: 1.5rem; }
                .cta-button { padding: 1rem 2rem; font-size: 1.125rem; }
            }
        ";

        return $baseCss . "\n\n" . $desktopOverrides;
    }

    /**
     * Get responsive breakpoints for device
     */
    protected function getResponsiveBreakpoints(string $device): array
    {
        return match ($device) {
            'mobile' => array_filter(self::BREAKPOINTS, fn($bp) => $bp <= self::BREAKPOINTS['mobile']),
            'tablet' => array_filter(self::BREAKPOINTS, fn($bp) => $bp <= self::BREAKPOINTS['tablet']),
            default => self::BREAKPOINTS
        };
    }

    /**
     * Generate media queries for device
     */
    protected function generateMediaQueries(string $device): string
    {
        return match ($device) {
            'mobile' => "@media (max-width: " . self::BREAKPOINTS['mobile'] . "px)",
            'tablet' => "@media (max-width: " . self::BREAKPOINTS['tablet'] . "px)",
            default => "@media (min-width: " . self::BREAKPOINTS['desktop'] . "px)"
        };
    }

    /**
     * Generate preview URL
     */
    protected function generatePreviewUrl(int $templateId, array $config, array $options): string
    {
        $baseUrl = config('app.url');
        $params = http_build_query([
            'template_id' => $templateId,
            'device_mode' => $options['device_mode'] ?? 'desktop',
            'cache_hash' => md5(serialize([$templateId, $config, $options]))
        ]);

        return "{$baseUrl}/api/templates/preview/{$templateId}?{$params}";
    }

    /**
     * Find template with tenant isolation
     */
    protected function findTemplate(int $templateId): Template
    {
        $template = Template::findOrFail($templateId);

        if ($template->tenant_id !== (tenant()?->id)) {
            throw new \Exception("Access denied: Template does not belong to current tenant");
        }

        return $template;
    }

    /**
     * Find landing page with tenant isolation
     */
    protected function findLandingPage(int $landingPageId): LandingPage
    {
        $landingPage = LandingPage::findOrFail($landingPageId);

        if ($landingPage->tenant_id !== (tenant()?->id)) {
            throw new \Exception("Access denied: Landing page does not belong to current tenant");
        }

        return $landingPage;
    }

    /**
     * Calculate preview generation performance metrics
     */
    protected function calculatePreviewMetrics(float $startTime): array
    {
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        return [
            'generation_time_ms' => round($duration, 2),
            'memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'generated_at' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Generate cache key for preview
     */
    protected function getCacheKey(string $type, int $id, array $config = [], array $options = []): string
    {
        $configHash = md5(serialize($config));
        $optionsHash = md5(serialize($options));
        $tenantId = tenant()?->id ?? 'global';

        return self::CACHE_PREFIX . "{$type}_{$id}_{$tenantId}_{$configHash}_{$optionsHash}";
    }

    /**
     * Compile template HTML
     */
    protected function compileHtml(Template $template, array $config): string
    {
        $structure = $template->getEffectiveStructure();
        $html = '<!DOCTYPE html><html><head><title>' . ($config['title'] ?? $template->name) . '</title>';
        $html .= '<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
        $html .= '</head><body class="preview-body">';

        foreach ($structure['sections'] ?? [] as $section) {
            $html .= $this->renderSection($section, $config);
        }

        $html .= '</body></html>';
        return $html;
    }

    /**
     * Compile template CSS
     */
    protected function compileCss(Template $template, array $config): string
    {
        $baseCss = "
            .preview-body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
            .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
            .row { display: flex; flex-wrap: wrap; margin: 0 -15px; }
            .col { flex: 1 1 auto; padding: 0 15px; }
            .hero-section { text-align: center; padding: 4rem 0; }
            .hero-title { font-size: 2.5rem; margin-bottom: 1rem; color: #333; }
            .hero-subtitle { font-size: 1.2rem; color: #666; margin-bottom: 2rem; }
            .cta-button { display: inline-block; padding: 1rem 2rem; background: #007bff; color: white; text-decoration: none; border-radius: 0.25rem; transition: background 0.3s; }
            .cta-button:hover { background: #0056b3; }
        ";

        // Add brand-specific CSS if available
        $brandColors = $config['brand_config']['colors'] ?? [];
        if (!empty($brandColors['primary'])) {
            $baseCss .= ".cta-button { background: {$brandColors['primary']}; }";
        }

        return $baseCss;
    }

    /**
     * Compile template JavaScript
     */
    protected function compileJs(Template $template, array $config): string
    {
        return "
            // Template preview JavaScript
            console.log('Template preview loaded for: {$template->name}');
            document.addEventListener('DOMContentLoaded', function() {
                // Basic interactions
                const buttons = document.querySelectorAll('.cta-button');
                buttons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        console.log('CTA button clicked');
                    });
                });
            });
        ";
    }

    /**
     * Render a section
     */
    protected function renderSection(array $section, array $config): string
    {
        $type = $section['type'] ?? 'div';
        $sectionConfig = $section['config'] ?? [];

        // Merge with global config
        $effectiveConfig = array_merge($config, $sectionConfig);

        switch ($type) {
            case 'hero':
                return $this->renderHeroSection($effectiveConfig);
            case 'form':
                return $this->renderFormSection($effectiveConfig);
            case 'statistics':
                return $this->renderStatisticsSection($effectiveConfig);
            case 'testimonials':
                return $this->renderTestimonialsSection($effectiveConfig);
            default:
                return '<div class="section"><p>Section type: ' . htmlspecialchars($type) . ' not implemented</p></div>';
        }
    }

    /**
     * Render hero section
     */
    protected function renderHeroSection(array $config): string
    {
        $title = htmlspecialchars($config['title'] ?? 'Welcome to Our Site');
        $subtitle = htmlspecialchars($config['subtitle'] ?? 'Discover what we have to offer');
        $ctaText = htmlspecialchars($config['cta_text'] ?? 'Get Started');

        return "
            <section class='hero-section'>
                <div class='container'>
                    <h1 class='hero-title'>{$title}</h1>
                    <p class='hero-subtitle'>{$subtitle}</p>
                    <a href='#' class='cta-button'>{$ctaText}</a>
                </div>
            </section>
        ";
    }

    /**
     * Render form section
     */
    protected function renderFormSection(array $config): string
    {
        $fields = $config['fields'] ?? [];
        $submitText = htmlspecialchars($config['submit_text'] ?? 'Submit');

        $html = "<section class='form-section'><div class='container'><form method='post' action='#'>";

        foreach ($fields as $field) {
            $fieldName = htmlspecialchars($field['name'] ?? 'field');
            $fieldLabel = htmlspecialchars($field['label'] ?? 'Field');
            $fieldType = htmlspecialchars($field['type'] ?? 'text');

            $html .= "
                <div class='form-group' style='margin-bottom: 1rem;'>
                    <label for='{$fieldName}'>{$fieldLabel}</label>
                    <input type='{$fieldType}' id='{$fieldName}' name='{$fieldName}' class='form-control' style='width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 0.25rem;'>
                </div>
            ";
        }

        $html .= "<button type='submit' class='cta-button'>{$submitText}</button>";
        $html .= "</form></div></section>";

        return $html;
    }

    /**
     * Render statistics section
     */
    protected function renderStatisticsSection(array $config): string
    {
        $items = $config['items'] ?? [['value' => '100', 'label' => 'Projects Completed']];

        $html = "<section class='statistics-section' style='padding: 4rem 0; background: #f8f9fa;'><div class='container'><div class='row'>";

        foreach ($items as $item) {
            $value = htmlspecialchars($item['value'] ?? '0');
            $label = htmlspecialchars($item['label'] ?? 'Statistic');

            $html .= "
                <div class='col' style='text-align: center; margin-bottom: 2rem;'>
                    <div class='stat-value' style='font-size: 3rem; font-weight: bold; color: #007bff;'>{$value}</div>
                    <div class='stat-label' style='font-size: 1.1rem; color: #666;'>{$label}</div>
                </div>
            ";
        }

        $html .= "</div></div></section>";
        return $html;
    }

    /**
     * Render testimonials section
     */
    protected function renderTestimonialsSection(array $config): string
    {
        $testimonials = $config['testimonials'] ?? [
            ['quote' => 'Great service!', 'author' => 'Happy Customer']
        ];

        $html = "<section class='testimonials-section' style='padding: 4rem 0;'><div class='container'>";

        foreach ($testimonials as $testimonial) {
            $quote = htmlspecialchars($testimonial['quote'] ?? '');
            $author = htmlspecialchars($testimonial['author'] ?? '');

            $html .= "
                <blockquote style='text-align: center; margin-bottom: 2rem; padding: 2rem; border-left: 4px solid #007bff; background: #f8f9fa;'>
                    <p style='font-style: italic; font-size: 1.2rem; margin-bottom: 1rem;'>{$quote}</p>
                    <footer style='color: #666;'>&mdash; {$author}</footer>
                </blockquote>
            ";
        }

        $html .= "</div></section>";
        return $html;
    }

    /**
     * Clear cache by pattern
     */
    protected function clearCacheByPattern(): void
    {
        // Note: This is a simplified implementation
        // In production, implement proper cache invalidation strategies
        try {
            // Simple cache clearing - just forget specific patterns if possible
            // Laravel doesn't have built-in pattern clearing, so we accept this limitation
            Log::info("Cache clearing requested - implement proper invalidation strategy in production");
        } catch (\Exception $e) {
            Log::warning("Could not clear cache", ['error' => $e->getMessage()]);
        }
    }
}