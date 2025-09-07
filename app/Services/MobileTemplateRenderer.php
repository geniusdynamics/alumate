<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Mobile Template Renderer
 *
 * Handles mobile-responsive template rendering with adaptive layouts,
 * touch-optimized interactions, and performance optimizations for mobile devices.
 */
class MobileTemplateRenderer
{
    private const MOBILE_BREAKPOINTS = [
        'xs' => 320,
        'sm' => 640,
        'md' => 768,
        'lg' => 1024,
        'xl' => 1280,
    ];

    private const MOBILE_OPTIMIZATIONS = [
        'image_lazy_loading' => true,
        'font_preloading' => true,
        'critical_css_inlining' => true,
        'touch_friendly_sizing' => true,
        'reduced_animations' => true,
    ];

    /**
     * Render template for mobile devices
     *
     * @param array $templateStructure
     * @param string $deviceType
     * @param array $options
     * @return array
     */
    public function renderForMobile(array $templateStructure, string $deviceType = 'mobile', array $options = []): array
    {
        Log::info('Rendering template for mobile device', ['device_type' => $deviceType]);

        // Detect device capabilities
        $deviceCapabilities = $this->detectDeviceCapabilities($deviceType);

        // Apply mobile optimizations
        $optimizedStructure = $this->applyMobileOptimizations($templateStructure, $deviceCapabilities);

        // Generate responsive layout
        $responsiveLayout = $this->generateResponsiveLayout($optimizedStructure, $deviceCapabilities);

        // Add mobile-specific interactions
        $mobileInteractions = $this->addMobileInteractions($responsiveLayout);

        // Generate performance optimizations
        $performanceOptimizations = $this->generatePerformanceOptimizations($mobileInteractions);

        return [
            'html' => $this->generateMobileHTML($mobileInteractions),
            'css' => $this->generateMobileCSS($mobileInteractions, $deviceCapabilities),
            'javascript' => $this->generateMobileJS($mobileInteractions),
            'optimizations' => $performanceOptimizations,
            'device_capabilities' => $deviceCapabilities,
        ];
    }

    /**
     * Detect device capabilities
     *
     * @param string $deviceType
     * @return array
     */
    private function detectDeviceCapabilities(string $deviceType): array
    {
        $capabilities = [
            'touch_enabled' => true,
            'high_dpi' => false,
            'webgl_support' => false,
            'geolocation' => false,
            'accelerometer' => false,
            'viewport_width' => self::MOBILE_BREAKPOINTS['sm'],
            'connection_type' => '4g',
        ];

        switch ($deviceType) {
            case 'mobile':
                $capabilities['viewport_width'] = self::MOBILE_BREAKPOINTS['xs'];
                $capabilities['high_dpi'] = true;
                $capabilities['accelerometer'] = true;
                break;
            case 'tablet':
                $capabilities['viewport_width'] = self::MOBILE_BREAKPOINTS['md'];
                $capabilities['high_dpi'] = true;
                $capabilities['webgl_support'] = true;
                break;
            case 'phablet':
                $capabilities['viewport_width'] = self::MOBILE_BREAKPOINTS['sm'];
                $capabilities['high_dpi'] = true;
                break;
        }

        return $capabilities;
    }

    /**
     * Apply mobile optimizations to template structure
     *
     * @param array $structure
     * @param array $capabilities
     * @return array
     */
    private function applyMobileOptimizations(array $structure, array $capabilities): array
    {
        $optimized = $structure;

        // Optimize images for mobile
        if (isset($optimized['sections'])) {
            foreach ($optimized['sections'] as &$section) {
                $section = $this->optimizeSectionForMobile($section, $capabilities);
            }
        }

        // Add mobile-specific meta tags
        $optimized['mobile_meta'] = [
            'viewport' => 'width=device-width, initial-scale=1.0, maximum-scale=5.0',
            'mobile_web_app_capable' => 'yes',
            'apple_mobile_web_app_capable' => 'yes',
            'apple_mobile_web_app_status_bar_style' => 'default',
            'format_detection' => 'telephone=no',
        ];

        return $optimized;
    }

