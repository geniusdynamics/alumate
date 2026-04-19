<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CustomEventDefinition;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * CustomEventDefinition Factory
 *
 * Creates test data for custom event definitions.
 */
class CustomEventDefinitionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = CustomEventDefinition::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->unique()->word() . '_' . $this->faker->randomNumber(4),
            'description' => $this->faker->sentence(),
            'parameters_json' => [
                ['name' => 'value', 'type' => 'string'],
            ],
            'created_by' => null,
            'status' => 'active',
        ];
    }

    /**
     * Set the tenant ID.
     */
    public function forTenant(int|Tenant $tenant): self
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant instanceof Tenant ? $tenant->id : $tenant,
        ]);
    }

    /**
     * Set the status to active.
     */
    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Set the status to inactive.
     */
    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Set the creator.
     */
    public function createdBy(int|User $user): self
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user instanceof User ? $user->id : $user,
        ]);
    }
}
