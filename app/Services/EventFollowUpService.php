<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventConnectionRecommendation;
use App\Models\EventFeedback;
use App\Models\EventFollowUpActivity;
use App\Models\EventHighlight;
use App\Models\EventNetworkingConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EventFollowUpService
{
    /**
     * Submit feedback for an event
     */
    public function submitFeedback(Event $event, User $user, array $feedbackData): EventFeedback
    {
        // Check if user attended the event
        if (! $event->isUserCheckedIn($user)) {
            throw new \Exception('Only attendees can provide feedback.');
        }

        // Check if feedback already exists
        $existingFeedback = $event->feedback()->where('user_id', $user->id)->first();
        if ($existingFeedback) {
            throw new \Exception('Feedback has already been submitted for this event.');
        }

        $feedback = DB::transaction(function () use ($event, $user, $feedbackData) {
            $feedback = EventFeedback::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'overall_rating' => $feedbackData['overall_rating'],
                'content_rating' => $feedbackData['content_rating'] ?? null,
                'organization_rating' => $feedbackData['organization_rating'] ?? null,
                'networking_rating' => $feedbackData['networking_rating'] ?? null,
                'venue_rating' => $feedbackData['venue_rating'] ?? null,
                'feedback_text' => $feedbackData['feedback_text'] ?? null,
                'feedback_categories' => $feedbackData['feedback_categories'] ?? null,
                'would_recommend' => $feedbackData['would_recommend'] ?? false,
                'would_attend_again' => $feedbackData['would_attend_again'] ?? false,
                'improvement_suggestions' => $feedbackData['improvement_suggestions'] ?? null,
                'is_anonymous' => $feedbackData['is_anonymous'] ?? false,
            ]);

            // Record follow-up activity
            $this->recordActivity($event, $user, 'feedback_given', [
                'rating' => $feedbackData['overall_rating'],
                'has_text_feedback' => ! empty($feedbackData['feedback_text']),
            ]);

            return $feedback;
        });

        return $feedback;
    }

    /**
     * Get event feedback analytics
     */
    public function getEventFeedbackAnalytics(Event $event): array
    {
        $feedback = $event->feedback;

        if ($feedback->isEmpty()) {
            return [
                'total_responses' => 0,
                'average_rating' => 0,
                'rating_breakdown' => [],
                'recommendation_rate' => 0,
                'return_rate' => 0,
            ];
        }

        $totalResponses = $feedback->count();
        $averageRating = $feedback->avg('overall_rating');

        $ratingBreakdown = [
            'overall' => $feedback->avg('overall_rating'),
            'content' => $feedback->whereNotNull('content_rating')->avg('content_rating'),
            'organization' => $feedback->whereNotNull('organization_rating')->avg('organization_rating'),
            'networking' => $feedback->whereNotNull('networking_rating')->avg('networking_rating'),
            'venue' => $feedback->whereNotNull('venue_rating')->avg('venue_rating'),
        ];

        $recommendationRate = $feedback->where('would_recommend', true)->count() / $totalResponses * 100;
        $returnRate = $feedback->where('would_attend_again', true)->count() / $totalResponses * 100;

        return [
            'total_responses' => $totalResponses,
            'response_rate' => $event->checkIns()->count() > 0 ?
                ($totalResponses / $event->checkIns()->count()) * 100 : 0,
            'average_rating' => round($averageRating, 2),
            'rating_breakdown' => array_map(fn ($rating) => round($rating, 2), $ratingBreakdown),
            'rating_distribution' => $this->getRatingDistribution($feedback),
            'recommendation_rate' => round($recommendationRate, 2),
            'return_rate' => round($returnRate, 2),
            'sentiment_analysis' => $this->analyzeFeedbackSentiment($feedback),
        ];
    }

    /**
     * Create an event highlight
     */
    public function createHighlight(Event $event, User $user, array $highlightData): EventHighlight
    {
        // Check if user attended the event
        if (! $event->isUserCheckedIn($user)) {
            throw new \Exception('Only attendees can create highlights.');
        }

        $highlight = DB::transaction(function () use ($event, $user, $highlightData) {
            $highlight = EventHighlight::create([
                'event_id' => $event->id,
                'created_by' => $user->id,
                'type' => $highlightData['type'],
                'title' => $highlightData['title'],
                'description' => $highlightData['description'] ?? null,
                'media_urls' => $highlightData['media_urls'] ?? null,
                'metadata' => $highlightData['metadata'] ?? null,
                'is_approved' => $highlightData['auto_approve'] ?? true,
            ]);

            // Record follow-up activity
            $this->recordActivity($event, $user, 'highlight_created', [
                'highlight_type' => $highlightData['type'],
                'has_media' => ! empty($highlightData['media_urls']),
            ]);

            return $highlight;
        });

        return $highlight;
    }

    /**
     * Get event highlights with filtering
     */
    public function getEventHighlights(Event $event, array $filters = []): Collection
    {
        $query = $event->highlights()
            ->approved()
            ->with(['creator', 'interactions.user']);

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['featured_only'])) {
            $query->featured();
        }

        $sortBy = $filters['sort_by'] ?? 'recent';
        switch ($sortBy) {
            case 'popular':
                $query->popular();
                break;
            case 'featured':
                $query->orderByDesc('is_featured')->orderByDesc('featured_at');
                break;
            default:
                $query->recent();
        }

        return $query->get();
    }

    /**
     * Create a networking connection between two users at an event
     */
    public function createNetworkingConnection(
        Event $event,
        User $user,
        User $connectedUser,
        array $connectionData
    ): EventNetworkingConnection {
        // Check if both users attended the event
        if (! $event->isUserCheckedIn($user) || ! $event->isUserCheckedIn($connectedUser)) {
            throw new \Exception('Both users must have attended the event to create a connection.');
        }

        // Check if connection already exists
        $existingConnection = $event->networkingConnections()
            ->where(function ($q) use ($user, $connectedUser) {
                $q->where('user_id', $user->id)->where('connected_user_id', $connectedUser->id)
                    ->orWhere('user_id', $connectedUser->id)->where('connected_user_id', $user->id);
            })
            ->first();

        if ($existingConnection) {
            throw new \Exception('Connection already exists between these users.');
        }

        $connection = DB::transaction(function () use ($event, $user, $connectedUser, $connectionData) {
            $connection = EventNetworkingConnection::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'connected_user_id' => $connectedUser->id,
                'connection_type' => $connectionData['connection_type'] ?? 'met_at_event',
                'connection_note' => $connectionData['connection_note'] ?? null,
                'shared_interests' => $connectionData['shared_interests'] ?? null,
                'follow_up_requested' => $connectionData['follow_up_requested'] ?? false,
                'connected_at' => now(),
            ]);

            // Record follow-up activity for both users
            $this->recordActivity($event, $user, 'connections_made', ['count' => 1]);
            $this->recordActivity($event, $connectedUser, 'connections_made', ['count' => 1]);

            return $connection;
        });

        return $connection;
    }

    /**
     * Generate connection recommendations for event attendees
     */
    public function generateConnectionRecommendations(Event $event, User $user): Collection
    {
        // Get all other attendees
        $attendees = $event->checkIns()
            ->with('user')
            ->where('user_id', '!=', $user->id)
            ->get()
            ->pluck('user');

        $recommendations = collect();

        foreach ($attendees as $attendee) {
            // Skip if already connected
            $existingConnection = $event->networkingConnections()
                ->where(function ($q) use ($user, $attendee) {
                    $q->where('user_id', $user->id)->where('connected_user_id', $attendee->id)
                        ->orWhere('user_id', $attendee->id)->where('connected_user_id', $user->id);
                })
                ->exists();

            if ($existingConnection) {
                continue;
            }

            // Skip if recommendation already exists
            $existingRecommendation = $event->connectionRecommendations()
                ->where('user_id', $user->id)
                ->where('recommended_user_id', $attendee->id)
                ->exists();

            if ($existingRecommendation) {
                continue;
            }

            // Calculate match score and reasons
            $matchData = $this->calculateConnectionMatch($user, $attendee);

            if ($matchData['score'] >= 50) { // Only recommend if score is above threshold
                $recommendation = EventConnectionRecommendation::create([
                    'event_id' => $event->id,
                    'user_id' => $user->id,
                    'recommended_user_id' => $attendee->id,
                    'match_score' => $matchData['score'],
                    'match_reasons' => $matchData['reasons'],
                    'shared_attributes' => $matchData['shared_attributes'],
                    'recommended_at' => now(),
                ]);

                $recommendations->push($recommendation);
            }
        }

        return $recommendations;
    }

    /**
     * Get connection recommendations for a user at an event
     */
    public function getConnectionRecommendations(Event $event, User $user): Collection
    {
        return $event->connectionRecommendations()
            ->where('user_id', $user->id)
            ->with('recommendedUser')
            ->orderByScore()
            ->get();
    }

    /**
     * Get networking connections for a user at an event
     */
    public function getNetworkingConnections(Event $event, User $user): Collection
    {
        return $event->networkingConnections()
            ->forUser($user)
            ->with(['user', 'connectedUser'])
            ->orderByDesc('connected_at')
            ->get();
    }

    /**
     * Get follow-up activities for an event
     */
    public function getFollowUpActivities(Event $event, ?User $user = null): Collection
    {
        $query = $event->followUpActivities()->with('user');

        if ($user) {
            $query->where('user_id', $user->id);
        }

        return $query->orderByDesc('completed_at')->get();
    }

    /**
     * Get event follow-up analytics
     */
    public function getFollowUpAnalytics(Event $event): array
    {
        $totalAttendees = $event->checkIns()->count();
        $activities = $event->followUpActivities;

        $activityCounts = $activities->groupBy('activity_type')->map->count();
        $uniqueParticipants = $activities->pluck('user_id')->unique()->count();

        return [
            'total_attendees' => $totalAttendees,
            'active_participants' => $uniqueParticipants,
            'participation_rate' => $totalAttendees > 0 ?
                round(($uniqueParticipants / $totalAttendees) * 100, 2) : 0,
            'activity_breakdown' => $activityCounts->toArray(),
            'total_connections' => $event->networkingConnections()->count(),
            'total_highlights' => $event->highlights()->approved()->count(),
            'feedback_responses' => $event->feedback()->count(),
            'engagement_score' => $this->calculateEngagementScore($event),
        ];
    }

    /**
     * Record a follow-up activity
     */
    private function recordActivity(Event $event, User $user, string $activityType, array $data = []): EventFollowUpActivity
    {
        return EventFollowUpActivity::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'activity_type' => $activityType,
            'activity_data' => $data,
            'completed_at' => now(),
        ]);
    }

    /**
     * Calculate connection match between two users
     */
    private function calculateConnectionMatch(User $user1, User $user2): array
    {
        $score = 0;
        $reasons = [];
        $sharedAttributes = [];

        // Same institution
        if ($user1->institution_id === $user2->institution_id) {
            $score += 20;
            $reasons[] = 'Same institution';
            $sharedAttributes['institution'] = $user1->institution->name ?? 'Same institution';
        }

        // Same graduation year
        if ($user1->graduation_year === $user2->graduation_year) {
            $score += 15;
            $reasons[] = 'Same graduation year';
            $sharedAttributes['graduation_year'] = $user1->graduation_year;
        }

        // Similar location
        if ($user1->location && $user2->location &&
            stripos($user1->location, $user2->location) !== false ||
            stripos($user2->location, $user1->location) !== false) {
            $score += 10;
            $reasons[] = 'Similar location';
            $sharedAttributes['location'] = $user1->location;
        }

        // Same industry
        if ($user1->industry && $user2->industry && $user1->industry === $user2->industry) {
            $score += 15;
            $reasons[] = 'Same industry';
            $sharedAttributes['industry'] = $user1->industry;
        }

        // Mutual connections (if we had a connections system)
        // This would require implementing a broader alumni network system

        // Random factor for diversity
        $score += rand(0, 20);

        return [
            'score' => min(100, $score), // Cap at 100
            'reasons' => $reasons,
            'shared_attributes' => $sharedAttributes,
        ];
    }

    /**
     * Get rating distribution for feedback
     */
    private function getRatingDistribution(Collection $feedback): array
    {
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $count = $feedback->where('overall_rating', $i)->count();
            $distribution[$i] = [
                'count' => $count,
                'percentage' => $feedback->count() > 0 ? round(($count / $feedback->count()) * 100, 1) : 0,
            ];
        }

        return $distribution;
    }

    /**
     * Analyze feedback sentiment
     */
    private function analyzeFeedbackSentiment(Collection $feedback): array
    {
        $positive = $feedback->where('overall_rating', '>=', 4)->count();
        $neutral = $feedback->where('overall_rating', 3)->count();
        $negative = $feedback->where('overall_rating', '<=', 2)->count();
        $total = $feedback->count();

        return [
            'positive' => $total > 0 ? round(($positive / $total) * 100, 1) : 0,
            'neutral' => $total > 0 ? round(($neutral / $total) * 100, 1) : 0,
            'negative' => $total > 0 ? round(($negative / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Calculate overall engagement score for an event
     */
    private function calculateEngagementScore(Event $event): float
    {
        $totalAttendees = $event->checkIns()->count();

        if ($totalAttendees === 0) {
            return 0;
        }

        $feedbackCount = $event->feedback()->count();
        $highlightsCount = $event->highlights()->approved()->count();
        $connectionsCount = $event->networkingConnections()->count();
        $activitiesCount = $event->followUpActivities()->count();

        // Weighted scoring
        $feedbackScore = ($feedbackCount / $totalAttendees) * 30;
        $highlightsScore = ($highlightsCount / $totalAttendees) * 25;
        $connectionsScore = ($connectionsCount / $totalAttendees) * 25;
        $activitiesScore = ($activitiesCount / $totalAttendees) * 20;

        $totalScore = $feedbackScore + $highlightsScore + $connectionsScore + $activitiesScore;

        return min(100, round($totalScore, 2));
    }
}
