<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'privacy',
        'institution_id',
        'creator_id',
        'settings',
        'member_count',
    ];

    protected $casts = [
        'settings' => 'array',
        'member_count' => 'integer',
    ];

    /**
     * The institution this group belongs to.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * The user who created this group.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * The users that belong to this group.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_memberships')
                    ->withPivot('role', 'joined_at', 'status')
                    ->withTimestamps();
    }

    /**
     * Get active members of this group.
     */
    public function activeMembers(): BelongsToMany
    {
        return $this->users()->wherePivot('status', 'active');
    }

    /**
     * Get pending members of this group.
     */
    public function pendingMembers(): BelongsToMany
    {
        return $this->users()->wherePivot('status', 'pending');
    }

    /**
     * Get admins of this group.
     */
    public function admins(): BelongsToMany
    {
        return $this->activeMembers()->wherePivot('role', 'admin');
    }

    /**
     * Get moderators of this group.
     */
    public function moderators(): BelongsToMany
    {
        return $this->activeMembers()->wherePivot('role', 'moderator');
    }

    /**
     * Get posts in this group.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class)->whereJsonContains('group_ids', $this->id);
    }

    /**
     * Add a user to this group with a specific role.
     */
    public function addMember(User $user, string $role = 'member'): bool
    {
        if ($this->users()->where('user_id', $user->id)->exists()) {
            return false; // User already in group
        }

        $status = $this->privacy === 'private' ? 'pending' : 'active';

        $this->users()->attach($user->id, [
            'role' => $role,
            'joined_at' => now(),
            'status' => $status,
        ]);

        if ($status === 'active') {
            $this->updateMemberCount();
        }
        
        return true;
    }

    /**
     * Remove a user from this group.
     */
    public function removeMember(User $user): bool
    {
        $detached = $this->users()->detach($user->id);
        
        if ($detached) {
            $this->updateMemberCount();
            return true;
        }
        
        return false;
    }

    /**
     * Update the member count for this group.
     */
    public function updateMemberCount(): void
    {
        $count = $this->activeMembers()->count();
        $this->update(['member_count' => $count]);
    }

    /**
     * Check if a user can join this group.
     */
    public function canUserJoin(User $user): bool
    {
        // Check if user is already a member
        if ($this->users()->where('user_id', $user->id)->exists()) {
            return false;
        }

        switch ($this->privacy) {
            case 'public':
                return true;

            case 'private':
                // Private groups require invitation or approval
                return false;

            case 'secret':
                // Secret groups require invitation
                return false;

            default:
                return false;
        }
    }

    /**
     * Check if a user can post in this group.
     */
    public function canUserPost(User $user): bool
    {
        $membership = $this->users()
            ->where('user_id', $user->id)
            ->wherePivot('status', 'active')
            ->first();

        if (!$membership) {
            return false;
        }

        // Check group settings for posting permissions
        $settings = $this->settings ?? [];
        $postingRestriction = $settings['posting_restriction'] ?? 'all_members';

        switch ($postingRestriction) {
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
     * Check if a user is an admin of this group.
     */
    public function isAdmin(User $user): bool
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'admin')
            ->wherePivot('status', 'active')
            ->exists();
    }

    /**
     * Check if a user is a moderator or admin of this group.
     */
    public function isModerator(User $user): bool
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->whereIn('group_memberships.role', ['moderator', 'admin'])
            ->wherePivot('status', 'active')
            ->exists();
    }

    /**
     * Approve a pending member.
     */
    public function approveMember(User $user): bool
    {
        $updated = $this->users()
            ->where('user_id', $user->id)
            ->wherePivot('status', 'pending')
            ->updateExistingPivot($user->id, ['status' => 'active']);

        if ($updated) {
            $this->updateMemberCount();
            return true;
        }

        return false;
    }

    /**
     * Reject a pending member.
     */
    public function rejectMember(User $user): bool
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->wherePivot('status', 'pending')
            ->detach($user->id) > 0;
    }

    /**
     * Scope to get groups by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get public groups.
     */
    public function scopePublic($query)
    {
        return $query->where('privacy', 'public');
    }

    /**
     * Scope to get groups by institution.
     */
    public function scopeForInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Scope to get school-based groups.
     */
    public function scopeSchoolGroups($query)
    {
        return $query->where('type', 'school');
    }
}