<?php

namespace App\Services;

use App\Models\Graduate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AlumniMapService
{
    /**
     * Get alumni within specified map bounds
     */
    public function getAlumniByLocation(array $bounds, array $filters = []): Collection
    {
        $query = Graduate::query()
            ->select([
                'id', 'first_name', 'last_name', 'graduation_year',
                'current_position', 'current_company', 'industry',
                'latitude', 'longitude', 'city', 'state', 'country',
                'profile_photo_path', 'profile_visibility',
            ])
            ->where('profile_visibility', '!=', 'private')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Apply geographic bounds
        if (isset($bounds['north'], $bounds['south'], $bounds['east'], $bounds['west'])) {
            $query->whereBetween('latitude', [$bounds['south'], $bounds['north']])
                ->whereBetween('longitude', [$bounds['west'], $bounds['east']]);
        }

        // Apply filters
        if (! empty($filters['graduation_year'])) {
            $query->whereIn('graduation_year', (array) $filters['graduation_year']);
        }

        if (! empty($filters['industry'])) {
            $query->whereIn('industry', (array) $filters['industry']);
        }

        if (! empty($filters['country'])) {
            $query->whereIn('country', (array) $filters['country']);
        }

        if (! empty($filters['state'])) {
            $query->whereIn('state', (array) $filters['state']);
        }

        return $query->limit(1000)->get();
    }

    /**
     * Get location clusters based on zoom level
     */
    public function getLocationClusters(int $zoomLevel, array $bounds = []): Collection
    {
        $precision = $this->getClusterPrecision($zoomLevel);

        $query = Graduate::query()
            ->select([
                DB::raw("ROUND(latitude, {$precision}) as cluster_lat"),
                DB::raw("ROUND(longitude, {$precision}) as cluster_lng"),
                DB::raw('COUNT(*) as alumni_count'),
                DB::raw('GROUP_CONCAT(DISTINCT industry) as industries'),
                DB::raw('MIN(graduation_year) as earliest_year'),
                DB::raw('MAX(graduation_year) as latest_year'),
                DB::raw('GROUP_CONCAT(DISTINCT country) as countries'),
            ])
            ->where('profile_visibility', '!=', 'private')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Apply bounds if provided
        if (! empty($bounds)) {
            $query->whereBetween('latitude', [$bounds['south'], $bounds['north']])
                ->whereBetween('longitude', [$bounds['west'], $bounds['east']]);
        }

        return $query->groupBy('cluster_lat', 'cluster_lng')
            ->having('alumni_count', '>', 0)
            ->get()
            ->map(function ($cluster) {
                return [
                    'latitude' => (float) $cluster->cluster_lat,
                    'longitude' => (float) $cluster->cluster_lng,
                    'count' => (int) $cluster->alumni_count,
                    'industries' => array_filter(explode(',', $cluster->industries ?? '')),
                    'year_range' => [
                        'min' => (int) $cluster->earliest_year,
                        'max' => (int) $cluster->latest_year,
                    ],
                    'countries' => array_unique(array_filter(explode(',', $cluster->countries ?? ''))),
                ];
            });
    }

    /**
     * Get alumni statistics for a specific region
     */
    public function getRegionalStats(string $region, string $regionType = 'country'): array
    {
        $column = match ($regionType) {
            'country' => 'country',
            'state' => 'state',
            'city' => 'city',
            default => 'country'
        };

        $alumni = Graduate::query()
            ->where($column, $region)
            ->where('profile_visibility', '!=', 'private')
            ->get();

        $totalAlumni = $alumni->count();

        if ($totalAlumni === 0) {
            return [
                'total_alumni' => 0,
                'industries' => [],
                'graduation_years' => [],
                'top_companies' => [],
                'average_experience' => 0,
            ];
        }

        $industries = $alumni->groupBy('industry')
            ->map->count()
            ->sortDesc()
            ->take(10);

        $graduationYears = $alumni->groupBy('graduation_year')
            ->map->count()
            ->sortKeys();

        $topCompanies = $alumni->whereNotNull('current_company')
            ->groupBy('current_company')
            ->map->count()
            ->sortDesc()
            ->take(10);

        $currentYear = now()->year;
        $averageExperience = $alumni->avg(function ($graduate) use ($currentYear) {
            return $currentYear - $graduate->graduation_year;
        });

        return [
            'total_alumni' => $totalAlumni,
            'industries' => $industries->toArray(),
            'graduation_years' => $graduationYears->toArray(),
            'top_companies' => $topCompanies->toArray(),
            'average_experience' => round($averageExperience, 1),
        ];
    }

    /**
     * Suggest regional groups based on location
     */
    public function suggestRegionalGroups(array $location): Collection
    {
        $latitude = $location['latitude'];
        $longitude = $location['longitude'];
        $radius = $location['radius'] ?? 50; // km

        // Find nearby alumni using Haversine formula
        $nearbyAlumni = Graduate::query()
            ->select([
                'id', 'first_name', 'last_name', 'city', 'state', 'country',
                'industry', 'graduation_year', 'latitude', 'longitude',
                DB::raw('
                    (6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) * 
                        cos(radians(longitude) - radians(?)) + 
                        sin(radians(?)) * sin(radians(latitude))
                    )) AS distance
                '),
            ])
            ->where('profile_visibility', '!=', 'private')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->setBindings([$latitude, $longitude, $latitude])
            ->get();

        // Group suggestions by different criteria
        $suggestions = collect();

        // City-based groups
        $cityGroups = $nearbyAlumni->groupBy('city')
            ->filter(fn ($group) => $group->count() >= 3)
            ->map(function ($group, $city) {
                return [
                    'type' => 'city',
                    'name' => "{$city} Alumni Network",
                    'location' => $city,
                    'member_count' => $group->count(),
                    'industries' => $group->pluck('industry')->unique()->values(),
                    'year_range' => [
                        'min' => $group->min('graduation_year'),
                        'max' => $group->max('graduation_year'),
                    ],
                ];
            });

        // Industry-based groups in the region
        $industryGroups = $nearbyAlumni->groupBy('industry')
            ->filter(fn ($group) => $group->count() >= 5)
            ->map(function ($group, $industry) use ($location) {
                return [
                    'type' => 'industry',
                    'name' => "{$industry} Professionals",
                    'location' => $location['city'] ?? 'Regional',
                    'member_count' => $group->count(),
                    'cities' => $group->pluck('city')->unique()->values(),
                    'year_range' => [
                        'min' => $group->min('graduation_year'),
                        'max' => $group->max('graduation_year'),
                    ],
                ];
            });

        return $suggestions->merge($cityGroups->values())
            ->merge($industryGroups->values())
            ->sortByDesc('member_count')
            ->take(10);
    }

    /**
     * Find nearby alumni for a given user
     */
    public function findNearbyAlumni(int $userId, int $radiusKm = 25): Collection
    {
        $user = Graduate::findOrFail($userId);

        if (! $user->latitude || ! $user->longitude) {
            return collect();
        }

        return Graduate::query()
            ->select([
                'id', 'first_name', 'last_name', 'current_position',
                'current_company', 'industry', 'city', 'state',
                'profile_photo_path', 'latitude', 'longitude',
                DB::raw('
                    (6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) * 
                        cos(radians(longitude) - radians(?)) + 
                        sin(radians(?)) * sin(radians(latitude))
                    )) AS distance
                '),
            ])
            ->where('id', '!=', $userId)
            ->where('profile_visibility', '!=', 'private')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance')
            ->setBindings([$user->latitude, $user->longitude, $user->latitude])
            ->limit(50)
            ->get();
    }

    /**
     * Calculate distance between two coordinates
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get cluster precision based on zoom level
     */
    private function getClusterPrecision(int $zoomLevel): int
    {
        return match (true) {
            $zoomLevel <= 3 => 0,  // Country level
            $zoomLevel <= 6 => 1,  // State/Province level
            $zoomLevel <= 9 => 2,  // City level
            $zoomLevel <= 12 => 3, // District level
            default => 4           // Street level
        };
    }

    /**
     * Geocode an address to coordinates
     */
    public function geocodeAddress(string $address): ?array
    {
        // This would integrate with a geocoding service like Google Maps API
        // For now, return null - implement based on chosen geocoding service
        return null;
    }

    /**
     * Reverse geocode coordinates to address
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        // This would integrate with a reverse geocoding service
        // For now, return null - implement based on chosen service
        return null;
    }
}
