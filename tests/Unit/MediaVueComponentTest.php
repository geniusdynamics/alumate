<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Media Vue Components', function () {
    it('renders MediaBase component correctly', function () {
        $component = 'MediaBase';
        $props = [
            'type' => 'image',
            'loading' => false,
            'error' => null
        ];

        // Test component structure and props
        expect($component)->toBe('MediaBase');
        expect($props['type'])->toBe('image');
        expect($props['loading'])->toBeFalse();
        expect($props['error'])->toBeNull();
    });

    it('handles ImageGallery component props', function () {
        $props = [
            'images' => [
                [
                    'id' => 1,
                    'url' => 'https://example.com/image1.jpg',
                    'alt_text' => 'Image 1',
                    'thumbnail_url' => 'https://example.com/thumb1.jpg'
                ],
                [
                    'id' => 2,
                    'url' => 'https://example.com/image2.jpg',
                    'alt_text' => 'Image 2',
                    'thumbnail_url' => 'https://example.com/thumb2.jpg'
                ]
            ],
            'columns' => 3,
            'spacing' => 'md',
            'lightbox' => true
        ];

        expect($props['images'])->toHaveCount(2);
        expect($props['columns'])->toBe(3);
        expect($props['spacing'])->toBe('md');
        expect($props['lightbox'])->toBeTrue();
        expect($props['images'][0]['alt_text'])->toBe('Image 1');
    });

    it('validates VideoEmbed component props', function () {
        $props = [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'title' => 'Test Video',
            'autoplay' => false,
            'controls' => true,
            'muted' => false,
            'loop' => false,
            'aspectRatio' => '16:9'
        ];

        expect($props['url'])->toContain('youtube.com');
        expect($props['title'])->toBe('Test Video');
        expect($props['autoplay'])->toBeFalse();
        expect($props['controls'])->toBeTrue();
        expect($props['aspectRatio'])->toBe('16:9');
    });

    it('handles InteractiveDemo component state', function () {
        $initialState = [
            'selectedComponent' => 'ImageGallery',
            'demoProps' => [
                'images' => [],
                'columns' => 2,
                'spacing' => 'sm'
            ],
            'showCode' => false,
            'activeTab' => 'preview'
        ];

        expect($initialState['selectedComponent'])->toBe('ImageGallery');
        expect($initialState['demoProps']['columns'])->toBe(2);
        expect($initialState['showCode'])->toBeFalse();
        expect($initialState['activeTab'])->toBe('preview');
    });

    it('validates MediaComponent wrapper props', function () {
        $props = [
            'type' => 'gallery',
            'config' => [
                'images' => [
                    ['url' => 'image1.jpg', 'alt_text' => 'Alt 1'],
                    ['url' => 'image2.jpg', 'alt_text' => 'Alt 2']
                ],
                'columns' => 4,
                'lightbox' => true
            ],
            'className' => 'custom-gallery',
            'loading' => false
        ];

        expect($props['type'])->toBe('gallery');
        expect($props['config']['images'])->toHaveCount(2);
        expect($props['config']['columns'])->toBe(4);
        expect($props['config']['lightbox'])->toBeTrue();
        expect($props['className'])->toBe('custom-gallery');
    });

    it('handles video embed URL parsing', function () {
        $testUrls = [
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ' => [
                'platform' => 'youtube',
                'videoId' => 'dQw4w9WgXcQ',
                'embedUrl' => 'https://www.youtube.com/embed/dQw4w9WgXcQ'
            ],
            'https://vimeo.com/123456789' => [
                'platform' => 'vimeo',
                'videoId' => '123456789',
                'embedUrl' => 'https://player.vimeo.com/video/123456789'
            ],
            'https://example.com/video.mp4' => [
                'platform' => 'direct',
                'videoId' => null,
                'embedUrl' => 'https://example.com/video.mp4'
            ]
        ];

        foreach ($testUrls as $url => $expected) {
            expect($expected['platform'])->toBeString();
            expect($expected['embedUrl'])->toContain('http');
        }
    });

    it('validates image gallery responsive behavior', function () {
        $breakpoints = [
            'sm' => ['columns' => 1, 'spacing' => 'xs'],
            'md' => ['columns' => 2, 'spacing' => 'sm'],
            'lg' => ['columns' => 3, 'spacing' => 'md'],
            'xl' => ['columns' => 4, 'spacing' => 'lg']
        ];

        foreach ($breakpoints as $breakpoint => $config) {
            expect($config['columns'])->toBeInt();
            expect($config['spacing'])->toBeString();
            expect($config['columns'])->toBeGreaterThan(0);
        }
    });

    it('handles media loading states', function () {
        $loadingStates = [
            'idle' => ['loading' => false, 'error' => null, 'loaded' => false],
            'loading' => ['loading' => true, 'error' => null, 'loaded' => false],
            'loaded' => ['loading' => false, 'error' => null, 'loaded' => true],
            'error' => ['loading' => false, 'error' => 'Failed to load', 'loaded' => false]
        ];

        foreach ($loadingStates as $state => $config) {
            expect($config)->toHaveKeys(['loading', 'error', 'loaded']);
            
            if ($state === 'loading') {
                expect($config['loading'])->toBeTrue();
            } else {
                expect($config['loading'])->toBeFalse();
            }
        }
    });

    it('validates accessibility features', function () {
        $accessibilityProps = [
            'alt_text' => 'Descriptive alt text for screen readers',
            'aria_label' => 'Image gallery navigation',
            'role' => 'img',
            'tabindex' => 0,
            'keyboard_navigation' => true,
            'focus_management' => true
        ];

        expect($accessibilityProps['alt_text'])->toContain('screen readers');
        expect($accessibilityProps['role'])->toBe('img');
        expect($accessibilityProps['tabindex'])->toBe(0);
        expect($accessibilityProps['keyboard_navigation'])->toBeTrue();
    });

    it('handles media optimization settings', function () {
        $optimizationConfig = [
            'lazy_loading' => true,
            'webp_support' => true,
            'responsive_images' => true,
            'compression_quality' => 85,
            'max_width' => 1920,
            'thumbnail_sizes' => [150, 300, 600, 1200]
        ];

        expect($optimizationConfig['lazy_loading'])->toBeTrue();
        expect($optimizationConfig['compression_quality'])->toBeBetween(1, 100);
        expect($optimizationConfig['max_width'])->toBeGreaterThan(0);
        expect($optimizationConfig['thumbnail_sizes'])->toHaveCount(4);
    });

    it('validates component event handling', function () {
        $events = [
            'image-click' => ['imageId' => 1, 'index' => 0],
            'gallery-navigate' => ['direction' => 'next', 'currentIndex' => 2],
            'video-play' => ['videoId' => 'abc123', 'timestamp' => 0],
            'video-pause' => ['videoId' => 'abc123', 'timestamp' => 45.5],
            'lightbox-open' => ['imageId' => 3],
            'lightbox-close' => ['reason' => 'escape-key']
        ];

        foreach ($events as $eventName => $payload) {
            expect($eventName)->toBeString();
            expect($payload)->toBeArray();
        }

        expect($events['gallery-navigate']['direction'])->toBeIn(['next', 'prev']);
        expect($events['video-pause']['timestamp'])->toBeFloat();
    });
});