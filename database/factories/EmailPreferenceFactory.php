<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EmailPreference;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * EmailPreferenceFactory
 *
 * Factory for creating test EmailPreference instances
 */
class EmailPreferenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EmailPreference::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tenant_id' => Tenant::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'preferences' => $this->getDefaultPreferences(),
            'frequency_settings' => $this->getDefaultFrequencySettings(),
            'consent_given_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'consent_withdrawn_at' => null,
            'double_opt_in_verified_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'double_opt_in_token' => null,
            'unsubscribe_token' => null,
            'gdpr_compliant' => $this->faker->boolean(90), // 90% compliance rate
            'can_spam_compliant' => $this->faker->boolean(95), // 95% compliance rate
            'audit_trail' => [
                [
                    'action' => 'preferences_created',
                    'timestamp' => now()->toISOString(),
                    'preferences' => $this->getDefaultPreferences(),
                    'ip_address' => $this->faker->ipv4(),
                    'user_agent' => $this->faker->userAgent(),
                ]
            ],
        ];
    }

    /**
     * Create a preference with consent withdrawn.
     */
    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'consent_withdrawn_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'unsubscribe_token' => null,
            'audit_trail' => array_merge($attributes['audit_trail'] ?? [], [
                [
                    'action' => 'consent_withdrawn',
                    'timestamp' => now()->toISOString(),
                    'ip_address' => $this->faker->ipv4(),
                    'user_agent' => $this->faker->userAgent(),
                ]
            ])
        ]);
    }

    /**
     * Create a preference without double opt-in verification.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'double_opt_in_verified_at' => null,
            'double_opt_in_token' => $this->faker->sha256(),
        ]);
    }

    /**
     * Create a preference with expired consent.
     */
    public function expiredConsent(): static
    {
        return $this->state(fn (array $attributes) => [
            'consent_given_at' => $this->faker->dateTimeBetween('-2 years', '-1 year -1 day'),
        ]);
    }

    /**
     * Create a preference with specific preferences.
     */
    public function withPreferences(array $preferences): static
    {
        return $this->state(fn (array $attributes) => [
            'preferences' => array_merge($this->getDefaultPreferences(), $preferences),
        ]);
    }

    /**
     * Create a preference with specific frequency settings.
     */
    public function withFrequencySettings(array $frequencySettings): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency_settings' => array_merge($this->getDefaultFrequencySettings(), $frequencySettings),
        ]);
    }

    /**
     * Create a preference without GDPR compliance.
     */
    public function nonGdprCompliant(): static
    {
        return $this->state(fn (array $attributes) => [
            'gdpr_compliant' => false,
        ]);
    }

    /**
     * Create a preference without CAN-SPAM compliance.
     */
    public function nonCanSpamCompliant(): static
    {
        return $this->state(fn (array $attributes) => [
            'can_spam_compliant' => false,
        ]);
    }

    /**
     * Create a preference with unsubscribe token.
     */
    public function withUnsubscribeToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'unsubscribe_token' => $this->faker->sha256(),
        ]);
    }

    /**
     * Create a preference for a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Create a preference for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * Get default preferences.
     */
    private function getDefaultPreferences(): array
    {
        return [
            'newsletters' => $this->faker->boolean(80), // 80% subscribe to newsletters
            'promotions' => $this->faker->boolean(60), // 60% subscribe to promotions
            'announcements' => $this->faker->boolean(90), // 90% subscribe to announcements
            'events' => $this->faker->boolean(70), // 70% subscribe to events
            'surveys' => $this->faker->boolean(40), // 40% subscribe to surveys
        ];
    }

    /**
     * Get default frequency settings.
     */
    private function getDefaultFrequencySettings(): array
    {
        return [
            'newsletters' => $this->faker->randomElement(['daily', 'weekly', 'monthly', 'quarterly']),
            'promotions' => $this->faker->randomElement(['weekly', 'monthly', 'quarterly']),
            'announcements' => $this->faker->randomElement(['immediate', 'daily', 'weekly']),
            'events' => $this->faker->randomElement(['weekly', 'monthly']),
            'surveys' => $this->faker->randomElement(['monthly', 'quarterly', 'annually']),
        ];
    }
}