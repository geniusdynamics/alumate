<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReunionPhotoComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reunion_photo_id',
        'user_id',
        'comment',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    // Relationships
    public function photo(): BelongsTo
    {
        return $this->belongsTo(ReunionPhoto::class, 'reunion_photo_id');
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
               $this->photo->event->canUserEdit($user);
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->canBeEditedBy($user);
    }
}