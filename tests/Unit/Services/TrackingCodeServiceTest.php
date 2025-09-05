<?php

namespace Tests\Unit\Services;

use App\Models\LandingPage;
use App\Models\Template;
use App\Models\Tenant;
use App\Services\TrackingCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TrackingCodeService $service;
    protected Tenant $tenant;
    protected Template $template;
    protected LandingPage $landingPage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TrackingCodeService::class);

        $this->tenant = Tenant::factory()->create();
        $this->template = Template::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->landingPage = LandingPage::factory()->create([
            'template_id' => $this->template->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_inject_tracking_code_into_html()
    {
        $html = '<html><head><title>Test</title></head><body>Hello World</body></html>';

        $modifiedHtml = $this->service->injectTrackingCode($html, $this->landingPage);

        expect($modifiedHtml)->toBeString();
        expect($modifiedHtml)->toContain('<script>');
        expect($modifiedHtml)->toContain('landing_page_id');
        expect($modifiedHtml)->toContain($this->landingPage->id);
    }

    public function test_skips_injection_for_non_published_pages()
    {
        $unpublishedPage = LandingPage::factory()->create([
            'status' => 'draft',
            'tenant_id' => $this->tenant->id,
        ]);

        $html = '<html><body>Hello World</body></html>';

        $modifiedHtml = $this->service->injectTrackingCode($html, $unpublishedPage);

        expect($modifiedHtml)->toBe($html); // Should be unchanged
    }

    public function test_generates_google_analytics_script()
    {
        $this->landingPage->update([
            'settings' => ['google_analytics' => 'UA-12345-67']
        ]);

        $trackingCode = $this->service->generateFullTrackingScript($this->landingPage);

        expect($trackingCode)->toContain('gtag');
        expect($trackingCode)->toContain('UA-12345-67');
        expect($trackingCode)->toContain('custom_parameter');
        expect($trackingCode)->toContain('landing_page_id');
    }

    public function test_generates_facebook_pixel_script()
    {
        $this->landingPage->update([
            'settings' => ['facebook_pixel' => '123456789']
        ]);

        $trackingCode = $this->service->generateFullTrackingScript($this->landingPage);

        expect($trackingCode)->toContain('fbq');
        expect($trackingCode)->toContain('123456789');
        expect($trackingCode)->toContain('facebook');
    }

    public function test_includes_template_event_tracking()
    {
        $trackingScript = $this->service->generateFullTrackingScript($this->landingPage);

        expect($trackingScript)->toContain('template_id');
        expect($trackingScript)->toContain($this->template->id);
        expect($trackingScript)->toContain('template_page_view');
        expect($trackingScript)->toContain('localStorage.getItem');
    }

    public function test_generates_page_view_tracking_script()
    {
        $pageViewScript = $this->service->createPageViewScript($this->landingPage);

        expect($pageViewScript)->toContain('PageView');
        expect($pageViewScript)->toContain('trackingData');
        expect($pageViewScript)->toContain('visitor_id');
        expect($pageViewScript)->toContain('session_id');
        expect($pageViewScript)->toContain('addEventListener');
        expect($pageViewScript)->toContain('DOMContentLoaded');
    }

    public function test_tracks_scroll_events()
    {
        $pageViewScript = $this->service->createPageViewScript($this->landingPage);

        expect($pageViewScript)->toContain('scroll');
        expect($pageViewScript)->toContain('pageYOffset');
        expect($pageViewScript)->toContain('maxScroll');
    }

    public function test_tracks_time_on_page()
    {
        $pageViewScript = $this->service->createPageViewScript($this->landingPage);

        expect($pageViewScript)->toContain('Date.now');
        expect($pageViewScript)->toContain('startTime');
        expect($pageViewScript)->toContain('beforeunload');
        expect($pageViewScript)->toContain('page_exit');
    }

    public function test_tracks_cta_clicks()
    {
        $pageViewScript = $this->service->createPageViewScript($this->landingPage);

        expect($pageViewScript)->toContain('click');
        expect($pageViewScript)->toContain('cta_click');
        expect($pageViewScript)->toContain('getAttribute');
        expect($pageViewScript)->toContain('closest');
    }

    public function test_injects_into_head_section()
    {
        $html = '<html><head><title>Test</title></head><body>Hello World</body></html>';

        $script = '<script>console.log("test");</script>';
        $modifiedHtml = $this->service->injectIntoHead($html, $script);

        expect($modifiedHtml)->toContain('<script>console.log("test");</script>');
        expect($modifiedHtml)->toContain('<title>Test</title>');
        expect($modifiedHtml)->toContain('</head>');
    }

    public function test_injects_before_body_end()
    {
        $html = '<html><head></head><body>Hello World</body></html>';

        $script = '<script>console.log("test");</script>';
        $modifiedHtml = $this->service->injectBeforeBodyEnd($html, $script);

        expect($modifiedHtml)->toContain('<script>console.log("test");</script>');
        expect($modifiedHtml)->toContain('Hello World');
        expect($modifiedHtml)->toContain('</body>');
    }

    public function test_generates_visitor_id()
    {
        $visitorId = $this->service->generateVisitorId();

        expect($visitorId)->toBeString();
        expect(strlen($visitorId))->toBeGreaterThan(0);
        expect(str_starts_with($visitorId, 'visitor_'))->toBe(false); // No longer prefixed

        // Test with cookie
        $_COOKIE['_visitor_id'] = 'cookie_visitor_123';
        $cookieVisitorId = $this->service->generateVisitorId();

        expect($cookieVisitorId)->toBe('cookie_visitor_123');
    }

    public function test_generates_session_id()
    {
        $sessionId = $this->service->generateSessionId();

        expect($sessionId)->toBeString();
        expect(strlen($sessionId))->toBeGreaterThan(0);

        // Test with cookie
        $_COOKIE['_session_id'] = 'cookie_session_123';
        $cookieSessionId = $this->service->generateSessionId();

        expect($cookieSessionId)->toBe('cookie_session_123');
    }

    public function test_detects_device_types()
    {
        $testCases = [
            ['Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15', 'mobile'],
            ['Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15', 'tablet'],
            ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'desktop'],
            ['undefined', 'desktop'], // Fallback
        ];

        foreach ($testCases as [$userAgent, $expected]) {
            $_SERVER['HTTP_USER_AGENT'] = $userAgent;
            $deviceType = $this->service->detectDeviceType();

            expect($deviceType)->toBe($expected, "Failed for user agent: {$userAgent}");
        }
    }

    public function test_extracts_utm_data_from_url()
    {
        // Set up test query parameters
        $_GET['utm_source'] = 'google';
        $_GET['utm_medium'] = 'cpc';
        $_GET['utm_campaign'] = 'summer_sale';
        $_GET['utm_term'] = 'template_promotion';
        $_GET['utm_content'] = 'banner_ad';

        $utmData = $this->service->extractUtmData();

        expect($utmData)->toBeArray();
        expect($utmData)->toHaveKey('utm_source');
        expect($utmData)->toHaveKey('utm_medium');
        expect($utmData)->toHaveKey('utm_campaign');
        expect($utmData)->toHaveKey('utm_term');
        expect($utmData)->toHaveKey('utm_content');
        expect($utmData['utm_source'])->toBe('google');
        expect($utmData['utm_campaign'])->toBe('summer_sale');
    }

    public function test_generates_tracking_pixel()
    {
        $pixelUrl = $this->service->generateTrackingPixel($this->landingPage);

        expect($pixelUrl)->toContain('tracking-pixel');
        expect($pixelUrl)->toContain('landing_page_id');
        expect($pixelUrl)->toContain($this->landingPage->id);
        expect($pixelUrl)->toContain('style="display:none;"');
        expect($pixelUrl)->toContain('width="1"');
        expect($pixelUrl)->toContain('height="1"');
    }

    public function test_generates_seo_meta_tags()
    {
        $this->landingPage->update([
            'seo_title' => 'Test Landing Page',
            'seo_description' => 'Test description',
        ]);

        $metaTags = $this->service->generateSEOMetaTags($this->landingPage);

        expect($metaTags)->toContain('og:url');
        expect($metaTags)->toContain('og:title');
        expect($metaTags)->toContain('og:description');
        expect($metaTags)->toContain('twitter:card');
        expect($metaTags)->toContain('Test Landing Page');
        expect($metaTags)->toContain('Test description');
    }

    public function test_wraps_custom_scripts()
    {
        $customScript = 'console.log("Hello from custom script!");';

        $wrapped = $this->service->wrapCustomScript($customScript);

        expect($wrapped)->toContain('<!-- Custom Tracking Code -->');
        expect($wrapped)->toContain('<script>');
        expect($wrapped)->toContain('console.log("Hello from custom script!")');
        expect($wrapped)->toContain('</script>');
    }

    public function test_handles_google_analytics_from_tenant_settings()
    {
        $this->tenant->update([
            'settings' => json_encode([
                'analytics' => [
                    'google_analytics_id' => 'GA-98765'
                ]
            ])
        ]);

        $this->landingPage->update(['settings' => null]); // Clear page settings

        $script = $this->service->generateFullTrackingScript($this->landingPage);

        expect($script)->toContain('GA-98765');
    }

    public function test_handles_facebook_pixel_from_tenant_settings()
    {
        $this->tenant->update([
            'settings' => json_encode([
                'analytics' => [
                    'facebook_pixel_id' => '987654321'
                ]
            ])
        ]);

        $this->landingPage->update(['settings' => null]); // Clear page settings

        $script = $this->service->generateFullTrackingScript($this->landingPage);

        expect($script)->toContain('987654321');
    }

    public function test_custom_tracking_code_injection()
    {
        $this->landingPage->update([
            'custom_js' => 'console.log("Custom tracking");'
        ]);

        $script = $this->service->generateFullTrackingScript($this->landingPage);

        expect($script)->toContain('console.log("Custom tracking")');
    }

    public function test_caching_behavior_for_tracking_scripts()
    {
        // Generate script (this will cache)
        $script1 = $this->service->generateFullTrackingScript($this->landingPage);

        // Generate again (should get from cache)
        $script2 = $this->service->generateFullTrackingScript($this->landingPage);

        expect($script1)->toBe($script2);
    }

    public function test_edge_cases_with_invalid_data()
    {
        // Test with non-existent landing page
        $fakeLandingPage = new LandingPage(['id' => 99999, 'status' => 'published']);

        $script = $this->service->generateFullTrackingScript($this->landingPage);

        expect($script)->toBeString();
        expect(strlen($script))->toBeGreaterThan(0);
    }
}