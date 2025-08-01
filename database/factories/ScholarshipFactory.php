<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Scholarship>
 */
class ScholarshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $applicationDeadline = fake()->dateTimeBetween('+1 month', '+6 months');
        $awardDate = fake()->dateTimeBetween($applicationDeadline, '+8 months');
        
        return [
            'name' => fake()->words(3, true) . ' Scholarship',
            'description' => fake()->paragraphs(3, true),
            'amount' => fake()->randomFloat(2, 500, 10000),
            'type' => fake()->randomElement(['one_time', 'recurring', 'endowment']),
            'status' => fake()->randomElement(['draft', 'active', 'paused', 'closed']),
            'eligibility_criteria' => [
                'min_gpa' => fake()->randomFloat(2, 2.0, 4.0),
                'academic_year' => fake()->randomElement(['freshman', 'sophomore', 'junior', 'senior', 'graduate']),
                'field_of_study' => fake()->randomElement(['Engineering', 'Business', 'Liberal Arts', 'Sciences']),
                'financial_need' => fake()->boolean(),
            ],
            'application_requirements' => [
                'personal_statement' => true,
                'transcripts' => true,
                'letters_of_recommendation' => fake()->numberBetween(1, 3),
                'financial_documents' => fake()->boolean(),
                'portfolio' => fake()->boolean(),
            ],
            'application_deadline' => $applicationDeadline,
            'award_date' => $awardDate,
            'max_recipients' => fake()->numberBetween(1, 5),
            'total_fund_amount' => fake()->randomFloat(2, 1000, 50000),
            'awarded_amount' => 0,
            'creator_id' => \App\Models\User::factory(),
            'institution_id' => null,
            'metadata' => [
                'donor_recognition' => fake()->boolean(),
                'renewable' => fake()->boolean(),
                'special_requirements' => fake()->optional()->sentence(),
            ],
        ];
    }
}
