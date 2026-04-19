<?php

namespace Eslam\ModelSearch\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SearchService
{
    private ModelDiscoveryService $modelDiscovery;
    private array $config;
    private $cacheStore;

    public function __construct(
        ModelDiscoveryService $modelDiscovery,
        array $config,
        $cacheStore
    ) {
        $this->modelDiscovery = $modelDiscovery;
        $this->config = $config;
        $this->cacheStore = $cacheStore;
    }

    public function isModelAllowed(string $modelClass): bool
    {
        $allowedModels = $this->config['allowed_models'] ?? [];

        if (empty($allowedModels)) {
            return true;
        }

        return in_array($modelClass, $allowedModels, true);
    }

    public function isFieldAllowed(string $modelClass, string $field): bool
    {
        $searchableFields = $this->config['searchable_fields'] ?? [];

        if (!isset($searchableFields[$modelClass])) {
            return false;
        }

        $allowedFields = $searchableFields[$modelClass];

        return in_array($field, $allowedFields, true);
    }

    public function getAllowedModels(): array
    {
        $allowedModels = $this->config['allowed_models'] ?? [];
        $discoveredModels = $this->modelDiscovery->discoverModels();

        if (empty($allowedModels)) {
            return $discoveredModels;
        }

        return array_intersect($discoveredModels, $allowedModels);
    }

    public function getAllowedFields(string $modelClass): array
    {
        if (!$this->isModelAllowed($modelClass)) {
            return [];
        }

        return $this->config['searchable_fields'][$modelClass] ?? [];
    }

    public function search(
        string $modelClass,
        string $field,
        string $value,
        array $eagerLoad = [],
        ?string $sortBy = null,
        string $sortDirection = 'asc',
        int $page = 1,
        int $perPage = null
    ): array {
        if (!$this->isModelAllowed($modelClass)) {
            throw new \InvalidArgumentException("Model [{$modelClass}] is not allowed for search.");
        }

        if (!$this->isFieldAllowed($modelClass, $field)) {
            throw new \InvalidArgumentException("Field [{$field}] is not allowed for search on model [{$modelClass}].");
        }

        $perPage = $perPage ?? $this->config['pagination']['default_per_page'];
        $perPage = min($perPage, $this->config['pagination']['max_per_page']);

        $query = $modelClass::query();

        $this->applySearch($query, $modelClass, $field, $value);

        if (!empty($eagerLoad)) {
            $query->with($eagerLoad);
        }

        if ($sortBy !== null && $this->isFieldAllowed($modelClass, $sortBy)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $total = $query->count();

        $results = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $lastPage = (int) ceil($total / $perPage);

        return [
            'data' => $results,
            'meta' => [
                'total' => $total,
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
            ],
        ];
    }

    private function applySearch(Builder $query, string $modelClass, string $field, string $value): void
    {
        if (str_contains($field, '.')) {
            $this->applyRelationSearch($query, $field, $value);
            return;
        }

        $caseSensitive = $this->config['search']['case_sensitive'] ?? false;
        $wildcard = $this->config['search']['wildcard'] ?? 'both';

        $searchValue = $this->applyWildcard($value, $wildcard);

        if ($caseSensitive) {
            $query->where($field, 'LIKE', $searchValue);
        } else {
            $query->whereRaw(
                'LOWER(' . DB::getTablePrefix() . $this->getTable($modelClass) . '.' . DB::getTablePrefix() . $field . ') LIKE LOWER(?)',
                [$searchValue]
            );
        }
    }

    private function applyRelationSearch(Builder $query, string $field, string $value): void
    {
        $parts = explode('.', $field, 2);
        $relation = $parts[0];
        $relationField = $parts[1];

        $caseSensitive = $this->config['search']['case_sensitive'] ?? false;
        $wildcard = $this->config['search']['wildcard'] ?? 'both';

        $searchValue = $this->applyWildcard($value, $wildcard);

        $query->whereHas($relation, function ($q) use ($relationField, $searchValue, $caseSensitive) {
            if ($caseSensitive) {
                $q->where($relationField, 'LIKE', $searchValue);
            } else {
                $q->whereRaw(
                    'LOWER(' . DB::getTablePrefix() . $relationField . ') LIKE LOWER(?)',
                    [$searchValue]
                );
            }
        });
    }

    private function applyWildcard(string $value, string $position): string
    {
        return match ($position) {
            'both' => "%{$value}%",
            'left' => "%{$value}",
            'right' => "{$value}%",
            'none' => $value,
            default => "%{$value}%",
        };
    }

    private function getTable(string $modelClass): string
    {
        $instance = new $modelClass();
        return $instance->getTable();
    }
}