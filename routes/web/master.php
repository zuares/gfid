<?php

use App\Http\Controllers\Master\CustomerController as MasterCustomerController;

Route::middleware(['web', 'auth'])
    ->group(function () {

        Route::prefix('master')->name('master.')->group(function () {

            // Route::resource('items', \App\Http\Controllers\Master\ItemController::class);

            Route::resource('customers', MasterCustomerController::class)
                ->except(['show']);
        });

    });
