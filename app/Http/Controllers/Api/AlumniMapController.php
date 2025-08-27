<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AlumniMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AlumniMapController extends Controller
{
    public function __construct(
        private AlumniMapService $alumniMapService
    ) {}

    /**
     * Get alumni data for map visualization
     */
    public function getMapData(Request $request): JsonResponse
    {
        $request->validate([
            'filters' => 'array',
            'filters.graduation_year' => 'nullable|integer|min:1900|max:'.(date('Y') + 10),
            'filters.school_id' => 'nullable|integer|exists:schools,id',
            'filters.industry' => 'nullable|string|max:100',
            'filters.country' => 'nullable|string|max:100',
            'filters.region' => 'nullable|string|max:100',
            'privacy_filter' => ['nullable', Rule::in(['all', 'public', 'alumni_only'])],
        ]);

        try {
            $filters = $request->input('filters', []);
            $privacyFilter = $request->input('privacy_filter', 'all');

            // Apply privacy filter to the query
            if ($privacyFilter !== 'all') {
                $filters['privacy_filter'] = $privacyFilter;
            }

            $alumni = $this->alumniMapService->getAlumniWithLocations($filters);

            return response()->json([
                'success' => true,
                'alumni' => $alumni->map(function ($alumnus) {
                    return [
                        'id' => $alumnus->id,
                        'name' => $alumnus->name,
                        'avatar_url' => $alumnus->avatar_url,
                        'current_title' => $alumnus->current_title,
                        'current_company' => $alumnus->current_company,
                        'location' => $alumnus->location,
                        'latitude' => (float) $alumnus->latitude,
                        'longitude' => (float) $alumnus->longitude,
                        'location_privacy' => $alumnus->location_privacy ?? 'alumni_only',
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get alumni map data', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'filters' => $request->input('filters', []),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load alumni map data',
            ], 500);
        }
    }

    /**
     * Get clustered alumni data for performance
     */
    public function getClusters(Request $request): JsonResponse
    {
        $request->validate([
            'bounds' => 'required|array',
            'bounds.north' => 'required|numeric|between:-90,90',
            'bounds.south' => 'required|numeric|between:-90,90',
            'bounds.east' => 'required|numeric|between:-180,180',
            'bounds.west' => 'required|numeric|between:-180,180',
            'zoom_level' => 'required|integer|min:1|max:20',
            'filters' => 'array',
            'privacy_filter' => ['nullable', Rule::in(['all', 'public', 'alumni_only'])],
        ]);

        try {
            $bounds = $request->input('bounds');
            $zoomLevel = $request->input('zoom_level');
            $filters = $request->input('filters', []);
            $privacyFilter = $request->input('privacy_filter', 'all');

            $clusters = $this->alumniMapService->getClusteredAlumni(
                $bounds['north'],
                $bounds['south'],
                $bounds['east'],
                $bounds['west'],
                $zoomLevel
            );

            return response()->json([
                'success' => true,
                'clusters' => $clusters,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get alumni clusters', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'bounds' => $request->input('bounds'),
                'zoom_level' => $request->input('zoom_level'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load alumni clusters',
            ], 500);
        }
    }

    /**
     * Get regional statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->alumniMapService->getRegionalStats();

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get alumni map stats', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load statistics',
            ], 500);
        }
    }

    /**
     * Get nearby alumni
     */
    public function getNearby(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|integer|min:1|max:1000',
        ]);

        try {
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $radiusKm = $request->input('radius_km', 50);

            $nearbyAlumni = $this->alumniMapService->getNearbyAlumni(
                $latitude,
                $longitude,
                $radiusKm
            );

            return response()->json([
                'success' => true,
                'alumni' => $nearbyAlumni->map(function ($alumnus) {
                    return [
                        'id' => $alumnus->id,
                        'name' => $alumnus->name,
                        'avatar_url' => $alumnus->avatar_url,
                        'current_title' => $alumnus->current_title,
                        'current_company' => $alumnus->current_company,
                        'location' => $alumnus->location,
                        'latitude' => (float) $alumnus->latitude,
                        'longitude' => (float) $alumnus->longitude,
                        'distance' => round($alumnus->distance, 2),
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get nearby alumni', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to find nearby alumni',
            ], 500);
        }
    }

    /**
     * Update user location privacy settings
     */
    public function updateLocationPrivacy(Request $request): JsonResponse
    {
        $request->validate([
            'location_privacy' => ['required', Rule::in(['public', 'alumni_only', 'private'])],
        ]);

        try {
            $user = Auth::user();
            $privacyLevel = $request->input('location_privacy');

            $success = $this->alumniMapService->updateLocationPrivacy($user, $privacyLevel);

            if (! $success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid privacy level',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Location privacy updated successfully',
                'location_privacy' => $privacyLevel,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update location privacy', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'privacy_level' => $request->input('location_privacy'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update location privacy',
            ], 500);
        }
    }

    /**
     * Update user location
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        try {
            $user = Auth::user();
            $address = $request->input('address');
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');

            // If coordinates not provided, try to geocode the address
            if (! $latitude || ! $longitude) {
                $coordinates = $this->geocodeAddress($address);
                if ($coordinates) {
                    $latitude = $coordinates['latitude'];
                    $longitude = $coordinates['longitude'];
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to geocode the provided address',
                    ], 400);
                }
            }

            // Update user location
            $user->update([
                'location' => $address,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'location_updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'location' => [
                    'address' => $address,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update user location', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'address' => $request->input('address'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update location',
            ], 500);
        }
    }

    /**
     * Reverse geocode coordinates to address
     */
    public function reverseGeocode(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        try {
            $latitude = $request->input('lat');
            $longitude = $request->input('lng');

            $address = $this->reverseGeocodeCoordinates($latitude, $longitude);

            if (! $address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to reverse geocode coordinates',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'address' => $address,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reverse geocode', [
                'error' => $e->getMessage(),
                'latitude' => $request->input('lat'),
                'longitude' => $request->input('lng'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reverse geocode coordinates',
            ], 500);
        }
    }

    /**
     * Geocode address to coordinates using OpenStreetMap Nominatim
     */
    private function geocodeAddress(string $address): ?array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Alumni Platform Map Service',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 1,
                ]);

            if ($response->successful() && $response->json()) {
                $data = $response->json();
                if (! empty($data)) {
                    return [
                        'latitude' => (float) $data[0]['lat'],
                        'longitude' => (float) $data[0]['lon'],
                    ];
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Geocoding failed', [
                'error' => $e->getMessage(),
                'address' => $address,
            ]);

            return null;
        }
    }

    /**
     * Reverse geocode coordinates to address using OpenStreetMap Nominatim
     */
    private function reverseGeocodeCoordinates(float $latitude, float $longitude): ?string
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Alumni Platform Map Service',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'json',
                    'addressdetails' => 1,
                ]);

            if ($response->successful() && $response->json()) {
                $data = $response->json();
                if (isset($data['display_name'])) {
                    return $data['display_name'];
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Reverse geocoding failed', [
                'error' => $e->getMessage(),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            return null;
        }
    }
}
