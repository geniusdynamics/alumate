<?php

namespace Database\Factories;

use App\Models\CareerTimeline;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareerTimelineFactory extends Factory
{
    protected $model = CareerTimeline::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-5 years', '-1 year');
        $endDate = $this->faker->boolean(70) ? $this->faker->dateTimeBetween($startDate, 'now') : null;
        $isCurrent = is_null($endDate) && $this->faker->boolean(30);

        return [
            'user_id' => User::factory(),
            'company' => $this->faker->company(),
            'title' => $this->faker->jobTitle(),
            'start_date' => $startDate,
            'end_date' => $isCurrent ? null : $endDate,
            'description' => $this->faker->paragraph(3),
            'is_current' => $isCurrent,
            'achievements' => $this->faker->randomElements([
                'Led team of 5 developers',
                'Increased system performance by 40%',
                'Implemented new CI/CD pipeline',
                'Reduced bug reports by 60%',
                'Mentored 3 junior developers',
                'Delivered 10+ major features',
                'Improved code coverage to 90%',
                'Optimized database queries',
                'Built scalable microservices',
                'Established coding standards',
            ], $this->faker->numberBetween(0, 4)),
            'location' => $this->faker->city().', '.$this->faker->stateAbbr(),
            'company_logo_url' => $this->faker->boolean(30) ? $this->faker->imageUrl(100, 100, 'business') : null,
            'industry' => $this->faker->randomElement([
                'Technology',
                'Healthcare',
                'Finance',
                'Education',
                'Manufacturing',
                'Retail',
                'Consulting',
                'Media',
                'Government',
                'Non-profit',
            ]),
            'employment_type' => $this->faker->randomElement([
                'full-time',
                'part-time',
                'contract',
                'internship',
                'freelance',
            ]),
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
            'end_date' => null,
            'start_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
        ]);
    }

    public function past(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = $this->faker->dateTimeBetween('-5 years', '-1 year');
            $endDate = $this->faker->dateTimeBetween($startDate, 'now');

            return [
                'is_current' => false,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        });
    }

    public function withAchievements(int $count = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'achievements' => $this->faker->randomElements([
                'Led cross-functional team of 8 members',
                'Increased revenue by 25% through optimization',
                'Implemented automated testing framework',
                'Reduced deployment time from 2 hours to 15 minutes',
                'Mentored 5 junior team members',
                'Delivered project 2 weeks ahead of schedule',
                'Improved customer satisfaction score by 30%',
                'Built real-time analytics dashboard',
                'Established agile development processes',
                'Reduced technical debt by 40%',
            ], $count),
        ]);
    }

    public function atCompany(string $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company' => $company,
        ]);
    }

    public function withTitle(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }

    public function inIndustry(string $industry): static
    {
        return $this->state(fn (array $attributes) => [
            'industry' => $industry,
        ]);
    }

    public function fullTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'full-time',
        ]);
    }

    public function contract(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'contract',
        ]);
    }

    public function internship(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'internship',
        ]);
    }
}
