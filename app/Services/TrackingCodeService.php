<?php

namespace App\Services;

use App\Models\LandingPage;
use App\Models\Template;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;

/**
 * Tracking Code Service
 *
 * Handles generation and injection of analytics tracking codes for published landing pages
 */
class TrackingCodeService
{
    private const CACHE_PREFIX = 'tracking_code_';
    private const CACHE_DURATION = 3600; // 1 hour

    /**
     * Generate analytics tracking code for a landing page
     *
     * @param int $landingPageId Landing page ID
     * @param string $provider Analytics provider (google, facebook, custom)
     * @return string
     */
    public function generateTrackingCode(int $landingPageId, string $provider): string
    {
        $cacheKey = self::CACHE_PREFIX . "{$landingPageId}_{$provider}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($landingPageId, $provider) {
            $landingPage = LandingPage::with(['tenant'])->find($landingPageId);

            if (!$landingPage) {
                return '';
            }

            $trackingCode = $landingPage->getTrackingCode($provider);

            if (!$trackingCode) {
                return '';
            }

            return $this->createTrackingScript($provider, $trackingCode, $landingPage);
        });
    }

    /**
     * Generate complete tracking script injection for landing page
     *
     * @param LandingPage $landingPage
     * @return string
     */
    public function generateFullTrackingScript(LandingPage $landingPage): string
    {
        if (!$landingPage->isPublished()) {
            return '';
        }

        $scripts = [];

        // Google Analytics
        $googleAnalyticsId = $this->getGoogleAnalyticsId($landingPage);
        if ($googleAnalyticsId) {
            $scripts[] = $this->createGoogleAnalyticsScript($googleAnalyticsId, $landingPage);
        }

        // Facebook Pixel
        $facebookPixelId = $this->getFacebookPixelId($landingPage);
        if ($facebookPixelId) {
            $scripts[] = $this->createFacebookPixelScript($facebookPixelId, $landingPage);
        }

        // Custom tracking code
        $customTrackingCode = $landingPage->custom_js;
        if ($customTrackingCode) {
            $scripts[] = $this->wrapCustomScript($customTrackingCode);
        }

        // Add template-event trackingjs
        $scripts[] = $this->createTemplateEventTracking($landingPage);

        return implode("\n", $scripts);
    }

    /**
     * Inject tracking code into landing page HTML
     *
     * @param string $html Original HTML content
     * @param LandingPage $landingPage
     * @return string Modified HTML with tracking code injected
     */
    public function injectTrackingCode(string $html, LandingPage $landingPage): string
    {
        if (!$landingPage->isPublished()) {
            return $html;
        }

        $trackingScript = $this->generateFullTrackingScript($landingPage);

        if (empty($trackingScript)) {
            return $html;
        }

        // Inject tracking code into <head> section
        $headTracking = $this->generateHeadTrackingScript($landingPage);
        $html = $this->injectIntoHead($html, $headTracking);

        // Inject page view tracking (before closing </body>)
        $pageViewScript = $this->createPageViewScript($landingPage);
        $html = $this->injectBeforeBodyEnd($html, $pageViewScript);

        return $html;
    }

    /**
     * Generate head tracking script
     *
     * @param LandingPage $landingPage
     * @return string
     */
    protected function generateHeadTrackingScript(LandingPage $landingPage): string
    {
        return $this->generateFullTrackingScript($landingPage);
    }

    /**
     * Create page view tracking script
     *
     * @param LandingPage $landingPage
     * @return string
     */
    protected function createPageViewScript(LandingPage $landingPage): string
    {
        $visitorId = $this->generateVisitorId();
        $sessionId = $this->generateSessionId();
        $utmData = $this->extractUtmData();

        $trackingCode = "
            <script>
                (function() {
                    var trackingData = {
                        landing_page_id: {$landingPage->id},
                        template_id: " . ($landingPage->template_id ?: 'null') . ",
                        tenant_id: " . ($landingPage->tenant_id ?: 'null') . ",
                        visitor_id: '{$visitorId}',
                        session_id: '{$sessionId}',
                        utm_data: " . json_encode($utmData) . ",
                        page_url: window.location.href,
                        referrer: document.referrer,
                        device_type: '{$this->detectDeviceType()}',
                        user_agent: navigator.userAgent,
                        viewport_width: window.innerWidth,
                        viewport_height: window.innerHeight
                    };

                    // Send page view event
                    fetch('/api/analytics/track', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            event_type: 'page_view',
                            landing_page_id: {$landingPage->id},
                            event_data: trackingData
                        })
                    }).catch(console.error);

                    // Track scroll events
                    var maxScroll = 0;
                    window.addEventListener('scroll', function() {
                        var scrollPos = (window.pageYOffset / (document.body offsetHeight - window.innerHeight)) * 100;
                        if (scrollPos > maxScroll) {
                            maxScroll = Math.round(scrollPos);
                        }
                    });

                    // Track time on page
                    var startTime = Date.now();
                    window.addEventListener('beforeunload', function() {
                        var timeOnPage = Math.round((Date.now() - startTime) / 1000);

                        fetch('/api/analytics/track', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({
                                event_type: 'page_exit',
                                landing_page_id: {$landingPage->id},
                                event_data: {
                                    time_on_page: timeOnPage,
                                    max_scroll: maxScroll,
                                    visitor_id: '{$visitorId}',
                                    session_id: '{$sessionId}'
                                }
                            }),
                            keepalive: true
                        }).catch(console.error);
                    });

                    // Track clicks on CTA elements
                    document.addEventListener('click', function(e) {
                        var target = e.target.closest('a[href], button, input[type=\"submit\"], [data-track]');
                        if (target) {
                            var elementText = target.textContent?.trim() || target.value || target.getAttribute('data-track') || 'CTA Element';

                            fetch('/api/analytics/track', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''
                                },
                                body: JSON.stringify({
                                    event_type: 'cta_click',
                                    landing_page_id: {$landingPage->id},
                                    event_data: {
                                        element_text: elementText,
                                        element_tag: target.tagName,
                                        element_href: target.getAttribute('href'),
                                        visitor_id: '{$visitorId}',
                                        session_id: '{$sessionId}'
                                    }
                                })
                            }).catch(console.error);
                        }
                    });
                })();
            </script>";

        return $trackingCode;
    }

    /**
     * Create template event tracking script
     *
     * @param LandingPage $landingPage
     * @return string
     */
    protected function createTemplateEventTracking(LandingPage $landingPage): string
    {
        $templateId = $landingPage->template_id;
        $tenantId = $landingPage->tenant_id;

        if (!$templateId) {
            return '';
        }

        $trackingScript = "
            <script>
                (function() {
                    var templateData = {
                        template_id: {$templateId},
                        tenant_id: " . ($tenantId ?: 'null') . ",
                        landing_page_id: {$landingPage->id},
                        version: '" . addslashes($landingPage->version) . "',
                        audience_type: '" . addslashes($landingPage->audience_type) . "',
                        campaign_type: '" . addslashes($landingPage->campaign_type) . "'
                    };

                    // Track template usage if user hasn't seen it before
                    if (!localStorage.getItem('template_tracked_{$templateId}')) {
                        fetch('/api/analytics/track-template-usage', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({
                                template_id: {$templateId},
                                tenant_id: " . ($tenantId ?: 'null') . ",
                                event_type: 'template_page_view',
                                additional_data: templateData
                            })
                        }).catch(console.error);

                        localStorage.setItem('template_tracked_{$templateId}', Date.now());
                    }
                })();
            </script>";

        return $trackingScript;
    }

    /**
     * Create Google Analytics tracking script
     *
     * @param string $analyticsId GA Tracking ID
     * @param LandingPage $landingPage
     * @return string
     */
    protected function createGoogleAnalyticsScript(string $analyticsId, LandingPage $landingPage): string
    {
        return "
            <!-- Google Analytics -->
            <script async src='https://www.googletagmanager.com/gtag/js?id={$analyticsId}'></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '{$analyticsId}', {
                    'custom_map': {
                        'custom_parameter': {
                            'landing_page_id': {$landingPage->id},
                            'template_id': " . ($landingPage->template_id ?: 'null') . ",
                            'tenant_id': " . ($landingPage->tenant_id ?: 'null') . "
                        }
                    }
                });
            </script>";
    }

    /**
     * Create Facebook Pixel tracking script
     *
     * @param string $pixelId Facebook Pixel ID
     * @param LandingPage $landingPage
     * @return string
     */
    protected function createFacebookPixelScript(string $pixelId, LandingPage $landingPage): string
    {
        return "
            <!-- Facebook Pixel Code -->
            <script>
                !function(f,b,e,v,n,t,s)
                {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t,s)}(window, document,'script',
                'https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', '{$pixelId}');
                fbq('track', 'PageView', {
                    landing_page_id: {$landingPage->id},
                    template_id: " . ($landingPage->template_id ?: 'null') . "
                });
            </script>
            <noscript><img height='1' width='1' style='display:none'
                src='https://www.facebook.com/tr?id={$pixelId}&ev=PageView&noscript=1'
            /></noscript>";
    }

    /**
     * Create generic tracking script wrapper
     *
     * @param string $provider Analytics provider name
     * @param mixed $trackingCode The tracking code/configuration
     * @param LandingPage $landingPage
     * @return string
     */
    protected function createTrackingScript(string $provider, $trackingCode, LandingPage $landingPage): string
    {
        switch ($provider) {
            case 'google':
                return $this->createGoogleAnalyticsScript($trackingCode, $landingPage);
            case 'facebook':
                return $this->createFacebookPixelScript($trackingCode, $landingPage);
            case 'custom':
                return $this->wrapCustomScript($trackingCode);
            default:
                return '';
        }
    }

    /**
     * Wrap custom tracking code
     *
     * @param string $customCode
     * @return string
     */
    protected function wrapCustomScript(string $customCode): string
    {
        return "<!-- Custom Tracking Code -->\n<script>{$customCode}</script>";
    }

    /**
     * Get Google Analytics ID for landing page
     *
     * @param LandingPage $landingPage
     * @return string|null
     */
    protected function getGoogleAnalyticsId(LandingPage $landingPage): ?string
    {
        // Check tenant level configuration first
        if ($landingPage->tenant && $landingPage->tenant->settings) {
            $settings = json_decode($landingPage->tenant->settings, true);
            if (isset($settings['analytics']['google_analytics_id'])) {
                return $settings['analytics']['google_analytics_id'];
            }
        }

        // Check landing page specific configuration
        if ($landingPage->settings && isset($landingPage->settings['google_analytics'])) {
            return $landingPage->settings['google_analytics'];
        }

        return null;
    }

    /**
     * Get Facebook Pixel ID for landing page
     *
     * @param LandingPage $landingPage
     * @return string|null
     */
    protected function getFacebookPixelId(LandingPage $landingPage): ?string
    {
        // Check tenant level configuration first
        if ($landingPage->tenant && $landingPage->tenant->settings) {
            $settings = json_decode($landingPage->tenant->settings, true);
            if (isset($settings['analytics']['facebook_pixel_id'])) {
                return $settings['analytics']['facebook_pixel_id'];
            }
        }

        // Check landing page specific configuration
        if ($landingPage->settings && isset($landingPage->settings['facebook_pixel'])) {
            return $landingPage->settings['facebook_pixel'];
        }

        return null;
    }

    /**
     * Inject script into HTML head section
     *
     * @param string $html
     * @param string $script
     * @return string
     */
    protected function injectIntoHead(string $html, string $script): string
    {
        if (empty($script)) {
            return $html;
        }

        // Find the closing </head> tag
        if (preg_match('/<\/head>/i', $html)) {
            return preg_replace('/<\/head>/i', $script . '</head>', $html, 1);
        }

        // Fallback: inject before closing </html>
        return preg_replace('/<\/html>/i', $script . '</html>', $html, 1);
    }

    /**
     * Inject script before closing body tag
     *
     * @param string $html
     * @param string $script
     * @return string
     */
    protected function injectBeforeBodyEnd(string $html, string $script): string
    {
        if (empty($script)) {
            return $html;
        }

        // Find the closing </body> tag
        if (preg_match('/<\/body>/i', $html)) {
            return preg_replace('/<\/body>/i', $script . '</body>', $html, 1);
        }

        // Fallback: inject before closing </html>
        return preg_replace('/<\/html>/i', $script . '</html>', $html, 1);
    }

    /**
     * Extract UTM data from URL parameters
     *
     * @return array
     */
    protected function extractUtmData(): array
    {
        return [
            'utm_source' => $_GET['utm_source'] ?? null,
            'utm_medium' => $_GET['utm_medium'] ?? null,
            'utm_campaign' => $_GET['utm_campaign'] ?? null,
            'utm_term' => $_GET['utm_term'] ?? null,
            'utm_content' => $_GET['utm_content'] ?? null,
        ];
    }

    /**
     * Generate unique visitor ID
     *
     * @return string
     */
    protected function generateVisitorId(): string
    {
        if (isset($_COOKIE['_visitor_id'])) {
            return $_COOKIE['_visitor_id'];
        }

        $visitorId = uniqid('visitor_', true);
        setcookie('_visitor_id', $visitorId, time() + (365 * 24 * 60 * 60), '/'); // 1 year

        return $visitorId;
    }

    /**
     * Generate unique session ID
     *
     * @return string
     */
    protected function generateSessionId(): string
    {
        if (isset($_COOKIE['_session_id'])) {
            return $_COOKIE['_session_id'];
        }

        $sessionId = uniqid('session_', true);
        setcookie('_session_id', $sessionId, 0, '/'); // Session cookie

        return $sessionId;
    }

    /**
     * Detect device type from user agent
     *
     * @return string
     */
    protected function detectDeviceType(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent)) {
            if (preg_match('/ipad|tablet/i', $userAgent)) {
                return 'tablet';
            }
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Generate tracking pixel HTML
     *
     * @param LandingPage $landingPage
     * @return string
     */
    public function generateTrackingPixel(LandingPage $landingPage): string
    {
        $trackingUrl = route('analytics-tracking-pixel', [
            'landing_page_id' => $landingPage->id,
            'visitor_id' => $this->generateVisitorId(),
            'session_id' => $this->generateSessionId()
        ]);

        return "<img src='{$trackingUrl}' width='1' height='1' style='display:none;' alt=''>";
    }

    /**
     * Generate SEO-friendly tracking meta tags
     *
     * @param LandingPage $landingPage
     * @return string
     */
    public function generateSEOMetaTags(LandingPage $landingPage): string
    {
        $metaTags = '';

        // Open Graph tags for better tracking
        $metaTags .= "<meta property='og:url' content='" . $landingPage->getFullPublicUrl() . "'>\n";
        $metaTags .= "<meta property='og:title' content='" . htmlspecialchars($landingPage->seo_title ?? $landingPage->name) . "'>\n";
        $metaTags .= "<meta property='og:description' content='" . htmlspecialchars($landingPage->seo_description ?? $landingPage->description) . "'>\n";

        // Twitter Card tags
        $metaTags .= "<meta name='twitter:card' content='summary'>\n";
        $metaTags .= "<meta name='twitter:title' content='" . htmlspecialchars($landingPage->seo_title ?? $landingPage->name) . "'>\n";
        $metaTags .= "<meta name='twitter:description' content='" . htmlspecialchars($landingPage->seo_description ?? $landingPage->description) . "'>\n";

        return $metaTags;
    }
}