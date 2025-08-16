<?php

namespace Database\Factories;

use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MentorProfileFactory extends Factory
{
    protected $model = MentorProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bio' => $this->faker->paragraph(3),
            'expertise_areas' => $this->faker->randomElements([
                'Software Development',
                'Product Management',
                'Data Science',
                'Marketing',
                'Sales',
                'Finance',
                'Operations',
                'Human Resources',
                'Design',
                'Engineering',
                'Consulting',
                'Entrepreneurship',
                'Leadership',
                'Career Transition',
                'Technical Skills',
                'Communication',
                'Project Management',
            ], $this->faker->numberBetween(2, 5)),
            'industries' => $this->faker->randomElements([
                'Technology',
                'Healthcare',
                'Finance',
                'Education',
                'Retail',
                'Manufacturing',
                'Consulting',
                'Media',
                'Non-profit',
                'Government',
                'Startups',
                'E-commerce',
            ], $this->faker->numberBetween(1, 3)),
            'mentoring_capacity' => $this->faker->numberBetween(1, 10),
            'session_duration' => $this->faker->randomElement([30, 45, 60, 90]),
            'hourly_rate' => $this->faker->optional(0.3)->randomFloat(2, 0, 200),
            'availability' => [
                'monday' => $this->faker->optional()->randomElements(['09:00-12:00', '14:00-17:00'], rand(0, 2)),
                'tuesday' => $this->faker->optional()->randomElements(['09:00-12:00', '14:00-17:00'], rand(0, 2)),
                'wednesday' => $this->faker->optional()->randomElements(['09:00-12:00', '14:00-17:00'], rand(0, 2)),
                'thursday' => $this->faker->optional()->randomElements(['09:00-12:00', '14:00-17:00'], rand(0, 2)),
                'friday' => $this->faker->optional()->randomElements(['09:00-12:00', '14:00-17:00'], rand(0, 2)),
                'saturday' => $this->faker->optional()->randomElements(['09:00-12:00'], rand(0, 1)),
                'sunday' => $this->faker->optional()->randomElements(['09:00-12:00'], rand(0, 1)),
            ],
            'mentoring_preferences' => [
                'communication_style' => $this->faker->randomElement(['structured', 'casual', 'flexible']),
                'session_format' => $this->faker->randomElement(['video_call', 'phone_call', 'in_person', 'flexible']),
                'focus_areas' => $this->faker->randomElements(['career_planning', 'skill_development', 'networking', 'interview_prep'], rand(1, 3)),
            ],
            'is_active' => $this->faker->boolean(80),
            'rating' => $this->faker->optional(0.7)->randomFloat(1, 3.0, 5.0),
            'total_mentees' => $this->faker->numberBetween(0, 50),
            'total_sessions' => $this->faker->numberBetween(0, 200),
        ];
    }

    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    public function experienced(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'total_mentees' => $this->faker->numberBetween(20, 100),
                'total_sessions' => $this->faker->numberBetween(100, 500),
                'rating' => $this->faker->randomFloat(1, 4.0, 5.0),
            ];
        });
    }

    public function newMentor(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'total_mentees' => $this->faker->numberBetween(0, 5),
                'total_sessions' => $this->faker->numberBetween(0, 20),
                'rating' => null,
            ];
        });
    }
}
