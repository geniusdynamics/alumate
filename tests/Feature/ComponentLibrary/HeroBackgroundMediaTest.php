<?php

use App\Models\Component;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['institution_id' => $this->tenant->id]);
    $this->actingAs($this->user);
});

it('can create hero component with video background configuration', function () {
    $videoConfig = [
        'headline' => 'Test Hero with Video',
        'subheading' => 'Video background test',
        'audienceType' => 'individual',
        'backgroundMedia' => [
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
        ],
        'ctaButtons' => [],
        'layout' => 'centered',
        'textAlignment' => 'center',
        'contentPosition' => 'center',
        'headingLevel' => 1
    ];

    $component = Component::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Test Video Hero',
        'slug' => 'test-video-hero',
        'category' => 'hero',
        'type' => 'individual',
        'config' => $videoConfig,
        'version' => '1.0.0',
        'is_active' => true
    ]);

    expect($component)->toBeInstanceOf(Component::class);
    expect($component->config['backgroundMedia']['type'])->toBe('video');
    expect($component->config['backgroundMedia']['video']['autoplay'])->toBeTrue();
    expect($component->config['backgroundMedia']['video']['muted'])->toBeTrue();
    expect($component->config['backgroundMedia']['video']['adaptiveBitrate'])->toBeTrue();
    expect($component->config['backgroundMedia']['mobileOptimized'])->toBeTrue();
    expect($component->config['backgroundMedia']['fallback']['type'])->toBe('image');
});

it('can create hero component with responsive image background configuration', function () {
    $imageConfig = [
        'headline' => 'Test Hero with Image',
        'subheading' => 'Responsive image background test',
        'audienceType' => 'institution',
        'backgroundMedia' => [
            'type' => 'image',
            'image' => [
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
                    ],
                    [
                        'url' => 'https://example.com/hero-1920.webp',
                        'width' => 1920,
                        'format' => 'webp'
                    ]
                ],
                'mobileUrl' => 'https://example.com/hero-mobile.jpg',
                'mobileSrcSet' => [
                    [
                        'url' => 'https://example.com/hero-mobile-320.webp',
                        'width' => 320,
                        'format' => 'webp'
                    ],
                    [
                        'url' => 'https://example.com/hero-mobile-640.webp',
                        'width' => 640,
                        'format' => 'webp'
                    ]
                ],
                'fallbackUrl' => 'https://example.com/hero-fallback.jpg',
                'placeholder' => 'data:image/jpeg;base64,test-placeholder',
                'cdnUrl' => 'https://cdn.example.com',
                'optimized' => true
            ],
            'overlay' => [
                'color' => 'rgba(0, 0, 0, 0.3)',
                'opacity' => 0.3
            ],
            'lazyLoad' => true,
            'preload' => false,
            'mobileOptimized' => true
        ],
        'ctaButtons' => [],
        'layout' => 'centered',
        'textAlignment' => 'center',
        'contentPosition' => 'center',
        'headingLevel' => 1
    ];

    $component = Component::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Test Image Hero',
        'slug' => 'test-image-hero',
        'category' => 'hero',
        'type' => 'institution',
        'config' => $imageConfig,
        'version' => '1.0.0',
        'is_active' => true
    ]);

    expect($component)->toBeInstanceOf(Component::class);
    expect($component->config['backgroundMedia']['type'])->toBe('image');
    expect($component->config['backgroundMedia']['image']['optimized'])->toBeTrue();
    expect($component->config['backgroundMedia']['mobileOptimized'])->toBeTrue();
    expect($component->config['backgroundMedia']['image']['srcSet'])->toHaveCount(4);
    expect($component->config['backgroundMedia']['image']['mobileSrcSet'])->toHaveCount(2);
    expect($component->config['backgroundMedia']['image']['cdnUrl'])->toBe('https://cdn.example.com');
});

