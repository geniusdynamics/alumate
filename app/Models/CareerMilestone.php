<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CareerMilestone extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_PROMOTION = 'promotion';

    const TYPE_JOB_CHANGE = 'job_change';

    const TYPE_AWARD = 'award';

    const TYPE_CERTIFICATION = 'certification';

    const TYPE_EDUCATION = 'education';

    const TYPE_ACHIEVEMENT = 'achievement';

    const VISIBILITY_PUBLIC = 'public';

    const VISIBILITY_CONNECTIONS = 'connections';

    const VISIBILITY_PRIVATE = 'private';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'date',
        'visibility',
        'company',
        'organization',
        'metadata',
        'is_featured',
    ];

    protected $casts = [
        'date' => 'date',
        'metadata' => 'array',
        'is_featured' => 'boolean',
    ];

    protected $dates = [
        'date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the user that owns the milestone
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all available milestone types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_PROMOTION => 'Promotion',
            self::TYPE_JOB_CHANGE => 'Job Change',
            self::TYPE_AWARD => 'Award',
            self::TYPE_CERTIFICATION => 'Certification',
            self::TYPE_EDUCATION => 'Education',
            self::TYPE_ACHIEVEMENT => 'Achievement',
        ];
    }

    /**
     * Get all available visibility options
     */
    public static function getVisibilityOptions(): array
    {
        return [
            self::VISIBILITY_PUBLIC => 'Public',
            self::VISIBILITY_CONNECTIONS => 'Connections Only',
            self::VISIBILITY_PRIVATE => 'Private',
        ];
    }

    /**
     * Check if milestone is visible to a specific user
     */
    public function isVisibleTo(?User $viewer): bool
    {
        if (! $viewer) {
            return $this->visibility === self::VISIBILITY_PUBLIC;
        }

        if ($viewer->id === $this->user_id) {
            return true;
        }

        switch ($this->visibility) {
            case self::VISIBILITY_PUBLIC:
                return true;
            case self::VISIBILITY_CONNECTIONS:
                return $this->user->isConnectedTo($viewer);
            case self::VISIBILITY_PRIVATE:
                return false;
            default:
                return false;
        }
    }

    /**
     * Scope to get milestones visible to a specific user
     */
    public function scopeVisibleTo($query, ?User $viewer)
    {
        if (! $viewer) {
            return $query->where('visibility', self::VISIBILITY_PUBLIC);
        }

        return $query->where(function ($q) use ($viewer) {
            $q->where('user_id', $viewer->id)
                ->orWhere('visibility', self::VISIBILITY_PUBLIC)
                ->orWhere(function ($subQ) use ($viewer) {
                    $subQ->where('visibility', self::VISIBILITY_CONNECTIONS)
                        ->whereHas('user.connections', function ($connQ) use ($viewer) {
                            $connQ->where('connected_user_id', $viewer->id)
                                ->where('status', 'accepted');
                        });
                });
        });
    }

    /**
     * Scope to get featured milestones
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get milestones by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get milestones ordered by date
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('date', 'desc');
    }

    /**
     * Get the icon for this milestone type
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PROMOTION => 'trending-up',
            self::TYPE_JOB_CHANGE => 'briefcase',
            self::TYPE_AWARD => 'award',
            self::TYPE_CERTIFICATION => 'certificate',
            self::TYPE_EDUCATION => 'academic-cap',
            self::TYPE_ACHIEVEMENT => 'star',
            default => 'flag'
        };
    }
}
