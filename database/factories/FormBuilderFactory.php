<?php

namespace Database\Factories;

use App\Models\FormBuilder;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FormBuilder>
 */
class FormBuilderFactory extends Factory
{
    protected $model = FormBuilder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . ' Form',
            'description' => $this->faker->sentence(),
            'configuration' => [],
            'validation_rules' => [],
            'conditional_logic' => [],
            'crm_integration_config' => [
                'enabled' => false,
                'provider' => null,
                'field_mappings' => []
            ],
            'success_message' => 'Thank you for your submission!',
            'error_message' => 'Please correct the errors below.',
            'redirect_url' => null,
            'is_active' => true,
            'tenant_id' => Tenant::factory()
        ];
    }

    public function withCrmIntegration(string $provider = 'salesforce'): static
    {
        return $this->state(fn (array $attributes) => [
            'crm_integration_config' => [
                'enabled' => true,
                'provider' => $provider,
                'field_mappings' => [
                    'first_name' => 'FirstName',
                    'last_name' => 'LastName',
                    'email' => 'Email',
                    'company' => 'Company'
                ]
            ]
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false
        ]);
    }
}
