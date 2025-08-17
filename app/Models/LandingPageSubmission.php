<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPageSubmission extends Model
{
    protected $fillable = [
        'landing_page_id',
        'lead_id',
        'form_name',
        'form_data',
        'utm_data',
        'session_data',
        'ip_address',
        'user_agent',
        'referrer',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'form_data' => 'array',
        'utm_data' => 'array',
        'session_data' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the landing page this submission belongs to
     */
    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    /**
     * Get the lead associated with this submission
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Mark submission as processed
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark submission as converted
     */
    public function markAsConverted(): void
    {
        $this->update([
            'status' => 'converted',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark submission as spam
     */
    public function markAsSpam(): void
    {
        $this->update([
            'status' => 'spam',
            'processed_at' => now(),
        ]);
    }

    /**
     * Get form field value
     */
    public function getFormField(string $field, $default = null)
    {
        return $this->form_data[$field] ?? $default;
    }

    /**
     * Get UTM parameter
     */
    public function getUtmParameter(string $parameter, $default = null)
    {
        return $this->utm_data[$parameter] ?? $default;
    }

    /**
     * Scope for new submissions
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope for processed submissions
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Scope for converted submissions
     */
    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    /**
     * Scope for submissions by landing page
     */
    public function scopeForLandingPage($query, int $landingPageId)
    {
        return $query->where('landing_page_id', $landingPageId);
    }
}
