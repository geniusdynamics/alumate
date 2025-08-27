<?php

use App\Models\Component;
use App\Models\ComponentTheme;
use App\Models\Tenant;
use App\Services\ComponentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->service = new ComponentService;

    // Create a default theme for the tenant
    $this->defaultTheme = ComponentTheme::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_default' => true,
    ]);
});

describe('Component Creation', function () {
    it('creates a component with valid data', function () {
        $data = [
            'name' => 'Test Hero Component',
            'category' => 'hero',
            'type' => 'hero-basic',
            'description' => 'A basic hero component for testing',
            'config' => [
                'headline' => 'Welcome',
                'subheading' => 'Test subheading',
            ],
        ];

        $component = $this->service->create($data, $this->tenant->id);

        expect($component)->toBeInstanceOf(Component::class);
        expect($component->name)->toBe('Test Hero Component');
        expect($component->tenant_id)->toBe($this->tenant->id);
        expect($component->slug)->toBe('test-hero-component');
        expect($component->theme_id)->toBe($this->defaultTheme->id);
    });

    it('generates unique slug when name conflicts exist', function () {
        // Create first component
        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Component',
            'slug' => 'test-component',
        ]);

        $data = [
            'name' => 'Test Component',
            'category' => 'hero',
            'type' => 'hero-basic',
        ];

        $component = $this->service->create($data, $this->tenant->id);

        expect($component->slug)->toBe('test-component-1');
    });

    it('validates component configuration', function () {
        $data = [
            'name' => 'Invalid Component',
            'category' => 'hero',
            'type' => 'hero-basic',
            'config' => [
                'background_type' => 'invalid-type', // Should be image, video, or gradient
            ],
        ];

        expect(fn () => $this->service->create($data, $this->tenant->id))
            ->toThrow(ValidationException::class);
    });

    it('throws validation exception for invalid data', function () {
        $data = [
            'name' => '', // Required field
            'category' => 'invalid-category',
            'type' => 'test-type',
        ];

        expect(fn () => $this->service->create($data, $this->tenant->id))
            ->toThrow(ValidationException::class);
    });
});

describe('Component Updates', function () {
    it('updates component with valid data', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $updatedComponent = $this->service->update($component, $updateData);

        expect($updatedComponent->name)->toBe('Updated Name');
        expect($updatedComponent->description)->toBe('Updated description');
    });

    it('prevents changing tenant_id', function () {
        $otherTenant = Tenant::factory()->create();
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $updateData = [
            'tenant_id' => $otherTenant->id,
        ];

        expect(fn () => $this->service->update($component, $updateData))
            ->toThrow(\InvalidArgumentException::class, 'Cannot change component tenant');
    });

    it('generates new slug when name changes', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Name',
            'slug' => 'original-name',
        ]);

        $updateData = [
            'name' => 'New Name',
        ];

        $updatedComponent = $this->service->update($component, $updateData);

        expect($updatedComponent->slug)->toBe('new-name');
    });
});

describe('Component Duplication', function () {
    it('duplicates component with default modifications', function () {
        $original = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Component',
            'is_active' => true,
        ]);

        $duplicate = $this->service->duplicate($original);

        expect($duplicate->name)->toBe('Original Component (Copy)');
        expect($duplicate->tenant_id)->toBe($original->tenant_id);
        expect($duplicate->is_active)->toBeFalse();
        expect($duplicate->id)->not->toBe($original->id);
    });

    it('duplicates component with custom modifications', function () {
        $original = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $modifications = [
            'name' => 'Custom Duplicate Name',
            'description' => 'Custom description',
        ];

        $duplicate = $this->service->duplicate($original, $modifications);

        expect($duplicate->name)->toBe('Custom Duplicate Name');
        expect($duplicate->description)->toBe('Custom description');
    });
});

describe('Component Versioning', function () {
    it('creates new version with valid version number', function () {
        $original = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Component',
            'version' => '1.0.0',
        ]);

        $newVersion = $this->service->createVersion($original, '1.1.0');

        expect($newVersion->version)->toBe('1.1.0');
        expect($newVersion->name)->toBe('Test Component');
        expect($newVersion->slug)->toBe($original->slug.'-v1-1-0');
        expect($newVersion->is_active)->toBeFalse();
    });

    it('throws exception for invalid version format', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        expect(fn () => $this->service->createVersion($component, 'invalid-version'))
            ->toThrow(\InvalidArgumentException::class, 'Version must be in format x.y.z');
    });

    it('throws exception for duplicate version', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Component',
            'version' => '1.0.0',
        ]);

        // Create version 1.1.0
        $this->service->createVersion($component, '1.1.0');

        // Try to create the same version again
        expect(fn () => $this->service->createVersion($component, '1.1.0'))
            ->toThrow(\InvalidArgumentException::class);
    });
});

