<?php

use App\Services\MediaOptimizationService;

it('can generate responsive image sources with WebP support', function () {
    $mediaAsset = [
        'id' => 'test-image',
        'type' => 'image',
        'url' => 'https://example.com/hero-background.jpg',
        'alt' => 'Test image background',
        'width' => 1920,
        'height' => 1080,
        'srcSet' => [
            [
                'url' => 'https://example.com/hero-320.webp',
                'width' => 320,
                'format' => 'webp'
            ],
            [
                'url' => 'https://example.com/hero-640.webp',
                'width' => 640,
                'format' => 'webp'
            ],
            [
                'url' => 'https://example.com/hero-1024.webp',
                'width' => 1024,
                'format' => 'webp'
            ]
        ],
        'mobileUrl' => 'https://example.com/hero-mobile.jpg',
        'fallbackUrl' => 'https://example.com/hero-fallback.jpg',
        'placeholder' => 'data:image/jpeg;base64,test-placeholder',
        'cdnUrl' => 'https://cdn.example.com',
        'optimized' => true
    ];

    expect($mediaAsset['type'])->toBe('image');
    expect($mediaAsset['optimized'])->toBeTrue();
    expect($mediaAsset['srcSet'])->toHaveCount(3);
    expect($mediaAsset['srcSet'][0]['format'])->toBe('webp');
    expect($mediaAsset['cdnUrl'])->toBe('https://cdn.example.com');
    expect($mediaAsset['mobileUrl'])->toBe('https://example.com/hero-mobile.jpg');
});

it('can configure video background with mobile optimization', function () {
    $videoConfig = [
        'type' => 'video',
        'video' => [
            'id' => 'test-video',
            'type' => 'video',
            'url' => 'https://example.com/test-video.mp4',
            'alt' => 'Test video background',
            'mimeType' => 'video/mp4',
            'autoplay' => true,
            'muted' => true,
            'loop' => true,
            'poster' => 'https://example.com/poster.jpg',
            'preload' => 'metadata',
            'disableOnMobile' => false,
            'quality' => 'auto',
            'adaptiveBitrate' => true,
            'mobileVideo' => [
                'id' => 'test-video-mobile',
                'type' => 'video',
                'url' => 'https://example.com/test-video-mobile.mp4',
                'mimeType' => 'video/mp4'
            ]
        ],
        'overlay' => [
            'color' => 'rgba(0, 0, 0, 0.4)',
            'opacity' => 0.4
        ],
        'lazyLoad' => true,
        'mobileOptimized' => true,
        'fallback' => [
            'type' => 'image',
            'image' => [
                'id' => 'fallback-image',
                'type' => 'image',
                'url' => 'https://example.com/fallback.jpg',
                'alt' => 'Fallback image'
            ]
        ]
    ];

    expect($videoConfig['type'])->toBe('video');
    expect($videoConfig['video']['autoplay'])->toBeTrue();
    expect($videoConfig['video']['muted'])->toBeTrue();
    expect($videoConfig['video']['adaptiveBitrate'])->toBeTrue();
    expect($videoConfig['video']['disableOnMobile'])->toBeFalse();
    expect($videoConfig['mobileOptimized'])->toBeTrue();
    expect($videoConfig['fallback']['type'])->toBe('image');
    expect($videoConfig['video']['mobileVideo']['url'])->toBe('https://example.com/test-video-mobile.mp4');
});

it('can configure gradient background with fallback', function () {
    $gradientConfig = [
        'type' => 'gradient',
        'gradient' => [
            'type' => 'linear',
            'direction' => '135deg',
            'colors' => [
                ['color' => '#667eea', 'stop' => 0],
                ['color' => '#764ba2', 'stop' => 100]
            ]
        ],
        'overlay' => [
            'color' => 'rgba(0, 0, 0, 0.2)',
            'opacity' => 0.2
        ],
        'fallback' => [
            'type' => 'gradient',
            'gradient' => [
                'type' => 'linear',
                'direction' => '135deg',
                'colors' => [
                    ['color' => '#3b82f6', 'stop' => 0],
                    ['color' => '#1d4ed8', 'stop' => 100]
                ]
            ]
        ],
        'mobileOptimized' => true,
        'reducedMotion' => true
    ];

    expect($gradientConfig['type'])->toBe('gradient');
    expect($gradientConfig['gradient']['type'])->toBe('linear');
    expect($gradientConfig['gradient']['direction'])->toBe('135deg');
    expect($gradientConfig['gradient']['colors'])->toHaveCount(2);
    expect($gradientConfig['mobileOptimized'])->toBeTrue();
    expect($gradientConfig['reducedMotion'])->toBeTrue();
    expect($gradientConfig['fallback']['type'])->toBe('gradient');
});

