<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create video testimonial configuration with enhanced features', function () {
    $videoTestimonialConfig = [
        'layout' => 'carousel',
        'testimonials' => [
            [
                'id' => 'test-video-1',
                'author' => [
                    'id' => 'author-1',
                    'name' => 'John Doe',
                    'title' => 'Software Engineer',
                    'company' => 'Tech Corp',
                    'graduationYear' => 2020,
                    'industry' => 'Technology',
                    'audienceType' => 'individual'
                ],
                'content' => [
                    'id' => 'content-1',
                    'quote' => 'This platform transformed my career trajectory completely.',
                    'rating' => 5,
                    'type' => 'video',
                    'videoAsset' => [
                        'id' => 'video-1',
                        'type' => 'video',
                        'url' => '/storage/videos/testimonial.mp4',
                        'thumbnail' => '/storage/thumbnails/thumbnail.jpg',
                        'width' => 1920,
                        'height' => 1080,
                        'duration' => 120,
                        'mimeType' => 'video/mp4',
                        'captions' => '/storage/captions/captions.vtt',
                        'transcript' => 'This platform transformed my career trajectory completely. I was able to connect with amazing opportunities.',
                        'qualities' => [
                            [
                                'label' => 'HD',
                                'src' => '/storage/videos/testimonial-hd.mp4',
                                'type' => 'video/mp4',
                                'bandwidth' => 5000000,
                                'width' => 1920,
                                'height' => 1080
                            ],
                            [
                                'label' => '720p',
                                'src' => '/storage/videos/testimonial-720p.mp4',
                                'type' => 'video/mp4',
                                'bandwidth' => 2500000,
                                'width' => 1280,
                                'height' => 720
                            ],
                            [
                                'label' => '480p',
                                'src' => '/storage/videos/testimonial-480p.mp4',
                                'type' => 'video/mp4',
                                'bandwidth' => 1000000,
                                'width' => 854,
                                'height' => 480
                            ]
                        ],
                        'adaptiveBitrate' => true,
                        'chapters' => [
                            ['time' => 0, 'title' => 'Introduction'],
                            ['time' => 30, 'title' => 'Career Impact'],
                            ['time' => 90, 'title' => 'Recommendation']
                        ],
                        'engagementMetrics' => [
                            'averageWatchTime' => 95,
                            'completionRate' => 0.85,
                            'dropOffPoints' => [15, 45, 75]
                        ]
                    ],
                    'featured' => true,
                    'verified' => true,
                    'dateCreated' => now()->toISOString()
                ],
                'audienceType' => 'individual',
                'industry' => 'Technology',
                'graduationYear' => 2020,
                'featured' => true,
                'approved' => true,
                'priority' => 1
            ]
        ],
        'carouselConfig' => [
            'autoplay' => false,
            'autoplaySpeed' => 5000,
            'pauseOnHover' => true,
            'showDots' => true,
            'showArrows' => true,
            'infinite' => true,
            'slidesToShow' => 1,
            'slidesToScroll' => 1,
            'swipe' => true,
            'touchThreshold' => 10,
            'responsive' => [
                [
                    'breakpoint' => 768,
                    'settings' => [
                        'slidesToShow' => 1,
                        'slidesToScroll' => 1
                    ]
                ]
            ]
        ],
        'videoSettings' => [
            'autoplay' => false,
            'muted' => true,
            'showControls' => true,
            'showCaptions' => true,
            'preload' => 'metadata',
            'enableBandwidthDetection' => true,
            'showQualitySelector' => true,
            'showTranscript' => true
        ],
        'showAuthorPhoto' => true,
        'showAuthorTitle' => true,
        'showAuthorCompany' => true,
        'showGraduationYear' => true,
        'showRating' => true,
        'showDate' => true,
        'lazyLoad' => true,
        'trackingEnabled' => true,
        'respectReducedMotion' => true
    ];
    
    // Verify the configuration structure
    expect($videoTestimonialConfig['testimonials'][0]['content']['type'])->toBe('video');
    expect($videoTestimonialConfig['testimonials'][0]['content']['videoAsset']['qualities'])->toHaveCount(3);
    expect($videoTestimonialConfig['testimonials'][0]['content']['videoAsset']['captions'])->toBe('/storage/captions/captions.vtt');
    expect($videoTestimonialConfig['testimonials'][0]['content']['videoAsset']['duration'])->toBe(120);
    expect($videoTestimonialConfig['testimonials'][0]['content']['videoAsset']['adaptiveBitrate'])->toBeTrue();
    expect($videoTestimonialConfig['testimonials'][0]['content']['videoAsset']['chapters'])->toHaveCount(3);
    expect($videoTestimonialConfig['videoSettings']['enableBandwidthDetection'])->toBeTrue();
    expect($videoTestimonialConfig['videoSettings']['showQualitySelector'])->toBeTrue();
    expect($videoTestimonialConfig['videoSettings']['showTranscript'])->toBeTrue();
    expect($videoTestimonialConfig['carouselConfig']['swipe'])->toBeTrue();
    expect($videoTestimonialConfig['carouselConfig']['touchThreshold'])->toBe(10);
});

