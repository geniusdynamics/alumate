<?php

namespace Database\Factories;

use App\Models\IntegrationConfiguration;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IntegrationConfiguration>
 */
class IntegrationConfigurationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement([
            IntegrationConfiguration::TYPE_EMAIL_MARKETING,
            IntegrationConfiguration::TYPE_CALENDAR,
            IntegrationConfiguration::TYPE_SSO,
            IntegrationConfiguration::TYPE_CRM,
        ]);

        $providers = [
            IntegrationConfiguration::TYPE_EMAIL_MARKETING => ['mailchimp', 'constant_contact', 'internal'],
            IntegrationConfiguration::TYPE_CALENDAR => ['google', 'outlook', 'apple'],
            IntegrationConfiguration::TYPE_SSO => ['saml2', 'oidc', 'oauth2'],
            IntegrationConfiguration::TYPE_CRM => ['salesforce', 'hubspot', 'pipedrive'],
        ];

        $provider = $this->faker->randomElement($providers[$type]);

        return [
            'institution_id' => Tenant::factory(),
            'name' => $this->faker->company.' '.ucfirst($type),
            'type' => $type,
            'provider' => $provider,
            'configuration' => $this->getConfigurationForProvider($type, $provider),
            'credentials' => $this->getCredentialsForProvider($type, $provider),
            'field_mappings' => $this->getFieldMappings($type),
            'webhook_settings' => [
                'enabled' => $this->faker->boolean(30),
                'token' => $this->faker->sha256,
            ],
            'sync_settings' => [
                'interval_hours' => $this->faker->randomElement([1, 6, 12, 24]),
                'auto_sync' => $this->faker->boolean(70),
            ],
            'is_active' => $this->faker->boolean(80),
            'is_test_mode' => $this->faker->boolean(40),
            'last_sync_at' => $this->faker->optional(0.6)->dateTimeBetween('-1 week', 'now'),
            'sync_status' => $this->faker->randomElement(['success', 'failed', 'pending', null]),
            'sync_error' => $this->faker->optional(0.2)->sentence,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    /**
     * Get configuration for specific provider
     */
    protected function getConfigurationForProvider(string $type, string $provider): array
    {
        return match ($type) {
            IntegrationConfiguration::TYPE_EMAIL_MARKETING => match ($provider) {
                'mailchimp' => [
                    'server_prefix' => $this->faker->word,
                    'list_id' => $this->faker->uuid,
                ],
                'constant_contact' => [
                    'list_id' => $this->faker->uuid,
                ],
                default => [],
            },
            IntegrationConfiguration::TYPE_CALENDAR => match ($provider) {
                'google' => [
                    'calendar_id' => 'primary',
                ],
                'outlook' => [
                    'tenant_id' => $this->faker->uuid,
                ],
                default => [],
            },
            IntegrationConfiguration::TYPE_SSO => match ($provider) {
                'saml2' => [
                    'entity_id' => $this->faker->url,
                    'sso_url' => $this->faker->url,
                    'certificate' => '-----BEGIN CERTIFICATE-----'.$this->faker->sha256.'-----END CERTIFICATE-----',
                ],
                'oidc' => [
                    'discovery_url' => $this->faker->url.'/.well-known/openid_configuration',
                ],
                default => [],
            },
            IntegrationConfiguration::TYPE_CRM => match ($provider) {
                'salesforce' => [
                    'instance_url' => 'https://'.$this->faker->word.'.salesforce.com',
                ],
                'hubspot' => [
                    'portal_id' => $this->faker->randomNumber(8),
                ],
                default => [],
            },
            default => [],
        };
    }

    /**
     * Get credentials for specific provider
     */
    protected function getCredentialsForProvider(string $type, string $provider): array
    {
        return match ($type) {
            IntegrationConfiguration::TYPE_EMAIL_MARKETING => [
                'api_key' => $this->faker->sha256,
            ],
            IntegrationConfiguration::TYPE_CALENDAR => [
                'client_id' => $this->faker->uuid,
                'client_secret' => $this->faker->sha256,
            ],
            IntegrationConfiguration::TYPE_SSO => [
                'client_id' => $this->faker->uuid,
                'client_secret' => $this->faker->sha256,
            ],
            IntegrationConfiguration::TYPE_CRM => [
                'api_key' => $this->faker->sha256,
            ],
            default => [],
        };
    }

    /**
     * Get field mappings for type
     */
    protected function getFieldMappings(string $type): array
    {
        return match ($type) {
            IntegrationConfiguration::TYPE_EMAIL_MARKETING => [
                'email' => 'email',
                'first_name' => 'name',
                'last_name' => 'last_name',
            ],
            IntegrationConfiguration::TYPE_CRM => [
                'email' => 'email',
                'name' => 'full_name',
                'company' => 'current_company',
            ],
            default => [],
        };
    }

    /**
     * Create an email marketing integration
     */
    public function emailMarketing(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => IntegrationConfiguration::TYPE_EMAIL_MARKETING,
            'provider' => $this->faker->randomElement(['mailchimp', 'constant_contact', 'internal']),
        ]);
    }

    /**
     * Create a calendar integration
     */
    public function calendar(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => IntegrationConfiguration::TYPE_CALENDAR,
            'provider' => $this->faker->randomElement(['google', 'outlook', 'apple']),
        ]);
    }

    /**
     * Create an SSO integration
     */
    public function sso(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => IntegrationConfiguration::TYPE_SSO,
            'provider' => $this->faker->randomElement(['saml2', 'oidc', 'oauth2']),
        ]);
    }

    /**
     * Create a CRM integration
     */
    public function crm(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => IntegrationConfiguration::TYPE_CRM,
            'provider' => $this->faker->randomElement(['salesforce', 'hubspot', 'pipedrive']),
        ]);
    }

    /**
     * Create an active integration
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive integration
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a test mode integration
     */
    public function testMode(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_test_mode' => true,
        ]);
    }
}
