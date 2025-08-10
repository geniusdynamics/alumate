<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageContentApproval extends Model
{
    protected $fillable = [
        'homepage_content_id',
        'requested_by',
        'reviewer_id',
        'status',
        'request_notes',
        'review_notes',
        'requested_at',
        'reviewed_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the homepage content this approval belongs to
     */
    public function homepageContent(): BelongsTo
    {
        return $this->belongsTo(HomepageContent::class);
    }

    /**
     * Get the user who requested approval
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who reviewed the approval
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Scope for pending approvals
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
