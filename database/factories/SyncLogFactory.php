<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SyncLog;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SyncLog>
 */
class SyncLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SyncLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'sync_type' => $this->faker->randomElement(['ga', 'matomo', 'unified', 'discrepancy_detection']),
            'status' => $this->faker->randomElement(['success', 'failed', 'partial']),
            'discrepancies' => $this->faker->optional(0.7)->randomElements([
                [
                    'metric' => 'events_count',
                    'source' => 'ga',
                    'internal_value' => $this->faker->numberBetween(1000, 5000),
                    'external_value' => $this->faker->numberBetween(800, 4500),
                    'difference_percentage' => $this->faker->randomFloat(2, 1, 25),
                    'threshold_exceeded' => $this->faker->boolean(30),
                ]
            ], $this->faker->numberBetween(0, 3)),
            'timestamp' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the sync log is successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
        ]);
    }

    /**
     * Indicate that the sync log failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    /**
     * Indicate that the sync log is partial.
     */
    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'partial',
        ]);
    }

    /**
     * Set the sync type to Google Analytics.
     */
    public function googleAnalytics(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_type' => 'ga',
        ]);
    }

    /**
     * Set the sync type to Matomo.
     */
    public function matomo(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_type' => 'matomo',
        ]);
    }

    /**
     * Set the sync type to unified.
     */
    public function unified(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_type' => 'unified',
        ]);
    }

    /**
     * Set the sync type to discrepancy detection.
     */
    public function discrepancyDetection(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_type' => 'discrepancy_detection',
        ]);
    }

    /**
     * Add discrepancies to the sync log.
     */
    public function withDiscrepancies(int $count = 1): static
    {
        return $this->state(function (array $attributes) use ($count) {
            $discrepancies = [];
            for ($i = 0; $i < $count; $i++) {
                $discrepancies[] = [
                    'metric' => $this->faker->randomElement(['events_count', 'sessions', 'conversions']),
                    'source' => $this->faker->randomElement(['ga', 'matomo']),
                    'internal_value' => $this->faker->numberBetween(1000, 5000),
                    'external_value' => $this->faker->numberBetween(800, 4500),
                    'difference_percentage' => $this->faker->randomFloat(2, 5, 30),
                    'threshold_exceeded' => $this->faker->boolean(50),
                ];
            }

            return [
                'discrepancies' => $discrepancies,
            ];
        });
    }
}