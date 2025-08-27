<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\SavedJob;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    /**
     * Show a specific job
     */
    public function show(Job $job)
    {
        $user = Auth::user();

        $job->load(['company']);

        // Check if job is saved by user
        $isSaved = SavedJob::where('user_id', $user->id)
            ->where('job_id', $job->id)
            ->exists();

        return response()->json([
            'job' => array_merge($job->toArray(), [
                'is_saved' => $isSaved,
            ]),
        ]);
    }

    /**
     * Save a job
     */
    public function save(Job $job)
    {
        $user = Auth::user();

        $savedJob = SavedJob::firstOrCreate([
            'user_id' => $user->id,
            'job_id' => $job->id,
        ]);

        return response()->json([
            'message' => 'Job saved successfully',
            'saved' => true,
        ]);
    }

    /**
     * Unsave a job
     */
    public function unsave(Job $job)
    {
        $user = Auth::user();

        SavedJob::where('user_id', $user->id)
            ->where('job_id', $job->id)
            ->delete();

        return response()->json([
            'message' => 'Job removed from saved jobs',
            'saved' => false,
        ]);
    }
}
