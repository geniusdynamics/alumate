<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AlumniMapService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AlumniMapController extends Controller
{
    public function __construct(
        private AlumniMapService $alumniMapService
    ) {}

    /**
     * Get alumni within map bounds
     */
    public function getAlumniByLocation(Request $request): JsonResponse
    {
        $request->validate([
            'bounds' => 'required|array',
            'bounds.north' => 'required|numeric|between:-90,90',
            'bounds.south' => 'required|numeric|between:-90,90',
            'bounds.east' => 'required|numeric|between:-180,180',
            'bounds.west' => 'required|numeric|between:-180,180',
            'filters' => 'sometimes|array',
            'filters.graduation_year' => 'sometimes|array',
            'filters.industry' => 'sometimes|array',
            'filters.country' => 'sometimes|array',
            'filters.state' => 'sometimes|array',
        ]);

        $bounds = $request->input('bounds');
        $filters = $request->input('filters', []);

        $alumni = $this->alumniMapService->getAlumniByLocation($bounds, $filters);

        return response()->json($alumni);
    }

    /**
     * Get location clusters
     */
    public function getClusters(Request $request): JsonResponse
    {
        $request->validate([
            'zoom_level' => 'required|integer|min:1|max:18',
            'bounds' => 'sometimes|array',
            'bounds.north' => 'sometimes|numeric|between:-90,90',
            'bounds.south' => 'sometimes|numeric|between:-90,90',
            'bounds.east' => 'sometimes|numeric|between:-180,180',
            'bounds.west' => 'sometimes|numeric|between:-180,180',
            'filters' => 'sometimes|array',
        ]);

        $zoomLevel = $request->input('zoom_level');
        $bounds = $request->input('bounds', []);
        $filters = $request->input('filters', []);

        $clusters = $this->alumniMapService->getLocationClusters($zoomLevel, $bounds);

        return response()->json($clusters);
    }

    /**
     * Get nearby alumni for current user
     */
    public function getNearbyAlumni(Request $request): JsonResponse
    {
        $request->validate([
            'radius' => 'sometimes|integer|min:1|max:500',
        ]);

        $userId = auth()->id();
        $radius = $request->input('radius', 25);

        $nearbyAlumni = $this->alumniMapService->findNearbyAlumni($userId, $radius);

        return response()->json($nearbyAlumni);
    }

    /**
     * Get regional statistics
     */
    public function getRegionalStats(Request $request, string $region): JsonResponse
    {
        $request->validate([
            'region_type' => 'sometimes|in:country,state,city',
        ]);

        $regionType = $request->input('region_type', 'country');
        $stats = $this->alumniMapService->getRegionalStats($region, $regionType);

        return response()->json($stats);
    }

    /**
     * Get suggested regional groups
     */
    public function getSuggestedGroups(Request $request, string $region): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'sometimes|integer|min:1|max:500',
        ]);

        $location = [
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'radius' => $request->input('radius', 50),
            'city' => $region
        ];

        $suggestions = $this->alumniMapService->suggestRegionalGroups($location);

        return response()->json($suggestions);
    }

    /**
     * Get filter options for the map
     */
    public function getFilterOptions(): JsonResponse
    {
        // This would typically query the database for available options
        // For now, return static data
        
        $currentYear = now()->year;
        $graduationYears = range($currentYear - 50, $currentYear);
        
        $industries = [
            'Technology',
            'Healthcare',
            'Finance',
            'Education',
            'Manufacturing',
            'Consulting',
            'Marketing',
            'Sales',
            'Engineering',
            'Research',
            'Legal',
            'Media',
            'Non-profit',
            'Government',
            'Retail'
        ];

        $countries = [
            'United States',
            'Canada',
            'United Kingdom',
            'Germany',
            'France',
            'Australia',
            'Japan',
            'Singapore',
            'India',
            'Brazil',
            'Mexico',
            'Netherlands',
            'Sweden',
            'Switzerland',
            'South Korea'
        ];

        $states = [
            'California',
            'New York',
            'Texas',
            'Florida',
            'Illinois',
            'Pennsylvania',
            'Ohio',
            'Georgia',
            'North Carolina',
            'Michigan',
            'New Jersey',
            'Virginia',
            'Washington',
            'Arizona',
            'Massachusetts'
        ];

        return response()->json([
            'graduation_years' => $graduationYears,
            'industries' => $industries,
            'countries' => $countries,
            'states' => $states
        ]);
    }

    /**
     * Update user location
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'country' => 'sometimes|string|max:100',
            'privacy_level' => 'sometimes|in:public,alumni_only,private',
        ]);

        $user = auth()->user();
        
        $user->update([
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'country' => $request->input('country'),
            'location_privacy' => $request->input('privacy_level', 'alumni_only'),
        ]);

        return response()->json([
            'message' => 'Location updated successfully',
            'location' => [
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
                'city' => $user->city,
                'state' => $user->state,
                'country' => $user->country,
            ]
        ]);
    }

    /**
     * Get alumni density heatmap data
     */
    public function getHeatmapData(Request $request): JsonResponse
    {
        $request->validate([
            'bounds' => 'required|array',
            'bounds.north' => 'required|numeric|between:-90,90',
            'bounds.south' => 'required|numeric|between:-90,90',
            'bounds.east' => 'required|numeric|between:-180,180',
            'bounds.west' => 'required|numeric|between:-180,180',
            'filters' => 'sometimes|array',
        ]);

        $bounds = $request->input('bounds');
        $filters = $request->input('filters', []);

        // Get alumni data for heatmap
        $alumni = $this->alumniMapService->getAlumniByLocation($bounds, $filters);
        
        // Convert to heatmap format
        $heatmapData = $alumni->map(function ($alumnus) {
            return [
                'lat' => (float) $alumnus->latitude,
                'lng' => (float) $alumnus->longitude,
                'intensity' => 1 // Could be weighted by various factors
            ];
        });

        return response()->json($heatmapData);
    }

    /**
     * Search alumni by location and other criteria
     */
    public function searchAlumni(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'radius' => 'sometimes|integer|min:1|max:500',
            'filters' => 'sometimes|array',
        ]);

        // This would implement a more sophisticated search
        // For now, return basic location-based results
        
        if ($request->has(['latitude', 'longitude'])) {
            $userId = auth()->id();
            $radius = $request->input('radius', 50);
            $nearbyAlumni = $this->alumniMapService->findNearbyAlumni($userId, $radius);
            
            // Filter by search query
            $query = strtolower($request->input('query'));
            $filtered = $nearbyAlumni->filter(function ($alumnus) use ($query) {
                return str_contains(strtolower($alumnus->first_name . ' ' . $alumnus->last_name), $query) ||
                       str_contains(strtolower($alumnus->current_company ?? ''), $query) ||
                       str_contains(strtolower($alumnus->current_position ?? ''), $query);
            });
            
            return response()->json($filtered->values());
        }

        return response()->json([]);
    }
}