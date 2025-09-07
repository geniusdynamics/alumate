<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('component_themes table has correct structure', function () {
    expect(Schema::hasTable('component_themes'))->toBeTrue();

    expect(Schema::hasColumns('component_themes', [
        'id',
        'tenant_id',
        'name',
        'slug',
        'config',
        'is_default',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

test('component_themes table has correct indexes', function () {
    $indexes = collect(Schema::getIndexes('component_themes'));

    // Check for unique constraint on tenant_id and slug
    expect($indexes->where('name', 'component_themes_tenant_slug_unique')->first()['unique'])->toBeTrue();

    // Check for tenant_id index
    expect($indexes->where('name', 'component_themes_tenant_id_index')->count())->toBeGreaterThan(0);

    // Check for tenant_id and is_default composite index
    expect($indexes->where('name', 'component_themes_tenant_default_index')->count())->toBeGreaterThan(0);
});

test('component_themes table has foreign key constraint', function () {
    $foreignKeys = Schema::getForeignKeys('component_themes');

    expect($foreignKeys)->toHaveCount(1);
    expect($foreignKeys[0]['foreign_table'])->toBe('tenants');
    expect($foreignKeys[0]['columns'])->toBe(['tenant_id']);
    expect($foreignKeys[0]['foreign_columns'])->toBe(['id']);
});
