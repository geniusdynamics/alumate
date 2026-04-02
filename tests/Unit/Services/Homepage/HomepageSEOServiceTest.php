<?php

declare(strict_types=1);

use App\Services\Homepage\HomepageSEOService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(HomepageSEOService::class);
});

it('returns general SEO metadata', function () {
    $seo = $this->service->getSEOMetadata('general');

    expect($seo)->toBeArray()
        ->and($seo)->toHaveKeys(['title', 'description', 'keywords', 'canonical', 'robots', 'og_type', 'structured_data'])
        ->and($seo['title'])->toBe('Alumate - Connect, Grow, Succeed Together')
        ->and($seo['og_type'])->toBe('website');
});

it('returns institutional SEO metadata', function () {
    $seo = $this->service->getSEOMetadata('institutional');

    expect($seo['title'])->toBe('Alumate for Institutions - Transform Alumni Engagement')
        ->and($seo['description'])->toContain('400%')
        ->and($seo['structured_data'])->toHaveKey('@type');
});

it('returns employer SEO metadata', function () {
    $seo = $this->service->getSEOMetadata('employer');

    expect($seo['title'])->toBe('Alumate for Employers - Find Top Talent')
        ->and($seo['description'])->toContain('qualified alumni');
});

it('returns social sharing tags', function () {
    $tags = $this->service->getSocialSharingTags('general');

    expect($tags)->toBeArray()
        ->and($tags)->toHaveKeys([
            'og:title', 'og:description', 'og:image', 'og:url',
            'twitter:card', 'twitter:title', 'twitter:description', 'twitter:image',
        ]);
});

it('returns institutional social sharing tags', function () {
    $tags = $this->service->getSocialSharingTags('institutional');

    expect($tags['og:title'])->toBe('Alumate for Institutions - Transform Alumni Engagement')
        ->and($tags['og:image'])->toContain('institutional');
});

it('caches SEO metadata for performance', function () {
    $start = microtime(true);
    $this->service->getSEOMetadata('general');
    $duration1 = microtime(true) - $start;

    $start = microtime(true);
    $this->service->getSEOMetadata('general');
    $duration2 = microtime(true) - $start;

    expect($duration2)->toBeLessThan($duration1);
});
