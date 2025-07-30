<?php

namespace App\Services;

use App\Models\User;
use App\Models\Circle;
use App\Models\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlumniRecommendationService
{
    private const CACHE_PREFIX = 'recommendations:user:';
    private const CACHE_TTL = 86400; // 24 hours
    
    // Scoring weights
    private const SHARED_CIRCLES_WEIGHT = 0.40;
    private const MUTUAL_CONNECTIONS_WEIGHT = 0.30;
    private const INTEREST_SIMILARITY_WEIGHT = 0.20;
    private const GEOGRAPHIC_PROXIMITY_WEIGHT = 0.10;

    /**
     * Get personalized recommendations for a user
     */
    public function getRecommendationsForUser(User $user, int $limit = 10): Collection
    {
        $cacheKey = self::CACHE_PREFIX . $user->id;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $limit) {
            $candidates = $this->getCandidateUsers($user);
            $scoredRecommendations = collect();
            
            foreach ($candidates as $candidate) {
                $score = $this->calculateConnectionScore($user, $candidate);
                
                if ($score > 0) {
                    $scoredRecommendations->push([
                        'user' => $candidate,
                        'score' => $score,
                        'reasons' => $this->getConnectionReasons($user, $candidate),
                        'shared_circles' => $this->getSharedCircles($user, $candidate),
                        'mutual_connections' => $this->getMutualConnections($user, $candidate),
                    ]);
                }
            }
            
            $recommendations = $scoredRecommendations
                ->sortByDesc('score')
                ->take($limit * 2); // Get more than needed for filtering
            
            return $this->filterRecommendations($recommendations, $user)->take($limit);
        });
    }

    /**
     * Calculate connection score between two users
     */
    public function calculateConnectionScore(User $user, User $candidate): float
    {
        $sharedCirclesScore = $this->calculateSharedCirclesScore($user, $candidate);
        $mutualConnectionsScore = $this->calculateMutualConnectionsScore($user, $candidate);
        $interestSimilarityScore = $this->getInterestSimilarity($user, $candidate);
        $geographicProximityScore = $this->calculateGeographicProximityScore($user, $candidate);
        
        $totalScore = 
            ($sharedCirclesScore * self::SHARED_CIRCLES_WEIGHT) +
            ($mutualConnectionsScore * self::MUTUAL_CONNECTIONS_WEIGHT) +
            ($interestSimilarityScore * self::INTEREST_SIMILARITY_WEIGHT) +
            ($geographicProximityScore * self::GEOGRAPHIC_PROXIMITY_WEIGHT);
        
        return round($totalScore, 2);
    }

    /**
     * Find shared circles between two users
     */
    public function getSharedCircles(User $user, User $candidate): Collection
    {
        return $user->circles()
            ->whereIn('circles.id', $candidate->circles()->pluck('circles.id'))
            ->get();
    }

    /**
     * Find mutual connections between two users
     */
    public function getMutualConnections(User $user, User $candidate): Collection
    {
        $userConnections = $user->connections()
            ->where('status', 'accepted')
            ->pluck('connected_user_id');
        
        $candidateConnections = $candidate->connections()
            ->where('status', 'accepted')
            ->pluck('connected_user_id');
        
        $mutualConnectionIds = $userConnections->intersect($candidateConnections);
        
        return User::whereIn('id', $mutualConnectionIds)->get();
    }

    /**
     * Calculate interest similarity between two users
     */
    public function getInterestSimilarity(User $user, User $candidate): float
    {
        $userInterests = $this->extractUserInterests($user);
        $candidateInterests = $this->extractUserInterests($candidate);
        
        if ($userInterests->isEmpty() || $candidateInterests->isEmpty()) {
            return 0.0;
        }
        
        $commonInterests = $userInterests->intersect($candidateInterests);
        $totalInterests = $userInterests->merge($candidateInterests)->unique();
        
        return $totalInterests->count() > 0 ? 
            $commonInterests->count() / $totalInterests->count() : 0.0;
    }

    /**
     * Filter recommendations based on privacy and preferences
     */
    public function filterRecommendations(Collection $recommendations, User $user): Collection
    {
        return $recommendations->filter(function ($recommendation) use ($user) {
            $candidate = $recommendation['user'];
            
            // Skip if already connected
            if ($this->areUsersConnected($user, $candidate)) {
                return false;
            }
            
            // Skip if connection request already sent
            if ($this->hasConnectionRequestPending($user, $candidate)) {
                return false;
            }
            
            // Skip if user has dismissed this recommendation recently
            if ($this->isRecommendationDismissed($user, $candidate)) {
                return false;
            }
            
            // Check privacy settings
            if (!$this->canUserBeRecommended($candidate)) {
                return false;
            }
            
            return true;
        });
    }

    /**
     * Get candidates for recommendations (excluding already connected users)
     */
    private function getCandidateUsers(User $user): Collection
    {
        $excludeIds = $user->connections()
            ->pluck('connected_user_id')
            ->push($user->id);
        
        return User::whereNotIn('id', $excludeIds)
            ->where('tenant_id', $user->tenant_id)
            ->with(['circles', 'educations', 'workExperiences'])
            ->limit(500) // Reasonable limit for processing
            ->get();
    }

    /**
     * Calculate shared circles score
     */
    private function calculateSharedCirclesScore(User $user, User $candidate): float
    {
        $sharedCircles = $this->getSharedCircles($user, $candidate);
        $userCircleCount = $user->circles()->count();
        
        if ($userCircleCount === 0) {
            return 0.0;
        }
        
        // Weight by circle importance (school_year circles are more important)
        $score = 0.0;
        foreach ($sharedCircles as $circle) {
            $weight = $circle->type === 'school_year' ? 1.0 : 0.7;
            $score += $weight;
        }
        
        return min($score / $userCircleCount, 1.0);
    }

    /**
     * Calculate mutual connections score
     */
    private function calculateMutualConnectionsScore(User $user, User $candidate): float
    {
        $mutualConnections = $this->getMutualConnections($user, $candidate);
        $userConnectionCount = $user->connections()->where('status', 'accepted')->count();
        
        if ($userConnectionCount === 0) {
            return 0.0;
        }
        
        return min($mutualConnections->count() / $userConnectionCount, 1.0);
    }

    /**
     * Calculate geographic proximity score
     */
    private function calculateGeographicProximityScore(User $user, User $candidate): float
    {
        $userLocation = $user->location;
        $candidateLocation = $candidate->location;
        
        if (!$userLocation || !$candidateLocation) {
            return 0.0;
        }
        
        // Simple string matching for now - could be enhanced with geocoding
        if (strtolower($userLocation) === strtolower($candidateLocation)) {
            return 1.0;
        }
        
        // Check if they share city/state components
        $userParts = explode(',', strtolower($userLocation));
        $candidateParts = explode(',', strtolower($candidateLocation));
        
        $commonParts = array_intersect(
            array_map('trim', $userParts),
            array_map('trim', $candidateParts)
        );
        
        return count($commonParts) > 0 ? 0.5 : 0.0;
    }

    /**
     * Extract user interests from profile data
     */
    private function extractUserInterests(User $user): Collection
    {
        $interests = collect();
        
        // Extract from bio/description
        if ($user->bio) {
            $interests = $interests->merge($this->extractKeywordsFromText($user->bio));
        }
        
        // Extract from work experiences
        foreach ($user->workExperiences ?? [] as $experience) {
            if (isset($experience['industry'])) {
                $interests->push(strtolower($experience['industry']));
            }
            if (isset($experience['skills'])) {
                $interests = $interests->merge(
                    collect($experience['skills'])->map(fn($skill) => strtolower($skill))
                );
            }
        }
        
        // Extract from education
        foreach ($user->educations ?? [] as $education) {
            if (isset($education['field_of_study'])) {
                $interests->push(strtolower($education['field_of_study']));
            }
        }
        
        return $interests->unique()->filter();
    }

    /**
     * Extract keywords from text
     */
    private function extractKeywordsFromText(string $text): Collection
    {
        // Simple keyword extraction - could be enhanced with NLP
        $commonWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'a', 'an'];
        
        $words = collect(explode(' ', strtolower($text)))
            ->map(fn($word) => preg_replace('/[^a-z0-9]/', '', $word))
            ->filter(fn($word) => strlen($word) > 3)
            ->reject(fn($word) => in_array($word, $commonWords));
        
        return $words;
    }

    /**
     * Get connection reasons for display
     */
    private function getConnectionReasons(User $user, User $candidate): array
    {
        $reasons = [];
        
        $sharedCircles = $this->getSharedCircles($user, $candidate);
        if ($sharedCircles->count() > 0) {
            $reasons[] = [
                'type' => 'shared_circles',
                'message' => 'You share ' . $sharedCircles->count() . ' circle(s)',
                'details' => $sharedCircles->pluck('name')->toArray()
            ];
        }
        
        $mutualConnections = $this->getMutualConnections($user, $candidate);
        if ($mutualConnections->count() > 0) {
            $reasons[] = [
                'type' => 'mutual_connections',
                'message' => $mutualConnections->count() . ' mutual connection(s)',
                'details' => $mutualConnections->pluck('name')->take(3)->toArray()
            ];
        }
        
        $interestScore = $this->getInterestSimilarity($user, $candidate);
        if ($interestScore > 0.3) {
            $reasons[] = [
                'type' => 'similar_interests',
                'message' => 'Similar interests and background',
                'details' => []
            ];
        }
        
        if ($user->location && $candidate->location && 
            strtolower($user->location) === strtolower($candidate->location)) {
            $reasons[] = [
                'type' => 'same_location',
                'message' => 'Located in ' . $candidate->location,
                'details' => []
            ];
        }
        
        return $reasons;
    }

    /**
     * Check if users are already connected
     */
    private function areUsersConnected(User $user, User $candidate): bool
    {
        return Connection::where(function ($query) use ($user, $candidate) {
            $query->where('user_id', $user->id)
                  ->where('connected_user_id', $candidate->id);
        })->orWhere(function ($query) use ($user, $candidate) {
            $query->where('user_id', $candidate->id)
                  ->where('connected_user_id', $user->id);
        })->where('status', 'accepted')->exists();
    }

    /**
     * Check if connection request is pending
     */
    private function hasConnectionRequestPending(User $user, User $candidate): bool
    {
        return Connection::where(function ($query) use ($user, $candidate) {
            $query->where('user_id', $user->id)
                  ->where('connected_user_id', $candidate->id);
        })->orWhere(function ($query) use ($user, $candidate) {
            $query->where('user_id', $candidate->id)
                  ->where('connected_user_id', $user->id);
        })->where('status', 'pending')->exists();
    }

    /**
     * Check if recommendation was dismissed
     */
    private function isRecommendationDismissed(User $user, User $candidate): bool
    {
        $dismissedKey = "dismissed_recommendations:user:{$user->id}";
        $dismissed = Cache::get($dismissedKey, []);
        
        return in_array($candidate->id, $dismissed);
    }

    /**
     * Check if user can be recommended based on privacy settings
     */
    private function canUserBeRecommended(User $candidate): bool
    {
        $privacySettings = $candidate->privacy_settings ?? [];
        
        return !isset($privacySettings['hide_from_recommendations']) || 
               !$privacySettings['hide_from_recommendations'];
    }

    /**
     * Dismiss a recommendation
     */
    public function dismissRecommendation(User $user, int $candidateId): void
    {
        $dismissedKey = "dismissed_recommendations:user:{$user->id}";
        $dismissed = Cache::get($dismissedKey, []);
        
        if (!in_array($candidateId, $dismissed)) {
            $dismissed[] = $candidateId;
            Cache::put($dismissedKey, $dismissed, now()->addDays(30));
        }
        
        // Clear user's recommendation cache to refresh
        Cache::forget(self::CACHE_PREFIX . $user->id);
    }

    /**
     * Clear recommendation cache for user
     */
    public function clearRecommendationCache(User $user): void
    {
        Cache::forget(self::CACHE_PREFIX . $user->id);
    }

    /**
     * Get second-degree connections for graph analysis
     */
    public function getSecondDegreeConnections(User $user): Collection
    {
        $firstDegreeIds = $user->connections()
            ->where('status', 'accepted')
            ->pluck('connected_user_id');
        
        if ($firstDegreeIds->isEmpty()) {
            return collect();
        }
        
        $secondDegreeIds = Connection::whereIn('user_id', $firstDegreeIds)
            ->where('status', 'accepted')
            ->where('connected_user_id', '!=', $user->id)
            ->whereNotIn('connected_user_id', $firstDegreeIds)
            ->pluck('connected_user_id')
            ->unique();
        
        return User::whereIn('id', $secondDegreeIds)->get();
    }
}