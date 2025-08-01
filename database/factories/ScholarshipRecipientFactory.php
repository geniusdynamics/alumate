<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScholarshipRecipient>
 */
class ScholarshipRecipientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $awardDate = fake()->dateTimeBetween('-3 years', '-1 month');
        
        return [
            'scholarship_id' => \App\Models\Scholarship::factory(),
            'application_id' => \App\Models\ScholarshipApplication::factory(),
            'recipient_id' => \App\Models\User::factory(),
            'awarded_amount' => fake()->randomFloat(2, 1000, 10000),
            'award_date' => $awardDate,
            'status' => fake()->randomElement(['awarded', 'active', 'completed', 'revoked']),
            'success_story' => fake()->boolean(70) ? fake()->paragraphs(3, true) : null,
            'academic_progress' => [
                'current_gpa' => fake()->randomFloat(2, 3.0, 4.0),
                'graduation_status' => fake()->randomElement(['enrolled', 'graduated', 'on_track', 'delayed']),
                'academic_achievements' => fake()->boolean() ? [
                    fake()->sentence(),
                    fake()->sentence()
                ] : [],
                'courses_completed' => fake()->numberBetween(20, 120),
            ],
            'impact_metrics' => [
                'career_advancement' => fake()->randomElement([
                    'Secured internship at Fortune 500 company',
                    'Promoted to team lead position',
                    'Started own business',
                    'Accepted to graduate program',
                    'Published research paper'
                ]),
                'community_involvement' => fake()->randomElement([
                    'Volunteers at local food bank',
                    'Mentors high school students',
                    'Organizes community events',
                    'Participates in alumni network',
                    'Leads student organization'
                ]),
                'financial_impact' => fake()->randomElement([
                    'Reduced student debt by 50%',
                    'Able to focus on studies full-time',
                    'Avoided taking additional loans',
                    'Supported family financially'
                ])
            ],
            'thank_you_message' => fake()->boolean(60) ? fake()->paragraph() : null,
            'updates' => fake()->boolean(40) ? [
                [
                    'title' => 'Academic Achievement',
                    'description' => fake()->sentence(),
                    'date' => fake()->dateTimeBetween($awardDate, 'now')->format('Y-m-d')
                ],
                [
                    'title' => 'Career Update',
                    'description' => fake()->sentence(),
                    'date' => fake()->dateTimeBetween($awardDate, 'now')->format('Y-m-d')
                ]
            ] : null,
        ];
    }
}
