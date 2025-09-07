<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AlumniMapService
{
    /**
     * Get alumni with location data for map visualization
     */
    public function getAlumniWithLocations(array $filters = []): Collection
    {
        $query = User::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('location_privacy', '!=', 'private')
            ->with(['educations.school', 'currentEmployment.company']);

        // Apply filters
        if (! empty($filters['graduation_year'])) {
            $query->whereHas('educations', function ($q) use ($filters) {
                $q->where('graduation_year', $filters['graduation_year']);
            });
        }

        if (! empty($filters['school_id'])) {
            $query->whereHas('educations', function ($q) use ($filters) {
                $q->where('school_id', $filters['school_id']);
            });
        }

        if (! empty($filters['industry'])) {
            $query->whereHas('currentEmployment', function ($q) use ($filters) {
                $q->where('industry', $filters['industry']);
            });
        }

        if (! empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        if (! empty($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        return $query->get();
    }

    /**
     * Get clustered alumni data for performance
     */
    public function getClusteredAlumni(float $bounds_north, float $bounds_south, float $bounds_east, float $bounds_west, int $zoom_level = 10): array
    {
        // Determine cluster size based on zoom level
        $cluster_size = $this->getClusterSize($zoom_level);

        $alumni = DB::select("
            SELECT 
                ROUND(latitude / ?) * ? as cluster_lat,
                ROUND(longitude / ?) * ? as cluster_lng,
                COUNT(*) as count,
                JSON_AGG(
                    JSON_BUILD_OBJECT(
                        'id', id,
                        'name', name,
                        'avatar_url', avatar_url,
                        'current_title', current_title,
                        'current_company', current_company,
                        'latitude', latitude,
                        'longitude', longitude
                    )
                ) as alumni
            FROM users 
            WHERE latitude BETWEEN ? AND ?
            AND longitude BETWEEN ? AND ?
            AND latitude IS NOT NULL 
            AND longitude IS NOT NULL
            AND location_privacy != 'private'
            GROUP BY cluster_lat, cluster_lng
            HAVING COUNT(*) > 0
        ", [
            $cluster_size, $cluster_size,
            $cluster_size, $cluster_size,
            $bounds_south, $bounds_north,
            $bounds_west, $bounds_east,
        ]);

        return array_map(function ($cluster) {
            $cluster->alumni = json_decode($cluster->alumni, true);

            return $cluster;
        }, $alumni);
    }

    /**
     * Get regional statistics
     */
    public function getRegionalStats(): array
    {
        return [
            'by_country' => $this->getStatsByCountry(),
            'by_region' => $this->getStatsByRegion(),
            'by_industry' => $this->getStatsByIndustry(),
            'total_alumni' => $this->getTotalAlumniWithLocation(),
        ];
    }

    /**
     * Get alumni statistics by country
     */
    private function getStatsByCountry(): array
    {
        return User::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('location_privacy', '!=', 'private')
            ->select('country', DB::raw('COUNT(*) as count'))
            ->groupBy('country')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /**
     * Get alumni statistics by region
     */
    private function getStatsByRegion(): array
    {
        return User::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('location_privacy', '!=', 'private')
            ->select('region', DB::raw('COUNT(*) as count'))
            ->groupBy('region')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /**
     * Get alumni statistics by industry
     */
    private function getStatsByIndustry(): array
    {
        return User::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('location_privacy', '!=', 'private')
            ->whereHas('currentEmployment')
            ->join('employments', function ($join) {
                $join->on('users.id', '=', 'employments.user_id')
                    ->where('employments.is_current', true);
            })
            ->select('employments.industry', DB::raw('COUNT(*) as count'))
            ->groupBy('employments.industry')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /**
     * Get total count of alumni with location data
     */
    private function getTotalAlumniWithLocation(): int
    {
        return User::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('location_privacy', '!=', 'private')
            ->count();
    }

    /**
     * Determine cluster size based on zoom level
     */
    private function getClusterSize(int $zoom_level): float
    {
        // Smaller cluster size for higher zoom levels (more detailed view)
        return match (true) {
            $zoom_level >= 15 => 0.001,  // Very detailed
            $zoom_level >= 12 => 0.01,   // Detailed
            $zoom_level >= 9 => 0.1,     // Medium
            $zoom_level >= 6 => 0.5,     // Broad
            default => 1.0               // Very broad
        };
    }

    /**
     * Update user location privacy settings
     */
    public function updateLocationPrivacy(User $user, string $privacy_level): bool
    {
        $allowed_levels = ['public', 'alumni_only', 'private'];

        if (! in_array($privacy_level, $allowed_levels)) {
            return false;
        }

        return $user->update(['location_privacy' => $privacy_level]);
    }

    /**
     * Geocode address to coordinates
     */
    public function geocodeAddress(string $address): ?array
    {
        // This would integrate with a geocoding service like Google Maps API
        // For now, return null - implement based on chosen geocoding service
        return null;
    }

    /**
     * Get nearby alumni within specified radius
     */
    public function getNearbyAlumni(float $latitude, float $longitude, int $radius_km = 50): Collection
    {
        // Using Haversine formula for distance calculation
        return User::select('*')
            ->selectRaw('
                (6371 * acos(
                    cos(radians(?)) * 
                    cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(latitude))
                )) AS distance
            ', [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('location_privacy', '!=', 'private')
            ->having('distance', '<', $radius_km)
            ->orderBy('distance')
            ->with(['educations.school', 'currentEmployment.company'])
            ->get();
    }
}
