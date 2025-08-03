<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CareerTimeline;
use App\Models\CareerMilestone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CareerController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'position_title' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'is_current' => 'boolean',
            'industry' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|in:full-time,part-time,contract,internship,freelance'
        ]);

        $user = Auth::user();

        // If this is marked as current, update any other current positions
        if ($request->is_current) {
            CareerTimeline::where('user_id', $user->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);
        }

        // Create career timeline entry
        $careerEntry = CareerTimeline::create([
            'user_id' => $user->id,
            'company' => $request->company_name,
            'title' => $request->position_title,
            'start_date' => $request->start_date,
            'end_date' => $request->is_current ? null : $request->end_date,
            'location' => $request->location,
            'description' => $request->description,
            'is_current' => $request->is_current ?? false,
            'industry' => $request->industry,
            'employment_type' => $request->employment_type,
        ]);

        return response()->json([
            'message' => 'Career entry added successfully.',
            'career_entry' => $careerEntry
        ], 201);
    }

    public function update(Request $request, CareerTimeline $timeline)
    {
        $user = Auth::user();

        // Check if user owns this timeline entry
        if ($timeline->user_id !== $user->id) {
            abort(403, 'You can only update your own career entries.');
        }

        $request->validate([
            'position_title' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'is_current' => 'boolean',
            'industry' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|in:full-time,part-time,contract,internship,freelance'
        ]);

        // If this is marked as current, update any other current positions
        if ($request->is_current) {
            CareerTimeline::where('user_id', $user->id)
                ->where('id', '!=', $timeline->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);
        }

        // Update career timeline entry
        $timeline->update([
            'company' => $request->company_name,
            'title' => $request->position_title,
            'start_date' => $request->start_date,
            'end_date' => $request->is_current ? null : $request->end_date,
            'location' => $request->location,
            'description' => $request->description,
            'is_current' => $request->is_current ?? false,
            'industry' => $request->industry,
            'employment_type' => $request->employment_type,
        ]);

        return response()->json([
            'message' => 'Career entry updated successfully.',
            'career_entry' => $timeline
        ]);
    }

    public function destroy(CareerTimeline $timeline)
    {
        $user = Auth::user();

        // Check if user owns this timeline entry
        if ($timeline->user_id !== $user->id) {
            abort(403, 'You can only delete your own career entries.');
        }

        $timeline->delete();

        return response()->json([
            'message' => 'Career entry deleted successfully.'
        ]);
    }

    public function storeMilestone(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:promotion,job_change,award,certification,education,achievement',
            'date' => 'required|date',
            'company' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'visibility' => 'in:public,connections,private'
        ]);

        $user = Auth::user();

        // Create career milestone
        $milestone = CareerMilestone::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'date' => $request->date,
            'company' => $request->company,
            'organization' => $request->organization,
            'visibility' => $request->visibility ?? 'public',
        ]);

        return response()->json([
            'message' => 'Career milestone added successfully.',
            'milestone' => $milestone
        ], 201);
    }

    public function storeGoal(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category' => 'required|in:career_advancement,skill_development,networking,compensation,education,entrepreneurship,giving_back,personal_growth',
            'target_date' => 'nullable|date|after:today',
            'priority' => 'in:low,medium,high,critical',
            'success_criteria' => 'nullable|string|max:1000',
            'progress' => 'integer|min:0|max:100'
        ]);

        $user = Auth::user();

        // Create career goal
        $goal = CareerMilestone::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'type' => 'goal',
            'category' => $request->category,
            'target_date' => $request->target_date,
            'priority' => $request->priority ?? 'medium',
            'success_criteria' => $request->success_criteria,
            'progress' => $request->progress ?? 0,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Career goal created successfully.',
            'goal' => $goal
        ], 201);
    }

    public function updateGoal(Request $request, CareerMilestone $goal)
    {
        $user = Auth::user();

        // Check if user owns this goal
        if ($goal->user_id !== $user->id) {
            abort(403, 'You can only update your own goals.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category' => 'required|in:career_advancement,skill_development,networking,compensation,education,entrepreneurship,giving_back,personal_growth',
            'target_date' => 'nullable|date',
            'priority' => 'in:low,medium,high,critical',
            'success_criteria' => 'nullable|string|max:1000',
            'progress' => 'integer|min:0|max:100'
        ]);

        // Update goal
        $goal->update([
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'target_date' => $request->target_date,
            'priority' => $request->priority ?? 'medium',
            'success_criteria' => $request->success_criteria,
            'progress' => $request->progress ?? $goal->progress,
        ]);

        return response()->json([
            'message' => 'Career goal updated successfully.',
            'goal' => $goal
        ]);
    }

    public function destroyGoal(CareerMilestone $goal)
    {
        $user = Auth::user();

        // Check if user owns this goal
        if ($goal->user_id !== $user->id) {
            abort(403, 'You can only delete your own goals.');
        }

        $goal->delete();

        return response()->json([
            'message' => 'Career goal deleted successfully.'
        ]);
    }

    public function completeGoal(CareerMilestone $goal)
    {
        $user = Auth::user();

        // Check if user owns this goal
        if ($goal->user_id !== $user->id) {
            abort(403, 'You can only complete your own goals.');
        }

        // Mark goal as completed
        $goal->update([
            'achieved_at' => now(),
            'progress' => 100,
            'status' => 'completed'
        ]);

        return response()->json([
            'message' => 'Congratulations! Goal marked as completed.',
            'goal' => $goal
        ]);
    }
}
