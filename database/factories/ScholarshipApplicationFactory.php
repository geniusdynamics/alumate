<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScholarshipApplication>
 */
class ScholarshipApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $submittedAt = fake()->boolean(80) ? fake()->dateTimeBetween('-2 months', 'now') : null;

        return [
            'scholarship_id' => \App\Models\Scholarship::factory(),
            'applicant_id' => \App\Models\User::factory(),
            'status' => fake()->randomElement(['draft', 'submitted', 'under_review', 'approved', 'rejected', 'awarded']),
            'application_data' => [
                'academic_year' => fake()->randomElement(['freshman', 'sophomore', 'junior', 'senior', 'graduate']),
                'field_of_study' => fake()->randomElement(['Engineering', 'Business', 'Liberal Arts', 'Sciences', 'Medicine']),
                'expected_graduation' => fake()->dateTimeBetween('+1 year', '+4 years')->format('Y-m-d'),
                'current_institution' => fake()->company().' University',
            ],
            'documents' => [
                'transcript' => fake()->boolean() ? 'transcripts/transcript_'.fake()->uuid().'.pdf' : null,
                'recommendation_letters' => fake()->boolean() ? [
                    'letters/letter1_'.fake()->uuid().'.pdf',
                    'letters/letter2_'.fake()->uuid().'.pdf',
                ] : [],
                'portfolio' => fake()->boolean() ? 'portfolios/portfolio_'.fake()->uuid().'.pdf' : null,
            ],
            'personal_statement' => fake()->paragraphs(4, true),
            'gpa' => fake()->randomFloat(2, 2.0, 4.0),
            'financial_need_statement' => fake()->boolean(60) ? fake()->paragraphs(2, true) : null,
            'references' => [
                [
                    'name' => fake()->name(),
                    'title' => fake()->jobTitle(),
                    'email' => fake()->safeEmail(),
                    'phone' => fake()->phoneNumber(),
                    'relationship' => fake()->randomElement(['Professor', 'Supervisor', 'Mentor', 'Advisor']),
                ],
                [
                    'name' => fake()->name(),
                    'title' => fake()->jobTitle(),
                    'email' => fake()->safeEmail(),
                    'phone' => fake()->phoneNumber(),
                    'relationship' => fake()->randomElement(['Professor', 'Supervisor', 'Mentor', 'Advisor']),
                ],
            ],
            'submitted_at' => $submittedAt,
            'admin_notes' => fake()->boolean(30) ? fake()->sentence() : null,
            'score' => fake()->boolean(40) ? fake()->randomFloat(2, 60, 100) : null,
        ];
    }
}
