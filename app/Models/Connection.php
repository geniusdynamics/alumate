<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    use HasFactory;

    protected $table = 'alumni_connections';

    protected $fillable = [
        'requester_id',
        'recipient_id',
        'status',
        'message',
        'connected_at'
    ];

    protected $casts = [
        'connected_at' => 'datetime'
    ];

    /**
     * The user who initiated the connection
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * The user who received the connection request
     */
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Scope for accepted connections
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope for pending connections
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}