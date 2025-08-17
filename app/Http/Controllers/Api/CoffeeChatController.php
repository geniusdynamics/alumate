<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoffeeChatRequest;
use App\Services\CoffeeChatService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CoffeeChatController extends Controller
{
    public function __construct(
        private CoffeeChatService $coffeeChatService
    ) {}
    
    /**
     * Get coffee chat suggestions for the authenticated user.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $criteria = $request->validate([
            'industry' => 'nullable|string',
            'location' => 'nullable|string',
            'interests' => 'nullable|array',
        ]);
        
        $suggestions = $this->coffeeChatService->suggestMatches($user, $criteria);
        
        return response()->json([
            'success' => true,
            'data' => $suggestions->map(function ($suggestedUser) {
                return [
                    'id' => $suggestedUser->id,
                    'name' => $suggestedUser->name,
                    'avatar_url' => $suggestedUser->avatar_url,
                    'title' => $suggestedUser->profile->title ?? null,
                    'company' => $suggestedUser->profile->company ?? null,
                    'industry' => $suggestedUser->profile->industry ?? null,
                    'location' => $suggestedUser->profile->location ?? null,
                    'coffee_chats_completed' => $suggestedUser->coffee_chats_completed ?? 0,
                ];
            }),
        ]);
    }
    
    /**
     * Create a coffee chat request.
     */
    public function request(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'proposed_times' => 'required|array|min:1',
            'proposed_times.*' => 'date|after:now',
            'message' => 'nullable|string|max:500',
            'type' => 'nullable|in:direct_request,ai_matched,open_invitation',
        ]);
        
        $validated['requester_id'] = Auth::id();
        
        // Check if there's already a pending request between these users
        $existingRequest = CoffeeChatRequest::where(function ($query) use ($validated) {
            $query->where('requester_id', $validated['requester_id'])
                  ->where('recipient_id', $validated['recipient_id']);
        })->orWhere(function ($query) use ($validated) {
            $query->where('requester_id', $validated['recipient_id'])
                  ->where('recipient_id', $validated['requester_id']);
        })->pending()->first();
        
        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'There is already a pending coffee chat request between you and this user.',
            ], 400);
        }
        
        $coffeeChatRequest = $this->coffeeChatService->createRequest($validated);
        
        return response()->json([
            'success' => true,
            'data' => $coffeeChatRequest->load(['requester', 'recipient']),
            'message' => 'Coffee chat request sent successfully.',
        ], 201);
    }
    
    /**
     * Respond to a coffee chat request.
     */
    public function respond(Request $request, CoffeeChatRequest $coffeeChatRequest): JsonResponse
    {
        $user = Auth::user();
        
        if ($coffeeChatRequest->recipient_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only respond to requests sent to you.',
            ], 403);
        }
        
        if (!$coffeeChatRequest->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been responded to.',
            ], 400);
        }
        
        $validated = $request->validate([
            'action' => 'required|in:accept,decline',
            'selected_time' => 'required_if:action,accept|date|after:now',
        ]);
        
        if ($validated['action'] === 'accept') {
            // Verify the selected time is one of the proposed times
            $proposedTimes = collect($coffeeChatRequest->proposed_times);
            if (!$proposedTimes->contains($validated['selected_time'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected time must be one of the proposed times.',
                ], 400);
            }
            
            $call = $this->coffeeChatService->acceptRequest($coffeeChatRequest, $validated['selected_time']);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'request' => $coffeeChatRequest->fresh(['requester', 'recipient']),
                    'call' => $call->load('host'),
                ],
                'message' => 'Coffee chat request accepted and call scheduled.',
            ]);
        } else {
            $this->coffeeChatService->declineRequest($coffeeChatRequest);
            
            return response()->json([
                'success' => true,
                'data' => $coffeeChatRequest->fresh(['requester', 'recipient']),
                'message' => 'Coffee chat request declined.',
            ]);
        }
    }
    
    /**
     * Get coffee chat requests for the authenticated user.
     */
    public function myRequests(Request $request): JsonResponse
    {
        $user = Auth::user();
        $type = $request->get('type', 'sent'); // sent, received, all
        
        $requests = $this->coffeeChatService->getRequestsForUser($user, $type);
        
        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }
    
    /**
     * Get received coffee chat requests for the authenticated user.
     */
    public function receivedRequests(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $requests = $this->coffeeChatService->getRequestsForUser($user, 'received');
        
        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }
    
    /**
     * Get AI-generated coffee chat matches.
     */
    public function aiMatches(): JsonResponse
    {
        $user = Auth::user();
        
        $matches = $this->coffeeChatService->generateAIMatches($user);
        
        return response()->json([
            'success' => true,
            'data' => $matches->map(function ($match) {
                return [
                    'id' => $match->id,
                    'name' => $match->name,
                    'avatar_url' => $match->avatar_url,
                    'title' => $match->profile->title ?? null,
                    'company' => $match->profile->company ?? null,
                    'industry' => $match->profile->industry ?? null,
                    'match_score' => rand(70, 95), // Placeholder for actual AI matching score
                    'common_interests' => [], // Placeholder for common interests
                ];
            }),
        ]);
    }
}
