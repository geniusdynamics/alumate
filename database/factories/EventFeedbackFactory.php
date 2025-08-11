<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventFeedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFeedbackFactory extends Factory
{
    protected $model = EventFeedback::class;

    public function definition(): array
    {
        $overallRating = $this->faker->numberBetween(1, 5);

        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'overall_rating' => $overallRating,
            'content_rating' => $this->faker->optional(0.8)->numberBetween(1, 5),
            'organization_rating' => $this->faker->optional(0.8)->numberBetween(1, 5),
            'networking_rating' => $this->faker->optional(0.7)->numberBetween(1, 5),
            'venue_rating' => $this->faker->optional(0.7)->numberBetween(1, 5),
            'feedback_text' => $this->faker->optional(0.6)->paragraph(),
            'feedback_categories' => $this->faker->optional(0.4)->randomElements([
                'content_quality',
                'speaker_expertise',
                'networking_opportunities',
                'venue_facilities',
                'organization',
                'timing',
                'accessibility',
            ], $this->faker->numberBetween(1, 3)),
            'would_recommend' => $overallRating >= 4 ? $this->faker->boolean(80) : $this->faker->boolean(30),
            'would_attend_again' => $overallRating >= 4 ? $this->faker->boolean(85) : $this->faker->boolean(40),
            'improvement_suggestions' => $this->faker->optional(0.3)->randomElements([
                'Better time management',
                'More networking breaks',
                'Improved audio/visual setup',
                'More interactive sessions',
                'Better venue location',
                'More diverse speakers',
                'Longer Q&A sessions',
            ], $this->faker->numberBetween(1, 2)),
            'is_anonymous' => $this->faker->boolean(20),
        ];
    }

    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'overall_rating' => $this->faker->numberBetween(4, 5),
            'would_recommend' => true,
            'would_attend_again' => $this->faker->boolean(90),
        ]);
    }

    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'overall_rating' => $this->faker->numberBetween(1, 2),
            'would_recommend' => false,
            'would_attend_again' => $this->faker->boolean(20),
            'improvement_suggestions' => [
                'Better organization needed',
                'Content was not relevant',
                'Poor venue facilities',
            ],
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_anonymous' => true,
        ]);
    }
}
