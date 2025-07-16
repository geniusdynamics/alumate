<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\Employer;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition(): array
    {
        $jobTitles = [
            'Software Developer', 'Web Developer', 'Mobile App Developer', 'Data Analyst',
            'Cybersecurity Specialist', 'Digital Marketing Specialist', 'Graphic Designer',
            'Project Manager', 'Business Analyst', 'Network Administrator', 'Database Administrator',
            'UI/UX Designer', 'DevOps Engineer', 'Quality Assurance Tester', 'Technical Support Specialist'
        ];

        $title = $this->faker->randomElement($jobTitles);

        return [
            'employer_id' => Employer::factory(),
            'course_id' => Course::factory(),
            'title' => $title,
            'description' => $this->faker->paragraphs(3, true),
            'location' => $this->faker->city() . ', ' . $this->faker->state(),
            'required_skills' => $this->getSkillsForJob($title),
            'preferred_qualifications' => $this->faker->sentences(3),
            'experience_level' => $this->faker->randomElement(['entry', 'junior', 'mid', 'senior', 'executive']),
            'min_experience_years' => $this->faker->numberBetween(0, 8),
            'salary_min' => $this->faker->numberBetween(35000, 80000),
            'salary_max' => $this->faker->numberBetween(85000, 150000),
            'salary_type' => $this->faker->randomElement(['hourly', 'monthly', 'annually']),
            'job_type' => $this->faker->randomElement(['full_time', 'part_time', 'contract', 'internship', 'temporary']),
            'work_arrangement' => $this->faker->randomElement(['on_site', 'remote', 'hybrid']),
            'total_applications' => $this->faker->numberBetween(0, 50),
            'viewed_applications' => $this->faker->numberBetween(0, 30),
            'shortlisted_applications' => $this->faker->numberBetween(0, 10),
            'status' => $this->faker->randomElement(['draft', 'pending_approval', 'active', 'paused', 'filled', 'expired', 'cancelled']),
            'requires_approval' => $this->faker->boolean(30),
            'approved_at' => $this->faker->optional(0.7)->dateTimeBetween('-2 months', 'now'),
            'approved_by' => $this->faker->optional(0.7)->numberBetween(1, 10),
            'application_deadline' => $this->faker->optional(0.8)->dateTimeBetween('now', '+2 months'),
            'job_start_date' => $this->faker->optional(0.6)->dateTimeBetween('now', '+3 months'),
            'job_end_date' => $this->faker->optional(0.3)->dateTimeBetween('+3 months', '+1 year'),
            'employer_verified_required' => $this->faker->boolean(80),
            'matching_criteria' => [
                'course_match_weight' => 40,
                'skills_match_weight' => 30,
                'experience_weight' => 20,
                'location_weight' => 10,
            ],
            'view_count' => $this->faker->numberBetween(10, 500),
            'match_score' => $this->faker->optional(0.6)->randomFloat(2, 60, 95),
            'contact_email' => $this->faker->companyEmail(),
            'contact_phone' => $this->faker->optional(0.7)->phoneNumber(),
            'contact_person' => $this->faker->name(),
            'benefits' => $this->faker->randomElements([
                'Health Insurance', 'Dental Insurance', 'Vision Insurance',
                'Retirement Plan', 'Flexible Hours', 'Remote Work Options',
                'Professional Development', 'Paid Time Off', 'Performance Bonuses',
                'Stock Options', 'Gym Membership', 'Free Meals'
            ], $this->faker->numberBetween(3, 8)),
            'company_culture' => $this->faker->paragraph(2),
        ];
    }

    private function getSkillsForJob(string $jobTitle): array
    {
        $skillsMap = [
            'Software Developer' => ['PHP', 'JavaScript', 'Python', 'Java', 'SQL', 'Git'],
            'Web Developer' => ['HTML', 'CSS', 'JavaScript', 'React', 'Vue.js', 'PHP'],
            'Mobile App Developer' => ['Java', 'Kotlin', 'Swift', 'React Native', 'Flutter'],
            'Data Analyst' => ['Python', 'R', 'SQL', 'Excel', 'Tableau', 'Power BI'],
            'Cybersecurity Specialist' => ['Network Security', 'Penetration Testing', 'Risk Assessment'],
            'Digital Marketing Specialist' => ['SEO', 'Google Analytics', 'Social Media', 'Content Marketing'],
            'Graphic Designer' => ['Adobe Photoshop', 'Adobe Illustrator', 'InDesign', 'Figma'],
            'Project Manager' => ['Agile', 'Scrum', 'Project Planning', 'Risk Management'],
        ];

        return $skillsMap[$jobTitle] ?? ['Communication', 'Problem Solving', 'Teamwork'];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'approved_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'approved_by' => $this->faker->numberBetween(1, 10),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_approval',
            'requires_approval' => true,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    public function remote(): static
    {
        return $this->state(fn (array $attributes) => [
            'work_arrangement' => 'remote',
            'location' => 'Remote',
        ]);
    }

    public function entryLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'experience_level' => 'entry',
            'min_experience_years' => 0,
            'salary_min' => $this->faker->numberBetween(30000, 45000),
            'salary_max' => $this->faker->numberBetween(50000, 65000),
        ]);
    }

    public function senior(): static
    {
        return $this->state(fn (array $attributes) => [
            'experience_level' => 'senior',
            'min_experience_years' => $this->faker->numberBetween(5, 10),
            'salary_min' => $this->faker->numberBetween(80000, 120000),
            'salary_max' => $this->faker->numberBetween(130000, 200000),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'application_deadline' => $this->faker->dateTimeBetween('now', '+2 weeks'),
            'job_start_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
        ]);
    }
}