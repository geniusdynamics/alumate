<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ComponentAnalytic extends Model
{
    /** @use HasFactory<\Database\Factories\ComponentAnalyticFactory> */
    use HasFactory;

    protected $fillable = [
        'component_instance_id',
        'event_type',
        'user_id',
        'session_id',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // Relationships
    public function componentInstance(): BelongsTo
    {
        return $this->belongsTo(ComponentInstance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Event recording methods
    public static function recordView(int $componentInstanceId, ?int $userId = null, ?string $sessionId = null, array $data = []): self
    {
        return self::create([
            'component_instance_id' => $componentInstanceId,
            'event_type' => 'view',
            'user_id' => $userId,
            'session_id' => $sessionId,
            'data' => $data,
        ]);
    }

    public static function recordClick(int $componentInstanceId, ?int $userId = null, ?string $sessionId = null, array $data = []): self
    {
        return self::create([
            'component_instance_id' => $componentInstanceId,
            'event_type' => 'click',
            'user_id' => $userId,
            'session_id' => $sessionId,
            'data' => $data,
        ]);
    }

    public static function recordConversion(int $componentInstanceId, ?int $userId = null, ?string $sessionId = null, array $data = []): self
    {
        return self::create([
            'component_instance_id' => $componentInstanceId,
            'event_type' => 'conversion',
            'user_id' => $userId,
            'session_id' => $sessionId,
            'data' => $data,
        ]);
    }

    public static function recordFormSubmit(int $componentInstanceId, ?int $userId = null, ?string $sessionId = null, array $data = []): self
    {
        return self::create([
            'component_instance_id' => $componentInstanceId,
            'event_type' => 'form_submit',
            'user_id' => $userId,
            'session_id' => $sessionId,
            'data' => $data,
        ]);
    }

    // Data aggregation methods
    public static function getEventCounts(int $componentInstanceId, ?string $eventType = null): Collection
    {
        $query = self::where('component_instance_id', $componentInstanceId);

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        return $query->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->get();
    }

    public static function getConversionRate(int $componentInstanceId): float
    {
        $views = self::where('component_instance_id', $componentInstanceId)
            ->where('event_type', 'view')
            ->count();

        $conversions = self::where('component_instance_id', $componentInstanceId)
            ->where('event_type', 'conversion')
            ->count();

        return $views > 0 ? ($conversions / $views) * 100 : 0;
    }

    public static function getClickThroughRate(int $componentInstanceId): float
    {
        $views = self::where('component_instance_id', $componentInstanceId)
            ->where('event_type', 'view')
            ->count();

        $clicks = self::where('component_instance_id', $componentInstanceId)
            ->where('event_type', 'click')
            ->count();

        return $views > 0 ? ($clicks / $views) * 100 : 0;
    }

    public static function getUniqueUsers(int $componentInstanceId, ?string $eventType = null): int
    {
        $query = self::where('component_instance_id', $componentInstanceId)
            ->whereNotNull('user_id');

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        return $query->distinct('user_id')->count('user_id');
    }

    public static function getUniqueSessions(int $componentInstanceId, ?string $eventType = null): int
    {
        $query = self::where('component_instance_id', $componentInstanceId)
            ->whereNotNull('session_id');

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        return $query->distinct('session_id')->count('session_id');
    }

    // A/B testing variant tracking methods
    public static function recordVariantEvent(int $componentInstanceId, string $eventType, string $variant, ?int $userId = null, ?string $sessionId = null, array $additionalData = []): self
    {
        $data = array_merge($additionalData, ['variant' => $variant]);

        return self::create([
            'component_instance_id' => $componentInstanceId,
            'event_type' => $eventType,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'data' => $data,
        ]);
    }

    public static function getVariantPerformance(int $componentInstanceId): Collection
    {
        return self::where('component_instance_id', $componentInstanceId)
            ->whereNotNull('data->variant')
            ->selectRaw('JSON_EXTRACT(data, "$.variant") as variant, event_type, COUNT(*) as count')
            ->groupBy('variant', 'event_type')
            ->get()
            ->groupBy('variant')
            ->map(function ($events, $variant) {
                $eventCounts = $events->pluck('count', 'event_type');
                $views = $eventCounts->get('view', 0);
                $conversions = $eventCounts->get('conversion', 0);
                $clicks = $eventCounts->get('click', 0);

                return [
                    'variant' => $variant,
                    'views' => $views,
                    'clicks' => $clicks,
                    'conversions' => $conversions,
                    'click_through_rate' => $views > 0 ? ($clicks / $views) * 100 : 0,
                    'conversion_rate' => $views > 0 ? ($conversions / $views) * 100 : 0,
                ];
            });
    }

    public static function getBestPerformingVariant(int $componentInstanceId, string $metric = 'conversion_rate'): ?array
    {
        $performance = self::getVariantPerformance($componentInstanceId);

        if ($performance->isEmpty()) {
            return null;
        }

        return $performance->sortByDesc($metric)->first();
    }

    // Query scopes
    public function scopeForDateRange(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeForEventType(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeForEventTypes(Builder $query, array $eventTypes): Builder
    {
        return $query->whereIn('event_type', $eventTypes);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeForVariant(Builder $query, string $variant): Builder
    {
        return $query->where('data->variant', $variant);
    }

    public function scopeWithVariant(Builder $query): Builder
    {
        return $query->whereNotNull('data->variant');
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }

    public function scopeLastDays(Builder $query, int $days): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
