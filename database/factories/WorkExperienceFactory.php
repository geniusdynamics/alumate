<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkExperience;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkExperienceFactory extends Factory
{
    protected $model = WorkExperience::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-10 years', '-1 year');
        $isCurrent = $this->faker->boolean(30);
        $endDate = $isCurrent ? null : $this->faker->dateTimeBetween($startDate, 'now');

        return [
            'user_id' => User::factory(),
            'company' => $this->faker->company(),
            'title' => $this->faker->jobTitle(),
            'description' => $this->faker->optional(0.7)->paragraph(2),
            'industry' => $this->faker->randomElement([
                'Technology',
                'Healthcare',
                'Finance',
                'Education',
                'Retail',
                'Manufacturing',
                'Consulting',
                'Media',
                'Non-profit',
                'Government',
                'Real Estate',
                'Transportation',
                'Energy',
                'Telecommunications',
                'Hospitality',
                'Agriculture',
            ]),
            'location' => $this->faker->city().', '.$this->faker->stateAbbr(),
            'employment_type' => $this->faker->randomElement(['full_time', 'part_time', 'contract', 'internship', 'freelance']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_current' => $isCurrent,
            'skills_used' => json_encode($this->faker->randomElements([
                'JavaScript',
                'Python',
                'Java',
                'PHP',
                'React',
                'Vue.js',
                'Node.js',
                'Laravel',
                'Django',
                'SQL',
                'MongoDB',
                'AWS',
                'Docker',
                'Kubernetes',
                'Project Management',
                'Team Leadership',
                'Data Analysis',
                'Marketing',
                'Sales',
                'Customer Service',
                'Strategic Planning',
                'Budget Management',
                'Public Speaking',
                'Writing',
                'Design',
                'Research',
            ], $this->faker->numberBetween(2, 8))),
            'achievements' => json_encode($this->faker->optional(0.6)->sentences(rand(2, 4))),
        ];
    }

    public function current(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_current' => true,
                'end_date' => null,
                'start_date' => $this->faker->dateTimeBetween('-5 years', '-1 month'),
            ];
        });
    }

    public function past(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = $this->faker->dateTimeBetween('-10 years', '-2 years');

            return [
                'is_current' => false,
                'start_date' => $startDate,
                'end_date' => $this->faker->dateTimeBetween($startDate, '-1 month'),
            ];
        });
    }

    public function internship(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'employment_type' => 'internship',
                'title' => $this->faker->randomElement([
                    'Software Engineering Intern',
                    'Marketing Intern',
                    'Data Science Intern',
                    'Product Management Intern',
                    'Business Development Intern',
                    'Research Intern',
                ]),
                'is_current' => false,
            ];
        });
    }

    public function senior(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'title' => 'Senior '.$this->faker->jobTitle(),
                'start_date' => $this->faker->dateTimeBetween('-8 years', '-3 years'),
            ];
        });
    }

    public function leadership(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'title' => $this->faker->randomElement([
                    'Engineering Manager',
                    'Product Manager',
                    'Director of Marketing',
                    'VP of Sales',
                    'Team Lead',
                    'Department Head',
                    'Chief Technology Officer',
                    'Chief Executive Officer',
                ]),
                'skills_used' => json_encode(array_merge(
                    $this->faker->randomElements([
                        'Team Leadership',
                        'Strategic Planning',
                        'Budget Management',
                        'Project Management',
                        'Stakeholder Management',
                        'Performance Management',
                        'Hiring',
                        'Mentoring',
                    ], rand(3, 6)),
                    $this->faker->randomElements([
                        'JavaScript',
                        'Python',
                        'Data Analysis',
                        'Marketing',
                        'Sales',
                    ], rand(1, 3))
                )),
            ];
        });
    }
}