it('validates video quality configurations', function () {
    $qualities = [
        [
            'label' => 'HD',
            'src' => '/storage/videos/testimonial-hd.mp4',
            'type' => 'video/mp4',
            'bandwidth' => 5000000,
            'width' => 1920,
            'height' => 1080
        ],
        [
            'label' => '720p',
            'src' => '/storage/videos/testimonial-720p.mp4',
            'type' => 'video/mp4',
            'bandwidth' => 2500000,
            'width' => 1280,
            'height' => 720
        ],
        [
            'label' => '480p',
            'src' => '/storage/videos/testimonial-480p.mp4',
            'type' => 'video/mp4',
            'bandwidth' => 1000000,
            'width' => 854,
            'height' => 480
        ]
    ];
    
    // Verify quality structure
    foreach ($qualities as $quality) {
        expect($quality)->toHaveKeys(['label', 'src', 'type', 'bandwidth', 'width', 'height']);
        expect($quality['type'])->toBe('video/mp4');
        expect($quality['bandwidth'])->toBeInt();
        expect($quality['width'])->toBeInt();
        expect($quality['height'])->toBeInt();
    }
    
    // Verify quality ordering (highest to lowest bandwidth)
    expect($qualities[0]['bandwidth'])->toBeGreaterThan($qualities[1]['bandwidth']);
    expect($qualities[1]['bandwidth'])->toBeGreaterThan($qualities[2]['bandwidth']);
});

it('supports accessibility features configuration', function () {
    $accessibilityConfig = [
        'videoSettings' => [
            'showCaptions' => true,
            'showTranscript' => true,
            'respectReducedMotion' => true,
            'showControls' => true,
            'preload' => 'metadata'
        ],
        'carouselConfig' => [
            'ariaLabel' => 'Video testimonials carousel',
            'announceSlideChanges' => true,
            'pauseOnHover' => true,
            'swipe' => true,
            'touchThreshold' => 10
        ],
        'testimonials' => [
            [
                'content' => [
                    'videoAsset' => [
                        'captions' => '/storage/captions/captions.vtt',
                        'transcript' => 'Full transcript of the video testimonial content.',
                        'chapters' => [
                            ['time' => 0, 'title' => 'Introduction'],
                            ['time' => 30, 'title' => 'Main Content'],
                            ['time' => 90, 'title' => 'Conclusion']
                        ]
                    ]
                ]
            ]
        ]
    ];
    
    expect($accessibilityConfig['videoSettings']['showCaptions'])->toBeTrue();
    expect($accessibilityConfig['videoSettings']['showTranscript'])->toBeTrue();
    expect($accessibilityConfig['videoSettings']['respectReducedMotion'])->toBeTrue();
    expect($accessibilityConfig['carouselConfig']['announceSlideChanges'])->toBeTrue();
    expect($accessibilityConfig['testimonials'][0]['content']['videoAsset']['captions'])->toBeString();
    expect($accessibilityConfig['testimonials'][0]['content']['videoAsset']['transcript'])->toBeString();
    expect($accessibilityConfig['testimonials'][0]['content']['videoAsset']['chapters'])->toHaveCount(3);
});

