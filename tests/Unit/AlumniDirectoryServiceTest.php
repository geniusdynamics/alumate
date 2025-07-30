<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AlumniDirectoryService;
use App\Models\User;
use App\Models\Institution;
use App\Models\Education;
use App\Models\WorkExperience;
use App\Models\Connection;
use App\Models\Circle;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class AlumniDirectoryServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected AlumniDirectoryService $service;
    protected User $user;
    protected User $alumni;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new AlumniDirectoryService();
        
        $this->user = User::factory()->create();
        $this->alumni = User::factory()->create([
            'name' => 'Test Alumni',
            'location' => 'Test City',
            'skills' => ['PHP', 'Laravel']
        ]);
        
        $institution = Institution::factory()->create();
        
        Education::factory()->create([
            'user_id' => $this->alumni->id,
            'institution_id' => $institution->id,
            'graduation_year' => 2020
        ]);
        
        WorkExperience::factory()->create([
            'user_id' => $this->alumni->id,
            'company' => 'Test Company',
            'title' => 'Software Engineer',
            'industry' => 'Technology',
            'is_current' => true
        ]);
    }

    public function test_can_build_basic_filter_query()
    {
        $filters = ['search' => 'Test'];
        $query = $this->service->buildFilterQuery($filters);
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }

    public function test_can_filter_by_graduation_year_range()
    {
        $filters = [
            'graduation_year_from' => 2020,
            'graduation_year_to' => 2020
        ];
        
        $result = $this->service->getFilteredAlumni($filters);
        
        $this->assertCount(1, $result->items());
        $this->assertEquals('Test Alumni', $result->items()[0]->name);
    }

    public function test_can_filter_by_location()
    {
        $filters = ['location' => 'Test City'];
        
        $result = $this->service->getFilteredAlumni($filters);
        
        $this->assertCount(1, $result->items());
        $this->assertEquals('Test Alumni', $result->items()[0]->name);
    }

    public function test_can_filter_by_company()
    {
        $filters = ['company' => 'Test Company'];
        
        $result = $this->service->getFilteredAlumni($filters);
        
        $this->assertCount(1, $result->items());
        $this->assertEquals('Test Alumni', $result->items()[0]->name);
    }

    public function test_can_filter_by_skills()
    {
        $filters = ['skills' => ['PHP']];
        
        $result = $this->service->getFilteredAlumni($filters);
        
        $this->assertCount(1, $result->items());
        $this->assertEquals('Test Alumni', $result->items()[0]->name);
    }

    public function test_can_get_alumni_profile_with_privacy_controls()
    {
        $profile = $this->service->getAlumniProfile($this->alumni->id, $this->user);
        
        $this->assertNotNull($profile);
        $this->assertEquals('Test Alumni', $profile->name);
        $this->assertIsArray($profile->mutual_connections);
        $this->assertIsArray($profile->shared_circles);
        $this->assertIsArray($profile->shared_groups);
        $this->assertIsString($profile->connection_status);
    }

    public function test_can_get_mutual_connections()
    {
        // Create a mutual connection
        $mutualFriend = User::factory()->create();
        
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $mutualFriend->id,
            'status' => 'accepted'
        ]);
        
        Connection::create([
            'user_id' => $this->alumni->id,
            'connected_user_id' => $mutualFriend->id,
            'status' => 'accepted'
        ]);
        
        $mutualConnections = $this->service->getMutualConnections($this->alumni, $this->user);
        
        $this->assertCount(1, $mutualConnections);
        $this->assertEquals($mutualFriend->id, $mutualConnections[0]['id']);
    }

    public function test_can_get_connection_status()
    {
        // Test no connection
        $status = $this->service->getConnectionStatus($this->alumni, $this->user);
        $this->assertEquals('none', $status);
        
        // Test pending connection
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $this->alumni->id,
            'status' => 'pending'
        ]);
        
        $status = $this->service->getConnectionStatus($this->alumni, $this->user);
        $this->assertEquals('pending', $status);
        
        // Test self
        $status = $this->service->getConnectionStatus($this->user, $this->user);
        $this->assertEquals('self', $status);
    }

    public function test_can_get_available_filters()
    {
        $filters = $this->service->getAvailableFilters();
        
        $this->assertIsArray($filters);
        $this->assertArrayHasKey('graduation_years', $filters);
        $this->assertArrayHasKey('locations', $filters);
        $this->assertArrayHasKey('industries', $filters);
        $this->assertArrayHasKey('companies', $filters);
        $this->assertArrayHasKey('skills', $filters);
        $this->assertArrayHasKey('institutions', $filters);
        $this->assertArrayHasKey('circles', $filters);
        $this->assertArrayHasKey('groups', $filters);
    }

    public function test_pagination_works_correctly()
    {
        // Create additional alumni
        User::factory()->count(25)->create();
        
        $result = $this->service->getFilteredAlumni([], ['per_page' => 10, 'page' => 1]);
        
        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(1, $result->currentPage());
        $this->assertGreaterThan(1, $result->lastPage());
    }

    public function test_sorting_works_correctly()
    {
        // Create alumni with different names
        User::factory()->create(['name' => 'Alice Smith']);
        User::factory()->create(['name' => 'Bob Johnson']);
        
        $result = $this->service->getFilteredAlumni([
            'sort_by' => 'name',
            'sort_order' => 'asc'
        ]);
        
        $names = $result->pluck('name')->toArray();
        $sortedNames = $names;
        sort($sortedNames);
        
        $this->assertEquals($sortedNames, $names);
    }
}