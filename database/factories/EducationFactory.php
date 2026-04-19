<?php

namespace Database\Factories;

use App\Models\Education;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Education>
 */
class EducationFactory extends Factory
{
    protected $model = Education::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'institution_id' => null,
            'degree' => fake()->randomElement(['Bachelor', 'Master', 'PhD', 'Associate']),
            'field_of_study' => fake()->randomElement(['Computer Science', 'Business', 'Engineering', 'Arts', 'Medicine']),
            'start_year' => fake()->numberBetween(2010, 2020),
            'end_year' => fake()->numberBetween(2014, 2024),
            'graduation_year' => fake()->numberBetween(2014, 2024),
        ];
    }
}
