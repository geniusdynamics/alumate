<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomepageContent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'section',
        'audience',
        'key',
        'value',
        'metadata',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'published_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /**
     * Get the user who created this content
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this content
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get all versions of this content
     */
    public function versions(): HasMany
    {
        return $this->hasMany(HomepageContentVersion::class)->orderBy('version_number', 'desc');
    }

    /**
     * Get all approval requests for this content
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(HomepageContentApproval::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the latest approval request
     */
    public function latestApproval(): HasMany
    {
        return $this->hasMany(HomepageContentApproval::class)->latest();
    }

    /**
     * Create a new version when content is updated
     */
    public function createVersion(string $changeNotes = null): HomepageContentVersion
    {
        $latestVersion = $this->versions()->first();
        $versionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;

        return $this->versions()->create([
            'version_number' => $versionNumber,
            'value' => $this->value,
            'metadata' => $this->metadata,
            'change_notes' => $changeNotes,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Request approval for this content
     */
    public function requestApproval(string $notes = null): HomepageContentApproval
    {
        return $this->approvals()->create([
            'requested_by' => auth()->id(),
            'request_notes' => $notes,
            'requested_at' => now(),
            'status' => 'pending',
        ]);
    }

    /**
     * Approve this content
     */
    public function approve(int $reviewerId, string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $reviewerId,
            'approved_at' => now(),
        ]);

        $this->latestApproval()->update([
            'status' => 'approved',
            'reviewer_id' => $reviewerId,
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Publish this content
     */
    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Scope for published content
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for specific audience
     */
    public function scopeForAudience($query, string $audience)
    {
        return $query->where(function ($q) use ($audience) {
            $q->where('audience', $audience)
              ->orWhere('audience', 'both');
        });
    }

    /**
     * Scope for specific section
     */
    public function scopeForSection($query, string $section)
    {
        return $query->where('section', $section);
    }
}
