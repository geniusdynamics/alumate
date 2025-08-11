<?php

namespace App\Http\Controllers;

use App\Models\Graduate;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class JobApplicationController extends Controller
{
    public function index(Request $request, Job $job)
    {
        $this->authorize('view', $job);

        $query = JobApplication::where('job_id', $job->id)
            ->with(['graduate.user', 'graduate.course']);

        // Apply filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->flagged) {
            $query->where('is_flagged', true);
        }

        if ($request->search) {
            $query->whereHas('graduate', function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->course_id) {
            $query->whereHas('graduate', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }

        if ($request->gpa_min) {
            $query->whereHas('graduate', function ($q) use ($request) {
                $q->where('gpa', '>=', $request->gpa_min);
            });
        }

        if ($request->skills && is_array($request->skills)) {
            $query->whereHas('graduate', function ($q) use ($request) {
                foreach ($request->skills as $skill) {
                    $q->whereJsonContains('skills', $skill);
                }
            });
        }

        // Sorting
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';

        switch ($sortBy) {
            case 'match_score':
                $query->orderBy('match_score', $sortOrder);
                break;
            case 'gpa':
                $query->join('graduates', 'job_applications.graduate_id', '=', 'graduates.id')
                    ->orderBy('graduates.gpa', $sortOrder);
                break;
            case 'status':
                $query->orderBy('status', $sortOrder);
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        $applications = $query->paginate(20);

        // Calculate match scores for applications without them
        foreach ($applications as $application) {
            if (! $application->match_score) {
                $application->calculateMatchScore();
            }
        }

        // Get application statistics
        $stats = [
            'total' => JobApplication::where('job_id', $job->id)->count(),
            'pending' => JobApplication::where('job_id', $job->id)->where('status', 'pending')->count(),
            'reviewed' => JobApplication::where('job_id', $job->id)->where('status', 'reviewed')->count(),
            'shortlisted' => JobApplication::where('job_id', $job->id)->where('status', 'shortlisted')->count(),
            'interviewed' => JobApplication::where('job_id', $job->id)->whereIn('status', ['interview_scheduled', 'interviewed'])->count(),
            'hired' => JobApplication::where('job_id', $job->id)->where('status', 'hired')->count(),
            'rejected' => JobApplication::where('job_id', $job->id)->where('status', 'rejected')->count(),
            'flagged' => JobApplication::where('job_id', $job->id)->where('is_flagged', true)->count(),
            'avg_match_score' => JobApplication::where('job_id', $job->id)->avg('match_score'),
        ];

        return Inertia::render('Jobs/Applications/Index', [
            'job' => $job->load('employer', 'course'),
            'applications' => $applications,
            'stats' => $stats,
            'filters' => $request->only(['status', 'priority', 'flagged', 'search', 'course_id', 'gpa_min', 'skills', 'sort_by', 'sort_order']),
            'courses' => \App\Models\Course::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(JobApplication $application)
    {
        $this->authorize('view', $application->job);

        $application->load(['graduate.user', 'graduate.course', 'job.employer', 'statusChanger']);

        // Calculate match score if not exists
        if (! $application->match_score) {
            $application->calculateMatchScore();
        }

        // Get other applications from this graduate
        $otherApplications = JobApplication::where('graduate_id', $application->graduate_id)
            ->where('id', '!=', $application->id)
            ->with('job.employer')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Jobs/Applications/Show', [
            'application' => $application,
            'other_applications' => $otherApplications,
        ]);
    }

    public function myApplications(Request $request)
    {
        $graduate = Graduate::where('user_id', Auth::id())->first();

        if (! $graduate) {
            return redirect()->route('dashboard')->with('error', 'Graduate profile not found.');
        }

        $query = JobApplication::where('graduate_id', $graduate->id)
            ->with(['job.employer', 'job.course']);

        // Apply filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->whereHas('job', function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhereHas('employer', function ($eq) use ($request) {
                        $eq->where('company_name', 'like', "%{$request->search}%");
                    });
            });
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(10);

        // Get application statistics
        $stats = [
            'total' => JobApplication::where('graduate_id', $graduate->id)->count(),
            'pending' => JobApplication::where('graduate_id', $graduate->id)->where('status', 'pending')->count(),
            'interviewed' => JobApplication::where('graduate_id', $graduate->id)->whereIn('status', ['interview_scheduled', 'interviewed'])->count(),
            'offers' => JobApplication::where('graduate_id', $graduate->id)->where('status', 'offer_made')->count(),
            'hired' => JobApplication::where('graduate_id', $graduate->id)->where('status', 'hired')->count(),
            'rejected' => JobApplication::where('graduate_id', $graduate->id)->where('status', 'rejected')->count(),
        ];

        return Inertia::render('Applications/MyApplications', [
            'applications' => $applications,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function store(Request $request, Job $job)
    {
        $graduate = Graduate::where('user_id', Auth::id())->first();

        if (! $graduate) {
            return back()->with('error', 'Graduate profile not found.');
        }

        // Check if already applied
        $existingApplication = JobApplication::where('job_id', $job->id)
            ->where('graduate_id', $graduate->id)
            ->first();

        if ($existingApplication) {
            return back()->with('error', 'You have already applied for this job.');
        }

        $request->validate([
            'cover_letter' => 'required|string|max:2000',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB max
            'additional_documents.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $applicationData = [
            'job_id' => $job->id,
            'graduate_id' => $graduate->id,
            'cover_letter' => $request->cover_letter,
            'status' => 'pending',
            'application_source' => 'web',
        ];

        // Handle resume upload
        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('resumes', 'private');
            $applicationData['resume_file_path'] = $resumePath;
            $applicationData['resume_data'] = [
                'original_name' => $request->file('resume')->getClientOriginalName(),
                'size' => $request->file('resume')->getSize(),
                'mime_type' => $request->file('resume')->getMimeType(),
            ];
        }

        // Handle additional documents
        if ($request->hasFile('additional_documents')) {
            $documents = [];
            foreach ($request->file('additional_documents') as $file) {
                $path = $file->store('application-documents', 'private');
                $documents[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ];
            }
            $applicationData['additional_documents'] = $documents;
        }

        $application = JobApplication::create($applicationData);

        // Calculate match score
        $application->calculateMatchScore();

        // Update job application count
        $job->increment('total_applications');

        // Send notification to employer
        $job->employer->user->notify(new \App\Notifications\JobApplicationNotification($application));

        return redirect()->route('my.applications')->with('success', 'Application submitted successfully!');
    }

    public function updateStatus(Request $request, JobApplication $application)
    {
        $this->authorize('update', $application->job);

        $request->validate([
            'status' => ['required', Rule::in([
                'pending', 'reviewed', 'shortlisted', 'interview_scheduled',
                'interviewed', 'reference_check', 'offer_made', 'offer_accepted',
                'offer_declined', 'hired', 'rejected', 'withdrawn',
            ])],
            'notes' => 'nullable|string|max:1000',
        ]);

        $application->updateStatus($request->status, Auth::id(), $request->notes);

        return back()->with('success', 'Application status updated successfully.');
    }

    public function scheduleInterview(Request $request, JobApplication $application)
    {
        $this->authorize('update', $application->job);

        $request->validate([
            'interview_date' => 'required|date|after:now',
            'interview_location' => 'nullable|string|max:255',
            'interview_notes' => 'nullable|string|max:1000',
        ]);

        $application->scheduleInterview(
            $request->interview_date,
            $request->interview_location,
            $request->interview_notes
        );

        // Send notification to graduate
        $application->graduate->user->notify(new \App\Notifications\InterviewScheduledNotification($application));

        return back()->with('success', 'Interview scheduled successfully.');
    }

    public function makeOffer(Request $request, JobApplication $application)
    {
        $this->authorize('update', $application->job);

        $request->validate([
            'offered_salary' => 'required|numeric|min:0',
            'offer_expiry_date' => 'nullable|date|after:now',
            'offer_terms' => 'nullable|array',
        ]);

        $application->makeOffer(
            $request->offered_salary,
            $request->offer_expiry_date,
            $request->offer_terms ?? []
        );

        // Send notification to graduate
        $application->graduate->user->notify(new \App\Notifications\JobOfferNotification($application));

        return back()->with('success', 'Job offer made successfully.');
    }

    public function respondToOffer(Request $request, JobApplication $application)
    {
        // Ensure the authenticated user is the graduate who received the offer
        $graduate = Graduate::where('user_id', Auth::id())->first();
        if (! $graduate || $application->graduate_id !== $graduate->id) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'response' => ['required', Rule::in(['accept', 'decline'])],
            'message' => 'nullable|string|max:1000',
        ]);

        if ($request->response === 'accept') {
            $application->acceptOffer($request->message);
            $message = 'Job offer accepted successfully!';
        } else {
            $application->declineOffer($request->message);
            $message = 'Job offer declined.';
        }

        // Notify employer
        $application->job->employer->user->notify(new \App\Notifications\OfferResponseNotification($application));

        return back()->with('success', $message);
    }

    public function reject(Request $request, JobApplication $application)
    {
        $this->authorize('update', $application->job);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $application->reject($request->reason, Auth::id());

        // Send notification to graduate
        $application->graduate->user->notify(new \App\Notifications\ApplicationRejectedNotification($application));

        return back()->with('success', 'Application rejected.');
    }

    public function flag(Request $request, JobApplication $application)
    {
        $this->authorize('update', $application->job);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $application->flag($request->reason, Auth::id());

        return back()->with('success', 'Application flagged for review.');
    }

    public function unflag(JobApplication $application)
    {
        $this->authorize('update', $application->job);

        $application->unflag();

        return back()->with('success', 'Application flag removed.');
    }

    public function bulkAction(Request $request, Job $job)
    {
        $this->authorize('update', $job);

        $request->validate([
            'action' => ['required', Rule::in(['reject', 'shortlist', 'review', 'flag'])],
            'application_ids' => 'required|array',
            'application_ids.*' => 'exists:job_applications,id',
            'reason' => 'required_if:action,reject,flag|string|max:500',
        ]);

        $applications = JobApplication::whereIn('id', $request->application_ids)
            ->where('job_id', $job->id)
            ->get();

        $count = 0;
        foreach ($applications as $application) {
            switch ($request->action) {
                case 'reject':
                    $application->reject($request->reason, Auth::id());
                    $count++;
                    break;
                case 'shortlist':
                    $application->updateStatus('shortlisted', Auth::id());
                    $count++;
                    break;
                case 'review':
                    $application->updateStatus('reviewed', Auth::id());
                    $count++;
                    break;
                case 'flag':
                    $application->flag($request->reason, Auth::id());
                    $count++;
                    break;
            }
        }

        $actionText = [
            'reject' => 'rejected',
            'shortlist' => 'shortlisted',
            'review' => 'marked as reviewed',
            'flag' => 'flagged',
        ];

        return back()->with('success', "Successfully {$actionText[$request->action]} {$count} applications.");
    }

    public function downloadResume(JobApplication $application)
    {
        $this->authorize('view', $application->job);

        if (! $application->resume_file_path || ! Storage::disk('private')->exists($application->resume_file_path)) {
            abort(404, 'Resume not found.');
        }

        $resumeData = $application->resume_data;
        $fileName = $resumeData['original_name'] ?? 'resume.pdf';

        return Storage::disk('private')->download($application->resume_file_path, $fileName);
    }

    public function downloadDocument(JobApplication $application, $documentIndex)
    {
        $this->authorize('view', $application->job);

        $documents = $application->additional_documents ?? [];

        if (! isset($documents[$documentIndex])) {
            abort(404, 'Document not found.');
        }

        $document = $documents[$documentIndex];

        if (! Storage::disk('private')->exists($document['path'])) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('private')->download($document['path'], $document['original_name']);
    }

    public function analytics(Job $job)
    {
        $this->authorize('view', $job);

        $applications = JobApplication::where('job_id', $job->id)->with('graduate')->get();

        $analytics = [
            'total_applications' => $applications->count(),
            'status_breakdown' => $applications->groupBy('status')->map->count(),
            'applications_by_course' => $applications->groupBy('graduate.course.name')->map->count(),
            'applications_by_gpa' => [
                '3.5+' => $applications->filter(fn ($app) => $app->graduate->gpa >= 3.5)->count(),
                '3.0-3.49' => $applications->filter(fn ($app) => $app->graduate->gpa >= 3.0 && $app->graduate->gpa < 3.5)->count(),
                '2.5-2.99' => $applications->filter(fn ($app) => $app->graduate->gpa >= 2.5 && $app->graduate->gpa < 3.0)->count(),
                'Below 2.5' => $applications->filter(fn ($app) => $app->graduate->gpa < 2.5)->count(),
            ],
            'average_match_score' => $applications->avg('match_score'),
            'application_timeline' => $applications->groupBy(function ($app) {
                return $app->created_at->format('Y-m-d');
            })->map->count(),
            'conversion_funnel' => [
                'applied' => $applications->count(),
                'reviewed' => $applications->where('status', '!=', 'pending')->count(),
                'shortlisted' => $applications->where('status', 'shortlisted')->count(),
                'interviewed' => $applications->whereIn('status', ['interview_scheduled', 'interviewed'])->count(),
                'offered' => $applications->where('status', 'offer_made')->count(),
                'hired' => $applications->where('status', 'hired')->count(),
            ],
        ];

        return Inertia::render('Jobs/Applications/Analytics', [
            'job' => $job,
            'analytics' => $analytics,
        ]);
    }
}
