<?php

namespace App\Services;

use App\Models\User;
use App\Models\CoffeeChatRequest;
use App\Models\VideoCall;
use Illuminate\Support\Collection;

class CoffeeChatService
{
    public function __construct(
        private VideoCallService $videoCallService
    ) {}
    
    public function suggestMatches(User $user, array $criteria = []): Collection
    {
        $query = User::where('id', '!=', $user->id)
            ->whereHas('profile', function($q) use ($user, $criteria) {
                // Match by industry
                if (!empty($criteria['industry'])) {
                    $q->where('industry', $criteria['industry']);
                } elseif ($user->profile && $user->profile->industry) {
                    $q->where('industry', $user->profile->industry);
                }
                
                // Geographic proximity (50km radius)
                if ($user->profile && $user->profile->location) {
                    $q->whereRaw('ST_DWithin(location, ?, 50000)', [$user->profile->location]);
                }
            });
            
        // Prioritize users with fewer completed coffee chats
        return $query->withCount(['coffeeChatRequestsAsRequester as coffee_chats_completed' => function ($q) {
                $q->where('status', 'completed');
            }])
            ->orderBy('coffee_chats_completed', 'asc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }
    
    public function createRequest(array $data): CoffeeChatRequest
    {
        return CoffeeChatRequest::create([
            'requester_id' => $data['requester_id'],
            'recipient_id' => $data['recipient_id'] ?? null,
            'type' => $data['type'] ?? 'direct_request',
            'proposed_times' => $data['proposed_times'],
            'message' => $data['message'] ?? null,
            'matching_criteria' => $data['matching_criteria'] ?? [],
        ]);
    }
    
    public function acceptRequest(CoffeeChatRequest $request, string $selectedTime): VideoCall
    {
        $request->accept($selectedTime);
        
        $call = $this->videoCallService->createCall([
            'host_user_id' => $request->requester_id,
            'title' => 'Coffee Chat: ' . $request->requester->name . ' & ' . $request->recipient->name,
            'type' => 'coffee_chat',
            'scheduled_at' => $selectedTime,
            'max_participants' => 2,
        ]);
        
        $request->update(['call_id' => $call->id]);
        
        return $call;
    }
    
    public function declineRequest(CoffeeChatRequest $request): void
    {
        $request->decline();
    }
    
    public function getRequestsForUser(User $user, string $type = 'all'): Collection
    {
        $query = CoffeeChatRequest::forUser($user->id)->with(['requester', 'recipient', 'call']);
        
        return match ($type) {
            'sent' => $query->where('requester_id', $user->id)->get(),
            'received' => $query->where('recipient_id', $user->id)->get(),
            'pending' => $query->pending()->get(),
            default => $query->get(),
        };
    }
    
    public function generateAIMatches(User $user): Collection
    {
        // AI-powered matching based on profile data, interests, and activity
        $criteria = [
            'industry' => $user->profile->industry ?? null,
            'interests' => $user->interests ?? [],
            'location' => $user->profile->location ?? null,
        ];
        
        return $this->suggestMatches($user, $criteria);
    }
    
    public function scheduleFollowUp(CoffeeChatRequest $request): void
    {
        // Schedule a follow-up reminder or survey
        // This could trigger a job or notification
        if ($request->isAccepted() && $request->call) {
            // Logic to schedule follow-up after the call
        }
    }
    
    public function getMatchingScore(User $user1, User $user2): float
    {
        $score = 0.0;
        
        // Industry match (40% weight)
        if ($user1->profile?->industry && $user2->profile?->industry && 
            $user1->profile->industry === $user2->profile->industry) {
            $score += 0.4;
        }
        
        // Location proximity (30% weight)
        if ($user1->profile?->location && $user2->profile?->location) {
            // Simplified distance calculation - in real implementation use proper geospatial functions
            $score += 0.3;
        }
        
        // Mutual connections (20% weight)
        // This would require a connections/network table
        
        // Activity level (10% weight)
        $user1Activity = $user1->coffeeChatRequestsAsRequester()->count() + 
                        $user1->coffeeChatRequestsAsRecipient()->count();
        $user2Activity = $user2->coffeeChatRequestsAsRequester()->count() + 
                        $user2->coffeeChatRequestsAsRecipient()->count();
        
        if ($user1Activity > 0 && $user2Activity > 0) {
            $score += 0.1;
        }
        
        return min($score, 1.0);
    }
}