<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Graduate;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Auth;

class JobApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \App\Models\Job  $job
     * @return \Illuminate\Http\Response
     */
    public function index(Job $job)
    {
        $this->authorize('view', $job);
        $applications = JobApplication::where('job_id', $job->id)->with('graduate')->get();
        return inertia('Jobs/Applications', ['job' => $job, 'applications' => $applications]);
    }

    /**
     * Display a listing of the authenticated user's applications.
     *
     * @return \Illuminate\Http\Response
     */
    public function myApplications()
    {
        $graduate = Graduate::where('email', Auth::user()->email)->firstOrFail();
        $applications = JobApplication::where('graduate_id', $graduate->id)->with('job')->get();
        return inertia('MyApplications/Index', ['applications' => $applications]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Job  $job
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Job $job)
    {
        $graduate = Graduate::where('email', Auth::user()->email)->firstOrFail();

        $jobApplication = JobApplication::create([
            'job_id' => $job->id,
            'graduate_id' => $graduate->id,
            'cover_letter' => $request->cover_letter,
        ]);

        $job->employer->user->notify(new \App\Notifications\JobApplicationNotification($jobApplication));

        return redirect()->route('jobs.public.index')->with('success', 'Application submitted successfully!');
    }

    /**
     * Mark the specified resource as hired.
     *
     * @param  \App\Models\JobApplication  $application
     * @return \Illuminate\Http\Response
     */
    public function hire(JobApplication $application)
    {
        $this->authorize('update', $application->job);
        $application->update(['status' => 'hired']);

        return back()->with('success', 'Applicant marked as hired!');
    }
}
