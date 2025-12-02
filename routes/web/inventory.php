<?php
use App\Http\Controllers\Inventory\ExternalTransferController;
use App\Http\Controllers\Inventory\InventoryAdjustmentController;
use App\Http\Controllers\Inventory\InventoryStockController;
use App\Http\Controllers\Inventory\RtsStockRequestController;
use App\Http\Controllers\Inventory\RtsStockRequestProcessController;
use App\Http\Controllers\Inventory\StockCardController;
use App\Http\Controllers\Inventory\StockOpnameController;

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

Route::prefix('rts/stock-requests')
    ->name('rts.stock-requests.')
    ->group(function () {
        // RTS side (packing online)
        Route::get('/', [RtsStockRequestController::class, 'index'])->name('index');
        Route::get('/create', [RtsStockRequestController::class, 'create'])->name('create');
        Route::post('/', [RtsStockRequestController::class, 'store'])->name('store');
        Route::get('/{stockRequest}', [RtsStockRequestController::class, 'show'])->name('show');
    });

Route::prefix('prd/stock-requests')
    ->name('prd.stock-requests.')
    ->group(function () {
        // PRD side (gudang produksi) – proses permintaan RTS
        Route::get('/', [RtsStockRequestProcessController::class, 'index'])->name('index');
        Route::get('/{stockRequest}/process', [RtsStockRequestProcessController::class, 'edit'])->name('edit');
        Route::post('/{stockRequest}/process', [RtsStockRequestProcessController::class, 'update'])->name('update');
        Route::get('/{stockRequest}', [RtsStockRequestProcessController::class, 'show'])
            ->name('show');
    });

Route::prefix('inventory')
    ->name('inventory.')
    ->middleware(['web', 'auth'])
    ->group(function () {
        Route::get('stock-opnames', [StockOpnameController::class, 'index'])->name('stock_opnames.index');
        Route::get('stock-opnames/create', [StockOpnameController::class, 'create'])->name('stock_opnames.create');
        Route::post('stock-opnames', [StockOpnameController::class, 'store'])->name('stock_opnames.store');
        Route::get('stock-opnames/{stockOpname}', [StockOpnameController::class, 'show'])->name('stock_opnames.show');
        Route::get('stock-opnames/{stockOpname}/edit', [StockOpnameController::class, 'edit'])->name('stock_opnames.edit');
        Route::put('stock-opnames/{stockOpname}', [StockOpnameController::class, 'update'])->name('stock_opnames.update');

        // Finalize → auto create Inventory Adjustment + mutations
        Route::post('stock-opnames/{stockOpname}/finalize', [StockOpnameController::class, 'finalize'])
            ->name('stock_opnames.finalize');
    });

Route::middleware(['web', 'auth'])
    ->prefix('inventory')
    ->name('inventory.')
    ->group(function () {
        // ... route inventory lain (stock_card, transfers, dll)

        Route::get('adjustments', [InventoryAdjustmentController::class, 'index'])
            ->name('adjustments.index');

        Route::get('adjustments/{inventoryAdjustment}', [InventoryAdjustmentController::class, 'show'])
            ->name('adjustments.show');
    });
