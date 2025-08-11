<?php

namespace Tests\Feature;

use App\Models\SavedSearch;
use App\Models\SearchAlert;
use App\Models\User;
use App\Services\ElasticsearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class AdvancedSearchTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $elasticsearchService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Elasticsearch service for testing
        $this->elasticsearchService = Mockery::mock(ElasticsearchService::class);
        $this->app->instance(ElasticsearchService::class, $this->elasticsearchService);
    }

    public function test_user_can_perform_basic_search()
    {
        $user = User::factory()->create();

        $this->elasticsearchService
            ->shouldReceive('searchUsers')
            ->once()
            ->with('john doe', [], ['size' => 20, 'from' => 0])
            ->andReturn([
                'users' => collect([
                    ['id' => 1, 'name' => 'John Doe', 'company' => 'Tech Corp'],
                    ['id' => 2, 'name' => 'Jane Doe', 'company' => 'StartupCo'],
                ]),
                'total' => 2,
                'aggregations' => [],
                'suggestions' => [],
            ]);

        $response = $this->actingAs($user)
            ->getJson('/api/search?query=john doe');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 2,
                    'page' => 1,
                    'size' => 20,
                ],
            ]);
    }

    public function test_user_can_search_with_filters()
    {
        $user = User::factory()->create();

        $filters = [
            'graduation_year' => ['min' => 2020, 'max' => 2023],
            'location' => 'New York',
            'industry' => ['Technology', 'Finance'],
        ];

        $this->elasticsearchService
            ->shouldReceive('searchUsers')
            ->once()
            ->with('', $filters, ['size' => 20, 'from' => 0])
            ->andReturn([
                'users' => collect([]),
                'total' => 0,
                'aggregations' => [
                    'graduation_years' => [
                        ['key' => 2023, 'count' => 15],
                        ['key' => 2022, 'count' => 20],
                    ],
                ],
                'suggestions' => [],
            ]);

        $response = $this->actingAs($user)
            ->getJson('/api/search?'.http_build_query(['filters' => $filters]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 0,
                ],
            ]);
    }

    public function test_user_can_get_search_suggestions()
    {
        $user = User::factory()->create();

        $this->elasticsearchService
            ->shouldReceive('suggestUsers')
            ->once()
            ->with('joh')
            ->andReturn([
                ['text' => 'John Doe', 'type' => 'name', 'score' => 0.9],
                ['text' => 'Johnson & Co', 'type' => 'company', 'score' => 0.7],
            ]);

        $response = $this->actingAs($user)
            ->getJson('/api/search/suggestions?query=joh');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    ['text' => 'John Doe', 'type' => 'name'],
                    ['text' => 'Johnson & Co', 'type' => 'company'],
                ],
            ]);
    }

    public function test_user_can_save_search()
    {
        $user = User::factory()->create();

        $this->elasticsearchService
            ->shouldReceive('saveSearch')
            ->once()
            ->with($user, 'software engineer', ['location' => 'San Francisco'])
            ->andReturn(SavedSearch::factory()->make([
                'id' => 1,
                'user_id' => $user->id,
                'name' => 'Software Engineers in SF',
                'query' => 'software engineer',
                'filters' => ['location' => 'San Francisco'],
            ]));

        $response = $this->actingAs($user)
            ->postJson('/api/search/save', [
                'name' => 'Software Engineers in SF',
                'query' => 'software engineer',
                'filters' => ['location' => 'San Francisco'],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Search saved successfully',
            ]);
    }

    public function test_user_can_save_search_with_alert()
    {
        $user = User::factory()->create();

        $savedSearch = SavedSearch::factory()->make([
            'id' => 1,
            'user_id' => $user->id,
        ]);

        $this->elasticsearchService
            ->shouldReceive('saveSearch')
            ->once()
            ->andReturn($savedSearch);

        $this->elasticsearchService
            ->shouldReceive('createSearchAlert')
            ->once()
            ->with($user, 1)
            ->andReturn(SearchAlert::factory()->make([
                'user_id' => $user->id,
                'saved_search_id' => 1,
                'frequency' => 'weekly',
            ]));

        $response = $this->actingAs($user)
            ->postJson('/api/search/save', [
                'name' => 'Test Search',
                'query' => 'test',
                'filters' => [],
                'create_alert' => true,
                'alert_frequency' => 'weekly',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Search saved successfully',
            ]);
    }

    public function test_user_can_get_saved_searches()
    {
        $user = User::factory()->create();

        $savedSearches = SavedSearch::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/search/saved');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_delete_saved_search()
    {
        $user = User::factory()->create();

        $savedSearch = SavedSearch::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/search/saved/{$savedSearch->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Saved search deleted successfully',
            ]);

        $this->assertDatabaseMissing('saved_searches', [
            'id' => $savedSearch->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_saved_search()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $savedSearch = SavedSearch::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/search/saved/{$savedSearch->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Saved search not found',
            ]);
    }

    public function test_user_can_update_search_alert()
    {
        $user = User::factory()->create();

        $savedSearch = SavedSearch::factory()->create([
            'user_id' => $user->id,
        ]);

        $alert = SearchAlert::factory()->create([
            'user_id' => $user->id,
            'saved_search_id' => $savedSearch->id,
            'frequency' => 'daily',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/search/alerts/{$alert->id}", [
                'frequency' => 'weekly',
                'is_active' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Search alert updated successfully',
            ]);

        $alert->refresh();
        $this->assertEquals('weekly', $alert->frequency);
        $this->assertFalse($alert->is_active);
    }

    public function test_search_requires_authentication()
    {
        $response = $this->getJson('/api/search?query=test');
        $response->assertStatus(401);
    }

    public function test_search_validates_input()
    {
        $user = User::factory()->create();

        // Test invalid graduation year
        $response = $this->actingAs($user)
            ->getJson('/api/search?'.http_build_query([
                'filters' => [
                    'graduation_year' => [
                        'min' => 1800, // Too early
                        'max' => 2050,  // Too late
                    ],
                ],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['filters.graduation_year.min', 'filters.graduation_year.max']);
    }

    public function test_save_search_validates_input()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/search/save', [
                'query' => '', // Empty query
                'alert_frequency' => 'invalid', // Invalid frequency
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query', 'alert_frequency']);
    }

    public function test_suggestions_require_minimum_query_length()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/search/suggestions?query=a'); // Too short

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
