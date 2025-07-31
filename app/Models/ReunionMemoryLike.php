<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReunionMemoryLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'reunion_memory_id',
        'user_id',
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
}