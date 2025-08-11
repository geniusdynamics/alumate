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
                'job' => 'Job is already saved.',
            ]);
        }

        // Save the job
        SavedJob::create([
            'user_id' => $user->id,
            'job_id' => $job->id,
        ]);

        return response()->json([
            'message' => 'Job saved successfully.',
        ]);
    }

    public function unsave(Job $job)
    {
        $user = Auth::user();

        // Find and remove the saved job
        $savedJob = SavedJob::where('user_id', $user->id)
            ->where('job_id', $job->id)
            ->first();

        if (! $savedJob) {
            throw ValidationException::withMessages([
                'job' => 'Job is not saved.',
            ]);
        }

        $savedJob->delete();

        return response()->json([
            'message' => 'Job removed from saved jobs.',
        ]);
    }

    public function apply(Request $request, Job $job)
    {
        $request->validate([
            'cover_letter' => 'nullable|string|max:5000',
            'resume_url' => 'nullable|url',
            'additional_info' => 'nullable|string|max:2000',
        ]);

        $user = Auth::user();

        // Check if user already applied to this job
        $existingApplication = JobApplication::where('user_id', $user->id)
            ->where('job_id', $job->id)
            ->first();

        if ($existingApplication) {
            throw ValidationException::withMessages([
                'job' => 'You have already applied to this job.',
            ]);
        }

        // Check if job is still accepting applications
        if ($job->status !== 'active' || $job->application_deadline < now()) {
            throw ValidationException::withMessages([
                'job' => 'This job is no longer accepting applications.',
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
            'applied_at' => now(),
        ]);

        // TODO: Send notification to employer

        return response()->json([
            'message' => 'Application submitted successfully.',
            'application' => $application,
        ], 201);
    }

    public function requestReferral(Request $request)
    {
        $request->validate([
            'job_id' => 'required|exists:jobs,id',
            'referrer_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $job = Job::findOrFail($request->job_id);
        $referrer = User::findOrFail($request->referrer_id);

        // Check if connection exists
        $connection = Connection::where(function ($query) use ($user, $referrer) {
            $query->where('user_id', $user->id)
                ->where('connected_user_id', $referrer->id);
        })->orWhere(function ($query) use ($user, $referrer) {
            $query->where('user_id', $referrer->id)
                ->where('connected_user_id', $user->id);
        })->where('status', 'accepted')->first();

        if (! $connection) {
            throw ValidationException::withMessages([
                'referrer_id' => 'You must be connected to request a referral.',
            ]);
        }

        // Create referral request
        $referralRequest = \DB::table('referral_requests')->insert([
            'requester_id' => $user->id,
            'referrer_id' => $referrer->id,
            'job_id' => $job->id,
            'message' => $request->message,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // TODO: Send notification to referrer

        return response()->json([
            'success' => true,
            'message' => 'Referral request sent successfully.',
        ]);
    }
}
