<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SearchService
{
    private ?Client $client = null;

    public function __construct()
    {
        $this->initializeClient();
    }

    /**
     * Initialize Elasticsearch client.
     */
    private function initializeClient(): void
    {
        try {
            $hosts = config('elasticsearch.hosts', ['http://localhost:9200']);
            
            $this->client = ClientBuilder::create()
                ->setHosts($hosts)
                ->setRetries(config('elasticsearch.retries', 3))
                ->build();
        } catch (\Exception $e) {
            Log::error('Failed to initialize Elasticsearch client', [
                'error' => $e->getMessage(),
            ]);
            $this->client = null;
        }
    }

    /**
     * Check if Elasticsearch is available.
     */
    public function isAvailable(): bool
    {
        if (!$this->client) {
            return false;
        }

        try {
            $response = $this->client->ping();
            return $response->asBool();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create index for a tenant.
     */
    public function createIndex(string $tenantId, string $type = 'alumni'): bool
    {
        if (!$this->client) {
            return false;
        }

        $indexName = $this->getIndexName($tenantId, $type);

        try {
            // Check if index exists
            $exists = $this->client->indices()->exists(['index' => $indexName])->asBool();
            
            if ($exists) {
                return true;
            }

            // Create index with mappings
            $mappings = $this->getMappings($type);
            
            $this->client->indices()->create([
                'index' => $indexName,
                'body' => [
                    'settings' => [
                        'number_of_shards' => 1,
                        'number_of_replicas' => 0,
                        'analysis' => [
                            'analyzer' => [
                                'custom_analyzer' => [
                                    'type' => 'custom',
                                    'tokenizer' => 'standard',
                                    'filter' => ['lowercase', 'asciifolding'],
                                ],
                            ],
                        ],
                    ],
                    'mappings' => $mappings,
                ],
            ]);

            Log::info("Search index created", ['index' => $indexName]);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to create search index", [
                'index' => $indexName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Index a document.
     */
    public function indexDocument(string $tenantId, string $type, string $id, array $data): bool
    {
        if (!$this->client) {
            return false;
        }

        $indexName = $this->getIndexName($tenantId, $type);

        try {
            $this->client->index([
                'index' => $indexName,
                'id' => $id,
                'body' => $data,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to index document", [
                'index' => $indexName,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Bulk index documents.
     */
    public function bulkIndex(string $tenantId, string $type, array $documents): array
    {
        if (!$this->client) {
            return ['success' => false, 'error' => 'Elasticsearch not available'];
        }

        $indexName = $this->getIndexName($tenantId, $type);
        
        $body = [];
        foreach ($documents as $document) {
            $body[] = ['index' => ['_index' => $indexName, '_id' => $document['id']]];
            unset($document['id']);
            $body[] = $document;
        }

        try {
            $response = $this->client->bulk(['body' => $body]);
            $result = $response->asArray();

            return [
                'success' => true,
                'indexed' => $result['items'] ?? [],
                'errors' => $result['errors'] ?? false,
                'took' => $result['took'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error("Bulk index failed", [
                'index' => $indexName,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Search across an index.
     */
    public function search(
        string $tenantId,
        string $type,
        string $query,
        array $filters = [],
        int $page = 1,
        int $perPage = 20
    ): array {
        if (!$this->client) {
            return $this->fallbackSearch($tenantId, $type, $query, $filters, $page, $perPage);
        }

        $indexName = $this->getIndexName($tenantId, $type);

        try {
            $searchBody = [
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'multi_match' => [
                                    'query' => $query,
                                    'fields' => ['name^3', 'email^2', 'bio', 'skills', 'company', 'title'],
                                    'type' => 'best_fields',
                                    'fuzziness' => 'AUTO',
                                ],
                            ],
                        ],
                        'filter' => $this->buildFilters($filters),
                    ],
                ],
                'highlight' => [
                    'fields' => [
                        'name' => new \stdClass(),
                        'bio' => new \stdClass(),
                    ],
                ],
                'sort' => [
                    '_score' => 'desc',
                ],
            ];

            $response = $this->client->search([
                'index' => $indexName,
                'body' => $searchBody,
            ]);

            $result = $response->asArray();

            return [
                'data' => $this->formatSearchResults($result),
                'total' => $result['hits']['total']['value'] ?? 0,
                'page' => $page,
                'per_page' => $perPage,
                'took' => $result['took'] ?? 0,
                'source' => 'elasticsearch',
            ];
        } catch (\Exception $e) {
            Log::warning("Elasticsearch search failed, using fallback", [
                'error' => $e->getMessage(),
            ]);
            return $this->fallbackSearch($tenantId, $type, $query, $filters, $page, $perPage);
        }
    }

    /**
     * Get search suggestions (autocomplete).
     */
    public function suggest(string $tenantId, string $type, string $query, int $limit = 5): array
    {
        if (!$this->client) {
            return [];
        }

        $indexName = $this->getIndexName($tenantId, $type);

        try {
            $response = $this->client->search([
                'index' => $indexName,
                'body' => [
                    'size' => 0,
                    'suggest' => [
                        'name_suggest' => [
                            'prefix' => $query,
                            'completion' => [
                                'field' => 'name_suggest',
                                'fuzzy' => ['fuzziness' => 'AUTO'],
                                'size' => $limit,
                            ],
                        ],
                    ],
                ],
            ]);

            $result = $response->asArray();
            $suggestions = $result['suggest']['name_suggest'][0]['options'] ?? [];

            return array_map(fn ($s) => $s['text'], $suggestions);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Delete a document from the index.
     */
    public function deleteDocument(string $tenantId, string $type, string $id): bool
    {
        if (!$this->client) {
            return false;
        }

        $indexName = $this->getIndexName($tenantId, $type);

        try {
            $this->client->delete([
                'index' => $indexName,
                'id' => $id,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete document", [
                'index' => $indexName,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Delete an entire index.
     */
    public function deleteIndex(string $tenantId, string $type): bool
    {
        if (!$this->client) {
            return false;
        }

        $indexName = $this->getIndexName($tenantId, $type);

        try {
            $this->client->indices()->delete(['index' => $indexName]);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete index", [
                'index' => $indexName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get index statistics.
     */
    public function getStats(string $tenantId, string $type): array
    {
        if (!$this->client) {
            return ['error' => 'Elasticsearch not available'];
        }

        $indexName = $this->getIndexName($tenantId, $type);

        try {
            $response = $this->client->indices()->stats(['index' => $indexName]);
            return $response->asArray();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Build filter clauses.
     */
    private function buildFilters(array $filters): array
    {
        $filterClauses = [];

        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $filterClauses[] = ['terms' => [$field => $value]];
            } else {
                $filterClauses[] = ['term' => [$field => $value]];
            }
        }

        return $filterClauses;
    }

    /**
     * Format search results.
     */
    private function formatSearchResults(array $result): array
    {
        $hits = $result['hits']['hits'] ?? [];

        return array_map(function ($hit) {
            return [
                'id' => $hit['_id'],
                'score' => $hit['_score'],
                'source' => $hit['_source'],
                'highlight' => $hit['highlight'] ?? [],
            ];
        }, $hits);
    }

    /**
     * Fallback search using database.
     */
    private function fallbackSearch(
        string $tenantId,
        string $type,
        string $query,
        array $filters,
        int $page,
        int $perPage
    ): array {
        // Use database search as fallback
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            return ['data' => [], 'total' => 0, 'source' => 'fallback'];
        }

        return $tenant->run(function () use ($query, $filters, $page, $perPage) {
            $qb = \App\Models\User::query()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%")
                      ->orWhereHas('profile', function ($pq) use ($query) {
                          $pq->where('bio', 'like', "%{$query}%")
                             ->orWhere('company', 'like', "%{$query}%")
                             ->orWhere('title', 'like', "%{$query}%");
                      });
                });

            // Apply filters
            foreach ($filters as $field => $value) {
                if ($field === 'graduation_year') {
                    $qb->whereHas('education', function ($eq) use ($value) {
                        $eq->where('graduation_year', $value);
                    });
                }
            }

            $total = $qb->count();
            $results = $qb->skip(($page - 1) * $perPage)
                          ->take($perPage)
                          ->get();

            return [
                'data' => $results->map(fn ($u) => [
                    'id' => $u->id,
                    'source' => [
                        'name' => $u->name,
                        'email' => $u->email,
                        'avatar' => $u->avatar,
                    ],
                ]),
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'source' => 'database_fallback',
            ];
        });
    }

    /**
     * Get index name for tenant and type.
     */
    private function getIndexName(string $tenantId, string $type): string
    {
        $prefix = config('elasticsearch.index_prefix', 'alumate');
        return "{$prefix}_{$tenantId}_{$type}";
    }

    /**
     * Get mappings for a type.
     */
    private function getMappings(string $type): array
    {
        $mappings = [
            'alumni' => [
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'name' => [
                        'type' => 'text',
                        'analyzer' => 'custom_analyzer',
                        'fields' => [
                            'keyword' => ['type' => 'keyword'],
                        ],
                    ],
                    'name_suggest' => [
                        'type' => 'completion',
                    ],
                    'email' => ['type' => 'keyword'],
                    'bio' => ['type' => 'text', 'analyzer' => 'custom_analyzer'],
                    'skills' => ['type' => 'keyword'],
                    'company' => ['type' => 'text', 'analyzer' => 'custom_analyzer'],
                    'title' => ['type' => 'text', 'analyzer' => 'custom_analyzer'],
                    'location' => ['type' => 'text'],
                    'graduation_year' => ['type' => 'integer'],
                    'degree' => ['type' => 'keyword'],
                    'major' => ['type' => 'keyword'],
                    'industry' => ['type' => 'keyword'],
                    'is_mentor' => ['type' => 'boolean'],
                    'available_for_mentorship' => ['type' => 'boolean'],
                    'created_at' => ['type' => 'date'],
                ],
            ],
            'jobs' => [
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'title' => ['type' => 'text', 'analyzer' => 'custom_analyzer'],
                    'description' => ['type' => 'text', 'analyzer' => 'custom_analyzer'],
                    'company' => ['type' => 'text', 'analyzer' => 'custom_analyzer'],
                    'location' => ['type' => 'text'],
                    'type' => ['type' => 'keyword'],
                    'salary_min' => ['type' => 'integer'],
                    'salary_max' => ['type' => 'integer'],
                    'skills_required' => ['type' => 'keyword'],
                    'is_remote' => ['type' => 'boolean'],
                    'created_at' => ['type' => 'date'],
                ],
            ],
            'events' => [
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'title' => ['type' => 'text', 'analyzer' => 'custom_analyzer'],
                    'description' => ['type' => 'text', 'analyzer' => 'custom_analyzer'],
                    'location' => ['type' => 'text'],
                    'type' => ['type' => 'keyword'],
                    'start_date' => ['type' => 'date'],
                    'is_virtual' => ['type' => 'boolean'],
                    'created_at' => ['type' => 'date'],
                ],
            ],
        ];

        return $mappings[$type] ?? $mappings['alumni'];
    }
}
