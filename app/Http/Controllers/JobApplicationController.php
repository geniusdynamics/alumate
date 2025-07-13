<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Graduate;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Auth;

class JobApplicationController extends Controller
{
    public function index(Job $job)
    {
        $this->authorize('view', $job);
        $applications = JobApplication::where('job_id', $job->id)->with('graduate')->get();
        return inertia('Jobs/Applications', ['job' => $job, 'applications' => $applications]);
    }

    public function myApplications()
    {
        $graduate = Graduate::where('email', Auth::user()->email)->firstOrFail();
        $applications = JobApplication::where('graduate_id', $graduate->id)->with('job')->get();
        return inertia('MyApplications/Index', ['applications' => $applications]);
    }

    public function store(Request $request, Job $job)
    {
        $graduate = Graduate::where('email', Auth::user()->email)->firstOrFail();

        JobApplication::create([
            'job_id' => $job->id,
            'graduate_id' => $graduate->id,
            'cover_letter' => $request->cover_letter,
        ]);

        return redirect()->route('jobs.public.index')->with('success', 'Application submitted successfully!');
    }
}
