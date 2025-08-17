<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageContentVersion extends Model
{
    protected $fillable = [
        'homepage_content_id',
        'version_number',
        'value',
        'metadata',
        'change_notes',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the homepage content this version belongs to
     */
    public function homepageContent(): BelongsTo
    {
        return $this->belongsTo(HomepageContent::class);
    }

    /**
     * Get the user who created this version
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
