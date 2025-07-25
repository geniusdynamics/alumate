<?php

namespace App\Http\Controllers;

use App\Models\EmployerRating;
use App\Models\Employer;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmployerRatingController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployerRating::with(['employer', 'graduate.user', 'job'])
            ->approved();

        if ($request->filled('employer_id')) {
            $query->forEmployer($request->employer_id);
        }

        if ($request->filled('rating')) {
            $query->byRating($request->rating);
        }

        if ($request->filled('min_rating')) {
            $query->minRating($request->min_rating);
        }

        $ratings = $query->orderByDesc('created_at')->paginate(15);
        $employers = Employer::select('id', 'company_name')->get();

        return Inertia::render('EmployerRatings/Index', [
            'ratings' => $ratings,
            'employers' => $employers,
            'filters' => $request->only(['employer_id', 'rating', 'min_rating']),
        ]);
    }

    public function show(EmployerRating $rating)
    {
        if (!$rating->is_approved) {
            abort(404);
        }

        $rating->load(['employer', 'graduate.user', 'job']);

        return Inertia::render('EmployerRatings/Show', [
            'rating' => $rating,
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->graduate) {
            abort(403, 'Only graduates can rate employers.');
        }

        $employerId = $request->get('employer_id');
        $jobId = $request->get('job_id');

        $employer = null;
        $job = null;

        if ($employerId) {
            $employer = Employer::findOrFail($employerId);
        }

        if ($jobId) {
            $job = Job::with('employer')->findOrFail($jobId);
            $employer = $job->employer;
        }

        // Check if user has already rated this employer for this job
        if ($employer && $job) {
            $existingRating = EmployerRating::where('employer_id', $employer->id)
                ->where('graduate_id', $user->graduate->id)
                ->where('job_id', $job->id)
                ->first();

            if ($existingRating) {
                return redirect()->route('employer-ratings.edit', $existingRating)
                    ->with('info', 'You have already rated this employer for this job. You can edit your rating.');
            }
        }

        return Inertia::render('EmployerRatings/Create', [
            'employer' => $employer,
            'job' => $job,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->graduate) {
            abort(403, 'Only graduates can rate employers.');
        }

        $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'job_id' => 'nullable|exists:jobs,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'rating_categories' => 'nullable|array',
            'rating_categories.work_environment' => 'nullable|integer|min:1|max:5',
            'rating_categories.management' => 'nullable|integer|min:1|max:5',
            'rating_categories.benefits' => 'nullable|integer|min:1|max:5',
            'rating_categories.career_growth' => 'nullable|integer|min:1|max:5',
            'rating_categories.work_life_balance' => 'nullable|integer|min:1|max:5',
            'is_anonymous' => 'boolean',
        ]);

        // Check for duplicate rating
        $existingRating = EmployerRating::where('employer_id', $request->employer_id)
            ->where('graduate_id', $user->graduate->id)
            ->where('job_id', $request->job_id)
            ->first();

        if ($existingRating) {
            return back()->withErrors(['employer_id' => 'You have already rated this employer for this job.']);
        }

        $rating = EmployerRating::create([
            'employer_id' => $request->employer_id,
            'graduate_id' => $user->graduate->id,
            'job_id' => $request->job_id,
            'rating' => $request->rating,
            'review' => $request->review,
            'rating_categories' => $request->rating_categories,
            'is_anonymous' => $request->is_anonymous ?? false,
        ]);

        return redirect()->route('employer-ratings.show', $rating)
            ->with('success', 'Rating submitted successfully! It will be reviewed before being published.');
    }

    public function edit(EmployerRating $rating)
    {
        $user = Auth::user();
        
        if (!$rating->canBeEditedBy($user)) {
            abort(403, 'You cannot edit this rating.');
        }

        $rating->load(['employer', 'job']);

        return Inertia::render('EmployerRatings/Edit', [
            'rating' => $rating,
        ]);
    }

    public function update(Request $request, EmployerRating $rating)
    {
        $user = Auth::user();
        
        if (!$rating->canBeEditedBy($user)) {
            abort(403, 'You cannot edit this rating.');
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'rating_categories' => 'nullable|array',
            'rating_categories.work_environment' => 'nullable|integer|min:1|max:5',
            'rating_categories.management' => 'nullable|integer|min:1|max:5',
            'rating_categories.benefits' => 'nullable|integer|min:1|max:5',
            'rating_categories.career_growth' => 'nullable|integer|min:1|max:5',
            'rating_categories.work_life_balance' => 'nullable|integer|min:1|max:5',
            'is_anonymous' => 'boolean',
        ]);

        $rating->update([
            'rating' => $request->rating,
            'review' => $request->review,
            'rating_categories' => $request->rating_categories,
            'is_anonymous' => $request->is_anonymous ?? false,
            'is_approved' => false, // Reset approval status
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return redirect()->route('employer-ratings.show', $rating)
            ->with('success', 'Rating updated successfully! It will be reviewed again before being published.');
    }

    public function destroy(EmployerRating $rating)
    {
        $user = Auth::user();
        
        if ($rating->graduate->user_id !== $user->id) {
            abort(403, 'You cannot delete this rating.');
        }

        $rating->delete();

        return redirect()->route('employer-ratings.index')
            ->with('success', 'Rating deleted successfully!');
    }

    public function approve(EmployerRating $rating)
    {
        $this->authorize('moderate', $rating);

        $rating->approve(Auth::user());

        return back()->with('success', 'Rating approved successfully!');
    }

    public function reject(EmployerRating $rating)
    {
        $this->authorize('moderate', $rating);

        $rating->reject();

        return back()->with('success', 'Rating rejected!');
    }

    public function moderate(Request $request)
    {
        $this->authorize('moderate', EmployerRating::class);

        $query = EmployerRating::with(['employer', 'graduate.user', 'job']);

        if ($request->get('status') === 'pending') {
            $query->pending();
        } else {
            $query->approved();
        }

        $ratings = $query->orderByDesc('created_at')->paginate(15);

        return Inertia::render('EmployerRatings/Moderate', [
            'ratings' => $ratings,
            'status' => $request->get('status', 'approved'),
        ]);
    }

    public function getEmployerRatings(Employer $employer)
    {
        $ratings = EmployerRating::forEmployer($employer->id)
            ->approved()
            ->with(['graduate.user', 'job'])
            ->orderByDesc('created_at')
            ->get();

        $averageRating = $ratings->avg('rating');
        $totalRatings = $ratings->count();

        $ratingBreakdown = [
            5 => $ratings->where('rating', 5)->count(),
            4 => $ratings->where('rating', 4)->count(),
            3 => $ratings->where('rating', 3)->count(),
            2 => $ratings->where('rating', 2)->count(),
            1 => $ratings->where('rating', 1)->count(),
        ];

        return response()->json([
            'ratings' => $ratings,
            'average_rating' => round($averageRating, 1),
            'total_ratings' => $totalRatings,
            'rating_breakdown' => $ratingBreakdown,
        ]);
    }
}