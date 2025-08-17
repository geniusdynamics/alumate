<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'rsvp_date',
        'checked_in_at'
    ];

    protected $casts = [
        'rsvp_date' => 'datetime',
        'checked_in_at' => 'datetime'
    ];

    /**
     * The event
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * The attendee user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for attending status
     */
    public function scopeAttending($query)
    {
        return $query->where('status', 'attending');
    }

    /**
     * Scope for maybe status
     */
    public function scopeMaybe($query)
    {
        return $query->where('status', 'maybe');
    }
}