<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReunionPhotoLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'reunion_photo_id',
        'user_id',
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
}
