<?php

namespace App\Services;

use App\Models\User;
use App\Models\CareerTimeline;
use App\Models\CareerMilestone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CareerTimelineService
{
    /**
     * Get career timeline for a user with privacy controls
     */
    public function getTimelineForUser(User $user, ?User $viewerUser = null): array
    {
        // Get career timeline entries
        $careerEntries = CareerTimeline::where('user_id', $user->id)
            ->ordered()
            ->get();

        // Get milestones visible to the viewer
        $milestones = CareerMilestone::where('user_id', $user->id)
            ->visibleTo($viewerUser)
            ->ordered()
            ->get();

        // Combine and sort chronologically
        $timeline = $this->combineTimelineData($careerEntries, $milestones);

        return [
            'timeline' => $timeline,
            'career_entries' => $careerEntries,
            'milestones' => $milestones,
            'progression' => $this->calculateCareerProgression($user),
            'stats' => $this->getCareerStats($user),
            'can_edit' => $viewerUser && $viewerUser->id === $user->id
        ];
    }

    /**
     * Add a new career entry
     */
    public function addCareerEntry(array $data, User $user): CareerTimeline
    {
        // Validate dates
        $this->validateCareerDates($data);

        // If this is marked as current, update other current positions
        if ($data['is_current'] ?? false) {
            CareerTimeline::where('user_id', $user->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);
        }

        $careerEntry = CareerTimeline::create([
            'user_id' => $user->id,
            'company' => $data['company'],
            'title' => $data['title'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'description' => $data['description'] ?? null,
            'is_current' => $data['is_current'] ?? false,
            'achievements' => $data['achievements'] ?? [],
            'location' => $data['location'] ?? null,
            'company_logo_url' => $data['company_logo_url'] ?? null,
            'industry' => $data['industry'] ?? null,
            'employment_type' => $data['employment_type'] ?? 'full-time'
        ]);

        // Auto-detect and create milestone for job change
        $this->detectAndCreateJobChangeMilestone($careerEntry, $user);

        return $careerEntry;
    }

    /**
     * Update an existing career entry
     */
    public function updateCareerEntry(int $id, array $data, User $user): CareerTimeline
    {
        $careerEntry = CareerTimeline::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Validate dates
        $this->validateCareerDates($data);

        // If this is marked as current, update other current positions
        if ($data['is_current'] ?? false) {
            CareerTimeline::where('user_id', $user->id)
                ->where('id', '!=', $id)
                ->where('is_current', true)
                ->update(['is_current' => false]);
        }

        $careerEntry->update($data);

        return $careerEntry->fresh();
    }

    /**
     * Add a career milestone
     */
    public function addMilestone(array $data, User $user): CareerMilestone
    {
        return CareerMilestone::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'date' => $data['date'],
            'visibility' => $data['visibility'] ?? CareerMilestone::VISIBILITY_PUBLIC,
            'company' => $data['company'] ?? null,
            'organization' => $data['organization'] ?? null,
            'metadata' => $data['metadata'] ?? [],
            'is_featured' => $data['is_featured'] ?? false
        ]);
    }

    /**
     * Calculate career progression metrics
     */
    public function calculateCareerProgression(User $user): array
    {
        $careerEntries = CareerTimeline::where('user_id', $user->id)
            ->orderBy('start_date')
            ->get();

        if ($careerEntries->isEmpty()) {
            return [
                'total_experience_months' => 0,
                'companies_count' => 0,
                'promotions_count' => 0,
                'career_growth_rate' => 0,
                'average_tenure_months' => 0,
                'industries' => []
            ];
        }

        $totalExperience = $careerEntries->sum('duration_in_months');
        $companiesCount = $careerEntries->pluck('company')->unique()->count();
        $promotionsCount = $this->countPromotions($careerEntries);
        $averageTenure = $careerEntries->where('is_current', false)->avg('duration_in_months') ?? 0;
        $industries = $careerEntries->pluck('industry')->filter()->unique()->values();

        return [
            'total_experience_months' => $totalExperience,
            'total_experience_years' => round($totalExperience / 12, 1),
            'companies_count' => $companiesCount,
            'promotions_count' => $promotionsCount,
            'career_growth_rate' => $this->calculateGrowthRate($careerEntries),
            'average_tenure_months' => round($averageTenure, 1),
            'industries' => $industries
        ];
    }

    /**
     * Suggest career goals based on user's progression
     */
    public function suggestCareerGoals(User $user): array
    {
        $progression = $this->calculateCareerProgression($user);
        $currentPosition = CareerTimeline::where('user_id', $user->id)
            ->where('is_current', true)
            ->first();

        $suggestions = [];

        // Suggest based on experience level
        if ($progression['total_experience_years'] < 2) {
            $suggestions[] = [
                'type' => 'skill_development',
                'title' => 'Build Core Skills',
                'description' => 'Focus on developing fundamental skills in your field',
                'priority' => 'high'
            ];
        } elseif ($progression['total_experience_years'] < 5) {
            $suggestions[] = [
                'type' => 'specialization',
                'title' => 'Develop Specialization',
                'description' => 'Consider specializing in a specific area of expertise',
                'priority' => 'medium'
            ];
        } else {
            $suggestions[] = [
                'type' => 'leadership',
                'title' => 'Leadership Development',
                'description' => 'Consider taking on leadership roles or responsibilities',
                'priority' => 'high'
            ];
        }

        // Suggest based on tenure
        if ($currentPosition && $currentPosition->duration_in_months > 24) {
            $suggestions[] = [
                'type' => 'career_move',
                'title' => 'Consider New Opportunities',
                'description' => 'You\'ve been in your current role for over 2 years. Consider new challenges.',
                'priority' => 'medium'
            ];
        }

        // Suggest certifications
        $suggestions[] = [
            'type' => 'certification',
            'title' => 'Professional Certification',
            'description' => 'Consider obtaining relevant professional certifications',
            'priority' => 'low'
        ];

        return $suggestions;
    }

    /**
     * Get career statistics
     */
    private function getCareerStats(User $user): array
    {
        $milestonesCount = CareerMilestone::where('user_id', $user->id)->count();
        $awardsCount = CareerMilestone::where('user_id', $user->id)
            ->where('type', CareerMilestone::TYPE_AWARD)
            ->count();
        $certificationsCount = CareerMilestone::where('user_id', $user->id)
            ->where('type', CareerMilestone::TYPE_CERTIFICATION)
            ->count();

        return [
            'total_milestones' => $milestonesCount,
            'awards_count' => $awardsCount,
            'certifications_count' => $certificationsCount
        ];
    }

    /**
     * Combine career entries and milestones into a unified timeline
     */
    private function combineTimelineData(Collection $careerEntries, Collection $milestones): Collection
    {
        $timeline = collect();

        // Add career entries
        foreach ($careerEntries as $entry) {
            $timeline->push([
                'type' => 'career_entry',
                'date' => $entry->start_date,
                'data' => $entry,
                'sort_date' => $entry->start_date
            ]);

            if (!$entry->is_current && $entry->end_date) {
                $timeline->push([
                    'type' => 'career_end',
                    'date' => $entry->end_date,
                    'data' => $entry,
                    'sort_date' => $entry->end_date
                ]);
            }
        }

        // Add milestones
        foreach ($milestones as $milestone) {
            $timeline->push([
                'type' => 'milestone',
                'date' => $milestone->date,
                'data' => $milestone,
                'sort_date' => $milestone->date
            ]);
        }

        return $timeline->sortByDesc('sort_date')->values();
    }

    /**
     * Validate career entry dates
     */
    private function validateCareerDates(array $data): void
    {
        $startDate = Carbon::parse($data['start_date']);
        
        if (isset($data['end_date']) && $data['end_date']) {
            $endDate = Carbon::parse($data['end_date']);
            
            if ($endDate->lt($startDate)) {
                throw new \InvalidArgumentException('End date cannot be before start date');
            }
        }

        if ($startDate->gt(now())) {
            throw new \InvalidArgumentException('Start date cannot be in the future');
        }
    }

    /**
     * Count promotions within the same company
     */
    private function countPromotions(Collection $careerEntries): int
    {
        $promotions = 0;
        $entriesByCompany = $careerEntries->groupBy('company');

        foreach ($entriesByCompany as $companyEntries) {
            if ($companyEntries->count() > 1) {
                $promotions += $companyEntries->count() - 1;
            }
        }

        return $promotions;
    }

    /**
     * Calculate career growth rate
     */
    private function calculateGrowthRate(Collection $careerEntries): float
    {
        if ($careerEntries->count() < 2) {
            return 0;
        }

        $firstEntry = $careerEntries->first();
        $lastEntry = $careerEntries->last();
        $timeSpan = $firstEntry->start_date->diffInMonths($lastEntry->start_date);

        if ($timeSpan === 0) {
            return 0;
        }

        $positionChanges = $careerEntries->count() - 1;
        return round(($positionChanges / $timeSpan) * 12, 2); // Changes per year
    }

    /**
     * Auto-detect and create milestone for job changes
     */
    private function detectAndCreateJobChangeMilestone(CareerTimeline $careerEntry, User $user): void
    {
        $previousEntry = CareerTimeline::where('user_id', $user->id)
            ->where('id', '!=', $careerEntry->id)
            ->orderBy('start_date', 'desc')
            ->first();

        if ($previousEntry) {
            $milestoneType = $careerEntry->isPromotionFrom($previousEntry) 
                ? CareerMilestone::TYPE_PROMOTION 
                : CareerMilestone::TYPE_JOB_CHANGE;

            CareerMilestone::create([
                'user_id' => $user->id,
                'type' => $milestoneType,
                'title' => $milestoneType === CareerMilestone::TYPE_PROMOTION 
                    ? "Promoted to {$careerEntry->title}" 
                    : "Started new role at {$careerEntry->company}",
                'description' => "Started as {$careerEntry->title} at {$careerEntry->company}",
                'date' => $careerEntry->start_date,
                'company' => $careerEntry->company,
                'visibility' => CareerMilestone::VISIBILITY_PUBLIC
            ]);
        }
    }
}