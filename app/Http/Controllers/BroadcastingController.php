<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\RealtimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BroadcastingController extends Controller
{
    public function __construct(
        private RealtimeService $realtimeService
    ) {}

    /**
     * Authenticate private channel access.
     */
    public function auth(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $channel = $request->channel_name;
        $socketId = $request->socket_id;

        // Authorize based on channel type
        if (str_starts_with($channel, 'private-user.')) {
            $userId = (int) str_replace('private-user.', '', $channel);
            if ($user->id !== $userId) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        }

        if (str_starts_with($channel, 'private-tenant.')) {
            $tenantId = (int) str_replace('private-tenant.', '', $channel);
            $hasAccess = $user->tenants()->where('tenants.id', $tenantId)->exists();
            if (! $hasAccess) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        }

        if (str_starts_with($channel, 'private-conversation.')) {
            $conversationId = (int) str_replace('private-conversation.', '', $channel);
            $hasAccess = $user->conversations()->where('conversations.id', $conversationId)->exists();
            if (! $hasAccess) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        }

        if (str_starts_with($channel, 'presence-')) {
            $auth = $this->realtimeService->presenceAuth($channel, $socketId, $user);
        } else {
            $auth = $this->realtimeService->auth($channel, $socketId);
        }

        if (! $auth) {
            return response()->json(['error' => 'Authentication failed'], 500);
        }

        return response()->json(json_decode($auth, true));
    }

    /**
     * Get Pusher configuration for frontend.
     */
    public function config(): JsonResponse
    {
        return response()->json($this->realtimeService->getPublicConfig());
    }
}
