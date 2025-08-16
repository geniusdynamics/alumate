<?php

namespace Database\Factories;

use App\Models\Circle;
use Illuminate\Database\Eloquent\Factories\Factory;

class CircleFactory extends Factory
{
    protected $model = Circle::class;

    public function definition(): array
    {
        $types = ['school_year', 'multi_school', 'custom'];
        $type = $this->faker->randomElement($types);

        return [
            'name' => $this->generateName($type),
            'type' => $type,
            'criteria' => $this->generateCriteria($type),
            'member_count' => $this->faker->numberBetween(0, 100),
            'auto_generated' => $type !== 'custom',
        ];
    }

    private function generateName(string $type): string
    {
        switch ($type) {
            case 'school_year':
                $university = $this->faker->company.' University';
                $year = $this->faker->numberBetween(2000, 2025);

                return "{$university} Class of {$year}";

            case 'multi_school':
                $schools = [
                    $this->faker->company.' University',
                    $this->faker->company.' College',
                ];

                return 'Multi-School Alumni: '.implode(', ', $schools);

            case 'custom':
                return $this->faker->words(3, true).' Circle';

            default:
                return 'Alumni Circle';
        }
    }

    private function generateCriteria(string $type): array
    {
        switch ($type) {
            case 'school_year':
                return [
                    'institution_name' => $this->faker->company.' University',
                    'graduation_year' => $this->faker->numberBetween(2000, 2025),
                ];

            case 'multi_school':
                return [
                    'institution_names' => [
                        $this->faker->company.' University',
                        $this->faker->company.' College',
                    ],
                ];

            case 'custom':
                return [
                    'name' => $this->faker->words(3, true),
                    'description' => $this->faker->sentence(),
                ];

            default:
                return [];
        }
    }

    public function schoolYear(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'school_year',
                'name' => $this->faker->company.' University Class of '.$this->faker->numberBetween(2000, 2025),
                'criteria' => [
                    'institution_name' => $this->faker->company.' University',
                    'graduation_year' => $this->faker->numberBetween(2000, 2025),
                ],
                'auto_generated' => true,
            ];
        });
    }

    public function multiSchool(): static
    {
        return $this->state(function (array $attributes) {
            $schools = [
                $this->faker->company.' University',
                $this->faker->company.' College',
            ];

            return [
                'type' => 'multi_school',
                'name' => 'Multi-School Alumni: '.implode(', ', $schools),
                'criteria' => [
                    'institution_names' => $schools,
                ],
                'auto_generated' => true,
            ];
        });
    }

    public function custom(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'custom',
                'name' => $this->faker->words(3, true).' Circle',
                'criteria' => [
                    'name' => $this->faker->words(3, true),
                    'description' => $this->faker->sentence(),
                ],
                'auto_generated' => false,
            ];
        });
    }
}
