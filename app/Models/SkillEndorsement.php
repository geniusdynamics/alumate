<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillEndorsement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_skill_id',
        'endorser_id',
        'message',
    ];

    public function userSkill(): BelongsTo
    {
        return $this->belongsTo(UserSkill::class);
    }

    public function endorser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'endorser_id');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeWithMessage($query)
    {
        return $query->whereNotNull('message');
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($endorsement) {
            $endorsement->userSkill->increment('endorsed_count');
        });

        static::deleted(function ($endorsement) {
            $endorsement->userSkill->decrement('endorsed_count');
        });
    }
}