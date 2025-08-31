<?php

namespace Database\Factories;

use App\Models\Component;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ComponentVersion>
 */
class ComponentVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'component_id' => Component::factory(),
            'version_number' => $this->faker->numberBetween(1, 10),
            'config' => [
                'headline' => $this->faker->sentence(),
                'subheading' => $this->faker->paragraph(),
                'background_color' => $this->faker->hexColor(),
            ],
            'metadata' => [
                'created_from' => 'factory',
                'test_data' => true,
            ],
            'changes' => [
                'action' => $this->faker->randomElement(['created', 'updated', 'restored']),
                'modified_fields' => $this->faker->randomElements(['headline', 'subheading', 'config'], 2),
            ],
            'description' => $this->faker->sentence(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Create a version with specific version number
     */
    public function withVersionNumber(int $versionNumber): static
    {
        return $this->state(fn (array $attributes) => [
            'version_number' => $versionNumber,
        ]);
    }

    /**
     * Create a version with specific config
     */
    public function withConfig(array $config): static
    {
        return $this->state(fn (array $attributes) => [
            'config' => $config,
        ]);
    }

    /**
     * Create a version with specific changes
     */
    public function withChanges(array $changes): static
    {
        return $this->state(fn (array $attributes) => [
            'changes' => $changes,
        ]);
    }

    /**
     * Create a version for a specific component
     */
    public function forComponent(Component $component): static
    {
        return $this->state(fn (array $attributes) => [
            'component_id' => $component->id,
        ]);
    }

    /**
     * Create a version by a specific user
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
