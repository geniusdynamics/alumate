<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Elasticsearch client and indexing settings
    |
    */

    'hosts' => [
        env('ELASTICSEARCH_HOST', 'localhost:9200'),
    ],

    'retries' => env('ELASTICSEARCH_RETRIES', 2),

    'handler' => env('ELASTICSEARCH_HANDLER', 'default'),

    'connection_pool' => env('ELASTICSEARCH_CONNECTION_POOL', 'StaticNoPingConnectionPool'),

    'selector' => env('ELASTICSEARCH_SELECTOR', 'RoundRobinSelector'),

    'serializer' => env('ELASTICSEARCH_SERIALIZER', 'SmartSerializer'),

    'sniff_on_start' => env('ELASTICSEARCH_SNIFF_ON_START', false),

    'sniff_on_connection_fail' => env('ELASTICSEARCH_SNIFF_ON_CONNECTION_FAIL', false),

    'sniff_interval' => env('ELASTICSEARCH_SNIFF_INTERVAL', 300),

    'indices' => [
        'alumni' => [
            'index' => env('ELASTICSEARCH_ALUMNI_INDEX', 'alumni'),
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
                'analysis' => [
                    'analyzer' => [
                        'alumni_analyzer' => [
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => [
                                'lowercase',
                                'stop',
                                'snowball',
                                'synonym_filter'
                            ]
                        ],
                        'autocomplete_analyzer' => [
                            'type' => 'custom',
                            'tokenizer' => 'keyword',
                            'filter' => [
                                'lowercase',
                                'edge_ngram_filter'
                            ]
                        ]
                    ],
                    'filter' => [
                        'synonym_filter' => [
                            'type' => 'synonym',
                            'synonyms' => [
                                'university,college,school',
                                'graduate,alumni,alumnus',
                                'software engineer,developer,programmer',
                                'manager,supervisor,lead',
                                'CEO,chief executive officer',
                                'CTO,chief technology officer'
                            ]
                        ],
                        'edge_ngram_filter' => [
                            'type' => 'edge_ngram',
                            'min_gram' => 2,
                            'max_gram' => 20
                        ]
                    ]
                ]
            ],
            'mappings' => [
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'name' => [
                        'type' => 'text',
                        'analyzer' => 'alumni_analyzer',
                        'fields' => [
                            'keyword' => ['type' => 'keyword'],
                            'autocomplete' => [
                                'type' => 'text',
                                'analyzer' => 'autocomplete_analyzer'
                            ]
                        ]
                    ],
                    'email' => ['type' => 'keyword'],
                    'bio' => [
                        'type' => 'text',
                        'analyzer' => 'alumni_analyzer'
                    ],
                    'skills' => [
                        'type' => 'text',
                        'analyzer' => 'alumni_analyzer',
                        'fields' => [
                            'keyword' => ['type' => 'keyword']
                        ]
                    ],
                    'location' => [
                        'type' => 'text',
                        'fields' => [
                            'keyword' => ['type' => 'keyword']
                        ]
                    ],
                    'location_coordinates' => ['type' => 'geo_point'],
                    'industry' => ['type' => 'keyword'],
                    'company' => [
                        'type' => 'text',
                        'analyzer' => 'alumni_analyzer',
                        'fields' => [
                            'keyword' => ['type' => 'keyword'],
                            'autocomplete' => [
                                'type' => 'text',
                                'analyzer' => 'autocomplete_analyzer'
                            ]
                        ]
                    ],
                    'title' => [
                        'type' => 'text',
                        'analyzer' => 'alumni_analyzer',
                        'fields' => [
                            'keyword' => ['type' => 'keyword']
                        ]
                    ],
                    'graduation_year' => ['type' => 'integer'],
                    'school' => [
                        'type' => 'text',
                        'analyzer' => 'alumni_analyzer',
                        'fields' => [
                            'keyword' => ['type' => 'keyword']
                        ]
                    ],
                    'education_history' => [
                        'type' => 'nested',
                        'properties' => [
                            'school_name' => [
                                'type' => 'text',
                                'analyzer' => 'alumni_analyzer',
                                'fields' => ['keyword' => ['type' => 'keyword']]
                            ],
                            'degree' => ['type' => 'keyword'],
                            'field_of_study' => [
                                'type' => 'text',
                                'analyzer' => 'alumni_analyzer'
                            ],
                            'graduation_year' => ['type' => 'integer'],
                            'gpa' => ['type' => 'float']
                        ]
                    ],
                    'work_experience' => [
                        'type' => 'nested',
                        'properties' => [
                            'company' => [
                                'type' => 'text',
                                'analyzer' => 'alumni_analyzer',
                                'fields' => ['keyword' => ['type' => 'keyword']]
                            ],
                            'title' => [
                                'type' => 'text',
                                'analyzer' => 'alumni_analyzer'
                            ],
                            'industry' => ['type' => 'keyword'],
                            'start_date' => ['type' => 'date'],
                            'end_date' => ['type' => 'date'],
                            'is_current' => ['type' => 'boolean'],
                            'description' => [
                                'type' => 'text',
                                'analyzer' => 'alumni_analyzer'
                            ]
                        ]
                    ],
                    'created_at' => ['type' => 'date'],
                    'updated_at' => ['type' => 'date'],
                    'last_active' => ['type' => 'date'],
                    'profile_completeness' => ['type' => 'integer'],
                    'connection_count' => ['type' => 'integer'],
                    'post_count' => ['type' => 'integer']
                ]
            ]
        ]
    ]
];