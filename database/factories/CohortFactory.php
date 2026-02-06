<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cohort;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Cohort Factory
 *
 * Factory for creating Cohort model instances for testing.
 */
class CohortFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Cohort::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->words(3, true) . ' Cohort',
            'criteria_json' => $this->generateRandomCriteria(),
            'created_by' => User::factory(),
            'members_count' => $this->faker->numberBetween(10, 500),
        ];
    }

    /**
     * Create a cohort with graduation year criteria.
     *
     * @param int $year
     * @return self
     */
    public function withGraduationYear(int $year): self
    {
        return $this->state(function (array $attributes) use ($year) {
            $criteria = $attributes['criteria_json'] ?? [];
            $criteria['grad_year'] = $year;
            
            return [
                'name' => "Class of {$year} Cohort",
                'criteria_json' => $criteria,
            ];
        });
    }

    /**
     * Create a cohort with degree criteria.
     *
     * @param string $degree
     * @return self
     */
    public function withDegree(string $degree): self
    {
        return $this->state(function (array $attributes) use ($degree) {
            $criteria = $attributes['criteria_json'] ?? [];
            $criteria['degree'] = $degree;
            
            return [
                'name' => "{$degree} Graduates Cohort",
                'criteria_json' => $criteria,
            ];
        });
    }

    /**
     * Create a cohort with acquisition date criteria.
     *
     * @param string $date
     * @return self
     */
    public function withAcquisitionDate(string $date): self
    {
        return $this->state(function (array $attributes) use ($date) {
            $criteria = $attributes['criteria_json'] ?? [];
            $criteria['acquisition_date'] = $date;
            
            return [
                'name' => "Acquired {$date} Cohort",
                'criteria_json' => $criteria,
            ];
        });
    }

    /**
     * Create a cohort with multiple criteria.
     *
     * @param array $criteria
     * @return self
     */
    public function withCriteria(array $criteria): self
    {
        return $this->state(function (array $attributes) use ($criteria) {
            $existingCriteria = $attributes['criteria_json'] ?? [];
            $mergedCriteria = array_merge($existingCriteria, $criteria);
            
            return [
                'criteria_json' => $mergedCriteria,
            ];
        });
    }

    /**
     * Create a small cohort (10-50 members).
     *
     * @return self
     */
    public function small(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'members_count' => $this->faker->numberBetween(10, 50),
                'name' => 'Small ' . ($attributes['name'] ?? 'Cohort'),
            ];
        });
    }

    /**
     * Create a medium cohort (50-200 members).
     *
     * @return self
     */
    public function medium(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'members_count' => $this->faker->numberBetween(50, 200),
                'name' => 'Medium ' . ($attributes['name'] ?? 'Cohort'),
            ];
        });
    }

    /**
     * Create a large cohort (200-1000 members).
     *
     * @return self
     */
    public function large(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'members_count' => $this->faker->numberBetween(200, 1000),
                'name' => 'Large ' . ($attributes['name'] ?? 'Cohort'),
            ];
        });
    }

    /**
     * Create a cohort for a specific tenant.
     *
     * @param int $tenantId
     * @return self
     */
    public function forTenant(int $tenantId): self
    {
        return $this->state(function (array $attributes) use ($tenantId) {
            return [
                'tenant_id' => $tenantId,
            ];
        });
    }

    /**
     * Create a cohort created by a specific user.
     *
     * @param int $userId
     * @return self
     */
    public function createdBy(int $userId): self
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'created_by' => $userId,
            ];
        });
    }

    /**
     * Generate random cohort criteria.
     *
     * @return array<string, mixed>
     */
    private function generateRandomCriteria(): array
    {
        $criteria = [];
        
        // Randomly add graduation year
        if ($this->faker->boolean(70)) {
            $criteria['grad_year'] = $this->faker->numberBetween(2018, 2024);
        }
        
        // Randomly add degree
        if ($this->faker->boolean(50)) {
            $degrees = ['Bachelor', 'Master', 'PhD', 'Associate', 'Certificate'];
            $criteria['degree'] = $this->faker->randomElement($degrees);
        }
        
        // Randomly add acquisition source
        if ($this->faker->boolean(40)) {
            $sources = ['organic', 'referral', 'social', 'email', 'paid'];
            $criteria['acquisition_source'] = $this->faker->randomElement($sources);
        }
        
        // Randomly add acquisition date
        if ($this->faker->boolean(60)) {
            $criteria['acquisition_date'] = $this->faker->date('Y-m-d', '-2 years');
        }
        
        return $criteria;
    }
}
