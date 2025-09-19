<?php

use App\Models\StylePreset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Style Presets API', function () {
    it('can list style presets', function () {
        StylePreset::factory()->count(3)->create([
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        $response = $this->getJson('/api/style-presets');

        $response->assertOk()
            ->assertJsonCount(3);
    });

    it('can create a style preset', function () {
        $presetData = [
            'name' => 'Primary Button Style',
            'description' => 'Standard primary button styling',
            'category' => 'buttons',
            'styles' => [
                'background-color' => '#3B82F6',
                'color' => '#FFFFFF',
                'padding' => '0.75rem 1.5rem',
                'border-radius' => '0.375rem',
                'font-weight' => '600'
            ],
            'tailwind_classes' => ['bg-blue-500', 'text-white', 'px-6', 'py-3', 'rounded-md', 'font-semibold']
        ];

        $response = $this->postJson('/api/style-presets', $presetData);

        $response->assertCreated()
            ->assertJsonFragment([
                'name' => 'Primary Button Style',
                'category' => 'buttons'
            ]);

        $this->assertDatabaseHas('style_presets', [
            'name' => 'Primary Button Style',
            'category' => 'buttons',
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);
    });

    it('validates required fields when creating preset', function () {
        $response = $this->postJson('/api/style-presets', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'category', 'styles']);
    });

    it('can update a style preset', function () {
        $preset = StylePreset::factory()->create([
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        $updateData = [
            'name' => 'Updated Preset Name',
            'description' => 'Updated description'
        ];

        $response = $this->putJson("/api/style-presets/{$preset->id}", $updateData);

        $response->assertOk()
            ->assertJsonFragment([
                'name' => 'Updated Preset Name',
                'description' => 'Updated description'
            ]);
    });

    it('can delete a style preset', function () {
        $preset = StylePreset::factory()->create([
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/style-presets/{$preset->id}");

        $response->assertOk();
        $this->assertSoftDeleted('style_presets', ['id' => $preset->id]);
    });

    it('can get presets by category', function () {
        StylePreset::factory()->create([
            'category' => 'buttons',
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        StylePreset::factory()->create([
            'category' => 'cards',
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        $response = $this->getJson('/api/style-presets/categories/buttons');

        $response->assertOk()
            ->assertJsonCount(1);
    });

    it('can duplicate a style preset', function () {
        $preset = StylePreset::factory()->create([
            'name' => 'Original Preset',
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        $response = $this->postJson("/api/style-presets/{$preset->id}/duplicate");

        $response->assertCreated()
            ->assertJsonFragment([
                'name' => 'Original Preset (Copy)'
            ]);

        $this->assertDatabaseHas('style_presets', [
            'name' => 'Original Preset (Copy)',
            'tenant_id' => tenant('id')
        ]);
    });

    it('can bulk store style presets', function () {
        $presetsData = [
            'presets' => [
                [
                    'name' => 'Preset 1',
                    'category' => 'buttons',
                    'styles' => ['color' => '#000000']
                ],
                [
                    'name' => 'Preset 2',
                    'category' => 'cards',
                    'styles' => ['background-color' => '#FFFFFF']
                ]
            ]
        ];

        $response = $this->postJson('/api/style-presets/bulk-store', $presetsData);

        $response->assertCreated()
            ->assertJsonFragment(['message' => 'Style presets imported successfully']);

        $this->assertDatabaseHas('style_presets', ['name' => 'Preset 1']);
        $this->assertDatabaseHas('style_presets', ['name' => 'Preset 2']);
    });

    it('can export style presets', function () {
        StylePreset::factory()->count(2)->create([
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        $response = $this->getJson('/api/style-presets/export');

        $response->assertOk()
            ->assertJsonStructure([
                'presets' => [
                    '*' => ['name', 'description', 'category', 'styles', 'tailwind_classes']
                ],
                'exported_at',
                'tenant_id'
            ]);
    });

    it('enforces tenant isolation', function () {
        $otherTenantPreset = StylePreset::factory()->create([
            'tenant_id' => 'other-tenant-id',
            'created_by' => $this->user->id
        ]);

        $response = $this->getJson("/api/style-presets/{$otherTenantPreset->id}");

        $response->assertNotFound();
    });
});

describe('Brand Guidelines Validation', function () {
    it('validates brand color compliance', function () {
        $brandColors = [
            '#3B82F6', '#1E40AF', '#10B981', '#F59E0B', 
            '#6B7280', '#059669', '#D97706', '#DC2626'
        ];

        $preset = StylePreset::factory()->create([
            'styles' => [
                'color' => '#3B82F6', // Brand compliant
                'background-color' => '#FF0000' // Not brand compliant
            ],
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        expect($preset->isBrandCompliant())->toBeFalse();
    });

    it('identifies brand compliant presets', function () {
        $preset = StylePreset::factory()->create([
            'styles' => [
                'color' => '#3B82F6',
                'background-color' => '#10B981',
                'border-color' => '#6B7280'
            ],
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        expect($preset->isBrandCompliant())->toBeTrue();
    });

    it('generates correct preview styles', function () {
        $preset = StylePreset::factory()->create([
            'styles' => [
                'color' => '#FFFFFF',
                'background-color' => '#3B82F6',
                'padding' => '1rem'
            ],
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        $previewStyle = $preset->getPreviewStyle();

        expect($previewStyle)->toHaveKey('color', '#FFFFFF');
        expect($previewStyle)->toHaveKey('background-color', '#3B82F6');
        expect($previewStyle)->toHaveKey('padding', '1rem');
        expect($previewStyle)->toHaveKey('width', '100%');
        expect($previewStyle)->toHaveKey('height', '40px');
    });

    it('converts camelCase to kebab-case for CSS properties', function () {
        $preset = StylePreset::factory()->create([
            'styles' => [
                'backgroundColor' => '#3B82F6',
                'borderRadius' => '0.5rem',
                'fontSize' => '1rem'
            ],
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        $formattedStyles = $preset->formatted_styles;

        expect($formattedStyles)->toHaveKey('background-color', '#3B82F6');
        expect($formattedStyles)->toHaveKey('border-radius', '0.5rem');
        expect($formattedStyles)->toHaveKey('font-size', '1rem');
    });

    it('applies preset to component data correctly', function () {
        $preset = StylePreset::factory()->create([
            'styles' => [
                'color' => '#FFFFFF',
                'background-color' => '#3B82F6'
            ],
            'tailwind_classes' => ['text-white', 'bg-blue-500'],
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        $componentData = [
            'style' => ['padding' => '1rem'],
            'classes' => 'existing-class'
        ];

        $updatedData = $preset->applyToComponent($componentData);

        expect($updatedData['style'])->toHaveKey('color', '#FFFFFF');
        expect($updatedData['style'])->toHaveKey('background-color', '#3B82F6');
        expect($updatedData['style'])->toHaveKey('padding', '1rem');
        expect($updatedData['classes'])->toContain('text-white');
        expect($updatedData['classes'])->toContain('bg-blue-500');
        expect($updatedData['classes'])->toContain('existing-class');
    });

    it('exports preset data correctly', function () {
        $preset = StylePreset::factory()->create([
            'name' => 'Test Preset',
            'description' => 'Test Description',
            'category' => 'buttons',
            'styles' => ['color' => '#3B82F6'],
            'tailwind_classes' => ['text-blue-500'],
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        $exportData = $preset->export();

        expect($exportData)->toHaveKey('name', 'Test Preset');
        expect($exportData)->toHaveKey('description', 'Test Description');
        expect($exportData)->toHaveKey('category', 'buttons');
        expect($exportData)->toHaveKey('styles');
        expect($exportData)->toHaveKey('tailwind_classes');
        expect($exportData)->toHaveKey('is_brand_compliant');
        expect($exportData)->toHaveKey('exported_at');
    });
});

describe('Tailwind CSS Integration', function () {
    it('generates correct Tailwind classes string', function () {
        $preset = StylePreset::factory()->create([
            'tailwind_classes' => ['bg-blue-500', 'text-white', 'px-4', 'py-2', 'rounded'],
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        expect($preset->tailwind_classes_string)->toBe('bg-blue-500 text-white px-4 py-2 rounded');
    });

    it('handles empty Tailwind classes array', function () {
        $preset = StylePreset::factory()->create([
            'tailwind_classes' => [],
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        expect($preset->tailwind_classes_string)->toBe('');
    });

    it('handles null Tailwind classes', function () {
        $preset = StylePreset::factory()->create([
            'tailwind_classes' => null,
            'tenant_id' => tenant('id'),
            'created_by' => $this->user->id
        ]);

        expect($preset->tailwind_classes_string)->toBe('');
    });
});