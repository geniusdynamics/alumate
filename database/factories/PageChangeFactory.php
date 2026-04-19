<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PageChange>
 */
class PageChangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $operationType = $this->faker->randomElement(['add', 'update', 'delete', 'move']);
        $componentId = 'component-' . $this->faker->uuid();

        return [
            'page_id' => \App\Models\LandingPage::factory(),
            'user_id' => \App\Models\User::factory(),
            'operation_type' => $operationType,
            'component_id' => $componentId,
            'operation_data' => $this->generateOperationData($operationType, $componentId),
            'previous_state' => $this->faker->optional()->randomElement([
                ['content' => 'Old content', 'style' => 'color: red;'],
                ['position' => ['x' => 100, 'y' => 200]],
            ]),
            'new_state' => [
                'content' => $this->faker->sentence(),
                'style' => 'color: blue; font-size: 16px;',
                'position' => ['x' => $this->faker->numberBetween(0, 500), 'y' => $this->faker->numberBetween(0, 500)],
            ],
            'sequence_number' => $this->faker->numberBetween(1, 1000),
            'session_id' => $this->faker->uuid(),
            'is_applied' => $this->faker->boolean(70),
        ];
    }

    public function applied(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_applied' => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_applied' => false,
        ]);
    }

    public function addOperation(): static
    {
        return $this->state(fn (array $attributes) => [
            'operation_type' => 'add',
            'operation_data' => [
                'component_type' => $this->faker->randomElement(['text', 'image', 'button']),
                'parent_id' => 'container-' . $this->faker->uuid(),
                'position' => $this->faker->numberBetween(0, 10),
                'content' => $this->faker->sentence(),
            ],
        ]);
    }

    public function updateOperation(): static
    {
        return $this->state(fn (array $attributes) => [
            'operation_type' => 'update',
            'operation_data' => [
                'changes' => [
                    'content' => $this->faker->sentence(),
                    'style' => 'color: ' . $this->faker->colorName() . ';',
                ],
            ],
        ]);
    }

    public function deleteOperation(): static
    {
        return $this->state(fn (array $attributes) => [
            'operation_type' => 'delete',
            'operation_data' => [
                'reason' => 'User requested deletion',
            ],
        ]);
    }

    public function moveOperation(): static
    {
        return $this->state(fn (array $attributes) => [
            'operation_type' => 'move',
            'operation_data' => [
                'old_parent_id' => 'container-' . $this->faker->uuid(),
                'new_parent_id' => 'container-' . $this->faker->uuid(),
                'old_position' => $this->faker->numberBetween(0, 5),
                'new_position' => $this->faker->numberBetween(0, 10),
            ],
        ]);
    }

    private function generateOperationData(string $operationType, string $componentId): array
    {
        return match ($operationType) {
            'add' => [
                'component_type' => $this->faker->randomElement(['text', 'image', 'button', 'form']),
                'parent_id' => 'container-' . $this->faker->uuid(),
                'position' => $this->faker->numberBetween(0, 10),
                'content' => $this->faker->sentence(),
                'attributes' => [
                    'class' => $this->faker->words(2, true),
                    'id' => $componentId,
                ],
            ],
            'update' => [
                'changes' => [
                    'content' => $this->faker->sentence(),
                    'style' => 'color: ' . $this->faker->colorName() . '; font-size: ' . $this->faker->numberBetween(12, 24) . 'px;',
                    'attributes' => [
                        'class' => $this->faker->words(3, true),
                    ],
                ],
            ],
            'delete' => [
                'reason' => $this->faker->randomElement(['User requested', 'Cleanup', 'Replaced']),
                'backup' => true,
            ],
            'move' => [
                'old_parent_id' => 'container-' . $this->faker->uuid(),
                'new_parent_id' => 'container-' . $this->faker->uuid(),
                'old_position' => $this->faker->numberBetween(0, 5),
                'new_position' => $this->faker->numberBetween(0, 10),
            ],
            default => [],
        };
    }
}
