
<?php

use App\Http\Controllers\Production\CuttingJobController;
use App\Http\Controllers\Production\QcController;
use App\Http\Controllers\Production\SewingPickupController;
use App\Http\Controllers\Production\SewingReturnController;

Route::middleware(['auth'])->group(function () {
    Route::prefix('production/cutting-jobs')
        ->name('production.cutting_jobs.')
        ->group(function () {

            Route::get('/', [CuttingJobController::class, 'index'])
                ->name('index');

            Route::get('/create', [CuttingJobController::class, 'create'])
                ->name('create');

            Route::post('/', [CuttingJobController::class, 'store'])
                ->name('store');

            Route::get('/{cuttingJob}', [CuttingJobController::class, 'show'])
                ->name('show');

            Route::post('/{cuttingJob}/send-to-qc', [CuttingJobController::class, 'sendToQc'])
                ->name('send_to_qc');

            // >>> EDIT & UPDATE HASIL CUTTING <<<
            Route::get('/{cuttingJob}/edit', [CuttingJobController::class, 'edit'])
                ->name('edit');

            Route::put('/{cuttingJob}', [CuttingJobController::class, 'update'])
                ->name('update');
        });
});

Route::middleware(['auth'])->group(function () {
    // INDEX QC SEMUA STAGE
    Route::get('/production/qc', [QcController::class, 'index'])
        ->name('production.qc.index');

    // QC Cutting existing (punyamu sekarang)
    Route::prefix('production/qc')
        ->name('production.qc.')
        ->group(function () {
            Route::get('/cutting/{cuttingJob}/edit', [QcController::class, 'editCutting'])
                ->name('cutting.edit');

            Route::put('/cutting/{cuttingJob}', [QcController::class, 'updateCutting'])
                ->name('cutting.update');
        });
});

Route::prefix('production/sewing/pickups')
    ->name('production.sewing_pickups.')
    ->middleware(['auth'])
    ->group(function () {

        Route::get('/', [SewingPickupController::class, 'index'])
            ->name('index');

        Route::get('/bundles-ready', [SewingPickupController::class, 'bundlesReady'])
            ->name('bundles_ready');

        Route::get('/create', [SewingPickupController::class, 'create'])
            ->name('create');

        Route::post('/', [SewingPickupController::class, 'store'])
            ->name('store');

        Route::get('/{pickup}', [SewingPickupController::class, 'show'])
            ->name('show');

        Route::get('/{pickup}/edit', [SewingPickupController::class, 'edit'])
            ->name('edit');

        Route::put('/{pickup}', [SewingPickupController::class, 'update'])
            ->name('update');

        Route::delete('/{pickup}', [SewingPickupController::class, 'destroy'])
            ->name('destroy');

    });

Route::prefix('production/sewing/returns')
    ->name('production.sewing_returns.')
    ->middleware(['auth'])
    ->group(function () {

        Route::get('/', [SewingReturnController::class, 'index'])
            ->name('index'); // optional, nanti bisa buat index return

        Route::get('/create', [SewingReturnController::class, 'create'])
            ->name('create'); // perlu ?pickup_id=xx

        Route::post('/', [SewingReturnController::class, 'store'])
            ->name('store');

        Route::get('/{return}', [SewingReturnController::class, 'show'])
            ->name('show'); // laporan return

        Route::delete('/{return}', [SewingReturnController::class, 'destroy'])
            ->name('destroy');
    });
