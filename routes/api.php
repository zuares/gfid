<?php
use App\Http\Controllers\Api\ItemController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // List + search + filter
    Route::get('/items', [ItemController::class, 'index'])->name('api.items.index');

    // Endpoint ringan khusus suggest (id, code, name saja)
    Route::get('/items/suggest', [ItemController::class, 'suggest'])->name('api.items.suggest');

    // Detail item
    Route::get('/items/{item}', [ItemController::class, 'show'])->name('api.items.show');
});
