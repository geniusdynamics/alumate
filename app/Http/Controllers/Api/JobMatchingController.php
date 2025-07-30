<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use App\Models\JobApplication;
use App\Models\JobMatchScore;
use App\Services\JobMatchingService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class JobMatchingController extends Controller
{
    public function __construct(
        private JobMatchingService $jobMatchingService,
        private NotificationService $notificationService
    ) {}

    /**
     * Get personalized job recommendations for the authenticated user
     */
    public function getRecommendations(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 10);
        $minScore = $request->get('min_score', 50);
        $location = $request->get('location');
        $remoteOnly = $request->boolean('remote_only');

        // Get active jobs with match scores
        $query = JobPosting::active()
            ->with(['company', 'postedBy'])
            ->whereHas('matchScores', function ($q) use ($user, $minScore) {
                $q->where('user_id', $user->id)
                  ->where('score', '>=', $minScore);
            });

        // Apply filters
        if ($location) {
            $query->byLocation($location);
        }

        if ($remoteOnly) {
            $query->remote();
        }

        $jobs = $query->paginate($perPage);

        // Transform jobs with match data
        $jobs->getCollection()->transform(function ($job) use ($user) {
            $matchScore = $job->getMatchScoreForUser($user);
            
            return [
                'id' => $job->id,
                'title' => $job->title,
                'company' => [
                    'id' => $job->company->id,
                    'name' => $job->company->name,
                    'logo_url' => $job->company->logo_url,
                ],
                'location' => $job->location,
                'remote_allowed' => $job->remote_allowed,
                'employment_type' => $job->employment_type,
                'salary_range' => $job->salary_range,
                'posted_at' => $job->created_at,
                'expires_at' => $job->expires_at,
                'match_score' => $matchScore ? [
                    'score' => $matchScore->score,
                    'percentage' => $matchScore->getMatchPercentage(),
                    'level' => $matchScore->getMatchLevel(),
                    'level_color' => $matchScore->getMatchLevelColor(),
                    'top_reasons' => $matchScore->getTopReasons(),
                    'mutual_connections_count' => $matchScore->mutual_connections_count,
                ] : null,
                'has_applied' => $job->applications()
                    ->where('user_id', $user->id)
                    ->exists(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $jobs,
            'meta' => [
                'total_jobs' => JobPosting::active()->count(),
                'matched_jobs' => $jobs->total(),
                'user_applications' => JobApplication::where('user_id', $user->id)
                    ->active()
                    ->count(),
            ]
        ]);
    }

    /**
     * Get detailed job information with match analysis
     */
    public function getJobDetails(Request $request, int $jobId): JsonResponse
    {
        $user = Auth::user();
        
        $job = JobPosting::with(['company', 'postedBy', 'applications'])
            ->findOrFail($jobId);

        if (!$job->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'This job posting is no longer active.'
            ], 404);
        }

        $matchScore = $job->getMatchScoreForUser($user);
        $mutualConnections = $this->jobMatchingService->findMutualConnections($user, $job);
        $userApplication = $job->applications()
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $job->id,
                'title' => $job->title,
                'description' => $job->description,
                'requirements' => $job->requirements,
                'location' => $job->location,
                'remote_allowed' => $job->remote_allowed,
                'employment_type' => $job->employment_type,
                'experience_level' => $job->experience_level,
                'salary_range' => $job->salary_range,
                'skills_required' => $job->skills_required,
                'posted_at' => $job->created_at,
                'expires_at' => $job->expires_at,
                'company' => [
                    'id' => $job->company->id,
                    'name' => $job->company->name,
                    'description' => $job->company->description,
                    'logo_url' => $job->company->logo_url,
                    'website' => $job->company->website,
                    'size' => $job->company->size,
                    'industry' => $job->company->industry,
                ],
                'posted_by' => [
                    'id' => $job->postedBy->id,
                    'name' => $job->postedBy->name,
                    'title' => $job->postedBy->current_title,
                    'avatar_url' => $job->postedBy->avatar_url,
                ],
                'match_analysis' => $matchScore ? [
                    'overall_score' => $matchScore->score,
                    'percentage' => $matchScore->getMatchPercentage(),
                    'level' => $matchScore->getMatchLevel(),
                    'level_color' => $matchScore->getMatchLevelColor(),
                    'breakdown' => [
                        'connections' => $matchScore->connection_score,
                        'skills' => $matchScore->skills_score,
                        'education' => $matchScore->education_score,
                        'circles' => $matchScore->circle_score,
                    ],
                    'detailed_reasons' => $matchScore->reasons,
                    'calculated_at' => $matchScore->calculated_at,
                ] : null,
                'mutual_connections' => $mutualConnections->map(function ($connection) {
                    return [
                        'id' => $connection->id,
                        'name' => $connection->name,
                        'title' => $connection->careerTimelines->first()?->title,
                        'avatar_url' => $connection->avatar_url,
                        'can_request_introduction' => true,
                    ];
                }),
                'application_status' => $userApplication ? [
                    'id' => $userApplication->id,
                    'status' => $userApplication->status,
                    'status_label' => $userApplication->getStatusLabel(),
                    'status_color' => $userApplication->getStatusColor(),
                    'applied_at' => $userApplication->applied_at,
                    'introduction_requested' => $userApplication->hasIntroductionRequest(),
                ] : null,
                'application_count' => $job->applications()->count(),
            ]
        ]);
    }

    /**
     * Apply for a job
     */
    public function apply(Request $request, int $jobId): JsonResponse
    {
        $user = Auth::user();
        
        $request->validate([
            'cover_letter' => 'required|string|max:2000',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB max
            'introduction_contact_id' => 'nullable|exists:users,id',
        ]);

        $job = JobPosting::findOrFail($jobId);

        if (!$job->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'This job posting is no longer active.'
            ], 400);
        }

        // Check if user already applied
        $existingApplication = JobApplication::where('job_id', $jobId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingApplication) {
            return response()->json([
                'success' => false,
                'message' => 'You have already applied for this position.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Handle resume upload
            $resumeUrl = null;
            if ($request->hasFile('resume')) {
                $resumePath = $request->file('resume')->store('resumes', 'private');
                $resumeUrl = Storage::url($resumePath);
            }

            // Create application
            $application = JobApplication::create([
                'job_id' => $jobId,
                'user_id' => $user->id,
                'status' => JobApplication::STATUS_PENDING,
                'applied_at' => now(),
                'cover_letter' => $request->cover_letter,
                'resume_url' => $resumeUrl,
                'introduction_requested' => $request->boolean('introduction_contact_id'),
                'introduction_contact_id' => $request->introduction_contact_id,
            ]);

            // Send notification to job poster
            $this->notificationService->sendJobApplicationNotification(
                $job->postedBy,
                $application
            );

            // If introduction requested, notify the contact
            if ($request->introduction_contact_id) {
                $contact = User::find($request->introduction_contact_id);
                $this->notificationService->sendIntroductionRequestNotification(
                    $contact,
                    $user,
                    $job
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully!',
                'data' => [
                    'application_id' => $application->id,
                    'status' => $application->status,
                    'applied_at' => $application->applied_at,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit application. Please try again.'
            ], 500);
        }
    }

    /**
     * Request introduction through mutual connection
     */
    public function requestIntroduction(Request $request, int $jobId): JsonResponse
    {
        $user = Auth::user();
        
        $request->validate([
            'contact_id' => 'required|exists:users,id',
            'message' => 'required|string|max:500',
        ]);

        $job = JobPosting::findOrFail($jobId);
        $contact = User::findOrFail($request->contact_id);

        // Verify the contact actually works at the company
        $worksAtCompany = $contact->careerTimelines()
            ->where('company', 'ILIKE', '%' . $job->company->name . '%')
            ->where('is_current', true)
            ->exists();

        if (!$worksAtCompany) {
            return response()->json([
                'success' => false,
                'message' => 'The selected contact does not appear to work at this company.'
            ], 400);
        }

        // Verify they are connected
        $areConnected = Connection::where('user_id', $user->id)
            ->where('connected_user_id', $contact->id)
            ->where('status', 'accepted')
            ->exists();

        if (!$areConnected) {
            return response()->json([
                'success' => false,
                'message' => 'You must be connected with this person to request an introduction.'
            ], 400);
        }

        // Send introduction request notification
        $this->notificationService->sendIntroductionRequestNotification(
            $contact,
            $user,
            $job,
            $request->message
        );

        return response()->json([
            'success' => true,
            'message' => 'Introduction request sent successfully!'
        ]);
    }

    /**
     * Get mutual connections at a company for a specific job
     */
    public function getJobConnections(Request $request, int $jobId): JsonResponse
    {
        $user = Auth::user();
        $job = JobPosting::findOrFail($jobId);
        
        $mutualConnections = $this->jobMatchingService->findMutualConnections($user, $job);

        return response()->json([
            'success' => true,
            'data' => $mutualConnections->map(function ($connection) {
                $currentRole = $connection->careerTimelines->first();
                
                return [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'avatar_url' => $connection->avatar_url,
                    'title' => $currentRole?->title,
                    'department' => $currentRole?->department,
                    'tenure' => $currentRole?->start_date ? 
                        $currentRole->start_date->diffForHumans() : null,
                    'mutual_circles' => $user->circles()
                        ->whereIn('id', $connection->circles->pluck('id'))
                        ->pluck('name')
                        ->toArray(),
                    'can_request_introduction' => true,
                ];
            })
        ]);
    }

    /**
     * Get user's job applications with status tracking
     */
    public function getApplications(Request $request): JsonResponse
    {
        $user = Auth::user();
        $status = $request->get('status');
        $perPage = $request->get('per_page', 10);

        $query = JobApplication::where('user_id', $user->id)
            ->with(['job.company'])
            ->orderBy('applied_at', 'desc');

        if ($status) {
            $query->byStatus($status);
        }

        $applications = $query->paginate($perPage);

        $applications->getCollection()->transform(function ($application) {
            return [
                'id' => $application->id,
                'status' => $application->status,
                'status_label' => $application->getStatusLabel(),
                'status_color' => $application->getStatusColor(),
                'applied_at' => $application->applied_at,
                'job' => [
                    'id' => $application->job->id,
                    'title' => $application->job->title,
                    'company' => [
                        'name' => $application->job->company->name,
                        'logo_url' => $application->job->company->logo_url,
                    ],
                    'location' => $application->job->location,
                    'remote_allowed' => $application->job->remote_allowed,
                ],
                'introduction_requested' => $application->hasIntroductionRequest(),
                'is_active' => $application->isActive(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $applications
        ]);
    }
}