<?php

use App\Models\SsoConfiguration;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SSOIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ssoService = app(SSOIntegrationService::class);
});

test('can create sso configuration', function () {
    $tenant = Tenant::create([
        'id' => 'test-tenant',
        'name' => 'Test University',
        'address' => '123 Test St',
        'contact_information' => 'test@example.com',
        'plan' => 'basic',
    ]);

    $config = SsoConfiguration::factory()->create([
        'name' => 'Test University SAML',
        'provider' => 'saml',
        'protocol' => 'saml2',
        'institution_id' => $tenant->id,
        'is_active' => true,
    ]);

    expect($config)->toBeInstanceOf(SsoConfiguration::class);
    expect($config->name)->toBe('Test University SAML');
    expect($config->provider)->toBe('saml');
    expect($config->protocol)->toBe('saml2');
    expect($config->is_active)->toBeTrue();
});

test('can get available configurations for institution', function () {
    $tenant = Tenant::create([
        'id' => 'test-tenant-2',
        'name' => 'Test University 2',
        'address' => '123 Test St',
        'contact_information' => 'test@example.com',
        'plan' => 'basic',
    ]);

    // Create active configuration for institution
    SsoConfiguration::factory()->active()->create([
        'institution_id' => $tenant->id,
        'name' => 'Institution SSO',
    ]);

    // Create inactive configuration (should not be returned)
    SsoConfiguration::factory()->inactive()->create([
        'institution_id' => $tenant->id,
        'name' => 'Inactive SSO',
    ]);

    // Create global configuration
    SsoConfiguration::factory()->active()->global()->create([
        'name' => 'Global SSO',
    ]);

    $configurations = $this->ssoService->getAvailableConfigurations($tenant->id);

    expect($configurations)->toHaveCount(2);
    expect($configurations->contains('name', 'Institution SSO'))->toBeTrue();
    expect($configurations->contains('name', 'Global SSO'))->toBeTrue();
    expect($configurations->contains('name', 'Inactive SSO'))->toBeFalse();
});

test('can authenticate user with sso data', function () {
    $tenant = Tenant::create([
        'id' => 'test-tenant-3',
        'name' => 'Test University 3',
        'address' => '123 Test St',
        'contact_information' => 'test@example.com',
        'plan' => 'basic',
    ]);
    $config = SsoConfiguration::factory()->active()->create([
        'institution_id' => $tenant->id,
        'auto_provision' => true,
        'auto_update' => true,
    ]);

    $userData = [
        'email' => 'test@example.com',
        'name' => 'Test User',
        'first_name' => 'Test',
        'last_name' => 'User',
        'roles' => ['alumni'],
    ];

    $user = $this->ssoService->authenticate($config, $userData);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->email)->toBe('test@example.com');
    expect($user->name)->toBe('Test User');
    expect($user->institution_id)->toBe($tenant->id);
});

test('can find existing user for sso authentication', function () {
    $tenant = Tenant::create([
        'id' => 'test-tenant-4',
        'name' => 'Test University 4',
        'address' => '123 Test St',
        'contact_information' => 'test@example.com',
        'plan' => 'basic',
    ]);
    $config = SsoConfiguration::factory()->active()->create([
        'institution_id' => $tenant->id,
        'auto_provision' => false,
        'auto_update' => true,
    ]);

    // Create existing user
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'name' => 'Existing User',
        'institution_id' => $tenant->id,
    ]);

    $userData = [
        'email' => 'existing@example.com',
        'name' => 'Updated Name',
        'roles' => ['student'],
    ];

    $user = $this->ssoService->authenticate($config, $userData);

    expect($user->id)->toBe($existingUser->id);
    expect($user->fresh()->name)->toBe('Updated Name'); // Should be updated
});

test('sso configuration validation', function () {
    // Test SAML configuration validation
    $samlConfig = SsoConfiguration::factory()->saml()->make([
        'entity_id' => null, // Missing required field
    ]);

    $errors = $samlConfig->validateConfiguration();
    expect($errors)->toContain('Entity ID is required for SAML configuration');

    // Test OAuth configuration validation
    $oauthConfig = SsoConfiguration::factory()->oauth2()->make([
        'client_id' => null, // Missing required field
    ]);

    $errors = $oauthConfig->validateConfiguration();
    expect($errors)->toContain('Client ID is required for OAuth configuration');
});

