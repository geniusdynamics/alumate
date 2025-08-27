<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Elasticsearch integration in the alumni platform.
    | This includes connection settings, index configuration, and search
    | behavior settings.
    |
    */

    'host' => env('ELASTICSEARCH_HOST', 'localhost:9200'),

    'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'alumni_platform'),

    'settings' => [
        'number_of_shards' => env('ELASTICSEARCH_SHARDS', 1),
        'number_of_replicas' => env('ELASTICSEARCH_REPLICAS', 0),
    ],

    'search' => [
        'default_size' => 20,
        'max_size' => 100,
        'highlight_fragment_size' => 150,
        'suggestion_size' => 5,
    ],

    'indexing' => [
        'batch_size' => 100,
        'queue_connection' => env('ELASTICSEARCH_QUEUE_CONNECTION', 'database'),
    ],
];