describe('Component Activation/Deactivation', function () {
    it('activates component', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => false,
        ]);

        $activated = $this->service->activate($component);

        expect($activated->is_active)->toBeTrue();
    });

    it('deactivates component', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $deactivated = $this->service->deactivate($component);

        expect($deactivated->is_active)->toBeFalse();
    });
});

describe('Component Search and Filtering', function () {
    beforeEach(function () {
        // Create test components
        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Hero Component',
            'category' => 'hero',
            'type' => 'hero-basic',
            'is_active' => true,
        ]);

        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Form Component',
            'category' => 'forms',
            'type' => 'contact-form',
            'is_active' => false,
        ]);

        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Testimonial Component',
            'category' => 'testimonials',
            'type' => 'testimonial-carousel',
            'is_active' => true,
        ]);
    });

    it('searches components by name', function () {
        $results = $this->service->search([
            'search' => 'Hero',
        ], $this->tenant->id);

        expect($results->total())->toBe(1);
        expect($results->first()->name)->toBe('Hero Component');
    });

    it('filters components by category', function () {
        $results = $this->service->search([
            'category' => 'hero',
        ], $this->tenant->id);

        expect($results->total())->toBe(1);
        expect($results->first()->category)->toBe('hero');
    });

    it('filters components by active status', function () {
        $activeResults = $this->service->search([
            'is_active' => true,
        ], $this->tenant->id);

        $inactiveResults = $this->service->search([
            'is_active' => false,
        ], $this->tenant->id);

        expect($activeResults->total())->toBe(2);
        expect($inactiveResults->total())->toBe(1);
    });

    it('filters components by multiple categories', function () {
        $results = $this->service->search([
            'category' => ['hero', 'forms'],
        ], $this->tenant->id);

        expect($results->total())->toBe(2);
    });

    it('gets components by category', function () {
        $heroComponents = $this->service->getByCategory('hero', $this->tenant->id);

        expect($heroComponents)->toHaveCount(1);
        expect($heroComponents->first()->category)->toBe('hero');
    });
});

describe('Component Preview Generation', function () {
    it('generates preview for hero component', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'type' => 'hero-basic',
            'config' => [
                'headline' => 'Test Headline',
                'subheading' => 'Test Subheading',
            ],
        ]);

        $preview = $this->service->generatePreview($component);

        expect($preview)->toHaveKeys(['id', 'name', 'category', 'config', 'sample_data', 'preview_html']);
        expect($preview['category'])->toBe('hero');
        expect($preview['sample_data'])->toHaveKey('headline');
        expect($preview['preview_html'])->toContain('hero-preview');
    });

    it('generates preview for form component', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'type' => 'contact-form',
        ]);

        $preview = $this->service->generatePreview($component);

        expect($preview['category'])->toBe('forms');
        expect($preview['sample_data'])->toHaveKey('fields');
        expect($preview['preview_html'])->toContain('form-preview');
    });

    it('merges custom config with component config', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'headline' => 'Original Headline',
                'subheading' => 'Original Subheading',
            ],
        ]);

        $customConfig = [
            'headline' => 'Custom Headline',
        ];

        $preview = $this->service->generatePreview($component, $customConfig);

        expect($preview['config']['headline'])->toBe('Custom Headline');
        expect($preview['config']['subheading'])->toBe('Original Subheading');
    });
});

describe('Component Deletion', function () {
    it('deletes component and its instances', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create some instances
        $component->instances()->create([
            'page_type' => 'landing_page',
            'page_id' => 1,
            'position' => 0,
        ]);

        $result = $this->service->delete($component);

        expect($result)->toBeTrue();
        expect(Component::find($component->id))->toBeNull();
    });
});

describe('Error Handling', function () {
    it('handles component errors gracefully', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $exception = new ValidationException(
            validator([], ['required_field' => 'required'])
        );

        $errorData = $this->service->handleComponentError($component, $exception);

        expect($errorData)->toHaveKeys(['error', 'suggestions']);
        expect($errorData['error']['component_id'])->toBe($component->id);
        expect($errorData['suggestions'])->toBeArray();
    });
});

describe('Component Statistics', function () {
    it('returns component statistics for tenant', function () {
        // Create components in different categories
        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'is_active' => true,
        ]);

        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'is_active' => false,
        ]);

        $stats = $this->service->getComponentStats($this->tenant->id);

        expect($stats)->toHaveKeys([
            'total_components',
            'active_components',
            'components_by_category',
            'recent_components',
        ]);

        expect($stats['total_components'])->toBe(2);
        expect($stats['active_components'])->toBe(1);
        expect($stats['components_by_category']['hero'])->toBe(1);
        expect($stats['components_by_category']['forms'])->toBe(1);
    });
});
