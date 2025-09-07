<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Template A/B Test Model
 *
 * Manages A/B testing functionality for templates with statistical analysis
 * and traffic distribution capabilities.
 */
class TemplateAbTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'name',
        'description',
        'variants',
        'status',
        'goal_metric',
        'confidence_threshold',
        'sample_size_per_variant',
        'traffic_distribution',
        'started_at',
        'ended_at',
        'results'
    ];

    protected $casts = [
        'variants' => 'array',
        'traffic_distribution' => 'array',
        'results' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'confidence_threshold' => 'decimal:4',
        'sample_size_per_variant' => 'integer'
    ];

    /**
     * Get the template this A/B test belongs to
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Get all events for this A/B test
     */
    public function events(): HasMany
    {
        return $this->hasMany(AbTestEvent::class, 'ab_test_id');
    }

    /**
     * Scope for active tests
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for completed tests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Check if the test is currently running
     */
    public function isRunning(): bool
    {
        return $this->status === 'active' &&
               $this->started_at &&
               (!$this->ended_at || $this->ended_at->isFuture());
    }

    /**
     * Check if the test has reached statistical significance
     */
    public function hasStatisticalSignificance(): bool
    {
        $results = $this->results;

        if (!$results || !isset($results['confidence_level'])) {
            return false;
        }

        return $results['confidence_level'] >= $this->confidence_threshold;
    }

    /**
     * Get the winning variant based on results
     */
    public function getWinningVariant(): ?array
    {
        $results = $this->results;

        if (!$results || !isset($results['winner'])) {
            return null;
        }

        return collect($this->variants)->firstWhere('id', $results['winner']);
    }

    /**
     * Calculate current traffic distribution
     */
    public function getCurrentTrafficDistribution(): array
    {
        if ($this->traffic_distribution) {
            return $this->traffic_distribution;
        }

        // Default to equal distribution
        $variantCount = count($this->variants);
        $distribution = 100 / $variantCount;

        return collect($this->variants)->mapWithKeys(function ($variant) use ($distribution) {
            return [$variant['id'] => $distribution];
        })->toArray();
    }

    /**
     * Get variant assignment for a session
     */
    public function getVariantForSession(string $sessionId): string
    {
        // Use consistent hashing for variant assignment
        $hash = crc32($sessionId . $this->id);
        $distribution = $this->getCurrentTrafficDistribution();

        $cumulative = 0;
        foreach ($distribution as $variantId => $percentage) {
            $cumulative += $percentage;
            if (($hash % 100) < $cumulative) {
                return $variantId;
            }
        }

        // Fallback to first variant
        return collect($this->variants)->first()['id'];
    }

    /**
     * Record an event for the A/B test
     */
    public function recordEvent(string $variantId, string $eventType, string $sessionId = null, array $eventData = []): void
    {
        $this->events()->create([
            'variant_id' => $variantId,
            'event_type' => $eventType,
            'session_id' => $sessionId,
            'event_data' => $eventData,
            'occurred_at' => now()
        ]);
    }

    /**
     * Calculate statistical results
     */
    public function calculateResults(): array
    {
        $events = $this->events()->get();
        $variants = collect($this->variants);

        $results = [
            'total_events' => $events->count(),
            'variants' => [],
            'winner' => null,
            'confidence_level' => 0.0,
            'calculated_at' => now()->toISOString()
        ];

        foreach ($variants as $variant) {
            $variantEvents = $events->where('variant_id', $variant['id']);
            $variantResults = $this->calculateVariantStats($variantEvents, $variant);

            $results['variants'][$variant['id']] = $variantResults;
        }

        // Determine winner based on goal metric
        $results['winner'] = $this->determineWinner($results['variants']);
        $results['confidence_level'] = $this->calculateConfidenceLevel($results['variants']);

        return $results;
    }

    /**
     * Calculate statistics for a specific variant
     */
    private function calculateVariantStats(Collection $events, array $variant): array
    {
        $totalEvents = $events->count();
        $goalEvents = $events->where('event_type', $this->goal_metric)->count();

        return [
            'variant_id' => $variant['id'],
            'variant_name' => $variant['name'],
            'total_events' => $totalEvents,
            'goal_events' => $goalEvents,
            'conversion_rate' => $totalEvents > 0 ? ($goalEvents / $totalEvents) * 100 : 0,
            'unique_sessions' => $events->pluck('session_id')->unique()->count()
        ];
    }

    /**
     * Determine the winning variant
     */
    private function determineWinner(array $variantResults): ?string
    {
        if (empty($variantResults)) {
            return null;
        }

        $bestVariant = null;
        $bestScore = -1;

        foreach ($variantResults as $variantId => $results) {
            $score = $results['conversion_rate'];
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestVariant = $variantId;
            }
        }

        return $bestVariant;
    }

    /**
     * Calculate statistical confidence level
     */
    private function calculateConfidenceLevel(array $variantResults): float
    {
        // Simplified confidence calculation
        // In a real implementation, you'd use proper statistical tests
        if (count($variantResults) < 2) {
            return 0.0;
        }

        $conversionRates = array_column($variantResults, 'conversion_rate');
        $maxRate = max($conversionRates);
        $minRate = min($conversionRates);

        if ($maxRate === $minRate) {
            return 0.0;
        }

        // Simple confidence based on difference and sample size
        $difference = $maxRate - $minRate;
        $avgSampleSize = array_sum(array_column($variantResults, 'total_events')) / count($variantResults);

        // Higher confidence with larger differences and sample sizes
        $confidence = min(1.0, ($difference / 100) * sqrt($avgSampleSize / 1000));

        return round($confidence, 4);
    }

    /**
     * Start the A/B test
     */
    public function start(): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }

        $this->update([
            'status' => 'active',
            'started_at' => now()
        ]);

        return true;
    }

    /**
     * Stop the A/B test
     */
    public function stop(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $this->update([
            'status' => 'completed',
            'ended_at' => now(),
            'results' => $this->calculateResults()
        ]);

        return true;
    }

    /**
     * Pause the A/B test
     */
    public function pause(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $this->update(['status' => 'paused']);

        return true;
    }

    /**
     * Resume the A/B test
     */
    public function resume(): bool
    {
        if ($this->status !== 'paused') {
            return false;
        }

        $this->update(['status' => 'active']);

        return true;
    }
}