<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SequenceEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'sequence_id',
        'template_id',
        'subject_line',
        'delay_hours',
        'send_order',
        'trigger_conditions',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'delay_hours' => 'integer',
        'send_order' => 'integer',
    ];

    protected $attributes = [
        'delay_hours' => 0,
        'send_order' => 0,
        'trigger_conditions' => '{}',
    ];

    /**
     * Get the sequence this email belongs to
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(EmailSequence::class, 'sequence_id');
    }

    /**
     * Get the template used for this email
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    /**
     * Get the email sends for this sequence email
     */
    public function emailSends(): HasMany
    {
        return $this->hasMany(EmailSend::class, 'sequence_email_id');
    }

    /**
     * Get email statistics
     */
    public function getEmailStats(): array
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
     * Get open rate percentage
     */
    public function getOpenRate(): float
    {
        $stats = $this->getEmailStats();
        return $stats['delivered_count'] > 0
            ? round(($stats['opened_count'] / $stats['delivered_count']) * 100, 2)
            : 0.0;
    }

    /**
     * Get click rate percentage
     */
    public function getClickRate(): float
    {
        $stats = $this->getEmailStats();
        return $stats['delivered_count'] > 0
            ? round(($stats['clicked_count'] / $stats['delivered_count']) * 100, 2)
            : 0.0;
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'sequence_id' => 'required|exists:email_sequences,id',
            'template_id' => 'required|exists:templates,id',
            'subject_line' => 'required|string|max:255',
            'delay_hours' => 'integer|min:0',
            'send_order' => 'required|integer|min:0',
            'trigger_conditions' => 'nullable|array',
        ];
    }

    /**
     * Get unique validation rules (for updates)
     */
    public static function getUniqueValidationRules(?int $ignoreId = null): array
    {
        $rules = self::getValidationRules();

        if ($ignoreId) {
            $rules['send_order'] = 'required|integer|min:0|unique:sequence_emails,send_order,' . $ignoreId . ',id,sequence_id,' . request('sequence_id');
        } else {
            $rules['send_order'] = 'required|integer|min:0|unique:sequence_emails,send_order,NULL,id,sequence_id,' . request('sequence_id');
        }

        return $rules;
    }
}