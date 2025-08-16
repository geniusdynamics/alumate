<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'filename',
        'file_path',
        'total_rows',
        'processed_rows',
        'created_count',
        'updated_count',
        'skipped_count',
        'valid_rows',
        'invalid_rows',
        'conflicts',
        'status',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'valid_rows' => 'array',
        'invalid_rows' => 'array',
        'conflicts' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getSuccessRateAttribute()
    {
        if ($this->total_rows == 0) {
            return 0;
        }

        return round(($this->created_count + $this->updated_count) / $this->total_rows * 100, 2);
    }

    public function getErrorRateAttribute()
    {
        if ($this->total_rows == 0) {
            return 0;
        }

        return round($this->skipped_count / $this->total_rows * 100, 2);
    }

    public function canRollback()
    {
        return $this->status === 'completed' &&
               $this->created_count > 0 &&
               $this->created_at->diffInHours(now()) <= 24; // Allow rollback within 24 hours
    }
}
