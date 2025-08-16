<?php

namespace Database\Factories;

use App\Models\MentorshipRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MentorshipRequestFactory extends Factory
{
    protected $model = MentorshipRequest::class;

    public function definition(): array
    {
        return [
            'mentor_id' => User::factory(),
            'mentee_id' => User::factory(),
            'message' => $this->faker->paragraph(2),
            'goals' => $this->faker->paragraph(1),
            'preferred_frequency' => $this->faker->randomElement(['weekly', 'bi_weekly', 'monthly']),
            'preferred_duration' => $this->faker->randomElement([30, 45, 60, 90]),
            'topics_of_interest' => $this->faker->randomElements([
                'Technical Skills',
                'Career Planning',
                'Interview Preparation',
                'Leadership Development',
                'Networking',
                'Work-Life Balance',
                'Industry Insights',
                'Skill Development',
                'Job Search Strategy',
                'Professional Growth',
            ], $this->faker->numberBetween(2, 5)),
            'status' => $this->faker->randomElement(['pending', 'accepted', 'rejected', 'waitlisted']),
            'accepted_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'mentorship_agreement' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'accepted_at' => null,
                'rejected_at' => null,
            ];
        });
    }

    public function accepted(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'accepted',
                'accepted_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'rejected_at' => null,
                'mentorship_agreement' => [
                    'session_frequency' => $this->faker->randomElement(['weekly', 'bi_weekly', 'monthly']),
                    'session_duration' => $this->faker->randomElement([30, 45, 60]),
                    'communication_channels' => $this->faker->randomElements(['video_call', 'email', 'phone'], rand(1, 3)),
                    'goals' => $this->faker->sentence(),
                ],
            ];
        });
    }

    public function rejected(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'rejected',
                'accepted_at' => null,
                'rejected_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'rejection_reason' => $this->faker->randomElement([
                    'Currently at capacity',
                    'Not a good fit for expertise area',
                    'Schedule conflicts',
                    'Other commitments',
                ]),
            ];
        });
    }

    public function waitlisted(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'waitlisted',
                'accepted_at' => null,
                'rejected_at' => null,
            ];
        });
    }
}
