<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobListController extends Controller
{
    public function index(Request $request)
    {
        $jobs = Job::with('employer.user')
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->when($request->location, function ($query, $location) {
                $query->where('location', 'like', "%{$location}%");
            })
            ->latest()
            ->paginate(10);

        return Inertia::render('Jobs/PublicIndex', [
            'jobs' => $jobs,
            'filters' => $request->only(['search', 'location']),
        ]);
    }
}