    /**
     * Optimize individual section for mobile
     *
     * @param array $section
     * @param array $capabilities
     * @return array
     */
    private function optimizeSectionForMobile(array $section, array $capabilities): array
    {
        // Optimize images
        if (isset($section['content']['images'])) {
            foreach ($section['content']['images'] as &$image) {
                $image = $this->optimizeImageForMobile($image, $capabilities);
            }
        }

        // Optimize text sizes
        if (isset($section['content']['text'])) {
            $section['content']['text'] = $this->optimizeTextForMobile($section['content']['text'], $capabilities);
        }

        // Add touch-friendly spacing
        $section['mobile_spacing'] = [
            'padding' => ($capabilities['viewport_width'] < 640 ? '1rem' : '1.5rem'),
            'margin' => ($capabilities['viewport_width'] < 640 ? '0.5rem' : '1rem'),
        ];

        return $section;
    }

    /**
     * Optimize image for mobile devices
     *
     * @param array $image
     * @param array $capabilities
     * @return array
     */
    private function optimizeImageForMobile(array $image, array $capabilities): array
    {
        $optimized = $image;

        // Add responsive image attributes
        $optimized['attributes'] = array_merge($optimized['attributes'] ?? [], [
            'loading' => 'lazy',
            'decoding' => 'async',
        ]);

        // Generate responsive image sources
        if ($capabilities['high_dpi']) {
            $optimized['srcset'] = $this->generateResponsiveImageSources($image['src'], $capabilities);
        }

        // Add mobile-specific styling
        $optimized['mobile_styles'] = [
            'max_width' => '100%',
            'height' => 'auto',
            'object_fit' => 'cover',
        ];

        return $optimized;
    }

    /**
     * Optimize text for mobile devices
     *
     * @param array $text
     * @param array $capabilities
     * @return array
     */
    private function optimizeTextForMobile(array $text, array $capabilities): array
    {
        $optimized = $text;

        // Adjust font sizes for mobile
        if (isset($optimized['font_size'])) {
            $baseSize = (int) $optimized['font_size'];
            $optimized['mobile_font_size'] = $capabilities['viewport_width'] < 640
                ? max($baseSize * 0.9, 14) . 'px'
                : $baseSize . 'px';
        }

        // Adjust line heights for better readability
        $optimized['mobile_line_height'] = $capabilities['viewport_width'] < 640 ? 1.5 : 1.4;

        return $optimized;
    }

    /**
     * Generate responsive layout
     *
     * @param array $structure
     * @param array $capabilities
     * @return array
     */
    private function generateResponsiveLayout(array $structure, array $capabilities): array
    {
        $layout = $structure;

        // Apply mobile-first responsive design
        $layout['responsive_breakpoints'] = $this->generateBreakpoints($capabilities);

        // Generate CSS Grid or Flexbox layouts optimized for mobile
        $layout['mobile_layout'] = [
            'display' => 'flex',
            'flex_direction' => 'column',
            'align_items' => 'stretch',
        ];

        // Add swipe gestures for mobile navigation
        if ($capabilities['touch_enabled']) {
            $layout['touch_gestures'] = [
                'swipe_left' => 'next_section',
                'swipe_right' => 'previous_section',
                'tap' => 'activate_element',
            ];
        }

        return $layout;
    }

    /**
     * Add mobile-specific interactions
     *
     * @param array $layout
     * @return array
     */
    private function addMobileInteractions(array $layout): array
    {
        $layout['mobile_interactions'] = [
            'touch_events' => [
                'tap' => 'handleTap',
                'swipe' => 'handleSwipe',
                'pinch' => 'handlePinch',
                'long_press' => 'handleLongPress',
            ],
            'gestures' => [
                'pull_to_refresh' => 'handlePullToRefresh',
                'infinite_scroll' => 'handleInfiniteScroll',
            ],
            'accessibility' => [
                'screen_reader_support' => true,
                'keyboard_navigation' => true,
                'high_contrast_support' => true,
            ],
        ];

        return $layout;
    }

