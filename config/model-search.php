<?php

return [
    'allowed_models' => [
        // App\Models\Product::class,
        // App\Models\Order::class,
    ],

    'searchable_fields' => [
        // App\Models\Product::class => ['name', 'price', 'description'],
        // App\Models\Order::class => ['user_email', 'price', 'status'],
    ],

    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'prefix' => 'model_search_',
    ],

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    'models' => [
        'path' => base_path('app/Models'),
        'namespace' => 'App\\Models',
    ],

    'search' => [
        'case_sensitive' => false,
        'wildcard' => 'both',
    ],

    'api' => [
        'prefix' => 'api/model-search',
        'middleware' => ['api'],
    ],
];