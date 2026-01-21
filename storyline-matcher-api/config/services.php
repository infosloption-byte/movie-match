<?php

return [
    'tmdb' => [
        'api_key' => env('TMDB_API_KEY'),
        'base_url' => env('TMDB_BASE_URL', 'https://api.themoviedb.org/3'),
        'image_base_url' => env('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p'),
        'rate_limit_per_second' => 4, // TMDB allows ~40 requests per 10 seconds
    ],

    'embedding' => [
        'provider' => env('EMBEDDING_PROVIDER', 'openai'), // 'openai' or 'huggingface'
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'model' => env('OPENAI_MODEL', 'text-embedding-3-small'),
    ],

    'huggingface' => [
        'api_key' => env('HUGGINGFACE_API_KEY'),
        'model' => env('HUGGINGFACE_MODEL', 'sentence-transformers/all-MiniLM-L6-v2'),
    ],

    'similarity' => [
        'threshold' => env('SIMILARITY_THRESHOLD', 0.7),
        'limit' => env('SIMILAR_MOVIES_LIMIT', 10),
    ],
];
