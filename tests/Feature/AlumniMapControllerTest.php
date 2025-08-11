<?php

namespace Tests\Feature;

use App\Models\Graduate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AlumniMapControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_get_alumni_by_location()
    {
        Graduate::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'profile_visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/alumni/map', [
                'bounds' => [
                    'north' => 45.0,
                    'south' => 35.0,
                    'east' => -70.0,
                    'west' => -80.0,
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'first_name',
                    'last_name',
                    'latitude',
                    'longitude',
                    'profile_visibility',
                ],
            ]);
    }

    public function test_can_get_location_clusters()
    {
        Graduate::factory()->count(5)->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'profile_visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/alumni/map/clusters', [
                'zoom_level' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'latitude',
                    'longitude',
                    'count',
                ],
            ]);
    }

    public function test_can_get_nearby_alumni()
    {
        // Create the authenticated user as a graduate with location
        $userGraduate = Graduate::factory()->create([
            'user_id' => $this->user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'profile_visibility' => 'public',
        ]);

        // Create nearby alumni
        Graduate::factory()->create([
            'latitude' => 40.7589,
            'longitude' => -73.9851,
            'profile_visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni/nearby?radius=25');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'first_name',
                    'last_name',
                    'distance',
                ],
            ]);
    }

    public function test_can_get_regional_stats()
    {
        Graduate::factory()->count(3)->create([
            'country' => 'United States',
            'industry' => 'Technology',
            'profile_visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/regions/United%20States/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_alumni',
                'industries',
                'graduation_years',
                'top_companies',
                'average_experience',
            ]);
    }

    public function test_can_get_filter_options()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni/filter-options');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'graduation_years',
                'industries',
                'countries',
                'states',
            ]);
    }

    public function test_can_update_user_location()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/alumni/location', [
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'United States',
                'privacy_level' => 'public',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'location' => [
                    'latitude',
                    'longitude',
                    'city',
                    'state',
                    'country',
                ],
            ]);

        $this->user->refresh();
        $this->assertEquals(40.7128, $this->user->latitude);
        $this->assertEquals(-74.0060, $this->user->longitude);
    }

    public function test_validates_location_bounds()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/alumni/map', [
                'bounds' => [
                    'north' => 95.0, // Invalid latitude
                    'south' => 35.0,
                    'east' => -70.0,
                    'west' => -80.0,
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bounds.north']);
    }

    public function test_validates_zoom_level()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/alumni/map/clusters', [
                'zoom_level' => 25, // Invalid zoom level
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['zoom_level']);
    }

    public function test_can_search_alumni()
    {
        Graduate::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'current_company' => 'Google',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'profile_visibility' => 'public',
        ]);

        // Create user graduate for location-based search
        Graduate::factory()->create([
            'user_id' => $this->user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'profile_visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni/search?query=John&latitude=40.7128&longitude=-74.0060&radius=50');

        $response->assertStatus(200);
    }

    public function test_requires_authentication()
    {
        $response = $this->postJson('/api/alumni/map', [
            'bounds' => [
                'north' => 45.0,
                'south' => 35.0,
                'east' => -70.0,
                'west' => -80.0,
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_can_get_heatmap_data()
    {
        Graduate::factory()->count(10)->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'profile_visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni/map/heatmap?'.http_build_query([
                'bounds' => [
                    'north' => 45.0,
                    'south' => 35.0,
                    'east' => -70.0,
                    'west' => -80.0,
                ],
            ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'lat',
                    'lng',
                    'intensity',
                ],
            ]);
    }

    public function test_can_get_suggested_groups()
    {
        Graduate::factory()->count(5)->create([
            'city' => 'New York',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'industry' => 'Technology',
            'profile_visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/regions/New%20York/groups?latitude=40.7128&longitude=-74.0060');

        $response->assertStatus(200);
    }

    public function test_filters_work_correctly()
    {
        Graduate::factory()->create([
            'graduation_year' => 2020,
            'industry' => 'Technology',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'profile_visibility' => 'public',
        ]);

        Graduate::factory()->create([
            'graduation_year' => 2021,
            'industry' => 'Healthcare',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'profile_visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/alumni/map', [
                'bounds' => [
                    'north' => 45.0,
                    'south' => 35.0,
                    'east' => -70.0,
                    'west' => -80.0,
                ],
                'filters' => [
                    'graduation_year' => [2020],
                    'industry' => ['Technology'],
                ],
            ]);

        $response->assertStatus(200);

        $alumni = $response->json();
        $this->assertCount(1, $alumni);
        $this->assertEquals(2020, $alumni[0]['graduation_year']);
        $this->assertEquals('Technology', $alumni[0]['industry']);
    }
}
