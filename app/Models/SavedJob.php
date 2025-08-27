<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_id',
    ];

    /**
     * The user who saved the job
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The saved job
     */
    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
