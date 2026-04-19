<?php

namespace Eslam\ModelSearch\DTOs;

class SearchDTO
{
    public function __construct(
        public readonly string $model,
        public readonly string $field,
        public readonly string $value,
        public readonly ?string $sortBy = null,
        public readonly string $sortDirection = 'asc',
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly array $eagerLoad = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            model: $data['model'],
            field: $data['field'],
            value: $data['value'],
            sortBy: $data['sort_by'] ?? null,
            sortDirection: $data['sort_direction'] ?? 'asc',
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 15),
            eagerLoad: $data['eager_load'] ?? []
        );
    }
}