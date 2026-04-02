<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkExperience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkExperience>
 */
class WorkExperienceFactory extends Factory
{
    protected $model = WorkExperience::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company' => fake()->company(),
            'title' => fake()->jobTitle(),
            'industry' => fake()->randomElement(['Technology', 'Healthcare', 'Finance', 'Education', 'Retail']),
            'employment_type' => fake()->randomElement(['full_time', 'part_time', 'contract', 'internship']),
            'is_current' => false,
            'start_date' => fake()->date('Y-m-d', '-1 year'),
            'end_date' => fake()->date('Y-m-d', 'now'),
            'description' => fake()->optional()->paragraph(),
            'skills_used' => json_encode(fake()->randomElements([
                'JavaScript', 'Python', 'Java', 'PHP', 'React', 'Vue.js', 'Node.js', 'Laravel', 'SQL', 'MongoDB', 'AWS'
            ], 3)),
            'achievements' => json_encode(fake()->optional()->sentences(2)),
            'location' => fake()->city(),
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
            'end_date' => null,
        ]);
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => false,
        ]);
    }

    public function internship(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'internship',
            'title' => 'Intern',
        ]);
    }

    public function senior(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Senior ' . (fake()->jobTitle()),
        ]);
    }
}
