<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEngagementMetric extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'posts_created',
        'posts_liked',
        'comments_made',
        'connections_made',
        'profile_views',
        'job_views',
        'event_views',
        'session_duration_minutes',
        'page_views',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
