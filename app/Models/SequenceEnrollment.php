<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SequenceEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sequence_id',
        'lead_id',
        'current_step',
        'status',
        'enrolled_at',
        'completed_at',
    ];

    protected $casts = [
        'current_step' => 'integer',
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'current_step' => 0,
        'status' => 'active',
    ];

    /**
     * Enrollment statuses
     */
    public const STATUSES = [
        'active',
        'completed',
        'paused',
        'unsubscribed',
    ];

    /**
     * Get the sequence this enrollment belongs to
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(EmailSequence::class, 'sequence_id');
    }

    /**
     * Get the lead this enrollment belongs to
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    /**
     * Get the email sends for this enrollment
     */
    public function emailSends(): HasMany
    {
        return $this->hasMany(EmailSend::class, 'enrollment_id');
    }

    /**
     * Scope query to active enrollments
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope query to completed enrollments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope query to paused enrollments
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    /**
     * Scope query to unsubscribed enrollments
     */
    public function scopeUnsubscribed($query)
    {
        return $query->where('status', 'unsubscribed');
    }

    /**
     * Scope query by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get enrollment statistics
     */
    public function getEnrollmentStats(): array
    {
        $sends = $this->emailSends();

        return [
            'total_sends' => $sends->count(),
            'sent_count' => $sends->where('status', 'sent')->count(),
            'delivered_count' => $sends->where('status', 'delivered')->count(),
            'opened_count' => $sends->whereNotNull('opened_at')->count(),
            'clicked_count' => $sends->whereNotNull('clicked_at')->count(),
            'bounced_count' => $sends->where('status', 'bounced')->count(),
            'unsubscribed_count' => $sends->whereNotNull('unsubscribed_at')->count(),
        ];
    }

    /**
     * Check if enrollment is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if enrollment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if enrollment is paused
     */
    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    /**
     * Check if enrollment is unsubscribed
     */
    public function isUnsubscribed(): bool
    {
        return $this->status === 'unsubscribed';
    }

    /**
     * Mark enrollment as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark enrollment as paused
     */
    public function markAsPaused(): void
    {
        $this->update(['status' => 'paused']);
    }

    /**
     * Mark enrollment as unsubscribed
     */
    public function markAsUnsubscribed(): void
    {
        $this->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);
    }

    /**
     * Advance to next step
     */
    public function advanceStep(): void
    {
        $this->increment('current_step');
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'sequence_id' => 'required|exists:email_sequences,id',
            'lead_id' => 'required|exists:leads,id',
            'current_step' => 'integer|min:0',
            'status' => ['required', 'string', Rule::in(self::STATUSES)],
            'enrolled_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
        ];
    }

    /**
     * Get unique validation rules (for updates)
     */
    public static function getUniqueValidationRules(?int $ignoreId = null): array
    {
        $rules = self::getValidationRules();

        if ($ignoreId) {
            $rules['lead_id'] = 'required|exists:leads,id|unique:sequence_enrollments,lead_id,' . $ignoreId . ',id,sequence_id,' . request('sequence_id');
        } else {
            $rules['lead_id'] = 'required|exists:leads,id|unique:sequence_enrollments,lead_id,NULL,id,sequence_id,' . request('sequence_id');
        }

        return $rules;
    }
}