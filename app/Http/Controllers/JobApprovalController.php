<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobApprovalController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Job::class); // Ensure only admins can access

        $query = Job::with(['employer.user', 'course'])
            ->where('status', 'pending_approval')
            ->orderBy('created_at', 'desc');

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhereHas('employer', function($eq) use ($request) {
                      $eq->where('company_name', 'like', "%{$request->search}%");
                  });
            });
        }

        $pendingJobs = $query->paginate(10);

        $stats = [
            'pending_count' => Job::where('status', 'pending_approval')->count(),
            'approved_today' => Job::where('status', 'active')
                ->whereDate('approved_at', today())
                ->count(),
            'rejected_today' => Job::where('status', 'cancelled')
                ->whereDate('updated_at', today())
                ->count(),
        ];

        return inertia('Admin/JobApproval/Index', [
            'pending_jobs' => $pendingJobs,
            'stats' => $stats,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Job $job)
    {
        $this->authorize('view', $job);

        if ($job->status !== 'pending_approval') {
            return redirect()->route('admin.job-approval.index')
                ->with('error', 'This job is not pending approval.');
        }

        $job->load(['employer.user', 'course', 'applications']);

        // Get employer's other jobs for context
        $employerJobs = Job::where('employer_id', $job->employer_id)
            ->where('id', '!=', $job->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return inertia('Admin/JobApproval/Show', [
            'job' => $job,
            'employer_jobs' => $employerJobs,
        ]);
    }

    public function approve(Request $request, Job $job)
    {
        $this->authorize('approve', $job);

        if ($job->status !== 'pending_approval') {
            return back()->with('error', 'This job is not pending approval.');
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $job->approve(Auth::id());
        
        // Add approval notes if provided
        if ($request->notes) {
            $job->update(['approval_notes' => $request->notes]);
        }

        // Send job to matching graduates
        $job->sendToGraduates();

        return redirect()->route('admin.job-approval.index')
            ->with('success', 'Job approved successfully and sent to matching graduates.');
    }

    public function reject(Request $request, Job $job)
    {
        $this->authorize('reject', $job);

        if ($job->status !== 'pending_approval') {
            return back()->with('error', 'This job is not pending approval.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $job->reject($request->reason);

        // Notify employer about rejection
        // You could send an email notification here

        return redirect()->route('admin.job-approval.index')
            ->with('success', 'Job rejected successfully.');
    }

    public function bulkApprove(Request $request)
    {
        $this->authorize('viewAny', Job::class);

        $request->validate([
            'job_ids' => 'required|array',
            'job_ids.*' => 'exists:jobs,id',
        ]);

        $jobs = Job::whereIn('id', $request->job_ids)
            ->where('status', 'pending_approval')
            ->get();

        $approvedCount = 0;
        foreach ($jobs as $job) {
            $job->approve(Auth::id());
            $job->sendToGraduates();
            $approvedCount++;
        }

        return back()->with('success', "Successfully approved {$approvedCount} jobs.");
    }

    public function bulkReject(Request $request)
    {
        $this->authorize('manage-jobs');

        $request->validate([
            'job_ids' => 'required|array',
            'job_ids.*' => 'exists:jobs,id',
            'reason' => 'required|string|max:500',
        ]);

        $jobs = Job::whereIn('id', $request->job_ids)
            ->where('status', 'pending_approval')
            ->get();

        $rejectedCount = 0;
        foreach ($jobs as $job) {
            $job->reject($request->reason);
            $rejectedCount++;
        }

        return back()->with('success', "Successfully rejected {$rejectedCount} jobs.");
    }
}