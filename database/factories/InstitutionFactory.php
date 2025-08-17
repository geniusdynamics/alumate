<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        $institutionTypes = ['university', 'college', 'community_college', 'technical_school', 'trade_school'];
        $type = fake()->randomElement($institutionTypes);

        return [
            'name' => $this->generateInstitutionName($type),
            'slug' => $this->faker->unique()->slug(),
            'type' => $type,
            'description' => $this->faker->paragraph(2),
            'website' => $this->faker->url(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => [
                'street' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->state(),
                'postal_code' => $this->faker->postcode(),
                'country' => 'United States',
            ],
            'logo_url' => $this->faker->optional(0.7)->imageUrl(200, 200, 'business'),
            'banner_url' => $this->faker->optional(0.5)->imageUrl(800, 300, 'business'),
            'established_year' => $this->faker->numberBetween(1800, 2020),
            'student_count' => $this->faker->numberBetween(500, 50000),
            'alumni_count' => $this->faker->numberBetween(1000, 200000),
            'settings' => [
                'public_directory' => $this->faker->boolean(80),
                'allow_job_postings' => $this->faker->boolean(90),
                'enable_mentorship' => $this->faker->boolean(85),
                'enable_events' => $this->faker->boolean(95),
                'enable_fundraising' => $this->faker->boolean(70),
                'branding' => [
                    'primary_color' => $this->faker->hexColor(),
                    'secondary_color' => $this->faker->hexColor(),
                    'font_family' => $this->faker->randomElement(['Arial', 'Helvetica', 'Georgia', 'Times New Roman']),
                ],
            ],
            'subscription_plan' => $this->faker->randomElement(['basic', 'professional', 'enterprise']),
            'subscription_status' => $this->faker->randomElement(['active', 'trial', 'suspended', 'cancelled']),
            'trial_ends_at' => $this->faker->optional(0.3)->dateTimeBetween('now', '+30 days'),
            'is_active' => $this->faker->boolean(95),
            'verified_at' => $this->faker->optional(0.8)->dateTimeBetween('-2 years', 'now'),
        ];
    }

    private function generateInstitutionName(string $type): string
    {
        $prefixes = [
            'university' => ['University of', 'State University of', 'National University of'],
            'college' => ['College of', 'Community College of', 'Liberal Arts College of'],
            'community_college' => ['Community College of', 'City College of'],
            'technical_school' => ['Technical Institute of', 'Polytechnic Institute of'],
            'trade_school' => ['Trade School of', 'Vocational Institute of'],
        ];

        $locations = [
            $this->faker->city(),
            $this->faker->state(),
            $this->faker->lastName(),
            $this->faker->word(),
        ];

        $suffixes = [
            'university' => ['University', 'State University', 'Tech'],
            'college' => ['College', 'Institute', 'Academy'],
            'community_college' => ['Community College', 'CC'],
            'technical_school' => ['Technical Institute', 'Polytechnic', 'Tech'],
            'trade_school' => ['Trade School', 'Vocational Institute'],
        ];

        if ($this->faker->boolean(60)) {
            // Use prefix format
            $prefix = $this->faker->randomElement($prefixes[$type]);
            $location = $this->faker->randomElement($locations);

            return "{$prefix} {$location}";
        } else {
            // Use suffix format
            $location = $this->faker->randomElement($locations);
            $suffix = $this->faker->randomElement($suffixes[$type]);

            return "{$location} {$suffix}";
        }
    }

    public function university(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'university',
                'name' => $this->generateInstitutionName('university'),
                'student_count' => $this->faker->numberBetween(5000, 50000),
                'alumni_count' => $this->faker->numberBetween(10000, 200000),
            ];
        });
    }

    public function college(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'college',
                'name' => $this->generateInstitutionName('college'),
                'student_count' => $this->faker->numberBetween(1000, 10000),
                'alumni_count' => $this->faker->numberBetween(2000, 50000),
            ];
        });
    }

    public function communityCollege(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'community_college',
                'name' => $this->generateInstitutionName('community_college'),
                'student_count' => $this->faker->numberBetween(500, 5000),
                'alumni_count' => $this->faker->numberBetween(1000, 20000),
            ];
        });
    }

    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
                'subscription_status' => 'active',
                'verified_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            ];
        });
    }

    public function trial(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'subscription_status' => 'trial',
                'trial_ends_at' => $this->faker->dateTimeBetween('now', '+30 days'),
            ];
        });
    }

    public function enterprise(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'subscription_plan' => 'enterprise',
                'subscription_status' => 'active',
                'student_count' => $this->faker->numberBetween(10000, 50000),
                'alumni_count' => $this->faker->numberBetween(50000, 200000),
            ];
        });
    }
}
