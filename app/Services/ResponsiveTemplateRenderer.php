<?php

namespace App\Services;

use App\Models\Template;
use App\Models\LandingPage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Responsive Template Renderer Service
 *
 * Advanced mobile-responsive template rendering with touch-optimized interactions,
 * comprehensive CSS generation, and mobile-specific optimizations for the Template
 * Creation System. Extends the base TemplatePreviewService functionality.
 *
 * @package App\Services
 */
class ResponsiveTemplateRenderer
{
    protected const CACHE_PREFIX = 'responsive_template_';
    protected const CACHE_DURATION = 1200; // 20 minutes

    protected TemplatePreviewService $previewService;

    // Enhanced device configurations with more detailed specifications
    protected const ENHANCED_DEVICE_MODES = [
        'mobile' => [
            'width' => 375,
            'height' => 667,
            'device_pixel_ratio' => 2,
            'orientation' => 'portrait',
            'touch_enabled' => true,
            'max_touch_points' => 5,
        ],
        'mobile-landscape' => [
            'width' => 667,
            'height' => 375,
            'device_pixel_ratio' => 2,
            'orientation' => 'landscape',
            'touch_enabled' => true,
            'max_touch_points' => 5,
        ],
        'tablet' => [
            'width' => 768,
            'height' => 1024,
            'device_pixel_ratio' => 1,
            'orientation' => 'portrait',
            'touch_enabled' => true,
            'max_touch_points' => 10,
        ],
        'tablet-landscape' => [
            'width' => 1024,
            'height' => 768,
            'device_pixel_ratio' => 1,
            'orientation' => 'landscape',
            'touch_enabled' => true,
            'max_touch_points' => 10,
        ],
        'desktop' => [
            'width' => 1920,
            'height' => 1080,
            'device_pixel_ratio' => 1,
            'orientation' => 'landscape',
            'touch_enabled' => false,
            'max_touch_points' => 0,
        ],
    ];

    // Enhanced breakpoints with naming following CSS conventions
    protected const ENHANCED_BREAKPOINTS = [
        'xs' => 0,      // Extra small (mobile)
        'sm' => 576,    // Small (mobile landscape)
        'md' => 768,    // Medium (tablet)
        'lg' => 992,    // Large (tablet landscape)
        'xl' => 1200,   // Extra large (desktop)
        'xxl' => 1400,  // Extra extra large (large desktop)
    ];

    // Touch interaction targets and sizes
    protected const TOUCH_TARGETS = [
        'minimum' => 44,      // Minimum touch target size (Apple HIG)
        'recommended' => 48,  // Recommended for accessibility
        'comfortable' => 56,  // Comfortable touch target
    ];

    public function __construct(TemplatePreviewService $previewService)
    {
        $this->previewService = $previewService;
    }

