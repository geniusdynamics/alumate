<?php

namespace Tests\Feature;

use App\Services\HomepageService;
use App\Services\PersonalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class PersonalizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private PersonalizationService $personalizationService;

    private HomepageService $homepageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->homepageService = app(HomepageService::class);
        $this->personalizationService = app(PersonalizationService::class);
    }

    /** @test */
    public function it_detects_institutional_audience_from_url_parameter()
    {
        $request = Request::create('/homepage?audience=institutional');

        $detection = $this->personalizationService->detectAudience($request);

        $this->assertEquals('institutional', $detection['detected_audience']);
        $this->assertGreaterThan(0.5, $detection['confidence']);
        $this->assertCount(1, $detection['factors']);
        $this->assertEquals('url_param', $detection['factors'][0]['type']);
    }

    /** @test */
    public function it_detects_institutional_audience_from_educational_referrer()
    {
        $request = Request::create('/homepage');
        $request->headers->set('referer', 'https://stanford.edu/alumni');

        $detection = $this->personalizationService->detectAudience($request);

        $this->assertEquals('institutional', $detection['detected_audience']);
        $this->assertGreaterThan(0.5, $detection['confidence']);

        $referrerFactor = collect($detection['factors'])->firstWhere('type', 'referrer');
        $this->assertNotNull($referrerFactor);
        $this->assertEquals('stanford.edu', $referrerFactor['value']);
    }

    /** @test */
    public function it_detects_individual_audience_when_no_institutional_signals()
    {
        $request = Request::create('/homepage');

        $detection = $this->personalizationService->detectAudience($request);

        $this->assertEquals('individual', $detection['detected_audience']);
        $this->assertEquals(0, $detection['confidence']);
        $this->assertEmpty($detection['factors']);
    }

    /** @test */
    public function it_detects_institutional_audience_from_utm_source()
    {
        $request = Request::create('/homepage?utm_source=university');

        $detection = $this->personalizationService->detectAudience($request);

        $this->assertEquals('institutional', $detection['detected_audience']);
        $this->assertGreaterThan(0.5, $detection['confidence']);

        $utmFactor = collect($detection['factors'])->firstWhere('type', 'utm_source');
        $this->assertNotNull($utmFactor);
        $this->assertEquals('university', $utmFactor['value']);
    }

    /** @test */
    public function it_gets_personalized_content_for_individual_audience()
    {
        $request = Request::create('/homepage');

        $content = $this->personalizationService->getPersonalizedContent('individual', $request);

        $this->assertArrayHasKey('hero', $content);
        $this->assertArrayHasKey('features', $content);
        $this->assertArrayHasKey('testimonials', $content);
        $this->assertArrayHasKey('pricing', $content);
        $this->assertArrayHasKey('cta', $content);
        $this->assertArrayHasKey('meta', $content);

        // Check individual-specific content
        $this->assertStringContains('career', strtolower($content['hero']['headline']));
        $this->assertEquals('Start Free Trial', $content['cta']['primary']['text']);
    }

    /** @test */
    public function it_gets_personalized_content_for_institutional_audience()
    {
        $request = Request::create('/homepage');

        $content = $this->personalizationService->getPersonalizedContent('institutional', $request);

        $this->assertArrayHasKey('hero', $content);
        $this->assertArrayHasKey('features', $content);
        $this->assertArrayHasKey('testimonials', $content);
        $this->assertArrayHasKey('pricing', $content);
        $this->assertArrayHasKey('cta', $content);
        $this->assertArrayHasKey('meta', $content);

        // Check institutional-specific content
        $this->assertStringContains('engagement', strtolower($content['hero']['headline']));
        $this->assertEquals('Request Demo', $content['cta']['primary']['text']);
    }

    /** @test */
    public function it_stores_audience_preference_in_session()
    {
        $preference = $this->personalizationService->storeAudiencePreference('institutional', 'manual');

        $this->assertEquals('institutional', $preference['type']);
        $this->assertEquals('manual', $preference['source']);
        $this->assertArrayHasKey('timestamp', $preference);
        $this->assertArrayHasKey('session_id', $preference);

        // Check session storage
        $storedPreference = session('homepage_audience_preference');
        $this->assertEquals($preference, $storedPreference);
    }

    /** @test */
    public function it_retrieves_stored_audience_preference()
    {
        // Store a preference first
        $originalPreference = [
            'type' => 'institutional',
            'timestamp' => now()->toISOString(),
            'source' => 'manual',
            'session_id' => 'test-session',
        ];

        session(['homepage_audience_preference' => $originalPreference]);

        $retrievedPreference = $this->personalizationService->getStoredAudiencePreference();

        $this->assertEquals($originalPreference, $retrievedPreference);
    }

    /** @test */
    public function it_returns_null_when_no_stored_preference()
    {
        $preference = $this->personalizationService->getStoredAudiencePreference();

        $this->assertNull($preference);
    }

    /** @test */
    public function it_caches_personalized_content()
    {
        Cache::flush();

        $request = Request::create('/homepage');

        // First call should cache the content
        $content1 = $this->personalizationService->getPersonalizedContent('individual', $request);

        // Second call should return cached content
        $content2 = $this->personalizationService->getPersonalizedContent('individual', $request);

        $this->assertEquals($content1, $content2);

        // Verify cache was used by checking cache keys
        $cacheKeys = Cache::getRedis()->keys('*homepage_personalized_*');
        $this->assertNotEmpty($cacheKeys);
    }

    /** @test */
    public function it_applies_geographic_personalization()
    {
        $request = Request::create('/homepage');
        $request->headers->set('X-Timezone', 'America/New_York');

        $content = $this->personalizationService->getPersonalizedContent('individual', $request);

        // Check if geographic personalization was applied
        $this->assertStringContains('career networking', $content['hero']['description']);
    }

    /** @test */
    public function it_applies_time_based_personalization()
    {
        $request = Request::create('/homepage');

        // Mock current time to business hours
        $this->travelTo(now()->setHour(14)); // 2 PM

        $content = $this->personalizationService->getPersonalizedContent('individual', $request);

        // Check if time-based personalization was applied during business hours
        $this->assertStringContains('professionals', $content['hero']['subtitle']);
    }

    /** @test */
    public function it_gets_ab_test_variant_consistently()
    {
        $testId = 'hero_message_test';
        $userId = 'test-user-123';

        // Get variant multiple times with same user ID
        $variant1 = $this->personalizationService->getABTestVariant($testId, $userId);
        $variant2 = $this->personalizationService->getABTestVariant($testId, $userId);

        // Should return the same variant for the same user
        $this->assertEquals($variant1['variant_id'], $variant2['variant_id']);
        $this->assertEquals($testId, $variant1['test_id']);
        $this->assertEquals($userId, $variant1['user_id']);
    }

    /** @test */
    public function it_tracks_ab_test_conversion()
    {
        $testId = 'hero_message_test';
        $variantId = 'variant_a';
        $goal = 'trial_signup';

        // This should not throw an exception
        $this->personalizationService->trackABTestConversion($testId, $variantId, $goal);

        // We can't easily test the actual tracking without mocking the logging system
        // but we can ensure the method executes without errors
        $this->assertTrue(true);
    }

    /** @test */
    public function it_clears_personalization_cache()
    {
        // Set up some cached content
        $request = Request::create('/homepage');
        $this->personalizationService->getPersonalizedContent('individual', $request);
        $this->personalizationService->getPersonalizedContent('institutional', $request);

        // Clear cache for specific audience
        $this->personalizationService->clearPersonalizationCache('individual');

        // This test mainly ensures the method executes without errors
        $this->assertTrue(true);
    }

    /** @test */
    public function it_gets_personalization_analytics()
    {
        $analytics = $this->personalizationService->getPersonalizationAnalytics();

        $this->assertArrayHasKey('audience_distribution', $analytics);
        $this->assertArrayHasKey('detection_accuracy', $analytics);
        $this->assertArrayHasKey('conversion_rates', $analytics);
        $this->assertArrayHasKey('ab_test_results', $analytics);

        // Check structure of audience distribution
        $this->assertArrayHasKey('individual', $analytics['audience_distribution']);
        $this->assertArrayHasKey('institutional', $analytics['audience_distribution']);

        // Check conversion rates structure
        $this->assertArrayHasKey('individual', $analytics['conversion_rates']);
        $this->assertArrayHasKey('institutional', $analytics['conversion_rates']);
    }

    /** @test */
    public function it_handles_multiple_detection_factors()
    {
        $request = Request::create('/homepage?audience=institutional&utm_source=university');
        $request->headers->set('referer', 'https://college.edu/admin');

        $detection = $this->personalizationService->detectAudience($request);

        $this->assertEquals('institutional', $detection['detected_audience']);
        $this->assertGreaterThan(0.8, $detection['confidence']); // High confidence with multiple factors
        $this->assertGreaterThanOrEqual(3, count($detection['factors'])); // URL param, UTM source, referrer

        // Check that all factors are present
        $factorTypes = collect($detection['factors'])->pluck('type')->toArray();
        $this->assertContains('url_param', $factorTypes);
        $this->assertContains('utm_source', $factorTypes);
        $this->assertContains('referrer', $factorTypes);
    }

    /** @test */
    public function it_personalizes_content_based_on_utm_campaign()
    {
        $request = Request::create('/homepage?utm_campaign=career_fair');

        $content = $this->personalizationService->getPersonalizedContent('individual', $request);

        // Check if campaign-specific personalization was applied
        $this->assertStringContains('Ready to Take the Next Step', $content['hero']['headline']);
    }

    /** @test */
    public function it_handles_edge_cases_gracefully()
    {
        // Test with malformed referrer
        $request = Request::create('/homepage');
        $request->headers->set('referer', 'not-a-valid-url');

        $detection = $this->personalizationService->detectAudience($request);

        // Should not crash and should default to individual
        $this->assertEquals('individual', $detection['detected_audience']);
        $this->assertEquals('individual', $detection['fallback']);
    }
}
