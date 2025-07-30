<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\GroupInvitationNotification;
use App\Notifications\GroupJoinRequestNotification;

class GroupManager
{
    /**
     * Create a new group with the creator as admin.
     */
    public function createGroup(array $data, User $creator): Group
    {
        return DB::transaction(function () use ($data, $creator) {
            $group = Group::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'],
                'privacy' => $data['privacy'],
                'institution_id' => $data['institution_id'] ?? null,
                'creator_id' => $creator->id,
                'settings' => $data['settings'] ?? [],
                'member_count' => 1,
            ]);

            // Add creator as admin
            $group->users()->attach($creator->id, [
                'role' => 'admin',
                'joined_at' => now(),
                'status' => 'active',
            ]);

            return $group;
        });
    }

    /**
     * Handle group invitation process.
     */
    public function handleInvitation(Group $group, User $user, User $inviter): bool
    {
        try {
            // Check if user is already a member
            if ($group->users()->where('user_id', $user->id)->exists()) {
                return false;
            }

            // Check if inviter has permission to invite
            if (!$this->canInviteToGroup($group, $inviter)) {
                return false;
            }

            // For school groups, check if user belongs to the institution
            if ($group->type === 'school' && $group->institution_id) {
                if ($this->userBelongsToSchool($user, $group->institution_id)) {
                    // Auto-join for school groups
                    return $group->addMember($user, 'member');
                }
            }

            // Send invitation for other group types
            return $this->sendInvitation($group, $user, $inviter);
        } catch (\Exception $e) {
            Log::error('Failed to handle group invitation', [
                'group_id' => $group->id,
                'user_id' => $user->id,
                'inviter_id' => $inviter->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Automatically join user to their school's groups.
     */
    public function autoJoinSchoolGroups(User $user): int
    {
        $joinedCount = 0;
        
        // Get user's institution names
        $institutionNames = $user->educations()
            ->pluck('institution_name')
            ->unique();

        if ($institutionNames->isEmpty()) {
            return $joinedCount;
        }

        // Get tenant IDs that match user's institution names
        $institutionIds = Tenant::whereIn('name', $institutionNames)->pluck('id');

        if ($institutionIds->isEmpty()) {
            return $joinedCount;
        }

        // Find school groups for these institutions
        $schoolGroups = Group::schoolGroups()
            ->whereIn('institution_id', $institutionIds)
            ->where('privacy', 'public')
            ->get();

        foreach ($schoolGroups as $group) {
            if ($group->addMember($user, 'member')) {
                $joinedCount++;
            }
        }

        return $joinedCount;
    }

    /**
     * Send group invitation to a user.
     */
    public function sendInvitation(Group $group, User $user, User $inviter, string $message = null): bool
    {
        try {
            // Create invitation record (you might want to create an Invitation model)
            // For now, we'll send the notification directly
            
            $user->notify(new GroupInvitationNotification($group, $inviter, $message));
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send group invitation', [
                'group_id' => $group->id,
                'user_id' => $user->id,
                'inviter_id' => $inviter->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Process join request for private groups.
     */
    public function processJoinRequest(Group $group, User $user): bool
    {
        try {
            // Check if user can join
            if (!$group->canUserJoin($user) && $group->privacy !== 'private') {
                return false;
            }

            if ($group->privacy === 'private') {
                // Add as pending member
                $group->users()->attach($user->id, [
                    'role' => 'member',
                    'joined_at' => now(),
                    'status' => 'pending',
                ]);

                // Notify group admins
                $admins = $group->admins()->get();
                foreach ($admins as $admin) {
                    $admin->notify(new GroupJoinRequestNotification($group, $user));
                }

                return true;
            }

            // For public groups, join immediately
            return $group->addMember($user, 'member');
        } catch (\Exception $e) {
            Log::error('Failed to process join request', [
                'group_id' => $group->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Approve a pending member.
     */
    public function approveMember(Group $group, User $user, User $approver): bool
    {
        if (!$group->isAdmin($approver) && !$group->isModerator($approver)) {
            return false;
        }

        return $group->approveMember($user);
    }

    /**
     * Reject a pending member.
     */
    public function rejectMember(Group $group, User $user, User $rejector): bool
    {
        if (!$group->isAdmin($rejector) && !$group->isModerator($rejector)) {
            return false;
        }

        return $group->rejectMember($user);
    }

    /**
     * Remove a member from the group.
     */
    public function removeMember(Group $group, User $user, User $remover): bool
    {
        // Check permissions
        if (!$this->canRemoveMember($group, $user, $remover)) {
            return false;
        }

        return $group->removeMember($user);
    }

    /**
     * Update member role in the group.
     */
    public function updateMemberRole(Group $group, User $user, string $newRole, User $updater): bool
    {
        // Check permissions
        if (!$group->isAdmin($updater)) {
            return false;
        }

        // Can't change creator's role
        if ($user->id === $group->creator_id) {
            return false;
        }

        try {
            $group->users()
                ->where('user_id', $user->id)
                ->updateExistingPivot($user->id, ['role' => $newRole]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update member role', [
                'group_id' => $group->id,
                'user_id' => $user->id,
                'new_role' => $newRole,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if user belongs to a specific school.
     */
    protected function userBelongsToSchool(User $user, int $institutionId): bool
    {
        // Get the institution name from the tenant
        $tenant = Tenant::find($institutionId);
        if (!$tenant) {
            return false;
        }

        return $user->educations()
            ->where('institution_name', $tenant->name)
            ->exists();
    }

    /**
     * Check if user can invite others to the group.
     */
    protected function canInviteToGroup(Group $group, User $user): bool
    {
        $membership = $group->users()
            ->where('user_id', $user->id)
            ->wherePivot('status', 'active')
            ->first();

        if (!$membership) {
            return false;
        }

        $settings = $group->settings ?? [];
        $invitePermission = $settings['invite_permission'] ?? 'all_members';

        switch ($invitePermission) {
            case 'admins_only':
                return $membership->pivot->role === 'admin';

            case 'admins_and_moderators':
                return in_array($membership->pivot->role, ['admin', 'moderator']);

            case 'all_members':
            default:
                return true;
        }
    }

    /**
     * Check if user can remove a member from the group.
     */
    protected function canRemoveMember(Group $group, User $memberToRemove, User $remover): bool
    {
        // Users can remove themselves
        if ($memberToRemove->id === $remover->id) {
            return true;
        }

        // Only admins and moderators can remove others
        if (!$group->isModerator($remover)) {
            return false;
        }

        // Can't remove the creator
        if ($memberToRemove->id === $group->creator_id) {
            return false;
        }

        // Moderators can't remove admins
        $memberRole = $group->users()
            ->where('user_id', $memberToRemove->id)
            ->first()?->pivot?->role;

        if ($memberRole === 'admin' && !$group->isAdmin($remover)) {
            return false;
        }

        return true;
    }

    /**
     * Get group statistics.
     */
    public function getGroupStatistics(): array
    {
        return [
            'total_groups' => Group::count(),
            'school_groups' => Group::ofType('school')->count(),
            'custom_groups' => Group::ofType('custom')->count(),
            'interest_groups' => Group::ofType('interest')->count(),
            'professional_groups' => Group::ofType('professional')->count(),
            'public_groups' => Group::public()->count(),
            'private_groups' => Group::where('privacy', 'private')->count(),
            'secret_groups' => Group::where('privacy', 'secret')->count(),
            'average_members_per_group' => Group::avg('member_count'),
            'largest_group_size' => Group::max('member_count'),
        ];
    }

    /**
     * Get recommended groups for a user.
     */
    public function getRecommendedGroups(User $user, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        // Get user's institution names
        $userInstitutionNames = $user->educations()
            ->pluck('institution_name')
            ->unique();

        // Get tenant IDs that match user's institution names
        $userInstitutionIds = collect();
        if ($userInstitutionNames->isNotEmpty()) {
            $userInstitutionIds = Tenant::whereIn('name', $userInstitutionNames)
                ->pluck('id');
        }

        return Group::public()
            ->where(function ($query) use ($userInstitutionIds, $user) {
                // School groups from user's institutions
                if ($userInstitutionIds->isNotEmpty()) {
                    $query->where(function ($q) use ($userInstitutionIds) {
                        $q->where('type', 'school')
                          ->whereIn('institution_id', $userInstitutionIds);
                    });
                }
                // Or interest/professional groups
                $query->orWhereIn('type', ['interest', 'professional']);
            })
            ->whereNotExists(function ($query) use ($user) {
                $query->select(DB::raw(1))
                      ->from('group_memberships')
                      ->whereColumn('group_memberships.group_id', 'groups.id')
                      ->where('group_memberships.user_id', $user->id);
            })
            ->orderBy('member_count', 'desc')
            ->limit($limit)
            ->get();
    }
}