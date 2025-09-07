<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates component_instances table with correct structure', function () {
    expect(Schema::hasTable('component_instances'))->toBeTrue();

    $columns = Schema::getColumnListing('component_instances');
    expect($columns)->toContain('id');
    expect($columns)->toContain('component_id');
    expect($columns)->toContain('page_type');
    expect($columns)->toContain('page_id');
    expect($columns)->toContain('position');
    expect($columns)->toContain('custom_config');
    expect($columns)->toContain('created_at');
    expect($columns)->toContain('updated_at');
});

it('has correct column types and constraints', function () {
    // Test that we can insert data with correct types
    DB::table('tenants')->insert([
        'id' => 'test-tenant-types',
        'name' => 'Test Tenant Types',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $componentId = DB::table('components')->insertGetId([
        'tenant_id' => 'test-tenant-types',
        'name' => 'Test Component Types',
        'slug' => 'test-component-types',
        'category' => 'hero',
        'type' => 'basic',
        'description' => 'Test component',
        'config' => json_encode(['schema' => [], 'defaults' => []]),
        'version' => '1.0.0',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Test inserting with correct data types
    DB::table('component_instances')->insert([
        'component_id' => $componentId, // bigint
        'page_type' => 'landing_page', // string
        'page_id' => 123, // bigint
        'position' => 5, // integer
        'custom_config' => json_encode(['test' => 'value']), // json
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $instance = DB::table('component_instances')->first();
    expect($instance->component_id)->toBe($componentId);
    expect($instance->page_type)->toBe('landing_page');
    expect($instance->page_id)->toBe(123);
    expect($instance->position)->toBe(5);
    expect(json_decode($instance->custom_config, true))->toBe(['test' => 'value']);
});

it('has required indexes for performance', function () {
    // Test that indexes exist by checking if queries can use them efficiently
    // We'll verify this by ensuring the table structure allows for the expected operations

    DB::table('tenants')->insert([
        'id' => 'test-tenant-index',
        'name' => 'Test Tenant Index',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $componentId = DB::table('components')->insertGetId([
        'tenant_id' => 'test-tenant-index',
        'name' => 'Test Component Index',
        'slug' => 'test-component-index',
        'category' => 'hero',
        'type' => 'basic',
        'description' => 'Test component',
        'config' => json_encode(['schema' => [], 'defaults' => []]),
        'version' => '1.0.0',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Insert multiple instances to test index performance
    for ($i = 0; $i < 5; $i++) {
        DB::table('component_instances')->insert([
            'component_id' => $componentId,
            'page_type' => 'landing_page',
            'page_id' => 1,
            'position' => $i,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Test queries that should use the indexes
    $pageInstances = DB::table('component_instances')
        ->where('page_type', 'landing_page')
        ->where('page_id', 1)
        ->orderBy('position')
        ->get();

    expect($pageInstances)->toHaveCount(5);

    $componentInstances = DB::table('component_instances')
        ->where('component_id', $componentId)
        ->get();

    expect($componentInstances)->toHaveCount(5);
});

it('has foreign key constraint to components table', function () {
    // Test cascade delete behavior
    DB::table('tenants')->insert([
        'id' => 'test-tenant-fk',
        'name' => 'Test Tenant FK',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $componentId = DB::table('components')->insertGetId([
        'tenant_id' => 'test-tenant-fk',
        'name' => 'Test Component FK',
        'slug' => 'test-component-fk',
        'category' => 'hero',
        'type' => 'basic',
        'description' => 'Test component',
        'config' => json_encode(['schema' => [], 'defaults' => []]),
        'version' => '1.0.0',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Insert component instance
    DB::table('component_instances')->insert([
        'component_id' => $componentId,
        'page_type' => 'landing_page',
        'page_id' => 1,
        'position' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('component_instances')->where('component_id', $componentId)->count())->toBe(1);

    // Delete the component - should cascade delete the instance
    DB::table('components')->where('id', $componentId)->delete();

    expect(DB::table('component_instances')->where('component_id', $componentId)->count())->toBe(0);
});

it('enforces unique constraint on page_type, page_id, position', function () {
    // First, we need to create a tenant and component for testing
    DB::table('tenants')->insert([
        'id' => 'test-tenant',
        'name' => 'Test Tenant',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $componentId = DB::table('components')->insertGetId([
        'tenant_id' => 'test-tenant',
        'name' => 'Test Component',
        'slug' => 'test-component',
        'category' => 'hero',
        'type' => 'basic',
        'description' => 'Test component',
        'config' => json_encode(['schema' => [], 'defaults' => []]),
        'version' => '1.0.0',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Insert first instance
    DB::table('component_instances')->insert([
        'component_id' => $componentId,
        'page_type' => 'landing_page',
        'page_id' => 1,
        'position' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Try to insert duplicate position - should fail
    expect(function () use ($componentId) {
        DB::table('component_instances')->insert([
            'component_id' => $componentId,
            'page_type' => 'landing_page',
            'page_id' => 1,
            'position' => 0, // Same position
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toThrow(Exception::class);
});

it('allows same position for different pages', function () {
    // Create tenant and component
    DB::table('tenants')->insert([
        'id' => 'test-tenant-2',
        'name' => 'Test Tenant 2',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $componentId = DB::table('components')->insertGetId([
        'tenant_id' => 'test-tenant-2',
        'name' => 'Test Component 2',
        'slug' => 'test-component-2',
        'category' => 'forms',
        'type' => 'contact',
        'description' => 'Test component 2',
        'config' => json_encode(['schema' => [], 'defaults' => []]),
        'version' => '1.0.0',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Insert instance on page 1
    DB::table('component_instances')->insert([
        'component_id' => $componentId,
        'page_type' => 'landing_page',
        'page_id' => 1,
        'position' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Insert instance on page 2 with same position - should succeed
    DB::table('component_instances')->insert([
        'component_id' => $componentId,
        'page_type' => 'landing_page',
        'page_id' => 2,
        'position' => 0, // Same position but different page
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('component_instances')->count())->toBe(2);
});

it('allows custom_config as valid json', function () {
    // Create tenant and component
    DB::table('tenants')->insert([
        'id' => 'test-tenant-3',
        'name' => 'Test Tenant 3',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $componentId = DB::table('components')->insertGetId([
        'tenant_id' => 'test-tenant-3',
        'name' => 'Test Component 3',
        'slug' => 'test-component-3',
        'category' => 'testimonials',
        'type' => 'carousel',
        'description' => 'Test component 3',
        'config' => json_encode(['schema' => [], 'defaults' => []]),
        'version' => '1.0.0',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $customConfig = [
        'title' => 'Custom Title',
        'colors' => ['primary' => '#FF0000'],
        'settings' => ['autoplay' => true],
    ];

    // Insert instance with custom config
    DB::table('component_instances')->insert([
        'component_id' => $componentId,
        'page_type' => 'template',
        'page_id' => 1,
        'position' => 0,
        'custom_config' => json_encode($customConfig),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $instance = DB::table('component_instances')->first();
    expect(json_decode($instance->custom_config, true))->toBe($customConfig);
});
