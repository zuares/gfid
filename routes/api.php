<?php

use App\Http\Controllers\Api\ItemSearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth') // atau sesuaikan middleware-mu
    ->prefix('items')
    ->name('api.items.')
    ->group(function () {
        Route::get('/search', ItemSearchController::class)
            ->name('search');
    });
