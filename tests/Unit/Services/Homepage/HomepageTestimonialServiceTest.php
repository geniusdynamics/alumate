<?php

declare(strict_types=1);

use App\Models\SuccessStory;
use App\Models\Testimonial;
use App\Services\Homepage\HomepageTestimonialService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(HomepageTestimonialService::class);
});

it('returns individual testimonials for general audience', function () {
    // Arrange
    Testimonial::factory()->count(3)->create([
        'audience_type' => 'individual',
        'is_featured' => true,
        'is_approved' => true,
    ]);

    // Act
    $testimonials = $this->service->getTestimonials('general');

    // Assert
    expect($testimonials)->toBeCollection()
        ->and($testimonials)->toHaveCount(3)
        ->and($testimonials->first())->toHaveKeys(['id', 'quote', 'author', 'metrics']);
});

it('returns institutional testimonials for institutional audience', function () {
    // Arrange
    Testimonial::factory()->count(2)->create([
        'audience_type' => 'institutional',
        'is_featured' => true,
        'is_approved' => true,
    ]);

    // Act
    $testimonials = $this->service->getTestimonials('institutional');

    // Assert
    expect($testimonials)->toBeCollection()
        ->and($testimonials->first())->toHaveKeys(['id', 'quote', 'institution', 'administrator', 'results']);
});

it('returns curated fallback testimonials when database is empty', function () {
    $testimonials = $this->service->getTestimonials('general');

    expect($testimonials)->toBeCollection()
        ->and($testimonials)->toHaveCount(2)
        ->and($testimonials->first()['author'])->toHaveKeys(['name', 'graduation_year', 'current_role', 'current_company']);
});

it('returns success stories', function () {
    // Arrange
    SuccessStory::factory()->count(3)->create([
        'is_published' => true,
        'is_featured' => true,
    ]);

    // Act
    $stories = $this->service->getSuccessStories();

    // Assert
    expect($stories)->toBeCollection()
        ->and($stories)->toHaveCount(3);
});

it('limits success stories to specified count', function () {
    // Arrange
    SuccessStory::factory()->count(10)->create([
        'is_published' => true,
        'is_featured' => true,
    ]);

    // Act
    $stories = $this->service->getSuccessStories(3);

    // Assert
    expect($stories)->toHaveCount(3);
});

it('caches testimonials for performance', function () {
    // First call
    $start = microtime(true);
    $this->service->getTestimonials('general');
    $duration1 = microtime(true) - $start;

    // Second call (cache hit)
    $start = microtime(true);
    $this->service->getTestimonials('general');
    $duration2 = microtime(true) - $start;

    expect($duration2)->toBeLessThan($duration1);
});
