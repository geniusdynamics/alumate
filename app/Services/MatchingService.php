<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\JobGraduateMatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MatchingService
{
    public function calculateJobGraduateMatch(Job $job, Graduate $graduate)
    {
        $matchScore = 0;
        $compatibilityScore = 0;
        $factors = [];
        $compatibilityFactors = [];

        // Course Match (40% weight)
        if ($job->course_id === $graduate->course_id) {
            $matchScore += 40;
            $factors['course_match'] = true;
        } else {
            // Check for related courses
            $courseCompatibility = $this->calculateCourseCompatibility($job->course, $graduate->course);
            $matchScore += $courseCompatibility * 0.4;
            $factors['course_compatibility'] = $courseCompatibility;
        }

        // Skills Match (30% weight)
        if (! empty($job->required_skills) && ! empty($graduate->skills)) {
            $skillsMatch = $this->calculateSkillsMatch($job->required_skills, $graduate->skills);
            $matchScore += $skillsMatch['score'] * 0.3;
            $factors['skills_match'] = $skillsMatch;
        }

        // Profile Completion (15% weight)
        $profileScore = ($graduate->profile_completion_percentage / 100) * 15;
        $matchScore += $profileScore;
        $factors['profile_completion'] = $graduate->profile_completion_percentage;

        // GPA Bonus (10% weight)
        if ($graduate->gpa) {
            $gpaScore = ($graduate->gpa / 4.0) * 10;
            $matchScore += $gpaScore;
            $factors['gpa'] = $graduate->gpa;
        }

        // Experience Level Match (5% weight)
        $experienceMatch = $this->calculateExperienceMatch($job, $graduate);
        $matchScore += $experienceMatch * 0.05;
        $factors['experience_match'] = $experienceMatch;

        // Calculate compatibility score
        $compatibilityData = $this->calculateCompatibilityScore($job, $graduate);
        $compatibilityScore = $compatibilityData['score'];
        $compatibilityFactors = $compatibilityData['factors'];

        return [
            'match_score' => round($matchScore, 2),
            'match_factors' => $factors,
            'compatibility_score' => $compatibilityScore,
            'compatibility_factors' => $compatibilityFactors,
            'overall_score' => round(($matchScore * 0.7) + ($compatibilityScore * 0.3), 2),
        ];
    }

    public function calculateSkillsMatch(array $requiredSkills, array $graduateSkills)
    {
        $requiredSkills = array_map('strtolower', array_map('trim', $requiredSkills));
        $graduateSkills = array_map('strtolower', array_map('trim', $graduateSkills));

        $exactMatches = array_intersect($requiredSkills, $graduateSkills);
        $partialMatches = [];

        // Check for partial matches (similar skills)
        foreach ($requiredSkills as $required) {
            if (! in_array($required, $exactMatches)) {
                foreach ($graduateSkills as $graduate) {
                    if ($this->areSkillsSimilar($required, $graduate)) {
                        $partialMatches[] = ['required' => $required, 'graduate' => $graduate];
                        break;
                    }
                }
            }
        }

        $exactScore = (count($exactMatches) / count($requiredSkills)) * 100;
        $partialScore = (count($partialMatches) / count($requiredSkills)) * 50;
        $totalScore = min(100, $exactScore + $partialScore);

        return [
            'score' => $totalScore,
            'exact_matches' => $exactMatches,
            'partial_matches' => $partialMatches,
            'missing_skills' => array_diff($requiredSkills, $exactMatches, array_column($partialMatches, 'required')),
        ];
    }

    public function calculateCourseCompatibility(Course $jobCourse, Course $graduateCourse)
    {
        if ($jobCourse->id === $graduateCourse->id) {
            return 100;
        }

        $compatibility = 0;

        // Check skill overlap
        if (! empty($jobCourse->skills_gained) && ! empty($graduateCourse->skills_gained)) {
            $jobSkills = array_map('strtolower', $jobCourse->skills_gained);
            $gradSkills = array_map('strtolower', $graduateCourse->skills_gained);
            $overlap = array_intersect($jobSkills, $gradSkills);
            $compatibility += (count($overlap) / count($jobSkills)) * 60;
        }

        // Check career path overlap
        if (! empty($jobCourse->career_paths) && ! empty($graduateCourse->career_paths)) {
            $jobPaths = array_map('strtolower', $jobCourse->career_paths);
            $gradPaths = array_map('strtolower', $graduateCourse->career_paths);
            $pathOverlap = array_intersect($jobPaths, $gradPaths);
            $compatibility += (count($pathOverlap) / count($jobPaths)) * 30;
        }

        // Same level bonus
        if ($jobCourse->level === $graduateCourse->level) {
            $compatibility += 10;
        }

        return min(100, $compatibility);
    }

    public function calculateExperienceMatch(Job $job, Graduate $graduate)
    {
        $requiredYears = $job->min_experience_years ?? 0;

        // Estimate graduate experience
        $graduateExperience = 0;
        if ($graduate->employment_status === 'employed' && $graduate->employment_start_date) {
            $graduateExperience = $graduate->employment_start_date->diffInYears(now());
        }

        if ($requiredYears === 0) {
            return 100; // Entry level
        }

        if ($graduateExperience >= $requiredYears) {
            return 100;
        }

        // Partial match based on how close they are
        return max(0, 100 - (($requiredYears - $graduateExperience) * 25));
    }

    public function calculateCompatibilityScore(Job $job, Graduate $graduate)
    {
        $score = 0;
        $factors = [];

        // Location compatibility (if graduate has location preference)
        if ($graduate->address && $job->location) {
            $locationMatch = $this->calculateLocationCompatibility($job->location, $graduate->address);
            $score += $locationMatch * 0.25;
            $factors['location_compatibility'] = $locationMatch;
        }

        // Salary expectations (if graduate has salary data)
        if ($graduate->current_salary && ($job->salary_min || $job->salary_max)) {
            $salaryMatch = $this->calculateSalaryCompatibility($job, $graduate);
            $score += $salaryMatch * 0.35;
            $factors['salary_compatibility'] = $salaryMatch;
        }

        // Job type preference (inferred from current employment)
        if ($graduate->employment_status === 'employed') {
            $score += 20; // Bonus for currently employed (might prefer stability)
            $factors['employment_stability'] = true;
        } elseif ($graduate->job_search_active) {
            $score += 30; // Higher bonus for actively searching
            $factors['active_job_seeker'] = true;
        }

        // Profile activity (recent updates indicate active engagement)
        if ($graduate->last_profile_update && $graduate->last_profile_update->diffInDays(now()) <= 30) {
            $score += 15;
            $factors['recent_activity'] = true;
        }

        return [
            'score' => round($score, 2),
            'factors' => $factors,
        ];
    }

    public function calculateLocationCompatibility($jobLocation, $graduateAddress)
    {
        // Simple location matching - in a real system you'd use geocoding
        $jobLocation = strtolower(trim($jobLocation));
        $graduateAddress = strtolower(trim($graduateAddress));

        if (strpos($graduateAddress, $jobLocation) !== false || strpos($jobLocation, $graduateAddress) !== false) {
            return 100;
        }

        // Check for city/state matches
        $jobParts = explode(',', $jobLocation);
        $gradParts = explode(',', $graduateAddress);

        foreach ($jobParts as $jobPart) {
            foreach ($gradParts as $gradPart) {
                if (trim($jobPart) === trim($gradPart)) {
                    return 70;
                }
            }
        }

        return 0;
    }

    public function calculateSalaryCompatibility(Job $job, Graduate $graduate)
    {
        $currentSalary = $graduate->current_salary;
        $jobMin = $job->salary_min;
        $jobMax = $job->salary_max;

        if (! $jobMin && ! $jobMax) {
            return 50; // Neutral if no salary info
        }

        // If graduate is unemployed, any salary is good
        if ($graduate->employment_status === 'unemployed') {
            return 80;
        }

        // Calculate based on salary improvement potential
        if ($jobMax && $jobMax > $currentSalary) {
            $improvement = (($jobMax - $currentSalary) / $currentSalary) * 100;

            return min(100, 50 + $improvement);
        }

        if ($jobMin && $jobMin >= $currentSalary * 0.9) {
            return 70; // Similar or slightly better
        }

        return 30; // Lower than current
    }

    public function areSkillsSimilar($skill1, $skill2)
    {
        // Simple similarity check - in production you'd use more sophisticated matching
        $skill1 = strtolower($skill1);
        $skill2 = strtolower($skill2);

        // Exact match
        if ($skill1 === $skill2) {
            return true;
        }

        // Contains match
        if (strpos($skill1, $skill2) !== false || strpos($skill2, $skill1) !== false) {
            return true;
        }

        // Common skill synonyms
        $synonyms = [
            'javascript' => ['js', 'node.js', 'nodejs'],
            'python' => ['py'],
            'database' => ['sql', 'mysql', 'postgresql'],
            'web development' => ['web dev', 'frontend', 'backend'],
            'project management' => ['pm', 'project manager'],
        ];

        foreach ($synonyms as $base => $variants) {
            if (($skill1 === $base && in_array($skill2, $variants)) ||
                ($skill2 === $base && in_array($skill1, $variants)) ||
                (in_array($skill1, $variants) && in_array($skill2, $variants))) {
                return true;
            }
        }

        return false;
    }

    public function generateJobMatches(Job $job, $limit = 50)
    {
        // Get potential candidates
        $candidates = Graduate::where('job_search_active', true)
            ->where('allow_employer_contact', true)
            ->with(['course'])
            ->get();

        $matches = [];

        foreach ($candidates as $graduate) {
            $matchData = $this->calculateJobGraduateMatch($job, $graduate);

            if ($matchData['overall_score'] >= 30) { // Minimum threshold
                $matches[] = [
                    'graduate' => $graduate,
                    'match_data' => $matchData,
                ];
            }
        }

        // Sort by overall score
        usort($matches, function ($a, $b) {
            return $b['match_data']['overall_score'] <=> $a['match_data']['overall_score'];
        });

        return array_slice($matches, 0, $limit);
    }

    public function generateGraduateMatches(Graduate $graduate, $limit = 20)
    {
        // Get active jobs
        $jobs = Job::active()
            ->notExpired()
            ->with(['employer', 'course'])
            ->get();

        $matches = [];

        foreach ($jobs as $job) {
            $matchData = $this->calculateJobGraduateMatch($job, $graduate);

            if ($matchData['overall_score'] >= 30) { // Minimum threshold
                $matches[] = [
                    'job' => $job,
                    'match_data' => $matchData,
                ];
            }
        }

        // Sort by overall score
        usort($matches, function ($a, $b) {
            return $b['match_data']['overall_score'] <=> $a['match_data']['overall_score'];
        });

        return array_slice($matches, 0, $limit);
    }

    public function storeJobGraduateMatch(Job $job, Graduate $graduate, array $matchData)
    {
        return JobGraduateMatch::updateOrCreate(
            [
                'job_id' => $job->id,
                'graduate_id' => $graduate->id,
            ],
            [
                'match_score' => $matchData['match_score'],
                'match_factors' => $matchData['match_factors'],
                'compatibility_score' => $matchData['compatibility_score'],
                'compatibility_factors' => $matchData['compatibility_factors'],
                'calculated_at' => now(),
            ]
        );
    }

    public function batchCalculateMatches(Job $job)
    {
        $matches = $this->generateJobMatches($job);
        $stored = 0;

        foreach ($matches as $match) {
            $this->storeJobGraduateMatch($job, $match['graduate'], $match['match_data']);
            $stored++;
        }

        return $stored;
    }

    public function getTopMatchesForJob(Job $job, $limit = 20)
    {
        return JobGraduateMatch::where('job_id', $job->id)
            ->with(['graduate.course'])
            ->orderBy('match_score', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getTopMatchesForGraduate(Graduate $graduate, $limit = 20)
    {
        return JobGraduateMatch::where('graduate_id', $graduate->id)
            ->with(['job.employer', 'job.course'])
            ->orderBy('match_score', 'desc')
            ->limit($limit)
            ->get();
    }

    public function markMatchAsRecommended(JobGraduateMatch $match)
    {
        $match->update(['is_recommended' => true]);

        // Send notification to graduate
        $match->graduate->user->notifications()->create([
            'type' => 'job_recommendation',
            'data' => [
                'job_id' => $match->job_id,
                'job_title' => $match->job->title,
                'company_name' => $match->job->employer->company_name,
                'match_score' => $match->match_score,
                'match_id' => $match->id,
            ],
        ]);

        return $match;
    }

    public function markMatchAsViewed(JobGraduateMatch $match)
    {
        $match->update(['is_viewed' => true]);

        return $match;
    }

    public function markMatchAsApplied(JobGraduateMatch $match)
    {
        $match->update(['is_applied' => true]);

        return $match;
    }

    public function getMatchingStatistics()
    {
        return Cache::remember('matching_statistics', 3600, function () {
            return [
                'total_matches' => JobGraduateMatch::count(),
                'high_quality_matches' => JobGraduateMatch::where('match_score', '>=', 80)->count(),
                'recommended_matches' => JobGraduateMatch::where('is_recommended', true)->count(),
                'applied_matches' => JobGraduateMatch::where('is_applied', true)->count(),
                'avg_match_score' => JobGraduateMatch::avg('match_score'),
                'match_success_rate' => $this->calculateMatchSuccessRate(),
                'top_matching_courses' => $this->getTopMatchingCourses(),
                'matching_trends' => $this->getMatchingTrends(),
            ];
        });
    }

    private function calculateMatchSuccessRate()
    {
        $totalRecommended = JobGraduateMatch::where('is_recommended', true)->count();
        $totalApplied = JobGraduateMatch::where('is_applied', true)->count();

        return $totalRecommended > 0 ? round(($totalApplied / $totalRecommended) * 100, 2) : 0;
    }

    private function getTopMatchingCourses()
    {
        return DB::table('job_graduate_matches')
            ->join('graduates', 'job_graduate_matches.graduate_id', '=', 'graduates.id')
            ->join('courses', 'graduates.course_id', '=', 'courses.id')
            ->select('courses.name', DB::raw('AVG(job_graduate_matches.match_score) as avg_score'), DB::raw('COUNT(*) as total_matches'))
            ->groupBy('courses.id', 'courses.name')
            ->orderBy('avg_score', 'desc')
            ->limit(10)
            ->get();
    }

    private function getMatchingTrends()
    {
        return DB::table('job_graduate_matches')
            ->select(
                DB::raw('DATE(calculated_at) as date'),
                DB::raw('COUNT(*) as matches_calculated'),
                DB::raw('AVG(match_score) as avg_score')
            )
            ->where('calculated_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(calculated_at)'))
            ->orderBy('date')
            ->get();
    }

    public function refreshAllMatches()
    {
        $jobs = Job::active()->get();
        $totalMatches = 0;

        foreach ($jobs as $job) {
            $matches = $this->batchCalculateMatches($job);
            $totalMatches += $matches;
        }

        return $totalMatches;
    }

    public function cleanupOldMatches($days = 30)
    {
        return JobGraduateMatch::where('calculated_at', '<', now()->subDays($days))->delete();
    }
}
