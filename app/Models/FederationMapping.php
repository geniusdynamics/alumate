<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Federation Mapping Model
 * Tracks mappings between local platform entities and federated protocol identifiers
 */
class FederationMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'local_type',
        'local_id',
        'protocol',
        'federation_id',
        'federation_data',
        'server_name',
        'federated_at',
    ];

    protected function casts(): array
    {
        return [
            'federation_data' => 'array',
            'federated_at' => 'datetime',
        ];
    }

    /**
     * Get the local entity that this mapping belongs to
     */
    public function local(): MorphTo
    {
        return $this->morphTo('local', 'local_type', 'local_id');
    }

    /**
     * Scope to filter by protocol
     */
    public function scopeForProtocol($query, string $protocol)
    {
        return $query->where('protocol', $protocol);
    }

    /**
     * Scope to filter by local entity type
     */
    public function scopeForLocalType($query, string $type)
    {
        return $query->where('local_type', $type);
    }

    /**
     * Get mapping for a specific local entity and protocol
     */
    public static function findMapping(string $type, int $localId, string $protocol): ?self
    {
        return static::where([
            'local_type' => $type,
            'local_id' => $localId,
            'protocol' => $protocol,
        ])->first();
    }

    /**
     * Check if an entity is federated to a specific protocol
     */
    public static function isFederated(string $type, int $localId, string $protocol): bool
    {
        return static::where([
            'local_type' => $type,
            'local_id' => $localId,
            'protocol' => $protocol,
        ])->exists();
    }

    /**
     * Get all protocols an entity is federated to
     */
    public static function getFederatedProtocols(string $type, int $localId): array
    {
        return static::where([
            'local_type' => $type,
            'local_id' => $localId,
        ])->pluck('protocol')->toArray();
    }

    /**
     * Get federation statistics
     */
    public static function getStatistics(): array
    {
        return [
            'total_mappings' => static::count(),
            'by_protocol' => static::groupBy('protocol')
                ->selectRaw('protocol, count(*) as count')
                ->pluck('count', 'protocol')
                ->toArray(),
            'by_type' => static::groupBy('local_type')
                ->selectRaw('local_type, count(*) as count')
                ->pluck('count', 'local_type')
                ->toArray(),
            'recent_activity' => static::where('federated_at', '>=', now()->subDays(7))
                ->count(),
        ];
    }
}
