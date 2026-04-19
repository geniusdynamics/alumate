<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\HeatMapData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for collecting and aggregating heat map data from analytics events
 */
class HeatMapService
{
    private const GRID_SIZE = 10; // 10x10 grid

    private const MAX_COORDINATE = 100; // Normalized coordinates 0-100

    /**
     * Collect heat map data for a specific page URL and date range
     */
    public function collectHeatMapData(string $pageUrl, array $dateRange): array
    {
        $tenantId = app(TenantContextService::class)->getCurrentTenantId();

        if (! $tenantId) {
            Log::warning('No tenant context available for heat map data collection');

            return [];
        }

        $events = AnalyticsEvent::byTenant($tenantId)
            ->whereIn('event_type', ['click', 'scroll'])
            ->where('page_url', $pageUrl)
            ->where('consent_given', true)
            ->byDateRange($dateRange[0], $dateRange[1])
            ->get();

        if ($events->isEmpty()) {
            Log::info('No events found for heat map data collection', [
                'page_url' => $pageUrl,
                'date_range' => $dateRange,
                'tenant_id' => $tenantId,
            ]);

            return [];
        }

        $coordinateData = $this->aggregateCoordinates($events);

        // Store or update heat map data
        $this->storeHeatMapData($pageUrl, $coordinateData, $tenantId);

        return $coordinateData;
    }

    /**
     * Record a heat map event from an analytics event
     */
    public function recordHeatMapEvent(AnalyticsEvent $event): void
    {
        if (! $this->shouldProcessEvent($event)) {
            return;
        }

        $tenantId = app(TenantContextService::class)->getCurrentTenantId();

        if (! $tenantId) {
            Log::warning('No tenant context available for heat map event recording');

            return;
        }

        $coordinates = $this->extractCoordinates($event);
        if (empty($coordinates)) {
            return;
        }

        $coordinateData = $this->aggregateCoordinates(collect([$event]));

        $this->updateHeatMapData($event->page_url, $coordinateData, $tenantId, $event->session_id);
    }

    /**
     * Check if an event should be processed for heat map data
     */
    private function shouldProcessEvent(AnalyticsEvent $event): bool
    {
        return in_array($event->event_type, ['click', 'scroll'])
            && $event->consent_given
            && isset($event->properties['x']) && isset($event->properties['y']);
    }

    /**
     * Extract coordinates from event properties
     */
    private function extractCoordinates(AnalyticsEvent $event): array
    {
        $coordinates = [];

        if (isset($event->properties['x']) && isset($event->properties['y'])) {
            $coordinates[] = [
                'x' => (float) $event->properties['x'],
                'y' => (float) $event->properties['y'],
                'intensity' => 1,
            ];
        }

        return $coordinates;
    }

    /**
     * Aggregate coordinates into grid bins
     */
    private function aggregateCoordinates(Collection $events): array
    {
        $grid = $this->initializeGrid();
        $totalEvents = 0;

        foreach ($events as $event) {
            $coordinates = $this->extractCoordinates($event);

            foreach ($coordinates as $coord) {
                $binX = $this->getGridBin($coord['x']);
                $binY = $this->getGridBin($coord['y']);

                if ($binX >= 0 && $binX < self::GRID_SIZE && $binY >= 0 && $binY < self::GRID_SIZE) {
                    $grid[$binY][$binX] += $coord['intensity'];
                    $totalEvents++;
                }
            }
        }

        return $this->normalizeGrid($grid, $totalEvents);
    }

    /**
     * Initialize empty grid
     */
    private function initializeGrid(): array
    {
        $grid = [];
        for ($y = 0; $y < self::GRID_SIZE; $y++) {
            $grid[$y] = array_fill(0, self::GRID_SIZE, 0);
        }

        return $grid;
    }

    /**
     * Get grid bin for a coordinate
     */
    private function getGridBin(float $coordinate): int
    {
        // Normalize coordinate to 0-100 range if needed
        $normalized = min(max($coordinate, 0), self::MAX_COORDINATE);

        return (int) floor(($normalized / self::MAX_COORDINATE) * self::GRID_SIZE);
    }

    /**
     * Normalize grid values to intensity percentages
     */
    private function normalizeGrid(array $grid, int $totalEvents): array
    {
        if ($totalEvents === 0) {
            return [];
        }

        $result = [];
        $maxIntensity = 0;

        // Find maximum intensity
        foreach ($grid as $row) {
            $maxIntensity = max($maxIntensity, max($row));
        }

        if ($maxIntensity === 0) {
            return [];
        }

        // Normalize to 0-100
        foreach ($grid as $y => $row) {
            foreach ($row as $x => $intensity) {
                if ($intensity > 0) {
                    $result[] = [
                        'x' => $x,
                        'y' => $y,
                        'intensity' => round(($intensity / $maxIntensity) * 100, 2),
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Store heat map data
     */
    private function storeHeatMapData(string $pageUrl, array $coordinateData, string $tenantId): void
    {
        if (empty($coordinateData)) {
            return;
        }

        HeatMapData::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'page_url' => $pageUrl,
            ],
            [
                'coordinate_data' => $coordinateData,
                'timestamp' => now(),
            ]
        );

        Log::info('Heat map data stored', [
            'page_url' => $pageUrl,
            'tenant_id' => $tenantId,
            'data_points' => count($coordinateData),
        ]);
    }

    /**
     * Update existing heat map data with new event
     */
    private function updateHeatMapData(string $pageUrl, array $newData, string $tenantId, string $sessionId): void
    {
        $heatMapData = HeatMapData::byTenant($tenantId)
            ->byPageUrl($pageUrl)
            ->first();

        if ($heatMapData) {
            // Merge new data with existing data
            $existingData = $heatMapData->coordinate_data ?? [];
            $mergedData = $this->mergeCoordinateData($existingData, $newData);

            $heatMapData->update([
                'coordinate_data' => $mergedData,
                'timestamp' => now(),
            ]);
        } else {
            // Create new record
            HeatMapData::create([
                'tenant_id' => $tenantId,
                'page_url' => $pageUrl,
                'coordinate_data' => $newData,
                'timestamp' => now(),
                'session_id' => $sessionId,
            ]);
        }

        Log::debug('Heat map data updated', [
            'page_url' => $pageUrl,
            'tenant_id' => $tenantId,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Merge coordinate data arrays
     */
    private function mergeCoordinateData(array $existing, array $new): array
    {
        $merged = [];

        // Create lookup map for existing data
        $existingMap = [];
        foreach ($existing as $point) {
            $key = $point['x'].','.$point['y'];
            $existingMap[$key] = $point['intensity'];
        }

        // Merge new data
        foreach ($new as $point) {
            $key = $point['x'].','.$point['y'];
            $intensity = ($existingMap[$key] ?? 0) + $point['intensity'];
            $merged[] = [
                'x' => $point['x'],
                'y' => $point['y'],
                'intensity' => min($intensity, 100), // Cap at 100
            ];
        }

        // Add any existing points not in new data
        foreach ($existing as $point) {
            $key = $point['x'].','.$point['y'];
            if (! isset($existingMap[$key])) {
                $merged[] = $point;
            }
        }

        return $merged;
    }
}
