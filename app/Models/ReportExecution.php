<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'custom_report_id',
        'user_id',
        'status',
        'parameters',
        'result_data',
        'file_path',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'result_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function customReport(): BelongsTo
    {
        return $this->belongsTo(CustomReport::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    // Helper methods
    public function markAsStarted()
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted($resultData = null, $filePath = null)
    {
        $this->update([
            'status' => 'completed',
            'result_data' => $resultData,
            'file_path' => $filePath,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    public function getDuration()
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    public function getFormattedDuration()
    {
        $duration = $this->getDuration();
        
        if ($duration === null) {
            return 'N/A';
        }

        if ($duration < 60) {
            return $duration . 's';
        }

        $minutes = floor($duration / 60);
        $seconds = $duration % 60;

        return $minutes . 'm ' . $seconds . 's';
    }

    public function getStatusColor()
    {
        return match($this->status) {
            'completed' => 'green',
            'failed' => 'red',
            'processing' => 'yellow',
            'pending' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusIcon()
    {
        return match($this->status) {
            'completed' => 'check-circle',
            'failed' => 'x-circle',
            'processing' => 'clock',
            'pending' => 'clock',
            default => 'clock',
        };
    }

    public function hasFile()
    {
        return !empty($this->file_path) && \Storage::exists($this->file_path);
    }

    public function getFileSize()
    {
        if (!$this->hasFile()) {
            return 0;
        }

        return \Storage::size($this->file_path);
    }

    public function getFormattedFileSize()
    {
        $size = $this->getFileSize();
        
        if ($size === 0) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function getDownloadUrl()
    {
        if (!$this->hasFile()) {
            return null;
        }

        return route('analytics.reports.download', $this->id);
    }

    public function getResultSummary()
    {
        if (!$this->result_data) {
            return null;
        }

        return [
            'total_records' => $this->result_data['total_records'] ?? 0,
            'filters_applied' => count($this->result_data['filters_applied'] ?? []),
            'generated_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'file_size' => $this->getFormattedFileSize(),
        ];
    }

    public function canBeDownloaded()
    {
        return $this->isCompleted() && $this->hasFile();
    }

    public function canBeRetried()
    {
        return $this->isFailed();
    }

    public function shouldExpire()
    {
        if (!$this->isCompleted()) {
            return false;
        }

        $expirationDays = config('analytics.report_expiration_days', 30);
        
        return $this->completed_at->addDays($expirationDays)->isPast();
    }

    public function cleanup()
    {
        if ($this->hasFile()) {
            \Storage::delete($this->file_path);
        }

        $this->delete();
    }
}