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
    private Client $client;
    private string $indexPrefix;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts([config('elasticsearch.host', 'localhost:9200')])
            ->build();
        
        $this->indexPrefix = config('elasticsearch.index_prefix', 'alumni_platform');
    }

    /**
     * Search across multiple content types with natural language processing
     */
    public function search(string $query, array $filters = [], int $size = 20, int $from = 0): array
    {
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
     * Get search suggestions based on partial query
     */
    public function getSuggestions(string $query, int $size = 5): array
    {
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
     * Index a user document
     */
    public function indexUser(User $user): bool
    {
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
     * Index a post document
     */
    public function indexPost(Post $post): bool
    {
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

    /**
     * Build search query with natural language processing
     */
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

    /**
     * Build sort configuration
     */
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

    /**
     * Build highlight configuration
     */
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

    /**
     * Build aggregations for faceted search
     */
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

    /**
     * Get search indices based on filters
     */
    private function getSearchIndices(array $filters): string
    {
        $types = $filters['types'] ?? ['users', 'posts', 'jobs', 'events'];
        $indices = array_map(fn($type) => $this->getIndexName($type), $types);
        return implode(',', $indices);
    }

    /**
     * Format search results
     */
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

    /**
     * Format suggestions
     */
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

    /**
     * Get fallback results when Elasticsearch fails
     */
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

    private function getEventMapping(): array
    {
        return [
            'mappings' => [
                'properties' => [
                    'title' => ['type' => 'text', 'analyzer' => 'standard'],
                    'description' => ['type' => 'text', 'analyzer' => 'standard'],
                    'location' => ['type' => 'keyword'],
                    'event_date' => ['type' => 'date'],
                    'created_at' => ['type' => 'date']
                ]
            ]
        ];
    }
}