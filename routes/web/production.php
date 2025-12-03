<?php

use App\Http\Controllers\Production\CuttingJobController;
use App\Http\Controllers\Production\FinishingJobController;
use App\Http\Controllers\Production\PackingJobController;
use App\Http\Controllers\Production\ProductionReportController;
use App\Http\Controllers\Production\QcController;
use App\Http\Controllers\Production\SewingPickupController;
use App\Http\Controllers\Production\SewingReportController;
use App\Http\Controllers\Production\SewingReturnController;

Route::middleware(['web', 'auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PRODUCTION NAMESPACE
    |--------------------------------------------------------------------------
     */
    Route::prefix('production')
        ->name('production.')
        ->group(function () {

            /*
        |--------------------------------------------------------------------------
        | CUTTING JOBS
        |--------------------------------------------------------------------------
         */
            Route::prefix('cutting-jobs')
                ->name('cutting_jobs.')
                ->group(function () {

                    Route::get('/', [CuttingJobController::class, 'index'])->name('index');
                    Route::get('/create', [CuttingJobController::class, 'create'])->name('create');
                    Route::post('/', [CuttingJobController::class, 'store'])->name('store');

                    Route::get('/{cuttingJob}', [CuttingJobController::class, 'show'])->name('show');

                    Route::get('/{cuttingJob}/edit', [CuttingJobController::class, 'edit'])->name('edit');
                    Route::put('/{cuttingJob}', [CuttingJobController::class, 'update'])->name('update');

                    Route::post('/{cuttingJob}/send-to-qc', [CuttingJobController::class, 'sendToQc'])
                        ->name('send_to_qc');
                });

            /*
        |--------------------------------------------------------------------------
        | QC (Cutting / Sewing / Packing overview)
        |--------------------------------------------------------------------------
         */
            // Overview QC semua stage
            Route::get('/qc', [QcController::class, 'index'])
                ->name('qc.index');

            Route::prefix('qc')
                ->name('qc.')
                ->group(function () {
                    // QC Cutting
                    Route::get('/cutting/{cuttingJob}/edit', [QcController::class, 'editCutting'])
                        ->name('cutting.edit');

                    Route::put('/cutting/{cuttingJob}', [QcController::class, 'updateCutting'])
                        ->name('cutting.update');

                    // (nanti bisa tambah qc.sewing.*, qc.packing.*, ...)
                });

            /*
        |--------------------------------------------------------------------------
        | SEWING (Pickups + Returns + Sewing-only Reports)
        |--------------------------------------------------------------------------
         */
            Route::prefix('sewing')->group(function () {

                /*
            |------------------------------
            | SEWING PICKUPS
            |------------------------------
             */
                Route::prefix('pickups')
                    ->name('sewing_pickups.')
                    ->group(function () {

                        Route::get('/', [SewingPickupController::class, 'index'])->name('index');
                        Route::get('/bundles-ready', [SewingPickupController::class, 'bundlesReady'])
                            ->name('bundles_ready');

                        Route::get('/create', [SewingPickupController::class, 'create'])->name('create');
                        Route::post('/', [SewingPickupController::class, 'store'])->name('store');

                        Route::get('/{pickup}', [SewingPickupController::class, 'show'])->name('show');
                        Route::get('/{pickup}/edit', [SewingPickupController::class, 'edit'])->name('edit');
                        Route::put('/{pickup}', [SewingPickupController::class, 'update'])->name('update');
                        Route::delete('/{pickup}', [SewingPickupController::class, 'destroy'])->name('destroy');
                    });

                /*
            |------------------------------
            | SEWING RETURNS
            |------------------------------
             */
                Route::prefix('returns')
                    ->name('sewing_returns.')
                    ->group(function () {

                        Route::get('/', [SewingReturnController::class, 'index'])->name('index');
                        Route::get('/create', [SewingReturnController::class, 'create'])->name('create');
                        Route::post('/', [SewingReturnController::class, 'store'])->name('store');
                        Route::get('/{return}', [SewingReturnController::class, 'show'])->name('show');
                        Route::delete('/{return}', [SewingReturnController::class, 'destroy'])->name('destroy');
                    });

                /*
            |------------------------------
            | SEWING REPORTS (khusus jahit)
            | URL: /production/sewing/reports/...
            | Name: production.sewing.reports.*
            |------------------------------
             */
                Route::prefix('reports')
                    ->name('reports.')
                    ->group(function () {

                        // Operator dashboard summary
                        Route::get('/operators', [SewingReportController::class, 'operatorSummary'])
                            ->name('operators');

                        // Not Yet Returned / Outstanding Report
                        Route::get('/outstanding', [SewingReportController::class, 'outstanding'])
                            ->name('outstanding');

                        // Aging WIP-SEW
                        Route::get('/aging-wip-sew', [SewingReportController::class, 'agingWipSew'])
                            ->name('aging_wip_sew');

                        // Productivity per Operator
                        Route::get('/productivity', [SewingReportController::class, 'productivity'])
                            ->name('productivity');

                        // Partial Pickup Report
                        Route::get('/partial-pickup', [SewingReportController::class, 'partialPickup'])
                            ->name('partial_pickup');

                        // Reject Sewing Analysis
                        Route::get('/reject-analysis', [SewingReportController::class, 'rejectAnalysis'])
                            ->name('report_reject');

                        // Daily Sewing Dashboard
                        Route::get('/dashboard', [SewingReportController::class, 'dailyDashboard'])
                            ->name('dashboard');

                        // Lead Time Sewing (Pickup → Return)
                        Route::get('/lead-time', [SewingReportController::class, 'leadTime'])
                            ->name('lead_time');

                        Route::get('/operator-behavior', [SewingReportController::class, 'operatorBehavior'])
                            ->name('operator_behavior');

                        // ❌ DULU di sini ada item-chain pakai ProductionReportController
                        //    Sekarang dipindah ke /production/reports (lihat di bawah).
                    });
            });

            /*
        |--------------------------------------------------------------------------
        | FINISHING JOBS + REPORTS
        |--------------------------------------------------------------------------
         */

            // Action khusus untuk POST & UNPOST (jalanin inventory)
            Route::post('finishing_jobs/{finishing_job}/post', [FinishingJobController::class, 'post'])
                ->name('finishing_jobs.post');

            Route::post('finishing_jobs/{finishing_job}/unpost', [FinishingJobController::class, 'unpost'])
                ->name('finishing_jobs.unpost');

            Route::get('finishing_jobs/bundles-ready', [FinishingJobController::class, 'readyBundles'])
                ->name('finishing_jobs.bundles_ready');

            Route::resource('finishing_jobs', FinishingJobController::class)
                ->except(['destroy']);

            // Report Finishing per Item (header)
            Route::get('finishing_jobs/report/per-item', [FinishingJobController::class, 'reportPerItem'])
                ->name('finishing_jobs.report_per_item');

            // Drilldown: detail per item → list finishing job
            Route::get('finishing_jobs/report/per-item/{item}', [FinishingJobController::class, 'reportPerItemDetail'])
                ->name('finishing_jobs.report_per_item_detail');

            /*
        |--------------------------------------------------------------------------
        | PACKING (status + WH-PRD)
        |--------------------------------------------------------------------------
         */

            // Daftar item WH-PRD yang siap di-packing
            Route::get('packing/ready-items', [PackingJobController::class, 'readyItems'])
                ->name('packing_jobs.ready_items');

            Route::resource('packing_jobs', PackingJobController::class)
                ->except(['destroy']);

            Route::post('packing_jobs/{packing_job}/post', [PackingJobController::class, 'post'])
                ->name('packing_jobs.post');

            Route::post('packing_jobs/{packing_job}/unpost', [PackingJobController::class, 'unpost'])
                ->name('packing_jobs.unpost');

            /*
        |--------------------------------------------------------------------------
        | PRODUCTION-WIDE REPORTS (CHAIN, DAILY, LOSS, DLL)
        | URL:  /production/reports/...
        | Name: production.reports.*
        |--------------------------------------------------------------------------
         */
            Route::prefix('reports')
                ->name('reports.')
                ->group(function () {

                    // 📅 Daily Production Summary
                    Route::get('daily-production', [ProductionReportController::class, 'dailyProduction'])
                        ->name('daily_production');

                    // ❌ Reject Detail (Cutting + Sewing)
                    Route::get('reject-detail', [ProductionReportController::class, 'rejectDetail'])
                        ->name('reject_detail');

                    // 🧵 WIP Sewing Age (versi report produksi, beda dari dashboard sewing)
                    Route::get('wip-sewing-age', [ProductionReportController::class, 'wipSewingAge'])
                        ->name('wip_sewing_age');

                    // 🧵 Sewing per Item Jadi
                    Route::get('sewing-per-item', [ProductionReportController::class, 'sewingPerItem'])
                        ->name('sewing_per_item');

                    // 🎯 Finishing Jobs Summary
                    Route::get('finishing-jobs', [ProductionReportController::class, 'finishingJobs'])
                        ->name('finishing_jobs');

                    // (opsional nanti:) Cutting → Sewing Loss
                    // Route::get('cutting-to-sewing-loss', [ProductionReportController::class, 'cuttingToSewingLoss'])
                    //      ->name('cutting_to_sewing_loss');
                });
        });
});
