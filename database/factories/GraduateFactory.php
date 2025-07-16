<?php

namespace Database\Factories;

use App\Models\Graduate;
use App\Models\Course;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class GraduateFactory extends Factory
{
    protected $model = Graduate::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'student_id' => $this->faker->unique()->numerify('STU####'),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'graduation_year' => $this->faker->numberBetween(2020, 2024),
            'course_id' => Course::factory(),
            'gpa' => $this->faker->randomFloat(2, 2.0, 4.0),
            'academic_standing' => $this->faker->randomElement(['excellent', 'very_good', 'good', 'satisfactory', 'pass']),
            'employment_status' => $this->faker->randomElement(['unemployed', 'employed', 'self_employed', 'further_studies', 'other']),
            'current_job_title' => $this->faker->optional(0.6)->jobTitle(),
            'current_company' => $this->faker->optional(0.6)->company(),
            'current_salary' => $this->faker->optional(0.6)->numberBetween(30000, 120000),
            'employment_start_date' => $this->faker->optional(0.6)->dateTimeBetween('-2 years', 'now'),
            'profile_completion_percentage' => $this->faker->numberBetween(40, 100),
            'profile_completion_fields' => $this->faker->randomElements(['name', 'email', 'phone', 'address', 'course_id'], $this->faker->numberBetween(3, 5)),
            'privacy_settings' => [
                'show_contact_info' => $this->faker->boolean(80),
                'show_employment_status' => $this->faker->boolean(90),
                'allow_employer_contact' => $this->faker->boolean(85),
            ],
            'skills' => $this->faker->randomElements([
                'PHP', 'JavaScript', 'Python', 'Java', 'C++', 'HTML/CSS', 'React', 'Vue.js', 
                'Node.js', 'Laravel', 'Django', 'Spring Boot', 'MySQL', 'PostgreSQL', 
                'MongoDB', 'Git', 'Docker', 'AWS', 'Azure'
            ], $this->faker->numberBetween(3, 8)),
            'certifications' => $this->faker->optional(0.4)->randomElements([
                'AWS Certified Developer', 'Google Cloud Professional', 'Microsoft Azure Fundamentals',
                'Cisco CCNA', 'CompTIA Security+', 'Oracle Certified Professional'
            ], $this->faker->numberBetween(1, 3)),
            'allow_employer_contact' => $this->faker->boolean(85),
            'job_search_active' => $this->faker->boolean(70),
            'last_profile_update' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'last_employment_update' => $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function employed(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'employed',
            'current_job_title' => $this->faker->jobTitle(),
            'current_company' => $this->faker->company(),
            'current_salary' => $this->faker->numberBetween(40000, 100000),
            'employment_start_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
        ]);
    }

    public function unemployed(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'unemployed',
            'current_job_title' => null,
            'current_company' => null,
            'current_salary' => null,
            'employment_start_date' => null,
            'job_search_active' => true,
        ]);
    }

    public function highPerformer(): static
    {
        return $this->state(fn (array $attributes) => [
            'gpa' => $this->faker->randomFloat(2, 3.5, 4.0),
            'academic_standing' => $this->faker->randomElement(['excellent', 'very_good']),
            'profile_completion_percentage' => $this->faker->numberBetween(85, 100),
        ]);
    }
}