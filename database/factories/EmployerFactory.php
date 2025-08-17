<?php

namespace Database\Factories;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployerFactory extends Factory
{
    protected $model = Employer::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_name' => $this->faker->company(),
            'company_address' => $this->faker->address(),
            'company_phone' => $this->faker->phoneNumber(),
            'approved' => $this->faker->boolean(70),
            'verification_status' => $this->faker->randomElement(['pending', 'under_review', 'verified', 'rejected']),
            'verification_documents' => [
                'business_registration' => 'documents/business_reg_'.$this->faker->uuid().'.pdf',
                'tax_certificate' => 'documents/tax_cert_'.$this->faker->uuid().'.pdf',
            ],
            'verification_submitted_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'verification_completed_at' => $this->faker->optional(0.7)->dateTimeBetween('-2 months', 'now'),
            'verified_by' => $this->faker->optional(0.7)->numberBetween(1, 10),
            'verification_notes' => $this->faker->optional(0.5)->sentence(),
            'rejection_reason' => $this->faker->optional(0.2)->sentence(),
            'company_registration_number' => $this->faker->numerify('REG########'),
            'company_tax_number' => $this->faker->numerify('TAX########'),
            'company_website' => $this->faker->optional(0.8)->url(),
            'company_size' => $this->faker->randomElement(['startup', 'small', 'medium', 'large', 'enterprise']),
            'industry' => $this->faker->randomElement([
                'Technology', 'Healthcare', 'Finance', 'Education', 'Manufacturing',
                'Retail', 'Construction', 'Hospitality', 'Transportation', 'Government',
            ]),
            'company_description' => $this->faker->paragraph(3),
            'contact_person_name' => $this->faker->name(),
            'contact_person_title' => $this->faker->jobTitle(),
            'contact_person_email' => $this->faker->companyEmail(),
            'contact_person_phone' => $this->faker->phoneNumber(),
            'established_year' => $this->faker->numberBetween(1990, 2020),
            'employee_count' => $this->faker->numberBetween(5, 1000),
            'business_locations' => [
                $this->faker->city(),
                $this->faker->optional(0.3)->city(),
            ],
            'services_products' => $this->faker->sentences(3),
            'total_jobs_posted' => $this->faker->numberBetween(0, 50),
            'active_jobs_count' => $this->faker->numberBetween(0, 10),
            'total_hires' => $this->faker->numberBetween(0, 25),
            'average_time_to_hire' => $this->faker->randomFloat(2, 15, 60),
            'employer_rating' => $this->faker->optional(0.6)->randomFloat(2, 3.0, 5.0),
            'total_reviews' => $this->faker->numberBetween(0, 20),
            'employer_benefits' => $this->faker->randomElements([
                'Health Insurance', 'Dental Insurance', 'Retirement Plan',
                'Flexible Hours', 'Remote Work', 'Professional Development',
                'Paid Time Off', 'Performance Bonuses',
            ], $this->faker->numberBetween(2, 6)),
            'subscription_plan' => $this->faker->randomElement(['free', 'basic', 'premium', 'enterprise']),
            'subscription_expires_at' => $this->faker->optional(0.6)->dateTimeBetween('now', '+1 year'),
            'job_posting_limit' => $this->faker->randomElement([5, 10, 25, 100]),
            'jobs_posted_this_month' => $this->faker->numberBetween(0, 8),
            'is_active' => $this->faker->boolean(85),
            'can_post_jobs' => $this->faker->boolean(70),
            'can_search_graduates' => $this->faker->boolean(70),
            'notification_preferences' => [
                'email_notifications' => $this->faker->boolean(80),
                'sms_notifications' => $this->faker->boolean(40),
                'application_alerts' => $this->faker->boolean(90),
            ],
            'terms_accepted' => $this->faker->boolean(95),
            'terms_accepted_at' => $this->faker->optional(0.95)->dateTimeBetween('-1 year', 'now'),
            'privacy_policy_accepted' => $this->faker->boolean(95),
            'privacy_policy_accepted_at' => $this->faker->optional(0.95)->dateTimeBetween('-1 year', 'now'),
            'last_login_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 month', 'now'),
            'last_job_posted_at' => $this->faker->optional(0.6)->dateTimeBetween('-3 months', 'now'),
            'profile_completed_at' => $this->faker->optional(0.7)->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'verified',
            'approved' => true,
            'verification_completed_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'verified_by' => $this->faker->numberBetween(1, 10),
            'can_post_jobs' => true,
            'can_search_graduates' => true,
            'is_active' => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'pending',
            'approved' => false,
            'verification_completed_at' => null,
            'verified_by' => null,
            'can_post_jobs' => false,
            'can_search_graduates' => false,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'rejected',
            'approved' => false,
            'verification_completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'verified_by' => $this->faker->numberBetween(1, 10),
            'rejection_reason' => $this->faker->sentence(),
            'can_post_jobs' => false,
            'can_search_graduates' => false,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_plan' => 'premium',
            'subscription_expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
            'job_posting_limit' => 25,
            'can_post_jobs' => true,
            'can_search_graduates' => true,
        ]);
    }
}
