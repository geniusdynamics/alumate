<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VideoCall;
use App\Services\VideoCallService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class VideoCallController extends Controller
{
    public function __construct(
        private VideoCallService $videoCallService
    ) {}
    
    /**
     * Display a listing of video calls for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = VideoCall::forUser($user->id)
            ->with(['host', 'participants.user'])
            ->orderBy('scheduled_at', 'desc');
            
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        $calls = $query->paginate($request->get('per_page', 15));
        
        return response()->json([
            'success' => true,
            'data' => $calls->items(),
            'pagination' => [
                'current_page' => $calls->currentPage(),
                'last_page' => $calls->lastPage(),
                'per_page' => $calls->perPage(),
                'total' => $calls->total(),
            ],
        ]);
    }

    /**
     * Store a newly created video call.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:coffee_chat,group_meeting,alumni_gathering,mentorship',
            'scheduled_at' => 'required|date|after:now',
            'max_participants' => 'nullable|integer|min:2|max:50',
            'provider' => 'nullable|in:jitsi,jitsi_videobridge,livekit',
            'settings' => 'nullable|array',
        ]);
        
        $validated['host_user_id'] = Auth::id();
        
        $call = $this->videoCallService->createCall($validated);
        
        return response()->json([
            'success' => true,
            'data' => $call->load('host'),
            'message' => 'Video call scheduled successfully.',
        ], 201);
    }

    /**
     * Display the specified video call.
     */
    public function show(VideoCall $call): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user has access to this call
        if (!$call->canUserAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this call.',
            ], 403);
        }
        
        $call->load(['host', 'participants.user', 'recordings', 'screenSharingSessions']);
        
        $jitsiUrl = null;
        if ($call->provider === 'jitsi' || $call->provider === 'jitsi_videobridge') {
            $jitsiUrl = $this->videoCallService->generateJitsiUrl($call, $user);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'call' => $call,
                'jitsi_url' => $jitsiUrl,
                'can_moderate' => $call->isHost($user),
                'analytics' => $this->videoCallService->getCallAnalytics($call),
            ],
        ]);
    }

    /**
     * Update the specified video call.
     */
    public function update(Request $request, VideoCall $call): JsonResponse
    {
        $user = Auth::user();
        
        if (!$call->isHost($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Only the host can update this call.',
            ], 403);
        }
        
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'sometimes|date|after:now',
            'max_participants' => 'sometimes|integer|min:2|max:50',
            'settings' => 'nullable|array',
        ]);
        
        $call->update($validated);
        
        return response()->json([
            'success' => true,
            'data' => $call,
            'message' => 'Video call updated successfully.',
        ]);
    }

    /**
     * Remove the specified video call.
     */
    public function destroy(VideoCall $call): JsonResponse
    {
        $user = Auth::user();
        
        if (!$call->isHost($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Only the host can delete this call.',
            ], 403);
        }
        
        if ($call->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete an active call.',
            ], 400);
        }
        
        $call->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Video call deleted successfully.',
        ]);
    }
    
    /**
     * Join a video call.
     */
    public function join(VideoCall $call): JsonResponse
    {
        $user = Auth::user();
        
        if (!$call->canUserAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this call.',
            ], 403);
        }
        
        if ($call->status === 'ended' || $call->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'This call has ended.',
            ], 400);
        }
        
        $this->videoCallService->joinCall($call, $user);
        
        $jitsiUrl = null;
        if ($call->provider === 'jitsi' || $call->provider === 'jitsi_videobridge') {
            $jitsiUrl = $this->videoCallService->generateJitsiUrl($call, $user);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'jitsi_url' => $jitsiUrl,
                'call' => $call->fresh(),
            ],
            'message' => 'Joined call successfully.',
        ]);
    }
    
    /**
     * Leave a video call.
     */
    public function leave(VideoCall $call): JsonResponse
    {
        $user = Auth::user();
        
        if (!$call->hasParticipant($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in this call.',
            ], 400);
        }
        
        $this->videoCallService->leaveCall($call, $user);
        
        return response()->json([
            'success' => true,
            'message' => 'Left call successfully.',
        ]);
    }
    
    /**
     * End a video call.
     */
    public function end(VideoCall $call): JsonResponse
    {
        $user = Auth::user();
        
        if (!$call->isHost($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Only the host can end this call.',
            ], 403);
        }
        
        $this->videoCallService->endCall($call);
        
        return response()->json([
            'success' => true,
            'message' => 'Call ended successfully.',
        ]);
    }
    
    /**
     * Get call analytics.
     */
    public function analytics(VideoCall $call): JsonResponse
    {
        $user = Auth::user();
        
        if (!$call->canUserAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this call.',
            ], 403);
        }
        
        $analytics = $this->videoCallService->getCallAnalytics($call);
        
        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }
}
