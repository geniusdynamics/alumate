<?php

namespace App\Models;

use App\Traits\HasDataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasDataTable, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'institution_id',
        'profile_data',
        'preferences',
        'notification_preferences',
        'is_suspended',
        'suspended_at',
        'suspension_reason',
        'last_login_at',
        'last_activity_at',
        'email_verified_at',
        'two_factor_enabled',
        'timezone',
        'language',
        'status',
        'user_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected array $searchableColumns = [
        'name',
        'email',
        'phone',
    ];

    protected array $sortableColumns = [
        'name',
        'email',
        'created_at',
        'last_login_at',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'profile_data' => 'array',
            'preferences' => 'array',
            'notification_preferences' => 'array',
            'two_factor_enabled' => 'boolean',
            'is_suspended' => 'boolean',
        ];
    }

    // Relationships
    public function institution()
    {
        return $this->belongsTo(Tenant::class, 'institution_id');
    }

    public function graduate()
    {
        return $this->hasOne(Graduate::class);
    }

    public function employer()
    {
        return $this->hasOne(Employer::class);
    }

    public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function speakerProfile()
    {
        return $this->hasOne(SpeakerProfile::class);
    }

    public function notificationPreferences()
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function securityEvents()
    {
        return $this->hasMany(SecurityEvent::class);
    }

    public function dataAccessLogs()
    {
        return $this->hasMany(DataAccessLog::class);
    }

    public function sessionSecurity()
    {
        return $this->hasMany(SessionSecurity::class);
    }

    // Social relationships
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function socialProfiles()
    {
        return $this->hasMany(SocialProfile::class);
    }

    public function circles()
    {
        return $this->belongsToMany(Circle::class, 'circle_memberships')
            ->withPivot('joined_at', 'status')
            ->withTimestamps();
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_memberships')
            ->withPivot('role', 'joined_at', 'status')
            ->withTimestamps();
    }

    public function connections()
    {
        return $this->belongsToMany(User::class, 'alumni_connections', 'requester_id', 'recipient_id')
            ->withPivot('status', 'message', 'connected_at')
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
    }

    public function sentConnectionRequests()
    {
        return $this->hasMany(Connection::class, 'user_id');
    }

    public function receivedConnectionRequests()
    {
        return $this->hasMany(Connection::class, 'connected_user_id');
    }

    public function postEngagements()
    {
        return $this->hasMany(PostEngagement::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function educations()
    {
        return $this->hasMany(EducationHistory::class, 'graduate_id');
    }

    public function careerTimeline()
    {
        return $this->hasMany(CareerTimeline::class)->ordered();
    }

    public function careerMilestones()
    {
        return $this->hasMany(CareerMilestone::class)->ordered();
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot(['earned_at', 'metadata', 'is_featured', 'is_notified'])
            ->withTimestamps();
    }

    public function userAchievements()
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function featuredAchievements()
    {
        return $this->userAchievements()->where('is_featured', true);
    }

    public function currentPosition()
    {
        return $this->hasOne(CareerTimeline::class)->where('is_current', true);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('is_suspended', false);
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('is_suspended', true);
    }

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        });
    }

    public function scopeByInstitution(Builder $query, $institutionId): Builder
    {
        return $query->where('institution_id', $institutionId);
    }

    // Helper methods for user types
    public function isStudent(): bool
    {
        return $this->hasRole('Student') && $this->studentProfile !== null;
    }

    public function isAlumni(): bool
    {
        return $this->hasRole('Graduate') && $this->graduate !== null;
    }

    public function isEmployer(): bool
    {
        return $this->hasRole('Employer') && $this->employer !== null;
    }

    public function getUserType(): string
    {
        if ($this->isStudent()) {
            return 'student';
        }
        if ($this->isAlumni()) {
            return 'alumni';
        }
        if ($this->isEmployer()) {
            return 'employer';
        }
        if ($this->hasRole('Institution Admin')) {
            return 'admin';
        }
        if ($this->hasRole('Super Admin')) {
            return 'super_admin';
        }

        return 'user';
    }

    public function scopeRecentlyActive(Builder $query, int $days = 30): Builder
    {
        return $query->where('last_activity_at', '>=', now()->subDays($days));
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

    // Accessors & Mutators
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && ! $this->is_suspended;
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/'.$this->avatar);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&color=7F9CF5&background=EBF4FF';
    }

    public function getLastSeenAttribute(): string
    {
        if (! $this->last_activity_at) {
            return 'Never';
        }

        return $this->last_activity_at->diffForHumans();
    }

    public function getStatusBadgeAttribute(): array
    {
        if ($this->is_suspended) {
            return ['text' => 'Suspended', 'color' => 'red'];
        }

        return match ($this->status) {
            'active' => ['text' => 'Active', 'color' => 'green'],
            'inactive' => ['text' => 'Inactive', 'color' => 'gray'],
            'pending' => ['text' => 'Pending', 'color' => 'yellow'],
            default => ['text' => 'Unknown', 'color' => 'gray'],
        };
    }

    // Methods
    public function suspend(?string $reason = null, $suspendedBy = null): void
    {
        $this->update([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);

        // Log the suspension
        $this->activityLogs()->create([
            'action' => 'user_suspended',
            'description' => "User suspended. Reason: {$reason}",
            'performed_by' => $suspendedBy,
            'metadata' => [
                'reason' => $reason,
                'suspended_by' => $suspendedBy,
            ],
        ]);
    }

    public function unsuspend($unsuspendedBy = null): void
    {
        $this->update([
            'is_suspended' => false,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        // Log the unsuspension
        $this->activityLogs()->create([
            'action' => 'user_unsuspended',
            'description' => 'User suspension lifted',
            'performed_by' => $unsuspendedBy,
            'metadata' => [
                'unsuspended_by' => $unsuspendedBy,
            ],
        ]);
    }

    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public function hasSpecificRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function getPrimaryRole(): ?string
    {
        $role = $this->roles()->first();

        return $role ? $role->name : null;
    }

    public function canAccessInstitution($institutionId): bool
    {
        // Super admins can access all institutions
        if ($this->hasSpecificRole('super-admin')) {
            return true;
        }

        // Users can only access their own institution
        return $this->institution_id == $institutionId;
    }

    public function getPermissionsForInstitution($institutionId): array
    {
        if (! $this->canAccessInstitution($institutionId)) {
            return [];
        }

        return $this->getAllPermissions()->pluck('name')->toArray();
    }

    public function getDashboardRoute(): string
    {
        $role = $this->getPrimaryRole();

        return match ($role) {
            'super-admin' => route('super-admin.dashboard'),
            'institution-admin' => route('institution-admin.dashboard'),
            'employer' => route('employer.dashboard'),
            'graduate' => route('graduate.dashboard'),
            default => route('dashboard'),
        };
    }

    public function getProfileCompletionPercentage(): int
    {
        $requiredFields = ['name', 'email', 'phone'];
        $completedFields = 0;

        foreach ($requiredFields as $field) {
            if (! empty($this->$field)) {
                $completedFields++;
            }
        }

        // Check profile data
        if (! empty($this->profile_data)) {
            $profileFields = ['bio', 'location', 'website'];
            foreach ($profileFields as $field) {
                if (! empty($this->profile_data[$field] ?? null)) {
                    $completedFields++;
                }
            }
            $requiredFields = array_merge($requiredFields, $profileFields);
        }

        return round(($completedFields / count($requiredFields)) * 100);
    }

    public function generateApiToken(): string
    {
        return $this->createToken('api-token')->plainTextToken;
    }

    public function revokeAllTokens(): void
    {
        $this->tokens()->delete();
    }

    public function getActivitySummary(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return [
            'total_logins' => $this->activityLogs()
                ->where('action', 'user_login')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'last_login' => $this->last_login_at?->format('Y-m-d H:i:s'),
            'total_activities' => $this->activityLogs()
                ->where('created_at', '>=', $startDate)
                ->count(),
            'most_active_day' => $this->activityLogs()
                ->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('count', 'desc')
                ->first()?->date,
        ];
    }

    // Social methods
    public function getConnectionStatus(User $otherUser): string
    {
        if ($this->id === $otherUser->id) {
            return 'self';
        }

        $connection = $this->sentConnectionRequests()
            ->where('connected_user_id', $otherUser->id)
            ->first();

        if ($connection) {
            return $connection->status;
        }

        $receivedConnection = $this->receivedConnectionRequests()
            ->where('user_id', $otherUser->id)
            ->first();

        if ($receivedConnection) {
            return $receivedConnection->status === 'pending' ? 'received_request' : $receivedConnection->status;
        }

        return 'none';
    }

    public function sendConnectionRequest(User $otherUser, ?string $message = null): Connection
    {
        return Connection::create([
            'user_id' => $this->id,
            'connected_user_id' => $otherUser->id,
            'message' => $message,
            'status' => 'pending',
        ]);
    }

    public function acceptConnectionRequest(int $connectionId): bool
    {
        $connection = $this->receivedConnectionRequests()
            ->where('id', $connectionId)
            ->where('status', 'pending')
            ->first();

        return $connection ? $connection->accept() : false;
    }

    public function getAcceptedConnections()
    {
        return $this->connections()->wherePivot('status', 'accepted');
    }

    public function getPendingConnectionRequests()
    {
        return $this->receivedConnectionRequests()->where('status', 'pending');
    }

    public function getMutualConnections(User $otherUser)
    {
        return Connection::mutualConnections($this, $otherUser);
    }

    public function isConnectedTo(User $otherUser): bool
    {
        return $this->getConnectionStatus($otherUser) === 'accepted';
    }

    public function getSharedCircles(User $otherUser)
    {
        $myCircleIds = $this->circles()->pluck('circles.id');
        $theirCircleIds = $otherUser->circles()->pluck('circles.id');

        $sharedCircleIds = $myCircleIds->intersect($theirCircleIds);

        return Circle::whereIn('id', $sharedCircleIds)->get();
    }

    public function getSharedGroups(User $otherUser)
    {
        $myGroupIds = $this->groups()->pluck('groups.id');
        $theirGroupIds = $otherUser->groups()->pluck('groups.id');

        $sharedGroupIds = $myGroupIds->intersect($theirGroupIds);

        return Group::whereIn('id', $sharedGroupIds)->get();
    }

    // Mentorship relationships
    public function mentorProfile()
    {
        return $this->hasOne(MentorProfile::class);
    }

    public function mentorRequests()
    {
        return $this->hasMany(MentorshipRequest::class, 'mentor_id');
    }

    public function menteeRequests()
    {
        return $this->hasMany(MentorshipRequest::class, 'mentee_id');
    }

    public function activeMentorships()
    {
        return $this->menteeRequests()->where('status', 'accepted');
    }

    public function activeMentees()
    {
        return $this->mentorRequests()->where('status', 'accepted');
    }

    public function isMentor(): bool
    {
        return $this->mentorProfile()->exists() && $this->mentorProfile->is_active;
    }

    public function canBeMentor(): bool
    {
        // Basic criteria for becoming a mentor
        $hasExperience = $this->careerTimeline()->count() > 0;
        $hasEducation = $this->educations()->count() > 0;

        return $hasExperience && $hasEducation;
    }
}
