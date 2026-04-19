<?php

namespace Eslam\ModelSearch\Contracts;

interface SearchableInterface
{
    public function isModelAllowed(string $modelClass): bool;
    public function isFieldAllowed(string $modelClass, string $field): bool;
    public function getAllowedModels(): array;
    public function getAllowedFields(string $modelClass): array;
    public function search(
        string $modelClass,
        string $field,
        string $value,
        array $eagerLoad = [],
        ?string $sortBy = null,
        string $sortDirection = 'asc',
        int $page = 1,
        int $perPage = null
    ): array;
}