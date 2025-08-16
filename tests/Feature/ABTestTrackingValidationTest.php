<?php

use App\Http\Controllers\HomepageController;
use App\Services\ABTestingService;
use App\Services\HomepageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Create mocks
    $this->homepageService = Mockery::mock(HomepageService::class);
    $this->abTestingService = Mockery::mock(ABTestingService::class);

    // Bind mocks in container
    $this->app->instance(HomepageService::class, $this->homepageService);
    $this->app->instance(ABTestingService::class, $this->abTestingService);

    // Create controller instances
    $this->controller = new HomepageController(
        $this->homepageService,
        $this->abTestingService
    );
});

afterEach(function () {
    Mockery::close();
});

test('trackCTAClick normalizes missing ab_tests to empty array', function () {
    // Mock tracking event call
    $this->homepageService
        ->shouldReceive('trackPersonalizationEvent')
        ->once()
        ->with('individual', 'cta_click', Mockery::any());

    // Create request with missing ab_tests
    $request = Request::create('/track-cta', 'POST', [
        'action' => 'sign_up',
        'section' => 'hero',
        'audience' => 'individual',
        'additional_data' => [],
        // missing ab_tests key
    ]);
    $request->setLaravelSession($this->app['session']->driver());

    // Call trackCTAClick - should not throw exception
    $response = $this->controller->trackCTAClick($request);

    // Verify successful response
    expect($response->getStatusCode())->toBe(200);
    $data = json_decode($response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($data['message'])->toBe('CTA click tracked successfully');
});

test('trackCTAClick validates ab_tests data types and skips invalid entries', function () {
    // Mock tracking event call
    $this->homepageService
        ->shouldReceive('trackPersonalizationEvent')
        ->once()
        ->with('individual', 'cta_click', Mockery::any());

    // Mock trackConversion call for valid entries only
    $this->abTestingService
        ->shouldReceive('trackConversion')
        ->once()
        ->with('valid_test', 'variant_a', 'hero_cta_click', Mockery::any(), Mockery::any());

    // Create request with mixed valid/invalid ab_tests
    $request = Request::create('/track-cta', 'POST', [
        'action' => 'sign_up',
        'section' => 'hero',
        'audience' => 'individual',
        'additional_data' => [],
        'ab_tests' => [
            'valid_test' => 'variant_a',        // Valid - both key and value are strings
            123 => 'variant_b',                 // Invalid - numeric key
            'test_with_array' => ['not_string'], // Invalid - array value
            'test_with_null' => null,           // Invalid - null value
            'empty_test' => '',                 // Valid but empty string
        ],
    ]);
    $request->setLaravelSession($this->app['session']->driver());

    // Mock Log::warning to capture invalid entries
    Log::shouldReceive('warning')
        ->times(3) // Should log 3 invalid entries
        ->with('Skipping invalid A/B test data - keys/values must be scalar strings', Mockery::any());

    // Call trackCTAClick
    $response = $this->controller->trackCTAClick($request);

    // Verify successful response
    expect($response->getStatusCode())->toBe(200);
    $data = json_decode($response->getContent(), true);
    expect($data['success'])->toBeTrue();
});

test('trackCTAClick handles ABTestingService exceptions gracefully', function () {
    // Mock tracking event call
    $this->homepageService
        ->shouldReceive('trackPersonalizationEvent')
        ->once()
        ->with('individual', 'cta_click', Mockery::any());

    // Mock trackConversion to throw exception
    $this->abTestingService
        ->shouldReceive('trackConversion')
        ->once()
        ->andThrow(new Exception('Service temporarily unavailable'));

    // Create request with valid ab_tests
    $request = Request::create('/track-cta', 'POST', [
        'action' => 'sign_up',
        'section' => 'hero',
        'audience' => 'individual',
        'ab_tests' => [
            'test_id' => 'variant_id',
        ],
    ]);
    $request->setLaravelSession($this->app['session']->driver());

    // Mock Log::warning to capture exception
    Log::shouldReceive('warning')
        ->once()
        ->with('Failed to track A/B test conversion', Mockery::any());

    // Call trackCTAClick - should not throw exception despite service failure
    $response = $this->controller->trackCTAClick($request);

    // Verify successful response (stability requirement)
    expect($response->getStatusCode())->toBe(200);
    $data = json_decode($response->getContent(), true);
    expect($data['success'])->toBeTrue();
});

test('trackConversion validates non-empty strings', function () {
    // Create request with empty/whitespace strings
    $request = Request::create('/track-conversion', 'POST', [
        'test_id' => '  ',
        'variant_id' => 'variant_a',
        'goal' => '',
        'audience' => 'individual',
    ]);
    $request->setLaravelSession($this->app['session']->driver());

    // Call trackConversion
    $response = $this->controller->trackConversion($request);

    // Should return 400 error for empty strings
    expect($response->getStatusCode())->toBe(400);
    $data = json_decode($response->getContent(), true);
    expect($data['success'])->toBeFalse();
    expect($data['message'])->toBe('test_id, variant_id, and goal cannot be empty');
});

test('trackConversion handles service exceptions and returns success', function () {
    // Mock trackConversion to throw exception
    $this->abTestingService
        ->shouldReceive('trackConversion')
        ->once()
        ->andThrow(new Exception('Database connection failed'));

    // Create request with valid data
    $request = Request::create('/track-conversion', 'POST', [
        'test_id' => 'test123',
        'variant_id' => 'variant_a',
        'goal' => 'signup',
        'audience' => 'individual',
    ]);
    $request->setLaravelSession($this->app['session']->driver());

    // Mock Log::warning to capture exception
    Log::shouldReceive('warning')
        ->once()
        ->with('Failed to track conversion in trackConversion endpoint', Mockery::any());

    // Call trackConversion - should handle exception gracefully
    $response = $this->controller->trackConversion($request);

    // Should return success for stability (as per requirements)
    expect($response->getStatusCode())->toBe(200);
    $data = json_decode($response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($data['message'])->toBe('Conversion tracked successfully');
});
