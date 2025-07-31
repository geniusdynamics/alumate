<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\UserSkill;
use App\Models\LearningResource;
use App\Services\SkillsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SkillsController extends Controller
{
    protected SkillsService $skillsService;

    public function __construct(SkillsService $skillsService)
    {
        $this->skillsService = $skillsService;
    }

    public function getUserSkills(int $userId): JsonResponse
    {
        $userSkills = UserSkill::where('user_id', $userId)
            ->with(['skill', 'endorsements.endorser'])
            ->orderBy('endorsed_count', 'desc')
            ->get();

        return response()->json([
            'skills' => $userSkills,
            'total_skills' => $userSkills->count(),
            'total_endorsements' => $userSkills->sum('endorsed_count'),
        ]);
    }

    public function addSkill(Request $request): JsonResponse
    {
        $request->validate([
            'skill_name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'proficiency_level' => 'required|in:Beginner,Intermediate,Advanced,Expert',
            'years_experience' => 'nullable|integer|min:0|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $userSkill = $this->skillsService->addSkillToUser(
                $request->user(),
                $request->all()
            );

            return response()->json([
                'message' => 'Skill added successfully',
                'user_skill' => $userSkill->load('skill'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add skill',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function endorseSkill(Request $request): JsonResponse
    {
        $request->validate([
            'user_skill_id' => 'required|exists:user_skills,id',
            'message' => 'nullable|string|max:500',
        ]);

        try {
            $endorsement = $this->skillsService->endorseUserSkill(
                $request->user_skill_id,
                $request->user(),
                $request->message
            );

            return response()->json([
                'message' => 'Skill endorsed successfully',
                'endorsement' => $endorsement->load(['userSkill.skill', 'endorser']),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to endorse skill',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getResources(Request $request): JsonResponse
    {
        $request->validate([
            'skill_id' => 'nullable|exists:skills,id',
            'type' => 'nullable|in:Course,Article,Video,Book,Workshop,Certification',
            'min_rating' => 'nullable|numeric|min:0|max:5',
        ]);

        $query = LearningResource::with(['creator', 'skills']);

        if ($request->skill_id) {
            $query->bySkill($request->skill_id);
        }

        if ($request->type) {
            $query->byType($request->type);
        }

        if ($request->min_rating) {
            $query->highRated($request->min_rating);
        }

        $resources = $query->orderBy('rating', 'desc')
            ->paginate(20);

        return response()->json($resources);
    }

    public function getSkillSuggestions(Request $request): JsonResponse
    {
        $suggestions = $this->skillsService->getSkillSuggestions($request->user());

        return response()->json([
            'suggestions' => $suggestions,
            'count' => $suggestions->count(),
        ]);
    }

    public function getSkillProgression(Request $request, int $skillId): JsonResponse
    {
        $progression = $this->skillsService->trackSkillProgression($request->user(), $skillId);

        if (empty($progression)) {
            return response()->json([
                'message' => 'Skill not found for user',
            ], 404);
        }

        return response()->json($progression);
    }

    public function getLearningRecommendations(Request $request, int $skillId): JsonResponse
    {
        $recommendations = $this->skillsService->recommendLearningResources($request->user(), $skillId);

        return response()->json([
            'recommendations' => $recommendations,
            'count' => $recommendations->count(),
        ]);
    }

    public function getSkillsGapAnalysis(Request $request): JsonResponse
    {
        $analysis = $this->skillsService->getSkillsGapAnalysis($request->user());

        return response()->json($analysis);
    }

    public function searchSkills(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'category' => 'nullable|string',
        ]);

        $query = Skill::search($request->query);

        if ($request->category) {
            $query->byCategory($request->category);
        }

        $skills = $query->verified()
            ->limit(20)
            ->get();

        return response()->json([
            'skills' => $skills,
            'count' => $skills->count(),
        ]);
    }

    public function createLearningResource(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'type' => 'required|in:Course,Article,Video,Book,Workshop,Certification',
            'url' => 'required|url',
            'skill_ids' => 'required|array',
            'skill_ids.*' => 'exists:skills,id',
        ]);

        $resource = LearningResource::create([
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'url' => $request->url,
            'skill_ids' => $request->skill_ids,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Learning resource created successfully',
            'resource' => $resource->load(['creator', 'skills']),
        ], 201);
    }

    public function rateResource(Request $request, LearningResource $resource): JsonResponse
    {
        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
        ]);

        $resource->addRating($request->rating);

        return response()->json([
            'message' => 'Resource rated successfully',
            'resource' => $resource->fresh(),
        ]);
    }
}