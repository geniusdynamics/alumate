<?php

namespace App\Services;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Client;
use App\Models\User;
use App\Models\Post;
use App\Models\Job;
use App\Models\Event;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ElasticsearchService
{
    private ?Client $client;
    private string $indexPrefix;

    public function __construct()
    {
        try {
            if (class_exists(\Elasticsearch\ClientBuilder::class)) {
                $this->client = ClientBuilder::create()
                    ->setHosts([config('elasticsearch.host', 'localhost:9200')])
                    ->build();
            } else {
                $this->client = null;
            }
        } catch (\Exception $e) {
            $this->client = null;
            \Log::warning('Elasticsearch client could not be initialized: ' . $e->getMessage());
        }
        
        $this->indexPrefix = config('elasticsearch.index_prefix', 'alumni_platform');
    }

    /**
     * Search users specifically with formatted response
     */
    public function searchUsers(string $query, array $filters = [], array $options = []): array
    {
        if (!$this->client) {
            return $this->getFallbackUserResults($query, $filters, $options);
        }
        
        $size = $options['size'] ?? 20;
        $from = $options['from'] ?? 0;
        
        $searchParams = [
            'index' => $this->getIndexName('users'),
            'body' => [
                'query' => $this->buildUserSearchQuery($query, $filters),
                'sort' => $this->buildSort($filters),
                'highlight' => $this->buildHighlight(),
                'aggs' => $this->buildUserAggregations(),
                'size' => $size,
                'from' => $from
            ]
        ];

        try {
            $response = $this->client->search($searchParams);
            return $this->formatUserSearchResults($response);
        } catch (\Exception $e) {
            Log::error('Elasticsearch user search failed', [
                'query' => $query,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            return $this->getFallbackUserResults($query, $filters, $options);
        }
    }

    /**
     * Index a user document
     */
    public function indexUser(User $user): bool
    {
        if (!$this->client) {
            return true; // Silently succeed if Elasticsearch is not available
        }
        
        $params = [
            'index' => $this->getIndexName('users'),
            'id' => $user->id,
            'body' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'bio' => $user->bio,
                'location' => $user->location,
                'current_position' => $user->current_position,
                'current_company' => $user->current_company,
                'skills' => $user->skills ?? [],
                'industries' => $user->industries ?? [],
                'graduation_year' => $user->graduation_year,
                'school' => $user->school,
                'degree' => $user->degree,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
                'privacy_settings' => $user->privacy_settings ?? [],
                'name_suggest' => [
                    'input' => [$user->name, $user->email],
                    'weight' => 10
                ],
                'skills_suggest' => [
                    'input' => $user->skills ?? [],
                    'weight' => 5
                ]
            ]
        ];

        try {
            $this->client->index($params);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to index user', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Update user index (alias for indexUser)
     */
    public function updateUserIndex(User $user): bool
    {
        return $this->indexUser($user);
    }
    
    /**
     * Remove user from index
     */
    public function removeUser(User $user): bool
    {
        if (!$this->client) {
            return true; // Silently succeed if Elasticsearch is not available
        }
        
        $params = [
            'index' => $this->getIndexName('users'),
            'id' => $user->id
        ];

        try {
            $this->client->delete($params);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove user from index', [
                'user_id' => $user->id, 
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get search suggestions based on partial query
     */
    public function getSuggestions(string $query, int $size = 5): array
    {
        if (!$this->client) {
            return [];
        }
        
        $params = [
            'index' => $this->getIndexName('users'),
            'body' => [
                'suggest' => [
                    'name_suggest' => [
                        'prefix' => $query,
                        'completion' => [
                            'field' => 'name_suggest',
                            'size' => $size
                        ]
                    ],
                    'skill_suggest' => [
                        'prefix' => $query,
                        'completion' => [
                            'field' => 'skills_suggest',
                            'size' => $size
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = $this->client->search($params);
            return $this->formatSuggestions($response);
        } catch (\Exception $e) {
            Log::error('Elasticsearch suggestions failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Search across multiple content types with natural language processing
     */
    public function search(string $query, array $filters = [], int $size = 20, int $from = 0): array
    {
        if (!$this->client) {
            return $this->getFallbackResults($query, $filters, $size, $from);
        }
        
        $searchParams = [
            'index' => $this->getSearchIndices($filters),
            'body' => [
                'query' => $this->buildSearchQuery($query, $filters),
                'sort' => $this->buildSort($filters),
                'highlight' => $this->buildHighlight(),
                'aggs' => $this->buildAggregations(),
                'size' => $size,
                'from' => $from
            ]
        ];

        try {
            $response = $this->client->search($searchParams);
            return $this->formatSearchResults($response);
        } catch (\Exception $e) {
            Log::error('Elasticsearch search failed', [
                'query' => $query,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            return $this->getFallbackResults($query, $filters, $size, $from);
        }
    }

    /**
     * Index a post document
     */
    public function indexPost(Post $post): bool
    {
        if (!$this->client) {
            return true; // Silently succeed if Elasticsearch is not available
        }
        
        $params = [
            'index' => $this->getIndexName('posts'),
            'id' => $post->id,
            'body' => [
                'id' => $post->id,
                'user_id' => $post->user_id,
                'user_name' => $post->user->name,
                'content' => $post->content,
                'post_type' => $post->post_type,
                'tags' => $post->tags ?? [],
                'engagement_count' => $post->engagement_count ?? 0,
                'created_at' => $post->created_at->toISOString(),
                'updated_at' => $post->updated_at->toISOString()
            ]
        ];

        try {
            $this->client->index($params);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to index post', ['post_id' => $post->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Create search indices with proper mappings
     */
    public function createIndices(): bool
    {
        if (!$this->client) {
            return false;
        }
        
        $indices = [
            'users' => $this->getUserMapping(),
            'posts' => $this->getPostMapping(),
            'jobs' => $this->getJobMapping(),
            'events' => $this->getEventMapping()
        ];

        foreach ($indices as $type => $mapping) {
            $indexName = $this->getIndexName($type);
            
            if ($this->client->indices()->exists(['index' => $indexName])) {
                continue;
            }

            try {
                $this->client->indices()->create([
                    'index' => $indexName,
                    'body' => $mapping
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to create index {$indexName}", ['error' => $e->getMessage()]);
                return false;
            }
        }

        return true;
    }

    // Private helper methods

    private function buildUserSearchQuery(string $query, array $filters): array
    {
        $must = [];
        $filter = [];

        // Main search query
        if (!empty($query)) {
            $must[] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => [
                        'name^3',
                        'bio^2',
                        'skills^2',
                        'current_position^2',
                        'current_company^2',
                        'location',
                        'school',
                        'degree'
                    ],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO'
                ]
            ];
        }

        // Apply user-specific filters
        if (!empty($filters['location'])) {
            $filter[] = ['term' => ['location.keyword' => $filters['location']]];
        }

        if (!empty($filters['graduation_year'])) {
            if (is_array($filters['graduation_year'])) {
                $yearFilter = [];
                if (isset($filters['graduation_year']['min'])) {
                    $yearFilter['gte'] = $filters['graduation_year']['min'];
                }
                if (isset($filters['graduation_year']['max'])) {
                    $yearFilter['lte'] = $filters['graduation_year']['max'];
                }
                if (!empty($yearFilter)) {
                    $filter[] = ['range' => ['graduation_year' => $yearFilter]];
                }
            } else {
                $filter[] = ['term' => ['graduation_year' => $filters['graduation_year']]];
            }
        }

        if (!empty($filters['industry'])) {
            $filter[] = ['terms' => ['industries' => (array)$filters['industry']]];
        }

        if (!empty($filters['skills'])) {
            $filter[] = ['terms' => ['skills' => (array)$filters['skills']]];
        }

        // Privacy filter - exclude users who opted out
        $filter[] = [
            'bool' => [
                'should' => [
                    ['bool' => ['must_not' => ['exists' => ['field' => 'privacy_settings.searchable']]]],
                    ['term' => ['privacy_settings.searchable' => true]]
                ]
            ]
        ];

        return [
            'bool' => [
                'must' => $must,
                'filter' => $filter
            ]
        ];
    }

    private function buildSearchQuery(string $query, array $filters): array
    {
        $must = [];
        $filter = [];

        // Main search query
        if (!empty($query)) {
            $must[] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => [
                        'name^3',
                        'bio^2',
                        'skills^2',
                        'current_position^2',
                        'current_company^2',
                        'content^1.5',
                        'location',
                        'school',
                        'degree'
                    ],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO'
                ]
            ];
        }

        // Apply filters
        if (!empty($filters['location'])) {
            $filter[] = ['term' => ['location.keyword' => $filters['location']]];
        }

        if (!empty($filters['graduation_year'])) {
            $filter[] = ['term' => ['graduation_year' => $filters['graduation_year']]];
        }

        if (!empty($filters['industry'])) {
            $filter[] = ['terms' => ['industries' => (array)$filters['industry']]];
        }

        if (!empty($filters['skills'])) {
            $filter[] = ['terms' => ['skills' => (array)$filters['skills']]];
        }

        if (!empty($filters['date_range'])) {
            $filter[] = [
                'range' => [
                    'created_at' => [
                        'gte' => $filters['date_range']['from'] ?? 'now-1y',
                        'lte' => $filters['date_range']['to'] ?? 'now'
                    ]
                ]
            ];
        }

        return [
            'bool' => [
                'must' => $must,
                'filter' => $filter
            ]
        ];
    }

    private function buildUserAggregations(): array
    {
        return [
            'graduation_years' => [
                'terms' => ['field' => 'graduation_year', 'size' => 20]
            ],
            'locations' => [
                'terms' => ['field' => 'location.keyword', 'size' => 20]
            ],
            'industries' => [
                'terms' => ['field' => 'industries', 'size' => 20]
            ],
            'skills' => [
                'terms' => ['field' => 'skills', 'size' => 30]
            ],
            'schools' => [
                'terms' => ['field' => 'school.keyword', 'size' => 20]
            ]
        ];
    }

    private function formatUserSearchResults(array $response): array
    {
        return [
            'users' => array_map(function ($hit) {
                return array_merge($hit['_source'], [
                    'score' => $hit['_score'],
                    'highlight' => $hit['highlight'] ?? []
                ]);
            }, $response['hits']['hits']),
            'total' => $response['hits']['total']['value'] ?? 0,
            'aggregations' => $this->formatAggregations($response['aggregations'] ?? []),
            'suggestions' => [],
            'took' => $response['took'] ?? 0
        ];
    }

    private function formatAggregations(array $aggregations): array
    {
        $formatted = [];
        
        foreach ($aggregations as $name => $agg) {
            if (isset($agg['buckets'])) {
                $formatted[$name] = array_map(function ($bucket) {
                    return [
                        'key' => $bucket['key'],
                        'count' => $bucket['doc_count']
                    ];
                }, $agg['buckets']);
            }
        }
        
        return $formatted;
    }

    private function getFallbackUserResults(string $query, array $filters, array $options): array
    {
        $size = $options['size'] ?? 20;
        $from = $options['from'] ?? 0;
        
        $queryBuilder = User::query();
        
        if (!empty($query)) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('bio', 'LIKE', "%{$query}%")
                  ->orWhere('current_position', 'LIKE', "%{$query}%")
                  ->orWhere('current_company', 'LIKE', "%{$query}%");
            });
        }
        
        // Apply filters
        if (!empty($filters['location'])) {
            $queryBuilder->where('location', $filters['location']);
        }
        
        if (!empty($filters['graduation_year'])) {
            if (is_array($filters['graduation_year'])) {
                if (isset($filters['graduation_year']['min'])) {
                    $queryBuilder->where('graduation_year', '>=', $filters['graduation_year']['min']);
                }
                if (isset($filters['graduation_year']['max'])) {
                    $queryBuilder->where('graduation_year', '<=', $filters['graduation_year']['max']);
                }
            } else {
                $queryBuilder->where('graduation_year', $filters['graduation_year']);
            }
        }
        
        $total = $queryBuilder->count();
        $users = $queryBuilder->limit($size)->offset($from)->get();

        return [
            'users' => $users->map(function ($user) {
                return array_merge($user->toArray(), [
                    'score' => 1.0,
                    'highlight' => []
                ]);
            })->toArray(),
            'total' => $total,
            'aggregations' => [],
            'suggestions' => [],
            'took' => 0
        ];
    }

    private function buildSort(array $filters): array
    {
        $sort = [];

        switch ($filters['sort'] ?? 'relevance') {
            case 'date':
                $sort[] = ['created_at' => ['order' => 'desc']];
                break;
            case 'name':
                $sort[] = ['name.keyword' => ['order' => 'asc']];
                break;
            case 'engagement':
                $sort[] = ['engagement_count' => ['order' => 'desc']];
                break;
            default:
                $sort[] = ['_score' => ['order' => 'desc']];
        }

        return $sort;
    }

    private function buildHighlight(): array
    {
        return [
            'fields' => [
                'name' => new \stdClass(),
                'bio' => new \stdClass(),
                'content' => new \stdClass(),
                'skills' => new \stdClass()
            ],
            'pre_tags' => ['<mark>'],
            'post_tags' => ['</mark>']
        ];
    }

    private function buildAggregations(): array
    {
        return [
            'locations' => [
                'terms' => ['field' => 'location.keyword', 'size' => 20]
            ],
            'graduation_years' => [
                'terms' => ['field' => 'graduation_year', 'size' => 20]
            ],
            'industries' => [
                'terms' => ['field' => 'industries', 'size' => 20]
            ],
            'skills' => [
                'terms' => ['field' => 'skills', 'size' => 30]
            ],
            'schools' => [
                'terms' => ['field' => 'school.keyword', 'size' => 20]
            ]
        ];
    }

    private function getSearchIndices(array $filters): string
    {
        $types = $filters['types'] ?? ['users', 'posts', 'jobs', 'events'];
        $indices = array_map(fn($type) => $this->getIndexName($type), $types);
        return implode(',', $indices);
    }

    private function formatSearchResults(array $response): array
    {
        return [
            'hits' => array_map(function ($hit) {
                return [
                    'id' => $hit['_id'],
                    'type' => $this->getTypeFromIndex($hit['_index']),
                    'score' => $hit['_score'],
                    'source' => $hit['_source'],
                    'highlight' => $hit['highlight'] ?? []
                ];
            }, $response['hits']['hits']),
            'total' => $response['hits']['total']['value'],
            'aggregations' => $response['aggregations'] ?? [],
            'took' => $response['took']
        ];
    }

    private function formatSuggestions(array $response): array
    {
        $suggestions = [];
        
        foreach ($response['suggest'] as $suggestionType => $suggestionData) {
            foreach ($suggestionData[0]['options'] as $option) {
                $suggestions[] = [
                    'text' => $option['text'],
                    'score' => $option['_score'],
                    'type' => str_replace('_suggest', '', $suggestionType)
                ];
            }
        }

        return $suggestions;
    }

    private function getFallbackResults(string $query, array $filters, int $size, int $from): array
    {
        // Fallback to database search
        $users = User::where('name', 'LIKE', "%{$query}%")
            ->orWhere('bio', 'LIKE', "%{$query}%")
            ->limit($size)
            ->offset($from)
            ->get();

        return [
            'hits' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'type' => 'user',
                    'score' => 1.0,
                    'source' => $user->toArray(),
                    'highlight' => []
                ];
            })->toArray(),
            'total' => $users->count(),
            'aggregations' => [],
            'took' => 0
        ];
    }

    private function getIndexName(string $type): string
    {
        return "{$this->indexPrefix}_{$type}";
    }

    private function getTypeFromIndex(string $index): string
    {
        return str_replace($this->indexPrefix . '_', '', $index);
    }

    private function getUserMapping(): array
    {
        return [
            'mappings' => [
                'properties' => [
                    'name' => [
                        'type' => 'text',
                        'analyzer' => 'standard',
                        'fields' => ['keyword' => ['type' => 'keyword']]
                    ],
                    'bio' => ['type' => 'text', 'analyzer' => 'standard'],
                    'location' => [
                        'type' => 'text',
                        'fields' => ['keyword' => ['type' => 'keyword']]
                    ],
                    'skills' => ['type' => 'keyword'],
                    'industries' => ['type' => 'keyword'],
                    'graduation_year' => ['type' => 'integer'],
                    'school' => [
                        'type' => 'text',
                        'fields' => ['keyword' => ['type' => 'keyword']]
                    ],
                    'name_suggest' => ['type' => 'completion'],
                    'skills_suggest' => ['type' => 'completion'],
                    'created_at' => ['type' => 'date'],
                    'updated_at' => ['type' => 'date']
                ]
            ]
        ];
    }

    private function getPostMapping(): array
    {
        return [
            'mappings' => [
                'properties' => [
                    'content' => ['type' => 'text', 'analyzer' => 'standard'],
                    'post_type' => ['type' => 'keyword'],
                    'tags' => ['type' => 'keyword'],
                    'engagement_count' => ['type' => 'integer'],
                    'created_at' => ['type' => 'date'],
                    'updated_at' => ['type' => 'date']
                ]
            ]
        ];
    }

    private function getJobMapping(): array
    {
        return [
            'mappings' => [
                'properties' => [
                    'title' => ['type' => 'text', 'analyzer' => 'standard'],
                    'description' => ['type' => 'text', 'analyzer' => 'standard'],
                    'company' => ['type' => 'keyword'],
                    'location' => ['type' => 'keyword'],
                    'skills_required' => ['type' => 'keyword'],
                    'created_at' => ['type' => 'date']
                ]
            ]
        ];
    }

    /**
     * Suggest users based on partial query
     */
    public function suggestUsers(string $partialQuery, int $size = 5): array
    {
        if (!$this->client) {
            return [];
        }
        
        $params = [
            'index' => $this->getIndexName('users'),
            'body' => [
                'suggest' => [
                    'name_suggest' => [
                        'prefix' => $partialQuery,
                        'completion' => [
                            'field' => 'name_suggest',
                            'size' => $size
                        ]
                    ],
                    'company_suggest' => [
                        'prefix' => $partialQuery,
                        'completion' => [
                            'field' => 'skills_suggest',
                            'size' => $size
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = $this->client->search($params);
            return $this->formatUserSuggestions($response);
        } catch (\Exception $e) {
            Log::error('Elasticsearch user suggestions failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Save a search query for later use
     */
    public function saveSearch(User $user, string $query, array $filters = []): \App\Models\SavedSearch
    {
        $resultCount = $this->getSearchResultCount($query, $filters);
        
        return \App\Models\SavedSearch::create([
            'user_id' => $user->id,
            'query' => $query,
            'filters' => $filters,
            'result_count' => $resultCount,
            'name' => 'Search: ' . $query
        ]);
    }

    /**
     * Create a search alert for saved searches
     */
    public function createSearchAlert(User $user, int $savedSearchId, string $frequency = 'daily'): \App\Models\SearchAlert
    {
        return \App\Models\SearchAlert::create([
            'user_id' => $user->id,
            'saved_search_id' => $savedSearchId,
            'frequency' => $frequency,
            'is_active' => true,
            'last_sent_at' => null
        ]);
    }

    /**
     * Create search index with proper mappings
     */
    public function createIndex(): bool
    {
        if (!$this->client) {
            return false;
        }
        
        $indexName = $this->getIndexName('users');
        
        if ($this->client->indices()->exists(['index' => $indexName])) {
            return true;
        }

        try {
            $this->client->indices()->create([
                'index' => $indexName,
                'body' => $this->getAlumniMapping()
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to create index {$indexName}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get search result count without retrieving documents
     */
    public function getSearchResultCount(string $query, array $filters = []): int
    {
        if (!$this->client) {
            return 0;
        }
        
        $searchParams = [
            'index' => $this->getIndexName('users'),
            'body' => [
                'query' => $this->buildUserSearchQuery($query, $filters),
                'size' => 0
            ]
        ];

        try {
            $response = $this->client->search($searchParams);
            return $response['hits']['total']['value'] ?? 0;
        } catch (\Exception $e) {
            Log::error('Elasticsearch count query failed', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    // Private helper methods

    private function formatUserSuggestions(array $response): array
    {
        $suggestions = [];
        
        foreach ($response['suggest'] as $suggestionType => $suggestionData) {
            foreach ($suggestionData[0]['options'] as $option) {
                $suggestions[] = [
                    'text' => $option['text'],
                    'score' => $option['_score'],
                    'type' => str_replace('_suggest', '', $suggestionType)
                ];
            }
        }

        return $suggestions;
    }

    private function getAlumniMapping(): array
    {
        return [
            'mappings' => [
                'properties' => [
                    'name' => [
                        'type' => 'text',
                        'analyzer' => 'standard',
                        'fields' => ['keyword' => ['type' => 'keyword']]
                    ],
                    'bio' => ['type' => 'text', 'analyzer' => 'standard'],
                    'location' => [
                        'type' => 'text',
                        'fields' => ['keyword' => ['type' => 'keyword']]
                    ],
                    'location_coordinates' => ['type' => 'geo_point'],
                    'skills' => ['type' => 'keyword'],
                    'industries' => ['type' => 'keyword'],
                    'graduation_year' => ['type' => 'integer'],
                    'school' => [
                        'type' => 'text',
                        'fields' => ['keyword' => ['type' => 'keyword']]
                    ],
                    'education_history' => [
                        'type' => 'nested',
                        'properties' => [
                            'institution' => ['type' => 'text'],
                            'degree' => ['type' => 'text'],
                            'graduation_year' => ['type' => 'integer']
                        ]
                    ],
                    'name_suggest' => ['type' => 'completion'],
                    'skills_suggest' => ['type' => 'completion'],
                    'created_at' => ['type' => 'date'],
                    'updated_at' => ['type' => 'date']
                ]
            ]
        ];
    }