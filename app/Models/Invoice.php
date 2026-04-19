<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_OPEN = 'open';

    public const STATUS_PAID = 'paid';

    public const STATUS_UNCOLLECTIBLE = 'uncollectible';

    public const STATUS_VOID = 'void';

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'stripe_invoice_id',
        'stripe_charge_id',
        'amount_due',
        'amount_paid',
        'amount_remaining',
        'currency',
        'status',
        'description',
        'pdf_url',
        'hosted_invoice_url',
        'invoice_number',
        'billing_reason',
        'period_start',
        'period_end',
        'due_date',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_remaining' => 'decimal:2',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns this invoice.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the subscription for this invoice.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Scope to paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope to unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_DRAFT]);
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is open/unpaid.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmount(): string
    {
        return '$'.number_format($this->amount_due, 2);
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'green',
            self::STATUS_OPEN => 'yellow',
            self::STATUS_UNCOLLECTIBLE => 'red',
            self::STATUS_VOID => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Paid',
            self::STATUS_OPEN => 'Unpaid',
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_UNCOLLECTIBLE => 'Uncollectible',
            self::STATUS_VOID => 'Void',
            default => ucfirst($this->status),
        };
    }
}