it('can create hero component with gradient background and fallback configuration', function () {
    $gradientConfig = [
        'headline' => 'Test Hero with Gradient',
        'subheading' => 'Gradient background with fallback test',
        'audienceType' => 'employer',
        'backgroundMedia' => [
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
        ],
        'ctaButtons' => [],
        'layout' => 'centered',
        'textAlignment' => 'center',
        'contentPosition' => 'center',
        'headingLevel' => 1
    ];

    $component = Component::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Test Gradient Hero',
        'slug' => 'test-gradient-hero',
        'category' => 'hero',
        'type' => 'employer',
        'config' => $gradientConfig,
        'version' => '1.0.0',
        'is_active' => true
    ]);

    expect($component)->toBeInstanceOf(Component::class);
    expect($component->config['backgroundMedia']['type'])->toBe('gradient');
    expect($component->config['backgroundMedia']['mobileOptimized'])->toBeTrue();
    expect($component->config['backgroundMedia']['reducedMotion'])->toBeTrue();
    expect($component->config['backgroundMedia']['fallback']['type'])->toBe('gradient');
    expect($component->config['backgroundMedia']['gradient']['colors'])->toHaveCount(2);
});

it('validates background media configuration structure', function () {
    $invalidConfig = [
        'headline' => 'Test Hero',
        'audienceType' => 'individual',
        'backgroundMedia' => [
            'type' => 'video',
            // Missing required video configuration
        ],
        'ctaButtons' => [],
        'layout' => 'centered',
        'textAlignment' => 'center',
        'contentPosition' => 'center',
        'headingLevel' => 1
    ];

    // Test that creating a component with invalid config structure fails
    expect(function () use ($invalidConfig) {
        Component::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Invalid Hero Component',
            'slug' => 'invalid-hero-component',
            'category' => 'hero',
            'type' => 'individual',
            'config' => $invalidConfig,
            'version' => '1.0.0',
            'is_active' => true
        ]);
    })->toThrow(Exception::class);
});

it('handles mobile-specific video configuration', function () {
    $mobileConfig = [
        'headline' => 'Mobile Optimized Hero',
        'audienceType' => 'individual',
        'backgroundMedia' => [
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
        ],
        'ctaButtons' => [],
        'layout' => 'centered',
        'textAlignment' => 'center',
        'contentPosition' => 'center',
        'headingLevel' => 1
    ];

    $component = Component::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Mobile Optimized Hero',
        'slug' => 'mobile-optimized-hero',
        'category' => 'hero',
        'type' => 'individual',
        'config' => $mobileConfig,
        'version' => '1.0.0',
        'is_active' => true
    ]);

    expect($component)->toBeInstanceOf(Component::class);
    expect($component->config['backgroundMedia']['video']['disableOnMobile'])->toBeTrue();
    expect($component->config['backgroundMedia']['mobileOptimized'])->toBeTrue();
    expect($component->config['backgroundMedia']['fallback']['type'])->toBe('image');
    expect($component->config['backgroundMedia']['fallback']['image']['mobileUrl'])->toBe('https://example.com/mobile-hero-optimized.jpg');
});

it('supports CDN integration for media optimization', function () {
    $cdnConfig = [
        'headline' => 'CDN Optimized Hero',
        'audienceType' => 'individual',
        'backgroundMedia' => [
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
        ],
        'ctaButtons' => [],
        'layout' => 'centered',
        'textAlignment' => 'center',
        'contentPosition' => 'center',
        'headingLevel' => 1
    ];

    $component = Component::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'CDN Optimized Hero',
        'slug' => 'cdn-optimized-hero',
        'category' => 'hero',
        'type' => 'individual',
        'config' => $cdnConfig,
        'version' => '1.0.0',
        'is_active' => true
    ]);

    expect($component)->toBeInstanceOf(Component::class);
    expect($component->config['backgroundMedia']['image']['cdnUrl'])->toBe('https://cdn.example.com');
    expect($component->config['backgroundMedia']['image']['optimized'])->toBeTrue();
    expect($component->config['backgroundMedia']['lazyLoad'])->toBeTrue();
    expect($component->config['backgroundMedia']['preload'])->toBeFalse();
    expect($component->config['backgroundMedia']['image']['srcSet'])->toHaveCount(2);
});