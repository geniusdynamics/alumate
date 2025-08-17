<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'skill_id',
        'proficiency_level',
        'years_experience',
        'endorsed_count',
    ];

    protected $casts = [
        'years_experience' => 'integer',
        'endorsed_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function endorsements(): HasMany
    {
        return $this->hasMany(SkillEndorsement::class);
    }

    public function scopeByProficiency($query, $level)
    {
        return $query->where('proficiency_level', $level);
    }

    public function scopeByExperience($query, $minYears)
    {
        return $query->where('years_experience', '>=', $minYears);
    }

    public function scopeMostEndorsed($query, $limit = 10)
    {
        return $query->orderBy('endorsed_count', 'desc')->limit($limit);
    }

    public function getProficiencyLevelAttribute($value)
    {
        return $value;
    }

    public function getExperienceLevelAttribute()
    {
        if ($this->years_experience < 1) {
            return 'Entry Level';
        }
        if ($this->years_experience < 3) {
            return 'Junior';
        }
        if ($this->years_experience < 5) {
            return 'Mid Level';
        }
        if ($this->years_experience < 8) {
            return 'Senior';
        }

        return 'Expert';
    }
}
