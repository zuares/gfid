<?php

use App\Http\Controllers\Sales\Reports\ChannelProfitReportController;
use App\Http\Controllers\Sales\Reports\ItemProfitReportController;
use App\Http\Controllers\Sales\Reports\ShipmentAnalyticsController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Sales\ShipmentController;

Route::middleware(['web', 'auth'])->group(function () {

    // ... route lain

    Route::prefix('sales')->name('sales.')->group(function () {
        // URL:  /sales/invoices
        // Name: sales.invoices.index, sales.invoices.create, dst.
        Route::post('invoices/{invoice}/post', [SalesInvoiceController::class, 'post'])
            ->name('invoices.post');
        Route::resource('invoices', SalesInvoiceController::class);

        // 🔥 Laporan Laba Rugi per Item
        Route::get('reports/item-profit', [ItemProfitReportController::class, 'index'])
            ->name('reports.item_profit');

        // 🔥 Laporan laba rugi per channel (store)
        Route::get('reports/channel-profit', [ChannelProfitReportController::class, 'index'])
            ->name('reports.channel_profit');

        Route::post('shipments/{shipment}/ship', [ShipmentController::class, 'ship'])
            ->name('shipments.ship');
        // 🔗 Shipment resource
        Route::resource('shipments', ShipmentController::class)
            ->only(['index', 'show', 'create', 'store']);
        // Buat Shipment dari Invoice
        Route::get('invoices/{invoice}/shipments/create', [ShipmentController::class, 'createFromInvoice'])
            ->name('invoices.shipments.create');

        Route::get('reports/shipment-analytics', [ShipmentAnalyticsController::class, 'index'])
            ->name('reports.shipment_analytics');

    });
});
