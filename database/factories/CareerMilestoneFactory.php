<?php

namespace Database\Factories;

use App\Models\CareerMilestone;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareerMilestoneFactory extends Factory
{
    protected $model = CareerMilestone::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement([
            CareerMilestone::TYPE_PROMOTION,
            CareerMilestone::TYPE_JOB_CHANGE,
            CareerMilestone::TYPE_AWARD,
            CareerMilestone::TYPE_CERTIFICATION,
            CareerMilestone::TYPE_EDUCATION,
            CareerMilestone::TYPE_ACHIEVEMENT
        ]);

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'title' => $this->getTitleForType($type),
            'description' => $this->getDescriptionForType($type),
            'date' => $this->faker->dateTimeBetween('-3 years', 'now'),
            'visibility' => $this->faker->randomElement([
                CareerMilestone::VISIBILITY_PUBLIC,
                CareerMilestone::VISIBILITY_CONNECTIONS,
                CareerMilestone::VISIBILITY_PRIVATE
            ]),
            'company' => $this->faker->boolean(60) ? $this->faker->company() : null,
            'organization' => $this->faker->boolean(40) ? $this->faker->company() . ' Institute' : null,
            'metadata' => $this->getMetadataForType($type),
            'is_featured' => $this->faker->boolean(20)
        ];
    }

    public function promotion(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CareerMilestone::TYPE_PROMOTION,
            'title' => 'Promoted to ' . $this->faker->jobTitle(),
            'description' => 'Received promotion in recognition of outstanding performance and leadership.',
            'company' => $this->faker->company()
        ]);
    }

    public function jobChange(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CareerMilestone::TYPE_JOB_CHANGE,
            'title' => 'Started new role at ' . $this->faker->company(),
            'description' => 'Joined new company as ' . $this->faker->jobTitle(),
            'company' => $this->faker->company()
        ]);
    }

    public function award(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CareerMilestone::TYPE_AWARD,
            'title' => $this->faker->randomElement([
                'Employee of the Year',
                'Innovation Award',
                'Excellence in Leadership',
                'Outstanding Performance Award',
                'Customer Service Excellence'
            ]),
            'description' => 'Recognized for exceptional contributions and achievements.',
            'company' => $this->faker->company()
        ]);
    }

    public function certification(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CareerMilestone::TYPE_CERTIFICATION,
            'title' => $this->faker->randomElement([
                'AWS Solutions Architect',
                'PMP Certification',
                'Scrum Master Certification',
                'Google Cloud Professional',
                'Microsoft Azure Expert',
                'Salesforce Administrator'
            ]),
            'description' => 'Successfully completed certification requirements and passed examination.',
            'organization' => $this->faker->randomElement([
                'Amazon Web Services',
                'Project Management Institute',
                'Scrum Alliance',
                'Google Cloud',
                'Microsoft',
                'Salesforce'
            ])
        ]);
    }

    public function education(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CareerMilestone::TYPE_EDUCATION,
            'title' => $this->faker->randomElement([
                'Master of Business Administration',
                'Master of Science in Computer Science',
                'Bachelor of Engineering',
                'Professional Development Program',
                'Executive Leadership Program'
            ]),
            'description' => 'Completed advanced education program with distinction.',
            'organization' => $this->faker->randomElement([
                'Stanford University',
                'MIT',
                'Harvard Business School',
                'UC Berkeley',
                'Carnegie Mellon University'
            ])
        ]);
    }

    public function achievement(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CareerMilestone::TYPE_ACHIEVEMENT,
            'title' => $this->faker->randomElement([
                'Led successful product launch',
                'Increased team productivity by 40%',
                'Completed major system migration',
                'Established new department',
                'Secured major client contract'
            ]),
            'description' => 'Achieved significant milestone through dedication and hard work.',
            'company' => $this->faker->company()
        ]);
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => CareerMilestone::VISIBILITY_PUBLIC
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => CareerMilestone::VISIBILITY_PRIVATE
        ]);
    }

    public function connectionsOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => CareerMilestone::VISIBILITY_CONNECTIONS
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true
        ]);
    }

    private function getTitleForType(string $type): string
    {
        return match($type) {
            CareerMilestone::TYPE_PROMOTION => 'Promoted to ' . $this->faker->jobTitle(),
            CareerMilestone::TYPE_JOB_CHANGE => 'Started new role at ' . $this->faker->company(),
            CareerMilestone::TYPE_AWARD => $this->faker->randomElement([
                'Employee of the Year',
                'Innovation Award',
                'Excellence Award'
            ]),
            CareerMilestone::TYPE_CERTIFICATION => $this->faker->randomElement([
                'AWS Solutions Architect',
                'PMP Certification',
                'Scrum Master Certification'
            ]),
            CareerMilestone::TYPE_EDUCATION => $this->faker->randomElement([
                'Master of Business Administration',
                'Master of Science',
                'Professional Development Program'
            ]),
            CareerMilestone::TYPE_ACHIEVEMENT => $this->faker->randomElement([
                'Led successful product launch',
                'Increased team productivity',
                'Completed major project'
            ]),
            default => 'Career Milestone'
        };
    }

    private function getDescriptionForType(string $type): string
    {
        return match($type) {
            CareerMilestone::TYPE_PROMOTION => 'Received promotion in recognition of outstanding performance.',
            CareerMilestone::TYPE_JOB_CHANGE => 'Joined new company in an exciting role.',
            CareerMilestone::TYPE_AWARD => 'Recognized for exceptional contributions and achievements.',
            CareerMilestone::TYPE_CERTIFICATION => 'Successfully completed certification requirements.',
            CareerMilestone::TYPE_EDUCATION => 'Completed advanced education program.',
            CareerMilestone::TYPE_ACHIEVEMENT => 'Achieved significant milestone through hard work.',
            default => 'Important career milestone.'
        };
    }

    private function getMetadataForType(string $type): array
    {
        return match($type) {
            CareerMilestone::TYPE_CERTIFICATION => [
                'credential_id' => $this->faker->uuid(),
                'expiry_date' => $this->faker->dateTimeBetween('now', '+3 years')->format('Y-m-d'),
                'score' => $this->faker->numberBetween(80, 100) . '%'
            ],
            CareerMilestone::TYPE_EDUCATION => [
                'gpa' => $this->faker->randomFloat(2, 3.0, 4.0),
                'honors' => $this->faker->randomElement(['Summa Cum Laude', 'Magna Cum Laude', 'Cum Laude']),
                'field_of_study' => $this->faker->randomElement([
                    'Computer Science',
                    'Business Administration',
                    'Engineering',
                    'Data Science'
                ])
            ],
            CareerMilestone::TYPE_AWARD => [
                'award_value' => '$' . $this->faker->numberBetween(1000, 10000),
                'selection_criteria' => 'Performance, Leadership, Innovation'
            ],
            default => []
        };
    }
}