    /**
     * Generate performance optimizations
     *
     * @param array $layout
     * @return array
     */
    private function generatePerformanceOptimizations(array $layout): array
    {
        return [
            'lazy_loading' => true,
            'image_optimization' => true,
            'css_minification' => true,
            'javascript_minification' => true,
            'critical_css' => true,
            'font_preloading' => true,
            'service_worker_cache' => true,
            'cdn_optimization' => true,
        ];
    }

    /**
     * Generate mobile HTML
     *
     * @param array $layout
     * @return string
     */
    private function generateMobileHTML(array $layout): string
    {
        $html = '<div class="mobile-template-container">';

        if (isset($layout['sections'])) {
            foreach ($layout['sections'] as $section) {
                $html .= $this->generateSectionHTML($section);
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate mobile CSS
     *
     * @param array $layout
     * @param array $capabilities
     * @return string
     */
    private function generateMobileCSS(array $layout, array $capabilities): string
    {
        $css = $this->generateBaseMobileCSS($capabilities);

        if (isset($layout['sections'])) {
            foreach ($layout['sections'] as $section) {
                $css .= $this->generateSectionCSS($section, $capabilities);
            }
        }

        $css .= $this->generateResponsiveCSS($capabilities);

        return $css;
    }

    /**
     * Generate mobile JavaScript
     *
     * @param array $layout
     * @return string
     */
    private function generateMobileJS(array $layout): string
    {
        $js = $this->generateBaseMobileJS();

        if (isset($layout['mobile_interactions'])) {
            $js .= $this->generateInteractionJS($layout['mobile_interactions']);
        }

        return $js;
    }

    /**
     * Generate base mobile CSS
     *
     * @param array $capabilities
     * @return string
     */
    private function generateBaseMobileCSS(array $capabilities): string
    {
        return "
            .mobile-template-container {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
                touch-action: manipulation;
            }

            .mobile-section {
                padding: " . ($capabilities['viewport_width'] < 640 ? '1rem' : '1.5rem') . ";
                margin-bottom: 1rem;
            }

            .mobile-touch-target {
                min-height: 44px;
                min-width: 44px;
            }

            @media (max-width: 640px) {
                .mobile-template-container {
                    font-size: 16px;
                    line-height: 1.5;
                }
            }
        ";
    }

    /**
     * Generate section HTML
     *
     * @param array $section
     * @return string
     */
    private function generateSectionHTML(array $section): string
    {
        $html = "<section class=\"mobile-section\" data-section-type=\"{$section['type']}\">";

        if (isset($section['content'])) {
            $html .= $this->generateSectionContentHTML($section['content']);
        }

        $html .= "</section>";

        return $html;
    }

    /**
     * Generate section CSS
     *
     * @param array $section
     * @param array $capabilities
     * @return string
     */
    private function generateSectionCSS(array $section, array $capabilities): string
    {
        $css = ".mobile-section[data-section-type=\"{$section['type']}\"] {\n";

        if (isset($section['mobile_spacing'])) {
            $css .= "    padding: {$section['mobile_spacing']['padding']};\n";
            $css .= "    margin-bottom: {$section['mobile_spacing']['margin']};\n";
        }

        $css .= "}\n\n";

        return $css;
    }

    /**
     * Generate responsive CSS
     *
     * @param array $capabilities
     * @return string
     */
    private function generateResponsiveCSS(array $capabilities): string
    {
        $css = "
            @media (max-width: 640px) {
                .mobile-template-container {
                    padding: 0 1rem;
                }

                .mobile-section {
                    margin-bottom: 2rem;
                }
            }

            @media (max-width: 480px) {
                .mobile-template-container {
                    padding: 0 0.5rem;
                }

                .mobile-section {
                    padding: 1rem 0.5rem;
                }
            }
        ";

        return $css;
    }

    /**
     * Generate base mobile JavaScript
     *
     * @return string
     */
    private function generateBaseMobileJS(): string
    {
        return "
            // Mobile-specific JavaScript utilities
            const MobileUtils = {
                isTouchDevice: () => {
                    return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
                },

                getViewportSize: () => {
                    return {
                        width: window.innerWidth,
                        height: window.innerHeight
                    };
                },

                addTouchListeners: (element, handlers) => {
                    if (!MobileUtils.isTouchDevice()) return;

                    Object.keys(handlers).forEach(event => {
                        element.addEventListener(event, handlers[event], { passive: false });
                    });
                }
            };

            // Initialize mobile optimizations
            document.addEventListener('DOMContentLoaded', function() {
                if (MobileUtils.isTouchDevice()) {
                    document.body.classList.add('touch-device');
                }
            });
        ";
    }

    /**
     * Generate interaction JavaScript
     *
     * @param array $interactions
     * @return string
     */
    private function generateInteractionJS(array $interactions): string
    {
        $js = "
            // Touch event handlers
            const TouchHandler = {
                init: function() {
                    this.bindEvents();
                },

                bindEvents: function() {
                    const sections = document.querySelectorAll('.mobile-section');

                    sections.forEach(section => {
                        MobileUtils.addTouchListeners(section, {
                            touchstart: this.handleTouchStart.bind(this),
                            touchmove: this.handleTouchMove.bind(this),
                            touchend: this.handleTouchEnd.bind(this)
                        });
                    });
                },

                handleTouchStart: function(e) {
                    this.startX = e.touches[0].clientX;
                    this.startY = e.touches[0].clientY;
                },

                handleTouchMove: function(e) {
                    if (!this.startX || !this.startY) return;

                    const deltaX = e.touches[0].clientX - this.startX;
                    const deltaY = e.touches[0].clientY - this.startY;

                    // Handle swipe gestures
                    if (Math.abs(deltaX) > Math.abs(deltaY)) {
                        e.preventDefault();
                        if (deltaX > 50) {
                            this.handleSwipeRight(e.target);
                        } else if (deltaX < -50) {
                            this.handleSwipeLeft(e.target);
                        }
                    }
                },

                handleTouchEnd: function(e) {
                    this.startX = null;
                    this.startY = null;
                },

                handleSwipeLeft: function(target) {
                    console.log('Swipe left detected', target);
                    // Implement swipe left logic
                },

                handleSwipeRight: function(target) {
                    console.log('Swipe right detected', target);
                    // Implement swipe right logic
                }
            };

            // Initialize touch handler
            TouchHandler.init();
        ";

        return $js;
    }

    /**
     * Generate responsive image sources
     *
     * @param string $src
     * @param array $capabilities
     * @return array
     */
    private function generateResponsiveImageSources(string $src, array $capabilities): array
    {
        // This would generate different image sizes for responsive loading
        // For now, return a basic structure
        return [
            $src . '?w=320 320w',
            $src . '?w=640 640w',
            $src . '?w=1024 1024w',
        ];
    }

    /**
     * Generate breakpoints for responsive design
     *
     * @param array $capabilities
     * @return array
     */
    private function generateBreakpoints(array $capabilities): array
    {
        return [
            'xs' => ['max' => 639, 'container' => 'full'],
            'sm' => ['min' => 640, 'max' => 767, 'container' => 'full'],
            'md' => ['min' => 768, 'max' => 1023, 'container' => 'full'],
            'lg' => ['min' => 1024, 'container' => '1200px'],
        ];
    }

    /**
     * Generate section content HTML
     *
     * @param array $content
     * @return string
     */
    private function generateSectionContentHTML(array $content): string
    {
        $html = '';

        if (isset($content['text'])) {
            $html .= "<div class=\"mobile-text\">{$content['text']}</div>";
        }

        if (isset($content['images'])) {
            foreach ($content['images'] as $image) {
                $srcset = isset($image['srcset']) ? ' srcset="' . implode(', ', $image['srcset']) . '"' : '';
                $html .= "<img src=\"{$image['src']}\"{$srcset} class=\"mobile-image\" loading=\"lazy\" />";
            }
        }

        return $html;
    }
}