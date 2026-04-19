<?php

namespace Database\Factories;

use App\Models\AttributionTouch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttributionTouch>
 */
class AttributionTouchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = AttributionTouch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'session_id' => $this->faker->uuid(),
            'touch_type' => $this->faker->randomElement(['email', 'ad', 'social', 'direct', 'organic']),
            'channel' => $this->faker->randomElement(['google', 'facebook', 'email', 'twitter', 'linkedin', 'direct']),
            'timestamp' => $this->faker->dateTimeBetween('-90 days', 'now'),
            'value' => $this->faker->randomFloat(2, 0, 100), // engagement score 0-100
            'metadata' => [
                'campaign_id' => $this->faker->uuid(),
                'source_url' => $this->faker->url(),
                'device_type' => $this->faker->randomElement(['desktop', 'mobile', 'tablet']),
            ],
            'conversion_value' => $this->faker->randomElement([null, $this->faker->randomFloat(2, 10, 1000)]),
        ];
    }

    /**
     * Indicate that the touchpoint resulted in a conversion.
     */
    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'conversion_value' => $this->faker->randomFloat(2, 50, 500),
        ]);
    }

    /**
     * Create a touchpoint for a specific channel.
     */
    public function channel(string $channel): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => $channel,
        ]);
    }

    /**
     * Create a touchpoint for a specific touch type.
     */
    public function touchType(string $touchType): static
    {
        return $this->state(fn (array $attributes) => [
            'touch_type' => $touchType,
        ]);
    }
}
