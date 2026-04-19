# eslam/model-search

[![Packagist](https://img.shields.io/packagist/v/eslam/model-search.svg)](https://packagist.org/packages/eslam/model-search)
[![Packagist Downloads](https://img.shields.io/packagist/dt/eslam/model-search.svg)](https://packagist.org/packages/eslam/model-search)
[![License](https://img.shields.io/packagist/l/eslam/model-search.svg)](https://packagist.org/packages/eslam/model-search)
[![PHP](https://img.shields.io/packagist/php-v/eslam/model-search.svg)](https://packagist.org/packages/eslam/model-search)

A production-ready Laravel package that provides a dynamic model search system with auto-discovery, security, and high performance. Designed for Laravel 9-11.

## Features

- **Model Auto-Discovery**: Automatically scans and detects all Eloquent models in `app/Models`
- **Secure Model Access**: Config-based whitelist for allowed models
- **Dynamic Field Discovery**: Extracts database columns using Schema with field whitelisting
- **Relationship Support**: Search related fields using dot notation (e.g., `user.email`)
- **REST API Endpoints**: Built-in API routes for searching
- **Pagination**: Laravel pagination with meta data
- **Sorting**: Sort by any allowed field
- **Performance Optimization**: Caching, eager loading, optimized queries

## Requirements

- PHP 8.1+
- Laravel 9.0+ / 10.0+ / 11.0+

## Installation

### 1. Install the package

```bash
composer require eslam/model-search
```

### 2. Publish the configuration

```bash
php artisan vendor:publish --provider="Eslam\ModelSearch\Providers\ModelSearchServiceProvider" --tag="model-search-config"
```

This will create `config/model-search.php` in your Laravel application.

### 3. Configure the package

Edit `config/model-search.php`:

```php
<?php

return [
    // Whitelist of allowed models for searching
    'allowed_models' => [
        App\Models\Product::class,
        App\Models\Order::class,
    ],

    // Allowed searchable fields per model
    'searchable_fields' => [
        App\Models\Product::class => ['name', 'price', 'description'],
        App\Models\Order::class => ['user_email', 'price', 'status'],
    ],

    // Cache configuration
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'prefix' => 'model_search_',
    ],

    // Pagination settings
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    // Models path configuration
    'models' => [
        'path' => base_path('app/Models'),
        'namespace' => 'App\\Models',
    ],

    // Search settings
    'search' => [
        'case_sensitive' => false,
        'wildcard' => 'both', // 'both', 'left', 'right', 'none'
    ],

    // API routes configuration
    'api' => [
        'prefix' => 'api/model-search',
        'middleware' => ['api'],
    ],
];
```

## API Endpoints

### GET /api/model-search/models

Returns list of allowed models.

**Example Request:**
```bash
curl -X GET http://localhost:8000/api/model-search/models
```

**Response:**
```json
{
  "data": [
    {
      "class": "App\\Models\\Product",
      "name": "Product"
    },
    {
      "class": "App\\Models\\Order",
      "name": "Order"
    }
  ]
}
```

### GET /api/model-search/fields/{model}

Returns allowed searchable fields for a model.

**Parameters:**
- `model` (base64 encoded): The model class name

**Example Request:**
```bash
# Encode model class name
echo -n "App\\Models\\Product" | base64
# Output: TXB0XE1vZGVsc1xQcm9kdWN0

curl -X GET http://localhost:8000/api/model-search/fields/TXB0XE1vZGVsc1xQcm9kdWN0
```

**Response:**
```json
{
  "data": [
    {"name": "name"},
    {"name": "price"},
    {"name": "description"}
  ]
}
```

### POST /api/model-search/search

Perform a search.

**Request Body:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| model | string | Yes | The model class name |
| field | string | Yes | The field to search (supports dot notation for relations) |
| value | string | Yes | The search value |
| sort_by | string | No | Field to sort by |
| sort_direction | string | No | Sort direction (asc/desc), default: asc |
| page | integer | No | Page number, default: 1 |
| per_page | integer | No | Items per page, default: 15, max: 100 |
| eager_load | array | No | Relationships to eager load |

**Example Request:**
```bash
curl -X POST http://localhost:8000/api/model-search/search \
  -H "Content-Type: application/json" \
  -d '{
    "model": "App\\Models\\Product",
    "field": "name",
    "value": "phone",
    "sort_by": "price",
    "sort_direction": "asc",
    "page": 1,
    "per_page": 15
  }'
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "iPhone 15",
      "price": 999.00,
      "description": "Latest iPhone"
    }
  ],
  "meta": {
    "total": 100,
    "current_page": 1,
    "last_page": 10,
    "per_page": 15
  }
}
```

### Search with Relationships

Search related fields using dot notation:

```bash
curl -X POST http://localhost:8000/api/model-search/search \
  -H "Content-Type: application/json" \
  -d '{
    "model": "App\\Models\\Order",
    "field": "user.email",
    "value": "gmail",
    "eager_load": ["user", "products"]
  }'
```

This uses Laravel's `whereHas` internally to safely query relationships.

## Usage via Facade

The package provides a facade for programmatic usage:

```php
use Eslam\ModelSearch\Facades\ModelSearch;

// Get allowed models
$models = ModelSearch::getAllowedModels();

// Get allowed fields for a model
$fields = ModelSearch::getAllowedFields(App\Models\Product::class);

// Perform a search
$result = ModelSearch::search(
    modelClass: App\Models\Product::class,
    field: 'name',
    value: 'phone',
    sortBy: 'price',
    sortDirection: 'asc',
    page: 1,
    perPage: 15,
    eagerLoad: ['category']
);

// Access results
$data = $result['data'];
$meta = $result['meta'];

// Check if model/field is allowed
$isModelAllowed = ModelSearch::isModelAllowed(App\Models\Product::class);
$isFieldAllowed = ModelSearch::isFieldAllowed(App\Models\Product::class, 'name');
```

## Security

This package prioritizes security:

- **Model Whitelist**: Only explicitly whitelisted models can be searched
- **Field Whitelist**: Only explicitly whitelisted fields per model can be searched
- **Input Validation**: All requests validated via Laravel Form Request
- **SQL Injection Protection**: Uses parameterized queries
- **Relationship Safety**: Uses `whereHas` to prevent arbitrary relationship access

## Performance

The package is optimized for large datasets:

- **Caching**: Model and field lists are cached (configurable TTL)
- **Eager Loading**: Support for eager loading to avoid N+1 queries
- **Optimized Queries**: Uses indexed columns efficiently
- **Pagination**: Efficient offset-based pagination

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| allowed_models | array | [] | Whitelist of model classes |
| searchable_fields | array | [] | Allowed fields per model |
| cache.enabled | boolean | true | Enable caching |
| cache.ttl | integer | 3600 | Cache TTL in seconds |
| cache.prefix | string | model_search_ | Cache key prefix |
| pagination.default_per_page | integer | 15 | Default items per page |
| pagination.max_per_page | integer | 100 | Maximum items per page |
| models.path | string | app/Models | Path to models directory |
| models.namespace | string | App\Models | Models namespace |
| search.case_sensitive | boolean | false | Case sensitive search |
| search.wildcard | string | both | Wildcard position (both/left/right/none) |
| api.prefix | string | api/model-search | API route prefix |
| api.middleware | array | ['api'] | API middleware |

## Versioning

This package follows [Semantic Versioning](https://semver.org/).

- **Major**: Backward-incompatible changes
- **Minor**: New features (backward-compatible)
- **Patch**: Bug fixes

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a complete list of changes.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.