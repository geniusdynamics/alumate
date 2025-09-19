<?php

namespace App\Services;

use App\Models\LandingPage;
use App\Models\CollaborationSession;
use App\Models\PageChange;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CollaborationService
{
    public function __construct(
        private OperationalTransformService $operationalTransform
    ) {}

    public function startSession(LandingPage $page, User $user): CollaborationSession
    {
        // End any existing sessions for this user on this page
        $this->endUserSessions($page, $user);

        return CollaborationSession::create([
            'page_id' => $page->id,
            'user_id' => $user->id,
            'session_id' => Str::uuid()->toString(),
            'status' => 'active',
            'last_activity' => now(),
        ]);
    }

    public function endSession(string $sessionId): void
    {
        $session = CollaborationSession::where('session_id', $sessionId)->first();
        
        if ($session) {
            $session->disconnect();
        }
    }

    public function getActiveSessions(LandingPage $page): Collection
    {
        return CollaborationSession::forPage($page->id)
            ->active()
            ->recentActivity(10) // Active within last 10 minutes
            ->with(['user:id,name,email,avatar'])
            ->get();
    }

    public function updateSessionActivity(
        string $sessionId,
        ?array $cursorPosition = null,
        ?array $selectedComponent = null
    ): void {
        $session = CollaborationSession::where('session_id', $sessionId)->first();
        
        if ($session) {
            $updateData = ['last_activity' => now(), 'status' => 'active'];
            
            if ($cursorPosition !== null) {
                $updateData['cursor_position'] = $cursorPosition;
            }
            
            if ($selectedComponent !== null) {
                $updateData['selected_component'] = $selectedComponent;
            }
            
            $session->update($updateData);
        }
    }

    public function recordChange(
        LandingPage $page,
        User $user,
        string $sessionId,
        string $operationType,
        array $operationData,
        ?string $componentId = null,
        ?array $previousState = null,
        ?array $newState = null
    ): PageChange {
        $sequenceNumber = $this->getNextSequenceNumber($page);

        return PageChange::create([
            'page_id' => $page->id,
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'operation_type' => $operationType,
            'component_id' => $componentId,
            'operation_data' => $operationData,
            'previous_state' => $previousState,
            'new_state' => $newState,
            'sequence_number' => $sequenceNumber,
            'is_applied' => false,
        ]);
    }

    public function applyChanges(LandingPage $page, array $changeIds): array
    {
        return DB::transaction(function () use ($page, $changeIds) {
            $changes = PageChange::whereIn('id', $changeIds)
                ->forPage($page->id)
                ->unapplied()
                ->inSequence()
                ->get();

            $appliedChanges = [];
            $conflicts = [];

            foreach ($changes as $change) {
                try {
                    $transformedChange = $this->operationalTransform
                        ->transformChange($change, $appliedChanges);

                    if ($transformedChange) {
                        $change->markAsApplied();
                        $appliedChanges[] = $transformedChange;
                    }
                } catch (\Exception $e) {
                    $conflicts[] = [
                        'change_id' => $change->id,
                        'error' => $e->getMessage(),
                        'change' => $change,
                    ];
                }
            }

            return [
                'applied' => $appliedChanges,
                'conflicts' => $conflicts,
            ];
        });
    }

    public function getRecentChanges(LandingPage $page, int $limit = 100): Collection
    {
        return PageChange::forPage($page->id)
            ->with(['user:id,name,email'])
            ->orderBy('sequence_number', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getChangesSince(LandingPage $page, int $sequenceNumber): Collection
    {
        return PageChange::forPage($page->id)
            ->where('sequence_number', '>', $sequenceNumber)
            ->with(['user:id,name,email'])
            ->inSequence()
            ->get();
    }

    public function resolveConflict(
        PageChange $change,
        string $resolution, // 'accept', 'reject', 'merge'
        ?array $mergedData = null
    ): bool {
        return DB::transaction(function () use ($change, $resolution, $mergedData) {
            switch ($resolution) {
                case 'accept':
                    $change->markAsApplied();
                    return true;

                case 'reject':
                    $change->delete();
                    return true;

                case 'merge':
                    if ($mergedData) {
                        $change->update([
                            'operation_data' => $mergedData,
                            'is_applied' => true,
                        ]);
                        return true;
                    }
                    return false;

                default:
                    return false;
            }
        });
    }

    public function cleanupInactiveSessions(): int
    {
        $cutoff = now()->subMinutes(30);
        
        return CollaborationSession::where('last_activity', '<', $cutoff)
            ->update(['status' => 'disconnected']);
    }

    public function getUserActivity(LandingPage $page, User $user, int $hours = 24): Collection
    {
        $since = now()->subHours($hours);

        return PageChange::forPage($page->id)
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function endUserSessions(LandingPage $page, User $user): void
    {
        CollaborationSession::forPage($page->id)
            ->where('user_id', $user->id)
            ->active()
            ->update(['status' => 'disconnected']);
    }

    private function getNextSequenceNumber(LandingPage $page): int
    {
        $lastChange = PageChange::forPage($page->id)
            ->orderBy('sequence_number', 'desc')
            ->first();

        return $lastChange ? $lastChange->sequence_number + 1 : 1;
    }
}
