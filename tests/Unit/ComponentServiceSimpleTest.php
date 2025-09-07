<?php

use App\Models\Component;
use App\Models\Tenant;
use App\Services\ComponentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ComponentService;
});

describe('Component Service Basic Tests', function () {
    it('can create a component service instance', function () {
        expect($this->service)->toBeInstanceOf(ComponentService::class);
    });

    it('validates component data correctly', function () {
        // Create tenant manually
        $tenant = new Tenant;
        $tenant->id = 'test-tenant';
        $tenant->save();

        $data = [
            'tenant_id' => $tenant->id,
            'name' => 'Test Component',
            'slug' => 'test-component',
            'category' => 'hero',
            'type' => 'hero-basic',
            'description' => 'A test component',
        ];

        $component = $this->service->create($data, $tenant->id);

        expect($component)->toBeInstanceOf(Component::class);
        expect($component->name)->toBe('Test Component');
        expect($component->tenant_id)->toBe($tenant->id);
    });

    it('throws validation exception for invalid data', function () {
        $tenant = new Tenant;
        $tenant->id = 'test-tenant-2';
        $tenant->save();

        $data = [
            'name' => '', // Required field is empty
            'category' => 'invalid-category',
            'type' => 'test-type',
        ];

        expect(fn () => $this->service->create($data, $tenant->id))
            ->toThrow(ValidationException::class);
    });

    it('generates unique slugs', function () {
        $tenant = new Tenant;
        $tenant->id = 'test-tenant-3';
        $tenant->save();

        // Create first component
        $component1 = new Component([
            'tenant_id' => $tenant->id,
            'name' => 'Test Component',
            'slug' => 'test-component',
            'category' => 'hero',
            'type' => 'hero-basic',
        ]);
        $component1->save();

        // Create second component with same name
        $data = [
            'name' => 'Test Component',
            'category' => 'hero',
            'type' => 'hero-basic',
        ];

        $component2 = $this->service->create($data, $tenant->id);

        expect($component2->slug)->toBe('test-component-1');
    });

    it('can update a component', function () {
        $tenant = new Tenant;
        $tenant->id = 'test-tenant-4';
        $tenant->save();

        $component = new Component([
            'tenant_id' => $tenant->id,
            'name' => 'Original Name',
            'slug' => 'original-name',
            'category' => 'hero',
            'type' => 'hero-basic',
        ]);
        $component->save();

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $updatedComponent = $this->service->update($component, $updateData);

        expect($updatedComponent->name)->toBe('Updated Name');
        expect($updatedComponent->description)->toBe('Updated description');
    });

    it('can duplicate a component', function () {
        $tenant = new Tenant;
        $tenant->id = 'test-tenant-5';
        $tenant->save();

        $original = new Component([
            'tenant_id' => $tenant->id,
            'name' => 'Original Component',
            'slug' => 'original-component',
            'category' => 'hero',
            'type' => 'hero-basic',
            'is_active' => true,
        ]);
        $original->save();

        $duplicate = $this->service->duplicate($original);

        expect($duplicate->name)->toBe('Original Component (Copy)');
        expect($duplicate->tenant_id)->toBe($original->tenant_id);
        expect($duplicate->is_active)->toBeFalse();
        expect($duplicate->id)->not->toBe($original->id);
    });

    it('can activate and deactivate components', function () {
        $tenant = new Tenant;
        $tenant->id = 'test-tenant-6';
        $tenant->save();

        $component = new Component([
            'tenant_id' => $tenant->id,
            'name' => 'Test Component',
            'slug' => 'test-component',
            'category' => 'hero',
            'type' => 'hero-basic',
            'is_active' => false,
        ]);
        $component->save();

        // Test activation
        $activated = $this->service->activate($component);
        expect($activated->is_active)->toBeTrue();

        // Test deactivation
        $deactivated = $this->service->deactivate($component);
        expect($deactivated->is_active)->toBeFalse();
    });

    it('can generate component preview', function () {
        $tenant = new Tenant;
        $tenant->id = 'test-tenant-7';
        $tenant->save();

        $component = new Component([
            'tenant_id' => $tenant->id,
            'name' => 'Hero Component',
            'slug' => 'hero-component',
            'category' => 'hero',
            'type' => 'hero-basic',
            'config' => [
                'headline' => 'Test Headline',
                'subheading' => 'Test Subheading',
            ],
        ]);
        $component->save();

        $preview = $this->service->generatePreview($component);

        expect($preview)->toHaveKeys(['id', 'name', 'category', 'config', 'sample_data', 'preview_html']);
        expect($preview['category'])->toBe('hero');
        expect($preview['sample_data'])->toHaveKey('headline');
        expect($preview['preview_html'])->toContain('hero-preview');
    });
});
