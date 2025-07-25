<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HelpTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'category',
        'priority',
        'status',
        'subject',
        'description',
        'attachments',
        'assigned_to',
        'resolved_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'resolved_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function responses()
    {
        return $this->hasMany(HelpTicketResponse::class);
    }

    public function publicResponses()
    {
        return $this->hasMany(HelpTicketResponse::class)->where('is_internal', false);
    }

    public function internalResponses()
    {
        return $this->hasMany(HelpTicketResponse::class)->where('is_internal', true);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper methods
    public function assign($user)
    {
        $this->update([
            'assigned_to' => $user->id,
            'status' => 'in_progress',
        ]);
    }

    public function unassign()
    {
        $this->update([
            'assigned_to' => null,
            'status' => 'open',
        ]);
    }

    public function resolve($user = null)
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        if ($user) {
            $this->responses()->create([
                'user_id' => $user->id,
                'response' => 'Ticket marked as resolved.',
                'is_internal' => true,
            ]);
        }
    }

    public function close()
    {
        $this->update(['status' => 'closed']);
    }

    public function reopen()
    {
        $this->update([
            'status' => 'open',
            'resolved_at' => null,
        ]);
    }

    public function isOpen()
    {
        return in_array($this->status, ['open', 'in_progress']);
    }

    public function isClosed()
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    public function getStatusColor()
    {
        $colors = [
            'open' => 'bg-red-100 text-red-800',
            'in_progress' => 'bg-yellow-100 text-yellow-800',
            'resolved' => 'bg-green-100 text-green-800',
            'closed' => 'bg-gray-100 text-gray-800',
        ];

        return $colors[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getPriorityColor()
    {
        $colors = [
            'low' => 'bg-gray-100 text-gray-800',
            'normal' => 'bg-blue-100 text-blue-800',
            'high' => 'bg-orange-100 text-orange-800',
            'urgent' => 'bg-red-100 text-red-800',
        ];

        return $colors[$this->priority] ?? 'bg-gray-100 text-gray-800';
    }

    public function getLastResponse()
    {
        return $this->responses()->latest()->first();
    }

    public function hasResponses()
    {
        return $this->responses()->exists();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (!$ticket->ticket_number) {
                $ticket->ticket_number = 'TKT-' . strtoupper(Str::random(8));
            }
        });
    }
}