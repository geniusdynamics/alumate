<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumPostLike extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'type',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($like) {
            $like->post->increment('likes_count');
        });

        static::deleted(function ($like) {
            $like->post->decrement('likes_count');
        });
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(ForumPost::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
