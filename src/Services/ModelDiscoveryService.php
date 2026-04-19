<?php

namespace Eslam\ModelSearch\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class ModelDiscoveryService
{
    private string $modelsPath;
    private string $modelsNamespace;
    private array $cacheConfig;
    private ?array $discoveredModels = null;

    public function __construct(string $modelsPath, string $modelsNamespace, array $cacheConfig)
    {
        $this->modelsPath = $modelsPath;
        $this->modelsNamespace = $modelsNamespace;
        $this->cacheConfig = $cacheConfig;
    }

    public function discoverModels(): array
    {
        if ($this->discoveredModels !== null) {
            return $this->discoveredModels;
        }

        if ($this->cacheConfig['enabled']) {
            $cacheKey = $this->cacheConfig['prefix'] . 'models';
            return $this->discoveredModels = Cache::remember(
                $cacheKey,
                $this->cacheConfig['ttl'],
                fn() => $this->scanForModels()
            );
        }

        return $this->discoveredModels = $this->scanForModels();
    }

    private function scanForModels(): array
    {
        $models = [];

        if (!File::exists($this->modelsPath)) {
            return $models;
        }

        $files = File::files($this->modelsPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->modelsNamespace . '\\' . $file->getFilenameWithoutExtension();

            if (!$this->isValidModel($className)) {
                continue;
            }

            $models[] = $className;
        }

        return $models;
    }

    private function isValidModel(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        $reflection = new ReflectionClass($className);

        if ($reflection->isAbstract()) {
            return false;
        }

        if (!$reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class)) {
            return false;
        }

        if ($reflection->isTrait()) {
            return false;
        }

        return true;
    }

    public function getModelFields(string $modelClass): array
    {
        if (!class_exists($modelClass)) {
            return [];
        }

        if (!is_a($modelClass, \Illuminate\Database\Eloquent\Model::class, true)) {
            return [];
        }

        $cacheKey = $this->cacheConfig['prefix'] . 'fields_' . md5($modelClass);

        if ($this->cacheConfig['enabled']) {
            return Cache::remember(
                $cacheKey,
                $this->cacheConfig['ttl'],
                fn() => $this->extractFieldsFromModel($modelClass)
            );
        }

        return $this->extractFieldsFromModel($modelClass);
    }

    private function extractFieldsFromModel(string $modelClass): array
    {
        $instance = new $modelClass();
        $table = $instance->getTable();

        if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
            return [];
        }

        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);

        return array_values(array_diff($columns, $instance->getHidden()));
    }

    public function clearCache(): void
    {
        if (!$this->cacheConfig['enabled']) {
            return;
        }

        Cache::forget($this->cacheConfig['prefix'] . 'models');
    }
}