<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmailSend extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'sequence_email_id',
        'lead_id',
        'subject',
        'status',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    /**
     * Email send statuses
     */
    public const STATUSES = [
        'queued',
        'sent',
        'delivered',
        'bounced',
        'failed',
    ];

    /**
     * Get the enrollment this send belongs to
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(SequenceEnrollment::class, 'enrollment_id');
    }

    /**
     * Get the sequence email this send belongs to
     */
    public function sequenceEmail(): BelongsTo
    {
        return $this->belongsTo(SequenceEmail::class, 'sequence_email_id');
    }

    /**
     * Get the lead this send belongs to
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    /**
     * Scope query to queued sends
     */
    public function scopeQueued($query)
    {
        return $query->where('status', 'queued');
    }

    /**
     * Scope query to sent sends
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope query to delivered sends
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope query to bounced sends
     */
    public function scopeBounced($query)
    {
        return $query->where('status', 'bounced');
    }

    /**
     * Scope query to failed sends
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope query by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope query to opened emails
     */
    public function scopeOpened($query)
    {
        return $query->whereNotNull('opened_at');
    }

    /**
     * Scope query to clicked emails
     */
    public function scopeClicked($query)
    {
        return $query->whereNotNull('clicked_at');
    }

    /**
     * Scope query to unsubscribed emails
     */
    public function scopeUnsubscribed($query)
    {
        return $query->whereNotNull('unsubscribed_at');
    }

    /**
     * Check if email was opened
     */
    public function isOpened(): bool
    {
        return !is_null($this->opened_at);
    }

    /**
     * Check if email was clicked
     */
    public function isClicked(): bool
    {
        return !is_null($this->clicked_at);
    }

    /**
     * Check if email was unsubscribed
     */
    public function isUnsubscribed(): bool
    {
        return !is_null($this->unsubscribed_at);
    }

    /**
     * Mark email as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark email as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark email as opened
     */
    public function markAsOpened(): void
    {
        if (!$this->isOpened()) {
            $this->update(['opened_at' => now()]);
        }
    }

    /**
     * Mark email as clicked
     */
    public function markAsClicked(): void
    {
        if (!$this->isClicked()) {
            $this->update(['clicked_at' => now()]);
        }
    }

    /**
     * Mark email as unsubscribed
     */
    public function markAsUnsubscribed(): void
    {
        if (!$this->isUnsubscribed()) {
            $this->update(['unsubscribed_at' => now()]);
        }
    }

    /**
     * Mark email as bounced
     */
    public function markAsBounced(): void
    {
        $this->update(['status' => 'bounced']);
    }

    /**
     * Mark email as failed
     */
    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    /**
     * Get time to open in minutes
     */
    public function getTimeToOpen(): ?int
    {
        if (!$this->sent_at || !$this->opened_at) {
            return null;
        }

        return $this->sent_at->diffInMinutes($this->opened_at);
    }

    /**
     * Get time to click in minutes
     */
    public function getTimeToClick(): ?int
    {
        if (!$this->sent_at || !$this->clicked_at) {
            return null;
        }

        return $this->sent_at->diffInMinutes($this->clicked_at);
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'enrollment_id' => 'required|exists:sequence_enrollments,id',
            'sequence_email_id' => 'required|exists:sequence_emails,id',
            'lead_id' => 'required|exists:leads,id',
            'subject' => 'required|string|max:255',
            'status' => ['required', 'string', Rule::in(self::STATUSES)],
            'sent_at' => 'nullable|date',
            'delivered_at' => 'nullable|date',
            'opened_at' => 'nullable|date',
            'clicked_at' => 'nullable|date',
            'unsubscribed_at' => 'nullable|date',
        ];
    }
}