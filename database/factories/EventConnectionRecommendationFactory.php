<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventConnectionRecommendation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventConnectionRecommendationFactory extends Factory
{
    protected $model = EventConnectionRecommendation::class;

    public function definition(): array
    {
        $matchScore = $this->faker->numberBetween(50, 95);

        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'recommended_user_id' => User::factory(),
            'match_score' => $matchScore,
            'match_reasons' => $this->generateMatchReasons($matchScore),
            'shared_attributes' => $this->generateSharedAttributes(),
            'status' => $this->faker->randomElement(['pending', 'viewed', 'connected', 'dismissed']),
            'recommended_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'viewed_at' => function (array $attributes) {
                return in_array($attributes['status'], ['viewed', 'connected', 'dismissed'])
                    ? $this->faker->dateTimeBetween($attributes['recommended_at'], 'now')
                    : null;
            },
            'acted_on_at' => function (array $attributes) {
                return in_array($attributes['status'], ['connected', 'dismissed'])
                    ? $this->faker->dateTimeBetween($attributes['viewed_at'] ?? $attributes['recommended_at'], 'now')
                    : null;
            },
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'viewed_at' => null,
            'acted_on_at' => null,
        ]);
    }

    public function viewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'viewed',
            'viewed_at' => $this->faker->dateTimeBetween($attributes['recommended_at'] ?? '-1 week', 'now'),
            'acted_on_at' => null,
        ]);
    }

    public function connected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'connected',
            'viewed_at' => $this->faker->dateTimeBetween($attributes['recommended_at'] ?? '-1 week', '-1 day'),
            'acted_on_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'dismissed',
            'viewed_at' => $this->faker->dateTimeBetween($attributes['recommended_at'] ?? '-1 week', '-1 day'),
            'acted_on_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    public function highMatch(): static
    {
        return $this->state(fn (array $attributes) => [
            'match_score' => $this->faker->numberBetween(85, 95),
            'match_reasons' => [
                'Same institution',
                'Same graduation year',
                'Similar industry',
                'Mutual connections',
            ],
        ]);
    }

    private function generateMatchReasons(float $matchScore): array
    {
        $allReasons = [
            'Same institution',
            'Same graduation year',
            'Similar location',
            'Same industry',
            'Mutual connections',
            'Similar interests',
            'Complementary skills',
            'Similar career path',
        ];

        $numReasons = match (true) {
            $matchScore >= 90 => $this->faker->numberBetween(4, 6),
            $matchScore >= 80 => $this->faker->numberBetween(3, 5),
            $matchScore >= 70 => $this->faker->numberBetween(2, 4),
            default => $this->faker->numberBetween(1, 3)
        };

        return $this->faker->randomElements($allReasons, $numReasons);
    }

    private function generateSharedAttributes(): array
    {
        $attributes = [];

        if ($this->faker->boolean(60)) {
            $attributes['institution'] = $this->faker->company().' University';
        }

        if ($this->faker->boolean(40)) {
            $attributes['graduation_year'] = $this->faker->numberBetween(1990, 2020);
        }

        if ($this->faker->boolean(50)) {
            $attributes['industry'] = $this->faker->randomElement([
                'Technology',
                'Finance',
                'Healthcare',
                'Education',
                'Marketing',
                'Consulting',
            ]);
        }

        if ($this->faker->boolean(30)) {
            $attributes['location'] = $this->faker->city();
        }

        return $attributes;
    }
}
