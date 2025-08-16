<?php

namespace Tests\Unit;

use App\Models\SavedSearch;
use App\Models\SearchAlert;
use App\Models\User;
use App\Services\ElasticsearchService;
use Elasticsearch\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ElasticsearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $elasticsearchClient;

    protected $elasticsearchService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->elasticsearchClient = Mockery::mock(Client::class);

        // Mock the service with the mocked client
        $this->elasticsearchService = Mockery::mock(ElasticsearchService::class)->makePartial();
        $this->elasticsearchService->shouldAllowMockingProtectedMethods();
        $this->elasticsearchService
            ->shouldReceive('createClient')
            ->andReturn($this->elasticsearchClient);
    }

    public function test_index_user_creates_proper_document()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'bio' => 'Software engineer with 5 years experience',
            'skills' => ['PHP', 'JavaScript', 'Vue.js'],
            'location' => 'San Francisco',
            'industry' => 'Technology',
            'graduation_year' => 2020,
            'school' => 'Stanford University',
        ]);

        $expectedDocument = [
            'id' => $user->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'bio' => 'Software engineer with 5 years experience',
            'skills' => ['PHP', 'JavaScript', 'Vue.js'],
            'location' => 'San Francisco',
            'industry' => 'Technology',
            'graduation_year' => 2020,
            'school' => 'Stanford University',
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
            'privacy_settings' => [],
        ];

        $this->elasticsearchClient
            ->shouldReceive('index')
            ->once()
            ->with(Mockery::on(function ($params) use ($user, $expectedDocument) {
                return $params['index'] === config('elasticsearch.indices.alumni.name') &&
                       $params['id'] === $user->id &&
                       $params['body']['name'] === $expectedDocument['name'] &&
                       $params['body']['email'] === $expectedDocument['email'];
            }))
            ->andReturn(['result' => 'created']);

        $service = new ElasticsearchService;
        $result = $service->indexUser($user);

        $this->assertTrue($result);
    }

    public function test_index_user_respects_privacy_settings()
    {
        $user = User::factory()->create([
            'privacy_settings' => ['searchable' => false],
        ]);

        $this->elasticsearchClient
            ->shouldReceive('delete')
            ->once()
            ->with(Mockery::on(function ($params) use ($user) {
                return $params['index'] === config('elasticsearch.indices.alumni.name') &&
                       $params['id'] === $user->id;
            }))
            ->andReturn(['result' => 'deleted']);

        $service = new ElasticsearchService;
        $result = $service->indexUser($user);

        $this->assertTrue($result);
    }

    public function test_search_users_builds_correct_query()
    {
        $query = 'software engineer';
        $filters = [
            'graduation_year' => ['min' => 2020, 'max' => 2023],
            'location' => 'San Francisco',
            'industry' => ['Technology'],
        ];

        $expectedResponse = [
            'hits' => [
                'hits' => [
                    [
                        '_source' => ['id' => 1, 'name' => 'John Doe'],
                        '_score' => 1.5,
                        'highlight' => ['name' => ['<mark>John</mark> Doe']],
                    ],
                ],
                'total' => ['value' => 1],
            ],
            'aggregations' => [
                'graduation_years' => [
                    'buckets' => [
                        ['key' => 2023, 'doc_count' => 5],
                        ['key' => 2022, 'doc_count' => 8],
                    ],
                ],
            ],
        ];

        $this->elasticsearchClient
            ->shouldReceive('search')
            ->once()
            ->with(Mockery::on(function ($params) use ($query) {
                $body = $params['body'];

                // Check if multi_match query is present
                $hasMultiMatch = isset($body['query']['bool']['must'][0]['multi_match']);
                $queryMatches = $hasMultiMatch && $body['query']['bool']['must'][0]['multi_match']['query'] === $query;

                // Check if filters are applied
                $hasFilters = isset($body['query']['bool']['filter']) && count($body['query']['bool']['filter']) > 0;

                return $queryMatches && $hasFilters;
            }))
            ->andReturn($expectedResponse);

        $service = new ElasticsearchService;
        $result = $service->searchUsers($query, $filters);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total']);
        $this->assertCount(1, $result['users']);
        $this->assertEquals('John Doe', $result['users'][0]['name']);
    }

    public function test_suggest_users_returns_formatted_suggestions()
    {
        $partialQuery = 'joh';

        $expectedResponse = [
            'suggest' => [
                'name_suggest' => [
                    [
                        'options' => [
                            ['text' => 'John Doe', '_score' => 0.9],
                            ['text' => 'John Smith', '_score' => 0.8],
                        ],
                    ],
                ],
                'company_suggest' => [
                    [
                        'options' => [
                            ['text' => 'Johnson & Co', '_score' => 0.7],
                        ],
                    ],
                ],
            ],
        ];

        $this->elasticsearchClient
            ->shouldReceive('search')
            ->once()
            ->with(Mockery::on(function ($params) use ($partialQuery) {
                return isset($params['body']['suggest']) &&
                       $params['body']['suggest']['name_suggest']['prefix'] === $partialQuery;
            }))
            ->andReturn($expectedResponse);

        $service = new ElasticsearchService;
        $result = $service->suggestUsers($partialQuery);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals('John Doe', $result[0]['text']);
        $this->assertEquals('name', $result[0]['type']);
    }

    public function test_save_search_creates_saved_search_record()
    {
        $user = User::factory()->create();
        $query = 'software engineer';
        $filters = ['location' => 'San Francisco'];

        // Mock the search count method
        $this->elasticsearchClient
            ->shouldReceive('search')
            ->once()
            ->andReturn(['hits' => ['total' => ['value' => 25]]]);

        $service = new ElasticsearchService;
        $savedSearch = $service->saveSearch($user, $query, $filters);

        $this->assertInstanceOf(SavedSearch::class, $savedSearch);
        $this->assertEquals($user->id, $savedSearch->user_id);
        $this->assertEquals($query, $savedSearch->query);
        $this->assertEquals($filters, $savedSearch->filters);
        $this->assertEquals(25, $savedSearch->result_count);
    }

    public function test_create_search_alert_creates_alert_record()
    {
        $user = User::factory()->create();
        $savedSearch = SavedSearch::factory()->create(['user_id' => $user->id]);

        $service = new ElasticsearchService;
        $alert = $service->createSearchAlert($user, $savedSearch->id);

        $this->assertInstanceOf(SearchAlert::class, $alert);
        $this->assertEquals($user->id, $alert->user_id);
        $this->assertEquals($savedSearch->id, $alert->saved_search_id);
        $this->assertEquals('daily', $alert->frequency);
        $this->assertTrue($alert->is_active);
    }

    public function test_create_index_creates_proper_mapping()
    {
        $indices = Mockery::mock();
        $indices->shouldReceive('exists')
            ->once()
            ->with(['index' => config('elasticsearch.indices.alumni.name')])
            ->andReturn(false);

        $indices->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($params) {
                $mapping = $params['body']['mappings'];

                // Check if essential fields are mapped correctly
                return isset($mapping['properties']['name']['type']) &&
                       $mapping['properties']['name']['type'] === 'text' &&
                       isset($mapping['properties']['location_coordinates']['type']) &&
                       $mapping['properties']['location_coordinates']['type'] === 'geo_point' &&
                       isset($mapping['properties']['education_history']['type']) &&
                       $mapping['properties']['education_history']['type'] === 'nested';
            }))
            ->andReturn(['acknowledged' => true]);

        $this->elasticsearchClient
            ->shouldReceive('indices')
            ->twice()
            ->andReturn($indices);

        $service = new ElasticsearchService;
        $result = $service->createIndex();

        $this->assertTrue($result);
    }

    public function test_remove_user_deletes_from_index()
    {
        $user = User::factory()->create();

        $this->elasticsearchClient
            ->shouldReceive('delete')
            ->once()
            ->with([
                'index' => config('elasticsearch.indices.alumni.name'),
                'id' => $user->id,
            ])
            ->andReturn(['result' => 'deleted']);

        $service = new ElasticsearchService;
        $result = $service->removeUser($user);

        $this->assertTrue($result);
    }

    public function test_get_search_result_count_returns_correct_count()
    {
        $query = 'engineer';
        $filters = ['location' => 'NYC'];

        $this->elasticsearchClient
            ->shouldReceive('search')
            ->once()
            ->with(Mockery::on(function ($params) {
                return $params['body']['size'] === 0; // Count query should have size 0
            }))
            ->andReturn(['hits' => ['total' => ['value' => 42]]]);

        $service = new ElasticsearchService;
        $count = $service->getSearchResultCount($query, $filters);

        $this->assertEquals(42, $count);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
