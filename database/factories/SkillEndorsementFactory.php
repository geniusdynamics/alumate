<?php

namespace Database\Factories;

use App\Models\SkillEndorsement;
use App\Models\UserSkill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkillEndorsementFactory extends Factory
{
    protected $model = SkillEndorsement::class;

    public function definition(): array
    {
        $messages = [
            'Excellent technical skills and great problem-solving abilities.',
            'Outstanding leadership and team collaboration.',
            'Highly skilled professional with deep expertise.',
            'Great mentor and always willing to help others.',
            'Innovative thinker with strong analytical skills.',
            'Reliable team player with excellent communication.',
            'Demonstrates exceptional proficiency in this area.',
            'Consistently delivers high-quality work.',
            'Strong technical foundation and continuous learner.',
            'Natural leader with great people skills.',
        ];

        return [
            'user_skill_id' => UserSkill::factory(),
            'endorser_id' => User::factory(),
            'message' => $this->faker->boolean(60) ? $this->faker->randomElement($messages) : null,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function withMessage(): static
    {
        return $this->state(fn (array $attributes) => [
            'message' => $this->faker->randomElement([
                'Excellent technical skills and great problem-solving abilities.',
                'Outstanding leadership and team collaboration.',
                'Highly skilled professional with deep expertise.',
                'Great mentor and always willing to help others.',
                'Innovative thinker with strong analytical skills.',
            ]),
        ]);
    }

    public function withoutMessage(): static
    {
        return $this->state(fn (array $attributes) => [
            'message' => null,
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }
}