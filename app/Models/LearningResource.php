<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'url',
        'skill_ids',
        'created_by',
        'rating',
        'rating_count',
    ];

    protected $casts = [
        'skill_ids' => 'array',
        'rating' => 'decimal:2',
        'rating_count' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'skill_ids', 'id', 'id');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySkill($query, $skillId)
    {
        return $query->whereJsonContains('skill_ids', $skillId);
    }

    public function scopeHighRated($query, $minRating = 4.0)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('rating_count', 'desc')->limit($limit);
    }

    public function getSkillsAttribute()
    {
        if (empty($this->skill_ids)) {
            return collect();
        }

        return Skill::whereIn('id', $this->skill_ids)->get();
    }

    public function addRating($rating)
    {
        $totalRating = ($this->rating * $this->rating_count) + $rating;
        $this->rating_count++;
        $this->rating = $totalRating / $this->rating_count;
        $this->save();
    }
}
