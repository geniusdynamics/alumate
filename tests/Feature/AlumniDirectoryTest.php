<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Institution;
use App\Models\Education;
use App\Models\WorkExperience;
use App\Models\Connection;
use App\Models\Circle;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class AlumniDirectoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test institutions
        $this->institution = Institution::factory()->create([
            'name' => 'Test University'
        ]);
        
        // Create test users
        $this->user = User::factory()->create();
        $this->alumni1 = User::factory()->create([
            'name' => 'John Doe',
            'location' => 'New York, NY',
            'skills' => ['PHP', 'Laravel', 'Vue.js']
        ]);
        $this->alumni2 = User::factory()->create([
            'name' => 'Jane Smith',
            'location' => 'San Francisco, CA',
            'skills' => ['JavaScript', 'React', 'Node.js']
        ]);
        
        // Create education records
        Education::factory()->create([
            'user_id' => $this->alumni1->id,
            'institution_id' => $this->institution->id,
            'graduation_year' => 2020
        ]);
        
        Education::factory()->create([
            'user_id' => $this->alumni2->id,
            'institution_id' => $this->institution->id,
            'graduation_year' => 2019
        ]);
        
        // Create work experiences
        WorkExperience::factory()->create([
            'user_id' => $this->alumni1->id,
            'company' => 'Tech Corp',
            'title' => 'Software Engineer',
            'industry' => 'Technology',
            'is_current' => true
        ]);
        
        WorkExperience::factory()->create([
            'user_id' => $this->alumni2->id,
            'company' => 'StartupXYZ',
            'title' => 'Frontend Developer',
            'industry' => 'Technology',
            'is_current' => true
        ]);
    }  
  public function test_can_get_alumni_directory_listing()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'location',
                        'avatar_url',
                        'educations',
                        'work_experiences',
                        'skills'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total'
                ]
            ]);
    }
    
    public function test_can_filter_alumni_by_graduation_year()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?graduation_year_from=2020&graduation_year_to=2020');
        
        $response->assertStatus(200);
        
        $alumni = $response->json('data');
        $this->assertCount(1, $alumni);
        $this->assertEquals('John Doe', $alumni[0]['name']);
    }
    
    public function test_can_filter_alumni_by_location()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?location=New York');
        
        $response->assertStatus(200);
        
        $alumni = $response->json('data');
        $this->assertCount(1, $alumni);
        $this->assertEquals('John Doe', $alumni[0]['name']);
    }
    
    public function test_can_filter_alumni_by_company()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?company=Tech Corp');
        
        $response->assertStatus(200);
        
        $alumni = $response->json('data');
        $this->assertCount(1, $alumni);
        $this->assertEquals('John Doe', $alumni[0]['name']);
    }
    
    public function test_can_filter_alumni_by_skills()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?skills[]=PHP&skills[]=Laravel');
        
        $response->assertStatus(200);
        
        $alumni = $response->json('data');
        $this->assertCount(1, $alumni);
        $this->assertEquals('John Doe', $alumni[0]['name']);
    }
    
    public function test_can_search_alumni_by_name()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?search=John');
        
        $response->assertStatus(200);
        
        $alumni = $response->json('data');
        $this->assertCount(1, $alumni);
        $this->assertEquals('John Doe', $alumni[0]['name']);
    }
    
    public function test_can_get_alumni_profile()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/alumni/{$this->alumni1->id}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'location',
                    'bio',
                    'educations',
                    'work_experiences',
                    'skills',
                    'mutual_connections',
                    'shared_circles',
                    'shared_groups',
                    'connection_status'
                ]
            ]);
    }
    
    public function test_can_get_available_filters()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni/filters');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'graduation_years',
                    'locations',
                    'industries',
                    'companies',
                    'skills',
                    'institutions',
                    'circles',
                    'groups'
                ]
            ]);
    }
    
    public function test_can_send_connection_request()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/alumni/{$this->alumni1->id}/connect", [
                'message' => 'Hi, I would like to connect with you!'
            ]);
        
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Connection request sent successfully'
            ]);
        
        $this->assertDatabaseHas('connections', [
            'user_id' => $this->user->id,
            'connected_user_id' => $this->alumni1->id,
            'status' => 'pending'
        ]);
    }
    
    public function test_cannot_send_duplicate_connection_request()
    {
        // Create existing connection
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $this->alumni1->id,
            'status' => 'pending'
        ]);
        
        $response = $this->actingAs($this->user)
            ->postJson("/api/alumni/{$this->alumni1->id}/connect", [
                'message' => 'Hi, I would like to connect with you!'
            ]);
        
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Connection already exists'
            ]);
    }
    
    public function test_cannot_connect_to_self()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/alumni/{$this->user->id}/connect", [
                'message' => 'Hi, I would like to connect with you!'
            ]);
        
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Cannot connect to yourself'
            ]);
    }
    
    public function test_can_search_for_suggestions()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni/search?query=John&type=name');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'avatar_url'
                    ]
                ]
            ]);
    }
    
    public function test_pagination_works_correctly()
    {
        // Create more alumni for pagination test
        User::factory()->count(25)->create();
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?per_page=10&page=1');
        
        $response->assertStatus(200);
        
        $meta = $response->json('meta');
        $this->assertEquals(1, $meta['current_page']);
        $this->assertEquals(10, $meta['per_page']);
        $this->assertGreaterThan(1, $meta['last_page']);
    }
    
    public function test_sorting_works_correctly()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?sort_by=name&sort_order=asc');
        
        $response->assertStatus(200);
        
        $alumni = $response->json('data');
        $names = array_column($alumni, 'name');
        $sortedNames = $names;
        sort($sortedNames);
        
        $this->assertEquals($sortedNames, $names);
    }
}