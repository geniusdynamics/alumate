<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Graduate;
use App\Services\AlumniMapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class AlumniMapServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private AlumniMapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AlumniMapService::class);
    }

    public function test_can_get_alumni_by_location_bounds()
    {
        // Create test alumni with coordinates
        Graduate::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'city' => 'New York',
            'state' => 'NY',
            'country' => 'United States',
            'profile_visibility' => 'public'
        ]);

        Graduate::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'latitude' => 34.0522,
            'longitude' => -118.2437,
            'city' => 'Los Angeles',
            'state' => 'CA',
            'country' => 'United States',
            'profile_visibility' => 'alumni_only'
        ]);

        // Create alumni outside bounds
        Graduate::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'city' => 'London',
            'country' => 'United Kingdom',
            'profile_visibility' => 'public'
        ]);

        $bounds = [
            'north' => 45.0,
            'south' => 30.0,
            'east' => -70.0,
            'west' => -125.0
        ];

        $alumni = $this->service->getAlumniByLocation($bounds);

        $this->assertCount(2, $alumni);
        $this->assertTrue($alumni->contains('first_name', 'John'));
        $this->assertTrue($alumni->contains('first_name', 'Jane'));
        $this->assertFalse($alumni->contains('first_name', 'Bob'));
    }

    public function test_can_filter_alumni_by_graduation_year()
    {
        Graduate::factory()->create([
            'graduation_year' => 2020,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'profile_visibility' => 'public'
        ]);

        Graduate::factory()->create([
            'graduation_year' => 2021,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'profile_visibility' => 'public'
        ]);

        $bounds = [
            'north' => 45.0,
            'south' => 35.0,
            'east' => -70.0,
            'west' => -80.0
        ];

        $filters = ['graduation_year' => [2020]];
        $alumni = $this->service->getAlumniByLocation($bounds, $filters);

        $this->assertCount(1, $alumni);
        $this->assertEquals(2020, $alumni->first()->graduation_year);
    }

    public function test_can_get_location_clusters()
    {
        // Create multiple alumni in similar locations
        for ($i = 0; $i < 5; $i++) {
            Graduate::factory()->create([
                'latitude' => 40.7128 + ($i * 0.01), // Slight variations
                'longitude' => -74.0060 + ($i * 0.01),
                'industry' => 'Technology',
                'graduation_year' => 2020 + $i,
                'country' => 'United States',
                'profile_visibility' => 'public'
            ]);
        }

        $clusters = $this->service->getLocationClusters(5);

        $this->assertGreaterThan(0, $clusters->count());
        
        $firstCluster = $clusters->first();
        $this->assertArrayHasKey('latitude', $firstCluster);
        $this->assertArrayHasKey('longitude', $firstCluster);
        $this->assertArrayHasKey('count', $firstCluster);
        $this->assertGreaterThan(0, $firstCluster['count']);
    }

    public function test_can_get_regional_stats()
    {
        Graduate::factory()->count(3)->create([
            'country' => 'United States',
            'industry' => 'Technology',
            'graduation_year' => 2020,
            'current_company' => 'Google',
            'profile_visibility' => 'public'
        ]);

        Graduate::factory()->count(2)->create([
            'country' => 'United States',
            'industry' => 'Healthcare',
            'graduation_year' => 2021,
            'current_company' => 'Hospital Corp',
            'profile_visibility' => 'public'
        ]);

        $stats = $this->service->getRegionalStats('United States', 'country');

        $this->assertEquals(5, $stats['total_alumni']);
        $this->assertArrayHasKey('industries', $stats);
        $this->assertArrayHasKey('graduation_years', $stats);
        $this->assertArrayHasKey('top_companies', $stats);
        $this->assertArrayHasKey('average_experience', $stats);

        $this->assertEquals(3, $stats['industries']['Technology']);
        $this->assertEquals(2, $stats['industries']['Healthcare']);
    }

    public function test_can_find_nearby_alumni()
    {
        $user = Graduate::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'profile_visibility' => 'public'
        ]);

        // Create nearby alumni
        Graduate::factory()->create([
            'latitude' => 40.7589, // ~5km away
            'longitude' => -73.9851,
            'profile_visibility' => 'public'
        ]);

        // Create far away alumni
        Graduate::factory()->create([
            'latitude' => 34.0522, // Los Angeles - far away
            'longitude' => -118.2437,
            'profile_visibility' => 'public'
        ]);

        $nearbyAlumni = $this->service->findNearbyAlumni($user->id, 25);

        $this->assertCount(1, $nearbyAlumni);
        $this->assertNotEquals($user->id, $nearbyAlumni->first()->id);
    }

    public function test_can_suggest_regional_groups()
    {
        // Create alumni in the same city with different industries
        Graduate::factory()->count(5)->create([
            'city' => 'New York',
            'state' => 'NY',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'industry' => 'Technology',
            'profile_visibility' => 'public'
        ]);

        Graduate::factory()->count(3)->create([
            'city' => 'New York',
            'state' => 'NY',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'industry' => 'Finance',
            'profile_visibility' => 'public'
        ]);

        $location = [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'radius' => 50
        ];

        $suggestions = $this->service->suggestRegionalGroups($location);

        $this->assertGreaterThan(0, $suggestions->count());
        
        // Should suggest both city-based and industry-based groups
        $groupTypes = $suggestions->pluck('type')->unique();
        $this->assertTrue($groupTypes->contains('city') || $groupTypes->contains('industry'));
    }

    public function test_can_calculate_distance_between_coordinates()
    {
        // Distance between New York and Los Angeles (approximately 3944 km)
        $distance = $this->service->calculateDistance(
            40.7128, -74.0060, // New York
            34.0522, -118.2437  // Los Angeles
        );

        $this->assertGreaterThan(3900, $distance);
        $this->assertLessThan(4000, $distance);
    }

    public function test_excludes_private_profiles()
    {
        Graduate::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'profile_visibility' => 'private'
        ]);

        Graduate::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'profile_visibility' => 'public'
        ]);

        $bounds = [
            'north' => 45.0,
            'south' => 35.0,
            'east' => -70.0,
            'west' => -80.0
        ];

        $alumni = $this->service->getAlumniByLocation($bounds);

        $this->assertCount(1, $alumni);
        $this->assertEquals('public', $alumni->first()->profile_visibility);
    }

    public function test_respects_alumni_limit()
    {
        // Create more than 1000 alumni
        Graduate::factory()->count(1200)->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'profile_visibility' => 'public'
        ]);

        $bounds = [
            'north' => 45.0,
            'south' => 35.0,
            'east' => -70.0,
            'west' => -80.0
        ];

        $alumni = $this->service->getAlumniByLocation($bounds);

        $this->assertLessThanOrEqual(1000, $alumni->count());
    }
}