<?php

namespace Database\Factories;

use App\Models\SalaryProgression;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalaryProgressionFactory extends Factory
{
    protected $model = SalaryProgression::class;

    public function definition(): array
    {
        $industries = [
            'Technology', 'Healthcare', 'Finance', 'Education', 'Manufacturing',
            'Retail', 'Consulting', 'Marketing', 'Engineering', 'Government',
        ];

        $positions = [
            'Software Engineer', 'Data Analyst', 'Project Manager', 'Marketing Manager',
            'Sales Representative', 'Business Analyst', 'Product Manager', 'Designer',
            'Operations Manager', 'Financial Analyst',
        ];

        $companies = [
            'Microsoft', 'Google', 'Amazon', 'Apple', 'Meta', 'Tesla', 'Netflix',
            'Salesforce', 'Adobe', 'IBM', 'Oracle', 'Cisco', 'Intel', 'HP',
        ];

        $yearsSinceGraduation = $this->faker->numberBetween(0, 15);
        $baseSalary = $this->faker->numberBetween(40000, 200000);

        // Adjust salary based on years of experience
        $experienceMultiplier = 1 + ($yearsSinceGraduation * 0.05);
        $adjustedSalary = $baseSalary * $experienceMultiplier;

        return [
            'user_id' => User::factory(),
            'salary' => $adjustedSalary,
            'currency' => 'USD',
            'salary_type' => $this->faker->randomElement(['annual', 'hourly', 'monthly']),
            'position_title' => $this->faker->randomElement($positions),
            'company' => $this->faker->randomElement($companies),
            'industry' => $this->faker->randomElement($industries),
            'effective_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'years_since_graduation' => $yearsSinceGraduation,
            'metadata' => [
                'benefits' => $this->faker->randomElements([
                    'Health Insurance', 'Dental Insurance', '401k Matching',
                    'Stock Options', 'Flexible PTO', 'Remote Work',
                ], $this->faker->numberBetween(2, 4)),
                'bonus_percentage' => $this->faker->numberBetween(0, 25),
                'equity_percentage' => $this->faker->numberBetween(0, 5),
            ],
        ];
    }
}
