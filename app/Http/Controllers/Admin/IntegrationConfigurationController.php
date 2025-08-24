<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationConfiguration;
use App\Services\IntegrationTestingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class IntegrationConfigurationController extends Controller
{
    public function __construct(
        protected IntegrationTestingService $testingService
    ) {}

    /**
     * Display a listing of integrations
     */
    public function index()
    {
        Gate::authorize('viewAny', IntegrationConfiguration::class);

        $integrations = IntegrationConfiguration::with(['creator', 'updater'])
            ->forInstitution(tenant()->id)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(function ($integration) {
                return [
                    'id' => $integration->id,
                    'name' => $integration->name,
                    'type' => $integration->type,
                    'provider' => $integration->provider,
                    'is_active' => $integration->is_active,
                    'is_test_mode' => $integration->is_test_mode,
                    'sync_status' => $integration->sync_status,
                    'last_sync_at' => $integration->last_sync_at?->format('M j, Y g:i A'),
                    'created_by' => $integration->creator->name,
                    'updated_at' => $integration->updated_at->format('M j, Y g:i A'),
                    'is_valid' => $integration->isValid(),
                    'validation_errors' => $integration->validateConfiguration(),
                ];
            });

        $integrationTypes = [
            'email_marketing' => [
                'label' => 'Email Marketing',
                'providers' => ['mailchimp', 'constant_contact', 'mautic', 'internal'],
                'icon' => 'mail',
            ],
            'calendar' => [
                'label' => 'Calendar',
                'providers' => ['google', 'outlook', 'apple', 'caldav'],
                'icon' => 'calendar',
            ],
            'sso' => [
                'label' => 'Single Sign-On',
                'providers' => ['saml2', 'oauth2', 'oidc', 'ldap'],
                'icon' => 'shield-check',
            ],
            'crm' => [
                'label' => 'CRM',
                'providers' => ['salesforce', 'hubspot', 'pipedrive', 'zoho'],
                'icon' => 'users',
            ],
            'payment' => [
                'label' => 'Payment',
                'providers' => ['stripe', 'paypal', 'square', 'braintree'],
                'icon' => 'credit-card',
            ],
            'analytics' => [
                'label' => 'Analytics',
                'providers' => ['google_analytics', 'mixpanel', 'amplitude'],
                'icon' => 'chart-bar',
            ],
        ];

        return Inertia::render('Admin/Integrations/Index', [
            'integrations' => $integrations,
            'integrationTypes' => $integrationTypes,
        ]);
    }

    /**
     * Show the form for creating a new integration
     */
    public function create(Request $request)
    {
        Gate::authorize('create', IntegrationConfiguration::class);

        $type = $request->get('type');
        $provider = $request->get('provider');

        return Inertia::render('Admin/Integrations/Create', [
            'type' => $type,
            'provider' => $provider,
            'integrationTypes' => $this->getIntegrationTypes(),
            'providerConfigs' => $this->getProviderConfigs(),
        ]);
    }

    /**
     * Store a newly created integration
     */
    public function store(Request $request)
    {
        Gate::authorize('create', IntegrationConfiguration::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:email_marketing,calendar,sso,crm,payment,analytics,webhook',
            'provider' => 'required|string|max:100',
            'configuration' => 'nullable|array',
            'credentials' => 'nullable|array',
            'field_mappings' => 'nullable|array',
            'webhook_settings' => 'nullable|array',
            'sync_settings' => 'nullable|array',
            'is_active' => 'boolean',
            'is_test_mode' => 'boolean',
        ]);

        $integration = IntegrationConfiguration::create([
            ...$validated,
            'institution_id' => tenant()->id,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.integrations.show', $integration)
            ->with('success', 'Integration configuration created successfully.');
    }

    /**
     * Display the specified integration
     */
    public function show(IntegrationConfiguration $integration)
    {
        Gate::authorize('view', $integration);

        $integration->load(['creator', 'updater']);

        return Inertia::render('Admin/Integrations/Show', [
            'integration' => [
                'id' => $integration->id,
                'name' => $integration->name,
                'type' => $integration->type,
                'provider' => $integration->provider,
                'configuration' => $integration->configuration,
                'field_mappings' => $integration->field_mappings,
                'webhook_settings' => $integration->webhook_settings,
                'sync_settings' => $integration->sync_settings,
                'is_active' => $integration->is_active,
                'is_test_mode' => $integration->is_test_mode,
                'sync_status' => $integration->sync_status,
                'sync_error' => $integration->sync_error,
                'last_sync_at' => $integration->last_sync_at?->format('M j, Y g:i A'),
                'created_by' => $integration->creator->name,
                'updated_by' => $integration->updater?->name,
                'created_at' => $integration->created_at->format('M j, Y g:i A'),
                'updated_at' => $integration->updated_at->format('M j, Y g:i A'),
                'webhook_url' => $integration->getWebhookUrl(),
                'validation_errors' => $integration->validateConfiguration(),
                'is_valid' => $integration->isValid(),
            ],
            'providerConfig' => $this->getProviderConfig($integration->type, $integration->provider),
        ]);
    }

    /**
     * Show the form for editing the integration
     */
    public function edit(IntegrationConfiguration $integration)
    {
        Gate::authorize('update', $integration);

        return Inertia::render('Admin/Integrations/Edit', [
            'integration' => [
                'id' => $integration->id,
                'name' => $integration->name,
                'type' => $integration->type,
                'provider' => $integration->provider,
                'configuration' => $integration->configuration,
                'field_mappings' => $integration->field_mappings,
                'webhook_settings' => $integration->webhook_settings,
                'sync_settings' => $integration->sync_settings,
                'is_active' => $integration->is_active,
                'is_test_mode' => $integration->is_test_mode,
            ],
            'providerConfig' => $this->getProviderConfig($integration->type, $integration->provider),
            'integrationTypes' => $this->getIntegrationTypes(),
        ]);
    }

    /**
     * Update the integration
     */
    public function update(Request $request, IntegrationConfiguration $integration)
    {
        Gate::authorize('update', $integration);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'configuration' => 'nullable|array',
            'credentials' => 'nullable|array',
            'field_mappings' => 'nullable|array',
            'webhook_settings' => 'nullable|array',
            'sync_settings' => 'nullable|array',
            'is_active' => 'boolean',
            'is_test_mode' => 'boolean',
        ]);

        $integration->update([
            ...$validated,
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.integrations.show', $integration)
            ->with('success', 'Integration configuration updated successfully.');
    }

    /**
     * Remove the integration
     */
    public function destroy(IntegrationConfiguration $integration)
    {
        Gate::authorize('delete', $integration);

        $integration->delete();

        return redirect()
            ->route('admin.integrations.index')
            ->with('success', 'Integration configuration deleted successfully.');
    }

    /**
     * Test integration connection
     */
    public function test(IntegrationConfiguration $integration)
    {
        Gate::authorize('update', $integration);

        $result = $this->testingService->testIntegration($integration);

        return response()->json($result);
    }

    /**
     * Sync integration data
     */
    public function sync(IntegrationConfiguration $integration)
    {
        Gate::authorize('update', $integration);

        $result = $this->testingService->syncIntegration($integration);

        return response()->json($result);
    }

    /**
     * Generate webhook token
     */
    public function generateWebhookToken(IntegrationConfiguration $integration)
    {
        Gate::authorize('update', $integration);

        $token = $integration->generateWebhookToken();

        return response()->json([
            'success' => true,
            'token' => $token,
            'webhook_url' => $integration->getWebhookUrl(),
        ]);
    }

    /**
     * Get integration types configuration
     */
    protected function getIntegrationTypes(): array
    {
        return [
            'email_marketing' => [
                'label' => 'Email Marketing',
                'description' => 'Connect with email marketing platforms to manage campaigns and lists',
                'providers' => [
                    'mailchimp' => 'Mailchimp',
                    'constant_contact' => 'Constant Contact',
                    'mautic' => 'Mautic',
                    'internal' => 'Internal System',
                ],
                'icon' => 'mail',
            ],
            'calendar' => [
                'label' => 'Calendar Integration',
                'description' => 'Sync events and schedules with external calendar systems',
                'providers' => [
                    'google' => 'Google Calendar',
                    'outlook' => 'Microsoft Outlook',
                    'apple' => 'Apple Calendar',
                    'caldav' => 'CalDAV',
                ],
                'icon' => 'calendar',
            ],
            'sso' => [
                'label' => 'Single Sign-On',
                'description' => 'Enable SSO authentication for your institution',
                'providers' => [
                    'saml2' => 'SAML 2.0',
                    'oauth2' => 'OAuth 2.0',
                    'oidc' => 'OpenID Connect',
                    'ldap' => 'LDAP',
                ],
                'icon' => 'shield-check',
            ],
            'crm' => [
                'label' => 'CRM Integration',
                'description' => 'Connect with CRM systems for contact and relationship management',
                'providers' => [
                    'salesforce' => 'Salesforce',
                    'hubspot' => 'HubSpot',
                    'pipedrive' => 'Pipedrive',
                    'zoho' => 'Zoho CRM',
                ],
                'icon' => 'users',
            ],
            'payment' => [
                'label' => 'Payment Processing',
                'description' => 'Process donations and event payments',
                'providers' => [
                    'stripe' => 'Stripe',
                    'paypal' => 'PayPal',
                    'square' => 'Square',
                    'braintree' => 'Braintree',
                ],
                'icon' => 'credit-card',
            ],
            'analytics' => [
                'label' => 'Analytics',
                'description' => 'Track user behavior and engagement metrics',
                'providers' => [
                    'google_analytics' => 'Google Analytics',
                    'mixpanel' => 'Mixpanel',
                    'amplitude' => 'Amplitude',
                ],
                'icon' => 'chart-bar',
            ],
        ];
    }

    /**
     * Get provider-specific configuration templates
     */
    protected function getProviderConfigs(): array
    {
        return [
            'email_marketing' => [
                'mailchimp' => [
                    'fields' => [
                        'api_key' => ['type' => 'password', 'label' => 'API Key', 'required' => true],
                        'server_prefix' => ['type' => 'text', 'label' => 'Server Prefix', 'required' => true],
                        'list_id' => ['type' => 'text', 'label' => 'Default List ID', 'required' => false],
                    ],
                    'webhooks' => true,
                    'sync_options' => ['contacts', 'campaigns', 'lists'],
                ],
                'constant_contact' => [
                    'fields' => [
                        'api_key' => ['type' => 'password', 'label' => 'API Key', 'required' => true],
                        'access_token' => ['type' => 'password', 'label' => 'Access Token', 'required' => true],
                        'list_id' => ['type' => 'text', 'label' => 'Default List ID', 'required' => false],
                    ],
                    'webhooks' => true,
                    'sync_options' => ['contacts', 'campaigns'],
                ],
            ],
            'calendar' => [
                'google' => [
                    'fields' => [
                        'client_id' => ['type' => 'text', 'label' => 'Client ID', 'required' => true],
                        'client_secret' => ['type' => 'password', 'label' => 'Client Secret', 'required' => true],
                        'calendar_id' => ['type' => 'text', 'label' => 'Calendar ID', 'required' => false],
                    ],
                    'oauth' => true,
                    'sync_options' => ['events', 'availability'],
                ],
                'outlook' => [
                    'fields' => [
                        'client_id' => ['type' => 'text', 'label' => 'Application ID', 'required' => true],
                        'client_secret' => ['type' => 'password', 'label' => 'Client Secret', 'required' => true],
                        'tenant_id' => ['type' => 'text', 'label' => 'Tenant ID', 'required' => true],
                    ],
                    'oauth' => true,
                    'sync_options' => ['events', 'availability'],
                ],
            ],
            'sso' => [
                'saml2' => [
                    'fields' => [
                        'entity_id' => ['type' => 'text', 'label' => 'Entity ID', 'required' => true],
                        'sso_url' => ['type' => 'url', 'label' => 'SSO URL', 'required' => true],
                        'sls_url' => ['type' => 'url', 'label' => 'SLS URL', 'required' => false],
                        'certificate' => ['type' => 'textarea', 'label' => 'X.509 Certificate', 'required' => true],
                    ],
                    'attribute_mapping' => true,
                    'role_mapping' => true,
                ],
                'oidc' => [
                    'fields' => [
                        'client_id' => ['type' => 'text', 'label' => 'Client ID', 'required' => true],
                        'client_secret' => ['type' => 'password', 'label' => 'Client Secret', 'required' => true],
                        'discovery_url' => ['type' => 'url', 'label' => 'Discovery URL', 'required' => true],
                    ],
                    'attribute_mapping' => true,
                    'role_mapping' => true,
                ],
            ],
            'crm' => [
                'salesforce' => [
                    'fields' => [
                        'instance_url' => ['type' => 'url', 'label' => 'Instance URL', 'required' => true],
                        'client_id' => ['type' => 'text', 'label' => 'Consumer Key', 'required' => true],
                        'client_secret' => ['type' => 'password', 'label' => 'Consumer Secret', 'required' => true],
                        'username' => ['type' => 'text', 'label' => 'Username', 'required' => true],
                        'password' => ['type' => 'password', 'label' => 'Password + Security Token', 'required' => true],
                    ],
                    'field_mapping' => true,
                    'sync_options' => ['contacts', 'accounts', 'opportunities'],
                ],
                'hubspot' => [
                    'fields' => [
                        'api_key' => ['type' => 'password', 'label' => 'API Key', 'required' => true],
                        'portal_id' => ['type' => 'text', 'label' => 'Portal ID', 'required' => true],
                    ],
                    'field_mapping' => true,
                    'sync_options' => ['contacts', 'companies', 'deals'],
                ],
            ],
        ];
    }

    /**
     * Get specific provider configuration
     */
    protected function getProviderConfig(string $type, string $provider): ?array
    {
        $configs = $this->getProviderConfigs();
        return $configs[$type][$provider] ?? null;
    }
}
