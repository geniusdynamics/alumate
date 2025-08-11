<?php

namespace App\Services;

use App\Models\SavedSearch;
use App\Models\SearchAlert;
use App\Models\User;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

class ElasticsearchService
{
    protected Client $client;

    protected string $alumniIndex;

    protected array $config;

    public function __construct()
    {
        $this->config = config('elasticsearch');
        $this->alumniIndex = $this->config['indices']['alumni']['index'];
        // Temporarily disable client creation until Elasticsearch is properly installed
        // $this->client = $this->createClient();
    }

    /**
     * Create Elasticsearch client
     */
    protected function createClient(): Client
    {
        return ClientBuilder::create()
            ->setHosts($this->config['hosts'])
            ->setRetries($this->config['retries'])
            ->build();
    }

    /**
     * Index user data for search
     */
    public function indexUser(User $user): bool
    {
        try {
            // Check if user allows being searchable
            if (! $user->privacy_settings['searchable'] ?? true) {
                return $this->removeUser($user);
            }

            $body = $this->prepareUserDocument($user);

            $params = [
                'index' => $this->alumniIndex,
                'id' => $user->id,
                'body' => $body,
            ];

            $response = $this->client->index($params);

            Log::info('User indexed successfully', [
                'user_id' => $user->id,
                'result' => $response['result'] ?? 'unknown',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to index user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Remove user from search index
     */
    public function removeUser(User $user): bool
    {
        try {
            $params = [
                'index' => $this->alumniIndex,
                'id' => $user->id,
            ];

            $this->client->delete($params);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove user from index', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Perform search with filters and pagination
     */
    public function searchUsers(string $query, array $filters = [], array $pagination = []): array
    {
        try {
            $searchParams = $this->buildSearchParams($query, $filters, $pagination);
            $response = $this->client->search($searchParams);

            return $this->formatSearchResponse($response, $pagination);
        } catch (\Exception $e) {
            Log::error('Search failed', [
                'query' => $query,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            return [
                'users' => [],
                'total' => 0,
                'aggregations' => [],
                'suggestions' => [],
            ];
        }
    }

    /**
     * Provide search suggestions for autocomplete
     */
    public function suggestUsers(string $partialQuery): array
    {
        if (strlen($partialQuery) < 2) {
            return [];
        }

        try {
            $params = [
                'index' => $this->alumniIndex,
                'body' => [
                    'suggest' => [
                        'name_suggest' => [
                            'prefix' => $partialQuery,
                            'completion' => [
                                'field' => 'name.autocomplete',
                                'size' => 10,
                            ],
                        ],
                        'company_suggest' => [
                            'prefix' => $partialQuery,
                            'completion' => [
                                'field' => 'company.autocomplete',
                                'size' => 10,
                            ],
                        ],
                    ],
                ],
            ];

            $response = $this->client->search($params);

            return $this->formatSuggestions($response);
        } catch (\Exception $e) {
            Log::error('Suggestions failed', [
                'query' => $partialQuery,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Save search for future alerts
     */
    public function saveSearch(User $user, string $query, array $filters): SavedSearch
    {
        return SavedSearch::create([
            'user_id' => $user->id,
            'name' => $this->generateSearchName($query, $filters),
            'query' => $query,
            'filters' => $filters,
            'result_count' => $this->getSearchResultCount($query, $filters),
        ]);
    }

    /**
     * Create search alert for saved search
     */
    public function createSearchAlert(User $user, int $searchId): SearchAlert
    {
        $savedSearch = SavedSearch::findOrFail($searchId);

        return SearchAlert::create([
            'user_id' => $user->id,
            'saved_search_id' => $searchId,
            'frequency' => 'daily', // default frequency
            'is_active' => true,
            'last_sent_at' => null,
        ]);
    }

    /**
     * Get search results count for a query
     */
    public function getSearchResultCount(string $query, array $filters = []): int
    {
        try {
            $searchParams = $this->buildSearchParams($query, $filters, ['size' => 0]);
            $response = $this->client->search($searchParams);

            return $response['hits']['total']['value'] ?? 0;
        } catch (\Exception $e) {
            Log::error('Failed to get search count', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Create or update index with proper mapping
     */
    public function createIndex(): bool
    {
        try {
            $indexConfig = $this->config['indices']['alumni'];

            $params = [
                'index' => $this->alumniIndex,
                'body' => [
                    'settings' => $indexConfig['settings'],
                    'mappings' => $indexConfig['mappings'],
                ],
            ];

            // Delete index if it exists
            if ($this->client->indices()->exists(['index' => $this->alumniIndex])) {
                $this->client->indices()->delete(['index' => $this->alumniIndex]);
            }

            $this->client->indices()->create($params);

            Log::info('Elasticsearch index created successfully', [
                'index' => $this->alumniIndex,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create index', [
                'index' => $this->alumniIndex,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Prepare user document for indexing
     */
    protected function prepareUserDocument(User $user): array
    {
        $document = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'bio' => $user->bio ?? '',
            'skills' => $user->skills ?? [],
            'location' => $user->location ?? '',
            'industry' => $user->industry ?? '',
            'graduation_year' => $user->graduation_year,
            'school' => $user->school ?? '',
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
            'privacy_settings' => $user->privacy_settings ?? [],
        ];

        // Add current job information
        if ($user->currentJob) {
            $document['company'] = $user->currentJob->company ?? '';
            $document['title'] = $user->currentJob->title ?? '';
        }

        // Add location coordinates if available
        if ($user->latitude && $user->longitude) {
            $document['location_coordinates'] = [
                'lat' => $user->latitude,
                'lon' => $user->longitude,
            ];
        }

        // Add education history
        if ($user->educations) {
            $document['education_history'] = $user->educations->map(function ($education) {
                return [
                    'school_name' => $education->school_name,
                    'degree' => $education->degree,
                    'field_of_study' => $education->field_of_study,
                    'graduation_year' => $education->graduation_year,
                    'start_year' => $education->start_year,
                ];
            })->toArray();
        }

        // Add work experience
        if ($user->workExperiences) {
            $document['work_experience'] = $user->workExperiences->map(function ($experience) {
                return [
                    'company' => $experience->company,
                    'title' => $experience->title,
                    'industry' => $experience->industry,
                    'start_date' => $experience->start_date?->toISOString(),
                    'end_date' => $experience->end_date?->toISOString(),
                    'is_current' => $experience->is_current,
                ];
            })->toArray();
        }

        return $document;
    }

    /**
     * Build search parameters for Elasticsearch
     */
    protected function buildSearchParams(string $query, array $filters, array $pagination): array
    {
        $size = $pagination['size'] ?? $this->config['search']['default_size'];
        $from = $pagination['from'] ?? 0;

        $searchParams = [
            'index' => $this->alumniIndex,
            'body' => [
                'size' => min($size, $this->config['search']['max_size']),
                'from' => $from,
                'query' => $this->buildQuery($query, $filters),
                'highlight' => $this->config['search']['highlight'],
                'aggs' => $this->buildAggregations(),
            ],
        ];

        return $searchParams;
    }

    /**
     * Build Elasticsearch query
     */
    protected function buildQuery(string $query, array $filters): array
    {
        $must = [];
        $filter = [];

        // Main search query
        if (! empty($query)) {
            $must[] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => [
                        'name^'.$this->config['search']['boost']['name'],
                        'company^'.$this->config['search']['boost']['company'],
                        'title^'.$this->config['search']['boost']['title'],
                        'skills^'.$this->config['search']['boost']['skills'],
                        'bio^'.$this->config['search']['boost']['bio'],
                    ],
                    'fuzziness' => $this->config['search']['fuzziness'],
                    'operator' => 'and',
                ],
            ];
        } else {
            $must[] = ['match_all' => new \stdClass];
        }

        // Apply filters
        if (! empty($filters['graduation_year'])) {
            if (is_array($filters['graduation_year'])) {
                $filter[] = [
                    'range' => [
                        'graduation_year' => [
                            'gte' => $filters['graduation_year']['min'] ?? 1950,
                            'lte' => $filters['graduation_year']['max'] ?? date('Y'),
                        ],
                    ],
                ];
            } else {
                $filter[] = ['term' => ['graduation_year' => $filters['graduation_year']]];
            }
        }

        if (! empty($filters['location'])) {
            $filter[] = ['term' => ['location.keyword' => $filters['location']]];
        }

        if (! empty($filters['industry'])) {
            $filter[] = ['terms' => ['industry' => (array) $filters['industry']]];
        }

        if (! empty($filters['company'])) {
            $filter[] = ['term' => ['company.keyword' => $filters['company']]];
        }

        if (! empty($filters['school'])) {
            $filter[] = ['term' => ['school.keyword' => $filters['school']]];
        }

        if (! empty($filters['skills'])) {
            $filter[] = ['terms' => ['skills.keyword' => (array) $filters['skills']]];
        }

        // Geographic filter
        if (! empty($filters['location_radius']) && ! empty($filters['location_center'])) {
            $filter[] = [
                'geo_distance' => [
                    'distance' => $filters['location_radius'],
                    'location_coordinates' => $filters['location_center'],
                ],
            ];
        }

        // Privacy filter - only show searchable users
        $filter[] = ['term' => ['privacy_settings.searchable' => true]];

        return [
            'bool' => [
                'must' => $must,
                'filter' => $filter,
            ],
        ];
    }

    /**
     * Build aggregations for faceted search
     */
    protected function buildAggregations(): array
    {
        return [
            'graduation_years' => [
                'terms' => [
                    'field' => 'graduation_year',
                    'size' => 50,
                    'order' => ['_key' => 'desc'],
                ],
            ],
            'locations' => [
                'terms' => [
                    'field' => 'location.keyword',
                    'size' => 20,
                ],
            ],
            'industries' => [
                'terms' => [
                    'field' => 'industry',
                    'size' => 20,
                ],
            ],
            'companies' => [
                'terms' => [
                    'field' => 'company.keyword',
                    'size' => 20,
                ],
            ],
            'schools' => [
                'terms' => [
                    'field' => 'school.keyword',
                    'size' => 20,
                ],
            ],
            'skills' => [
                'terms' => [
                    'field' => 'skills.keyword',
                    'size' => 30,
                ],
            ],
        ];
    }

    /**
     * Format search response
     */
    protected function formatSearchResponse(array $response, array $pagination): array
    {
        $hits = $response['hits']['hits'] ?? [];
        $total = $response['hits']['total']['value'] ?? 0;
        $aggregations = $response['aggregations'] ?? [];

        $users = collect($hits)->map(function ($hit) {
            $source = $hit['_source'];
            $source['highlight'] = $hit['highlight'] ?? [];
            $source['score'] = $hit['_score'];

            return $source;
        });

        return [
            'users' => $users,
            'total' => $total,
            'aggregations' => $this->formatAggregations($aggregations),
            'suggestions' => [],
        ];
    }

    /**
     * Format aggregations for frontend
     */
    protected function formatAggregations(array $aggregations): array
    {
        $formatted = [];

        foreach ($aggregations as $key => $aggregation) {
            $formatted[$key] = collect($aggregation['buckets'] ?? [])->map(function ($bucket) {
                return [
                    'key' => $bucket['key'],
                    'count' => $bucket['doc_count'],
                ];
            });
        }

        return $formatted;
    }

    /**
     * Format suggestions response
     */
    protected function formatSuggestions(array $response): array
    {
        $suggestions = [];

        foreach ($response['suggest'] ?? [] as $suggestionType => $suggestionData) {
            foreach ($suggestionData as $suggestion) {
                foreach ($suggestion['options'] ?? [] as $option) {
                    $suggestions[] = [
                        'text' => $option['text'],
                        'type' => str_replace('_suggest', '', $suggestionType),
                        'score' => $option['_score'] ?? 0,
                    ];
                }
            }
        }

        return collect($suggestions)
            ->sortByDesc('score')
            ->unique('text')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * Generate a descriptive name for saved search
     */
    protected function generateSearchName(string $query, array $filters): string
    {
        $parts = [];

        if (! empty($query)) {
            $parts[] = "\"$query\"";
        }

        if (! empty($filters['location'])) {
            $parts[] = "in {$filters['location']}";
        }

        if (! empty($filters['industry'])) {
            $industry = is_array($filters['industry']) ? implode(', ', $filters['industry']) : $filters['industry'];
            $parts[] = "in $industry";
        }

        if (! empty($filters['graduation_year'])) {
            if (is_array($filters['graduation_year'])) {
                $parts[] = "graduated {$filters['graduation_year']['min']}-{$filters['graduation_year']['max']}";
            } else {
                $parts[] = "graduated {$filters['graduation_year']}";
            }
        }

        return implode(' ', $parts) ?: 'All Alumni';
    }
}
