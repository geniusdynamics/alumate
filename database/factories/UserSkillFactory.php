<?php

namespace Database\Factories;

use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSkillFactory extends Factory
{
    protected $model = UserSkill::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'skill_id' => Skill::factory(),
            'proficiency_level' => $this->faker->randomElement(['Beginner', 'Intermediate', 'Advanced', 'Expert']),
            'years_experience' => $this->faker->numberBetween(0, 20),
            'endorsed_count' => $this->faker->numberBetween(0, 50),
        ];
    }

    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'proficiency_level' => 'Beginner',
            'years_experience' => $this->faker->numberBetween(0, 2),
            'endorsed_count' => $this->faker->numberBetween(0, 5),
        ]);
    }

    public function intermediate(): static
    {
        return $this->state(fn (array $attributes) => [
            'proficiency_level' => 'Intermediate',
            'years_experience' => $this->faker->numberBetween(2, 5),
            'endorsed_count' => $this->faker->numberBetween(3, 15),
        ]);
    }

    public function advanced(): static
    {
        return $this->state(fn (array $attributes) => [
            'proficiency_level' => 'Advanced',
            'years_experience' => $this->faker->numberBetween(5, 10),
            'endorsed_count' => $this->faker->numberBetween(10, 30),
        ]);
    }

    public function expert(): static
    {
        return $this->state(fn (array $attributes) => [
            'proficiency_level' => 'Expert',
            'years_experience' => $this->faker->numberBetween(8, 20),
            'endorsed_count' => $this->faker->numberBetween(20, 100),
        ]);
    }

    public function withEndorsements(?int $count = null): static
    {
        return $this->state(fn (array $attributes) => [
            'endorsed_count' => $count ?? $this->faker->numberBetween(5, 25),
        ]);
    }
}
