<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\SavedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class JobController extends Controller
{
    public function save(Job $job)
    {
        $user = Auth::user();

        // Check if job is already saved
        $existingSave = SavedJob::where('user_id', $user->id)
            ->where('job_id', $job->id)
            ->first();

        if ($existingSave) {
            throw ValidationException::withMessages([
                'job' => 'Job is already saved.'
            ]);
        }

        // Save the job
        SavedJob::create([
            'user_id' => $user->id,
            'job_id' => $job->id
        ]);

        return response()->json([
            'message' => 'Job saved successfully.'
        ]);
    }

    public function unsave(Job $job)
    {
        $user = Auth::user();

        // Find and remove the saved job
        $savedJob = SavedJob::where('user_id', $user->id)
            ->where('job_id', $job->id)
            ->first();

        if (!$savedJob) {
            throw ValidationException::withMessages([
                'job' => 'Job is not saved.'
            ]);
        }

        $savedJob->delete();

        return response()->json([
            'message' => 'Job removed from saved jobs.'
        ]);
    }

    public function apply(Request $request, Job $job)
    {
        $request->validate([
            'cover_letter' => 'nullable|string|max:5000',
            'resume_url' => 'nullable|url',
            'additional_info' => 'nullable|string|max:2000'
        ]);

        $user = Auth::user();

        // Check if user already applied to this job
        $existingApplication = JobApplication::where('user_id', $user->id)
            ->where('job_id', $job->id)
            ->first();

        if ($existingApplication) {
            throw ValidationException::withMessages([
                'job' => 'You have already applied to this job.'
            ]);
        }

        // Check if job is still accepting applications
        if ($job->status !== 'active' || $job->application_deadline < now()) {
            throw ValidationException::withMessages([
                'job' => 'This job is no longer accepting applications.'
            ]);
        }

        // Create job application
        $application = JobApplication::create([
            'user_id' => $user->id,
            'job_id' => $job->id,
            'cover_letter' => $request->cover_letter,
            'resume_url' => $request->resume_url ?? $user->resume_url,
            'additional_info' => $request->additional_info,
            'status' => 'pending',
            'applied_at' => now()
        ]);

        // TODO: Send notification to employer

        return response()->json([
            'message' => 'Application submitted successfully.',
            'application' => $application
        ], 201);
    }
}