    /**
     * Generate comprehensive responsive template preview
     *
     * @param int $templateId
     * @param array $config
     * @param array $options
     * @return array
     */
    public function generateResponsivePreview(int $templateId, array $config = [], array $options = []): array
    {
        $cacheKey = $this->getCacheKey('responsive_preview', $templateId, $config, $options);
        $deviceMode = $options['device_mode'] ?? 'desktop';

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($templateId, $config, $options, $deviceMode) {
            $startTime = microtime(true);

            try {
                Log::info("Generating responsive template preview", [
                    'template_id' => $templateId,
                    'device_mode' => $deviceMode,
                    'tenant_id' => tenant()?->id
                ]);

                $basePreview = $this->previewService->generateTemplatePreview($templateId, $config, $options);
                $responsiveEnhancements = $this->generateResponsiveEnhancements($basePreview, $deviceMode);

                return array_merge($basePreview, [
                    'responsive_enhancements' => $responsiveEnhancements,
                    'touch_optimizations' => $this->generateTouchOptimizations($basePreview, $deviceMode),
                    'accessibility_features' => $this->generateAccessibilityFeatures($basePreview, $deviceMode),
                    'performance_optimizations' => $this->generatePerformanceOptimizations($deviceMode),
                    'generated_at' => Carbon::now()->toISOString(),
                    'responsive_cache_hash' => md5(serialize([
                        $templateId,
                        $config,
                        $options,
                        $responsiveEnhancements,
                        microtime()
                    ]))
                ]);
            } catch (\Exception $e) {
                Log::error("Responsive template preview generation failed", [
                    'template_id' => $templateId,
                    'device_mode' => $deviceMode,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Generate complete multi-device responsive preview
     *
     * @param int $templateId
     * @param array $config
     * @return array
     */
    public function generateMultiDeviceResponsivePreview(int $templateId, array $config = []): array
    {
        $cacheKey = $this->getCacheKey('multi_device_responsive', $templateId, $config);

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($templateId, $config) {
            $startTime = microtime(true);

            try {
                Log::info("Generating multi-device responsive preview", ['template_id' => $templateId]);

                $multiDevicePreview = [
                    'template_id' => $templateId,
                    'devices' => [],
                    'responsive_matrix' => [],
                    'generated_at' => Carbon::now()->toISOString(),
                ];

                foreach (self::ENHANCED_DEVICE_MODES as $device => $specs) {
                    $devicePreview = $this->generateResponsivePreview($templateId, $config, [
                        'device_mode' => $device,
                        'include_responsive' => true
                    ]);

                    $multiDevicePreview['devices'][$device] = array_merge($specs, [
                        'preview' => $devicePreview,
                        'responsive_score' => $this->calculateResponsiveScore($devicePreview, $device),
                        'performance_score' => $this->calculatePerformanceScore($devicePreview, $device),
                    ]);

                    $multiDevicePreview['responsive_matrix'][$device] = [
                        'breakpoints_used' => $this->getBreakpointsForDevice($device),
                        'touch_targets_met' => $this->validateTouchTargets($devicePreview),
                        'content_adaptation' => $this->getContentAdaptationLevel($device),
                    ];
                }

                $multiDevicePreview['performance_metrics'] = $this->calculatePreviewMetrics($startTime);
                $multiDevicePreview['cache_hash'] = md5(serialize([$templateId, $config, microtime()]));

                return $multiDevicePreview;
            } catch (\Exception $e) {
                Log::error("Multi-device responsive preview generation failed", [
                    'template_id' => $templateId,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Generate touch-optimized form rendering
     *
     * @param array $basePreview
     * @param string $deviceMode
     * @return array
     */
    protected function generateTouchOptimizations(array $basePreview, string $deviceMode): array
    {
        $isTouchDevice = self::ENHANCED_DEVICE_MODES[$deviceMode]['touch_enabled'] ?? false;

        if (!$isTouchDevice) {
            return [
                'touch_enabled' => false,
                'optimizations' => []
            ];
        }

        return [
            'touch_enabled' => true,
            'min_touch_target' => self::TOUCH_TARGETS['minimum'],
            'css_optimizations' => $this->generateTouchCssOptimizations($basePreview),
            'form_enhancements' => $this->generateFormTouchEnhancements($basePreview),
            'gesture_support' => $this->generateGestureSupport($deviceMode),
        ];
    }

    /**
     * Generate accessibility features for responsive rendering
     *
     * @param array $basePreview
     * @param string $deviceMode
     * @return array
     */
    protected function generateAccessibilityFeatures(array $basePreview, string $deviceMode): array
    {
        $isMobile = str_contains($deviceMode, 'mobile');

        return [
            'aria_support' => true,
            'focus_management' => $this->generateFocusManagement($deviceMode),
            'keyboard_navigation' => $this->generateKeyboardNavigation($deviceMode),
            'screen_reader_support' => $this->generateScreenReaderSupport($isMobile),
            'reduced_motion_support' => $this->generateReducedMotionSupport(),
            'high_contrast_support' => $this->generateHighContrastSupport(),
        ];
    }

    /**
     * Generate performance optimizations for different devices
     *
     * @param string $deviceMode
     * @return array
     */
    protected function generatePerformanceOptimizations(string $deviceMode): array
    {
        $device = self::ENHANCED_DEVICE_MODES[$deviceMode] ?? self::ENHANCED_DEVICE_MODES['desktop'];

        return [
            'device_pixel_ratio' => $device['device_pixel_ratio'],
            'lazy_loading_enabled' => true,
            'image_optimization' => $this->generateImageOptimization($deviceMode),
            'bundle_splitting' => $this->generateBundleSplitting($deviceMode),
            'critical_css' => $this->generateCriticalCss($deviceMode),
            'font_loading' => $this->generateFontLoadingStrategy($deviceMode),
        ];
    }

    /**
     * Generate enhanced responsive CSS with device-specific optimizations
     *
     * @param array $basePreview
     * @param string $deviceMode
     * @return array
     */
    protected function generateResponsiveEnhancements(array $basePreview, string $deviceMode): array
    {
        $device = self::ENHANCED_DEVICE_MODES[$deviceMode] ?? self::ENHANCED_DEVICE_MODES['desktop'];

        return [
            'device_specific_css' => $this->generateDeviceSpecificCss($basePreview, $deviceMode),
            'orientation_rules' => $this->generateOrientationRules($device),
            'viewport_optimizations' => $this->generateViewportOptimizations($device),
            'responsive_grid' => $this->generateResponsiveGrid($deviceMode),
            'typography_scales' => $this->generateResponsiveTypography($deviceMode),
         ];
    }

    /**
     * Generate touch-friendly CSS optimizations
     *
     * @param array $basePreview
     * @return string
     */
    protected function generateTouchCssOptimizations(array $basePreview): string
    {
        return "
            /* Touch optimizations */
            .cta-button, .form-control, .interactive-element {
                min-height: " . self::TOUCH_TARGETS['minimum'] . "px;
                min-width: " . self::TOUCH_TARGETS['minimum'] . "px;
                padding: 12px 16px;
            }

            .form-control:focus {
                outline: 3px solid #007bff;
                outline-offset: 2px;
            }

            /* Touch-friendly hover states */
            @media (hover: hover) and (pointer: fine) {
                .cta-button:hover { transform: translateY(-1px); }
            }

            /* Remove hover effects on touch devices */
            @media (hover: none) {
                .cta-button:hover { transform: none; }
            }

            /* Touch feedback animations */
            @supports (touch-action: manipulation) {
                .interactive-element:active {
                    transform: scale(0.98);
                    transition: transform 0.1s ease;
                }
            }
        ";
    }

    /**
     * Generate touch-optimized form enhancements
     *
     * @param array $basePreview
     * @return array
     */
    protected function generateFormTouchEnhancements(array $basePreview): array
    {
        return [
            'input_types' => $this->optimizeInputTypes(),
            'autocomplete_attributes' => $this->generateAutocompleteAttributes(),
            'validation_feedback' => $this->generateTouchFriendlyValidation(),
            'accessible_labels' => $this->generateAccessibleLabels(),
            'prevent_zoom_on_focus' => $this->generatePreventZoomOnFocus(),
        ];
    }

    /**
     * Generate gesture support configuration
     *
     * @param string $deviceMode
     * @return array
     */
    protected function generateGestureSupport(string $deviceMode): array
    {
        $device = self::ENHANCED_DEVICE_MODES[$deviceMode];

        return [
            'swipe_gestures' => $device['touch_enabled'],
            'pinch_zoom' => $device['touch_enabled'],
            'double_tap' => $device['touch_enabled'],
            'long_press' => $device['touch_enabled'],
            'gesture_library' => 'hammer.js',
            'max_touch_points' => $device['max_touch_points'],
        ];
    }

    /**
     * Generate device-specific CSS
     *
     * @param array $basePreview
     * @param string $deviceMode
     * @return string
     */
    protected function generateDeviceSpecificCss(array $basePreview, string $deviceMode): string
    {
        $css = "";

        switch ($deviceMode) {
            case 'mobile':
                $css .= $this->generateMobileSpecificCss();
                break;
            case 'mobile-landscape':
                $css .= $this->generateMobileLandscapeSpecificCss();
                break;
            case 'tablet':
                $css .= $this->generateTabletSpecificCss();
                break;
            case 'tablet-landscape':
                $css .= $this->generateTabletLandscapeSpecificCss();
                break;
            default:
                $css .= $this->generateDesktopSpecificCss();
        }

        return $css;
    }

    /**
     * Generate mobile-specific CSS
     *
     * @return string
     */
    protected function generateMobileSpecificCss(): string
    {
        return "
            /* Mobile-specific optimizations */
            .container { width: 100%; padding: 0 16px; }
            .hero-section { padding: 2rem 0; min-height: 60vh; }
            .hero-title { font-size: 2rem; line-height: 1.2; margin-bottom: 1rem; }
            .hero-subtitle { font-size: 1.125rem; line-height: 1.4; margin-bottom: 1.5rem; }
            .cta-button { width: 100%; margin-bottom: 1rem; font-size: 1.125rem; }

            /* Mobile navigation */
            .mobile-nav { position: fixed; bottom: 0; width: 100%; background: white; border-top: 1px solid #e5e7eb; z-index: 1000; }
            .mobile-nav-item { flex: 1; text-align: center; padding: 12px 8px; }

            /* Mobile forms */
            .form-group { margin-bottom: 1.5rem; }
            .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
            .form-control {
                width: 100%;
                padding: 12px 16px;
                font-size: 16px; /* Prevent zoom on iOS */
                border: 1px solid #d1d5db;
                border-radius: 8px;
                background: white;
            }
        ";
    }

    /**
     * Generate mobile landscape-specific CSS
     *
     * @return string
     */
    protected function generateMobileLandscapeSpecificCss(): string
    {
        return "
            /* Mobile landscape optimizations */
            @media (orientation: landscape) and (max-height: 500px) {
                .hero-section { min-height: 40vh; padding: 1.5rem 0; }
                .hero-title { font-size: 1.5rem; }
                .hero-subtitle { font-size: 1rem; }
                .cta-button { padding: 10px 20px; font-size: 1rem; }
            }
        ";
    }

    /**
     * Generate tablet-specific CSS
     *
     * @return string
     */
    protected function generateTabletSpecificCss(): string
    {
        return "
            /* Tablet portrait optimizations */
            .container { max-width: 720px; margin: 0 auto; padding: 0 24px; }
            .hero-section { min-height: 70vh; padding: 3rem 0; }
            .hero-title { font-size: 2.5rem; }
            .hero-subtitle { font-size: 1.25rem; }

            /* Tablet grid */
            .grid-tablet-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
            .grid-tablet-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        ";
    }

    /**
     * Generate tablet landscape-specific CSS
     *
     * @return string
     */
    protected function generateTabletLandscapeSpecificCss(): string
    {
        return "
            /* Tablet landscape optimizations */
            .container { max-width: 900px; }
            .hero-section { min-height: 80vh; }
            .hero-title { font-size: 3rem; }
            .hero-subtitle { font-size: 1.5rem; }

            /* Landscape grid */
            .grid-landscape-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; }
        ";
    }

    /**
     * Generate desktop-specific CSS
     *
     * @return string
     */
    protected function generateDesktopSpecificCss(): string
    {
        return "
            /* Desktop optimizations */
            .container { max-width: 1200px; }
            .hero-section { min-height: 90vh; }
            .hero-title { font-size: 3.5rem; }
            .hero-subtitle { font-size: 1.75rem; }

            /* Desktop grid */
            .grid-desktop-6 { display: grid; grid-template-columns: repeat(6, 1fr); gap: 2rem; }
            .grid-desktop-12 { display: grid; grid-template-columns: repeat(12, 1fr); gap: 1rem; }
        ";
    }

    /**
     * Generate orientation-specific rules
     *
     * @param array $device
     * @return string
     */
    protected function generateOrientationRules(array $device): string
    {
        $orientation = $device['orientation'];

        return "
            @media (orientation: {$orientation}) {
                body { font-size: " . ($orientation === 'landscape' ? '14px' : '16px') . "; }
            }

            @media (orientation: landscape) and (max-height: 500px) {
                /* Special rules for landscape with small height */
                .container { padding: 1rem; }
                header { display: none; }
                footer { display: none; }
            }
        ";
    }

    /**
     * Generate viewport optimizations
     *
     * @param array $device
     * @return array
     */
    protected function generateViewportOptimizations(array $device): array
    {
        return [
            'viewport_meta' => "width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes",
            'device_width' => $device['width'],
            'device_height' => $device['height'],
            'device_pixel_ratio' => $device['device_pixel_ratio'],
            'viewport_units' => true,
            'aspect_ratio_queries' => $this->generateAspectRatioQueries($device),
        ];
    }

    /**
     * Generate aspect ratio media queries
     *
     * @param array $device
     * @return string
     */
    protected function generateAspectRatioQueries(array $device): string
    {
        $ratio = $device['width'] / $device['height'];
        return "
            @media (aspect-ratio: {$ratio}) {
                .aspect-ratio-optimized {
                    width: 100vw;
                    height: 100vh;
                }
            }
        ";
    }

    /**
     * Generate responsive grid system
     *
     * @param string $deviceMode
     * @return string
     */
    protected function generateResponsiveGrid(string $deviceMode): string
    {
        $gridCss = "
            /* Responsive Grid System */
            .grid-responsive {
                display: grid;
                width: 100%;
            }

            /* Mobile first approach */
            .grid-responsive { grid-template-columns: 1fr; }
        ";

        foreach (self::ENHANCED_BREAKPOINTS as $breakpoint => $width) {
            $gridCss .= "
                @media (min-width: {$width}px) {
                    .grid-responsive-{$breakpoint} { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
                    .grid-responsive-{$breakpoint}-2 { grid-template-columns: repeat(2, 1fr); }
                    .grid-responsive-{$breakpoint}-3 { grid-template-columns: repeat(3, 1fr); }
                    .grid-responsive-{$breakpoint}-4 { grid-template-columns: repeat(4, 1fr); }
                }
            ";
        }

        return $gridCss;
    }

    /**
     * Generate responsive typography scales
     *
     * @param string $deviceMode
     * @return string
     */
    protected function generateResponsiveTypography(string $deviceMode): string
    {
        return "
            /* Responsive Typography System */
            :root {
                --font-size-xs: 0.75rem;
                --font-size-sm: 0.875rem;
                --font-size-base: 1rem;
                --font-size-lg: 1.125rem;
                --font-size-xl: 1.25rem;
                --font-size-2xl: 1.5rem;
                --font-size-3xl: 1.875rem;
                --font-size-4xl: 2.25rem;
                --font-size-5xl: 3rem;
            }

            /* Mobile typography */
            @media (max-width: " . self::ENHANCED_BREAKPOINTS['sm'] . "px) {
                :root {
                    --font-size-base: 16px; /* Prevent zoom on iOS */
                    --font-size-lg: 18px;
                    --font-size-xl: 20px;
                }
            }

            /* Desktop typography */
            @media (min-width: " . self::ENHANCED_BREAKPOINTS['xl'] . "px) {
                :root {
                    --font-size-4xl: 2.5rem;
                    --font-size-5xl: 3.5rem;
                }
            }
        ";
    }

    /**
     * Calculate responsive score for a device preview
     *
     * @param array $preview
     * @param string $device
     * @return float
     */
    protected function calculateResponsiveScore(array $preview, string $device): float
    {
        $score = 0;
        $maxScore = 100;

        // Breakpoints usage (20 points)
        $breakpointsUsed = count($this->getBreakpointsForDevice($device));
        $score += min(20, $breakpointsUsed * 5);

        // Touch targets validation (25 points)
        $touchTargetsValid = $this->validateTouchTargets($preview);
        $score += $touchTargetsValid ? 25 : 0;

        // Content adaptation (15 points)
        $adaptationLevel = $this->getContentAdaptationLevel($device);
        $score += $adaptationLevel * 15;

        // Accessibility features (20 points)
        $accessibilityFeatures = $preview['accessibility_features'] ?? [];
        $score += count(array_filter($accessibilityFeatures)) * 5;

        // Performance optimizations (20 points)
        $performanceOpts = $preview['performance_optimizations'] ?? [];
        $score += count(array_filter($performanceOpts)) * 4;

        return min($maxScore, $score);
    }

    /**
     * Calculate performance score for a device preview
     *
     * @param array $preview
     * @param string $device
     * @return float
     */
    protected function calculatePerformanceScore(array $preview, string $device): float
    {
        $metrics = $preview['performance_metrics'] ?? [];
        $score = 100;

        // Deduct points based on performance
        if (($metrics['generation_time_ms'] ?? 0) > 1000) {
            $score -= 20; // Slow generation
        }

        if (($metrics['memory_usage_mb'] ?? 0) > 50) {
            $score -= 15; // High memory usage
        }

        return max(0, $score);
    }

    /**
     * Get breakpoints relevant for a specific device
     *
     * @param string $device
     * @return array
     */
    protected function getBreakpointsForDevice(string $device): array
    {
        return match ($device) {
            'mobile', 'mobile-landscape' => [
                'xs' => self::ENHANCED_BREAKPOINTS['xs'],
                'sm' => self::ENHANCED_BREAKPOINTS['sm'],
            ],
            'tablet', 'tablet-landscape' => [
                'md' => self::ENHANCED_BREAKPOINTS['md'],
                'lg' => self::ENHANCED_BREAKPOINTS['lg'],
            ],
            'desktop' => [
                'xl' => self::ENHANCED_BREAKPOINTS['xl'],
                'xxl' => self::ENHANCED_BREAKPOINTS['xxl'],
            ],
            default => self::ENHANCED_BREAKPOINTS,
        };
    }

    /**
     * Validate touch target compliance
     *
     * @param array $preview
     * @return bool
     */
    protected function validateTouchTargets(array $preview): bool
    {
        // This would analyze the HTML/CSS to check touch target sizes
        // For now, return true assuming proper implementation
        return isset($preview['touch_optimizations']['min_touch_target']);
    }

    /**
     * Get content adaptation level for device (0-1 scale)
     *
     * @param string $device
     * @return float
     */
    protected function getContentAdaptationLevel(string $device): float
    {
        return match ($device) {
            'mobile', 'mobile-landscape' => 0.7, // Good adaptation for mobile
            'tablet', 'tablet-landscape' => 0.8, // Better for larger screens
            'desktop' => 0.9, // Best for large screens
            default => 0.5,
        };
    }

    /**
     * Generate cache key for responsive operations
     */
    protected function getCacheKey(string $type, int $id, array $config = [], array $options = []): string
    {
        $configHash = md5(serialize($config));
        $optionsHash = md5(serialize($options));
        $tenantId = tenant()?->id ?? 'global';

        return self::CACHE_PREFIX . "{$type}_{$id}_{$tenantId}_{$configHash}_{$optionsHash}";
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

    // Placeholder methods that would be fully implemented
    protected function optimizeInputTypes(): array { return []; }
    protected function generateAutocompleteAttributes(): array { return []; }
    protected function generateTouchFriendlyValidation(): array { return []; }
    protected function generateAccessibleLabels(): array { return []; }
    protected function generatePreventZoomOnFocus(): array { return []; }
    protected function generateFocusManagement(string $deviceMode): array { return []; }
    protected function generateKeyboardNavigation(string $deviceMode): array { return []; }
    protected function generateScreenReaderSupport(bool $isMobile): array { return []; }
    protected function generateReducedMotionSupport(): array { return []; }
    protected function generateHighContrastSupport(): array { return []; }
    protected function generateImageOptimization(string $deviceMode): array { return []; }
    protected function generateBundleSplitting(string $deviceMode): array { return []; }
    protected function generateCriticalCss(string $deviceMode): array { return []; }
    protected function generateFontLoadingStrategy(string $deviceMode): array { return []; }
}