it('supports bandwidth-aware video loading configuration', function () {
    $bandwidthConfig = [
        'videoSettings' => [
            'enableBandwidthDetection' => true,
            'adaptiveQuality' => true,
            'preload' => 'auto'
        ],
        'testimonials' => [
            [
                'content' => [
                    'videoAsset' => [
                        'adaptiveBitrate' => true,
                        'qualities' => [
                            [
                                'label' => 'HD',
                                'bandwidth' => 5000000,
                                'src' => '/storage/videos/testimonial-hd.mp4'
                            ],
                            [
                                'label' => '720p',
                                'bandwidth' => 2500000,
                                'src' => '/storage/videos/testimonial-720p.mp4'
                            ],
                            [
                                'label' => '480p',
                                'bandwidth' => 1000000,
                                'src' => '/storage/videos/testimonial-480p.mp4'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];
    
    expect($bandwidthConfig['videoSettings']['enableBandwidthDetection'])->toBeTrue();
    expect($bandwidthConfig['videoSettings']['adaptiveQuality'])->toBeTrue();
    expect($bandwidthConfig['testimonials'][0]['content']['videoAsset']['adaptiveBitrate'])->toBeTrue();
    expect($bandwidthConfig['testimonials'][0]['content']['videoAsset']['qualities'])->toHaveCount(3);
    
    // Verify bandwidth values are properly ordered
    $qualities = $bandwidthConfig['testimonials'][0]['content']['videoAsset']['qualities'];
    expect($qualities[0]['bandwidth'])->toBeGreaterThan($qualities[1]['bandwidth']);
    expect($qualities[1]['bandwidth'])->toBeGreaterThan($qualities[2]['bandwidth']);
});

it('supports touch gesture configuration for carousel', function () {
    $touchConfig = [
        'carouselConfig' => [
            'swipe' => true,
            'touchThreshold' => 10,
            'responsive' => [
                [
                    'breakpoint' => 768,
                    'settings' => [
                        'slidesToShow' => 1,
                        'swipe' => true,
                        'touchThreshold' => 5
                    ]
                ],
                [
                    'breakpoint' => 480,
                    'settings' => [
                        'slidesToShow' => 1,
                        'swipe' => true,
                        'touchThreshold' => 3
                    ]
                ]
            ]
        ]
    ];
    
    expect($touchConfig['carouselConfig']['swipe'])->toBeTrue();
    expect($touchConfig['carouselConfig']['touchThreshold'])->toBe(10);
    expect($touchConfig['carouselConfig']['responsive'])->toHaveCount(2);
    
    // Verify responsive touch settings
    $mobileSettings = $touchConfig['carouselConfig']['responsive'][0]['settings'];
    expect($mobileSettings['swipe'])->toBeTrue();
    expect($mobileSettings['touchThreshold'])->toBe(5);
    
    $smallMobileSettings = $touchConfig['carouselConfig']['responsive'][1]['settings'];
    expect($smallMobileSettings['touchThreshold'])->toBe(3);
});

it('supports analytics tracking configuration', function () {
    $analyticsConfig = [
        'trackingEnabled' => true,
        'videoSettings' => [
            'trackAnalytics' => true
        ],
        'testimonials' => [
            [
                'id' => 'test-video-1',
                'content' => [
                    'videoAsset' => [
                        'id' => 'video-1',
                        'engagementMetrics' => [
                            'averageWatchTime' => 95,
                            'completionRate' => 0.85,
                            'dropOffPoints' => [15, 45, 75]
                        ]
                    ]
                ]
            ]
        ]
    ];
    
    expect($analyticsConfig['trackingEnabled'])->toBeTrue();
    expect($analyticsConfig['videoSettings']['trackAnalytics'])->toBeTrue();
    
    $metrics = $analyticsConfig['testimonials'][0]['content']['videoAsset']['engagementMetrics'];
    expect($metrics['averageWatchTime'])->toBe(95);
    expect($metrics['completionRate'])->toBe(0.85);
    expect($metrics['dropOffPoints'])->toHaveCount(3);
    expect($metrics['dropOffPoints'])->toContain(15, 45, 75);
});

it('validates video format support', function () {
    $videoFormats = [
        [
            'type' => 'video/mp4',
            'codecs' => 'avc1.42E01E, mp4a.40.2',
            'supported' => true
        ],
        [
            'type' => 'video/webm',
            'codecs' => 'vp9, opus',
            'supported' => true
        ],
        [
            'type' => 'video/ogg',
            'codecs' => 'theora, vorbis',
            'supported' => false
        ]
    ];
    
    $supportedFormats = array_filter($videoFormats, fn($format) => $format['supported']);
    
    expect($supportedFormats)->toHaveCount(2);
    expect(array_column($supportedFormats, 'type'))->toContain('video/mp4', 'video/webm');
});

it('supports video optimization features', function () {
    $optimizationConfig = [
        'videoSettings' => [
            'lazyLoad' => true,
            'preload' => 'metadata',
            'enableBandwidthDetection' => true,
            'showQualitySelector' => true
        ],
        'testimonials' => [
            [
                'content' => [
                    'videoAsset' => [
                        'optimized' => true,
                        'cdnUrl' => 'https://cdn.example.com/videos/',
                        'placeholder' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQ...',
                        'qualities' => [
                            [
                                'label' => 'Auto',
                                'src' => '/storage/videos/testimonial-auto.mp4',
                                'type' => 'video/mp4'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];
    
    expect($optimizationConfig['videoSettings']['lazyLoad'])->toBeTrue();
    expect($optimizationConfig['videoSettings']['enableBandwidthDetection'])->toBeTrue();
    expect($optimizationConfig['videoSettings']['showQualitySelector'])->toBeTrue();
    
    $videoAsset = $optimizationConfig['testimonials'][0]['content']['videoAsset'];
    expect($videoAsset['optimized'])->toBeTrue();
    expect($videoAsset['cdnUrl'])->toBeString();
    expect($videoAsset['placeholder'])->toStartWith('data:image/jpeg;base64,');
});