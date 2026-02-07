<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlumniVerification extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    public const METHOD_MANUAL = 'manual';
    public const METHOD_EMAIL_DOMAIN = 'email_domain';
    public const METHOD_BULK_IMPORT = 'bulk_import';
    public const METHOD_AUTO = 'auto';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'institution_id',
        'status',
        'verification_method',
        'graduation_year',
        'student_id',
        'degree',
        'major',
        'email_domain',
        'supporting_documents',
        'notes',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'submitted_at',
        'expires_at',
    ];

    protected $casts = [
        'graduation_year' => 'integer',
        'supporting_documents' => 'array',
        'reviewed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns this verification request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tenant for this verification.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the institution for this verification.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the admin who reviewed this verification.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope to pending verifications.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to approved verifications.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to rejected verifications.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Check if verification is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if verification is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if verification is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if verification has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Approve this verification.
     */
    public function approve(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'notes' => $notes,
        ]);

        // Update user verification status
        $this->user->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    /**
     * Reject this verification.
     */
    public function reject(int $reviewerId, string $reason, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'notes' => $notes,
        ]);

        // Update user verification status
        $this->user->update([
            'verification_status' => 'rejected',
        ]);
    }

    /**
     * Get status badge color.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'green',
            self::STATUS_PENDING => 'yellow',
            self::STATUS_REJECTED => 'red',
            self::STATUS_EXPIRED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Verified',
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_EXPIRED => 'Expired',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get verification method label.
     */
    public function getMethodLabel(): string
    {
        return match ($this->verification_method) {
            self::METHOD_MANUAL => 'Manual Review',
            self::METHOD_EMAIL_DOMAIN => 'Email Domain',
            self::METHOD_BULK_IMPORT => 'Bulk Import',
            self::METHOD_AUTO => 'Automatic',
            default => ucfirst(str_replace('_', ' ', $this->verification_method)),
        };
    }

    /**
     * Get documents as array of URLs.
     */
    public function getDocumentUrls(): array
    {
        if (!$this->supporting_documents) {
            return [];
        }

        return array_map(function ($path) {
            return [
                'path' => $path,
                'url' => asset('storage/' . $path),
                'name' => basename($path),
            ];
        }, $this->supporting_documents);
    }
}
