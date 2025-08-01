<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScholarshipReview>
 */
class ScholarshipReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $score = fake()->randomFloat(2, 60, 100);
        $recommendation = $score >= 80 ? 'approve' : ($score >= 70 ? 'needs_more_info' : 'reject');
        
        return [
            'application_id' => \App\Models\ScholarshipApplication::factory(),
            'reviewer_id' => \App\Models\User::factory(),
            'score' => $score,
            'comments' => fake()->paragraphs(2, true),
            'criteria_scores' => [
                'academic_performance' => fake()->randomFloat(1, 7.0, 10.0),
                'financial_need' => fake()->randomFloat(1, 6.0, 10.0),
                'personal_statement' => fake()->randomFloat(1, 7.0, 10.0),
                'extracurricular_activities' => fake()->randomFloat(1, 6.0, 9.0),
                'leadership_potential' => fake()->randomFloat(1, 6.0, 9.0),
                'community_involvement' => fake()->randomFloat(1, 5.0, 9.0),
            ],
            'recommendation' => $recommendation,
            'feedback_for_applicant' => fake()->boolean(60) ? fake()->paragraph() : null,
        ];
    }
}
