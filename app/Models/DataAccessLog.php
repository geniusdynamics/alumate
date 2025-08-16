<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'resource_type',
        'resource_id',
        'access_type',
        'ip_address',
        'user_agent',
        'query_parameters',
        'authorized',
        'authorization_method',
    ];

    protected $casts = [
        'query_parameters' => 'array',
        'authorized' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByResource($query, $type, $id = null)
    {
        $query->where('resource_type', $type);
        if ($id) {
            $query->where('resource_id', $id);
        }

        return $query;
    }

    public function scopeByAccessType($query, $type)
    {
        return $query->where('access_type', $type);
    }

    public function scopeUnauthorized($query)
    {
        return $query->where('authorized', false);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Helper methods
    public static function logAccess($userId, $resourceType, $resourceId, $accessType, $authorized = true, $authMethod = null)
    {
        return self::create([
            'user_id' => $userId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'access_type' => $accessType,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'query_parameters' => request()->query(),
            'authorized' => $authorized,
            'authorization_method' => $authMethod,
        ]);
    }

    // Access types constants
    const ACCESS_VIEW = 'view';

    const ACCESS_CREATE = 'create';

    const ACCESS_UPDATE = 'update';

    const ACCESS_DELETE = 'delete';

    const ACCESS_EXPORT = 'export';

    const ACCESS_IMPORT = 'import';

    // Resource types constants
    const RESOURCE_GRADUATE = 'graduate';

    const RESOURCE_JOB = 'job';

    const RESOURCE_APPLICATION = 'application';

    const RESOURCE_EMPLOYER = 'employer';

    const RESOURCE_COURSE = 'course';

    const RESOURCE_USER = 'user';

    const RESOURCE_INSTITUTION = 'institution';
}
