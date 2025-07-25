<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\Graduate;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendJobMatchNotifications extends Command
{
    protected $signature = 'notifications:job-matches {--hours=24 : Send notifications for jobs posted in the last X hours}';
    protected $description = 'Send job match notifications to graduates for recently posted jobs';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $hours = (int) $this->option('hours');
        $since = now()->subHours($hours);
        
        $this->info("Sending job match notifications for jobs posted in the last {$hours} hours...");

        // Get recently posted active jobs
        $jobs = Job::where('status', 'active')
            ->where('created_at', '>=', $since)
            ->with(['employer', 'course'])
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('No new jobs found in the specified time period.');
            return;
        }

        $this->info("Found {$jobs->count()} new jobs to process.");

        $totalNotifications = 0;

        foreach ($jobs as $job) {
            $this->line("Processing job: {$job->title} at {$job->employer->company_name}");

            // Get matching graduates for this job
            $graduates = $this->getMatchingGraduates($job);

            foreach ($graduates as $graduate) {
                try {
                    $this->notificationService->sendJobMatchNotification(
                        $graduate->user,
                        $job
                    );
                    $totalNotifications++;
                } catch (\Exception $e) {
                    Log::error("Failed to send job match notification to graduate {$graduate->id}: " . $e->getMessage());
                    $this->error("Failed to send notification to {$graduate->user->name}");
                }
            }
        }

        $this->info("Successfully sent {$totalNotifications} job match notifications.");
    }

    private function getMatchingGraduates($job)
    {
        $query = Graduate::with(['user'])
            ->whereHas('user', function($q) {
                $q->whereHas('roles', function($roleQuery) {
                    $roleQuery->where('name', 'graduate');
                });
            });

        // Match by course if specified
        if ($job->course_id) {
            $query->where('course_id', $job->course_id);
        }

        // Match by skills if available
        if ($job->required_skills || $job->preferred_skills) {
            $query->where(function($q) use ($job) {
                if ($job->required_skills) {
                    $q->whereJsonOverlaps('skills', $job->required_skills);
                }
                if ($job->preferred_skills) {
                    $q->orWhereJsonOverlaps('skills', $job->preferred_skills);
                }
            });
        }

        // Match by experience level
        if ($job->experience_level) {
            // This would need to be implemented based on how graduate experience is stored
            // For now, we'll skip this filter
        }

        // Exclude graduates who already applied
        $appliedGraduateIds = $job->applications()->pluck('graduate_id');
        if ($appliedGraduateIds->isNotEmpty()) {
            $query->whereNotIn('id', $appliedGraduateIds);
        }

        // Only include graduates who allow job match notifications
        $query->whereHas('user.notificationPreferences', function($q) {
            $q->where('notification_type', 'job_match')
              ->where(function($prefQuery) {
                  $prefQuery->where('email_enabled', true)
                           ->orWhere('sms_enabled', true)
                           ->orWhere('in_app_enabled', true)
                           ->orWhere('push_enabled', true);
              });
        });

        // Prioritize by profile completion and recent activity
        $query->orderByDesc('profile_completed_at')
              ->orderByDesc('updated_at');

        return $query->limit(50)->get(); // Limit to prevent spam
    }
}