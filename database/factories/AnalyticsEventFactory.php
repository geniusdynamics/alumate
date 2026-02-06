<?php

namespace Database\Factories;

use App\Models\AnalyticsEvent;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalyticsEvent>
 */
class AnalyticsEventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AnalyticsEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'event_type' => $this->faker->randomElement(['page_view', 'click', 'gamification', 'conversion']),
            'event_name' => $this->faker->word(),
            'gamification_type' => null,
            'points_earned' => null,
            'badge_earned' => null,
            'user_id' => $this->faker->uuid(),
            'properties' => [],
            'session_id' => $this->faker->uuid(),
            'user_agent' => $this->faker->userAgent(),
            'ip_address' => $this->faker->ipv4(),
            'referrer' => $this->faker->url(),
            'page_url' => $this->faker->url(),
            'occurred_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'is_compliant' => true,
            'consent_given' => true,
            'data_retention_until' => $this->faker->dateTimeBetween('now', '+1 year'),
            'analytics_version' => '1.0.0',
        ];
    }

    /**
     * Create gamification event
     */
    public function gamification(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'gamification',
            'event_name' => $this->faker->randomElement(['points_earned', 'badge_earned', 'level_up', 'achievement_unlocked']),
            'gamification_type' => $this->faker->randomElement(['points_earned', 'badge_earned', 'level_up', 'achievement_unlocked']),
            'points_earned' => $this->faker->numberBetween(1, 100),
            'badge_earned' => $this->faker->randomElement(['first_login', 'profile_complete', 'social_engagement', 'content_creator']),
            'properties' => [
                'source' => $this->faker->randomElement(['login', 'profile_update', 'post_creation', 'comment']),
                'difficulty' => $this->faker->randomElement(['easy', 'medium', 'hard']),
            ],
        ]);
    }

    /**
     * Create click event for heat map
     */
    public function click(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'click',
            'event_name' => 'click',
            'properties' => [
                'x' => $this->faker->numberBetween(0, 100),
                'y' => $this->faker->numberBetween(0, 100),
                'element' => $this->faker->randomElement(['button', 'link', 'image', 'form']),
            ],
        ]);
    }

    /**
     * Create scroll event
     */
    public function scroll(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'scroll',
            'event_name' => 'scroll',
            'properties' => [
                'scroll_depth' => $this->faker->numberBetween(10, 100),
                'time_spent' => $this->faker->numberBetween(1, 300),
            ],
        ]);
    }

    /**
     * Create A/B test event
     */
    public function abTest(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => $this->faker->randomElement(['page_view', 'button_click', 'form_submit']),
            'event_name' => $this->faker->randomElement(['page_view', 'button_click', 'form_submit']),
            'properties' => [
                'ab_test_id' => $this->faker->numberBetween(1, 100),
                'ab_variant' => $this->faker->randomElement(['control', 'variant_a', 'variant_b']),
            ],
        ]);
    }

    /**
     * Set specific tenant
     */
    public function forTenant(string $tenantId): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Set non-compliant event
     */
    public function nonCompliant(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_compliant' => false,
            'consent_given' => false,
        ]);
    }

    /**
     * Set specific user
     */
    public function forUser(string $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Set specific page URL
     */
    public function forPage(string $pageUrl): static
    {
        return $this->state(fn (array $attributes) => [
            'page_url' => $pageUrl,
        ]);
    }
}