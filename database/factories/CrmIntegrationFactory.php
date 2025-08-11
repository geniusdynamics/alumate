<?php

namespace Database\Factories;

use App\Models\CrmIntegration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CrmIntegration>
 */
class CrmIntegrationFactory extends Factory
{
    protected $model = CrmIntegration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $providers = ['salesforce', 'hubspot', 'pipedrive', 'zoho', 'twenty', 'frappe'];
        $provider = $this->faker->randomElement($providers);

        return [
            'name' => $this->faker->company().' '.ucfirst($provider).' Integration',
            'provider' => $provider,
            'config' => $this->generateConfig($provider),
            'is_active' => $this->faker->boolean(80),
            'sync_direction' => $this->faker->randomElement(['push', 'pull', 'bidirectional']),
            'sync_interval' => $this->faker->randomElement([300, 900, 1800, 3600]), // 5min, 15min, 30min, 1hr
            'last_sync_at' => $this->faker->optional(0.6)->dateTimeBetween('-7 days', 'now'),
            'last_sync_result' => $this->faker->optional(0.6)->randomElement([
                ['success' => true, 'synced_count' => $this->faker->numberBetween(1, 50)],
                ['success' => false, 'error' => 'Connection timeout'],
            ]),
            'field_mappings' => $this->generateFieldMappings($provider),
        ];
    }

    /**
     * Generate provider-specific configuration
     */
    private function generateConfig(string $provider): array
    {
        switch ($provider) {
            case 'salesforce':
                return [
                    'client_id' => $this->faker->uuid(),
                    'client_secret' => $this->faker->sha256(),
                    'instance_url' => 'https://'.$this->faker->domainWord().'.salesforce.com',
                ];

            case 'hubspot':
                return [
                    'access_token' => $this->faker->sha256(),
                    'refresh_token' => $this->faker->sha256(),
                    'portal_id' => $this->faker->numberBetween(1000000, 9999999),
                ];

            case 'zoho':
                return [
                    'client_id' => $this->faker->uuid(),
                    'client_secret' => $this->faker->sha256(),
                    'refresh_token' => $this->faker->sha256(),
                    'api_domain' => 'https://www.zohoapis.com',
                ];

            case 'twenty':
                return [
                    'api_key' => $this->faker->sha256(),
                    'instance_url' => 'https://'.$this->faker->domainWord().'.twenty.com',
                ];

            case 'frappe':
                return [
                    'api_key' => $this->faker->sha256(),
                    'api_secret' => $this->faker->sha256(),
                    'instance_url' => 'https://'.$this->faker->domainWord().'.frappe.cloud',
                ];

            default:
                return [
                    'api_url' => 'https://api.'.$this->faker->domainName(),
                    'api_key' => $this->faker->sha256(),
                ];
        }
    }

    /**
     * Generate provider-specific field mappings
     */
    private function generateFieldMappings(string $provider): array
    {
        $baseMappings = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'email' => 'email',
            'phone' => 'phone',
            'company' => 'company',
            'job_title' => 'job_title',
            'source' => 'lead_source',
        ];

        switch ($provider) {
            case 'salesforce':
                return array_merge($baseMappings, [
                    'first_name' => 'FirstName',
                    'last_name' => 'LastName',
                    'email' => 'Email',
                    'phone' => 'Phone',
                    'company' => 'Company',
                    'job_title' => 'Title',
                    'source' => 'LeadSource',
                ]);

            case 'hubspot':
                return array_merge($baseMappings, [
                    'first_name' => 'firstname',
                    'last_name' => 'lastname',
                    'email' => 'email',
                    'phone' => 'phone',
                    'company' => 'company',
                    'job_title' => 'jobtitle',
                ]);

            case 'zoho':
                return array_merge($baseMappings, [
                    'first_name' => 'First_Name',
                    'last_name' => 'Last_Name',
                    'email' => 'Email',
                    'phone' => 'Phone',
                    'company' => 'Company',
                    'job_title' => 'Designation',
                    'source' => 'Lead_Source',
                ]);

            case 'twenty':
                return array_merge($baseMappings, [
                    'first_name' => 'firstName',
                    'last_name' => 'lastName',
                    'email' => 'email',
                    'phone' => 'phone',
                    'company' => 'companyName',
                    'job_title' => 'jobTitle',
                    'source' => 'source',
                ]);

            case 'frappe':
                return array_merge($baseMappings, [
                    'first_name' => 'first_name',
                    'last_name' => 'last_name',
                    'email' => 'email_id',
                    'phone' => 'phone',
                    'company' => 'company_name',
                    'job_title' => 'designation',
                    'source' => 'source',
                ]);

            default:
                return $baseMappings;
        }
    }

    /**
     * Indicate that the integration is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the integration is for Salesforce.
     */
    public function salesforce(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'salesforce',
            'name' => 'Salesforce Integration',
            'config' => $this->generateConfig('salesforce'),
            'field_mappings' => $this->generateFieldMappings('salesforce'),
        ]);
    }

    /**
     * Indicate that the integration is for HubSpot.
     */
    public function hubspot(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'hubspot',
            'name' => 'HubSpot Integration',
            'config' => $this->generateConfig('hubspot'),
            'field_mappings' => $this->generateFieldMappings('hubspot'),
        ]);
    }

    /**
     * Indicate that the integration is for Zoho CRM.
     */
    public function zoho(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'zoho',
            'name' => 'Zoho CRM Integration',
            'config' => $this->generateConfig('zoho'),
            'field_mappings' => $this->generateFieldMappings('zoho'),
        ]);
    }

    /**
     * Indicate that the integration is for Twenty CRM.
     */
    public function twenty(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'twenty',
            'name' => 'Twenty CRM Integration',
            'config' => $this->generateConfig('twenty'),
            'field_mappings' => $this->generateFieldMappings('twenty'),
        ]);
    }

    /**
     * Indicate that the integration is for Frappe CRM.
     */
    public function frappe(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'frappe',
            'name' => 'Frappe CRM Integration',
            'config' => $this->generateConfig('frappe'),
            'field_mappings' => $this->generateFieldMappings('frappe'),
        ]);
    }
}
