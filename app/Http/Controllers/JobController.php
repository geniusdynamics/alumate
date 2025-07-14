<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    public function index()
    {
        $jobs = Job::where('employer_id', Auth::user()->employer->id)->get();
        return inertia('Jobs/Index', ['jobs' => $jobs]);
    }

    public function create()
    {
        return inertia('Jobs/Create');
    }

    public function store(Request $request)
    {
        if (! Auth::user()->employer->approved) {
            return back()->with('error', 'Your company has not been approved yet.');
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'nullable|string|max:255',
            'salary' => 'nullable|string|max:255',
            'curated_courses' => 'nullable|json',
            'external_application_link' => 'nullable|url',
        ]);

        Auth::user()->employer->jobs()->create($data);

        return redirect()->route('jobs.index');
    }

    public function edit(Job $job)
    {
        $this->authorize('update', $job);
        return inertia('Jobs/Edit', ['job' => $job]);
    }

    public function update(Request $request, Job $job)
    {
        $this->authorize('update', $job);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'nullable|string|max:255',
            'salary' => 'nullable|string|max:255',
            'curated_courses' => 'nullable|json',
            'external_application_link' => 'nullable|url',
        ]);

        $job->update($data);

        return redirect()->route('jobs.index');
    }

    public function destroy(Job $job)
    {
        $this->authorize('delete', $job);
        $job->delete();

        return redirect()->route('jobs.index');
    }
}
