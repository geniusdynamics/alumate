<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'recommender_id',
        'recommended_id',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function recommender()
    {
        return $this->belongsTo(User::class, 'recommender_id');
    }

    public function recommended()
    {
        return $this->belongsTo(User::class, 'recommended_id');
    }
}
