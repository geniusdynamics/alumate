<?php

use App\Models\Component;
use App\Models\ComponentAnalytic;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->actingAs($this->user);
    
    Storage::fake('public');
});

it('can create a video testimonial component with enhanced features', function () {
    $videoFile = UploadedFile::fake()->create('testimonial.mp4', 10000, 'video/mp4');
    $thumbnailFile = UploadedFile::fake()->image('thumbnail.jpg', 1920, 1080);
    $captionsFile = UploadedFile::fake()->create('captions.vtt', 1000, 'text/vtt');
    
    $componentData = [
        'name' => 'Enhanced Video Testimonials',
        'category' => 'testimonials',
        'type' => 'video_carousel',
        'config' => [
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
                'preload' => 'metadata'
            ],
            'showAuthorPhoto' => true,
            'showAuthorTitle' => true,
            'showAuthorCompany' => true,
            'showGraduationYear' => true,
            'showRating' => true,
            'showDate' => true,
            'lazyLoad' => true,
            'trackingEnabled' => true
        ]
    ];
    
    $response = $this->postJson('/api/components', $componentData);
    
    $response->assertSuccessful();
    
    $component = Component::where('name', 'Enhanced Video Testimonials')->first();
    expect($component)->not->toBeNull();
    expect($component->config['testimonials'][0]['content']['type'])->toBe('video');
    expect($component->config['testimonials'][0]['content']['videoAsset']['qualities'])->toHaveCount(3);
    expect($component->config['testimonials'][0]['content']['videoAsset']['captions'])->toBe('/storage/captions/captions.vtt');
    expect($component->config['testimonials'][0]['content']['videoAsset']['duration'])->toBe(120);
});

it('tracks video testimonial analytics events', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'testimonials',
        'type' => 'video_carousel',
        'config' => [
            'trackingEnabled' => true,
            'testimonials' => [
                [
                    'id' => 'test-video-1',
                    'content' => [
                        'type' => 'video',
                        'videoAsset' => [
                            'id' => 'video-1',
                            'url' => '/storage/videos/testimonial.mp4',
                            'duration' => 120
                        ]
                    ]
                ]
            ]
        ]
    ]);
    
    // Test video play tracking
    $playEventData = [
        'event_type' => 'video_play',
        'data' => [
            'video_id' => 'video-1',
            'testimonial_id' => 'test-video-1',
            'quality' => 'HD',
            'current_time' => 0,
            'autoplay' => false
        ]
    ];
    
    $response = $this->postJson("/api/components/{$component->id}/analytics", $playEventData);
    $response->assertSuccessful();
    
    // Test video progress tracking
    $progressEventData = [
        'event_type' => 'video_progress',
        'data' => [
            'video_id' => 'video-1',
            'testimonial_id' => 'test-video-1',
            'milestone' => 25,
            'current_time' => 30,
            'duration' => 120
        ]
    ];
    
    $response = $this->postJson("/api/components/{$component->id}/analytics", $progressEventData);
    $response->assertSuccessful();
    
    // Test video completion tracking
    $completeEventData = [
        'event_type' => 'video_complete',
        'data' => [
            'video_id' => 'video-1',
            'testimonial_id' => 'test-video-1',
            'total_watch_time' => 115000, // 115 seconds in milliseconds
            'quality' => 'HD'
        ]
    ];
    
    $response = $this->postJson("/api/components/{$component->id}/analytics", $completeEventData);
    $response->assertSuccessful();
    
    // Verify analytics were recorded
    $analytics = ComponentAnalytic::where('component_id', $component->id)->get();
    expect($analytics)->toHaveCount(3);
    
    $playEvent = $analytics->where('event_type', 'video_play')->first();
    expect($playEvent->data['video_id'])->toBe('video-1');
    expect($playEvent->data['quality'])->toBe('HD');
    
    $progressEvent = $analytics->where('event_type', 'video_progress')->first();
    expect($progressEvent->data['milestone'])->toBe(25);
    
    $completeEvent = $analytics->where('event_type', 'video_complete')->first();
    expect($completeEvent->data['total_watch_time'])->toBe(115000);
});

