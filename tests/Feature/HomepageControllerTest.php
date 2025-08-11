<?php

use App\Http\Controllers\HomepageController;
use App\Services\ABTestingService;
use App\Services\HomepageService;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;

uses(Tests\TestCase::class);

beforeEach(function () {
    // Create mocks
    $this->homepageService = Mockery::mock(HomepageService::class);
    $this->abTestingService = Mockery::mock(ABTestingService::class);

    // Bind mocks in container
    $this->app->instance(HomepageService::class, $this->homepageService);
    $this->app->instance(ABTestingService::class, $this->abTestingService);

    // Create controller instance
    $this->controller = new HomepageController(
        $this->homepageService,
        $this->abTestingService
    );
});

afterEach(function () {
    Mockery::close();
});

test('index handles null service responses gracefully', function () {
    // Mock HomepageService::getPersonalizedContent to return null
    $this->homepageService
        ->shouldReceive('getPersonalizedContent')
        ->once()
        ->with('individual', Mockery::any())
        ->andReturn(null);

    // Mock getDefaultContent method
    $this->homepageService
        ->shouldReceive('getDefaultContent')
        ->once()
        ->with('individual')
        ->andReturn([
            'hero' => [
                'headline' => 'Connect. Engage. Thrive.',
                'subtitle' => 'Join the alumni community that transforms careers and builds lasting connections',
            ],
            'cta' => [
                'primary' => ['text' => 'Get Started'],
                'secondary' => ['text' => 'Learn More'],
            ],
            'sections' => [],
            'meta' => [
                'title' => 'Alumate - Alumni Networking Platform',
                'description' => 'Connect with alumni, find mentors, and advance your career through meaningful networking',
            ],
        ]);

    // Mock deepMergeDefaults method
    $this->homepageService
        ->shouldReceive('deepMergeDefaults')
        ->once()
        ->andReturn([
            'hero' => [
                'headline' => 'Connect. Engage. Thrive.',
                'subtitle' => 'Join the alumni community that transforms careers and builds lasting connections',
            ],
            'cta' => [
                'primary' => ['text' => 'Get Started'],
                'secondary' => ['text' => 'Learn More'],
            ],
            'sections' => [],
            'meta' => [
                'title' => 'Alumate - Alumni Networking Platform',
                'description' => 'Connect with alumni, find mentors, and advance your career through meaningful networking',
            ],
        ]);

    // Mock ABTestingService::getActiveTests to return null
    $this->abTestingService
        ->shouldReceive('getActiveTests')
        ->once()
        ->with(Mockery::any(), 'individual')
        ->andReturn(null);

    // Mock formatABTestsForFrontend method
    $this->homepageService
        ->shouldReceive('formatABTestsForFrontend')
        ->once()
        ->with([])
        ->andReturn([]);

    // Mock tracking method to avoid errors
    $this->homepageService
        ->shouldReceive('trackPersonalizationEvent')
        ->once()
        ->with('individual', 'page_view', Mockery::any());

    // Create a request
    $request = Request::create('/');
    $request->setLaravelSession($this->app['session']->driver());

    // Call controller index method
    $response = $this->controller->index($request);

    // Assert Inertia response structure
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Homepage/Index')
        ->where('audience', 'individual')
        ->has('content')
        ->has('content.hero')
        ->has('content.cta')
        ->has('content.sections')
        ->where('abTests', [])
        ->has('meta')
        ->has('meta.title')
        ->has('meta.description')
        ->has('meta.canonical')
    );

    // Verify content structure is well-formed
    $props = $response->getOriginalContent()->getData()['page']['props'];

    expect($props['abTests'])->toBeArray();
    expect($props['meta']['title'])->not()->toBeEmpty();
    expect($props['meta']['description'])->not()->toBeEmpty();
    expect($props['meta']['canonical'])->not()->toBeEmpty();
});

test('institutional handles malformed ab tests safely', function () {
    // Mock getPersonalizedContent to return minimal/empty arrays
    $this->homepageService
        ->shouldReceive('getPersonalizedContent')
        ->once()
        ->with('institutional', Mockery::any())
        ->andReturn([
            'hero' => [],
            'cta' => [],
            'sections' => [],
        ]);

    // Mock deepMergeDefaults method
    $this->homepageService
        ->shouldReceive('deepMergeDefaults')
        ->once()
        ->andReturn([
            'hero' => [],
            'cta' => [],
            'sections' => [],
        ]);

    // Mock getActiveTests to return malformed items (non-arrays, missing variant etc)
    $this->abTestingService
        ->shouldReceive('getActiveTests')
        ->once()
        ->with(Mockery::any(), 'institutional')
        ->andReturn([
            'test1' => 'not_an_array',
            'test2' => null,
            'test3' => [
                'test' => ['name' => 'Test 3'],
                // missing 'variant' key
            ],
            'test4' => [
                'test' => null,
                'variant' => 'not_an_array',
            ],
        ]);

    // Mock formatABTestsForFrontend method
    $this->homepageService
        ->shouldReceive('formatABTestsForFrontend')
        ->once()
        ->andReturn([]);

    // Mock tracking method
    $this->homepageService
        ->shouldReceive('trackPersonalizationEvent')
        ->once()
        ->with('institutional', 'page_view', Mockery::any());

    // Create a request
    $request = Request::create('/institutional');
    $request->setLaravelSession($this->app['session']->driver());

    // Call controller institutional method - should not throw exceptions
    $response = $this->controller->institutional($request);

    // Assert no exceptions were thrown and props are well-formed
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Homepage/Index')
        ->where('audience', 'institutional')
        ->has('content')
        ->has('abTests')
    );

    // Verify props structure is safe
    $props = $response->getOriginalContent()->getData()['page']['props'];
    expect($props['abTests'])->toBeArray();
    expect($props['content'])->toBeArray();
});

test('getMetaData falls back to hero and defaults', function () {
    // Mock content missing meta but has hero
    $contentWithHero = [
        'hero' => [
            'headline' => 'Custom Hero Headline',
            'subtitle' => 'Custom Hero Subtitle',
        ],
        'cta' => [],
        'sections' => [],
        // notably missing 'meta' key
    ];

    $this->homepageService
        ->shouldReceive('getPersonalizedContent')
        ->once()
        ->with('individual', Mockery::any())
        ->andReturn($contentWithHero);

    // Mock deepMergeDefaults method
    $this->homepageService
        ->shouldReceive('deepMergeDefaults')
        ->once()
        ->andReturn($contentWithHero);

    $this->abTestingService
        ->shouldReceive('getActiveTests')
        ->once()
        ->andReturn([]);

    // Mock formatABTestsForFrontend method
    $this->homepageService
        ->shouldReceive('formatABTestsForFrontend')
        ->once()
        ->with([])
        ->andReturn([]);

    $this->homepageService
        ->shouldReceive('trackPersonalizationEvent')
        ->once();

    // Create a request with a specific URL
    $request = Request::create('https://example.com/homepage');
    $request->setLaravelSession($this->app['session']->driver());

    // Call controller
    $response = $this->controller->index($request);

    // Assert meta data extraction
    $props = $response->getOriginalContent()->getData()['page']['props'];

    // meta.title should equal hero headline
    expect($props['meta']['title'])->toBe('Custom Hero Headline');

    // canonical should equal request fullUrl
    expect($props['meta']['canonical'])->toBe('https://example.com/homepage');

    // Description should fall back to hero subtitle when meta is missing
    expect($props['meta']['description'])->toBe('Custom Hero Subtitle');
});
