<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PagePreviewController extends Controller
{
    /**
     * Generate a live preview of a page
     */
    public function preview(Request $request, $pageId)
    {
        try {
            $page = LandingPage::findOrFail($pageId);

            // Get device mode for responsive preview
            $device = $request->get('device', 'desktop');
            $interactionMode = $request->boolean('interaction_mode', false);

            // Get the latest page content (might be unsaved changes)
            $html = $request->get('html', $page->content);
            $css = $request->get('css', $page->styles);

            // Generate preview HTML
            $previewHtml = $this->generatePreviewHtml($html, $css, $device, $interactionMode);

            return response($previewHtml)
                ->header('Content-Type', 'text/html')
                ->header('X-Frame-Options', 'SAMEORIGIN')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate preview',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update preview with real-time changes
     */
    public function updatePreview(Request $request, $pageId)
    {
        $request->validate([
            'html' => 'required|string',
            'css' => 'required|string',
            'device' => 'string|in:desktop,tablet,mobile',
        ]);

        try {
            $device = $request->get('device', 'desktop');
            $interactionMode = $request->boolean('interaction_mode', false);

            // Generate updated preview
            $previewHtml = $this->generatePreviewHtml(
                $request->get('html'),
                $request->get('css'),
                $device,
                $interactionMode
            );

            // Cache the preview for quick access
            $cacheKey = "page_preview_{$pageId}_{$device}_".md5($request->get('html').$request->get('css'));
            Cache::put($cacheKey, $previewHtml, 300); // Cache for 5 minutes

            return response()->json([
                'success' => true,
                'preview_url' => route('api.pages.preview', ['page' => $pageId])."?cache_key={$cacheKey}",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update preview',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate the complete preview HTML
     */
    private function generatePreviewHtml(string $html, string $css, string $device, bool $interactionMode): string
    {
        $deviceClasses = $this->getDeviceClasses($device);
        $interactionScript = $interactionMode ? $this->getInteractionTrackingScript() : '';

        return view('page-builder.preview', [
            'html' => $html,
            'css' => $css,
            'device' => $device,
            'deviceClasses' => $deviceClasses,
            'interactionScript' => $interactionScript,
            'performanceScript' => $this->getPerformanceTrackingScript(),
        ])->render();
    }

    /**
     * Get device-specific CSS classes
     */
    private function getDeviceClasses(string $device): string
    {
        $classes = [
            'desktop' => 'min-w-full',
            'tablet' => 'max-w-3xl mx-auto',
            'mobile' => 'max-w-sm mx-auto',
        ];

        return $classes[$device] ?? $classes['desktop'];
    }

    /**
     * Get interaction tracking script for preview
     */
    private function getInteractionTrackingScript(): string
    {
        return "
        <script>
        (function() {
            const trackInteraction = (event) => {
                const data = {
                    type: event.type,
                    element: getElementSelector(event.target),
                    timestamp: Date.now(),
                    coordinates: event.type === 'click' ? { x: event.clientX, y: event.clientY } : null
                };
                
                // Send to parent window if in iframe
                if (window.parent !== window) {
                    window.parent.postMessage({
                        type: 'interaction',
                        data: data
                    }, '*');
                }
            };
            
            const getElementSelector = (element) => {
                if (element.id) return '#' + element.id;
                if (element.className) {
                    const classes = element.className.split(' ').filter(c => c.trim());
                    if (classes.length > 0) return '.' + classes[0];
                }
                return element.tagName.toLowerCase();
            };
            
            // Track various interactions
            ['click', 'submit', 'change', 'focus', 'blur'].forEach(eventType => {
                document.addEventListener(eventType, trackInteraction, true);
            });
        })();
        </script>
        ";
    }

    /**
     * Get performance tracking script
     */
    private function getPerformanceTrackingScript(): string
    {
        return "
        <script>
        (function() {
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const performance = window.performance;
                    const navigation = performance.getEntriesByType('navigation')[0];
                    
                    const metrics = {
                        loadTime: navigation ? Math.round(navigation.loadEventEnd - navigation.fetchStart) : 0,
                        domReady: navigation ? Math.round(navigation.domContentLoadedEventEnd - navigation.fetchStart) : 0,
                        firstPaint: 0,
                        lcp: 0
                    };
                    
                    // Get paint metrics
                    const paintEntries = performance.getEntriesByType('paint');
                    const firstPaint = paintEntries.find(entry => entry.name === 'first-paint');
                    if (firstPaint) {
                        metrics.firstPaint = Math.round(firstPaint.startTime);
                    }
                    
                    // Get LCP if available
                    if ('PerformanceObserver' in window) {
                        try {
                            const observer = new PerformanceObserver((list) => {
                                const entries = list.getEntries();
                                const lastEntry = entries[entries.length - 1];
                                if (lastEntry) {
                                    metrics.lcp = Math.round(lastEntry.startTime);
                                    
                                    // Send to parent window
                                    if (window.parent !== window) {
                                        window.parent.postMessage({
                                            type: 'performance',
                                            data: metrics
                                        }, '*');
                                    }
                                }
                            });
                            
                            observer.observe({ entryTypes: ['largest-contentful-paint'] });
                        } catch (e) {
                            // LCP not supported, send current metrics
                            if (window.parent !== window) {
                                window.parent.postMessage({
                                    type: 'performance',
                                    data: metrics
                                }, '*');
                            }
                        }
                    } else {
                        // Send current metrics
                        if (window.parent !== window) {
                            window.parent.postMessage({
                                type: 'performance',
                                data: metrics
                            }, '*');
                        }
                    }
                }, 1000);
            });
        })();
        </script>
        ";
    }
}
