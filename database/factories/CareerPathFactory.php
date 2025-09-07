<?php

namespace Database\Factories;

use App\Models\CareerPath;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareerPathFactory extends Factory
{
    protected $model = CareerPath::class;

    public function definition(): array
    {
        $pathTypes = [
            CareerPath::PATH_LINEAR,
            CareerPath::PATH_PIVOT,
            CareerPath::PATH_ENTREPRENEURIAL,
            CareerPath::PATH_PORTFOLIO,
            CareerPath::PATH_ACADEMIC,
        ];

        $pathType = $this->faker->randomElement($pathTypes);
        $totalJobChanges = $this->faker->numberBetween(0, 8);
        $promotions = $this->faker->numberBetween(0, min($totalJobChanges + 2, 6));
        $industryChanges = $this->faker->numberBetween(0, min($totalJobChanges, 3));

        $progressionStages = $this->generateProgressionStages($pathType, $totalJobChanges);

        return [
            'user_id' => User::factory(),
            'path_type' => $pathType,
            'progression_stages' => $progressionStages,
            'total_job_changes' => $totalJobChanges,
            'promotions_count' => $promotions,
            'industry_changes' => $industryChanges,
            'salary_growth_rate' => $this->faker->randomFloat(2, -10, 50),
            'years_to_leadership' => $this->faker->optional(0.3)->numberBetween(3, 12),
            'skills_evolution' => $this->generateSkillsEvolution(),
        ];
    }

    private function generateProgressionStages(string $pathType, int $jobChanges): array
    {
        $stages = [];
        $positions = [
            'Junior Developer', 'Software Engineer', 'Senior Developer', 'Team Lead',
            'Engineering Manager', 'Director of Engineering', 'VP of Engineering',
            'Analyst', 'Senior Analyst', 'Manager', 'Senior Manager', 'Director',
            'Associate', 'Specialist', 'Senior Specialist', 'Principal',
        ];

        for ($i = 0; $i <= $jobChanges; $i++) {
            $stages[] = [
                'position' => $this->faker->randomElement($positions),
                'company' => $this->faker->company(),
                'start_date' => $this->faker->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
                'duration_months' => $this->faker->numberBetween(6, 48),
                'is_promotion' => $i > 0 && $this->faker->boolean(30),
                'salary_change_percent' => $this->faker->randomFloat(2, -5, 25),
            ];
        }

        return $stages;
    }

    private function generateSkillsEvolution(): array
    {
        $skills = [
            'JavaScript', 'Python', 'Java', 'C++', 'React', 'Vue.js', 'Angular',
            'Node.js', 'PHP', 'Laravel', 'Django', 'Spring Boot', 'AWS', 'Azure',
            'Docker', 'Kubernetes', 'Git', 'SQL', 'MongoDB', 'PostgreSQL',
            'Project Management', 'Leadership', 'Communication', 'Problem Solving',
        ];

        $evolution = [];
        $timePoints = ['entry_level', '2_years', '5_years', '10_years'];

        foreach ($timePoints as $point) {
            $evolution[$point] = $this->faker->randomElements(
                $skills,
                $this->faker->numberBetween(3, 8)
            );
        }

        return $evolution;
    }
}
