<?php

namespace App\Listeners;

use App\Services\AchievementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CheckAchievementsListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private AchievementService $achievementService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        try {
            // Extract user from different event types
            $user = $this->getUserFromEvent($event);
            
            if (!$user) {
                return;
            }

            // Check and award achievements
            $newAchievements = $this->achievementService->checkAndAwardAchievements($user);

            if (!empty($newAchievements)) {
                Log::info('New achievements awarded', [
                    'user_id' => $user->id,
                    'achievement_count' => count($newAchievements),
                    'achievements' => array_map(fn($ua) => $ua->achievement->name, $newAchievements)
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to check achievements', [
                'event' => get_class($event),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Extract user from various event types
     */
    private function getUserFromEvent(object $event)
    {
        // Handle different event types
        if (property_exists($event, 'user')) {
            return $event->user;
        }

        if (property_exists($event, 'model') && method_exists($event->model, 'user')) {
            return $event->model->user;
        }

        if (property_exists($event, 'careerMilestone')) {
            return $event->careerMilestone->user;
        }

        if (property_exists($event, 'post')) {
            return $event->post->user;
        }

        if (property_exists($event, 'connection')) {
            return $event->connection->user;
        }

        return null;
    }
}