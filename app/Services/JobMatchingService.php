<?php

namespace App\Services;

use App\Models\Circle;
use App\Models\Connection;
use App\Models\JobMatchScore;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Support\Collection;

class JobMatchingService
{
    // Scoring weights (must total 100%)
    const WEIGHT_CONNECTIONS = 0.35;  // 35%

    const WEIGHT_SKILLS = 0.25;       // 25%

    const WEIGHT_EDUCATION = 0.20;    // 20%

    const WEIGHT_CIRCLES = 0.20;      // 20%

    /**
     * Calculate comprehensive match score for a job and user
     */
    public function calculateMatchScore(JobPosting $job, User $user): float
    {
        $connectionScore = $this->getConnectionScore($user, $job);
        $skillsScore = $this->getSkillsScore($user, $job);
        $educationScore = $this->getEducationScore($user, $job);
        $circleScore = $this->getCircleScore($user, $job);

        $totalScore = (
            ($connectionScore * self::WEIGHT_CONNECTIONS) +
            ($skillsScore * self::WEIGHT_SKILLS) +
            ($educationScore * self::WEIGHT_EDUCATION) +
            ($circleScore * self::WEIGHT_CIRCLES)
        );

        return round($totalScore, 2);
    }

    /**
     * Calculate score based on network connections at the company
     */
    public function getConnectionScore(User $user, JobPosting $job): float
    {
        $mutualConnections = $this->findMutualConnections($user, $job);
        $connectionCount = $mutualConnections->count();

        if ($connectionCount === 0) {
            return 0;
        }

        // Score increases with more connections, but with diminishing returns
        $baseScore = min($connectionCount * 20, 80); // Max 80 for 4+ connections

        // Bonus for senior connections or hiring managers
        $seniorBonus = $mutualConnections->filter(function ($connection) {
            return $this->isSeniorRole($connection->title ?? '');
        })->count() * 10;

        return min($baseScore + $seniorBonus, 100);
    }

    /**
     * Calculate score based on skills match
     */
    public function getSkillsScore(User $user, JobPosting $job): float
    {
        $userSkills = $this->getUserSkills($user);
        $jobSkills = $job->skills_required ?? [];

        if (empty($jobSkills) || empty($userSkills)) {
            return 50; // Neutral score if no skills data
        }

        $userSkillsLower = array_map('strtolower', $userSkills);
        $jobSkillsLower = array_map('strtolower', $jobSkills);

        $matchingSkills = array_intersect($userSkillsLower, $jobSkillsLower);
        $matchPercentage = (count($matchingSkills) / count($jobSkillsLower)) * 100;

        // Bonus for having more skills than required
        $extraSkillsBonus = max(0, count($userSkillsLower) - count($jobSkillsLower)) * 2;

        return min($matchPercentage + $extraSkillsBonus, 100);
    }

    /**
     * Calculate score based on education relevance
     */
    public function getEducationScore(User $user, JobPosting $job): float
    {
        $userEducations = $user->educations ?? collect();

        if ($userEducations->isEmpty()) {
            return 30; // Low score if no education data
        }

        $score = 0;
        $jobTitle = strtolower($job->title);
        $jobDescription = strtolower($job->description);

        foreach ($userEducations as $education) {
            $degree = strtolower($education->degree ?? '');
            $field = strtolower($education->field_of_study ?? '');
            $school = strtolower($education->school->name ?? '');

            // Check for relevant degree level
            if ($this->isDegreeRelevant($degree, $jobTitle, $jobDescription)) {
                $score += 30;
            }

            // Check for relevant field of study
            if ($this->isFieldRelevant($field, $jobTitle, $jobDescription)) {
                $score += 40;
            }

            // Bonus for prestigious schools
            if ($this->isPrestigiousSchool($school)) {
                $score += 10;
            }
        }

        return min($score, 100);
    }

    /**
     * Calculate score based on circle overlap with company employees
     */
    public function getCircleScore(User $user, JobPosting $job): float
    {
        $userCircles = $user->circles->pluck('id');

        if ($userCircles->isEmpty()) {
            return 0;
        }

        // Find employees at the company who share circles with the user
        $companyEmployees = User::whereHas('careerTimelines', function ($query) use ($job) {
            $query->where('company', 'ILIKE', '%'.$job->company->name.'%')
                ->where('is_current', true);
        })->get();

        if ($companyEmployees->isEmpty()) {
            return 0;
        }

        $sharedCircleCount = 0;
        $totalPossibleShares = 0;

        foreach ($companyEmployees as $employee) {
            $employeeCircles = $employee->circles->pluck('id');
            $sharedCircles = $userCircles->intersect($employeeCircles);
            $sharedCircleCount += $sharedCircles->count();
            $totalPossibleShares += $userCircles->count();
        }

        if ($totalPossibleShares === 0) {
            return 0;
        }

        $overlapPercentage = ($sharedCircleCount / $totalPossibleShares) * 100;

        // Bonus for having many employees in shared circles
        $employeeBonus = min($companyEmployees->count() * 5, 20);

        return min($overlapPercentage + $employeeBonus, 100);
    }

