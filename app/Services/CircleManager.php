<?php

namespace App\Services;

use App\Models\Circle;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CircleManager
{
    /**
     * Generate circles for a user based on their education history.
     */
    public function generateCirclesForUser(User $user): Collection
    {
        $circles = collect();

        // Get user's education history
        $educations = $user->educations()->get();

        if ($educations->isEmpty()) {
            return $circles;
        }

        // Generate school + graduation year circles
        foreach ($educations as $education) {
            if ($education->institution_name && $education->end_year) {
                $circle = $this->findOrCreateCircle([
                    'type' => 'school_year',
                    'institution_name' => $education->institution_name,
                    'graduation_year' => $education->end_year,
                ]);

                if ($circle) {
                    $circles->push($circle);
                }
            }
        }

        // Generate multi-school circles for users with multiple educations
        if ($educations->count() > 1) {
            $schoolCombinations = $this->getSchoolCombinations($educations);

            foreach ($schoolCombinations as $combination) {
                $circle = $this->findOrCreateCircle([
                    'type' => 'multi_school',
                    'institution_names' => $combination,
                ]);

                if ($circle) {
                    $circles->push($circle);
                }
            }
        }

        // Assign user to all generated circles
        $this->assignUserToCircles($user, $circles);

        return $circles;
    }

    /**
     * Find existing circle or create new one based on criteria.
     */
    public function findOrCreateCircle(array $criteria): ?Circle
    {
        try {
            return DB::transaction(function () use ($criteria) {
                $circle = $this->findExistingCircle($criteria);

                if ($circle) {
                    return $circle;
                }

                return $this->createNewCircle($criteria);
            });
        } catch (\Exception $e) {
            Log::error('Failed to find or create circle', [
                'criteria' => $criteria,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find existing circle based on criteria.
     */
    protected function findExistingCircle(array $criteria): ?Circle
    {
        $query = Circle::where('type', $criteria['type'])
            ->where('auto_generated', true);

        switch ($criteria['type']) {
            case 'school_year':
                return $query->whereJsonContains('criteria->institution_name', $criteria['institution_name'])
                    ->whereJsonContains('criteria->graduation_year', $criteria['graduation_year'])
                    ->first();

            case 'multi_school':
                // For multi-school circles, we need to match the exact combination
                return $query->where(function ($q) use ($criteria) {
                    $institutionNames = $criteria['institution_names'];
                    sort($institutionNames); // Ensure consistent ordering
                    $q->whereJsonLength('criteria->institution_names', count($institutionNames));

                    foreach ($institutionNames as $institutionName) {
                        $q->whereJsonContains('criteria->institution_names', $institutionName);
                    }
                })->first();

            default:
                return null;
        }
    }

    /**
     * Create a new circle based on criteria.
     */
    protected function createNewCircle(array $criteria): Circle
    {
        $name = $this->generateCircleName($criteria);

        return Circle::create([
            'name' => $name,
            'type' => $criteria['type'],
            'criteria' => $criteria,
            'auto_generated' => true,
            'member_count' => 0,
        ]);
    }

    /**
     * Generate a human-readable name for the circle.
     */
    protected function generateCircleName(array $criteria): string
    {
        switch ($criteria['type']) {
            case 'school_year':
                $schoolName = $criteria['institution_name'] ?? 'Unknown School';

                return "{$schoolName} Class of {$criteria['graduation_year']}";

            case 'multi_school':
                $schoolNames = collect($criteria['institution_names'])->sort()->take(3)->implode(', ');
                $remaining = count($criteria['institution_names']) - 3;

                if ($remaining > 0) {
                    $schoolNames .= " and {$remaining} more";
                }

                return "Multi-School Alumni: {$schoolNames}";

            case 'custom':
                return $criteria['name'] ?? 'Custom Circle';

            default:
                return 'Alumni Circle';
        }
    }

    /**
     * Generate school combinations for multi-school circles.
     */
    public function getSchoolCombinations(Collection $educations): array
    {
        $institutionNames = $educations->pluck('institution_name')->unique()->filter()->values()->toArray();

        if (count($institutionNames) < 2) {
            return [];
        }

        $combinations = [];

        // Generate combinations of 2 or more schools
        for ($i = 2; $i <= count($institutionNames); $i++) {
            $combinations = array_merge($combinations, $this->getCombinations($institutionNames, $i));
        }

        return $combinations;
    }

    /**
     * Get combinations of a specific size from an array.
     */
    protected function getCombinations(array $array, int $size): array
    {
        if ($size === 1) {
            return array_map(function ($item) {
                return [$item];
            }, $array);
        }

        $combinations = [];

        for ($i = 0; $i <= count($array) - $size; $i++) {
            $head = $array[$i];
            $tail = array_slice($array, $i + 1);

            foreach ($this->getCombinations($tail, $size - 1) as $combination) {
                $combinations[] = array_merge([$head], $combination);
            }
        }

        return $combinations;
    }

    /**
     * Assign user to multiple circles.
     */
    public function assignUserToCircles(User $user, Collection $circles): void
    {
        foreach ($circles as $circle) {
            if ($circle instanceof Circle) {
                $circle->addMember($user);
            }
        }
    }

    /**
     * Update circles for a user when their profile changes.
     */
    public function updateCirclesForUser(User $user): void
    {
        try {
            DB::transaction(function () use ($user) {
                // Remove user from all auto-generated circles
                $autoGeneratedCircles = Circle::autoGenerated()->get();

                foreach ($autoGeneratedCircles as $circle) {
                    $circle->removeMember($user);
                }

                // Regenerate circles based on current education data
                $this->generateCirclesForUser($user);
            });
        } catch (\Exception $e) {
            Log::error('Failed to update circles for user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get circles that a user should belong to based on their profile.
     */
    public function getEligibleCirclesForUser(User $user): Collection
    {
        $eligibleCircles = collect();

        // Get all auto-generated circles
        $circles = Circle::autoGenerated()->get();

        foreach ($circles as $circle) {
            if ($circle->canUserJoin($user)) {
                $eligibleCircles->push($circle);
            }
        }

        return $eligibleCircles;
    }

    /**
     * Clean up empty circles.
     */
    public function cleanupEmptyCircles(): int
    {
        $deletedCount = 0;

        $emptyCircles = Circle::autoGenerated()
            ->where('member_count', 0)
            ->where('created_at', '<', now()->subDays(7)) // Only delete circles older than 7 days
            ->get();

        foreach ($emptyCircles as $circle) {
            try {
                $circle->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                Log::error('Failed to delete empty circle', [
                    'circle_id' => $circle->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $deletedCount;
    }

    /**
     * Get statistics about circles.
     */
    public function getCircleStatistics(): array
    {
        return [
            'total_circles' => Circle::count(),
            'auto_generated_circles' => Circle::autoGenerated()->count(),
            'custom_circles' => Circle::custom()->count(),
            'school_year_circles' => Circle::ofType('school_year')->count(),
            'multi_school_circles' => Circle::ofType('multi_school')->count(),
            'average_members_per_circle' => Circle::avg('member_count'),
            'largest_circle_size' => Circle::max('member_count'),
        ];
    }
}