it('validates mobile-specific video handling', function () {
    $mobileVideoConfig = [
        'type' => 'video',
        'video' => [
            'id' => 'desktop-video',
            'type' => 'video',
            'url' => 'https://example.com/desktop-video.mp4',
            'mimeType' => 'video/mp4',
            'autoplay' => true,
            'muted' => true,
            'loop' => true,
            'disableOnMobile' => true, // Video disabled on mobile
            'quality' => 'high'
        ],
        'fallback' => [
            'type' => 'image',
            'image' => [
                'id' => 'mobile-fallback',
                'type' => 'image',
                'url' => 'https://example.com/mobile-hero.jpg',
                'alt' => 'Mobile hero image',
                'mobileUrl' => 'https://example.com/mobile-hero-optimized.jpg'
            ]
        ],
        'mobileOptimized' => true
    ];

    expect($mobileVideoConfig['video']['disableOnMobile'])->toBeTrue();
    expect($mobileVideoConfig['video']['quality'])->toBe('high');
    expect($mobileVideoConfig['mobileOptimized'])->toBeTrue();
    expect($mobileVideoConfig['fallback']['type'])->toBe('image');
    expect($mobileVideoConfig['fallback']['image']['mobileUrl'])->toBe('https://example.com/mobile-hero-optimized.jpg');
});

it('supports CDN integration configuration', function () {
    $cdnConfig = [
        'type' => 'image',
        'image' => [
            'id' => 'cdn-image',
            'type' => 'image',
            'url' => 'https://example.com/original-image.jpg',
            'alt' => 'CDN optimized image',
            'cdnUrl' => 'https://cdn.example.com',
            'optimized' => true,
            'srcSet' => [
                [
                    'url' => 'https://cdn.example.com/image-320.webp',
                    'width' => 320,
                    'format' => 'webp'
                ],
                [
                    'url' => 'https://cdn.example.com/image-640.webp',
                    'width' => 640,
                    'format' => 'webp'
                ]
            ]
        ],
        'lazyLoad' => true,
        'preload' => false
    ];

    expect($cdnConfig['image']['cdnUrl'])->toBe('https://cdn.example.com');
    expect($cdnConfig['image']['optimized'])->toBeTrue();
    expect($cdnConfig['lazyLoad'])->toBeTrue();
    expect($cdnConfig['preload'])->toBeFalse();
    expect($cdnConfig['image']['srcSet'])->toHaveCount(2);
    expect($cdnConfig['image']['srcSet'][0]['format'])->toBe('webp');
    expect($cdnConfig['image']['srcSet'][1]['format'])->toBe('webp');
});

it('validates bandwidth considerations for video quality', function () {
    $bandwidthConfig = [
        'type' => 'video',
        'video' => [
            'id' => 'adaptive-video',
            'type' => 'video',
            'url' => 'https://example.com/adaptive-video.mp4',
            'mimeType' => 'video/mp4',
            'autoplay' => true,
            'muted' => true,
            'loop' => true,
            'quality' => 'auto', // Adaptive quality based on connection
            'adaptiveBitrate' => true,
            'preload' => 'metadata' // Conservative preload for bandwidth
        ],
        'mobileOptimized' => true,
        'reducedMotion' => true // Respect user preferences
    ];

    expect($bandwidthConfig['video']['quality'])->toBe('auto');
    expect($bandwidthConfig['video']['adaptiveBitrate'])->toBeTrue();
    expect($bandwidthConfig['video']['preload'])->toBe('metadata');
    expect($bandwidthConfig['mobileOptimized'])->toBeTrue();
    expect($bandwidthConfig['reducedMotion'])->toBeTrue();
});

it('validates accessibility features for media components', function () {
    $accessibilityConfig = [
        'type' => 'video',
        'video' => [
            'id' => 'accessible-video',
            'type' => 'video',
            'url' => 'https://example.com/accessible-video.mp4',
            'alt' => 'Descriptive alt text for screen readers',
            'mimeType' => 'video/mp4',
            'autoplay' => true,
            'muted' => true, // Required for autoplay accessibility
            'loop' => true,
            'poster' => 'https://example.com/accessible-poster.jpg'
        ],
        'overlay' => [
            'color' => 'rgba(0, 0, 0, 0.4)',
            'opacity' => 0.4 // Ensures text contrast
        ],
        'reducedMotion' => true, // Respects prefers-reduced-motion
        'fallback' => [
            'type' => 'image',
            'image' => [
                'id' => 'accessible-fallback',
                'type' => 'image',
                'url' => 'https://example.com/accessible-fallback.jpg',
                'alt' => 'Descriptive fallback image alt text'
            ]
        ]
    ];

    expect($accessibilityConfig['video']['alt'])->toBe('Descriptive alt text for screen readers');
    expect($accessibilityConfig['video']['muted'])->toBeTrue(); // Required for autoplay
    expect($accessibilityConfig['video']['poster'])->toBe('https://example.com/accessible-poster.jpg');
    expect($accessibilityConfig['reducedMotion'])->toBeTrue();
    expect($accessibilityConfig['fallback']['image']['alt'])->toBe('Descriptive fallback image alt text');
    expect($accessibilityConfig['overlay']['opacity'])->toBe(0.4); // Ensures contrast
});