    /**
     * Get detailed reasons for why a job matches a user
     */
    public function getMatchReasons(User $user, JobPosting $job): array
    {
        $reasons = [];

        // Connection reasons
        $mutualConnections = $this->findMutualConnections($user, $job);
        if ($mutualConnections->count() > 0) {
            $reasons[] = [
                'type' => 'connections',
                'reason' => "You have {$mutualConnections->count()} connection(s) at {$job->company->name}",
                'score' => $this->getConnectionScore($user, $job),
                'details' => $mutualConnections->take(3)->pluck('name')->toArray(),
            ];
        }

        // Skills reasons
        $userSkills = $this->getUserSkills($user);
        $jobSkills = $job->skills_required ?? [];
        $matchingSkills = array_intersect(
            array_map('strtolower', $userSkills),
            array_map('strtolower', $jobSkills)
        );

        if (! empty($matchingSkills)) {
            $reasons[] = [
                'type' => 'skills',
                'reason' => 'Your skills match '.count($matchingSkills).' of the required skills',
                'score' => $this->getSkillsScore($user, $job),
                'details' => array_slice($matchingSkills, 0, 5),
            ];
        }

        // Education reasons
        $educationScore = $this->getEducationScore($user, $job);
        if ($educationScore > 50) {
            $reasons[] = [
                'type' => 'education',
                'reason' => 'Your educational background is relevant to this role',
                'score' => $educationScore,
                'details' => $user->educations->take(2)->pluck('degree')->toArray(),
            ];
        }

        // Circle reasons
        $circleScore = $this->getCircleScore($user, $job);
        if ($circleScore > 0) {
            $reasons[] = [
                'type' => 'circles',
                'reason' => 'You share alumni circles with employees at this company',
                'score' => $circleScore,
                'details' => [],
            ];
        }

        // Sort by score descending
        usort($reasons, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $reasons;
    }

    /**
     * Find mutual connections between user and company employees
     */
    public function findMutualConnections(User $user, JobPosting $job): Collection
    {
        return User::whereHas('connections', function ($query) use ($user) {
            $query->where('connected_user_id', $user->id)
                ->where('status', 'accepted');
        })
            ->whereHas('careerTimelines', function ($query) use ($job) {
                $query->where('company', 'ILIKE', '%'.$job->company->name.'%')
                    ->where('is_current', true);
            })
            ->with(['careerTimelines' => function ($query) use ($job) {
                $query->where('company', 'ILIKE', '%'.$job->company->name.'%')
                    ->where('is_current', true);
            }])
            ->get();
    }

    /**
     * Store or update match score for a user and job
     */
    public function storeMatchScore(JobPosting $job, User $user): JobMatchScore
    {
        $score = $this->calculateMatchScore($job, $user);
        $reasons = $this->getMatchReasons($user, $job);
        $mutualConnections = $this->findMutualConnections($user, $job);

        return JobMatchScore::updateOrCreate(
            [
                'job_id' => $job->id,
                'user_id' => $user->id,
            ],
            [
                'score' => $score,
                'reasons' => $reasons,
                'calculated_at' => now(),
                'connection_score' => $this->getConnectionScore($user, $job),
                'skills_score' => $this->getSkillsScore($user, $job),
                'education_score' => $this->getEducationScore($user, $job),
                'circle_score' => $this->getCircleScore($user, $job),
                'mutual_connections_count' => $mutualConnections->count(),
            ]
        );
    }

    /**
     * Get user's skills from various sources
     */
    private function getUserSkills(User $user): array
    {
        $skills = [];

        // From profile skills
        if ($user->skills) {
            $skills = array_merge($skills, $user->skills);
        }

        // From career timeline
        if ($user->careerTimelines) {
            foreach ($user->careerTimelines as $career) {
                if ($career->skills) {
                    $skills = array_merge($skills, $career->skills);
                }
            }
        }

        return array_unique($skills);
    }

    /**
     * Check if degree is relevant to job
     */
    private function isDegreeRelevant(string $degree, string $jobTitle, string $jobDescription): bool
    {
        $techDegrees = ['computer science', 'software engineering', 'information technology', 'engineering'];
        $businessDegrees = ['business', 'mba', 'management', 'marketing', 'finance'];

        $isTechJob = str_contains($jobTitle, 'engineer') || str_contains($jobTitle, 'developer') ||
                     str_contains($jobTitle, 'technical') || str_contains($jobDescription, 'programming');

        $isBusinessJob = str_contains($jobTitle, 'manager') || str_contains($jobTitle, 'director') ||
                        str_contains($jobTitle, 'analyst') || str_contains($jobDescription, 'business');

        if ($isTechJob) {
            return collect($techDegrees)->some(fn ($techDegree) => str_contains($degree, $techDegree));
        }

        if ($isBusinessJob) {
            return collect($businessDegrees)->some(fn ($businessDegree) => str_contains($degree, $businessDegree));
        }

        return false;
    }

    /**
     * Check if field of study is relevant to job
     */
    private function isFieldRelevant(string $field, string $jobTitle, string $jobDescription): bool
    {
        $fieldWords = explode(' ', $field);
        $jobWords = explode(' ', $jobTitle.' '.$jobDescription);

        return collect($fieldWords)->intersect($jobWords)->isNotEmpty();
    }

    /**
     * Check if school is considered prestigious (simplified)
     */
    private function isPrestigiousSchool(string $school): bool
    {
        $prestigiousKeywords = ['harvard', 'stanford', 'mit', 'berkeley', 'yale', 'princeton', 'columbia'];

        return collect($prestigiousKeywords)->some(fn ($keyword) => str_contains($school, $keyword));
    }

    /**
     * Check if role title indicates senior position
     */
    private function isSeniorRole(string $title): bool
    {
        $seniorKeywords = ['senior', 'lead', 'principal', 'director', 'manager', 'vp', 'head of', 'chief'];

        return collect($seniorKeywords)->some(fn ($keyword) => str_contains(strtolower($title), $keyword));
    }
}
