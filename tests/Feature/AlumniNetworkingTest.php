<?php

namespace Tests\Feature;

use App\Models\Circle;
use App\Models\Connection;
use App\Models\Education;
use App\Models\Group;
use App\Models\Institution;
use App\Models\User;
use App\Models\WorkExperience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AlumniNetworkingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected User $alumni1;

    protected User $alumni2;

    protected User $alumni3;

    protected Institution $institution;

    protected Circle $circle;

    protected Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create([
            'name' => 'Test University',
        ]);

        $this->user = User::factory()->create();
        $this->alumni1 = User::factory()->create([
            'name' => 'John Doe',
            'location' => 'New York, NY',
            'bio' => 'Software Engineer passionate about AI and machine learning',
        ]);
        $this->alumni2 = User::factory()->create([
            'name' => 'Jane Smith',
            'location' => 'San Francisco, CA',
            'bio' => 'Product Manager with expertise in fintech',
        ]);
        $this->alumni3 = User::factory()->create([
            'name' => 'Mike Johnson',
            'location' => 'Austin, TX',
            'bio' => 'Startup founder and entrepreneur',
        ]);

        // Create education records
        Education::factory()->create([
            'user_id' => $this->user->id,
            'institution_id' => $this->institution->id,
            'graduation_year' => 2020,
            'degree' => 'Bachelor of Science',
            'field_of_study' => 'Computer Science',
        ]);

        Education::factory()->create([
            'user_id' => $this->alumni1->id,
            'institution_id' => $this->institution->id,
            'graduation_year' => 2019,
            'degree' => 'Bachelor of Science',
            'field_of_study' => 'Computer Science',
        ]);

        Education::factory()->create([
            'user_id' => $this->alumni2->id,
            'institution_id' => $this->institution->id,
            'graduation_year' => 2018,
            'degree' => 'Master of Business Administration',
            'field_of_study' => 'Business Administration',
        ]);

        // Create work experiences
        WorkExperience::factory()->create([
            'user_id' => $this->alumni1->id,
            'company' => 'Google',
            'title' => 'Senior Software Engineer',
            'industry' => 'Technology',
            'is_current' => true,
        ]);

        WorkExperience::factory()->create([
            'user_id' => $this->alumni2->id,
            'company' => 'Stripe',
            'title' => 'Senior Product Manager',
            'industry' => 'Fintech',
            'is_current' => true,
        ]);

        // Set up circles and groups
        $this->circle = Circle::factory()->create([
            'name' => 'Test University Class of 2019-2020',
            'type' => 'school_year',
        ]);

        $this->group = Group::factory()->create([
            'name' => 'Computer Science Alumni',
            'type' => 'academic',
            'institution_id' => $this->institution->id,
        ]);

        // Add users to circles and groups
        $this->user->circles()->attach($this->circle->id);
        $this->alumni1->circles()->attach($this->circle->id);

        $this->user->groups()->attach($this->group->id);
        $this->alumni1->groups()->attach($this->group->id);
    }

    public function test_alumni_directory_search_and_filtering()
    {
        // Test basic directory listing
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'location',
                        'bio',
                        'avatar_url',
                        'educations',
                        'work_experiences',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        // Test search by name
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?search=John');

        $response->assertStatus(200);
        $alumni = $response->json('data');
        $this->assertCount(1, $alumni);
        $this->assertEquals('John Doe', $alumni[0]['name']);

        // Test filter by graduation year
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?graduation_year_from=2019&graduation_year_to=2019');

        $response->assertStatus(200);
        $alumni = $response->json('data');
        $this->assertCount(1, $alumni);
        $this->assertEquals('John Doe', $alumni[0]['name']);

        // Test filter by location
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?location=San Francisco');

        $response->assertStatus(200);
        $alumni = $response->json('data');
        $this->assertCount(1, $alumni);
        $this->assertEquals('Jane Smith', $alumni[0]['name']);

        // Test filter by company
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?company=Google');

        $response->assertStatus(200);
        $alumni = $response->json('data');
        $this->assertCount(1, $alumni);
        $this->assertEquals('John Doe', $alumni[0]['name']);

        // Test filter by industry
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?industry=Technology');

        $response->assertStatus(200);
        $alumni = $response->json('data');
        $this->assertCount(1, $alumni);
        $this->assertEquals('John Doe', $alumni[0]['name']);
    }

    public function test_connection_request_workflow()
    {
        // Send connection request
        $response = $this->actingAs($this->user)
            ->postJson("/api/alumni/{$this->alumni1->id}/connect", [
                'message' => 'Hi John! I saw we both studied Computer Science at Test University. Would love to connect!',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Connection request sent successfully',
            ]);

        // Verify connection request was created
        $this->assertDatabaseHas('connections', [
            'user_id' => $this->user->id,
            'connected_user_id' => $this->alumni1->id,
            'status' => 'pending',
        ]);

        // Test duplicate request prevention
        $response = $this->actingAs($this->user)
            ->postJson("/api/alumni/{$this->alumni1->id}/connect", [
                'message' => 'Another request',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Connection already exists',
            ]);

        // Accept connection request
        $connection = Connection::where('user_id', $this->user->id)
            ->where('connected_user_id', $this->alumni1->id)
            ->first();

        $response = $this->actingAs($this->alumni1)
            ->postJson("/api/connections/{$connection->id}/accept");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Connection request accepted',
            ]);

        // Verify connection status updated
        $this->assertDatabaseHas('connections', [
            'id' => $connection->id,
            'status' => 'accepted',
        ]);

        // Test connection status in profile
        $response = $this->actingAs($this->user)
            ->getJson("/api/alumni/{$this->alumni1->id}");

        $response->assertStatus(200);
        $profile = $response->json('data');
        $this->assertEquals('accepted', $profile['connection_status']);
    }

    public function test_alumni_profile_with_mutual_connections()
    {
        // Create mutual connections
        $mutualConnection = User::factory()->create();

        // User connects to mutual connection
        $this->user->sendConnectionRequest($mutualConnection);
        $mutualConnection->acceptConnectionRequest(
            $mutualConnection->receivedConnectionRequests()->first()->id
        );

        // Alumni1 connects to mutual connection
        $this->alumni1->sendConnectionRequest($mutualConnection);
        $mutualConnection->acceptConnectionRequest(
            $mutualConnection->receivedConnectionRequests()->first()->id
        );

        // View alumni1's profile
        $response = $this->actingAs($this->user)
            ->getJson("/api/alumni/{$this->alumni1->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'bio',
                    'location',
                    'educations',
                    'work_experiences',
                    'mutual_connections',
                    'shared_circles',
                    'shared_groups',
                    'connection_status',
                ],
            ]);

        $profile = $response->json('data');
        $this->assertCount(1, $profile['mutual_connections']);
        $this->assertEquals($mutualConnection->id, $profile['mutual_connections'][0]['id']);
    }

    public function test_shared_circles_and_groups_display()
    {
        // View alumni1's profile (they share circle and group)
        $response = $this->actingAs($this->user)
            ->getJson("/api/alumni/{$this->alumni1->id}");

        $response->assertStatus(200);
        $profile = $response->json('data');

        // Should show shared circles
        $this->assertCount(1, $profile['shared_circles']);
        $this->assertEquals($this->circle->id, $profile['shared_circles'][0]['id']);

        // Should show shared groups
        $this->assertCount(1, $profile['shared_groups']);
        $this->assertEquals($this->group->id, $profile['shared_groups'][0]['id']);

        // View alumni2's profile (no shared circles/groups)
        $response = $this->actingAs($this->user)
            ->getJson("/api/alumni/{$this->alumni2->id}");

        $response->assertStatus(200);
        $profile = $response->json('data');

        $this->assertCount(0, $profile['shared_circles']);
        $this->assertCount(0, $profile['shared_groups']);
    }

    public function test_alumni_recommendations_based_on_connections()
    {
        // Create a network: user -> alumni1 -> alumni3
        $this->user->sendConnectionRequest($this->alumni1);
        $this->alumni1->acceptConnectionRequest(
            $this->alumni1->receivedConnectionRequests()->first()->id
        );

        $this->alumni1->sendConnectionRequest($this->alumni3);
        $this->alumni3->acceptConnectionRequest(
            $this->alumni3->receivedConnectionRequests()->first()->id
        );

        // Get recommendations for user
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni/recommendations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'avatar_url',
                        'mutual_connections_count',
                        'shared_circles_count',
                        'shared_groups_count',
                        'recommendation_reason',
                    ],
                ],
            ]);

        $recommendations = $response->json('data');

        // Should recommend alumni3 (connected through alumni1)
        $alumni3Recommendation = collect($recommendations)
            ->firstWhere('id', $this->alumni3->id);

        $this->assertNotNull($alumni3Recommendation);
        $this->assertEquals(1, $alumni3Recommendation['mutual_connections_count']);
    }

    public function test_connection_management_workflow()
    {
        // Create some connections
        $this->user->sendConnectionRequest($this->alumni1);
        $this->alumni1->acceptConnectionRequest(
            $this->alumni1->receivedConnectionRequests()->first()->id
        );

        $this->alumni2->sendConnectionRequest($this->user);

        // Get user's connections
        $response = $this->actingAs($this->user)
            ->getJson('/api/connections');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'accepted' => [
                        '*' => [
                            'id',
                            'name',
                            'avatar_url',
                            'connected_at',
                        ],
                    ],
                    'pending_sent' => [],
                    'pending_received' => [
                        '*' => [
                            'id',
                            'name',
                            'avatar_url',
                            'message',
                            'created_at',
                        ],
                    ],
                ],
            ]);

        $connections = $response->json('data');

        // Should have one accepted connection
        $this->assertCount(1, $connections['accepted']);
        $this->assertEquals($this->alumni1->id, $connections['accepted'][0]['id']);

        // Should have one pending received request
        $this->assertCount(1, $connections['pending_received']);
        $this->assertEquals($this->alumni2->id, $connections['pending_received'][0]['id']);

        // Reject pending request
        $pendingConnection = Connection::where('user_id', $this->alumni2->id)
            ->where('connected_user_id', $this->user->id)
            ->first();

        $response = $this->actingAs($this->user)
            ->postJson("/api/connections/{$pendingConnection->id}/reject");

        $response->assertStatus(200);

        // Verify connection was rejected
        $this->assertDatabaseHas('connections', [
            'id' => $pendingConnection->id,
            'status' => 'rejected',
        ]);
    }

    public function test_alumni_search_with_advanced_filters()
    {
        // Create alumni with specific skills
        $this->alumni1->update([
            'profile_data' => [
                'skills' => ['PHP', 'Laravel', 'Vue.js', 'Machine Learning'],
            ],
        ]);

        $this->alumni2->update([
            'profile_data' => [
                'skills' => ['Product Management', 'Fintech', 'Strategy'],
            ],
        ]);

        // Test skill-based search
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?skills[]=PHP&skills[]=Laravel');

        $response->assertStatus(200);
        $alumni = $response->json('data');
        $this->assertCount(1, $alumni);
        $this->assertEquals('John Doe', $alumni[0]['name']);

        // Test combined filters
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?graduation_year_from=2018&graduation_year_to=2019&industry=Technology');

        $response->assertStatus(200);
        $alumni = $response->json('data');
        $this->assertCount(1, $alumni);
        $this->assertEquals('John Doe', $alumni[0]['name']);

        // Test sorting
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?sort_by=name&sort_order=desc');

        $response->assertStatus(200);
        $alumni = $response->json('data');

        // Should be sorted by name descending
        $names = collect($alumni)->pluck('name')->toArray();
        $sortedNames = $names;
        rsort($sortedNames);
        $this->assertEquals($sortedNames, $names);
    }

    public function test_alumni_directory_privacy_controls()
    {
        // Set alumni1 profile to private
        $this->alumni1->update([
            'profile_data' => [
                'privacy_settings' => [
                    'profile_visibility' => 'connections_only',
                ],
            ],
        ]);

        // Non-connected user should not see full profile
        $response = $this->actingAs($this->user)
            ->getJson("/api/alumni/{$this->alumni1->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'This profile is private',
            ]);

        // Connect users
        $this->user->sendConnectionRequest($this->alumni1);
        $this->alumni1->acceptConnectionRequest(
            $this->alumni1->receivedConnectionRequests()->first()->id
        );

        // Connected user should see full profile
        $response = $this->actingAs($this->user)
            ->getJson("/api/alumni/{$this->alumni1->id}");

        $response->assertStatus(200);
        $profile = $response->json('data');
        $this->assertEquals($this->alumni1->id, $profile['id']);
        $this->assertArrayHasKey('work_experiences', $profile);
    }

    public function test_bulk_connection_operations()
    {
        // Create multiple alumni
        $alumni = User::factory()->count(5)->create();

        // Send multiple connection requests
        $connectionData = $alumni->take(3)->map(function ($alumnus) {
            return [
                'user_id' => $alumnus->id,
                'message' => "Hi {$alumnus->name}, let's connect!",
            ];
        })->toArray();

        $response = $this->actingAs($this->user)
            ->postJson('/api/connections/bulk-request', [
                'connections' => $connectionData,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Connection requests sent successfully',
                'sent_count' => 3,
            ]);

        // Verify all requests were created
        foreach ($alumni->take(3) as $alumnus) {
            $this->assertDatabaseHas('connections', [
                'user_id' => $this->user->id,
                'connected_user_id' => $alumnus->id,
                'status' => 'pending',
            ]);
        }
    }

    public function test_alumni_activity_and_engagement_tracking()
    {
        // Connect users
        $this->user->sendConnectionRequest($this->alumni1);
        $this->alumni1->acceptConnectionRequest(
            $this->alumni1->receivedConnectionRequests()->first()->id
        );

        // Track profile views
        $response = $this->actingAs($this->user)
            ->getJson("/api/alumni/{$this->alumni1->id}");

        $response->assertStatus(200);

        // Verify profile view was tracked
        $this->assertDatabaseHas('profile_views', [
            'viewer_id' => $this->user->id,
            'viewed_user_id' => $this->alumni1->id,
        ]);

        // Get profile view analytics
        $response = $this->actingAs($this->alumni1)
            ->getJson('/api/profile/analytics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_views',
                    'recent_views',
                    'top_viewers',
                ],
            ]);

        $analytics = $response->json('data');
        $this->assertGreaterThan(0, $analytics['total_views']);
    }
}
