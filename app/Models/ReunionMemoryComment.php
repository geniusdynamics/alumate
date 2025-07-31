<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReunionMemoryComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reunion_memory_id',
        'user_id',
        'comment',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    // Relationships
    public function memory(): BelongsTo
    {
        return $this->belongsTo(ReunionMemory::class, 'reunion_memory_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    // Helper methods
    public function canBeEditedBy(User $user): bool
    {
        return $user->id === $this->user_id || 
               $user->hasRole('admin') || 
               $this->memory->event->canUserEdit($user);
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->canBeEditedBy($user);
    }
}