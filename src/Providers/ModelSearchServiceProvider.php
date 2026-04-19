<?php

namespace Eslam\ModelSearch\Providers;

use Eslam\ModelSearch\Services\ModelDiscoveryService;
use Eslam\ModelSearch\Services\SearchService;
use Illuminate\Support\ServiceProvider;

class ModelSearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/model-search.php',
            'model-search'
        );

        $this->app->singleton(ModelDiscoveryService::class, function ($app) {
            return new ModelDiscoveryService(
                config('model-search.models.path'),
                config('model-search.models.namespace'),
                config('model-search.cache')
            );
        });

        $this->app->singleton(SearchService::class, function ($app) {
            return new SearchService(
                $app->make(ModelDiscoveryService::class),
                config('model-search'),
                $app['cache']->store()
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/model-search.php' => config_path('model-search.php'),
        ], 'model-search-config');

        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}