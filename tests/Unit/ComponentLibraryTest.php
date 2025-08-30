<?php

use App\Models\Component;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
});

describe('Component Library System', function () {
    it('can create components for the library', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Hero Component',
            'category' => 'hero',
            'type' => 'individual',
            'description' => 'A test hero component for individual alumni',
            'config' => [
                'headline' => 'Test Headline',
                'subheading' => 'Test Subheading',
                'audienceType' => 'individual'
            ],
            'version' => '1.0.0',
            'is_active' => true
        ]);

        expect($component)->toBeInstanceOf(Component::class);
        expect($component->name)->toBe('Test Hero Component');
        expect($component->category)->toBe('hero');
        expect($component->type)->toBe('individual');
        expect($component->is_active)->toBeTrue();
    });

    it('can filter components by category', function () {
        // Create components in different categories
        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'name' => 'Hero Component'
        ]);

        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'name' => 'Form Component'
        ]);

        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'testimonials',
            'name' => 'Testimonial Component'
        ]);

        $heroComponents = Component::where('tenant_id', $this->tenant->id)
            ->where('category', 'hero')
            ->get();

        $formComponents = Component::where('tenant_id', $this->tenant->id)
            ->where('category', 'forms')
            ->get();

        expect($heroComponents)->toHaveCount(1);
        expect($formComponents)->toHaveCount(1);
        expect($heroComponents->first()->name)->toBe('Hero Component');
        expect($formComponents->first()->name)->toBe('Form Component');
    });

    it('can search components by name and description', function () {
        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Alumni Success Hero',
            'description' => 'Hero section for showcasing alumni success stories'
        ]);

        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Contact Form',
            'description' => 'Simple contact form for inquiries'
        ]);

        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Newsletter Signup',
            'description' => 'Form for newsletter subscriptions'
        ]);

        // Search by name
        $heroComponents = Component::where('tenant_id', $this->tenant->id)
            ->where('name', 'like', '%Hero%')
            ->get();

        // Search by description
        $formComponents = Component::where('tenant_id', $this->tenant->id)
            ->where('description', 'like', '%form%')
            ->get();

        expect($heroComponents)->toHaveCount(1);
        expect($formComponents)->toHaveCount(2);
        expect($heroComponents->first()->name)->toBe('Alumni Success Hero');
    });

    it('can manage component active status', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        expect($component->is_active)->toBeTrue();

        $component->update(['is_active' => false]);
        $component->refresh();

        expect($component->is_active)->toBeFalse();

        // Test filtering active components
        $activeComponents = Component::where('tenant_id', $this->tenant->id)
            ->where('is_active', true)
            ->get();

        expect($activeComponents)->toHaveCount(0);
    });

    it('stores component configuration as JSON', function () {
        $config = [
            'headline' => 'Welcome Alumni',
            'subheading' => 'Connect with your network',
            'ctaButtons' => [
                [
                    'text' => 'Join Now',
                    'url' => '/signup',
                    'style' => 'primary'
                ]
            ],
            'backgroundMedia' => [
                'type' => 'image',
                'url' => 'https://example.com/bg.jpg'
            ]
        ];

        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'config' => $config
        ]);

        expect($component->config)->toBeArray();
        expect($component->config['headline'])->toBe('Welcome Alumni');
        expect($component->config['ctaButtons'])->toHaveCount(1);
        expect($component->config['ctaButtons'][0]['text'])->toBe('Join Now');
    });
});