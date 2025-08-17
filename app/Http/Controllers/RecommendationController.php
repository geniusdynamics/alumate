<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Recommendation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecommendationController extends Controller
{
    public function store(Request $request, Job $job)
    {
        $request->validate([
            'recommended_id' => 'required|exists:users,id',
        ]);

        Recommendation::create([
            'job_id' => $job->id,
            'recommender_id' => Auth::id(),
            'recommended_id' => $request->recommended_id,
        ]);

        return back()->with('success', 'Recommendation sent successfully!');
    }
}
