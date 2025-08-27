<?php

use App\Models\IntegrationConfiguration;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    // Create a tenant and set it as current
    $this->tenant = Tenant::factory()->create();
    tenancy()->initialize($this->tenant);

    // Create an admin user
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Institution Admin');
});

test('admin can view integrations index', function () {
    $this->actingAs($this->admin);

    $response = $this->get(route('admin.integrations.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Admin/Integrations/Index')
        ->has('integrations')
        ->has('integrationTypes')
    );
});

test('admin can create integration', function () {
    $this->actingAs($this->admin);

    $integrationData = [
        'name' => 'Test Mailchimp Integration',
        'type' => IntegrationConfiguration::TYPE_EMAIL_MARKETING,
        'provider' => 'mailchimp',
        'configuration' => [
            'server_prefix' => 'us1',
            'list_id' => 'abc123',
        ],
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
        'is_active' => true,
        'is_test_mode' => true,
    ];

    $response = $this->post(route('admin.integrations.store'), $integrationData);

    $response->assertRedirect();
    $this->assertDatabaseHas('integration_configurations', [
        'name' => 'Test Mailchimp Integration',
        'type' => IntegrationConfiguration::TYPE_EMAIL_MARKETING,
        'provider' => 'mailchimp',
        'institution_id' => $this->tenant->id,
        'created_by' => $this->admin->id,
    ]);
});

test('admin can view integration details', function () {
    $this->actingAs($this->admin);

    $integration = IntegrationConfiguration::factory()
        ->emailMarketing()
        ->create([
            'institution_id' => $this->tenant->id,
            'created_by' => $this->admin->id,
        ]);

    $response = $this->get(route('admin.integrations.show', $integration));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Admin/Integrations/Show')
        ->has('integration')
        ->where('integration.id', $integration->id)
    );
});

test('admin can test integration', function () {
    $this->actingAs($this->admin);

    $integration = IntegrationConfiguration::factory()
        ->emailMarketing()
        ->create([
            'institution_id' => $this->tenant->id,
            'provider' => 'internal', // Use internal provider for testing
            'created_by' => $this->admin->id,
        ]);

    $response = $this->post(route('admin.integrations.test', $integration));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
    ]);
});

test('integration validation works', function () {
    // Test email marketing validation
    $emailIntegration = IntegrationConfiguration::factory()
        ->emailMarketing()
        ->create([
            'provider' => 'mailchimp',
            'configuration' => [], // Missing required config
            'credentials' => [], // Missing API key
        ]);

    $errors = $emailIntegration->validateConfiguration();
    expect($errors)->not->toBeEmpty();
    expect($emailIntegration->isValid())->toBeFalse();

    // Test SSO validation
    $ssoIntegration = IntegrationConfiguration::factory()
        ->sso()
        ->create([
            'provider' => 'saml2',
            'configuration' => [], // Missing required config
        ]);

    $errors = $ssoIntegration->validateConfiguration();
    expect($errors)->not->toBeEmpty();
    expect($ssoIntegration->isValid())->toBeFalse();
});

test('non admin cannot access integrations', function () {
    $user = User::factory()->create();
    $user->assignRole('Graduate');

    $this->actingAs($user);

    $response = $this->get(route('admin.integrations.index'));
    $response->assertForbidden();
});

test('integration field mappings work', function () {
    $integration = IntegrationConfiguration::factory()
        ->crm()
        ->create();

    $integration->setFieldMapping('external_email', 'email');
    $integration->setFieldMapping('external_name', 'full_name');

    expect($integration->getFieldMapping('external_email'))->toBe('email');
    expect($integration->getFieldMapping('external_name'))->toBe('full_name');
    expect($integration->getFieldMapping('non_existent'))->toBeNull();
});

test('integration sync status tracking', function () {
    $integration = IntegrationConfiguration::factory()
        ->emailMarketing()
        ->create([
            'last_sync_at' => null,
            'sync_status' => null,
        ]);

    expect($integration->needsSync())->toBeTrue();

    $integration->markSyncSuccessful();
    expect($integration->sync_status)->toBe('success');
    expect($integration->last_sync_at)->not->toBeNull();
    expect($integration->sync_error)->toBeNull();

    $integration->markSyncFailed('Test error message');
    expect($integration->sync_status)->toBe('failed');
    expect($integration->sync_error)->toBe('Test error message');
});

test('webhook url generation', function () {
    $integration = IntegrationConfiguration::factory()
        ->emailMarketing()
        ->create([
            'webhook_settings' => [
                'enabled' => false,
            ],
        ]);

    expect($integration->getWebhookUrl())->toBeNull();

    $token = $integration->generateWebhookToken();
    expect($token)->not->toBeNull();
    expect($integration->getWebhookUrl())->not->toBeNull();
    expect($integration->getWebhookUrl())->toContain($token);
});
