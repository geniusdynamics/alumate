<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpTicketResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'help_ticket_id',
        'user_id',
        'response',
        'attachments',
        'is_internal',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_internal' => 'boolean',
    ];

    // Relationships
    public function ticket()
    {
        return $this->belongsTo(HelpTicket::class, 'help_ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopeForTicket($query, $ticketId)
    {
        return $query->where('help_ticket_id', $ticketId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper methods
    public function isFromStaff()
    {
        return $this->user->hasAnyRole(['super-admin', 'institution-admin']);
    }

    public function isFromUser()
    {
        return ! $this->isFromStaff();
    }

    public function canBeViewedBy($user)
    {
        // Internal responses can only be viewed by staff
        if ($this->is_internal) {
            return $user->hasAnyRole(['super-admin', 'institution-admin']);
        }

        // Public responses can be viewed by ticket owner and staff
        return $this->ticket->user_id === $user->id ||
               $user->hasAnyRole(['super-admin', 'institution-admin']);
    }
}
