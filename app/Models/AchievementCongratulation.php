<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AchievementCongratulation extends Model
{
    use HasFactory;

    protected $fillable = [
        'achievement_celebration_id',
        'user_id',
        'message',
    ];

    /**
     * Get the celebration being congratulated
     */
    public function celebration(): BelongsTo
    {
        return $this->belongsTo(AchievementCelebration::class, 'achievement_celebration_id');
    }

    /**
     * Get the user who congratulated
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
