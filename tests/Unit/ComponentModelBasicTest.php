<?php

use App\Models\Component;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a component', function () {
    $tenant = Tenant::factory()->create();

    $component = Component::factory()->create([
        'tenant_id' => $tenant->id,
        'category' => 'hero',
    ]);

    expect($component)->toBeInstanceOf(Component::class);
    expect($component->category)->toBe('hero');
});
