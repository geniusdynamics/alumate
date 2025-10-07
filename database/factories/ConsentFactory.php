<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Consent>
 */
class ConsentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grantedAt = $this->faker->optional(0.8)->dateTimeBetween('-30 days', 'now'); // 80% chance of being granted

        return [
            'user_id' => \App\Models\User::factory(),
            'type' => $this->faker->randomElement(['analytics']),
            'granted_at' => $grantedAt,
            'revoked_at' => $grantedAt && $this->faker->boolean(20) ? $this->faker->dateTimeBetween($grantedAt, 'now') : null, // 20% chance of being revoked if granted
            'ip_address' => $this->faker->ipv4,
            'criteria' => $this->faker->optional(0.5)->randomElements(['gdpr', 'ccpa', 'marketing', 'profiling'], $this->faker->numberBetween(1, 3)),
            'parameters' => $this->faker->optional(0.3)->randomElements(['data_retention' => '30_days', 'purpose' => 'analytics', 'scope' => 'global'], $this->faker->numberBetween(1, 3)),
        ];
    }

    /**
     * Create an active consent
     */
    public function active(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'granted_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'revoked_at' => null,
            ];
        });
    }

    /**
     * Create a revoked consent
     */
    public function revoked(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'granted_at' => $this->faker->dateTimeBetween('-60 days', '-31 days'),
                'revoked_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            ];
        });
    }

    /**
     * Create consent for specific type
     */
    public function forType(string $type): self
    {
        return $this->state(function (array $attributes) use ($type) {
            return [
                'type' => $type,
            ];
        });
    }

    /**
     * Create consent for specific user
     */
    public function forUser(int $userId): self
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'user_id' => $userId,
            ];
        });
    }

    /**
     * Create expired consent (revoked >30 days ago)
     */
    public function expired(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'granted_at' => $this->faker->dateTimeBetween('-90 days', '-60 days'),
                'revoked_at' => $this->faker->dateTimeBetween('-40 days', '-31 days'),
            ];
        });
    }
}
