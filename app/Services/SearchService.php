<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\SavedSearch;
use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    public function searchJobs(array $criteria, int $perPage = 20)
    {
        $query = Job::with(['employer', 'course'])
            ->active()
            ->notExpired();

        $this->applyJobFilters($query, $criteria);

        $results = $query->paginate($perPage);

        // Calculate match scores for each job if user is a graduate
        if (auth()->check() && auth()->user()->hasRole('graduate')) {
            $graduate = auth()->user()->graduate;
            if ($graduate) {
                $results->getCollection()->transform(function ($job) use ($graduate) {
                    $matchData = $job->calculateMatchScore($graduate);
                    $job->match_score = $matchData['score'];
                    $job->match_factors = $matchData['factors'];

                    return $job;
                });

                // Sort by match score
                $sorted = $results->getCollection()->sortByDesc('match_score');
                $results->setCollection($sorted);
            }
        }

        return $results;
    }

    public function searchGraduates(array $criteria, int $perPage = 20)
    {
        $query = Graduate::with(['course', 'applications'])
            ->where('job_search_active', true)
            ->where('allow_employer_contact', true);

        $this->applyGraduateFilters($query, $criteria);

        $results = $query->paginate($perPage);

        // Calculate match scores if searching for a specific job
        if (! empty($criteria['job_id'])) {
            $job = Job::find($criteria['job_id']);
            if ($job) {
                $results->getCollection()->transform(function ($graduate) use ($job) {
                    $matchData = $job->calculateMatchScore($graduate);
                    $graduate->match_score = $matchData['score'];
                    $graduate->match_factors = $matchData['factors'];

                    return $graduate;
                });

                // Sort by match score
                $sorted = $results->getCollection()->sortByDesc('match_score');
                $results->setCollection($sorted);
            }
        }

        return $results;
    }

    public function searchCourses(array $criteria, int $perPage = 20)
    {
        $query = Course::with(['institution', 'graduates', 'jobs'])
            ->active();

        $this->applyCourseFilters($query, $criteria);

        return $query->paginate($perPage);
    }

    public function getJobRecommendations($graduate, int $limit = 10)
    {
        $baseQuery = Job::with(['employer', 'course'])
            ->active()
            ->notExpired();

        // Primary match: same course
        $courseJobs = (clone $baseQuery)
            ->where('course_id', $graduate->course_id)
            ->get();

        // Secondary match: skill-based
        $skillJobs = collect();
        if (! empty($graduate->skills)) {
            $skillJobs = (clone $baseQuery)
                ->where('course_id', '!=', $graduate->course_id)
                ->where(function ($query) use ($graduate) {
                    foreach ($graduate->skills as $skill) {
                        $query->orWhereJsonContains('required_skills', $skill);
                    }
                })
                ->get();
        }

        // Combine and score all jobs
        $allJobs = $courseJobs->merge($skillJobs)->unique('id');

        $scoredJobs = $allJobs->map(function ($job) use ($graduate) {
            $matchData = $job->calculateMatchScore($graduate);
            $job->match_score = $matchData['score'];
            $job->match_factors = $matchData['factors'];

            return $job;
        });

        return $scoredJobs->sortByDesc('match_score')->take($limit);
    }

    public function getCandidateRecommendations($job, int $limit = 20)
    {
        $baseQuery = Graduate::with(['course', 'applications'])
            ->where('job_search_active', true)
            ->where('allow_employer_contact', true);

        // Primary match: same course
        $courseGraduates = (clone $baseQuery)
            ->where('course_id', $job->course_id)
            ->get();

        // Secondary match: skill-based
        $skillGraduates = collect();
        if (! empty($job->required_skills)) {
            $skillGraduates = (clone $baseQuery)
                ->where('course_id', '!=', $job->course_id)
                ->where(function ($query) use ($job) {
                    foreach ($job->required_skills as $skill) {
                        $query->orWhereJsonContains('skills', $skill);
                    }
                })
                ->get();
        }

        // Combine and score all graduates
        $allGraduates = $courseGraduates->merge($skillGraduates)->unique('id');

        $scoredGraduates = $allGraduates->map(function ($graduate) use ($job) {
            $matchData = $job->calculateMatchScore($graduate);
            $graduate->match_score = $matchData['score'];
            $graduate->match_factors = $matchData['factors'];

            return $graduate;
        });

        return $scoredGraduates->sortByDesc('match_score')->take($limit);
    }

    public function getAdvancedJobMatches($graduate, array $preferences = [])
    {
        $query = Job::with(['employer', 'course'])
            ->active()
            ->notExpired();

        // Apply graduate preferences
        if (! empty($preferences['location'])) {
            $query->where('location', 'like', "%{$preferences['location']}%");
        }

        if (! empty($preferences['salary_min'])) {
            $query->where('salary_min', '>=', $preferences['salary_min']);
        }

        if (! empty($preferences['job_type'])) {
            $query->where('job_type', $preferences['job_type']);
        }

        if (! empty($preferences['work_arrangement'])) {
            $query->where('work_arrangement', $preferences['work_arrangement']);
        }

        $jobs = $query->get();

        // Calculate compatibility scores
        $scoredJobs = $jobs->map(function ($job) use ($graduate, $preferences) {
            $matchData = $job->calculateMatchScore($graduate);
            $compatibilityScore = $this->calculateCompatibilityScore($job, $graduate, $preferences);

            $job->match_score = $matchData['score'];
            $job->match_factors = $matchData['factors'];
            $job->compatibility_score = $compatibilityScore['score'];
            $job->compatibility_factors = $compatibilityScore['factors'];
            $job->overall_score = ($matchData['score'] * 0.7) + ($compatibilityScore['score'] * 0.3);

            return $job;
        });

        return $scoredJobs->sortByDesc('overall_score');
    }

    public function calculateCompatibilityScore($job, $graduate, $preferences = [])
    {
        $score = 0;
        $factors = [];

        // Location preference (20% weight)
        if (! empty($preferences['location'])) {
            if (stripos($job->location, $preferences['location']) !== false) {
                $score += 20;
                $factors['location_match'] = true;
            }
        }

        // Salary expectation (25% weight)
        if (! empty($preferences['salary_min']) && $job->salary_min) {
            if ($job->salary_min >= $preferences['salary_min']) {
                $score += 25;
                $factors['salary_match'] = true;
            } elseif ($job->salary_max && $job->salary_max >= $preferences['salary_min']) {
                $score += 15;
                $factors['salary_partial_match'] = true;
            }
        }

        // Job type preference (15% weight)
        if (! empty($preferences['job_type'])) {
            if ($job->job_type === $preferences['job_type']) {
                $score += 15;
                $factors['job_type_match'] = true;
            }
        }

        // Work arrangement preference (15% weight)
        if (! empty($preferences['work_arrangement'])) {
            if ($job->work_arrangement === $preferences['work_arrangement']) {
                $score += 15;
                $factors['work_arrangement_match'] = true;
            }
        }

        // Experience level compatibility (25% weight)
        $experienceScore = $this->calculateExperienceCompatibility($job, $graduate);
        $score += $experienceScore * 0.25;
        $factors['experience_compatibility'] = $experienceScore;

        return [
            'score' => round($score, 2),
            'factors' => $factors,
        ];
    }

    private function calculateExperienceCompatibility($job, $graduate)
    {
        // This is a simplified calculation - in a real system you'd have more detailed experience data
        $graduateExperience = $graduate->employment_status === 'employed' ? 1 : 0;
        $requiredExperience = $job->min_experience_years ?? 0;

        if ($requiredExperience === 0) {
            return 100; // Entry level position
        }

        if ($graduateExperience >= $requiredExperience) {
            return 100;
        }

        // Partial match based on how close they are
        return max(0, 100 - (($requiredExperience - $graduateExperience) * 20));
    }

    private function applyJobFilters(Builder $query, array $criteria)
    {
        if (! empty($criteria['keywords'])) {
            $query->where(function ($q) use ($criteria) {
                $q->where('title', 'like', "%{$criteria['keywords']}%")
                    ->orWhere('description', 'like', "%{$criteria['keywords']}%");
            });
        }

        if (! empty($criteria['location'])) {
            $query->where('location', 'like', "%{$criteria['location']}%");
        }

        if (! empty($criteria['course_id'])) {
            $query->where('course_id', $criteria['course_id']);
        }

        if (! empty($criteria['job_type'])) {
            $query->where('job_type', $criteria['job_type']);
        }

        if (! empty($criteria['experience_level'])) {
            $query->where('experience_level', $criteria['experience_level']);
        }

        if (! empty($criteria['salary_min'])) {
            $query->where(function ($q) use ($criteria) {
                $q->where('salary_min', '>=', $criteria['salary_min'])
                    ->orWhere('salary_max', '>=', $criteria['salary_min']);
            });
        }

        if (! empty($criteria['salary_max'])) {
            $query->where(function ($q) use ($criteria) {
                $q->where('salary_max', '<=', $criteria['salary_max'])
                    ->orWhere('salary_min', '<=', $criteria['salary_max']);
            });
        }

        if (! empty($criteria['skills'])) {
            $query->where(function ($q) use ($criteria) {
                foreach ($criteria['skills'] as $skill) {
                    $q->orWhereJsonContains('required_skills', $skill);
                }
            });
        }

        if (! empty($criteria['work_arrangement'])) {
            $query->where('work_arrangement', $criteria['work_arrangement']);
        }

        if (! empty($criteria['employer_verified'])) {
            $query->whereHas('employer', function ($q) {
                $q->where('verification_status', 'verified');
            });
        }

        // Sort options
        $sortBy = $criteria['sort_by'] ?? 'created_at';
        $sortOrder = $criteria['sort_order'] ?? 'desc';

        switch ($sortBy) {
            case 'salary':
                $query->orderBy('salary_max', $sortOrder);
                break;
            case 'deadline':
                $query->orderBy('application_deadline', $sortOrder);
                break;
            case 'applications':
                $query->orderBy('total_applications', $sortOrder);
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }
    }

    private function applyGraduateFilters(Builder $query, array $criteria)
    {
        if (! empty($criteria['keywords'])) {
            $query->where(function ($q) use ($criteria) {
                $q->where('name', 'like', "%{$criteria['keywords']}%")
                    ->orWhere('current_job_title', 'like', "%{$criteria['keywords']}%");
            });
        }

        if (! empty($criteria['course_id'])) {
            $query->where('course_id', $criteria['course_id']);
        }

        if (! empty($criteria['graduation_year'])) {
            if (is_array($criteria['graduation_year'])) {
                $query->whereBetween('graduation_year', $criteria['graduation_year']);
            } else {
                $query->where('graduation_year', $criteria['graduation_year']);
            }
        }

        if (! empty($criteria['employment_status'])) {
            $query->where('employment_status', $criteria['employment_status']);
        }

        if (! empty($criteria['skills'])) {
            $query->where(function ($q) use ($criteria) {
                foreach ($criteria['skills'] as $skill) {
                    $q->orWhereJsonContains('skills', $skill);
                }
            });
        }

        if (! empty($criteria['min_gpa'])) {
            $query->where('gpa', '>=', $criteria['min_gpa']);
        }

        if (! empty($criteria['max_gpa'])) {
            $query->where('gpa', '<=', $criteria['max_gpa']);
        }

        if (! empty($criteria['location'])) {
            $query->where('address', 'like', "%{$criteria['location']}%");
        }

        if (! empty($criteria['profile_completion_min'])) {
            $query->where('profile_completion_percentage', '>=', $criteria['profile_completion_min']);
        }

        // Sort options
        $sortBy = $criteria['sort_by'] ?? 'profile_completion_percentage';
        $sortOrder = $criteria['sort_order'] ?? 'desc';

        $query->orderBy($sortBy, $sortOrder);
    }

    private function applyCourseFilters(Builder $query, array $criteria)
    {
        if (! empty($criteria['keywords'])) {
            $query->where(function ($q) use ($criteria) {
                $q->where('name', 'like', "%{$criteria['keywords']}%")
                    ->orWhere('description', 'like', "%{$criteria['keywords']}%");
            });
        }

        if (! empty($criteria['level'])) {
            $query->where('level', $criteria['level']);
        }

        if (! empty($criteria['duration_min'])) {
            $query->where('duration_months', '>=', $criteria['duration_min']);
        }

        if (! empty($criteria['duration_max'])) {
            $query->where('duration_months', '<=', $criteria['duration_max']);
        }

        if (! empty($criteria['skills'])) {
            $query->where(function ($q) use ($criteria) {
                foreach ($criteria['skills'] as $skill) {
                    $q->orWhereJsonContains('skills_gained', $skill);
                }
            });
        }

        if (! empty($criteria['min_employment_rate'])) {
            $query->where('employment_rate', '>=', $criteria['min_employment_rate']);
        }

        if (! empty($criteria['featured_only'])) {
            $query->where('is_featured', true);
        }

        // Sort options
        $sortBy = $criteria['sort_by'] ?? 'employment_rate';
        $sortOrder = $criteria['sort_order'] ?? 'desc';

        $query->orderBy($sortBy, $sortOrder);
    }

    public function getSearchSuggestions($query, $type = 'all')
    {
        $suggestions = [];

        if ($type === 'all' || $type === 'jobs') {
            $jobTitles = Job::where('title', 'like', "%{$query}%")
                ->distinct()
                ->pluck('title')
                ->take(5);

            $suggestions['jobs'] = $jobTitles->map(function ($title) {
                return ['type' => 'job', 'text' => $title];
            });
        }

        if ($type === 'all' || $type === 'skills') {
            // Get skills from jobs and graduates
            $jobSkills = Job::whereJsonContains('required_skills', $query)->get()
                ->pluck('required_skills')
                ->flatten()
                ->filter(function ($skill) use ($query) {
                    return stripos($skill, $query) !== false;
                })
                ->unique()
                ->take(5);

            $suggestions['skills'] = $jobSkills->map(function ($skill) {
                return ['type' => 'skill', 'text' => $skill];
            });
        }

        if ($type === 'all' || $type === 'locations') {
            $locations = Job::where('location', 'like', "%{$query}%")
                ->distinct()
                ->pluck('location')
                ->take(5);

            $suggestions['locations'] = $locations->map(function ($location) {
                return ['type' => 'location', 'text' => $location];
            });
        }

        return $suggestions;
    }

    public function saveSearch($userId, $name, $type, $criteria, $alertEnabled = false, $alertFrequency = 'weekly')
    {
        return SavedSearch::create([
            'user_id' => $userId,
            'name' => $name,
            'search_type' => $type,
            'search_criteria' => $criteria,
            'alert_enabled' => $alertEnabled,
            'alert_frequency' => $alertFrequency,
            'is_active' => true,
        ]);
    }

    public function getUserSavedSearches($userId, $type = null)
    {
        $query = SavedSearch::where('user_id', $userId)->active();

        if ($type) {
            $query->where('search_type', $type);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function processSearchAlerts()
    {
        $searches = SavedSearch::withAlerts()->active()->get();
        $alertsSent = 0;

        foreach ($searches as $search) {
            if ($search->sendAlert()) {
                $alertsSent++;
            }
        }

        return $alertsSent;
    }
}
