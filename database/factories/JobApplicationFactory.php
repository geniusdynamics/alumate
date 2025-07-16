<?php

namespace Database\Factories;

use App\Models\JobApplication;
use App\Models\Job;
use App\Models\Graduate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobApplicationFactory extends Factory
{
    protected $model = JobApplication::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement([
            'pending', 'reviewed', 'shortlisted', 'interview_scheduled',
            'interviewed', 'reference_check', 'offer_made', 'offer_accepted',
            'offer_declined', 'hired', 'rejected', 'withdrawn'
        ]);

        return [
            'job_id' => Job::factory(),
            'graduate_id' => Graduate::factory(),
            'cover_letter' => $this->faker->paragraphs(3, true),
            'status' => $status,
            'status_history' => $this->generateStatusHistory($status),
            'status_changed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'status_changed_by' => $this->faker->optional(0.8)->numberBetween(1, 10),
            'resume_data' => [
                'education' => $this->faker->sentences(2),
                'experience' => $this->faker->sentences(3),
                'skills' => $this->faker->words(8),
                'certifications' => $this->faker->optional(0.6)->sentences(2),
            ],
            'resume_file_path' => 'resumes/' . $this->faker->uuid() . '.pdf',
            'additional_documents' => $this->faker->optional(0.3)->randomElements([
                'portfolio.pdf', 'certificates.pdf', 'references.pdf'
            ], $this->faker->numberBetween(1, 2)),
            'interview_scheduled_at' => $this->getInterviewDate($status),
            'interview_location' => $this->getInterviewLocation($status),
            'interview_notes' => $this->getInterviewNotes($status),
            'assessment_scores' => $this->getAssessmentScores($status),
            'employer_feedback' => $this->getEmployerFeedback($status),
            'employer_rating' => $this->getEmployerRating($status),
            'rejection_reason' => $this->getRejectionReason($status),
            'offered_salary' => $this->getOfferedSalary($status),
            'offer_expiry_date' => $this->getOfferExpiryDate($status),
            'offer_terms' => $this->getOfferTerms($status),
            'graduate_response' => $this->getGraduateResponse($status),
            'graduate_responded_at' => $this->getGraduateResponseDate($status),
            'match_score' => $this->faker->randomFloat(2, 60, 95),
            'matching_factors' => [
                'course_match' => $this->faker->boolean(80),
                'skills_match' => $this->faker->numberBetween(2, 8),
                'profile_completion' => $this->faker->numberBetween(70, 100),
                'gpa' => $this->faker->optional(0.8)->randomFloat(2, 2.5, 4.0),
            ],
            'messages_count' => $this->faker->numberBetween(0, 15),
            'last_message_at' => $this->faker->optional(0.7)->dateTimeBetween('-2 weeks', 'now'),
            'application_source' => $this->faker->randomElement(['direct', 'recommendation', 'search', 'notification', 'referral']),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
            'is_flagged' => $this->faker->boolean(10),
            'flag_reason' => $this->faker->optional(0.1)->sentence(),
        ];
    }

    private function generateStatusHistory(string $currentStatus): array
    {
        $statusFlow = [
            'pending' => ['pending'],
            'reviewed' => ['pending', 'reviewed'],
            'shortlisted' => ['pending', 'reviewed', 'shortlisted'],
            'interview_scheduled' => ['pending', 'reviewed', 'shortlisted', 'interview_scheduled'],
            'interviewed' => ['pending', 'reviewed', 'shortlisted', 'interview_scheduled', 'interviewed'],
            'offer_made' => ['pending', 'reviewed', 'shortlisted', 'interview_scheduled', 'interviewed', 'offer_made'],
            'offer_accepted' => ['pending', 'reviewed', 'shortlisted', 'interview_scheduled', 'interviewed', 'offer_made', 'offer_accepted'],
            'hired' => ['pending', 'reviewed', 'shortlisted', 'interview_scheduled', 'interviewed', 'offer_made', 'offer_accepted', 'hired'],
            'rejected' => ['pending', 'reviewed', 'rejected'],
            'withdrawn' => ['pending', 'withdrawn'],
        ];

        $statuses = $statusFlow[$currentStatus] ?? ['pending'];
        $history = [];

        foreach ($statuses as $index => $status) {
            $history[] = [
                'from' => $index > 0 ? $statuses[$index - 1] : null,
                'to' => $status,
                'changed_at' => $this->faker->dateTimeBetween('-2 months', 'now')->toISOString(),
                'changed_by' => $this->faker->numberBetween(1, 10),
                'notes' => $this->faker->optional(0.3)->sentence(),
            ];
        }

        return $history;
    }

    private function getInterviewDate(string $status): ?\DateTime
    {
        return in_array($status, ['interview_scheduled', 'interviewed', 'offer_made', 'offer_accepted', 'hired'])
            ? $this->faker->dateTimeBetween('-1 month', '+1 month')
            : null;
    }

    private function getInterviewLocation(string $status): ?string
    {
        return in_array($status, ['interview_scheduled', 'interviewed', 'offer_made', 'offer_accepted', 'hired'])
            ? $this->faker->randomElement(['Office', 'Video Call', 'Phone Call', 'Coffee Shop'])
            : null;
    }

    private function getInterviewNotes(string $status): ?string
    {
        return in_array($status, ['interviewed', 'offer_made', 'offer_accepted', 'hired'])
            ? $this->faker->paragraph()
            : null;
    }

    private function getAssessmentScores(string $status): ?array
    {
        return in_array($status, ['interviewed', 'offer_made', 'offer_accepted', 'hired'])
            ? [
                'technical_skills' => $this->faker->numberBetween(60, 100),
                'communication' => $this->faker->numberBetween(70, 100),
                'problem_solving' => $this->faker->numberBetween(65, 95),
                'cultural_fit' => $this->faker->numberBetween(70, 100),
            ]
            : null;
    }

    private function getEmployerFeedback(string $status): ?string
    {
        return in_array($status, ['reviewed', 'shortlisted', 'interviewed', 'offer_made', 'rejected'])
            ? $this->faker->paragraph()
            : null;
    }

    private function getEmployerRating(string $status): ?int
    {
        return in_array($status, ['interviewed', 'offer_made', 'offer_accepted', 'hired'])
            ? $this->faker->numberBetween(3, 5)
            : null;
    }

    private function getRejectionReason(string $status): ?string
    {
        return $status === 'rejected'
            ? $this->faker->randomElement([
                'Insufficient experience',
                'Skills mismatch',
                'Position filled',
                'Budget constraints',
                'Not a cultural fit'
            ])
            : null;
    }

    private function getOfferedSalary(string $status): ?float
    {
        return in_array($status, ['offer_made', 'offer_accepted', 'offer_declined', 'hired'])
            ? $this->faker->numberBetween(40000, 120000)
            : null;
    }

    private function getOfferExpiryDate(string $status): ?\DateTime
    {
        return in_array($status, ['offer_made', 'offer_accepted', 'offer_declined'])
            ? $this->faker->dateTimeBetween('now', '+2 weeks')
            : null;
    }

    private function getOfferTerms(string $status): ?array
    {
        return in_array($status, ['offer_made', 'offer_accepted', 'offer_declined', 'hired'])
            ? [
                'start_date' => $this->faker->dateTimeBetween('+1 week', '+1 month')->format('Y-m-d'),
                'probation_period' => $this->faker->randomElement(['3 months', '6 months']),
                'benefits' => $this->faker->randomElements(['Health Insurance', 'Dental', 'Retirement Plan'], 2),
            ]
            : null;
    }

    private function getGraduateResponse(string $status): ?string
    {
        return in_array($status, ['offer_accepted', 'offer_declined'])
            ? $this->faker->paragraph()
            : null;
    }

    private function getGraduateResponseDate(string $status): ?\DateTime
    {
        return in_array($status, ['offer_accepted', 'offer_declined'])
            ? $this->faker->dateTimeBetween('-1 week', 'now')
            : null;
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'status_history' => [
                [
                    'from' => null,
                    'to' => 'pending',
                    'changed_at' => now()->toISOString(),
                    'changed_by' => null,
                    'notes' => 'Application submitted',
                ]
            ],
        ]);
    }

    public function hired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'hired',
            'offered_salary' => $this->faker->numberBetween(50000, 100000),
            'employer_rating' => $this->faker->numberBetween(4, 5),
            'employer_feedback' => 'Excellent candidate, great fit for the role.',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejection_reason' => $this->faker->randomElement([
                'Insufficient experience',
                'Skills mismatch',
                'Position filled',
            ]),
            'employer_feedback' => $this->faker->sentence(),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
            'match_score' => $this->faker->randomFloat(2, 85, 95),
        ]);
    }

    public function flagged(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_flagged' => true,
            'flag_reason' => $this->faker->randomElement([
                'Duplicate application',
                'Incomplete information',
                'Suspicious activity',
            ]),
        ]);
    }
}