<?php

namespace Database\Factories;

use App\Models\CrmSyncLog;
use App\Models\CrmIntegration;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CrmSyncLog>
 */
class CrmSyncLogFactory extends Factory
{
    protected $model = CrmSyncLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $providers = ['salesforce', 'hubspot', 'pipedrive', 'zoho', 'twenty', 'frappe'];
        $syncTypes = ['create', 'update', 'delete', 'pull'];
        $statuses = ['pending', 'success', 'failed'];

        $crmIntegration = CrmIntegration::factory()->create();
        $lead = Lead::factory()->create();

        return [
            'tenant_id' => $crmIntegration->tenant_id ?? 1,
            'crm_integration_id' => $crmIntegration->id,
            'lead_id' => $lead->id,
            'sync_type' => $this->faker->randomElement($syncTypes),
            'crm_provider' => $crmIntegration->provider,
            'crm_record_id' => $this->faker->optional(0.7)->uuid(),
            'status' => $this->faker->randomElement($statuses),
            'sync_data' => $this->generateSyncData($crmIntegration->provider),
            'response_data' => $this->generateResponseData(),
            'error_message' => $this->faker->optional(0.3)->sentence(),
            'retry_count' => $this->faker->numberBetween(0, 3),
            'sync_duration' => $this->faker->optional(0.8)->numberBetween(1, 30),
            'synced_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 day', 'now'),
        ];
    }

    /**
     * Generate provider-specific sync data
     */
    private function generateSyncData(string $provider): array
    {
        $baseData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'company' => $this->faker->company(),
            'job_title' => $this->faker->jobTitle(),
            'source' => $this->faker->word(),
        ];

        switch ($provider) {
            case 'salesforce':
                return array_merge($baseData, [
                    'LeadSource' => $this->faker->randomElement(['Web', 'Phone', 'Email', 'Referral']),
                    'Status' => $this->faker->randomElement(['Open', 'Contacted', 'Qualified', 'Unqualified']),
                ]);

            case 'hubspot':
                return array_merge($baseData, [
                    'hs_lead_status' => $this->faker->randomElement(['NEW', 'OPEN', 'IN_PROGRESS', 'CLOSED']),
                    'lifecyclestage' => $this->faker->randomElement(['lead', 'marketingqualifiedlead', 'salesqualifiedlead']),
                ]);

            case 'zoho':
                return array_merge($baseData, [
                    'Lead_Source' => $this->faker->randomElement(['Web Site', 'Phone', 'Email', 'Referral']),
                    'Lead_Status' => $this->faker->randomElement(['Not Contacted', 'Contacted', 'Qualified', 'Lost']),
                ]);

            default:
                return $baseData;
        }
    }

    /**
     * Generate response data from CRM API
     */
    private function generateResponseData(): ?array
    {
        if ($this->faker->boolean(70)) {
            return [
                'id' => $this->faker->uuid(),
                'created' => true,
                'updated' => false,
                'errors' => [],
                'warnings' => [],
            ];
        }

        return null;
    }

    /**
     * Indicate that the sync log is successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'response_data' => $this->generateResponseData(),
            'error_message' => null,
            'synced_at' => now(),
            'sync_duration' => $this->faker->numberBetween(1, 30),
        ]);
    }

    /**
     * Indicate that the sync log is failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => $this->faker->sentence(),
            'response_data' => null,
        ]);
    }

    /**
     * Indicate that the sync log is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'response_data' => null,
            'error_message' => null,
            'synced_at' => null,
        ]);
    }

    /**
     * Indicate that the sync log is for a create operation.
     */
    public function forCreate(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_type' => 'create',
        ]);
    }

    /**
     * Indicate that the sync log is for an update operation.
     */
    public function update(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_type' => 'update',
        ]);
    }

    /**
     * Indicate that the sync log is for a delete operation.
     */
    public function delete(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_type' => 'delete',
        ]);
    }

    /**
     * Indicate that the sync log is for a pull operation.
     */
    public function pull(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_type' => 'pull',
        ]);
    }

    /**
     * Indicate that the sync log is for Salesforce.
     */
    public function salesforce(): static
    {
        return $this->state(fn (array $attributes) => [
            'crm_provider' => 'salesforce',
            'sync_data' => $this->generateSyncData('salesforce'),
        ]);
    }

    /**
     * Indicate that the sync log is for HubSpot.
     */
    public function hubspot(): static
    {
        return $this->state(fn (array $attributes) => [
            'crm_provider' => 'hubspot',
            'sync_data' => $this->generateSyncData('hubspot'),
        ]);
    }

    /**
     * Indicate that the sync log is for Zoho CRM.
     */
    public function zoho(): static
    {
        return $this->state(fn (array $attributes) => [
            'crm_provider' => 'zoho',
            'sync_data' => $this->generateSyncData('zoho'),
        ]);
    }

    /**
     * Set specific retry count.
     */
    public function retryCount(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'retry_count' => $count,
        ]);
    }
}