it('supports bandwidth-aware video quality selection', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'testimonials',
        'type' => 'video_carousel',
        'config' => [
            'videoSettings' => [
                'enableBandwidthDetection' => true,
                'adaptiveQuality' => true
            ],
            'testimonials' => [
                [
                    'id' => 'test-video-1',
                    'content' => [
                        'type' => 'video',
                        'videoAsset' => [
                            'id' => 'video-1',
                            'qualities' => [
                                [
                                    'label' => 'HD',
                                    'src' => '/storage/videos/testimonial-hd.mp4',
                                    'bandwidth' => 5000000
                                ],
                                [
                                    'label' => '720p',
                                    'src' => '/storage/videos/testimonial-720p.mp4',
                                    'bandwidth' => 2500000
                                ],
                                [
                                    'label' => '480p',
                                    'src' => '/storage/videos/testimonial-480p.mp4',
                                    'bandwidth' => 1000000
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]);
    
    // Test quality change tracking
    $qualityChangeData = [
        'event_type' => 'video_quality_change',
        'data' => [
            'video_id' => 'video-1',
            'from_quality' => 'HD',
            'to_quality' => '720p',
            'current_time' => 45,
            'reason' => 'bandwidth_detection'
        ]
    ];
    
    $response = $this->postJson("/api/components/{$component->id}/analytics", $qualityChangeData);
    $response->assertSuccessful();
    
    $analytic = ComponentAnalytic::where('component_id', $component->id)->first();
    expect($analytic->event_type)->toBe('video_quality_change');
    expect($analytic->data['from_quality'])->toBe('HD');
    expect($analytic->data['to_quality'])->toBe('720p');
    expect($analytic->data['reason'])->toBe('bandwidth_detection');
});

it('supports video testimonial accessibility features', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'testimonials',
        'type' => 'video_carousel',
        'config' => [
            'videoSettings' => [
                'showCaptions' => true,
                'showTranscript' => true,
                'respectReducedMotion' => true
            ],
            'testimonials' => [
                [
                    'id' => 'test-video-1',
                    'content' => [
                        'type' => 'video',
                        'videoAsset' => [
                            'id' => 'video-1',
                            'url' => '/storage/videos/testimonial.mp4',
                            'captions' => '/storage/captions/captions.vtt',
                            'transcript' => 'This is the full transcript of the video testimonial.',
                            'chapters' => [
                                ['time' => 0, 'title' => 'Introduction'],
                                ['time' => 30, 'title' => 'Main Content'],
                                ['time' => 90, 'title' => 'Conclusion']
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]);
    
    $response = $this->getJson("/api/components/{$component->id}");
    $response->assertSuccessful();
    
    $testimonial = $response->json('config.testimonials.0');
    expect($testimonial['content']['videoAsset']['captions'])->toBe('/storage/captions/captions.vtt');
    expect($testimonial['content']['videoAsset']['transcript'])->toBe('This is the full transcript of the video testimonial.');
    expect($testimonial['content']['videoAsset']['chapters'])->toHaveCount(3);
});

it('supports video testimonial carousel with touch gestures', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'testimonials',
        'type' => 'video_carousel',
        'config' => [
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
                    ]
                ]
            ],
            'testimonials' => [
                [
                    'id' => 'test-video-1',
                    'content' => ['type' => 'video']
                ],
                [
                    'id' => 'test-video-2',
                    'content' => ['type' => 'video']
                ],
                [
                    'id' => 'test-video-3',
                    'content' => ['type' => 'video']
                ]
            ]
        ]
    ]);
    
    // Test carousel slide change tracking
    $slideChangeData = [
        'event_type' => 'testimonial_carousel_slide_change',
        'data' => [
            'from_slide' => 0,
            'to_slide' => 1,
            'total_slides' => 3,
            'interaction_type' => 'swipe'
        ]
    ];
    
    $response = $this->postJson("/api/components/{$component->id}/analytics", $slideChangeData);
    $response->assertSuccessful();
    
    $analytic = ComponentAnalytic::where('component_id', $component->id)->first();
    expect($analytic->event_type)->toBe('testimonial_carousel_slide_change');
    expect($analytic->data['interaction_type'])->toBe('swipe');
});

it('optimizes video loading based on connection speed', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'testimonials',
        'type' => 'video_carousel',
        'config' => [
            'videoSettings' => [
                'enableBandwidthDetection' => true,
                'preload' => 'auto'
            ],
            'testimonials' => [
                [
                    'id' => 'test-video-1',
                    'content' => [
                        'type' => 'video',
                        'videoAsset' => [
                            'id' => 'video-1',
                            'adaptiveBitrate' => true,
                            'qualities' => [
                                ['label' => 'HD', 'bandwidth' => 5000000],
                                ['label' => '720p', 'bandwidth' => 2500000],
                                ['label' => '480p', 'bandwidth' => 1000000]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]);
    
    // Test bandwidth detection event
    $bandwidthData = [
        'event_type' => 'video_bandwidth_detected',
        'data' => [
            'video_id' => 'video-1',
            'connection_speed' => 2.5, // Mbps
            'effective_type' => '3g',
            'recommended_quality' => '720p'
        ]
    ];
    
    $response = $this->postJson("/api/components/{$component->id}/analytics", $bandwidthData);
    $response->assertSuccessful();
    
    $analytic = ComponentAnalytic::where('component_id', $component->id)->first();
    expect($analytic->data['connection_speed'])->toBe(2.5);
    expect($analytic->data['recommended_quality'])->toBe('720p');
});