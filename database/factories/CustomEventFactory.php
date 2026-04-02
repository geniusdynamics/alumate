<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CustomEvent;
use App\Models\CustomEventDefinition;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * CustomEvent Factory
 *
 * Creates test data for custom events.
 */
class CustomEventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = CustomEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'definition_id' => CustomEventDefinition::factory(),
            'user_id' => User::factory(),
            'data_json' => [
                'value' => $this->faker->word(),
            ],
            'timestamp' => now(),
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
     * Set the definition.
     */
    public function forDefinition(int|CustomEventDefinition $definition): self
    {
        return $this->state(fn (array $attributes) => [
            'definition_id' => $definition instanceof CustomEventDefinition ? $definition->id : $definition,
        ]);
    }

    /**
     * Set the user.
     */
    public function forUser(int|User $user): self
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user instanceof User ? $user->id : $user,
        ]);
    }

    /**
     * Set custom data.
     */
    public function withData(array $data): self
    {
        return $this->state(fn (array $attributes) => [
            'data_json' => $data,
        ]);
    }

    /**
     * Set a specific timestamp.
     */
    public function withTimestamp(\DateTimeInterface $timestamp): self
    {
        return $this->state(fn (array $attributes) => [
            'timestamp' => $timestamp,
        ]);
    }
}
