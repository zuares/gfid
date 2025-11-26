<?php
use App\Http\Controllers\Inventory\ExternalTransferController;
use App\Http\Controllers\Inventory\InventoryStockController;
use App\Http\Controllers\Inventory\StockCardController;
use App\Http\Controllers\Inventory\TransferController;

Route::middleware(['web', 'auth'])
    ->prefix('inventory')
    ->name('inventory.')
    ->group(function () {

        Route::get('stock-card', [StockCardController::class, 'index'])
            ->name('stock_card.index');

        Route::get('stock-card/export', [StockCardController::class, 'export'])
            ->name('stock_card.export');

        Route::resource('transfers', TransferController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->names('transfers');
    });

Route::prefix('inventory/external-transfers')
    ->name('inventory.external_transfers.')
    ->middleware(['auth']) // optional, kalau pakai auth
    ->group(function () {
        Route::get('/', [ExternalTransferController::class, 'index'])->name('index');
        Route::get('/create', [ExternalTransferController::class, 'create'])->name('create');
        Route::post('/', [ExternalTransferController::class, 'store'])->name('store');
        Route::get('/{externalTransfer}', [ExternalTransferController::class, 'show'])->name('show');
    });

Route::middleware(['auth'])->group(function () {
    Route::prefix('inventory/stocks')
        ->name('inventory.stocks.')
        ->group(function () {

            // stok per item
            Route::get('/items', [InventoryStockController::class, 'items'])
                ->name('items');

            // stok per LOT
            Route::get('/lots', [InventoryStockController::class, 'lots'])
                ->name('lots');
        });
});
