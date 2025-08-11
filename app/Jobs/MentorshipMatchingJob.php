<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\MentorSuggestionsNotification;
use App\Services\MentorshipService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MentorshipMatchingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private ?int $userId = null
    ) {}

    public function handle(MentorshipService $mentorshipService): void
    {
        try {
            if ($this->userId) {
                // Process specific user
                $user = User::find($this->userId);
                if ($user) {
                    $this->processUserMatching($user, $mentorshipService);
                }
            } else {
                // Process all users who might benefit from mentorship suggestions
                $users = User::whereDoesntHave('mentorProfile')
                    ->whereHas('careerTimelines')
                    ->where('created_at', '>', now()->subDays(30))
                    ->orWhere('updated_at', '>', now()->subDays(7))
                    ->limit(100)
                    ->get();

                foreach ($users as $user) {
                    $this->processUserMatching($user, $mentorshipService);
                }
            }
        } catch (\Exception $e) {
            Log::error('MentorshipMatchingJob failed: '.$e->getMessage());
            throw $e;
        }
    }

    private function processUserMatching(User $user, MentorshipService $mentorshipService): void
    {
        // Skip if user already has active mentorships as mentee
        $activeMentorships = $user->menteeRequests()
            ->where('status', 'accepted')
            ->count();

        if ($activeMentorships >= 2) {
            return; // User already has enough mentors
        }

        // Find potential mentors
        $suggestedMentors = $mentorshipService->matchMentorToMentee($user)
            ->take(5);

        if ($suggestedMentors->isNotEmpty()) {
            // Send notification with mentor suggestions
            $user->notify(new MentorSuggestionsNotification($suggestedMentors));
        }
    }
}
