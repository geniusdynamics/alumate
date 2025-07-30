<?php

namespace Database\Factories;

use App\Models\EducationHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EducationHistoryFactory extends Factory
{
    protected $model = EducationHistory::class;

    public function definition(): array
    {
        $startYear = $this->faker->numberBetween(2000, 2020);
        $endYear = $startYear + $this->faker->numberBetween(2, 6);
        
        $degrees = ['Bachelor of Science', 'Bachelor of Arts', 'Master of Science', 'Master of Arts', 'PhD', 'Associate Degree'];
        $fields = [
            'Computer Science', 'Business Administration', 'Engineering', 'Psychology', 
            'Biology', 'Mathematics', 'English Literature', 'History', 'Economics', 
            'Marketing', 'Nursing', 'Education', 'Art', 'Music'
        ];
        
        return [
            'graduate_id' => User::factory(),
            'institution_name' => $this->faker->company . ' ' . $this->faker->randomElement(['University', 'College', 'Institute']),
            'degree' => $this->faker->randomElement($degrees),
            'field_of_study' => $this->faker->randomElement($fields),
            'start_year' => $startYear,
            'end_year' => $endYear,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'graduate_id' => $user->id,
            ];
        });
    }

    public function atInstitution(string $institutionName): static
    {
        return $this->state(function (array $attributes) use ($institutionName) {
            return [
                'institution_name' => $institutionName,
            ];
        });
    }

    public function graduatedIn(int $year): static
    {
        return $this->state(function (array $attributes) use ($year) {
            return [
                'end_year' => $year,
                'start_year' => $year - $this->faker->numberBetween(2, 6),
            ];
        });
    }

    public function bachelor(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'degree' => 'Bachelor of ' . $this->faker->randomElement(['Science', 'Arts']),
                'start_year' => $attributes['end_year'] - 4,
            ];
        });
    }

    public function master(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'degree' => 'Master of ' . $this->faker->randomElement(['Science', 'Arts', 'Business Administration']),
                'start_year' => $attributes['end_year'] - 2,
            ];
        });
    }

    public function phd(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'degree' => 'PhD',
                'start_year' => $attributes['end_year'] - $this->faker->numberBetween(4, 7),
            ];
        });
    }
}