<?php

use App\Models\Component;
use App\Models\Tenant;
use App\Services\ComponentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->componentService = app(ComponentService::class);
});

describe('Media Component Creation', function () {
    it('can create an image gallery component', function () {
        $config = [
            'type' => 'image-gallery',
            'title' => 'Alumni Photo Gallery',
            'description' => 'Interactive photo gallery showcasing alumni events',
            'layout' => 'grid',
            'theme' => 'card',
            'spacing' => 'default',
            'gridColumns' => [
                'desktop' => 3,
                'tablet' => 2,
                'mobile' => 1
            ],
            'mediaAssets' => [
                [
                    'id' => 'img-1',
                    'type' => 'image',
                    'url' => 'https://example.com/image1.jpg',
                    'alt' => 'Alumni event photo',
                    'thumbnail' => 'https://example.com/thumb1.jpg'
                ]
            ],
            'lightbox' => [
                'enabled' => true,
                'showThumbnails' => true,
                'showCaptions' => true
            ],
            'optimization' => [
                'webpSupport' => true,
                'lazyLoading' => true,
                'responsiveImages' => true
            ],
            'accessibility' => [
                'altTextRequired' => true,
                'keyboardNavigation' => true,
                'screenReaderSupport' => true
            ],
            'mobileOptimized' => true
        ];

        $component = $this->componentService->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Alumni Photo Gallery',
            'category' => 'media',
            'type' => 'image-gallery',
            'description' => 'Interactive photo gallery showcasing alumni events',
            'config' => $config,
            'version' => '1.0.0',
            'is_active' => true
        ]);

        expect($component)->toBeInstanceOf(Component::class);
        expect($component->category)->toBe('media');
        expect($component->type)->toBe('image-gallery');
        expect($component->config['type'])->toBe('image-gallery');
        expect($component->config['title'])->toBe('Alumni Photo Gallery');
        expect($component->config['lightbox']['enabled'])->toBeTrue();
        expect($component->config['mobileOptimized'])->toBeTrue();
    });
});