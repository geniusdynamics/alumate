<?php

use App\Models\Component;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
});

describe('Component Serialization for GrapeJS', function () {
    it('serializes hero component configuration correctly', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'name' => 'Alumni Hero',
            'config' => [
                'headline' => 'Join Our Alumni Network',
                'subheading' => 'Connect with thousands of graduates',
                'audienceType' => 'individual',
                'backgroundType' => 'image',
                'backgroundImage' => '/images/hero-bg.jpg',
                'ctaButtons' => [
                    [
                        'text' => 'Get Started',
                        'url' => '/signup',
                        'style' => 'primary',
                        'tracking' => ['utm_source' => 'hero']
                    ]
                ],
                'responsive' => [
                    'desktop' => ['padding' => '80px 20px'],
                    'tablet' => ['padding' => '60px 15px'],
                    'mobile' => ['padding' => '40px 10px']
                ]
            ]
        ]);

        $serialized = $this->serializeComponent($component);

        expect($serialized)->toHaveKey('type', 'hero-component');
        expect($serialized)->toHaveKey('attributes');
        expect($serialized['attributes'])->toHaveKey('data-component-id', $component->id);
        expect($serialized['attributes'])->toHaveKey('data-headline', 'Join Our Alumni Network');
        expect($serialized['attributes'])->toHaveKey('data-audience-type', 'individual');
        
        expect($serialized)->toHaveKey('components');
        expect($serialized['components'])->toBeArray();
        
        expect($serialized)->toHaveKey('styles');
        expect($serialized['styles'])->toContain([
            'selectors' => ['.hero-component'],
            'style' => ['padding' => '80px 20px']
        ]);
    });

    it('serializes form component with field configurations', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'name' => 'Contact Form',
            'config' => [
                'title' => 'Get In Touch',
                'layout' => 'two-column',
                'fields' => [
                    [
                        'type' => 'text',
                        'name' => 'first_name',
                        'label' => 'First Name',
                        'placeholder' => 'Enter your first name',
                        'required' => true,
                        'validation' => ['min_length' => 2]
                    ],
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'your@email.com',
                        'required' => true,
                        'validation' => ['email_format' => true]
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'message',
                        'label' => 'Message',
                        'placeholder' => 'Your message here...',
                        'required' => false,
                        'validation' => ['max_length' => 1000]
                    ]
                ],
                'submitButton' => [
                    'text' => 'Send Message',
                    'style' => 'primary'
                ],
                'crmIntegration' => [
                    'enabled' => true,
                    'webhook_url' => 'https://api.crm.com/webhook',
                    'lead_source' => 'website_contact'
                ]
            ]
        ]);

        $serialized = $this->serializeComponent($component);

        expect($serialized['type'])->toBe('form-component');
        expect($serialized['attributes'])->toHaveKey('data-form-title', 'Get In Touch');
        expect($serialized['attributes'])->toHaveKey('data-layout', 'two-column');
        
        // Check form fields serialization
        $formFields = $serialized['components'];
        expect($formFields)->toHaveCount(4); // 3 fields + 1 submit button
        
        $firstNameField = collect($formFields)->firstWhere('attributes.name', 'first_name');
        expect($firstNameField)->not->toBeNull();
        expect($firstNameField['type'])->toBe('input');
        expect($firstNameField['attributes']['type'])->toBe('text');
        expect($firstNameField['attributes']['required'])->toBeTrue();
    });

    it('serializes testimonial component with filtering options', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'testimonials',
            'name' => 'Alumni Success Stories',
            'config' => [
                'layout' => 'carousel',
                'itemsPerSlide' => 3,
                'autoplay' => true,
                'autoplaySpeed' => 5000,
                'showAuthorPhoto' => true,
                'showRating' => true,
                'showCompanyLogo' => true,
                'filterOptions' => [
                    'industry' => ['tech', 'finance', 'healthcare'],
                    'graduation_year' => ['2020', '2021', '2022'],
                    'location' => ['usa', 'canada', 'uk']
                ],
                'testimonials' => [
                    [
                        'id' => 1,
                        'content' => 'Amazing network that helped me land my dream job!',
                        'author' => [
                            'name' => 'John Doe',
                            'title' => 'Software Engineer',
                            'company' => 'Tech Corp',
                            'photo' => '/images/john-doe.jpg',
                            'graduation_year' => '2020'
                        ],
                        'rating' => 5,
                        'industry' => 'tech',
                        'location' => 'usa'
                    ]
                ]
            ]
        ]);

        $serialized = $this->serializeComponent($component);

        expect($serialized['type'])->toBe('testimonial-component');
        expect($serialized['attributes'])->toHaveKey('data-layout', 'carousel');
        expect($serialized['attributes'])->toHaveKey('data-autoplay', true);
        expect($serialized['attributes'])->toHaveKey('data-show-rating', true);
        
        // Check testimonial items
        $testimonialItems = $serialized['components'];
        expect($testimonialItems)->toHaveCount(1);
        
        $testimonial = $testimonialItems[0];
        expect($testimonial['type'])->toBe('testimonial-item');
        expect($testimonial['attributes'])->toHaveKey('data-rating', 5);
        expect($testimonial['attributes'])->toHaveKey('data-industry', 'tech');
    });

    it('serializes statistics component with animation settings', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'statistics',
            'name' => 'Network Statistics',
            'config' => [
                'displayType' => 'counters',
                'layout' => 'grid',
                'columns' => 4,
                'animationEnabled' => true,
                'animationDuration' => 2000,
                'animationEasing' => 'ease-out',
                'triggerOnScroll' => true,
                'scrollOffset' => 100,
                'statistics' => [
                    [
                        'label' => 'Alumni Members',
                        'value' => 50000,
                        'suffix' => '+',
                        'icon' => 'users',
                        'color' => '#007bff'
                    ],
                    [
                        'label' => 'Partner Companies',
                        'value' => 5000,
                        'suffix' => '+',
                        'icon' => 'building',
                        'color' => '#28a745'
                    ],
                    [
                        'label' => 'Job Placements',
                        'value' => 25000,
                        'suffix' => '+',
                        'icon' => 'briefcase',
                        'color' => '#ffc107'
                    ],
                    [
                        'label' => 'Success Rate',
                        'value' => 95,
                        'suffix' => '%',
                        'icon' => 'chart-line',
                        'color' => '#dc3545'
                    ]
                ]
            ]
        ]);

        $serialized = $this->serializeComponent($component);

        expect($serialized['type'])->toBe('statistics-component');
        expect($serialized['attributes'])->toHaveKey('data-display-type', 'counters');
        expect($serialized['attributes'])->toHaveKey('data-animation-enabled', true);
        expect($serialized['attributes'])->toHaveKey('data-animation-duration', 2000);
        
        // Check statistics items
        $statisticItems = $serialized['components'];
        expect($statisticItems)->toHaveCount(4);
        
        $firstStat = $statisticItems[0];
        expect($firstStat['type'])->toBe('statistic-counter');
        expect($firstStat['attributes'])->toHaveKey('data-value', 50000);
        expect($firstStat['attributes'])->toHaveKey('data-suffix', '+');
        expect($firstStat['attributes'])->toHaveKey('data-icon', 'users');
    });

    it('serializes CTA component with tracking configuration', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'name' => 'Join Network CTA',
            'config' => [
                'type' => 'button',
                'buttonConfig' => [
                    'text' => 'Join Our Network',
                    'url' => '/signup',
                    'style' => 'primary',
                    'size' => 'large',
                    'icon' => 'arrow-right',
                    'iconPosition' => 'right'
                ],
                'tracking' => [
                    'enabled' => true,
                    'eventName' => 'cta_click',
                    'utmSource' => 'alumni_page',
                    'utmMedium' => 'cta_button',
                    'utmCampaign' => 'alumni_recruitment',
                    'customProperties' => [
                        'button_position' => 'hero_section',
                        'audience_type' => 'individual'
                    ]
                ],
                'abTesting' => [
                    'enabled' => true,
                    'variants' => [
                        ['text' => 'Join Our Network', 'weight' => 50],
                        ['text' => 'Get Started Today', 'weight' => 30],
                        ['text' => 'Sign Up Now', 'weight' => 20]
                    ]
                ]
            ]
        ]);

        $serialized = $this->serializeComponent($component);

        expect($serialized['type'])->toBe('cta-component');
        expect($serialized['attributes'])->toHaveKey('data-cta-type', 'button');
        expect($serialized['attributes'])->toHaveKey('data-button-text', 'Join Our Network');
        expect($serialized['attributes'])->toHaveKey('data-tracking-enabled', true);
        expect($serialized['attributes'])->toHaveKey('data-ab-testing', true);
        
        // Check tracking data
        expect($serialized['attributes'])->toHaveKey('data-utm-source', 'alumni_page');
        expect($serialized['attributes'])->toHaveKey('data-utm-campaign', 'alumni_recruitment');
    });

    it('serializes media component with optimization settings', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'media',
            'name' => 'Alumni Photo Gallery',
            'config' => [
                'type' => 'image-gallery',
                'layout' => 'masonry',
                'columns' => 3,
                'spacing' => 16,
                'optimization' => [
                    'lazyLoading' => true,
                    'webpSupport' => true,
                    'responsiveImages' => true,
                    'compressionQuality' => 85
                ],
                'lightbox' => [
                    'enabled' => true,
                    'showCaptions' => true,
                    'showNavigation' => true,
                    'showThumbnails' => true,
                    'autoplay' => false
                ],
                'accessibility' => [
                    'altTextRequired' => true,
                    'keyboardNavigation' => true,
                    'screenReaderSupport' => true,
                    'focusManagement' => true
                ],
                'images' => [
                    [
                        'src' => '/images/alumni-event-1.jpg',
                        'alt' => 'Alumni networking event 2023',
                        'caption' => 'Annual Alumni Networking Event',
                        'category' => 'events'
                    ],
                    [
                        'src' => '/images/alumni-graduation.jpg',
                        'alt' => 'Graduation ceremony 2023',
                        'caption' => 'Class of 2023 Graduation',
                        'category' => 'graduation'
                    ]
                ]
            ]
        ]);

        $serialized = $this->serializeComponent($component);

        expect($serialized['type'])->toBe('media-component');
        expect($serialized['attributes'])->toHaveKey('data-media-type', 'image-gallery');
        expect($serialized['attributes'])->toHaveKey('data-layout', 'masonry');
        expect($serialized['attributes'])->toHaveKey('data-lazy-loading', true);
        expect($serialized['attributes'])->toHaveKey('data-lightbox-enabled', true);
        
        // Check image items
        $imageItems = $serialized['components'];
        expect($imageItems)->toHaveCount(2);
        
        $firstImage = $imageItems[0];
        expect($firstImage['type'])->toBe('image');
        expect($firstImage['attributes'])->toHaveKey('src', '/images/alumni-event-1.jpg');
        expect($firstImage['attributes'])->toHaveKey('alt', 'Alumni networking event 2023');
        expect($firstImage['attributes'])->toHaveKey('data-category', 'events');
    });

    it('handles complex nested component structures', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'name' => 'Complex Hero Section',
            'config' => [
                'layout' => 'split',
                'leftColumn' => [
                    'headline' => 'Join Our Alumni Network',
                    'subheading' => 'Connect with graduates worldwide',
                    'description' => 'Access exclusive opportunities and build lasting connections.',
                    'ctaButtons' => [
                        ['text' => 'Get Started', 'style' => 'primary'],
                        ['text' => 'Learn More', 'style' => 'secondary']
                    ]
                ],
                'rightColumn' => [
                    'type' => 'statistics',
                    'statistics' => [
                        ['label' => 'Alumni', 'value' => 50000],
                        ['label' => 'Companies', 'value' => 5000]
                    ]
                ],
                'backgroundElements' => [
                    [
                        'type' => 'image',
                        'src' => '/images/hero-bg.jpg',
                        'position' => 'center',
                        'overlay' => ['color' => 'rgba(0,0,0,0.3)']
                    ],
                    [
                        'type' => 'particles',
                        'count' => 50,
                        'animation' => 'float'
                    ]
                ]
            ]
        ]);

        $serialized = $this->serializeComponent($component);

        expect($serialized['type'])->toBe('hero-component');
        expect($serialized['attributes'])->toHaveKey('data-layout', 'split');
        
        // Check nested components
        $nestedComponents = $serialized['components'];
        expect($nestedComponents)->toHaveCount(4); // left column, right column, background image, particles
        
        $leftColumn = collect($nestedComponents)->firstWhere('attributes.data-column', 'left');
        expect($leftColumn)->not->toBeNull();
        expect($leftColumn['components'])->toHaveCount(4); // headline, subheading, description, buttons
        
        $rightColumn = collect($nestedComponents)->firstWhere('attributes.data-column', 'right');
        expect($rightColumn)->not->toBeNull();
        expect($rightColumn['components'])->toHaveCount(2); // 2 statistics
    });

    it('preserves component metadata during serialization', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'name' => 'Metadata Test Component',
            'version' => '2.1.0',
            'metadata' => [
                'tags' => ['featured', 'responsive', 'accessible'],
                'author' => 'Design Team',
                'created_date' => '2023-01-15',
                'last_modified' => '2023-08-30',
                'usage_count' => 150,
                'performance_score' => 95,
                'accessibility_score' => 98,
                'seo_optimized' => true,
                'mobile_optimized' => true,
                'browser_compatibility' => ['chrome', 'firefox', 'safari', 'edge']
            ]
        ]);

        $serialized = $this->serializeComponent($component);

        expect($serialized['attributes'])->toHaveKey('data-component-version', '2.1.0');
        expect($serialized['attributes'])->toHaveKey('data-tags', 'featured,responsive,accessible');
        expect($serialized['attributes'])->toHaveKey('data-author', 'Design Team');
        expect($serialized['attributes'])->toHaveKey('data-performance-score', 95);
        expect($serialized['attributes'])->toHaveKey('data-accessibility-score', 98);
        expect($serialized['attributes'])->toHaveKey('data-seo-optimized', true);
        expect($serialized['attributes'])->toHaveKey('data-mobile-optimized', true);
    });

    it('handles serialization errors gracefully', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'invalid_category', // Invalid category
            'name' => 'Error Test Component',
            'config' => [
                'circular_reference' => null // This will be set to create circular reference
            ]
        ]);

        // Create circular reference in config
        $config = $component->config;
        $config['circular_reference'] = &$config;
        $component->config = $config;

        $result = $this->serializeComponentSafely($component);

        expect($result['success'])->toBeFalse();
        expect($result['errors'])->not->toBeEmpty();
        expect($result['errors'])->toContain('Invalid component category: invalid_category');
    });
});

