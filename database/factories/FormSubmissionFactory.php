<?php

namespace Database\Factories;

use App\Models\FormSubmission;
use App\Models\FormBuilder;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FormSubmission>
 */
class FormSubmissionFactory extends Factory
{
    protected $model = FormSubmission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => FormBuilder::factory(),
            'submission_data' => [
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'email' => $this->faker->safeEmail(),
                'message' => $this->faker->paragraph()
            ],
            'user_ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'referrer_url' => $this->faker->url(),
            'utm_source' => $this->faker->randomElement(['google', 'facebook', 'twitter', 'linkedin', null]),
            'utm_medium' => $this->faker->randomElement(['cpc', 'social', 'email', 'organic', null]),
            'utm_campaign' => $this->faker->randomElement(['summer-2024', 'alumni-outreach', 'newsletter', null]),
            'crm_sync_status' => $this->faker->randomElement(['pending', 'synced', 'failed']),
            'crm_lead_id' => $this->faker->optional()->uuid(),
            'crm_sync_error' => null,
            'validation_errors' => null,
            'status' => $this->faker->randomElement(['pending', 'processed', 'failed', 'synced']),
            'tenant_id' => Tenant::factory()
        ];
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processed',
            'crm_sync_status' => 'synced'
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'crm_sync_status' => 'failed',
            'crm_sync_error' => [
                'message' => 'CRM API error',
                'timestamp' => now()->toISOString()
            ]
        ]);
    }

    public function withUtmParameters(): static
    {
        return $this->state(fn (array $attributes) => [
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'alumni-outreach-2024'
        ]);
    }

    public function crmSynced(): static
    {
        return $this->state(fn (array $attributes) => [
            'crm_sync_status' => 'synced',
            'crm_lead_id' => $this->faker->uuid(),
            'status' => 'synced'
        ]);
    }

    public function crmFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'crm_sync_status' => 'failed',
            'crm_sync_error' => [
                'message' => 'Invalid API credentials',
                'timestamp' => now()->toISOString()
            ]
        ]);
    }
}
