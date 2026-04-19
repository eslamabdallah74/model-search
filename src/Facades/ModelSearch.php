<?php

namespace Eslam\ModelSearch\Facades;

use Eslam\ModelSearch\Services\SearchService;
use Illuminate\Support\Facades\Facade;

class ModelSearch extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SearchService::class;
    }
}