// Helper methods for testing
function serializeComponent(Component $component): array
{
    // This would normally call the actual serialization service
    // For testing, we'll simulate the expected structure
    return [
        'type' => $this->getGrapeJSComponentType($component->category),
        'attributes' => $this->buildComponentAttributes($component),
        'components' => $this->buildNestedComponents($component),
        'styles' => $this->buildComponentStyles($component)
    ];
}

function serializeComponentSafely(Component $component): array
{
    try {
        return [
            'success' => true,
            'data' => $this->serializeComponent($component)
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'errors' => [$e->getMessage()]
        ];
    }
}

function getGrapeJSComponentType(string $category): string
{
    return match($category) {
        'hero' => 'hero-component',
        'forms' => 'form-component',
        'testimonials' => 'testimonial-component',
        'statistics' => 'statistics-component',
        'ctas' => 'cta-component',
        'media' => 'media-component',
        default => throw new Exception("Invalid component category: {$category}")
    };
}

function buildComponentAttributes(Component $component): array
{
    $attributes = [
        'data-component-id' => $component->id,
        'data-component-category' => $component->category,
        'data-component-name' => $component->name,
        'data-component-version' => $component->version ?? '1.0.0'
    ];

    // Add category-specific attributes
    foreach ($component->config as $key => $value) {
        if (is_scalar($value)) {
            $attributes["data-{$key}"] = $value;
        }
    }

    // Add metadata attributes
    if ($component->metadata) {
        foreach ($component->metadata as $key => $value) {
            if (is_scalar($value)) {
                $attributes["data-{$key}"] = $value;
            } elseif (is_array($value)) {
                $attributes["data-{$key}"] = implode(',', $value);
            }
        }
    }

    return $attributes;
}

