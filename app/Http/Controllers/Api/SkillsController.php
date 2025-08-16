<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SkillsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function addSkill(Request $request)
    {
        $request->validate([
            'skill' => 'required|string|max:100',
        ]);

        $user = Auth::user();
        $skillName = trim($request->skill);

        // Find or create skill
        $skill = Skill::firstOrCreate(
            ['name' => $skillName],
            ['description' => '']
        );

        // Check if user already has this skill
        $existingUserSkill = $user->skills()->where('skill_id', $skill->id)->first();

        if ($existingUserSkill) {
            throw ValidationException::withMessages([
                'skill' => 'You already have this skill in your profile.',
            ]);
        }

        // Add skill to user
        $user->skills()->attach($skill->id, [
            'proficiency_level' => 'beginner',
            'years_of_experience' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'skill' => $skill,
                'message' => 'Skill added to your profile successfully!',
            ],
        ]);
    }

    public function requestEndorsement(Request $request)
    {
        $request->validate([
            'skill_id' => 'required|exists:skills,id',
            'endorser_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $skill = Skill::findOrFail($request->skill_id);
        $endorser = User::findOrFail($request->endorser_id);

        // Check if user has this skill
        $userSkill = $user->skills()->where('skill_id', $skill->id)->first();
        if (! $userSkill) {
            throw ValidationException::withMessages([
                'skill_id' => 'You do not have this skill in your profile.',
            ]);
        }

        // Check if users are connected
        $connection = DB::table('connections')
            ->where(function ($query) use ($user, $endorser) {
                $query->where('user_id', $user->id)
                    ->where('connected_user_id', $endorser->id);
            })
            ->orWhere(function ($query) use ($user, $endorser) {
                $query->where('user_id', $endorser->id)
                    ->where('connected_user_id', $user->id);
            })
            ->where('status', 'accepted')
            ->first();

        if (! $connection) {
            throw ValidationException::withMessages([
                'endorser_id' => 'You must be connected to request an endorsement.',
            ]);
        }

        // Check if endorsement request already exists
        $existingRequest = DB::table('endorsement_requests')
            ->where('user_id', $user->id)
            ->where('skill_id', $skill->id)
            ->where('endorser_id', $endorser->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            throw ValidationException::withMessages([
                'endorser_id' => 'You have already requested an endorsement from this person for this skill.',
            ]);
        }

        // Create endorsement request
        DB::table('endorsement_requests')->insert([
            'user_id' => $user->id,
            'skill_id' => $skill->id,
            'endorser_id' => $endorser->id,
            'message' => $request->message,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // TODO: Send notification to endorser

        return response()->json([
            'success' => true,
            'message' => 'Endorsement request sent successfully!',
        ]);
    }

    public function getUserSkills(?User $user = null)
    {
        $targetUser = $user ?? Auth::user();

        $skills = $targetUser->skills()
            ->withPivot(['proficiency_level', 'years_of_experience'])
            ->withCount('endorsements')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ['skills' => $skills],
        ]);
    }

    public function updateSkill(Request $request, Skill $skill)
    {
        $request->validate([
            'proficiency_level' => 'required|in:beginner,intermediate,advanced,expert',
            'years_of_experience' => 'required|integer|min:0|max:50',
        ]);

        $user = Auth::user();

        // Check if user has this skill
        $userSkill = $user->skills()->where('skill_id', $skill->id)->first();
        if (! $userSkill) {
            throw ValidationException::withMessages([
                'skill' => 'You do not have this skill in your profile.',
            ]);
        }

        // Update skill details
        $user->skills()->updateExistingPivot($skill->id, [
            'proficiency_level' => $request->proficiency_level,
            'years_of_experience' => $request->years_of_experience,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Skill updated successfully!',
        ]);
    }

    public function removeSkill(Skill $skill)
    {
        $user = Auth::user();

        // Check if user has this skill
        $userSkill = $user->skills()->where('skill_id', $skill->id)->first();
        if (! $userSkill) {
            throw ValidationException::withMessages([
                'skill' => 'You do not have this skill in your profile.',
            ]);
        }

        // Remove skill from user
        $user->skills()->detach($skill->id);

        return response()->json([
            'success' => true,
            'message' => 'Skill removed from your profile.',
        ]);
    }

    public function endorseSkill(Request $request)
    {
        $request->validate([
            'endorsement_request_id' => 'required|exists:endorsement_requests,id',
            'comment' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $requestId = $request->endorsement_request_id;

        // Get the endorsement request
        $endorsementRequest = DB::table('endorsement_requests')
            ->where('id', $requestId)
            ->where('endorser_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (! $endorsementRequest) {
            throw ValidationException::withMessages([
                'endorsement_request_id' => 'Invalid or already processed endorsement request.',
            ]);
        }

        // Create the endorsement
        DB::table('skill_endorsements')->insert([
            'user_id' => $endorsementRequest->user_id,
            'skill_id' => $endorsementRequest->skill_id,
            'endorser_id' => $user->id,
            'comment' => $request->comment,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update the request status
        DB::table('endorsement_requests')
            ->where('id', $requestId)
            ->update([
                'status' => 'approved',
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Skill endorsed successfully!',
        ]);
    }
}
