<?php

namespace App\Console\Commands;

use App\Models\Graduate;
use App\Models\Job;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendJobDeadlineReminders extends Command
{
    protected $signature = 'notifications:job-deadlines {--days=3 : Number of days before deadline to send reminder}';

    protected $description = 'Send job application deadline reminders to graduates';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $days = (int) $this->option('days');
        $targetDate = now()->addDays($days)->startOfDay();

        $this->info("Sending job deadline reminders for jobs expiring in {$days} days...");

        // Get jobs with deadlines approaching
        $jobs = Job::where('status', 'active')
            ->whereNotNull('application_deadline')
            ->whereDate('application_deadline', $targetDate)
            ->with(['employer', 'course'])
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('No jobs found with deadlines in '.$days.' days.');

            return;
        }

        $this->info("Found {$jobs->count()} jobs with approaching deadlines.");

        $totalNotifications = 0;

        foreach ($jobs as $job) {
            $this->line("Processing job: {$job->title} at {$job->employer->company_name}");

            // Get graduates who might be interested in this job
            $graduates = $this->getInterestedGraduates($job);

            foreach ($graduates as $graduate) {
                try {
                    $this->notificationService->sendJobDeadlineNotification(
                        $graduate->user,
                        $job,
                        $days
                    );
                    $totalNotifications++;
                } catch (\Exception $e) {
                    Log::error("Failed to send deadline reminder to graduate {$graduate->id}: ".$e->getMessage());
                    $this->error("Failed to send notification to {$graduate->user->name}");
                }
            }
        }

        $this->info("Successfully sent {$totalNotifications} deadline reminder notifications.");
    }

    private function getInterestedGraduates($job)
    {
        $query = Graduate::with(['user'])
            ->whereHas('user', function ($q) {
                $q->whereHas('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'graduate');
                });
            });

        // Match by course if specified
        if ($job->course_id) {
            $query->where('course_id', $job->course_id);
        }

        // Match by skills if available
        if ($job->required_skills) {
            $query->where(function ($q) use ($job) {
                $q->whereJsonOverlaps('skills', $job->required_skills)
                    ->orWhereJsonOverlaps('skills', $job->preferred_skills ?? []);
            });
        }

        // Exclude graduates who already applied
        $appliedGraduateIds = $job->applications()->pluck('graduate_id');
        if ($appliedGraduateIds->isNotEmpty()) {
            $query->whereNotIn('id', $appliedGraduateIds);
        }

        // Only include graduates who allow job notifications
        $query->whereHas('user.notificationPreferences', function ($q) {
            $q->where('notification_type', 'job_deadline')
                ->where(function ($prefQuery) {
                    $prefQuery->where('email_enabled', true)
                        ->orWhere('sms_enabled', true)
                        ->orWhere('in_app_enabled', true)
                        ->orWhere('push_enabled', true);
                });
        });

        return $query->limit(100)->get(); // Limit to prevent overwhelming the system
    }
}
