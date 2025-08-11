<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'short_description',
        'media_urls',
        'type',
        'format',
        'start_date',
        'end_date',
        'timezone',
        'venue_name',
        'venue_address',
        'latitude',
        'longitude',
        'virtual_link',
        'virtual_instructions',
        'jitsi_room_id',
        'meeting_url',
        'meeting_platform',
        'meeting_password',
        'meeting_embed_allowed',
        'recording_enabled',
        'jitsi_config',
        'meeting_metadata',
        'waiting_room_enabled',
        'chat_enabled',
        'screen_sharing_enabled',
        'meeting_instructions',
        'max_capacity',
        'current_attendees',
        'requires_approval',
        'ticket_price',
        'registration_status',
        'registration_deadline',
        'organizer_id',
        'institution_id',
        'graduation_year',
        'class_identifier',
        'is_reunion',
        'reunion_year_milestone',
        'reunion_committees',
        'memory_collection_settings',
        'enable_photo_sharing',
        'enable_memory_wall',
        'anniversary_milestones',
        'reunion_theme',
        'class_statistics',
        'visibility',
        'target_circles',
        'target_groups',
        'settings',
        'allow_guests',
        'max_guests_per_attendee',
        'enable_networking',
        'enable_checkin',
        'status',
        'tags',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'target_circles' => 'array',
        'target_groups' => 'array',
        'settings' => 'array',
        'tags' => 'array',
        'jitsi_config' => 'array',
        'meeting_metadata' => 'array',
        'reunion_committees' => 'array',
        'memory_collection_settings' => 'array',
        'anniversary_milestones' => 'array',
        'class_statistics' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_deadline' => 'datetime',
        'requires_approval' => 'boolean',
        'allow_guests' => 'boolean',
        'enable_networking' => 'boolean',
        'enable_checkin' => 'boolean',
        'meeting_embed_allowed' => 'boolean',
        'recording_enabled' => 'boolean',
        'waiting_room_enabled' => 'boolean',
        'chat_enabled' => 'boolean',
        'screen_sharing_enabled' => 'boolean',
        'is_reunion' => 'boolean',
        'enable_photo_sharing' => 'boolean',
        'enable_memory_wall' => 'boolean',
        'ticket_price' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'max_guests_per_attendee' => 'integer',
        'max_capacity' => 'integer',
        'current_attendees' => 'integer',
        'graduation_year' => 'integer',
        'reunion_year_milestone' => 'integer',
    ];

    // Relationships
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(EventCheckIn::class);
    }

    public function reunionPhotos(): HasMany
    {
        return $this->hasMany(ReunionPhoto::class);
    }

    public function reunionMemories(): HasMany
    {
        return $this->hasMany(ReunionMemory::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(EventFeedback::class);
    }

    public function highlights(): HasMany
    {
        return $this->hasMany(EventHighlight::class);
    }

    public function networkingConnections(): HasMany
    {
        return $this->hasMany(EventNetworkingConnection::class);
    }

    public function connectionRecommendations(): HasMany
    {
        return $this->hasMany(EventConnectionRecommendation::class);
    }

    public function followUpActivities(): HasMany
    {
        return $this->hasMany(EventFollowUpActivity::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('end_date', '<', now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByFormat($query, $format)
    {
        return $query->where('format', $format);
    }

    public function scopeReunions($query)
    {
        return $query->where('is_reunion', true);
    }

    public function scopeByGraduationYear($query, $year)
    {
        return $query->where('graduation_year', $year);
    }

    public function scopeByReunionMilestone($query, $milestone)
    {
        return $query->where('reunion_year_milestone', $milestone);
    }

    public function scopeNearLocation($query, $latitude, $longitude, $radius = 50)
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw('*, (
                6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * sin(radians(latitude))
                )
            ) AS distance', [$latitude, $longitude, $latitude])
            ->having('distance', '<', $radius)
            ->orderBy('distance');
    }

    // Helper methods
    public function isUpcoming(): bool
    {
        return $this->start_date->isFuture();
    }

    public function isPast(): bool
    {
        return $this->end_date->isPast();
    }

    public function isOngoing(): bool
    {
        $now = now();

        return $this->start_date->isPast() && $this->end_date->isFuture();
    }

    public function canRegister(): bool
    {
        if ($this->registration_status !== 'open') {
            return false;
        }

        if ($this->registration_deadline && $this->registration_deadline->isPast()) {
            return false;
        }

        if ($this->max_capacity && $this->current_attendees >= $this->max_capacity) {
            return false;
        }

        return true;
    }

    public function hasCapacity(): bool
    {
        if (! $this->max_capacity) {
            return true;
        }

        return $this->current_attendees < $this->max_capacity;
    }

    public function getAvailableSpots(): int
    {
        if (! $this->max_capacity) {
            return PHP_INT_MAX;
        }

        return max(0, $this->max_capacity - $this->current_attendees);
    }

    public function isUserRegistered(User $user): bool
    {
        return $this->registrations()
            ->where('user_id', $user->id)
            ->whereIn('status', ['registered', 'waitlisted'])
            ->exists();
    }

    public function getUserRegistration(User $user): ?EventRegistration
    {
        return $this->registrations()
            ->where('user_id', $user->id)
            ->first();
    }

    public function isUserCheckedIn(User $user): bool
    {
        return $this->checkIns()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function getFormattedDuration(): string
    {
        $duration = $this->start_date->diffInMinutes($this->end_date);

        if ($duration < 60) {
            return $duration.' minutes';
        }

        $hours = floor($duration / 60);
        $minutes = $duration % 60;

        if ($minutes === 0) {
            return $hours.' hour'.($hours > 1 ? 's' : '');
        }

        return $hours.'h '.$minutes.'m';
    }

    public function getLocalStartDate(): Carbon
    {
        return $this->start_date->setTimezone($this->timezone);
    }

    public function getLocalEndDate(): Carbon
    {
        return $this->end_date->setTimezone($this->timezone);
    }

    public function updateAttendeeCount(): void
    {
        $this->current_attendees = $this->registrations()
            ->whereIn('status', ['registered', 'attended'])
            ->sum('guests_count') + $this->registrations()
            ->whereIn('status', ['registered', 'attended'])
            ->count();

        $this->save();
    }

    public function canUserView(User $user): bool
    {
        switch ($this->visibility) {
            case 'public':
                return true;
            case 'alumni_only':
                return $user->hasRole('alumni') || $user->hasRole('admin');
            case 'institution_only':
                return $user->institution_id === $this->institution_id;
            case 'private':
                return $user->id === $this->organizer_id || $user->hasRole('admin');
            default:
                return false;
        }
    }

    public function canUserEdit(User $user): bool
    {
        return $user->id === $this->organizer_id ||
               $user->hasRole('admin') ||
               ($this->institution_id && $user->hasRole('institution_admin') && $user->institution_id === $this->institution_id);
    }

    // Virtual meeting helper methods
    public function isVirtual(): bool
    {
        return in_array($this->format, ['virtual', 'hybrid']);
    }

    public function hasJitsiMeeting(): bool
    {
        return $this->meeting_platform === 'jitsi' && ! empty($this->jitsi_room_id);
    }

    public function getJitsiMeetingUrl(): ?string
    {
        if (! $this->hasJitsiMeeting()) {
            return null;
        }

        $domain = config('services.jitsi.domain', 'meet.jit.si');

        return "https://{$domain}/{$this->jitsi_room_id}";
    }

    public function getMeetingCredentials(): array
    {
        $credentials = [
            'platform' => $this->meeting_platform,
            'url' => $this->meeting_url,
            'password' => $this->meeting_password,
            'instructions' => $this->meeting_instructions,
        ];

        if ($this->hasJitsiMeeting()) {
            $credentials['url'] = $this->getJitsiMeetingUrl();
            $credentials['room_id'] = $this->jitsi_room_id;
            $credentials['embed_allowed'] = $this->meeting_embed_allowed;
        }

        return $credentials;
    }

    public function canEmbedMeeting(): bool
    {
        return $this->meeting_platform === 'jitsi' && $this->meeting_embed_allowed;
    }

    public function getJitsiEmbedUrl(): ?string
    {
        if (! $this->canEmbedMeeting()) {
            return null;
        }

        $domain = config('services.jitsi.domain', 'meet.jit.si');
        $config = $this->jitsi_config ?? [];

        $params = http_build_query(array_merge([
            'config.startWithAudioMuted' => ! ($config['start_with_audio'] ?? true),
            'config.startWithVideoMuted' => ! ($config['start_with_video'] ?? true),
            'config.enableWelcomePage' => false,
            'config.prejoinPageEnabled' => $this->waiting_room_enabled,
            'config.disableDeepLinking' => true,
        ], $config['embed_params'] ?? []));

        return "https://{$domain}/{$this->jitsi_room_id}?{$params}";
    }

    public function generateJitsiRoomId(): string
    {
        if ($this->jitsi_room_id) {
            return $this->jitsi_room_id;
        }

        // Generate unique room ID based on event
        $roomId = 'alumni-'.$this->id.'-'.\Str::slug($this->title, '-');
        $this->jitsi_room_id = $roomId;
        $this->save();

        return $roomId;
    }

    // Reunion-specific helper methods
    public function isReunion(): bool
    {
        return $this->is_reunion;
    }

    public function getReunionYearsSinceGraduation(): ?int
    {
        if (! $this->graduation_year) {
            return null;
        }

        return now()->year - $this->graduation_year;
    }

    public function getClassDisplayName(): string
    {
        if ($this->class_identifier) {
            return $this->class_identifier;
        }

        if ($this->graduation_year) {
            return "Class of {$this->graduation_year}";
        }

        return 'Alumni Class';
    }

    public function getReunionMilestoneDisplay(): ?string
    {
        if (! $this->reunion_year_milestone) {
            return null;
        }

        return "{$this->reunion_year_milestone} Year Reunion";
    }

    public function hasPhotoSharing(): bool
    {
        return $this->enable_photo_sharing;
    }

    public function hasMemoryWall(): bool
    {
        return $this->enable_memory_wall;
    }

    public function getCommitteeMembers(): array
    {
        return $this->reunion_committees ?? [];
    }

    public function getCommitteeMembersByRole(string $role): array
    {
        $committees = $this->getCommitteeMembers();

        return array_filter($committees, function ($member) use ($role) {
            return ($member['role'] ?? '') === $role;
        });
    }

    public function isCommitteeMember(User $user): bool
    {
        $committees = $this->getCommitteeMembers();

        return collect($committees)->contains('user_id', $user->id);
    }

    public function getCommitteeRole(User $user): ?string
    {
        $committees = $this->getCommitteeMembers();
        $member = collect($committees)->firstWhere('user_id', $user->id);

        return $member['role'] ?? null;
    }

    public function addCommitteeMember(User $user, string $role): void
    {
        $committees = $this->getCommitteeMembers();

        // Remove existing entry if present
        $committees = array_filter($committees, function ($member) use ($user) {
            return ($member['user_id'] ?? null) !== $user->id;
        });

        // Add new entry
        $committees[] = [
            'user_id' => $user->id,
            'role' => $role,
            'added_at' => now()->toISOString(),
        ];

        $this->reunion_committees = array_values($committees);
        $this->save();
    }

    public function removeCommitteeMember(User $user): void
    {
        $committees = $this->getCommitteeMembers();
        $committees = array_filter($committees, function ($member) use ($user) {
            return ($member['user_id'] ?? null) !== $user->id;
        });

        $this->reunion_committees = array_values($committees);
        $this->save();
    }

    public function getMemoryCollectionSettings(): array
    {
        return $this->memory_collection_settings ?? [
            'allow_anonymous_submissions' => false,
            'require_approval' => true,
            'allow_photo_uploads' => true,
            'max_photos_per_memory' => 5,
            'memory_types' => ['story', 'achievement', 'memory', 'tribute', 'update'],
        ];
    }

    public function updateClassStatistics(): void
    {
        $registrations = $this->registrations()->whereIn('status', ['registered', 'attended'])->get();

        $statistics = [
            'total_registered' => $registrations->count(),
            'total_attended' => $this->checkIns()->count(),
            'attendance_rate' => $registrations->count() > 0 ?
                round(($this->checkIns()->count() / $registrations->count()) * 100, 2) : 0,
            'photos_shared' => $this->reunionPhotos()->approved()->count(),
            'memories_shared' => $this->reunionMemories()->approved()->count(),
            'updated_at' => now()->toISOString(),
        ];

        // Add demographic breakdowns if available
        $users = $registrations->map->user->filter();
        if ($users->isNotEmpty()) {
            $statistics['demographics'] = [
                'by_location' => $users->groupBy('location')->map->count(),
                'by_industry' => $users->groupBy('industry')->map->count(),
            ];
        }

        $this->class_statistics = $statistics;
        $this->save();
    }

    public function getAttendanceRate(): float
    {
        $stats = $this->class_statistics ?? [];

        return $stats['attendance_rate'] ?? 0.0;
    }

    public function canUserManageReunion(User $user): bool
    {
        return $this->canUserEdit($user) || $this->isCommitteeMember($user);
    }

    public function getAnniversaryMilestones(): array
    {
        return $this->anniversary_milestones ?? [];
    }

    public function addAnniversaryMilestone(string $type, string $title, string $date, array $metadata = []): void
    {
        $milestones = $this->getAnniversaryMilestones();
        $milestones[] = [
            'type' => $type,
            'title' => $title,
            'date' => $date,
            'metadata' => $metadata,
            'created_at' => now()->toISOString(),
        ];

        $this->anniversary_milestones = $milestones;
        $this->save();
    }
}
