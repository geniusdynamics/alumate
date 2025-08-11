<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\ReunionMemory;
use App\Models\ReunionPhoto;
use App\Services\ReunionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReunionController extends Controller
{
    public function __construct(
        private ReunionService $reunionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Event::reunions()
            ->published()
            ->where(function ($q) use ($user) {
                $q->where('visibility', 'public')
                    ->orWhere('visibility', 'alumni_only')
                    ->orWhere(function ($subQ) use ($user) {
                        $subQ->where('visibility', 'institution_only')
                            ->where('institution_id', $user->institution_id);
                    });
            })
            ->with(['organizer', 'institution']);

        // Filter by graduation year if provided
        if ($request->has('graduation_year')) {
            $query->byGraduationYear($request->graduation_year);
        }

        // Filter by reunion milestone if provided
        if ($request->has('milestone')) {
            $query->byReunionMilestone($request->milestone);
        }

        // Filter by time period
        if ($request->has('period')) {
            switch ($request->period) {
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'past':
                    $query->past();
                    break;
            }
        }

        $reunions = $query->orderBy('start_date', 'desc')->paginate(12);

        return response()->json($reunions);
    }

    public function show(Event $event, Request $request): JsonResponse
    {
        if (! $event->isReunion()) {
            return response()->json(['message' => 'Event is not a reunion'], 404);
        }

        if (! $event->canUserView($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $event->load([
            'organizer',
            'institution',
            'registrations.user',
            'reunionPhotos' => function ($query) {
                $query->approved()->featured()->limit(6);
            },
            'reunionMemories' => function ($query) {
                $query->approved()->featured()->limit(3);
            },
        ]);

        $statistics = $this->reunionService->generateReunionStatistics($event);

        return response()->json([
            'event' => $event,
            'statistics' => $statistics,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'graduation_year' => 'required|integer|min:1900|max:'.(now()->year + 10),
            'class_identifier' => 'nullable|string|max:255',
            'reunion_theme' => 'nullable|string|max:500',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string',
            'max_capacity' => 'nullable|integer|min:1',
            'ticket_price' => 'nullable|numeric|min:0',
            'enable_photo_sharing' => 'boolean',
            'enable_memory_wall' => 'boolean',
            'reunion_committees' => 'nullable|array',
            'reunion_committees.*.user_id' => 'required_with:reunion_committees|exists:users,id',
            'reunion_committees.*.role' => 'required_with:reunion_committees|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $event = $this->reunionService->createReunionEvent(
            $validator->validated(),
            $request->user()
        );

        return response()->json($event, 201);
    }

    public function update(Event $event, Request $request): JsonResponse
    {
        if (! $event->isReunion()) {
            return response()->json(['message' => 'Event is not a reunion'], 404);
        }

        if (! $event->canUserEdit($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'graduation_year' => 'sometimes|integer|min:1900|max:'.(now()->year + 10),
            'class_identifier' => 'nullable|string|max:255',
            'reunion_theme' => 'nullable|string|max:500',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string',
            'max_capacity' => 'nullable|integer|min:1',
            'ticket_price' => 'nullable|numeric|min:0',
            'enable_photo_sharing' => 'boolean',
            'enable_memory_wall' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $event->update($validator->validated());

        return response()->json($event);
    }

    public function photos(Event $event, Request $request): JsonResponse
    {
        if (! $event->isReunion() || ! $event->hasPhotoSharing()) {
            return response()->json(['message' => 'Photo sharing not available'], 404);
        }

        if (! $event->canUserView($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $filters = $request->only(['featured', 'uploaded_by']);
        $photos = $this->reunionService->getReunionPhotos($event, $request->user(), $filters);

        return response()->json($photos);
    }

    public function uploadPhoto(Event $event, Request $request): JsonResponse
    {
        if (! $event->isReunion() || ! $event->hasPhotoSharing()) {
            return response()->json(['message' => 'Photo sharing not available'], 404);
        }

        if (! $event->canUserView($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'tagged_users' => 'nullable|array',
            'tagged_users.*' => 'exists:users,id',
            'visibility' => 'in:public,alumni_only,class_only',
            'taken_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $photo = $this->reunionService->uploadReunionPhoto(
                $event,
                $request->file('photo'),
                $request->user(),
                $validator->validated()
            );

            return response()->json($photo, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function memories(Event $event, Request $request): JsonResponse
    {
        if (! $event->isReunion() || ! $event->hasMemoryWall()) {
            return response()->json(['message' => 'Memory wall not available'], 404);
        }

        if (! $event->canUserView($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $filters = $request->only(['featured', 'type', 'submitted_by']);
        $memories = $this->reunionService->getReunionMemories($event, $request->user(), $filters);

        return response()->json($memories);
    }

    public function createMemory(Event $event, Request $request): JsonResponse
    {
        if (! $event->isReunion() || ! $event->hasMemoryWall()) {
            return response()->json(['message' => 'Memory wall not available'], 404);
        }

        if (! $event->canUserView($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'in:story,achievement,memory,tribute,update',
            'media_urls' => 'nullable|array|max:5',
            'media_urls.*' => 'url',
            'tagged_users' => 'nullable|array',
            'tagged_users.*' => 'exists:users,id',
            'visibility' => 'in:public,alumni_only,class_only',
            'memory_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $memory = $this->reunionService->createReunionMemory(
                $event,
                $request->user(),
                $validator->validated()
            );

            return response()->json($memory, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function likePhoto(ReunionPhoto $photo, Request $request): JsonResponse
    {
        if (! $photo->canBeViewedBy($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $liked = $this->reunionService->likePhoto($photo, $request->user());

        return response()->json([
            'liked' => $liked,
            'likes_count' => $photo->fresh()->likes_count,
        ]);
    }

    public function unlikePhoto(ReunionPhoto $photo, Request $request): JsonResponse
    {
        if (! $photo->canBeViewedBy($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $unliked = $this->reunionService->unlikePhoto($photo, $request->user());

        return response()->json([
            'unliked' => $unliked,
            'likes_count' => $photo->fresh()->likes_count,
        ]);
    }

    public function likeMemory(ReunionMemory $memory, Request $request): JsonResponse
    {
        if (! $memory->canBeViewedBy($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $liked = $this->reunionService->likeMemory($memory, $request->user());

        return response()->json([
            'liked' => $liked,
            'likes_count' => $memory->fresh()->likes_count,
        ]);
    }

    public function unlikeMemory(ReunionMemory $memory, Request $request): JsonResponse
    {
        if (! $memory->canBeViewedBy($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $unliked = $this->reunionService->unlikeMemory($memory, $request->user());

        return response()->json([
            'unliked' => $unliked,
            'likes_count' => $memory->fresh()->likes_count,
        ]);
    }

    public function commentOnPhoto(ReunionPhoto $photo, Request $request): JsonResponse
    {
        if (! $photo->canBeViewedBy($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $comment = $this->reunionService->commentOnPhoto(
            $photo,
            $request->user(),
            $validator->validated()['comment']
        );

        return response()->json($comment->load('user'), 201);
    }

    public function commentOnMemory(ReunionMemory $memory, Request $request): JsonResponse
    {
        if (! $memory->canBeViewedBy($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $comment = $this->reunionService->commentOnMemory(
            $memory,
            $request->user(),
            $validator->validated()['comment']
        );

        return response()->json($comment->load('user'), 201);
    }

    public function milestones(Request $request): JsonResponse
    {
        $milestones = $this->reunionService->getUpcomingReunionMilestones($request->user());

        return response()->json($milestones);
    }

    public function byGraduationYear(int $year, Request $request): JsonResponse
    {
        $reunions = $this->reunionService->getReunionsByGraduationYear($year, $request->user());

        return response()->json($reunions);
    }

    public function statistics(Event $event, Request $request): JsonResponse
    {
        if (! $event->isReunion()) {
            return response()->json(['message' => 'Event is not a reunion'], 404);
        }

        if (! $event->canUserView($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $statistics = $this->reunionService->generateReunionStatistics($event);

        return response()->json($statistics);
    }

    public function committeeMembers(Event $event, Request $request): JsonResponse
    {
        if (! $event->isReunion()) {
            return response()->json(['message' => 'Event is not a reunion'], 404);
        }

        if (! $event->canUserView($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $committees = $event->getCommitteeMembers();
        $userIds = collect($committees)->pluck('user_id');

        $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');

        $committeesWithUsers = collect($committees)->map(function ($committee) use ($users) {
            $committee['user'] = $users->get($committee['user_id']);

            return $committee;
        });

        return response()->json($committeesWithUsers);
    }

    public function addCommitteeMember(Event $event, Request $request): JsonResponse
    {
        if (! $event->isReunion()) {
            return response()->json(['message' => 'Event is not a reunion'], 404);
        }

        if (! $event->canUserManageReunion($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = \App\Models\User::find($validator->validated()['user_id']);
        $event->addCommitteeMember($user, $validator->validated()['role']);

        return response()->json(['message' => 'Committee member added successfully']);
    }

    public function removeCommitteeMember(Event $event, Request $request): JsonResponse
    {
        if (! $event->isReunion()) {
            return response()->json(['message' => 'Event is not a reunion'], 404);
        }

        if (! $event->canUserManageReunion($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = \App\Models\User::find($validator->validated()['user_id']);
        $event->removeCommitteeMember($user);

        return response()->json(['message' => 'Committee member removed successfully']);
    }
}
