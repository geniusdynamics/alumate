<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventNetworkingConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventNetworkingConnectionFactory extends Factory
{
    protected $model = EventNetworkingConnection::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'connected_user_id' => User::factory(),
            'connection_type' => $this->faker->randomElement([
                'met_at_event',
                'mutual_interest',
                'follow_up',
                'collaboration',
            ]),
            'connection_note' => $this->faker->optional(0.7)->paragraph(),
            'shared_interests' => $this->faker->optional(0.6)->randomElements([
                'Technology',
                'Innovation',
                'Entrepreneurship',
                'Marketing',
                'Finance',
                'Healthcare',
                'Education',
                'Sustainability',
            ], $this->faker->numberBetween(1, 3)),
            'follow_up_requested' => $this->faker->boolean(30),
            'connected_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'last_interaction_at' => $this->faker->optional(0.4)->dateTimeBetween('-1 week', 'now'),
        ];
    }

    public function withFollowUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'follow_up_requested' => true,
        ]);
    }

    public function recentConnection(): static
    {
        return $this->state(fn (array $attributes) => [
            'connected_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'last_interaction_at' => $this->faker->dateTimeBetween('-3 days', 'now'),
        ]);
    }
}