function buildNestedComponents(Component $component): array
{
    $components = [];

    switch ($component->category) {
        case 'forms':
            if (isset($component->config['fields'])) {
                foreach ($component->config['fields'] as $field) {
                    $components[] = [
                        'type' => 'input',
                        'attributes' => [
                            'type' => $field['type'],
                            'name' => $field['name'],
                            'placeholder' => $field['placeholder'] ?? '',
                            'required' => $field['required'] ?? false
                        ]
                    ];
                }
            }
            break;

        case 'testimonials':
            if (isset($component->config['testimonials'])) {
                foreach ($component->config['testimonials'] as $testimonial) {
                    $components[] = [
                        'type' => 'testimonial-item',
                        'attributes' => [
                            'data-rating' => $testimonial['rating'] ?? 5,
                            'data-industry' => $testimonial['industry'] ?? '',
                            'data-location' => $testimonial['location'] ?? ''
                        ]
                    ];
                }
            }
            break;

        case 'statistics':
            if (isset($component->config['statistics'])) {
                foreach ($component->config['statistics'] as $stat) {
                    $components[] = [
                        'type' => 'statistic-counter',
                        'attributes' => [
                            'data-value' => $stat['value'],
                            'data-suffix' => $stat['suffix'] ?? '',
                            'data-icon' => $stat['icon'] ?? ''
                        ]
                    ];
                }
            }
            break;

        case 'media':
            if (isset($component->config['images'])) {
                foreach ($component->config['images'] as $image) {
                    $components[] = [
                        'type' => 'image',
                        'attributes' => [
                            'src' => $image['src'],
                            'alt' => $image['alt'],
                            'data-category' => $image['category'] ?? ''
                        ]
                    ];
                }
            }
            break;
    }

    return $components;
}

function buildComponentStyles(Component $component): array
{
    $styles = [];

    if (isset($component->config['responsive'])) {
        foreach ($component->config['responsive'] as $breakpoint => $styles_config) {
            $selector = $breakpoint === 'desktop' ? 
                ".{$component->category}-component" : 
                "@media (max-width: {$this->getBreakpointWidth($breakpoint)}) { .{$component->category}-component";
            
            $styles[] = [
                'selectors' => [$selector],
                'style' => $styles_config
            ];
        }
    }

    return $styles;
}

function getBreakpointWidth(string $breakpoint): string
{
    return match($breakpoint) {
        'tablet' => '768px',
        'mobile' => '480px',
        default => '1200px'
    };
}