test('sso configuration helper methods', function () {
    $samlConfig = SsoConfiguration::factory()->saml()->create();
    $oauthConfig = SsoConfiguration::factory()->oauth2()->create();

    expect($samlConfig->isSaml())->toBeTrue();
    expect($samlConfig->isOAuth())->toBeFalse();

    expect($oauthConfig->isOAuth())->toBeTrue();
    expect($oauthConfig->isSaml())->toBeFalse();
});

test('can get sso login url', function () {
    $config = SsoConfiguration::factory()->active()->create();

    $loginUrl = $this->ssoService->getLoginUrl($config);

    expect($loginUrl)->toContain('/auth/sso/redirect');
    expect($loginUrl)->toContain('config='.$config->id);
});

test('sso configuration scopes', function () {
    $activeConfig = SsoConfiguration::factory()->active()->create();
    $inactiveConfig = SsoConfiguration::factory()->inactive()->create();
    $tenant = Tenant::create([
        'id' => 'test-tenant-5',
        'name' => 'Test University 5',
        'address' => '123 Test St',
        'contact_information' => 'test@example.com',
        'plan' => 'basic',
    ]);
    $tenantConfig = SsoConfiguration::factory()->create(['institution_id' => $tenant->id]);

    // Test active scope
    $activeConfigs = SsoConfiguration::active()->get();
    expect($activeConfigs->contains($activeConfig))->toBeTrue();
    expect($activeConfigs->contains($inactiveConfig))->toBeFalse();

    // Test institution scope
    $tenantConfigs = SsoConfiguration::forInstitution($tenant->id)->get();
    expect($tenantConfigs->contains($tenantConfig))->toBeTrue();
});

test('attribute mapping functionality', function () {
    $config = SsoConfiguration::factory()->create([
        'attribute_mapping' => [
            'name' => 'displayName',
            'email' => 'mail',
            'phone' => 'telephoneNumber',
        ],
    ]);

    expect($config->getAttributeMapping('name'))->toBe('displayName');
    expect($config->getAttributeMapping('email'))->toBe('mail');
    expect($config->getAttributeMapping('nonexistent'))->toBeNull();
});

test('role mapping functionality', function () {
    $config = SsoConfiguration::factory()->create([
        'role_mapping' => [
            'admin' => 'Super Admin',
            'student' => 'Student',
            'alumni' => 'Graduate',
        ],
    ]);

    expect($config->getRoleMapping('admin'))->toBe('Super Admin');
    expect($config->getRoleMapping('student'))->toBe('Student');
    expect($config->getRoleMapping('nonexistent'))->toBeNull();
});

test('sso configuration factory states', function () {
    $samlConfig = SsoConfiguration::factory()->saml()->create();
    expect($samlConfig->protocol)->toBe('saml2');
    expect($samlConfig->entity_id)->not->toBeNull();
    expect($samlConfig->certificate)->not->toBeNull();

    $oauthConfig = SsoConfiguration::factory()->oauth2()->create();
    expect($oauthConfig->protocol)->toBe('oauth2');
    expect($oauthConfig->client_id)->not->toBeNull();
    expect($oauthConfig->client_secret)->not->toBeNull();

    $oidcConfig = SsoConfiguration::factory()->oidc()->create();
    expect($oidcConfig->protocol)->toBe('oidc');
    expect($oidcConfig->discovery_url)->not->toBeNull();
});

test('sso configuration belongs to tenant', function () {
    $tenant = Tenant::create([
        'id' => 'test-tenant-6',
        'name' => 'Test University 6',
        'address' => '123 Test St',
        'contact_information' => 'test@example.com',
        'plan' => 'basic',
    ]);
    $config = SsoConfiguration::factory()->create(['institution_id' => $tenant->id]);

    expect($config->institution)->toBeInstanceOf(Tenant::class);
    expect($config->institution->id)->toBe($tenant->id);
});
