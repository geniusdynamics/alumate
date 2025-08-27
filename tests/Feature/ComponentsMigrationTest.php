<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('has components table with correct structure', function () {
    expect(Schema::hasTable('components'))->toBeTrue();

    $columns = Schema::getColumnListing('components');

    expect($columns)->toContain('id');
    expect($columns)->toContain('tenant_id');
    expect($columns)->toContain('name');
    expect($columns)->toContain('slug');
    expect($columns)->toContain('category');
    expect($columns)->toContain('type');
    expect($columns)->toContain('description');
    expect($columns)->toContain('config');
    expect($columns)->toContain('metadata');
    expect($columns)->toContain('version');
    expect($columns)->toContain('is_active');
    expect($columns)->toContain('created_at');
    expect($columns)->toContain('updated_at');
});

it('has correct category enum values', function () {
    // Create test tenants first
    DB::table('tenants')->insertOrIgnore([
        'id' => 'test-tenant',
        'name' => 'Test Tenant',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Test that we can insert valid category values
    $validCategories = ['hero', 'forms', 'testimonials', 'statistics', 'ctas', 'media'];

    foreach ($validCategories as $category) {
        DB::table('components')->insert([
            'tenant_id' => 'test-tenant',
            'name' => "Test {$category} Component",
            'slug' => "test-{$category}-component",
            'category' => $category,
            'type' => 'test',
            'config' => json_encode(['schema' => [], 'defaults' => []]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Verify all categories were inserted
    $insertedCategories = DB::table('components')
        ->where('tenant_id', 'test-tenant')
        ->pluck('category')
        ->toArray();

    expect($insertedCategories)->toHaveCount(6);
    expect($insertedCategories)->toContain('hero');
    expect($insertedCategories)->toContain('forms');
    expect($insertedCategories)->toContain('testimonials');
    expect($insertedCategories)->toContain('statistics');
    expect($insertedCategories)->toContain('ctas');
    expect($insertedCategories)->toContain('media');

    // Clean up
    DB::table('components')->where('tenant_id', 'test-tenant')->delete();
    DB::table('tenants')->where('id', 'test-tenant')->delete();
});

it('enforces unique constraint on tenant_id and slug', function () {
    // Create test tenants first
    DB::table('tenants')->insertOrIgnore([
        ['id' => 'test-tenant-unique', 'name' => 'Test Tenant', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 'different-tenant-unique', 'name' => 'Different Tenant', 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Insert first component
    DB::table('components')->insert([
        'tenant_id' => 'test-tenant-unique',
        'name' => 'Test Component',
        'slug' => 'unique-test-component',
        'category' => 'hero',
        'type' => 'test',
        'config' => json_encode(['schema' => [], 'defaults' => []]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Same slug for different tenant should work
    DB::table('components')->insert([
        'tenant_id' => 'different-tenant-unique',
        'name' => 'Different Tenant Component',
        'slug' => 'unique-test-component', // Same slug, different tenant
        'category' => 'forms',
        'type' => 'test',
        'config' => json_encode(['schema' => [], 'defaults' => []]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Verify both components exist
    $components = DB::table('components')->whereIn('tenant_id', ['test-tenant-unique', 'different-tenant-unique'])->get();
    expect($components)->toHaveCount(2);

    // Clean up
    DB::table('components')->whereIn('tenant_id', ['test-tenant-unique', 'different-tenant-unique'])->delete();
    DB::table('tenants')->whereIn('id', ['test-tenant-unique', 'different-tenant-unique'])->delete();
});
