<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CareerTimeline;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\SuccessStory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentCareerGuidanceController extends Controller
{
    /**
     * Get personalized career recommendations for student
     */
    public function getCareerRecommendations(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required',
            ], 403);
        }

        $recommendations = $this->generateCareerRecommendations($studentProfile);

        return response()->json([
            'success' => true,
            'data' => $recommendations,
            'student_context' => [
                'career_interests' => $studentProfile->career_interests,
                'skills' => $studentProfile->skills,
                'course' => $studentProfile->course->name ?? null,
                'graduation_year' => $studentProfile->expected_graduation_year,
            ],
        ]);
    }

    /**
     * Get career paths from alumni in the same course
     */
    public function getCareerPaths(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required',
            ], 403);
        }

        $careerPaths = $this->getAlumniCareerPaths($studentProfile);

        return response()->json([
            'success' => true,
            'data' => $careerPaths,
        ]);
    }

    /**
     * Get industry insights and trends
     */
    public function getIndustryInsights(Request $request): JsonResponse
    {
        $industry = $request->get('industry');

        if (! $industry) {
            return response()->json([
                'message' => 'Industry parameter required',
            ], 400);
        }

        $insights = $this->generateIndustryInsights($industry);

        return response()->json([
            'success' => true,
            'data' => $insights,
            'industry' => $industry,
        ]);
    }

    /**
     * Get salary insights for career interests
     */
    public function getSalaryInsights(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required',
            ], 403);
        }

        $salaryData = $this->generateSalaryInsights($studentProfile);

        return response()->json([
            'success' => true,
            'data' => $salaryData,
        ]);
    }

    /**
     * Get skill gap analysis
     */
    public function getSkillGapAnalysis(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required',
            ], 403);
        }

        $targetRole = $request->get('target_role');
        $skillGaps = $this->analyzeSkillGaps($studentProfile, $targetRole);

        return response()->json([
            'success' => true,
            'data' => $skillGaps,
            'target_role' => $targetRole,
        ]);
    }

    /**
     * Get job market trends
     */
    public function getJobMarketTrends(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required',
            ], 403);
        }

        $trends = $this->generateJobMarketTrends($studentProfile);

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * Get networking recommendations
     */
    public function getNetworkingRecommendations(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required',
            ], 403);
        }

        $recommendations = $this->generateNetworkingRecommendations($studentProfile);

        return response()->json([
            'success' => true,
            'data' => $recommendations,
        ]);
    }

    /**
     * Get career timeline examples
     */
    public function getCareerTimelineExamples(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required',
            ], 403);
        }

        $timelines = $this->getRelevantCareerTimelines($studentProfile);

        return response()->json([
            'success' => true,
            'data' => $timelines,
        ]);
    }

    /**
     * Generate personalized career recommendations
     */
    private function generateCareerRecommendations($studentProfile)
    {
        $recommendations = [];

        // Get popular roles from same course
        if ($studentProfile->course_id) {
            $popularRoles = Graduate::where('course_id', $studentProfile->course_id)
                ->whereNotNull('current_position')
                ->select('current_position', DB::raw('COUNT(*) as count'))
                ->groupBy('current_position')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get();

            $recommendations['popular_roles_in_course'] = $popularRoles;
        }

        // Get roles matching career interests
        if ($studentProfile->career_interests) {
            $matchingRoles = [];
            foreach ($studentProfile->career_interests as $interest) {
                $roles = Graduate::where('current_position', 'like', "%{$interest}%")
                    ->orWhere('industry', 'like', "%{$interest}%")
                    ->select('current_position', 'industry', 'current_company')
                    ->distinct()
                    ->limit(3)
                    ->get();

                $matchingRoles[$interest] = $roles;
            }
            $recommendations['roles_by_interest'] = $matchingRoles;
        }

        // Get emerging opportunities
        $emergingRoles = Job::where('created_at', '>=', now()->subMonths(6))
            ->select('title', DB::raw('COUNT(*) as demand'))
            ->groupBy('title')
            ->orderBy('demand', 'desc')
            ->limit(5)
            ->get();

        $recommendations['emerging_opportunities'] = $emergingRoles;

        return $recommendations;
    }

    /**
     * Get alumni career paths
     */
    private function getAlumniCareerPaths($studentProfile)
    {
        $careerPaths = [];

        if ($studentProfile->course_id) {
            // Get career progressions from same course
            $alumni = Graduate::where('course_id', $studentProfile->course_id)
                ->with(['user.careerTimelines' => function ($query) {
                    $query->orderBy('start_date', 'asc');
                }])
                ->whereHas('user.careerTimelines')
                ->limit(10)
                ->get();

            foreach ($alumni as $alumnus) {
                $timeline = $alumnus->user->careerTimelines;
                if ($timeline->count() > 1) {
                    $careerPaths[] = [
                        'alumni_name' => $alumnus->user->name,
                        'graduation_year' => $alumnus->graduation_year,
                        'current_position' => $alumnus->current_position,
                        'current_company' => $alumnus->current_company,
                        'career_progression' => $timeline->map(function ($milestone) {
                            return [
                                'position' => $milestone->position,
                                'company' => $milestone->company,
                                'start_date' => $milestone->start_date,
                                'end_date' => $milestone->end_date,
                                'duration_months' => $milestone->duration_months,
                            ];
                        }),
                    ];
                }
            }
        }

        return $careerPaths;
    }

    /**
     * Generate industry insights
     */
    private function generateIndustryInsights($industry)
    {
        $insights = [];

        // Alumni in this industry
        $alumniCount = Graduate::where('industry', 'like', "%{$industry}%")->count();
        $insights['alumni_in_industry'] = $alumniCount;

        // Popular companies
        $popularCompanies = Graduate::where('industry', 'like', "%{$industry}%")
            ->whereNotNull('current_company')
            ->select('current_company', DB::raw('COUNT(*) as count'))
            ->groupBy('current_company')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        $insights['popular_companies'] = $popularCompanies;

        // Job opportunities
        $jobCount = Job::where('description', 'like', "%{$industry}%")
            ->orWhere('title', 'like', "%{$industry}%")
            ->where('created_at', '>=', now()->subMonths(3))
            ->count();

        $insights['recent_job_postings'] = $jobCount;

        // Success stories
        $successStories = SuccessStory::where('industry', 'like', "%{$industry}%")
            ->published()
            ->orderBy('view_count', 'desc')
            ->limit(3)
            ->get(['id', 'title', 'summary', 'current_role', 'current_company']);

        $insights['success_stories'] = $successStories;

        return $insights;
    }

    /**
     * Generate salary insights
     */
    private function generateSalaryInsights($studentProfile)
    {
        $salaryData = [];

        if ($studentProfile->career_interests) {
            foreach ($studentProfile->career_interests as $interest) {
                // This would typically connect to salary data APIs or internal salary data
                // For now, we'll provide general guidance
                $salaryData[$interest] = [
                    'entry_level_range' => '$45,000 - $65,000',
                    'mid_level_range' => '$65,000 - $95,000',
                    'senior_level_range' => '$95,000 - $150,000+',
                    'factors' => [
                        'Location significantly impacts salary',
                        'Company size and industry matter',
                        'Skills and certifications can increase earning potential',
                        'Experience and performance drive progression',
                    ],
                ];
            }
        }

        return $salaryData;
    }

    /**
     * Analyze skill gaps
     */
    private function analyzeSkillGaps($studentProfile, $targetRole)
    {
        $analysis = [];

        if ($targetRole) {
            // Get skills from alumni in similar roles
            $requiredSkills = Graduate::where('current_position', 'like', "%{$targetRole}%")
                ->with('user.skills')
                ->get()
                ->flatMap(function ($graduate) {
                    return $graduate->user->skills ?? [];
                })
                ->countBy()
                ->sortDesc()
                ->take(10)
                ->keys()
                ->toArray();

            $currentSkills = $studentProfile->skills ?? [];
            $missingSkills = array_diff($requiredSkills, $currentSkills);

            $analysis = [
                'target_role' => $targetRole,
                'required_skills' => $requiredSkills,
                'current_skills' => $currentSkills,
                'missing_skills' => $missingSkills,
                'skill_match_percentage' => count($currentSkills) > 0 ?
                    (count(array_intersect($requiredSkills, $currentSkills)) / count($requiredSkills)) * 100 : 0,
                'recommendations' => $this->generateSkillRecommendations($missingSkills),
            ];
        }

        return $analysis;
    }

    /**
     * Generate job market trends
     */
    private function generateJobMarketTrends($studentProfile)
    {
        $trends = [];

        // Trending job titles
        $trendingJobs = Job::where('created_at', '>=', now()->subMonths(3))
            ->select('title', DB::raw('COUNT(*) as count'))
            ->groupBy('title')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $trends['trending_jobs'] = $trendingJobs;

        // Skills in demand
        $inDemandSkills = Job::where('created_at', '>=', now()->subMonths(3))
            ->whereNotNull('required_skills')
            ->get()
            ->flatMap(function ($job) {
                return $job->required_skills ?? [];
            })
            ->countBy()
            ->sortDesc()
            ->take(10);

        $trends['in_demand_skills'] = $inDemandSkills;

        return $trends;
    }

    /**
     * Generate networking recommendations
     */
    private function generateNetworkingRecommendations($studentProfile)
    {
        $recommendations = [];

        // Alumni to connect with
        if ($studentProfile->career_interests) {
            foreach ($studentProfile->career_interests as $interest) {
                $relevantAlumni = Graduate::where('industry', 'like', "%{$interest}%")
                    ->orWhere('current_position', 'like', "%{$interest}%")
                    ->with('user')
                    ->limit(5)
                    ->get();

                $recommendations['alumni_by_interest'][$interest] = $relevantAlumni;
            }
        }

        // Events to attend
        $recommendations['suggested_actions'] = [
            'Join professional associations in your field of interest',
            'Attend industry conferences and networking events',
            'Connect with alumni on LinkedIn',
            'Participate in career fairs and company information sessions',
            'Join relevant online communities and forums',
        ];

        return $recommendations;
    }

    /**
     * Get relevant career timelines
     */
    private function getRelevantCareerTimelines($studentProfile)
    {
        $timelines = [];

        if ($studentProfile->course_id) {
            $careerTimelines = CareerTimeline::whereHas('user.graduate', function ($query) use ($studentProfile) {
                $query->where('course_id', $studentProfile->course_id);
            })
                ->with(['user.graduate', 'milestones'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $timelines = $careerTimelines->map(function ($timeline) {
                return [
                    'alumni_name' => $timeline->user->name,
                    'graduation_year' => $timeline->user->graduate->graduation_year ?? null,
                    'current_position' => $timeline->user->graduate->current_position ?? null,
                    'milestones' => $timeline->milestones->map(function ($milestone) {
                        return [
                            'title' => $milestone->title,
                            'description' => $milestone->description,
                            'date' => $milestone->date,
                            'category' => $milestone->category,
                        ];
                    }),
                ];
            });
        }

        return $timelines;
    }

    /**
     * Generate skill recommendations
     */
    private function generateSkillRecommendations($missingSkills)
    {
        $recommendations = [];

        foreach ($missingSkills as $skill) {
            $recommendations[] = [
                'skill' => $skill,
                'learning_resources' => [
                    'Online courses (Coursera, Udemy, LinkedIn Learning)',
                    'Professional certifications',
                    'Workshops and seminars',
                    'Hands-on projects and internships',
                ],
                'priority' => 'high', // This could be calculated based on demand
            ];
        }

        return $recommendations;
    }
}
