<?php

use App\Http\Controllers\Purchasing\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

// PURCHASING MODULE
Route::middleware(['auth'])->prefix('purchasing')->name('purchasing.')->group(function () {

    // Purchase Orders
    Route::resource('purchase-orders', PurchaseOrderController::class)
        ->names('purchase_orders');
});
