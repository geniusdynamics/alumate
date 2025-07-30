<?php

namespace App\Services;

use App\Models\MentorProfile;
use App\Models\MentorshipRequest;
use App\Models\MentorshipSession;
use App\Models\User;
use App\Notifications\MentorshipRequestNotification;
use App\Notifications\MentorshipAcceptedNotification;
use App\Notifications\SessionScheduledNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MentorshipService
{
    public function matchMentorToMentee(User $mentee, array $criteria = []): Collection
    {
        $query = MentorProfile::with(['user.educations', 'user.careerTimelines'])
            ->available()
            ->whereHas('user', function ($q) use ($mentee) {
                // Exclude self
                $q->where('id', '!=', $mentee->id);
            });

        // Apply filters if provided
        if (!empty($criteria['expertise_areas'])) {
            $query->where(function ($q) use ($criteria) {
                foreach ($criteria['expertise_areas'] as $area) {
                    $q->orWhereJsonContains('expertise_areas', $area);
                }
            });
        }

        if (!empty($criteria['availability'])) {
            $query->where('availability', $criteria['availability']);
        }

        $mentors = $query->get();

        // Score and rank mentors
        $scoredMentors = $mentors->map(function ($mentorProfile) use ($mentee) {
            $score = $this->calculateMentorScore($mentorProfile, $mentee);
            $mentorProfile->match_score = $score;
            return $mentorProfile;
        });

        return $scoredMentors->sortByDesc('match_score');
    }

    private function calculateMentorScore(MentorProfile $mentorProfile, User $mentee): float
    {
        $score = 0;
        $mentor = $mentorProfile->user;

        // Industry/role alignment (40%)
        $industryScore = $this->calculateIndustryAlignment($mentor, $mentee);
        $score += $industryScore * 0.4;

        // Career stage compatibility (30%)
        $careerStageScore = $this->calculateCareerStageCompatibility($mentor, $mentee);
        $score += $careerStageScore * 0.3;

        // Geographic proximity (15%)
        $locationScore = $this->calculateLocationScore($mentor, $mentee);
        $score += $locationScore * 0.15;

        // Availability match (15%)
        $availabilityScore = $this->calculateAvailabilityScore($mentorProfile);
        $score += $availabilityScore * 0.15;

        return $score;
    }

    private function calculateIndustryAlignment(User $mentor, User $mentee): float
    {
        $mentorIndustries = $mentor->careerTimelines->pluck('industry')->filter()->unique();
        $menteeIndustries = $mentee->careerTimelines->pluck('industry')->filter()->unique();

        if ($mentorIndustries->isEmpty() || $menteeIndustries->isEmpty()) {
            return 0.5; // Neutral score if no industry data
        }

        $commonIndustries = $mentorIndustries->intersect($menteeIndustries);
        return $commonIndustries->count() / max($mentorIndustries->count(), $menteeIndustries->count());
    }

    private function calculateCareerStageCompatibility(User $mentor, User $mentee): float
    {
        $mentorExperience = $mentor->careerTimelines->sum(function ($timeline) {
            $start = $timeline->start_date;
            $end = $timeline->end_date ?? now();
            return $start->diffInYears($end);
        });

        $menteeExperience = $mentee->careerTimelines->sum(function ($timeline) {
            $start = $timeline->start_date;
            $end = $timeline->end_date ?? now();
            return $start->diffInYears($end);
        });

        $experienceGap = $mentorExperience - $menteeExperience;

        // Ideal gap is 5-15 years
        if ($experienceGap >= 5 && $experienceGap <= 15) {
            return 1.0;
        } elseif ($experienceGap >= 3 && $experienceGap <= 20) {
            return 0.7;
        } elseif ($experienceGap >= 1) {
            return 0.4;
        }

        return 0.1;
    }

    private function calculateLocationScore(User $mentor, User $mentee): float
    {
        if (!$mentor->location || !$mentee->location) {
            return 0.5; // Neutral if no location data
        }

        // Simple location matching - in real implementation, use geocoding
        $mentorLocation = strtolower($mentor->location);
        $menteeLocation = strtolower($mentee->location);

        if ($mentorLocation === $menteeLocation) {
            return 1.0;
        }

        // Check if same city/state
        $mentorParts = explode(',', $mentorLocation);
        $menteeParts = explode(',', $menteeLocation);

        if (count($mentorParts) > 1 && count($menteeParts) > 1) {
            if (trim($mentorParts[1]) === trim($menteeParts[1])) {
                return 0.7; // Same state/region
            }
        }

        return 0.3; // Different locations
    }

    private function calculateAvailabilityScore(MentorProfile $mentorProfile): float
    {
        $currentMentees = $mentorProfile->getCurrentMenteeCount();
        $maxMentees = $mentorProfile->max_mentees;

        $availabilityRatio = 1 - ($currentMentees / $maxMentees);

        return match ($mentorProfile->availability) {
            'high' => $availabilityRatio * 1.0,
            'medium' => $availabilityRatio * 0.7,
            'low' => $availabilityRatio * 0.4,
            default => 0.5
        };
    }

    public function createMentorshipRequest(int $mentorId, int $menteeId, array $data): MentorshipRequest
    {
        // Check if there's already an active request
        $existingRequest = MentorshipRequest::where('mentor_id', $mentorId)
            ->where('mentee_id', $menteeId)
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existingRequest) {
            throw new \Exception('An active mentorship request already exists between these users.');
        }

        $request = MentorshipRequest::create([
            'mentor_id' => $mentorId,
            'mentee_id' => $menteeId,
            'message' => $data['message'],
            'goals' => $data['goals'] ?? null,
            'duration_months' => $data['duration_months'] ?? 6,
        ]);

        // Send notification to mentor
        $mentor = User::find($mentorId);
        $mentor->notify(new MentorshipRequestNotification($request));

        return $request;
    }

    public function acceptMentorshipRequest(int $requestId): MentorshipRequest
    {
        $request = MentorshipRequest::findOrFail($requestId);

        // Check if mentor still has available slots
        $mentorProfile = MentorProfile::where('user_id', $request->mentor_id)->first();
        if (!$mentorProfile || !$mentorProfile->hasAvailableSlots()) {
            throw new \Exception('Mentor no longer has available slots.');
        }

        DB::transaction(function () use ($request) {
            $request->accept();

            // Send notification to mentee
            $request->mentee->notify(new MentorshipAcceptedNotification($request));
        });

        return $request->fresh();
    }

    public function scheduleMentorshipSession(int $mentorshipId, array $data): MentorshipSession
    {
        $mentorship = MentorshipRequest::findOrFail($mentorshipId);

        if ($mentorship->status !== 'accepted') {
            throw new \Exception('Can only schedule sessions for accepted mentorships.');
        }

        $session = MentorshipSession::create([
            'mentorship_id' => $mentorshipId,
            'scheduled_at' => $data['scheduled_at'],
            'duration' => $data['duration'] ?? 60,
            'notes' => $data['notes'] ?? null,
        ]);

        // Send notifications to both mentor and mentee
        $mentorship->mentor->notify(new SessionScheduledNotification($session));
        $mentorship->mentee->notify(new SessionScheduledNotification($session));

        return $session;
    }

    public function getMentorshipAnalytics(int $mentorId): array
    {
        $mentorProfile = MentorProfile::where('user_id', $mentorId)->first();
        
        if (!$mentorProfile) {
            return [];
        }

        $totalMentorships = MentorshipRequest::where('mentor_id', $mentorId)->count();
        $activeMentorships = MentorshipRequest::where('mentor_id', $mentorId)
            ->where('status', 'accepted')
            ->count();
        $completedMentorships = MentorshipRequest::where('mentor_id', $mentorId)
            ->where('status', 'completed')
            ->count();

        $totalSessions = MentorshipSession::whereHas('mentorship', function ($q) use ($mentorId) {
            $q->where('mentor_id', $mentorId);
        })->count();

        $completedSessions = MentorshipSession::whereHas('mentorship', function ($q) use ($mentorId) {
            $q->where('mentor_id', $mentorId);
        })->where('status', 'completed')->count();

        $averageSessionRating = MentorshipSession::whereHas('mentorship', function ($q) use ($mentorId) {
            $q->where('mentor_id', $mentorId);
        })
        ->where('status', 'completed')
        ->whereNotNull('feedback')
        ->get()
        ->avg(function ($session) {
            return $session->feedback['rating'] ?? 0;
        });

        return [
            'total_mentorships' => $totalMentorships,
            'active_mentorships' => $activeMentorships,
            'completed_mentorships' => $completedMentorships,
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions,
            'session_completion_rate' => $totalSessions > 0 ? ($completedSessions / $totalSessions) * 100 : 0,
            'average_session_rating' => round($averageSessionRating, 2),
            'available_slots' => $mentorProfile->max_mentees - $activeMentorships,
        ];
    }

    public function createMentorProfile(User $user, array $data): MentorProfile
    {
        return MentorProfile::create([
            'user_id' => $user->id,
            'bio' => $data['bio'],
            'expertise_areas' => $data['expertise_areas'],
            'availability' => $data['availability'] ?? 'medium',
            'max_mentees' => $data['max_mentees'] ?? 3,
            'is_active' => true,
        ]);
    }

    public function updateMentorProfile(MentorProfile $profile, array $data): MentorProfile
    {
        $profile->update($data);
        return $profile->fresh();
    }

    public function getUpcomingSessions(User $user): Collection
    {
        return MentorshipSession::whereHas('mentorship', function ($q) use ($user) {
            $q->where('mentor_id', $user->id)
              ->orWhere('mentee_id', $user->id);
        })
        ->upcoming()
        ->with(['mentorship.mentor', 'mentorship.mentee'])
        ->orderBy('scheduled_at')
        ->get();
    }
}