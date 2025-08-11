<?php

namespace Database\Factories;

use App\Models\SavedSearch;
use App\Models\SearchAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SearchAlert>
 */
class SearchAlertFactory extends Factory
{
    protected $model = SearchAlert::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $frequencies = ['daily', 'weekly', 'monthly'];
        $frequency = $this->faker->randomElement($frequencies);

        $isActive = $this->faker->boolean(80); // 80% chance of being active
        $lastSentAt = $isActive && $this->faker->boolean(60)
            ? $this->faker->dateTimeBetween('-1 month', 'now')
            : null;

        $nextSendAt = null;
        if ($isActive && $lastSentAt) {
            $nextSendAt = $this->calculateNextSendTime($lastSentAt, $frequency);
        } elseif ($isActive) {
            // If active but never sent, set next send time to soon
            $nextSendAt = $this->faker->dateTimeBetween('now', '+1 day');
        }

        return [
            'user_id' => User::factory(),
            'saved_search_id' => SavedSearch::factory(),
            'frequency' => $frequency,
            'is_active' => $isActive,
            'last_sent_at' => $lastSentAt,
            'next_send_at' => $nextSendAt,
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ];
    }

    /**
     * Calculate next send time based on frequency
     */
    private function calculateNextSendTime(\DateTime $lastSent, string $frequency): \DateTime
    {
        $nextSend = clone $lastSent;

        return match ($frequency) {
            'daily' => $nextSend->modify('+1 day'),
            'weekly' => $nextSend->modify('+1 week'),
            'monthly' => $nextSend->modify('+1 month'),
            default => $nextSend->modify('+1 day')
        };
    }

    /**
     * Create an active alert
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'next_send_at' => $this->faker->dateTimeBetween('now', '+1 week'),
        ]);
    }

    /**
     * Create an inactive alert
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'next_send_at' => null,
        ]);
    }

    /**
     * Create a daily alert
     */
    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'daily',
        ]);
    }

    /**
     * Create a weekly alert
     */
    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'weekly',
        ]);
    }

    /**
     * Create a monthly alert
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'monthly',
        ]);
    }

    /**
     * Create an alert that is due to be sent
     */
    public function due(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'next_send_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Create an alert that was recently sent
     */
    public function recentlySent(): static
    {
        $lastSent = $this->faker->dateTimeBetween('-2 days', 'now');
        $frequency = $this->faker->randomElement(['daily', 'weekly', 'monthly']);

        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'frequency' => $frequency,
            'last_sent_at' => $lastSent,
            'next_send_at' => $this->calculateNextSendTime($lastSent, $frequency),
        ]);
    }

    /**
     * Create an alert that has never been sent
     */
    public function neverSent(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'last_sent_at' => null,
            'next_send_at' => $this->faker->dateTimeBetween('now', '+1 hour'),
        ]);
    }

    /**
     * Create an alert for a specific user and saved search
     */
    public function forUserAndSearch(User $user, SavedSearch $savedSearch): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'saved_search_id' => $savedSearch->id,
        ]);
    }
}
