<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Webhook>
 */
class WebhookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $availableEvents = [
            'user.created', 'user.updated', 'user.deleted',
            'post.created', 'post.updated', 'post.deleted', 'post.liked', 'post.commented', 'post.shared',
            'connection.created', 'connection.accepted',
            'event.created', 'event.updated', 'event.registered', 'event.cancelled',
            'donation.completed', 'donation.failed', 'donation.refunded',
            'mentorship.requested', 'mentorship.accepted', 'mentorship.declined',
            'job.applied', 'achievement.earned', 'notification.sent',
        ];

        return [
            'user_id' => User::factory(),
            'url' => $this->faker->url(),
            'events' => $this->faker->randomElements($availableEvents, $this->faker->numberBetween(1, 5)),
            'secret' => $this->faker->sha256(),
            'status' => $this->faker->randomElement(['active', 'paused', 'disabled']),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'headers' => [
                'X-Custom-Header' => $this->faker->word(),
                'Authorization' => 'Bearer '.$this->faker->sha256(),
            ],
            'timeout' => $this->faker->numberBetween(5, 300),
            'retry_attempts' => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * Indicate that the webhook is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the webhook is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }

    /**
     * Indicate that the webhook is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disabled',
        ]);
    }

    /**
     * Set specific events for the webhook.
     */
    public function withEvents(array $events): static
    {
        return $this->state(fn (array $attributes) => [
            'events' => $events,
        ]);
    }
}
