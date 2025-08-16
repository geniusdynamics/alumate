<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventCheckIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'checked_in_at',
        'check_in_method',
        'location_data',
        'notes',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'location_data' => 'array',
    ];

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function getCheckInMethodLabel(): string
    {
        return match ($this->check_in_method) {
            'manual' => 'Manual Check-in',
            'qr_code' => 'QR Code Scan',
            'nfc' => 'NFC Tap',
            'geofence' => 'Location-based',
            default => 'Unknown Method'
        };
    }
}
