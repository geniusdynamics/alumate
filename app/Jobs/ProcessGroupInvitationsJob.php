<?php

namespace App\Jobs;

use App\Models\Group;
use App\Models\User;
use App\Services\GroupManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProcessGroupInvitationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Group $group;

    protected Collection $userIds;

    protected User $inviter;

    protected ?string $message;

    /**
     * Create a new job instance.
     */
    public function __construct(Group $group, Collection $userIds, User $inviter, ?string $message = null)
    {
        $this->group = $group;
        $this->userIds = $userIds;
        $this->inviter = $inviter;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(GroupManager $groupManager): void
    {
        try {
            Log::info('Processing bulk group invitations', [
                'group_id' => $this->group->id,
                'inviter_id' => $this->inviter->id,
                'user_count' => $this->userIds->count(),
            ]);

            $successCount = 0;
            $failureCount = 0;

            foreach ($this->userIds as $userId) {
                try {
                    $user = User::find($userId);

                    if (! $user) {
                        Log::warning('User not found for group invitation', [
                            'user_id' => $userId,
                            'group_id' => $this->group->id,
                        ]);
                        $failureCount++;

                        continue;
                    }

                    $success = $groupManager->handleInvitation($this->group, $user, $this->inviter);

                    if ($success) {
                        $successCount++;
                        Log::debug('Successfully processed group invitation', [
                            'user_id' => $userId,
                            'group_id' => $this->group->id,
                        ]);
                    } else {
                        $failureCount++;
                        Log::warning('Failed to process group invitation', [
                            'user_id' => $userId,
                            'group_id' => $this->group->id,
                        ]);
                    }

                    // Add a small delay to prevent overwhelming the system
                    if ($successCount % 10 === 0) {
                        sleep(1);
                    }

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('Exception while processing group invitation', [
                        'user_id' => $userId,
                        'group_id' => $this->group->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Completed bulk group invitations', [
                'group_id' => $this->group->id,
                'inviter_id' => $this->inviter->id,
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'total_count' => $this->userIds->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process bulk group invitations', [
                'group_id' => $this->group->id,
                'inviter_id' => $this->inviter->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessGroupInvitationsJob failed permanently', [
            'group_id' => $this->group->id,
            'inviter_id' => $this->inviter->id,
            'user_count' => $this->userIds->count(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'group-invitations',
            'group:'.$this->group->id,
            'inviter:'.$this->inviter->id,
        ];
    }

    /**
     * Calculate the number of seconds the job can run before timing out.
     */
    public function timeout(): int
    {
        // Allow 5 seconds per user, with a minimum of 60 seconds
        return max(60, $this->userIds->count() * 5);
    }
}
