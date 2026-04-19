<?php

use Eslam\ModelSearch\Http\Controllers\ModelSearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/model-search')->middleware(['api'])->group(function () {
    Route::get('/models', [ModelSearchController::class, 'models']);
    Route::get('/fields/{model}', [ModelSearchController::class, 'fields']);
    Route::post('/search', [ModelSearchController::class, 'search']);
});