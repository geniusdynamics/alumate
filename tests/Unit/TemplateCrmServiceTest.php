<?php

use App\Models\Template;
use App\Models\TemplateCrmIntegration;
use App\Models\TemplateCrmSyncLog;
use App\Models\Tenant;
use App\Services\TemplateCrmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(TemplateCrmService::class);
    $this->tenant = Tenant::factory()->create();
});

test('can create CRM integration', function () {
    $data = [
        'tenant_id' => $this->tenant->id,
        'name' => 'Test HubSpot Integration',
        'provider' => 'hubspot',
        'config' => [
            'access_token' => 'test_token',
            'api_key' => 'test_key'
        ],
        'is_active' => true,
        'sync_direction' => 'one_way',
        'sync_interval' => 3600,
        'field_mappings' => [
            'name' => 'dealname',
            'description' => 'dealstage'
        ]
    ];

    $integration = $this->service->createCrmIntegration($data);

    expect($integration)->toBeInstanceOf(TemplateCrmIntegration::class);
    expect($integration->name)->toBe('Test HubSpot Integration');
    expect($integration->provider)->toBe('hubspot');
    expect($integration->is_active)->toBeTrue();
});

test('can get tenant CRM integrations', function () {
    TemplateCrmIntegration::factory()->count(3)->create([
        'tenant_id' => $this->tenant->id
    ]);

    $integrations = $this->service->getTenantCrmIntegrations($this->tenant->id);

    expect($integrations)->toHaveCount(3);
});

test('can update CRM integration', function () {
    $integration = TemplateCrmIntegration::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Original Name'
    ]);

    $updatedData = [
        'name' => 'Updated Name',
        'is_active' => false
    ];

    $updatedIntegration = $this->service->updateCrmIntegration($integration->id, $updatedData);

    expect($updatedIntegration->name)->toBe('Updated Name');
    expect($updatedIntegration->is_active)->toBeFalse();
});

test('can delete CRM integration', function () {
    $integration = TemplateCrmIntegration::factory()->create([
        'tenant_id' => $this->tenant->id
    ]);

    $this->service->deleteCrmIntegration($integration->id);

    expect(TemplateCrmIntegration::find($integration->id))->toBeNull();
});

test('can sync templates to CRM', function () {
    $integration = TemplateCrmIntegration::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_active' => true
    ]);

    $templates = Template::factory()->count(2)->create([
        'tenant_id' => $this->tenant->id
    ]);

    // Mock the sync method
    $integration->shouldReceive('syncTemplate')->andReturn([
        'success' => true,
        'message' => 'Template synced successfully'
    ]);

    $result = $this->service->syncTemplatesToCrm($templates->pluck('id')->toArray());

    expect($result['total_processed'])->toBe(2);
    expect($result['successful'])->toBe(2);
    expect($result['failed'])->toBe(0);
});

test('can sync templates by filters', function () {
    TemplateCrmIntegration::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_active' => true
    ]);

    Template::factory()->count(3)->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'landing'
    ]);

    $filters = ['category' => 'landing'];

    $result = $this->service->syncTemplatesByFilters($filters);

    expect($result['total_processed'])->toBe(3);
});

test('can get sync logs', function () {
    TemplateCrmSyncLog::factory()->count(5)->create([
        'tenant_id' => $this->tenant->id
    ]);

    $logs = $this->service->getSyncLogs($this->tenant->id);

    expect($logs)->toHaveCount(5);
});

test('can get sync statistics', function () {
    TemplateCrmSyncLog::factory()->count(10)->create([
        'tenant_id' => $this->tenant->id,
        'status' => 'success'
    ]);

    TemplateCrmSyncLog::factory()->count(5)->create([
        'tenant_id' => $this->tenant->id,
        'status' => 'failed'
    ]);

    $stats = $this->service->getSyncStatistics($this->tenant->id);

    expect($stats['total_syncs'])->toBe(15);
    expect($stats['successful_syncs'])->toBe(10);
    expect($stats['failed_syncs'])->toBe(5);
    expect($stats['success_rate'])->toBe(66.67);
});

test('can validate field mappings', function () {
    $integration = TemplateCrmIntegration::factory()->create([
        'tenant_id' => $this->tenant->id,
        'field_mappings' => [
            'name' => 'dealname',
            'invalid_field' => 'nonexistent_crm_field'
        ]
    ]);

    $result = $this->service->validateFieldMappings($integration->id, $integration->field_mappings);

    expect($result['valid'])->toBeFalse();
    expect($result['errors'])->toBeArray();
});

test('handles CRM webhook processing', function () {
    $integration = TemplateCrmIntegration::factory()->create([
        'tenant_id' => $this->tenant->id,
        'provider' => 'hubspot',
        'is_active' => true
    ]);

    $payload = [
        'event' => 'contact.created',
        'data' => ['id' => '123']
    ];

    $result = $this->service->processCrmWebhook('hubspot', $payload);

    expect($result['success'])->toBeTrue();
    expect($result['results'])->toBeArray();
});

test('handles inactive integrations gracefully', function () {
    $integration = TemplateCrmIntegration::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_active' => false
    ]);

    $templates = Template::factory()->count(1)->create([
        'tenant_id' => $this->tenant->id
    ]);

    $result = $this->service->syncTemplatesToCrm($templates->pluck('id')->toArray());

    expect($result['total_processed'])->toBe(0);
    expect($result['message'])->toBe('No active CRM integrations found');
});

test('handles empty template list', function () {
    TemplateCrmIntegration::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_active' => true
    ]);

    $result = $this->service->syncTemplatesToCrm([]);

    expect($result['total_processed'])->toBe(0);
});

test('caches tenant integrations', function () {
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn(collect([]));

    $this->service->getTenantCrmIntegrations($this->tenant->id);
});

test('clears cache on integration changes', function () {
    $integration = TemplateCrmIntegration::factory()->create([
        'tenant_id' => $this->tenant->id
    ]);

    Cache::shouldReceive('forget')
        ->with("template_crm_tenant_{$this->tenant->id}_integrations")
        ->once();

    $this->service->updateCrmIntegration($integration->id, ['name' => 'Updated']);
});
