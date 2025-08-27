<?php

use App\Models\Component;
use App\Models\Tenant;
use App\Services\ComponentRenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a component and render it', function () {
    $tenant = Tenant::factory()->create();

    $component = Component::factory()->create([
        'tenant_id' => $tenant->id,
        'category' => 'hero',
        'type' => 'standard',
        'config' => [
            'headline' => 'Test Headline',
        ],
    ]);

    $service = new ComponentRenderService;
    $result = $service->render($component, [], ['use_cache' => false]);

    expect($result)->toBeArray();
    expect($result)->toHaveKey('id');
    expect($result['id'])->toBe($component->id);
});
