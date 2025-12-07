<?php

use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Purchasing\PurchaseReceiptController;

Route::middleware(['web', 'auth', 'role:owner,admin'])->group(function () {

    Route::prefix('purchasing')->name('purchasing.')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | PURCHASE ORDERS
        |--------------------------------------------------------------------------
         */
        Route::resource('purchase-orders', PurchaseOrderController::class)
            ->names('purchase_orders');

        /*
        |--------------------------------------------------------------------------
        | PURCHASE RECEIPTS (GRN)
        |--------------------------------------------------------------------------
         */
        Route::resource('purchase-receipts', PurchaseReceiptController::class)
            ->names('purchase_receipts');

        // Posting GRN
        Route::post('purchase-receipts/{purchase_receipt}/post',
            [PurchaseReceiptController::class, 'post'])
            ->name('purchase_receipts.post');

        // Buat GRN dari Purchase Order
        Route::get('purchase-orders/{purchase_order}/create-grn',
            [PurchaseReceiptController::class, 'createFromOrder'])
            ->name('purchase_receipts.create_from_order');
    });

});
