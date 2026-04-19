<?php

namespace Eslam\ModelSearch\Http\Controllers;

use Eslam\ModelSearch\Http\Requests\ModelSearchRequest;
use Eslam\ModelSearch\Services\SearchService;
use Illuminate\Http\JsonResponse;

class ModelSearchController
{
    public function __construct(
        private SearchService $searchService
    ) {}

    public function models(): JsonResponse
    {
        $models = $this->searchService->getAllowedModels();

        return response()->json([
            'data' => array_map(fn($model) => [
                'class' => $model,
                'name' => class_basename($model),
            ], $models),
        ]);
    }

    public function fields(string $model): JsonResponse
    {
        $modelClass = base64_decode($model);

        if (!$modelClass || !class_exists($modelClass)) {
            return response()->json([
                'message' => 'Invalid model class',
            ], 400);
        }

        if (!$this->searchService->isModelAllowed($modelClass)) {
            return response()->json([
                'message' => 'Model not allowed for search',
            ], 403);
        }

        $fields = $this->searchService->getAllowedFields($modelClass);

        return response()->json([
            'data' => array_map(fn($field) => ['name' => $field], $fields),
        ]);
    }

    public function search(ModelSearchRequest $request): JsonResponse
    {
        try {
            $modelClass = $request->input('model');

            if (!$this->searchService->isModelAllowed($modelClass)) {
                return response()->json([
                    'message' => 'Model not allowed for search',
                ], 403);
            }

            $result = $this->searchService->search(
                modelClass: $request->input('model'),
                field: $request->input('field'),
                value: $request->input('value'),
                sortBy: $request->input('sort_by'),
                sortDirection: $request->input('sort_direction', 'asc'),
                page: $request->input('page', 1),
                perPage: $request->input('per_page'),
                eagerLoad: $request->input('eager_load', [])
            );

            return response()->json([
                'data' => $result['data'],
                'meta' => $result['meta'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Search error occurred',
            ], 500);
        }
    }
}