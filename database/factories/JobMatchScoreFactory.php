<?php

namespace Database\Factories;

use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobMatchScore>
 */
class JobMatchScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $connectionScore = $this->faker->numberBetween(0, 100);
        $skillsScore = $this->faker->numberBetween(0, 100);
        $educationScore = $this->faker->numberBetween(0, 100);
        $circleScore = $this->faker->numberBetween(0, 100);

        // Calculate weighted overall score
        $overallScore = (
            ($connectionScore * 0.35) +
            ($skillsScore * 0.25) +
            ($educationScore * 0.20) +
            ($circleScore * 0.20)
        );

        $reasons = $this->generateMatchReasons($connectionScore, $skillsScore, $educationScore, $circleScore);

        return [
            'job_id' => JobPosting::factory(),
            'user_id' => User::factory(),
            'score' => round($overallScore, 2),
            'reasons' => $reasons,
            'calculated_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'connection_score' => $connectionScore,
            'skills_score' => $skillsScore,
            'education_score' => $educationScore,
            'circle_score' => $circleScore,
            'mutual_connections_count' => $this->faker->numberBetween(0, 5),
        ];
    }

    /**
     * Generate realistic match reasons based on scores.
     */
    private function generateMatchReasons(float $connectionScore, float $skillsScore, float $educationScore, float $circleScore): array
    {
        $reasons = [];

        if ($connectionScore > 0) {
            $connectionCount = max(1, intval($connectionScore / 20));
            $reasons[] = [
                'type' => 'connections',
                'reason' => "You have {$connectionCount} connection" . ($connectionCount > 1 ? 's' : '') . " at this company",
                'score' => $connectionScore,
                'details' => $this->faker->randomElements([
                    'John Smith - Senior Developer',
                    'Sarah Johnson - Product Manager',
                    'Mike Chen - Engineering Manager',
                    'Lisa Rodriguez - UX Designer',
                    'David Kim - Data Scientist'
                ], min($connectionCount, 3))
            ];
        }

        if ($skillsScore > 50) {
            $matchingSkills = $this->faker->randomElements([
                'PHP', 'Laravel', 'JavaScript', 'Vue.js', 'React',
                'Python', 'SQL', 'AWS', 'Docker', 'Git'
            ], $this->faker->numberBetween(2, 5));

            $reasons[] = [
                'type' => 'skills',
                'reason' => "Your skills match " . count($matchingSkills) . " of the required skills",
                'score' => $skillsScore,
                'details' => $matchingSkills
            ];
        }

        if ($educationScore > 50) {
            $reasons[] = [
                'type' => 'education',
                'reason' => "Your educational background is relevant to this role",
                'score' => $educationScore,
                'details' => $this->faker->randomElements([
                    'Bachelor of Computer Science',
                    'Master of Engineering',
                    'Bachelor of Information Technology'
                ], $this->faker->numberBetween(1, 2))
            ];
        }

        if ($circleScore > 0) {
            $reasons[] = [
                'type' => 'circles',
                'reason' => "You share alumni circles with employees at this company",
                'score' => $circleScore,
                'details' => $this->faker->randomElements([
                    'MIT Class of 2020',
                    'Stanford Engineering Alumni',
                    'UC Berkeley Computer Science'
                ], $this->faker->numberBetween(1, 2))
            ];
        }

        // Sort by score descending
        usort($reasons, fn($a, $b) => $b['score'] <=> $a['score']);

        return $reasons;
    }

    /**
     * Indicate that this is a high match score.
     */
    public function highMatch(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $this->faker->numberBetween(80, 100),
            'connection_score' => $this->faker->numberBetween(70, 100),
            'skills_score' => $this->faker->numberBetween(70, 100),
            'education_score' => $this->faker->numberBetween(60, 100),
            'circle_score' => $this->faker->numberBetween(50, 100),
            'mutual_connections_count' => $this->faker->numberBetween(2, 5),
        ]);
    }

    /**
     * Indicate that this is a medium match score.
     */
    public function mediumMatch(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $this->faker->numberBetween(60, 79),
            'connection_score' => $this->faker->numberBetween(40, 80),
            'skills_score' => $this->faker->numberBetween(50, 80),
            'education_score' => $this->faker->numberBetween(40, 70),
            'circle_score' => $this->faker->numberBetween(30, 70),
            'mutual_connections_count' => $this->faker->numberBetween(1, 3),
        ]);
    }

    /**
     * Indicate that this is a low match score.
     */
    public function lowMatch(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $this->faker->numberBetween(20, 59),
            'connection_score' => $this->faker->numberBetween(0, 40),
            'skills_score' => $this->faker->numberBetween(20, 60),
            'education_score' => $this->faker->numberBetween(10, 50),
            'circle_score' => $this->faker->numberBetween(0, 40),
            'mutual_connections_count' => $this->faker->numberBetween(0, 1),
        ]);
    }

    /**
     * Indicate that this match score was calculated recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'calculated_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Indicate that this match score is stale.
     */
    public function stale(): static
    {
        return $this->state(fn (array $attributes) => [
            'calculated_at' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
        ]);
    }
}