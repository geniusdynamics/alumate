<?php

namespace Database\Factories;

use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SavedSearch>
 */
class SavedSearchFactory extends Factory
{
    protected $model = SavedSearch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $queries = [
            'software engineer',
            'product manager',
            'data scientist',
            'marketing director',
            'sales representative',
            'business analyst',
            'UX designer',
            'project manager',
        ];

        $locations = [
            'San Francisco',
            'New York',
            'Los Angeles',
            'Chicago',
            'Boston',
            'Seattle',
            'Austin',
            'Denver',
        ];

        $industries = [
            'Technology',
            'Finance',
            'Healthcare',
            'Education',
            'Retail',
            'Manufacturing',
            'Consulting',
            'Media',
        ];

        $skills = [
            ['PHP', 'Laravel', 'MySQL'],
            ['JavaScript', 'React', 'Node.js'],
            ['Python', 'Django', 'PostgreSQL'],
            ['Java', 'Spring', 'MongoDB'],
            ['C#', '.NET', 'SQL Server'],
            ['Ruby', 'Rails', 'Redis'],
            ['Go', 'Docker', 'Kubernetes'],
            ['Swift', 'iOS', 'Xcode'],
        ];

        $query = $this->faker->randomElement($queries);
        $hasFilters = $this->faker->boolean(70); // 70% chance of having filters

        $filters = [];
        if ($hasFilters) {
            if ($this->faker->boolean(60)) {
                $filters['location'] = $this->faker->randomElement($locations);
            }

            if ($this->faker->boolean(50)) {
                $filters['industry'] = $this->faker->randomElements($industries, $this->faker->numberBetween(1, 3));
            }

            if ($this->faker->boolean(40)) {
                $filters['graduation_year'] = [
                    'min' => $this->faker->numberBetween(2010, 2020),
                    'max' => $this->faker->numberBetween(2021, 2024),
                ];
            }

            if ($this->faker->boolean(30)) {
                $filters['skills'] = $this->faker->randomElement($skills);
            }
        }

        return [
            'user_id' => User::factory(),
            'name' => $this->generateSearchName($query, $filters),
            'query' => $query,
            'filters' => $filters,
            'result_count' => $this->faker->numberBetween(0, 150),
            'last_executed_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 month', 'now'),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * Generate a descriptive name for the search
     */
    private function generateSearchName(string $query, array $filters): string
    {
        $parts = [];

        if (! empty($query)) {
            $parts[] = ucwords($query);
        }

        if (! empty($filters['location'])) {
            $parts[] = "in {$filters['location']}";
        }

        if (! empty($filters['industry'])) {
            $industry = is_array($filters['industry'])
                ? implode(', ', $filters['industry'])
                : $filters['industry'];
            $parts[] = "in $industry";
        }

        if (! empty($filters['graduation_year'])) {
            if (is_array($filters['graduation_year'])) {
                $parts[] = "({$filters['graduation_year']['min']}-{$filters['graduation_year']['max']})";
            }
        }

        return implode(' ', $parts) ?: 'Alumni Search';
    }

    /**
     * Create a search with specific query
     */
    public function withQuery(string $query): static
    {
        return $this->state(fn (array $attributes) => [
            'query' => $query,
            'name' => ucwords($query),
        ]);
    }

    /**
     * Create a search with specific filters
     */
    public function withFilters(array $filters): static
    {
        return $this->state(fn (array $attributes) => [
            'filters' => $filters,
            'name' => $this->generateSearchName($attributes['query'] ?? '', $filters),
        ]);
    }

    /**
     * Create a search with high result count
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'result_count' => $this->faker->numberBetween(50, 200),
            'last_executed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create a search with no results
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'result_count' => 0,
        ]);
    }

    /**
     * Create a recently executed search
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_executed_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }
}
