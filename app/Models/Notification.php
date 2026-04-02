<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'action_url',
        'action_text',
        'is_read',
        'read_at',
        'sent_via_email',
        'email_sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'sent_via_email' => 'boolean',
        'read_at' => 'datetime',
        'email_sent_at' => 'datetime',
    ];

    protected $hidden = [
        'updated_at',
    ];

    public const TYPE_CONNECTION_REQUEST = 'connection_request';

    public const TYPE_CONNECTION_ACCEPTED = 'connection_accepted';

    public const TYPE_SKILL_ENDORSEMENT = 'skill_endorsement';

    public const TYPE_REFERRAL = 'referral';

    public const TYPE_MESSAGE = 'message';

    public const TYPE_JOB_APPLICATION = 'job_application';

    public const TYPE_EVENT_INVITATION = 'event_invitation';

    public const TYPE_SYSTEM = 'system';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function markAsUnread(): void
    {
        if ($this->is_read) {
            $this->update([
                'is_read' => false,
                'read_at' => null,
            ]);
        }
    }

    public function markAsEmailSent(): void
    {
        $this->update([
            'sent_via_email' => true,
            'email_sent_at' => now(),
        ]);
    }

    public function getIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_CONNECTION_REQUEST => 'UserPlusIcon',
            self::TYPE_CONNECTION_ACCEPTED => 'UserCheckIcon',
            self::TYPE_SKILL_ENDORSEMENT => 'StarIcon',
            self::TYPE_REFERRAL => 'ShareIcon',
            self::TYPE_MESSAGE => 'EnvelopeIcon',
            self::TYPE_JOB_APPLICATION => 'BriefcaseIcon',
            self::TYPE_EVENT_INVITATION => 'CalendarIcon',
            default => 'BellIcon',
        };
    }

    public function getColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_CONNECTION_REQUEST => 'blue',
            self::TYPE_CONNECTION_ACCEPTED => 'green',
            self::TYPE_SKILL_ENDORSEMENT => 'yellow',
            self::TYPE_REFERRAL => 'purple',
            self::TYPE_MESSAGE => 'indigo',
            self::TYPE_JOB_APPLICATION => 'pink',
            self::TYPE_EVENT_INVITATION => 'orange',
            default => 'gray',
        };
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
